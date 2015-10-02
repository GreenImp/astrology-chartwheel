<?php
/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 19/04/13 10:54
 */

global $astrologyPlugin;
$formValidation = $astrologyPlugin->library('FormValidation');

$locationOptions = array();
$hasChart = false;
if(isset($_POST['chartSubmit'])){
	// form has been submitted

	$rules = array(
		array(
			'field'	=> 'fName[]',
			'label'	=> 'First name',
			'rules'	=> 'trim|required'
		),
		array(
			'field'	=> 'lName[]',
			'label'	=> 'Last Name',
			'rules'	=> 'trim'
		),
		array(
			'field'	=> 'sex[]',
			'label'	=> 'Sex',
			'rules'	=> 'trim|required|callback_is_gender'
		),
		array(
			'field'	=> 'dob[]',
			'label'	=> 'Date of Birth',
			'rules'	=> 'trim|required|is_date[YYYY-MM-DD]'
		),
		array(
			'field'	=> 'tob[]',
			'label'	=> 'Time of Birth',
			'rules'	=> 'trim|is_time_24'
		),
		array(
			'field'	=> 'tobUnknown[]',
			'label'	=> 'Time of Birth Unknown',
			'rules'	=> ''
		),
		array(
			'field'	=> 'birthTown[]',
			'label'	=> 'Birth Town',
			'rules'	=> 'trim|required'
		),
		array(
			'field'	=> 'birthCountry[]',
			'label'	=> 'Birth Country',
			'rules'	=> 'trim|required'
		),
		array(
			'field'	=> 'birthState[]',
			'label'	=> 'Birth State',
			'rules'	=> 'trim'
		),
		array(
			'field'	=> 'birthLocation[]',
			'label'	=> 'Birth Location',
			'rules'	=> 'trim|is_natural_no_zero'
		)
	);

	// validate the form
	if(GreenFormValidation::validate($rules)){
		// form submitted successfully

		$error = false;
		// loop through each person and store them
		$people = array();
		foreach($_POST['fName'] as $n => $name){
			//list($d, $m, $y) = explode('/', $_POST['dob'][$n]);
			list($d, $m, $y) = explode('-', $_POST['dob'][$n]);
			$dob = $y . '-' . $m . '-' . $d;

			// get the location - usually, we'll generate this from the getLocationCode function,
			// but sometimes, if the user has selected from a choice of locations, the actual
			// location code will be supplied as birthLocation
			$location = (isset($_POST['birthLocation'][$n]) && !empty($_POST['birthLocation'][$n])) ?
							$_POST['birthLocation'][$n]
						:
							$astrologyPlugin->getLocationCode($_POST['birthTown'][$n], $_POST['birthCountry'][$n], $_POST['birthState'][$n]);

			if(is_null($location)){
				// error getting location
				$error = true;
				// loop through each error and add it to the list
				foreach($astrologyPlugin->getErrors() as $error){
					GreenMessage::add('error', $error['message']);

					if($error['code'] == 2){
						// the error is actually that the selected country has multiple results to chose from
						$locationOptions[$n] = $error['data'];
					}
				}
			}else{
				$people[] = array(
					'firstName'		=> $name,
					'lastName'		=> isset($_POST['lName'][$n]) ? $_POST['lName'][$n] : '',
					'sex'			=> $_POST['sex'][$n],
					'dob'			=> $dob,
					'tob'			=> isset($_POST['tob'][$n]) ? $_POST['tob'][$n] : '',
					'tobUnknown'	=> isset($_POST['tobUnknown'][$n]) ? 1 : 0,
					'locationCode'	=> is_object($location) ? $location->LocationCode : $location
				);
			}
		}

		if(!$error){
			// set the entered birth town to the full valid version
			if(is_null($chartData = $astrologyPlugin->getChart($people))){
				// error getting data
				// loop through each error and add it to the list
				foreach($astrologyPlugin->getErrors() as $error){
					GreenMessage::add('error', $error['message']);
				}
			}else{
				$hasChart = true;
			}
		}
	}elseif(count($errors = GreenFormValidation::getErrors()) > 0){
		// errors exist - output them to the user
		// loop through each error and add it to the list
		foreach($errors as $error){
			GreenMessage::add('error', $error);
		}
	}
}

// output any messages
GreenMessage::show();

