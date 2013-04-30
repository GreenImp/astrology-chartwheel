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
	<h2>Your <span>results</span></h2>

	<?php if(isset($chartData->images[0]->image->data) && !empty($chartData->images[0]->image->data)){ ?>
	<img src="data:image/png;base64, <?php echo $chartData->images[0]->image->data; ?>" alt="Astrology Chart results">
	<?php } ?>

	<div class="people">
		<ul>
			<?php
			foreach($people as $person){
				$person = isset($person->person) ? $person->person : $person;
				$hasBirthTime = preg_match('/[0-9]{1,2}\:[0-9]{1,2}/', $person->birth_time);
			?>
			<li class="person">
				<h3>Your Details</h3>

				<dl class="details">
					<dt class="name"><strong>Name</strong></dt>
					<dd class="name"><?php echo $person->first_name . ' ' . $person->last_name; ?></dd>

					<dt class="born"><strong>Born</strong></dt>
					<dd class="bornDate">
						<?php echo date('l jS F Y' . ($hasBirthTime ? ' h:i A' : ''), strtotime($person->birth_date . ($hasBirthTime ? ' ' . $person->birth_time : ''))); ?>
					</dd>
					<dd class="bornLocation">
						<?php echo $person->birth_location; ?>
					</dd>

					<dt class="bornLat"><strong>Birth Latitude</strong></dt>
					<dd class="bornLat">
						<?php echo $person->birth_lat; ?>
					</dd>

					<dt class="bornLon"><strong>Birth Longitude</strong></dt>
					<dd class="bornLon">
						<?php echo $person->birth_long; ?>
					</dd>
				</dl>


				<h3>Your Planets</h3>

				<table class="planets">
					<thead>
						<tr>
							<th>Planets</th>
							<th>Glyph</th>
							<th>Sign</th>
							<th>Position</th>
							<th>House</th>
						</tr>
					</thead>

					<tbody>
						<?php
						foreach($chartData->planet_data[0] as $planet){
							if((int) $planet->person == (int) $person->person_number){
						?>
						<tr>
							<td><?php echo $planet['name']; ?></td>
							<td>
								<img src="<?php echo $astrologyPlugin->uri . 'assets/images/chart/' . $planet['planet'] . '.png'; ?>">
							</td>
							<td>
								<img src="<?php echo $astrologyPlugin->uri . 'assets/images/chart/SIGN-' . $planet->sign . '.png'; ?>">
							</td>
							<td><?php echo $planet->position; ?></td>
							<td><?php echo $planet->house; ?></td>
						</tr>
						<?php
							}
						}
						?>
					</tbody>
				</table>
			</li>
			<?php
			}
			?>
		</ul>
	</div>
</div>
<?php
}
?>
<form action="<?php echo $currentURL; ?>" method="post" id="chartForm">
	<h2>Get Your <span>Astrology Chart</span></h2>

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

		<dt><label for="personBirthState">State</label></dt>
		<dd>
			<select name="birthState" id="personBirthState">
				<option value="">Select State...</option>

				<?php
				if(count($astrologyPlugin->getStates()) > 0){
					$countryCode = '';
					foreach($astrologyPlugin->getStates() as $state){
						$code = $formValidation->prep_for_form($state->code);

						if($countryCode != $state->country_code){
							$countryCode = $state->country_code;

							if($countryCode != ''){
								echo '</optgroup>';
							}
				?>
				<optgroup label="<?php echo $state->country_code; ?>">
				<?php
						}
				?>
					<option value="<?php echo $code; ?>" <?php echo $formValidation->getSelect('birthState', $code); ?>><?php echo $formValidation->prep_for_form($state->name); ?></option>
				<?php
					}
				?>
				</optgroup>
				<?php
				}
				?>
			</select>
		</dd>
	</fieldset>

	<input type="submit" name="chartSubmit" value="Submit" class="submit">
</form>