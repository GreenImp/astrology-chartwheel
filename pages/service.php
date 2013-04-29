<?php
/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 19/04/13 10:54
 */

global $astrologyPlugin;
$formValidation = $astrologyPlugin->library('FormValidation');

if(isset($_POST['chartSubmit'])){
	// form has been submitted

	$rules = array(
		array(
			'field'	=> 'fName',
			'label'	=> 'First name',
			'rules'	=> 'trim|required'
		),
		array(
			'field'	=> 'lName',
			'label'	=> 'Last Name',
			'rules'	=> 'trim'
		),
		array(
			'field'	=> 'sex',
			'label'	=> 'Sex',
			'rules'	=> 'trim|required|callback_is_gender'
		),
		array(
			'field'	=> 'dob',
			'label'	=> 'Date of Birth',
			'rules'	=> 'trim|required|is_date[DD/MM/YYYY]'
		),
		array(
			'field'	=> 'tob',
			'label'	=> 'Time of Birth',
			'rules'	=> 'trim|is_time_24'
		),
		array(
			'field'	=> 'tobUnknown',
			'label'	=> 'Time of Birth Unknown',
			'rules'	=> ''
		),
		array(
			'field'	=> 'birthTown',
			'label'	=> 'Birth Town',
			'rules'	=> 'trim|required'
		),
		array(
			'field'	=> 'birthCountry',
			'label'	=> 'Birth Country',
			'rules'	=> 'trim|required'
		),
		array(
			'field'	=> 'birthState',
			'label'	=> 'Birth State',
			'rules'	=> 'trim'
		)
	);

	// validate the form
	if(FormValidation::validate($rules)){
		// form submitted successfully

		list($d, $m, $y) = explode('/', $_POST['dob']);
		$dob = $y . '-' . $m . '-' . $d;

		if(!is_null($location = $astrologyPlugin->getLocationCode($_POST['birthTown'], $_POST['birthCountry'], $_POST['birthState']))){
			// set the entered birth town to the full valid version
			$_POST['birthTown'] = $formValidation->prep_for_form($location->NameWithRegion);

			$chartData = $astrologyPlugin->getChart(array(
				array(
					'firstName'		=> $_POST['fName'],
					'lastName'		=> isset($_POST['lName']) ? $_POST['lName'] : '',
					'sex'			=> $_POST['sex'],
					'dob'			=> $dob,
					'tob'			=> isset($_POST['tob']) ? $_POST['tob'] : '',
					'tobUnknown'	=> isset($_POST['tobUnknown']) ? 1 : 0,
					'locationCode'	=> $location->LocationCode
				)
			));

			if(is_null($chartData)){
				// error getting data
				// loop through each error and add it to the list
				foreach($astrologyPlugin->getErrors() as $error){
					Message::add('error', $error['message']);
				}
			}
		}else{
			// error getting location
			// loop through each error and add it to the list
			foreach($astrologyPlugin->getErrors() as $error){
				Message::add('error', $error['message']);
			}
		}
	}elseif(count($errors = FormValidation::getErrors()) > 0){
		// errors exist - output them to the user
		// loop through each error and add it to the list
		foreach($errors as $error){
			Message::add('error', $error);
		}
	}
}

// output any messages
Message::show();

