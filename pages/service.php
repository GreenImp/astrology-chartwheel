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

				<option value="AF">Afghanistan</option>
				<option value="AL">Albania</option>
				<option value="DZ">Algeria</option>
				<option value="AD">Andorra</option>
				<option value="AO">Angola</option>
				<option value="AI">Anguilla</option>
				<option value="AQ">Antarctica</option>
				<option value="AG">Antigua and Barbuda</option>
				<option value="AR">Argentina</option>
				<option value="AM">Armenia</option>
				<option value="AW">Aruba</option>
				<option value="AU">Australia</option>
				<option value="AT">Austria</option>
				<option value="AZ">Azerbaijan</option>
				<option value="BH">Bahrain</option>
				<option value="BD">Bangladesh</option>
				<option value="BB">Barbados</option>
				<option value="BY">Belarus</option>
				<option value="BE">Belgium</option>
				<option value="BZ">Belize</option>
				<option value="BJ">Benin</option>
				<option value="BM">Bermuda</option>
				<option value="BT">Bhutan</option>
				<option value="BO">Bolivia</option>
				<option value="BA">Bosnia and Herzegovina</option>
				<option value="BW">Botswana</option>
				<option value="BV">Bouvet Island</option>
				<option value="BR">Brazil</option>
				<option value="IO">British Indian Ocean Territory</option>
				<option value="VG">British Virgin Islands</option>
				<option value="BN">Brunei</option>
				<option value="BG">Bulgaria</option>
				<option value="BF">Burkina Faso</option>
				<option value="BI">Burundi</option>
				<option value="KH">Cambodia</option>
				<option value="CM">Cameroon</option>
				<option value="CA">Canada</option>
				<option value="CV">Cape Verde</option>
				<option value="KY">Cayman Islands</option>
				<option value="CF">Central African Republic</option>
				<option value="TD">Chad</option>
				<option value="CL">Chile</option>
				<option value="CN">China</option>
				<option value="CX">Christmas Island</option>
				<option value="CC">Cocos (Keeling) Islands</option>
				<option value="CO">Colombia</option>
				<option value="KM">Comoros</option>
				<option value="CG">Congo (Brazzaville)</option>
				<option value="CD">Congo (Kinshasa)</option>
				<option value="CK">Cook Islands</option>
				<option value="CR">Costa Rica</option>
				<option value="CI">Cote D'Ivoire</option>
				<option value="HR">Croatia</option>
				<option value="CU">Cuba</option>
				<option value="CY">Cyprus</option>
				<option value="CZ">Czech Republic</option>
				<option value="DK">Denmark</option>
				<option value="DJ">Djibouti</option>
				<option value="DM">Dominica</option>
				<option value="DO">Dominican Republic</option>
				<option value="TL">East Timor</option>
				<option value="EC">Ecuador</option>
				<option value="EG">Egypt</option>
				<option value="SV">El Salvador</option>
				<option value="GQ">Equatorial Guinea</option>
				<option value="ER">Eritrea</option>
				<option value="EE">Estonia</option>
				<option value="ET">Ethiopia</option>
				<option value="FK">Falkland Islands (Islas Malvinas)</option>
				<option value="FO">Faroe Islands</option>
				<option value="FJ">Fiji</option>
				<option value="FI">Finland</option>
				<option value="FR">France</option>
				<option value="GF">French Guiana</option>
				<option value="PF">French Polynesia</option>
				<option value="TF">French Southern and Antarctic Lands</option>
				<option value="GA">Gabon</option>
				<option value="GE">Georgia</option>
				<option value="DE">Germany</option>
				<option value="GH">Ghana</option>
				<option value="GI">Gibraltar</option>
				<option value="GR">Greece</option>
				<option value="GL">Greenland</option>
				<option value="GD">Grenada</option>
				<option value="GP">Guadeloupe</option>
				<option value="GT">Guatemala</option>
				<option value="GG">Guernsey</option>
				<option value="GN">Guinea</option>
				<option value="GW">Guinea-Bissau</option>
				<option value="GY">Guyana</option>
				<option value="HT">Haiti</option>
				<option value="HM">Heard Island and McDonald Islands</option>
				<option value="VA">Holy See (Vatican City)</option>
				<option value="HN">Honduras</option>
				<option value="HK">Hong Kong</option>
				<option value="HU">Hungary</option>
				<option value="IS">Iceland</option>
				<option value="IN">India</option>
				<option value="ID">Indonesia</option>
				<option value="IR">Iran</option>
				<option value="IQ">Iraq</option>
				<option value="IE">Ireland</option>
				<option value="IM">Isle of Man</option>
				<option value="IL">Israel</option>
				<option value="IT">Italy</option>
				<option value="JM">Jamaica</option>
				<option value="SJ">Jan Mayen</option>
				<option value="JP">Japan</option>
				<option value="JE">Jersey</option>
				<option value="JO">Jordan</option>
				<option value="KZ">Kazakhstan</option>
				<option value="KE">Kenya</option>
				<option value="KI">Kiribati</option>
				<option value="KW">Kuwait</option>
				<option value="KG">Kyrgyzstan</option>
				<option value="LA">Laos</option>
				<option value="LV">Latvia</option>
				<option value="LB">Lebanon</option>
				<option value="LS">Lesotho</option>
				<option value="LR">Liberia</option>
				<option value="LY">Libya</option>
				<option value="LI">Liechtenstein</option>
				<option value="LT">Lithuania</option>
				<option value="LU">Luxembourg</option>
				<option value="MO">Macau</option>
				<option value="MK">Macedonia</option>
				<option value="MG">Madagascar</option>
				<option value="MW">Malawi</option>
				<option value="MY">Malaysia</option>
				<option value="MV">Maldives</option>
				<option value="ML">Mali</option>
				<option value="MT">Malta</option>
				<option value="MQ">Martinique</option>
				<option value="MR">Mauritania</option>
				<option value="MU">Mauritius</option>
				<option value="YT">Mayotte</option>
				<option value="MX">Mexico</option>
				<option value="FM">Micronesia,Federated States of</option>
				<option value="MD">Moldova</option>
				<option value="MC">Monaco</option>
				<option value="MN">Mongolia</option>
				<option value="ME">Montenegro</option>
				<option value="MS">Montserrat</option>
				<option value="MA">Morocco</option>
				<option value="MZ">Mozambique</option>
				<option value="MM">Myanmar (Burma)</option>
				<option value="NA">Namibia</option>
				<option value="NR">Nauru</option>
				<option value="NP">Nepal</option>
				<option value="NL">Netherlands</option>
				<option value="AN">Netherlands Antilles</option>
				<option value="NC">New Caledonia</option>
				<option value="NZ">New Zealand</option>
				<option value="NI">Nicaragua</option>
				<option value="NE">Niger</option>
				<option value="NG">Nigeria</option>
				<option value="NU">Niue</option>
				<option value="NF">Norfolk Island</option>
				<option value="KP">North Korea</option>
				<option value="NO">Norway</option>
				<option value="OM">Oman</option>
				<option value="PK">Pakistan</option>
				<option value="PW">Paluau</option>
				<option value="PA">Panama</option>
				<option value="PG">Papua New Guinea</option>
				<option value="PY">Paraguay</option>
				<option value="PE">Peru</option>
				<option value="PH">Philippines</option>
				<option value="PN">Pitcairn Islands</option>
				<option value="PL">Poland</option>
				<option value="PT">Portugal</option>
				<option value="QA">Qatar</option>
				<option value="RE">Reunion</option>
				<option value="RO">Romania</option>
				<option value="RU">Russia</option>
				<option value="RW">Rwanda</option>
				<option value="SH">Saint Helena</option>
				<option value="KN">Saint Kitts and Nevis</option>
				<option value="LC">Saint Lucia</option>
				<option value="PM">Saint Pierre and Miquelon</option>
				<option value="VC">Saint Vincent and the Grenadines</option>
				<option value="WS">Samoa</option>
				<option value="SM">San Marino</option>
				<option value="ST">Sao Tome and Principe</option>
				<option value="SA">Saudi Arabia</option>
				<option value="SN">Senegal</option>
				<option value="RS">Serbia</option>
				<option value="SC">Seychelles</option>
				<option value="SL">Sierra Leone</option>
				<option value="SG">Singapore</option>
				<option value="SK">Slovakia</option>
				<option value="SI">Slovenia</option>
				<option value="SB">Solomon Islands</option>
				<option value="SO">Somalia</option>
				<option value="ZA">South Africa</option>
				<option value="GS">South Georgia and the South Sandwich Islands</option>
				<option value="KR">South Korea</option>
				<option value="ES">Spain</option>
				<option value="LK">Sri Lanka</option>
				<option value="SD">Sudan</option>
				<option value="SR">Suriname</option>
				<option value="SJ">Svalbard</option>
				<option value="SZ">Swaziland</option>
				<option value="SE">Sweden</option>
				<option value="CH">Switzerland</option>
				<option value="SY">Syria</option>
				<option value="TW">Taiwan</option>
				<option value="TJ">Tajikistan</option>
				<option value="TZ">Tanzania</option>
				<option value="TH">Thailand</option>
				<option value="BS">The Bahamas</option>
				<option value="GM">The Gambia</option>
				<option value="TG">Togo</option>
				<option value="TK">Tokelau</option>
				<option value="TO">Tonga</option>
				<option value="TT">Trinidad and Tobago</option>
				<option value="TN">Tunisia</option>
				<option value="TR">Turkey</option>
				<option value="TM">Turkmenistan</option>
				<option value="TC">Turks and Caicos Islands</option>
				<option value="TV">Tuvalu</option>
				<option value="UG">Uganda</option>
				<option value="UA">Ukraine</option>
				<option value="AE">United Arab Emirates</option>
				<option value="GB">United Kingdom</option>
				<option value="UY">Uruguay</option>
				<option value="UZ">Uzbekistan</option>
				<option value="VU">Vanuatu</option>
				<option value="VE">Venezuela</option>
				<option value="VN">Vietnam</option>
				<option value="WF">Wallis and Futuna</option>
				<option value="EH">Western Sahara</option>
				<option value="YE">Yemen</option>
				<option value="ZM">Zambia</option>
				<option value="ZW">Zimbabwe</option>
			</select>
		</dd>

		<dt><label for="personBirthState">US State</label></dt>
		<dd>
			<select name="birthState" id="personBirthState">
				<option value="">Select State (US Only)...</option>

				<option value="US-AL">Alabama</option>
				<option value="US-AK">Alaska</option>
				<option value="US-AZ">Arizona</option>
				<option value="US-AR">Arkansas</option>
				<option value="US-CA">California</option>
				<option value="US-CO">Colorado</option>
				<option value="US-CT">Connecticut</option>
				<option value="US-DE">Delaware</option>
				<option value="US-FL">Florida</option>
				<option value="US-GA">Georgia</option>
				<option value="US-HI">Hawaii</option>
				<option value="US-ID">Idaho</option>
				<option value="US-IL">Illinois</option>
				<option value="US-IN">Indiana</option>
				<option value="US-IA">Iowa</option>
				<option value="US-KS">Kansas</option>
				<option value="US-KY">Kentucky</option>
				<option value="US-LA">Louisiana</option>
				<option value="US-ME">Maine</option>
				<option value="US-MD">Maryland</option>
				<option value="US-MA">Massachusetts</option>
				<option value="US-MI">Michigan</option>
				<option value="US-MN">Minnesota</option>
				<option value="US-MS">Mississippi</option>
				<option value="US-MO">Missouri</option>
				<option value="US-MT">Montana</option>
				<option value="US-NE">Nebraska</option>
				<option value="US-NV">Nevada</option>
				<option value="US-NH">New Hampshire</option>
				<option value="US-NJ">New Jersey</option>
				<option value="US-NM">New Mexico</option>
				<option value="US-NY">New York</option>
				<option value="US-NC">North Carolina</option>
				<option value="US-ND">North Dakota</option>
				<option value="US-OH">Ohio</option>
				<option value="US-OK">Oklahoma</option>
				<option value="US-OR">Oregon</option>
				<option value="US-PA">Pennsylvania</option>
				<option value="US-RI">Rhode Island</option>
				<option value="US-SC">South Carolina</option>
				<option value="US-SD">South Dakota</option>
				<option value="US-TN">Tennessee</option>
				<option value="US-TX">Texas</option>
				<option value="US-UT">Utah</option>
				<option value="US-VT">Vermont</option>
				<option value="US-VA">Virginia</option>
				<option value="US-WA">Washington</option>
				<option value="US-WV">West Virginia</option>
				<option value="US-WI">Wisconsin</option>
				<option value="US-WY">Wyoming</option>
				<option value="US-DC">District of Columbia</option>
				<option value="US-AS">American Samoa</option>
				<option value="US-GU">Guam</option>
				<option value="US-MH">Marshall Islands</option>
				<option value="US-MP">Northern Mariana Islands</option>
				<option value="US-PR">Puerto Rico</option>
				<option value="US-VI">Virgin Islands of the U.S.</option>
			</select>
		</dd>
	</fieldset>

	<input type="submit" name="chartSubmit" value="Submit">
</form>