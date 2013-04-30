<?php
/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 19/04/13 15:24
 */

require_once(dirname(__FILE__) . '/PluginHandler.class.php');
require_once(dirname(__FILE__) . '/CSVHandler.class.php');

if(!class_exists( 'WP_Http' )){
	require_once(ABSPATH . WPINC. '/class-http.php');
}
 
class AstrologyPlugin extends PluginHandler{
	private $requestURL		= 'https://primary.astrowebserver.net/v1/';	// base URL for requests
	private $reportURI		= 'reports/CreateXML/%s/';					// URI for reports (charts)
	private $locationURI	= 'atlas/CheckLocation/';					// URI for location checks
	private $reportCode		= 'NEW-CHARTWHEEL';							// Report code for reports

	private $apiKey			= 'e7bc1e12-bba8-4274-9b0a-ea4bef4179d0';	// API key

	private $chartSize		= 1140;										// chart image size

	private $errors	= array();	// records the last occurred error

	public function __construct($name, $varName = null, $dbPrefix = null, $debug = false){
		parent::__construct($name, $varName, $dbPrefix, $debug);
	}

	/**
	 * Runs the initial installation functionality
	 */
	public function install(){
		// run the parent's install function
		parent::install();

		global $wpdb;

		/**
		 * Create the database tables
		 */
		// set up the table for storing countries
		$tableName = $this->dbPrefix . 'countries';
		$sql = "CREATE TABLE IF NOT EXISTS " . $tableName . " (
			id int(10) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			code varchar(2) NOT NULL,
			PRIMARY KEY (id),
			KEY code (code)
		)";
		dbDelta($sql);

		// remove any data from the table (in case the table already existed)
		$query = "TRUNCATE TABLE `" . $tableName . "`";
		$wpdb->query($query);