if(isset($chartData) && !is_null($chartData)){
	$people = $chartData->people;
	$planets = $chartData->planet_data;
?>
<div id="chartResults">
	<h2>Your results</h2>

	<?php if(isset($chartData->images[0]->image->data) && !empty($chartData->images[0]->image->data)){ ?>
	<img src="data:image/png;base64, <?php echo $chartData->images[0]->image->data; ?>" alt="Astrology Chart results">
	<?php } ?>

	<div class="people">
		<ul>
			<?php foreach($people as $person){ ?>
			<li class="person">
				<h3>Details</h3>

				<dl>
					<dt><strong>Name</strong></dt>
					<dd><?php echo $person->first_name . ' ' . $person->last_name; ?></dd>

					<dt><strong>Born</strong></dt>
					<dd>
						<?php echo date('l jS F Y h:i A', strtotime($person->birth_date)); ?>
					</dd>
					<dd>
						<?php echo $person->birth_location; ?>
					</dd>

					<dt><strong>Birth Latitude</strong></dt>
					<dd>
						<?php echo $person->birth_lat; ?>
					</dd>

					<dt><strong>Birth Longitude</strong></dt>
					<dd>
						<?php echo $person->birth_long; ?>
					</dd>
				</dl>


				<h3>Planets</h3>

				<?php
				foreach($chartData->planet_data[0] as $planet){
					if((int) $planet->person == (int) $person->person_number){
				?>
				<dl style="float:left; margin:10px 20px;">
					<dt><strong>Planet</strong></dt>
					<dd><?php echo $planet['name']; ?></dd>

					<dt><strong>Sign</strong></dt>
					<dd><?php echo $planet->sign; ?></dd>

					<dt><strong>House</strong></dt>
					<dd><?php echo $planet->house; ?></dd>

					<dt><strong>Position</strong></dt>
					<dd><?php echo $planet->position; ?></dd>
				</dl>
				<?php
					}
				}
				?>
			</li>
			<?php } ?>
		</ul>
	</div>
</div>
<?php
}
?>
<form action="<?php echo $currentURL; ?>" method="post">
	<fieldset>
		<legend>Personal details</legend>

		<dl>
			<dt><label for="personFName">First Name</label></dt>
			<dd>
				<input type="text" name="fName" value="<?php echo $formValidation->getValue('fName'); ?>" placeholder="First Name" required id="personFName">
			</dd>

			<dt><label for="personLName">Last Name</label></dt>
			<dd>
				<input type="text" name="lName" value="<?php echo $formValidation->getValue('lName'); ?>" placeholder="Last Name" id="personLName">
			</dd>

			<dt><label for="personSex">Sex</label></dt>
			<dd>
				<select name="sex" required id="personSex">
					<option value="">Select...</option>
					<option value="M" <?php echo $formValidation->getSelect('sex', 'M'); ?>>Male</option>
					<option value="F" <?php echo $formValidation->getSelect('sex', 'F'); ?>>Female</option>
				</select>
			</dd>

			<dt><label for="personDOB">Date of Birth</label></dt>
			<dd>
				<input type="date" name="dob" value="<?php echo $formValidation->getValue('dob'); ?>" placeholder="dd/mm/yyyy" required id="personDOB">
			</dd>

			<dt><label for="personTOB">Time of Birth</label></dt>
			<dd>
				<input type="time" name="tob" value="<?php echo $formValidation->getValue('tob'); ?>" placeholder="hh:mm" id="personTOB">
			</dd>
			<dd>
				<label for="personTOBUnknown">
					Unknown
					<input type="checkbox" name="tobUnknown" value="1" <?php echo $formValidation->getCheckbox('tobUnknown', '1'); ?> id="personTOBUnknown">
				</label>
			</dd>
		</dl>
	</fieldset>

	<fieldset>
		<legend>Place of Birth</legend>

		<dt><label for="personBirthTown">Town/City</label></dt>
		<dd>
			<input type="text" name="birthTown" value="<?php echo $formValidation->getValue('birthTown'); ?>" placeholder="Town/City" required id="personBirthTown">
		</dd>

		<dt><label for="personBirthCountry">Country</label></dt>
		<dd>
			<select name="birthCountry" required id="personBirthCountry">
				<option value="">Select Country...</option>

				<?php
				foreach($astrologyPlugin->getCountries() as $country){
					$code = $formValidation->prep_for_form($country->code)
				?>
				<option value="<?php echo $code; ?>" <?php echo $formValidation->getSelect('birthCountry', $code); ?>><?php echo $formValidation->prep_for_form($country->name); ?></option>
				<?php } ?>
			</select>
		</dd>

		<dt><label for="personBirthState">US State</label></dt>
		<dd>
			<select name="birthState" id="personBirthState">
				<option value="">Select State (US Only)...</option>

				<?php
				foreach($astrologyPlugin->getStates() as $state){
					$code = $formValidation->prep_for_form($state->code)
				?>
				<option value="<?php echo $code; ?>" <?php echo $formValidation->getSelect('birthState', $code); ?>><?php echo $formValidation->prep_for_form($state->name); ?></option>
				<?php } ?>
			</select>
		</dd>
	</fieldset>

	<input type="submit" name="chartSubmit" value="Submit">
</form>