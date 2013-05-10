<?php
/**
 * Author: GreenImp
 * Date Created: 06/09/2012 09:52
 */

// first check that we are NOT in a codeigniter project
// all of this is a tad fruitless if codeigniter is available
if(function_exists('get_instance') && is_a(get_instance(), 'CI_Controller')){
	// we are using codeigniter - wtf are you using this form validation class for if you have CI?!!
	$message = '';
	$message .= '<p>The GreenFormValidation class is meant to mimic the CI form validation for non-codeigniter projects (such as WordPress) and is <strong>not meant to be used within codeigniter projects</strong>, as it provides far <strong>less</strong> functionality than codeigniter\'s own form validation.</p>';
	$message .= '<p>Please use codeigniter\'s form validation, instead of this custom validation class.</p>';
	$message .= '<p>File: ' . __FILE__ . '</p>';
	die($message);
}

if(!class_exists('GreenFormValidation')){
	class GreenFormValidation{
		private static $instance = null;
		private static $db = null;

		private static $errors = array();
		private static $ruleSet = array();

		private static $fetchedInput = array();

		private static $errorMessages = array(
			'required'				=> 'The %s field is required',

			'matches'				=> 'The %s field doesn\'t match the %s field',
			'is_unique'				=> 'The %s field must be unique',

			'min_length'			=> 'The %s field must be at least %d characters long',
			'max_length'			=> 'The %s field must be no more than %d characters long',
			'exact_length'			=> 'The %s field must be exactly %d characters long',

			'valid_email'			=> 'The %s field must be a valid email address',
			'valid_emails'			=> 'The %s field must be a comma separated list of valid email address',

			'valid_ip'				=> 'The %s field must be a valid IP address',

			'alpha'					=> 'The %s field must contain only alpha characters (a-z)',
			'alpha_dash'			=> 'The %s field must contain only alpha characters, dashes and underscores (a-z, _, -)',

			'numeric'				=> 'The %f field must be numeric',
			'integer'				=> 'The %f field must be an integer',
			'decimal'				=> 'The %s field must contain a decimal number',

			'greater_than'			=> 'The %s field must be greater than %d',
			'less_than'				=> 'The %s field must be less than %d',

			'is_natural'			=> 'The %s field must be a natural number (ie; 0, 1, 2, 3, 4)',
			'is_natural_no_zero'	=> 'The %s field must be a natural number, not beginning with 0 (ie; 1, 2, 3, 4)',

			'valid_base64'			=> 'The %s field must be a valid base64 encoded string',


			'is_time'				=> 'The %s field must be a valid twelve hour time (ie; 09:45[:23], seconds are optional)',
			'is_time_24'			=> 'The %s field must be a valid twenty-four hour time (ie; 14:45[:23], seconds are optional)',

			'is_in'					=> 'The %s field value must exist',

			'is_date'				=> 'The %s field must be a date in the following format: %s'
		);

		private function __construct(){
			$this->getDB();
		}

		private function &getDB(){
			if(is_null(self::$db)){
				self::$db = new GreenFormValidationDB();
			}

			return self::$db;
		}

		public static function getInstance(){
			if(is_null(self::$instance)){
				self::$instance = new GreenFormValidation();
			}

			return self::$instance;
		}

		/**
		 * Sets the rules to the given set
		 *
		 * @static
		 * @param $rules
		 */
		public static function setRules($rules){
			self::$ruleSet = $rules;
		}

		/**
		 * Returns the current rule set
		 *
		 * @static
		 * @return array
		 */
		public static function getRules(){
			return self::$ruleSet;
		}

		/**
		 * Runs the form validation.
		 * If no rule is specified it defaults to the one
		 * set using the setRules() function.
		 * If no rules aer defined at all, false is returned.
		 *
		 * @static
		 * @param array $rules
		 * @return bool
		 */
		public static function validate($rules = array()){
			if(!empty($rules)){
				// a rule set has been given - set it
				self::setRules($rules);
			}

			// reset the errors
			self::$errors = array();

			if(isset($_POST) && is_array($_POST) && !empty(self::$ruleSet)){
				$variables = array();

				foreach(self::$ruleSet as $rule){
					if(is_array($rule) && isset($rule['rules']) && ($rule['rules'] != '')){
						// field has rules - check them
						$fieldRules = array_filter(array_unique(explode('|', $rule['rules'])));

						if(false !== ($field = self::getNameArray($rule['field']))){
							// the field name is an array
							if(
								(!isset($_POST[$field[1]]) || !is_array($_POST[$field[1]])) ||											// field not defined or is not an array
								(count($_POST[$field[1]]) == 0)	||																		// field is an empty array
								(($field[2] != '') && (!isset($_POST[$field[1]][$field[2]]) || ($_POST[$field[1]][$field[2]] == '')))	// a key is defined, but not found (or is empty)
							){
								// field not posted - check if it is required
								if(in_array('required', $fieldRules)){
									// field is required
									self::setError('required', $rule['label']);
								}

								// skip to the next field
								continue;
							}else{
								// post variable exists
								if($field[2] != ''){
									// specific key defined
									$variables[$rule['field']][] =& $_POST[$field[1]][$field[2]];
								}else{
									// no key defined - get them all
									$variables[$rule['field']] =& $_POST[$field[1]];
								}
							}
						}elseif(!isset($_POST[$rule['field']]) || ($_POST[$rule['field']] == '')){
							// field not posted - check if it is required
							if(in_array('required', $fieldRules)){
								// field is required
								self::setError('required', $rule['label']);
							}

							// skip to the next field
							continue;
						}else{
							// just a normal post variable, that exists
							$variables[$rule['field']][] =& $_POST[$rule['field']];
						}

						foreach($variables[$rule['field']] as $var){
							// get the current field value
							$val =& $var;

							if($val == ''){
								// value is empty - check if it is required
								if(in_array('required', $fieldRules)){
									// field is required
									self::setError('required', $rule['label']);
								}

								// skip to the next value
								continue;
							}

							foreach($fieldRules as $fieldRule){
								$ruleParams = array();
								if(preg_match('/(.*?)\[(.*)\]/', $fieldRule, $matches)){
									$fieldRule = $matches[1];
									$ruleParams = explode(',', $matches[2]);
								}
								array_unshift($ruleParams, $val);

								$result = true;
								if(0 === strpos($fieldRule, 'callback_')){
									// user defined callback function
									$fieldRule = substr($fieldRule, 9);
									if(function_exists($fieldRule)){
										// the user defined callback exists
										$result = call_user_func_array($fieldRule, $ruleParams);
									}
								}elseif(method_exists('GreenFormValidation', $fieldRule)){
									// a local function exists - use it
									$result = call_user_func_array(array('GreenFormValidation', $fieldRule), $ruleParams);
								}elseif(function_exists($fieldRule)){
									// a PHP function exists - use it
									$result = call_user_func_array($fieldRule, $ruleParams);
								}else{
									// no function exists
									continue;
								}

								// set the value
								$val = is_bool($result) ? $val : $result;

								// check the result
								if($result === false){
									// result was a failure - get the error message
									self::setError($fieldRule, $rule['label'], isset($ruleParams[1]) ? $ruleParams[1] : '');
								}
							}
						}
					}
				}
			}else{
				// no post variables set or no rules defined
				return false;
			}

			// returns true if no errors, false otherwise
			return empty(self::$errors);
		}

		/**
		 * Returns a list of any validation errors
		 *
		 * @static
		 * @return array
		 */
		public static function getErrors(){
			return self::$errors;
		}

		/**
		 * Adds an error to the list
		 *
		 * @param $rule
		 * @param string $label
		 */
		private static function setError($rule, $label = ''){
			if(isset(self::$errorMessages[$rule])){
				$message = self::$errorMessages[$rule];
			}else{
				$message ='The %s field contains errors';
			}

			$args = func_get_args();
			array_shift($args);
			foreach($args as $k => $arg){
				$args[$k] = htmlentities($arg);
			}

			self::$errors[] = vsprintf($message, $args);
		}

		/**
		 * Adds a message to the error message list
		 *
		 * @param $rule
		 * @param $message
		 */
		public static function set_message($rule, $message){
			self::$errorMessages[$rule] = $message;
		}



		/**
		 * Below are the form validation methods
		 * They can be called separately and used
		 * without running form validation
		 */

		/**
		 * Checks ifd the given string has a value
		 *
		 * @param $str
		 * @return bool
		 */
		public function required($str){
			return !is_array($str) ? trim($str) != '' : !empty($str);
		}

		/**
		 * Performs a regular expression match
		 * and returns true if the str matches,
		 * false otherwise
		 *
		 * @param $str
		 * @param $regex
		 * @return bool
		 */
		public function regex_match($str, $regex){
			return !!preg_match($regex, $str);
		}

		/**
		 * Checks if the given str matches the
		 * posted field
		 *
		 * @param $str
		 * @param $field
		 * @return bool
		 */
		public function matches($str, $field){
			return isset($_POST[$field]) && ($str === $_POST[$field]);
		}

		/**
		 * Checks if the given $str is
		 * unique with the supplied database
		 * field.
		 * $field must be supplied as
		 * TABLE.FIELD
		 *
		 * @param $str
		 * @param $field
		 * @return bool
		 */
		public function is_unique($str, $field){
			// only continue if the field has a separating dot
			// we are using !=, rather than !== as we don't care about type.
			// both value of false or position of 0 should both be a failure
			if(false != strpos($field, '.')){
				// ensure that we have a db connection
				if(!is_null($db =& self::getDB())){
					// split the field to get the table
					list($table, $field) = explode('.', $field);

					$notField = '';
					$notValue = '';
					if(func_num_args() >= 4){
						$notField = func_get_arg(2);
						$notValue = func_get_arg(3);
					}

					// build the sql query
					$query = "SELECT
									*
								FROM
									" . $db->escape($table) . "
								WHERE
									" . $db->escape($field) . " = '" . $db->escape($str) . "'
									" . (
										(($notField != '') && ($notValue != '')) ? "AND " . $db->escape($notField) . " != '" . $db->escape($notValue) . "'" : ""
									) . "
								LIMIT 1";
					return !$db->query($query) || ($db->numRows() == 0);
				}
			}

			return false;
		}

		/**
		 * Checks whether the given $str has a
		 * minimum length of $length
		 *
		 * @param string $str
		 * @param int $length
		 * @return bool
		 */
		public function min_length($str, $length){
			if(is_numeric($length)){
				if(function_exists('mb_strlen')){
					return mb_strlen($str) >= $length;
				}else{
					return strlen($str) >= $length;
				}
			}

			return false;
		}

		/**
		 * Checks whether the given $str has a
		 * maximum length of $length
		 *
		 * @param string $str
		 * @param int $length
		 * @return bool
		 */
		public function max_length($str, $length){
			if(is_numeric($length)){
				if(function_exists('mb_strlen')){
					return mb_strlen($str) <= $length;
				}else{
					return strlen($str) <= $length;
				}
			}

			return false;
		}

		/**
		 * Checks whether the given $str has an
		 * exact length of $length
		 *
		 * @param string $str
		 * @param int $length
		 * @return bool
		 */
		public function exact_length($str, $length){
			if(is_numeric($length)){
				if(function_exists('mb_strlen')){
					return mb_strlen($str) == $length;
				}else{
					return strlen($str) == $length;
				}
			}

			return false;
		}

		/**
		 * Checks whether the given $str is
		 * a valid email address
		 *
		 * @param $str
		 * @return bool
		 */
		public function valid_email($str){
			return !!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str);
		}

		/**
		 * Checks whether the given $str is
		 * a comma separated list of email
		 * addresses
		 *
		 * @param $str
		 * @return bool
		 */
		public function valid_emails($str){
			foreach(explode(',', $str) as $email){
				$email = trim($email);
				if(($email != '') && !self::valid_email($email)){
					return false;
				}
			}

			return true;
		}

		/**
		 * Checks if the given $str is a
		 * valid IP address
		 *
		 * @param $str
		 * @return bool
		 */
		public function valid_ip($str){
			$ipSegments = explode('.', $str);

			// IP always has 4 segments
			if(count($ipSegments) != 4){
				return false;
			}elseif($ipSegments[0][0] == '0'){
				// first character is a 0  IP cannot start with 0
				return false;
			}

			foreach($ipSegments as $segment){
				if(($segment == '') || preg_match('/[^0-9]/', $segment) || ($segment > 255) || (strlen($segment) > 3)){
					return false;
				}
			}

			return true;
		}

		/**
		 * Checks whether the string only contains
		 * alpha-numeric characters (a-z, 0-9)
		 *
		 * @param $str
		 * @return bool
		 */
		public function alpha($str){
			return !!preg_match('/^[a-z0-9]+$/i', $str);
		}

		/**
		 * Checks whether the string only contains
		 * alpha-numeric characters (a-z, 0-9), dashes
		 * and underscores
		 *
		 * @param $str
		 * @return bool
		 */
		public function alpha_dash($str){
			return !!preg_match('/^[a-z0-9\_\-]+$/i', $str);
		}

		/**
		 * Checks whether the string contains a
		 * purely numerical value.
		 * ie;
		 * 235,
		 * 12.87
		 * -78.5456
		 * +45
		 *
		 * @param $str
		 * @return bool
		 */
		public function numeric($str){
			// we can't use PHP is_numeric as it allows some aplha characters
			return !!preg_match('/^[\-+]?[0-9*]\.?[0-9]+$/', $str);
		}

		/**
		 * Checks if the string is a valid integer.
		 * ie;
		 * 234
		 * 12
		 * +34
		 * -567
		 *
		 * @param $str
		 * @return bool
		 */
		public function integer($str){
			return !!preg_match('/^[\-+]?[0-9]+$/', $str);
		}

		/**
		 * Checks if the string is a decimal number
		 *
		 * @param $str
		 * @return bool
		 */
		public function decimal($str){
			return !!preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
		}

		/**
		 * Checks whether the string is numeric and
		 * greater than the min
		 *
		 * @param $str
		 * @param $min
		 * @return bool
		 */
		public function greater_than($str, $min){
			return is_numeric($min) && is_numeric($str) && ($str > $min);
		}

		/**
		 * Checks whether the string is numeric and
		 * less than the min
		 *
		 * @param $str
		 * @param $max
		 * @return bool
		 */
		public function less_than($str, $max){
			return is_numeric($max) && is_numeric($str) && ($str < $max);
		}

		/**
		 * Checks if the given value is a natural
		 * number (including 0)
		 *
		 * @param $str
		 * @return bool
		 */
		public function is_natural($str){
			return ctype_digit((string) $str);
		}

		/**
		 * Checks if the given value is a natural
		 * number (excluding 0)
		 *
		 * @param $str
		 * @return bool
		 */
		public function is_natural_no_zero($str){
			return ($str != 0) && self::is_natural($str);
		}

		/**
		 * Valid Base64
		 *
		 * Tests a string for characters outside of the Base64 alphabet
		 * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
		 *
		 * @access	public
		 * @param	string
		 * @return	bool
		 */
		public function valid_base64($str){
			return (bool) !preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
		}

		/**
		 * Preps the given data for form input
		 *
		 * @param string|array $data
		 * @return array|string
		 */
		public function prep_for_form($data = ''){
			if(is_array($data)){
				foreach($data as $key => $val){
					$data[$key] = self::prep_for_form($val);
				}

				return $data;
			}elseif($data == ''){
				return $data;
			}

			return str_replace(array("'", '"', '<', '>'), array("&#39;", "&quot;", '&lt;', '&gt;'), stripslashes($data));
		}

		/**
		 * Preps a string as a URL, by
		 * ensuring that it starts with either
		 * http:// or https://.
		 * If not, it prepends http:// to the string.
		 *
		 * @param string $str
		 * @return string
		 */
		public function prep_url($str = ''){
			if(($str == 'http://') || ($str == 'https://') || ($str == '')){
				return '';
			}elseif((substr($str, 0, 7) != 'http://') && (substr($str, 0, 8) != 'https://')){
				$str = 'http://' . $str;
			}

			return $str;
		}

		/**
		 * Strips the image tag from the string,
		 * leaving only the URL
		 *
		 * @param $str
		 * @return mixed
		 */
		public function strip_image_tags($str){
			return preg_replace('/^<img\s*.*? src\s*=\s*(\'|")(.+?)(\1).*?>$/', '$2', trim($str));
		}

		/**
		 * Converts PHP tags to html entities
		 *
		 * @param $str
		 * @return string
		 */
		public function encode_php_tags($str){
			return str_replace(
				array(
					'<?php',
					'<?Php',
					'<?PHp',
					'<?PHP',
					'<?pHp',
					'<?pHP',
					'<?phP',

					'<?',
					'?>'
				),
				array(
					'&lt;?php',
					'&lt;?Php',
					'&lt;?PHp',
					'&lt;?PHP',
					'&lt;?pHp',
					'&lt;?pHP',
					'&lt;?phP',

					'&lt;?',
					'?&gt;'
				),
				$str);
		}


		/**
		 * Now my custom validation rules
		 */

		/**
		 * Checks if the string is a valid 12 hour formatted time
		 *
		 * @param $str
		 * @return bool
		 */
		public function is_time($str){
			return !!preg_match('/^(0[0-9]|1[0-2])(:[0-5][0-9]){1,2}$/', $str);
		}

		/**
		 * Checks if the string is a valid 24 hour formatted time
		 *
		 * @param $str
		 * @return bool
		 */
		public function is_time_24($str){
			return !!preg_match('/^([0-1][0-9]|2[0-3])(:[0-5][0-9]){1,2}$/', $str);
		}

		/**
		 * Checks that the given string exists
		 * in the supplied db field.
		 * $field must be supplied as
		 * TABLE.FIELD
		 *
		 * @param $str
		 * @param $field
		 * @return bool
		 */
		public function is_in($str, $field){
			// only continue if the field has a separating dot
			// we are using !=, rather than !== as we don't care about type.
			// both value of false or position of 0 should both be a failure
			if(false != strpos($field, '.')){
				// ensure that we have a db connection
				if(!is_null($db =& self::getDB())){
					// split the field to get the table
					list($table, $field) = explode('.', $field);

					$notField = '';
					$notValue = '';
					if(func_num_args() >= 4){
						$notField = func_get_arg(2);
						$notValue = func_get_arg(3);
					}

					// build the sql query
					$query = "SELECT
									*
								FROM
									" . $db->escape($table) . "
								WHERE
									" . $db->escape($field) . " = '" . $db->escape($str) . "'
									" . (
										(($notField != '') && ($notValue != '')) ? "AND " . $db->escape($notField) . " != '" . $db->escape($notValue) . "'" : ""
									) . "
								LIMIT 1";
					return $db->query($query) && ($db->numRows() > 0);
				}
			}

			return false;
		}

		/**
		 * Checks if the given string is a valid
		 * date, in the format supplied
		 *
		 * @param $str
		 * @param string $format
		 * @return bool
		 */
		public function is_date($str, $format = ''){
			$replace = array(
				'YYYY'	=> '([1-9][0-9]{3})',				// match 4 digit year
				'YY'	=> '([0-9]{2})',					// match 2 digit year
				'Y'		=> '(([1-9][0-9])?[0-9]{2})',		// matches 2-4 digit year,

				'MM'	=> '(0[1-9]|1[0-2])',				// match a 2 digit month (has pre-pending 0
				'M'		=> '([1-9]|1[0-2])',				// match a 1 digit date (doesn't have pre-pending 0

				'DD'	=> '(0[1-9]|[1-2][0-9]|3[0-1])',	// matches a 2 digit day (has pre-pending 0)
				'D'		=> '([1-9]|[1-2][0-9]|3[0-1])',		// matches a 1 digit day (doesn't have pre-pending 0)
			);
			$format = str_replace(array_keys($replace), $replace, preg_quote(strtoupper($format), '/'));

			return !!preg_match('/^' . $format . '$/', $str);
		}


		/**
		 * Checks whether the given name is an array reference.
		 * If true, then an array is returned in the following format:
		 * array(
		 * 	0	=> name[key],
		 *  1	=> name
		 *  2	=> key
		 * )
		 *
		 * @param $name
		 * @return array|bool
		 */
		private static function getNameArray($name){
			if(preg_match('/^(.+?)\[([^\]]*)\]$/', $name, $matches)){
				return $matches;
			}
			return false;
		}

		/**
		 * Takes a name and returns the referenced post variable.
		 * This can handle names referencing post arrays.
		 *
		 * @param $name
		 * @return array|string
		 */
		private function getInput($name){
			$return = '';

			if(false !== ($matches = self::getNameArray($name))){
				// name is an array
				if(isset($_POST[$matches[1]]) && is_array($_POST[$matches[1]])){
					// cache the form input
					self::$fetchedInput[$matches[1]] = isset(self::$fetchedInput[$matches[1]]) ? self::$fetchedInput[$matches[1]] : $_POST[$matches[1]];

					if($matches[2] != ''){
						// array key defined
						$return = isset(self::$fetchedInput[$matches[1]][$matches[2]]) ? self::$fetchedInput[$matches[1]][$matches[2]] : '';
					}else{
						// no key defined return the first element
						$return = (count(self::$fetchedInput[$matches[1]]) > 0) ? array_shift(self::$fetchedInput[$matches[1]]) : '';
					}
				}
			}else{
				// name is not an array
				$return = isset($_POST[$name]) ? $_POST[$name] : '';
			}

			return $this->prep_for_form($return);
		}

		/**
		 * Returns the value for the post variable, with the given name.
		 * If value isn't found, and empty string is returned.
		 * Returned value is escaped for form input.
		 *
		 * @param $name
		 * @return array|string
		 */
		public function getValue($name){
			return $this->getInput($name);
		}

		/**
		 * Returns the 'selected' value of the given post variable.
		 * If selected, a value of 'selected' is returned, otherwise
		 * and empty string.
		 *
		 * @param $name
		 * @param $val
		 * @return string
		 */
		public function getSelect($name, $val){
			$input = $this->getInput($name);
			return ($input == $val) ? 'selected' : '';
		}


		/**
		 * Returns the 'checked' value of the given post variable.
		 * If checked, a value of 'checked' is returned, otherwise
		 * and empty string.
		 *
		 * @param $name
		 * @param $val
		 * @return string
		 */
		public function getCheckbox($name, $val){
			$input = $this->getInput($name);
			return ($input == $val) ? 'checked' : '';
		}
	}


	class GreenFormValidationDB{
		private $db = null;
		private $functions = array();

		private $queryResult = null;

		public function __construct(){
			$this->getConnection();
		}

		private function &getConnection(){
			if(is_null($this->db)){
				// attempt to grab a db connection

				// check for wordpress db
				global $wpdb;
				if(isset($wpdb) && is_a($wpdb, 'wpdb')){
					$this->db =& $wpdb;
					return $this->db;
				}

				// check for a $db variable
				// this is un-reliable and should be left to last
				// as we have no way of knowing what it actually is
				global $db;
				if(isset($db) && is_object($db)){
					$this->db =& $db;
					return $this->db;
				}
			}

			return $this->db;
		}

		/**
		 * Checks if we have a DB connection or not
		 *
		 * @return bool
		 */
		public function hasDB(){
			return !is_null($this->getConnection());
		}

		private function getFunction($type){
			if($this->hasDB()){
				$type = strtolower($type);

				if(!isset($this->functions[$type])){
					$db =& $this->getConnection();

					switch($type){
						case 'query':
							if(method_exists($db, 'query')){
								$this->functions[$type] = 'query';
							}elseif(method_exists($db, 'get_rows')){
								$this->functions[$type] = 'get_rows';
							}elseif(method_exists($db, 'get_row')){
								$this->functions[$type] = 'get_row';
							}
						break;
						case 'num_rows':
						case 'numrows':
						case 'num-rows':
							if(method_exists($db, 'num_rows')){
								$this->functions[$type] = 'num_rows';
							}elseif(method_exists($db, 'numrows')){
								$this->functions[$type] = 'numrows';
							}elseif(method_exists($db, 'numRows')){
								$this->functions[$type] = 'numRows';
							}
						break;
						case 'escape':
						case 'escape_string':
						case 'escapestring':
							if(method_exists($db, 'escape')){
								$this->functions[$type] = 'escape';
							}elseif(method_exists($db, 'escape_string')){
								$this->functions[$type] = 'escape_string';
							}elseif(method_exists($db, 'escape_str')){
								$this->functions[$type] = 'escape_str';
							}elseif(method_exists($db, 'escapestring')){
								$this->functions[$type] = 'escapestring';
							}elseif(method_exists($db, 'escapestr')){
								$this->functions[$type] = 'escapestr';
							}
						break;
						default:
							if(method_exists($db, $type)){
								$this->functions[$type] = 'num_rows';
							}
						break;
					}
				}

				return isset($this->functions[$type]) ? $this->functions[$type] : false;
			}

			return false;
		}

		public function escape($val){
			if(false !== ($func = $this->getFunction('escape'))){
				$db =& $this->getConnection();
				$val = $db->$func($val);
			}else{
				// no escape function exists - manually attempt to escape it
				$val = addslashes(trim($val));
			}

			return $val;
		}

		public function query($query){
			$this->queryResult = null;

			if($this->hasDB()){
				$db =& $this->getConnection();

				if(false !== ($func = $this->getFunction('query'))){
					$result = $db->$func($query);

					if(is_bool($result)){
						// result is boolean - return it
						return $result;
					}elseif(is_int($result)){
						// result is an integer, this will probably either be
						// 1. a boolean style value (1 == true, 0 == false)
						// 2. an insert ID, if used for an insert query
						$this->queryResult = $result;
						return $result > 0;
					}elseif(is_array($result)){
						// result is an array - this is probably a list of results for a select query
						$this->queryResult = $result;
						return !empty($result);
					}elseif(is_object($result)){
						// result is an object - this is probably a result for a select query
						$this->queryResult = $result;
						return true;
					}
				}
			}

			return false;
		}

		public function numRows(){
			if(false !== ($func = $this->getFunction('num_rows'))){
				// num rows function exists - call it
				$db =& $this->getConnection();
				return (int) $db->$func();
			}else{
				// no num rows function exists - check the last query results

				if(is_null($this->queryResult)){
					// no query result defined - return 0
					return 0;
				}elseif(is_int($this->queryResult)){
					// result is numeric - assume number of results - return it
					return ($this->queryResult > 0) ? $this->queryResult : 0;
				}elseif(is_array($this->queryResult)){
					// result is array - return it's count
					return count($this->queryResult);
				}elseif(is_object($this->queryResult)){
					// result is an object - this could be an actual result row or a query object (as in CI)
					return method_exists($this->queryResult, 'num_rows') ? $this->queryResult->num_rows() : 1;
				}
			}

			return 0;
		}
	}
}