		// now that we've built the db tables, we need to fill them
		$data = CSVHandler::parseFile($this->directory . 'assets/resources/Country-List-CSV.csv');
		if(count($data) > 0){
			// now add the db data
			$query = "INSERT INTO
				`" . $tableName . "`
				(
					`name`,
					`code`
				)
			VALUES ";
			foreach($data as $row){
				$query .= "(
					'" . $wpdb->escape($row[0]) . "',
					'" . $wpdb->escape($row[1]) . "'
				),";
			}
			$query = rtrim($query, ',');
			$wpdb->query($query);
		}


		// set up the table for storing states
		$tableName = $this->dbPrefix . 'country_states';
		$sql = "CREATE TABLE IF NOT EXISTS " . $tableName . " (
			id int(10) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			code varchar(5) NOT NULL,
			country_code varchar(2) NOT NULL,
			PRIMARY KEY (id),
			KEY code (code),
			KEY country_code (country_code)
		)";
		dbDelta($sql);

		// remove any data from the table (in case the table already existed)
		$query = "TRUNCATE TABLE `" . $tableName . "`";
		$wpdb->query($query);

		// now that we've built the db tables, we need to fill them
		$data = CSVHandler::parseFile($this->directory . 'assets/resources/US-State-List-CSV.csv');
		if(count($data) > 0){
			// now add the db data
			$query = "INSERT INTO
				`" . $tableName . "`
				(
					`name`,
					`code`,
					`country_code`
				)
			VALUES ";
			foreach($data as $row){
				if(('code' != strtolower($row[0])) && ('state / territory' != strtolower($row[1]))){
					$query .= "(
						'" . $wpdb->escape($row[1]) . "',
						'" . $wpdb->escape($row[0]) . "',
						'US'
					),";
				}
			}
			$query = rtrim($query, ',');
			$wpdb->query($query);
		}
	}

	/**
	 * Runs the un-installation functionality
	 */
	public function uninstall(){
		parent::uninstall();

		if(!$this->debug){
			global $wpdb;

			// drop the dbs
			$wpdb->query("DROP TABLE " . $this->dbPrefix . "countries");
			$wpdb->query("DROP TABLE " . $this->dbPrefix . "country_states");
		}
	}

	/**
	 * Registers required short codes
	 */
	public function registerShortCodes(){
		add_shortcode('astrology-chart', array($this, 'doAstrology'));
	}

	/**
	 * Adds required JS/CSS
	 */
	public function enqueueScripts(){
		wp_enqueue_style('jquery-ui-css', 'http://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css', false, $this->version, false);

		wp_enqueue_script('astrology-chart-js', $this->uri . 'assets/js/charts.js', array('jquery-ui-datepicker'), $this->version, true);
	}

	/**
	 * Takes an array and returns a query string.
	 * ie;
	 * foo=bar&hello=world
	 *
	 * @param array $vars
	 * @return string
	 */
	private function arrayToQueryString(array $vars){
		$query = '';
		foreach($vars as $k => $v){
			$query .= urlencode($k) . '=' . urlencode($v) . '&';
		}

		return rtrim($query, '&');
	}

	/**
	 * Builds and returns the request URI
	 *
	 * @param string $uri
	 * @param array $vars
	 * @param string $format
	 * @return string
	 */
	private function buildRequest($uri, array $vars, $format = 'JSON'){
		// add required/relevant variable
		$vars['APIKey'] = $this->apiKey;	// system API key
		$vars['OutputFormat'] = $format;	// requested response type (XML/JSON)
		if($this->debug){
			// we're in debug mode
			$vars['TestMode'] = 1;
		}

		return ltrim($uri, '/') . '?' . $this->arrayToQueryString($vars);
	}

	/**
	 * Takes a URI and makes a http request to it.
	 * This function expects a relative path (URI),
	 * not a full URL.
	 * The request URL is prepended to the given URI.
	 *
	 * @param $url
	 * @return array
	 */
	private function makeRequest($url){
		$request = new WP_Http();
		return $request->request($this->requestURL . ltrim($url, '/'));
	}

	/**
	 * Takes a http response (and expected format) and validates it
	 *
	 * @param $response
	 * @param $format
	 * @return bool
	 */
	private function checkResponse($response, $format){
		if(
			isset($response['response']) && ($response['response']['code'] == 200) &&	// response header checks out
			(isset($response['body']) && (trim($response['body']) != ''))				// response body contains data
		){
			// response headers look okay - check the data
			$response = $this->parseData($response, $format);

			$status = $response['body']->ResponseStatus;

			if($status->Code == 1){
				// status is okay
				if($response['body']->ResponseData == null){
					// no data
					$this->setError(null, 'An unknown error has occurred');
					return false;
				}

				// response is okay
				return true;
			}else{
				// an error has occurred
				$this->setError($status->Code, $status->Message, $response['body']->ResponseData);
				return false;
			}
		}else{
			// error from response
			$this->setError(null, (isset($response['response']) ? $response['response'] . ' - ' . $response['response']['message'] : 'No response'));
			return false;
		}
	}

	/**
	 * Parses the data into a readable, normalised format
	 *
	 * @param $data
	 * @param $format
	 * @return mixed
	 */
	private function parseData($data, $format){
		if($format == 'JSON'){
			$data['body'] = json_decode($data['body']);
		}elseif($format == 'XML'){
			$data['body'] = $this->parseXML($data)->body;
		}

		return $data;
	}

	/**
	 * Takes an XML request response and returns it as an object
	 *
	 * @param $request
	 * @return SimpleXMLElement
	 */
	private function parseXML($request){
		return simplexml_load_string($request);
	}

	/**
	 * Sets the last error
	 *
	 * @param string|int $code
	 * @param string $error
	 * @param mixed $data
	 */
	private function setError($code, $error, $data = ''){
		$this->errors[] = array(
			'code'		=> $code,
			'message'	=> $error,
			'data'		=> $data
		);
	}

	/**
	 * Returns the last specified error
	 *
	 * @return array
	 */
	public function getErrors(){
		$errors = $this->errors;
		$this->errors = array();
		return $errors;
	}

	/**
	 * Returns a list of available countries
	 *
	 * @return mixed
	 */
	public function getCountries(){
		global $wpdb;

		$query = "SELECT
						*
					FROM
						" . $this->dbPrefix . "countries
					ORDER BY
						name ASC";
		return $wpdb->get_results($query);
	}

	/**
	 * Returns a list of available country states
	 *
	 * @param string $country
	 * @return mixed
	 */
	public function getStates($country = ''){
		global $wpdb;

		$query = "SELECT
						*
					FROM
						" . $this->dbPrefix . "country_states
					ORDER BY
						name ASC
					" . (($country != '') ? "
					WHERE country_code = '" . $wpdb->escape(strtoupper($country)) . "'" : '');
		return $wpdb->get_results($query);
	}

	/**
	 * Makes an Astrology request and
	 * returns the output as an object
	 *
	 * @param array $details
	 * @param string $format
	 * @return null|SimpleXMLElement
	 */
	public function getChart(array $details, $format = 'JSON'){
		// loop through each person's details and add them to the list
		$vars = array();
		$i = 1;
		foreach($details as $person){
			$vars['P' . $i . 'FirstName'] = $person['firstName'];
			$vars['P' . $i . 'LastName'] = isset($person['lastName']) ? $person['lastName'] : '';
			$vars['P' . $i . 'Sex'] = $person['sex'];
			$vars['P' . $i . 'DOB'] = date('Y-m-d', strtotime($person['dob']));
			$vars['P' . $i . 'TimeUnknown'] = (isset($person['tobUnknown']) && ($person['tobUnknown'] == true)) ? 1 : 0;
			$vars['P' . $i . 'Time'] = isset($person['tob']) ? $person['tob'] : '';
			$vars['P' . $i . 'LocationCode'] = $person['locationCode'];

			$i++;
		}

		if(count($vars) > 0){
			// details exist

			// define the chart image size
			$vars['ImageSize'] = $this->chartSize;

			// make the request
			$response = $this->makeRequest($this->buildRequest(
				sprintf($this->reportURI, $this->reportCode),
				$vars,
				$format
			));

			if($this->checkResponse($response, $format)){
				// response is okay
				$response = $this->parseData($response, $format);

				return $this->parseXML($response['body']->ResponseData->XML);
			}
		}else{
			$this->setError(null, 'No data defined');
		}

		return null;
	}

	/**
	 * Takes location data and returns the full,
	 * valid location name and code.
	 * If data is invalid, a value of null is returned.
	 *
	 * @param $town
	 * @param $country
	 * @param string $state
	 * @param string $format
	 * @return null
	 */
	public function getLocationCode($town, $country, $state = '', $format = 'JSON'){
		// loop through each person's details and add them to the list
		$vars = array(
			'CityTown'		=> $town,
			'CountryISO'	=> (($state != '') ? $state : $country)
		);

		$response = $this->makeRequest($this->buildRequest($this->locationURI, $vars, $format));

		if($this->checkResponse($response, $format)){
			// response is okay
			$response = $this->parseData($response, $format);
			return $response['body']->ResponseData[0];
		}

		return null;
	}

	public function doAstrology(){
		$this->lib['Page']->load('service');
	}
}