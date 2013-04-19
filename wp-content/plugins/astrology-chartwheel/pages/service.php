<?php
/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 19/04/13 10:54
 */

global $astrologyPlugin;

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
			'field'	=> 'location',
			'label'	=> 'Location',
			'rules'	=> 'trim|required'
		)
	);

	// validate the form
	if(FormValidation::validate($rules)){
		// form submitted successfully

		list($d, $m, $y) = explode('/', $_POST['dob']);
		$dob = $y . '-' . $m . '-' . $d;

		$requestData = $astrologyPlugin->getRequest(array(
			array(
				'firstName'		=> $_POST['fName'],
				'lastName'		=> isset($_POST['lName']) ? $_POST['lName'] : '',
				'sex'			=> $_POST['sex'],
				'dob'			=> $dob,
				'tob'			=> isset($_POST['tob']) ? $_POST['tob'] : '',
				'tobUnknown'	=> isset($_POST['tobUnknown']) ? 1 : 0,
				'location'		=> $_POST['location']
			)
		));

		if(is_null($requestData)){
			Message::add('error', 'An error has occurred, please try again');
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

if(isset($requestData) && !is_null($requestData)){
?>
<div id="chartResults">
	<h3>Your results</h3>

	<img src="data:image/png;base64, <?php echo $requestData->images[0]->image->data; ?>" alt="Astrology Chart results">
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
				<input type="text" name="fName" value="" placeholder="First Name" required id="personFName">
			</dd>

			<dt><label for="personLName">Last Name</label></dt>
			<dd>
				<input type="text" name="lName" value="" placeholder="Last Name" id="personLName">
			</dd>

			<dt><label for="personSex">Sex</label></dt>
			<dd>
				<select name="sex" required id="personSex">
					<option value="">Select...</option>
					<option value="M">Male</option>
					<option value="F">Female</option>
				</select>
			</dd>

			<dt><label for="personDOB">Date of Birth</label></dt>
			<dd>
				<input type="date" name="dob" value="" placeholder="dd/mm/yyyy" required id="personDOB">
			</dd>

			<dt><label for="personTOB">Time of Birth</label></dt>
			<dd>
				<input type="time" name="tob" value="" placeholder="hh:mm" id="personTOB">
			</dd>
			<dd>
				<label for="personTOBUnknown">
					Unknown
					<input type="checkbox" name="tobUnknown" value="1" id="personTOBUnknown">
				</label>
			</dd>

			<dt><label for="personLocation">Location</label></dt>
			<dd>
				<input type="text" name="location" value="" placeholder="Location" required id="personLocation">
			</dd>
		</dl>

		<input type="submit" name="chartSubmit" value="Submit">
	</fieldset>
</form>