if($hasChart){
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
				<div class="block">
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
				</div>

				<div class="block">
					<h3>Your Planets</h3>

					<table class="planets">
						<thead>
							<tr>
								<th class="planet">Planet</th>
								<th class="glyph">Glyph</th>
								<th class="sign">Sign</th>
								<th class="position">Position</th>
								<th class="house">House</th>
							</tr>
						</thead>

						<tbody>
							<?php
							foreach($chartData->planet_data[0] as $planet){
								if((int) $planet->person == (int) $person->person_number){
							?>
							<tr>
								<td class="planet"><?php echo $planet['name']; ?></td>
								<td class="glyph">
									<img src="<?php echo $astrologyPlugin->uri . 'assets/images/chart/' . $planet['planet'] . '.png'; ?>">
								</td>
								<td class="sign">
									<img src="<?php echo $astrologyPlugin->uri . 'assets/images/chart/SIGN-' . $planet->sign . '.png'; ?>">
								</td>
								<td class="position"><?php echo $planet->position; ?></td>
								<td class="house"><?php echo $planet->house; ?></td>
							</tr>
							<?php
								}
							}
							?>
						</tbody>
					</table>
				</div>
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

<form action="<?php echo $currentURL; ?>" method="post" id="chartForm" class="<?php echo $hasChart ? 'hasChart' : 'noChart'; ?>">
	<h2>Get Your <span>Astrology Chart</span></h2>

	<fieldset>
		<legend>Personal details</legend>

		<dl>
			<dt><label for="personFName">First Name</label></dt>
			<dd>
				<input type="text" name="fName[]" value="<?php echo $formValidation->getValue('fName[]'); ?>" placeholder="First Name" required id="personFName">
			</dd>

			<dt><label for="personLName">Last Name</label></dt>
			<dd>
				<input type="text" name="lName[]" value="<?php echo $formValidation->getValue('lName[]'); ?>" placeholder="Last Name" id="personLName">
			</dd>

			<dt><label for="personSex">Sex</label></dt>
			<dd>
				<select name="sex[]" required id="personSex">
					<option value="">Select...</option>
					<option value="M" <?php echo $formValidation->getSelect('sex[0]', 'M'); ?>>Male</option>
					<option value="F" <?php echo $formValidation->getSelect('sex[0]', 'F'); ?>>Female</option>
				</select>
			</dd>

			<dt><label for="personDOB">Date of Birth</label></dt>
			<dd>
				<input type="date" name="dob[]" value="<?php echo $formValidation->getValue('dob[]'); ?>" placeholder="yyyy-mm-dd" required id="personDOB">
			</dd>

			<dt><label for="personTOB">Time of Birth</label></dt>
			<dd>
				<input type="time" name="tob[]" value="<?php echo $formValidation->getValue('tob[]'); ?>" placeholder="hh:mm" id="personTOB">
			</dd>
			<dd>
				<label for="personTOBUnknown">
					Unknown
					<input type="checkbox" name="tobUnknown[]" value="1" <?php echo $formValidation->getCheckbox('tobUnknown[0]', '1'); ?> id="personTOBUnknown">
				</label>
			</dd>
		</dl>
	</fieldset>

	<fieldset>
		<legend>Place of Birth</legend>

		<dt><label for="personBirthTown">Town/City</label></dt>
		<dd>
			<?php $hasLocationOptions = isset($locationOptions[0]) && is_array($locationOptions[0]) && (count($locationOptions[0]) > 0); ?>
			<input type="<?php echo $hasLocationOptions ? 'hidden' : 'text'; ?>" name="birthTown[]" value="<?php echo $formValidation->getValue('birthTown[]'); ?>" placeholder="Town/City" required id="personBirthTown" class="town">

			<?php if($hasLocationOptions){ ?>
			<select name="birthLocation[]" multiple size="5" class="locationChoice">
				<?php foreach($locationOptions[0] as $option){ ?>
				<option value="<?php echo $option->LocationCode; ?>"><?php echo $option->NameWithRegion; ?></option>
				<?php } ?>
			</select>
			<?php } ?>
		</dd>

		<dt><label for="personBirthCountry">Country</label></dt>
		<dd>
			<select name="birthCountry[]" required id="personBirthCountry" class="country">
				<option value="">Select Country...</option>

				<?php
				foreach($astrologyPlugin->getCountries() as $country){
					$code = $formValidation->prep_for_form($country->code)
				?>
				<option value="<?php echo $code; ?>" <?php echo $formValidation->getSelect('birthCountry[0]', $code); ?>><?php echo $formValidation->prep_for_form($country->name); ?></option>
				<?php } ?>
			</select>
		</dd>

		<dt><label for="personBirthState">State</label></dt>
		<dd>
			<select name="birthState[]" id="personBirthState" class="state">
				<option value="">Select State...</option>

				<?php
				if(count($astrologyPlugin->getStates()) > 0){
					$countryCode = '';
					foreach($astrologyPlugin->getStates() as $state){
						$code = $formValidation->prep_for_form($state->code);

						if($countryCode != $state->country_code){
							if($countryCode != ''){
								echo '</optgroup>';
							}

							$countryCode = $state->country_code;
				?>
				<optgroup label="<?php echo $state->country_code; ?>">
				<?php
						}
				?>
					<option value="<?php echo $code; ?>" <?php echo $formValidation->getSelect('birthState[0]', $code); ?>><?php echo $formValidation->prep_for_form($state->name); ?></option>
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