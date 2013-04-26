<?php
/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 19/04/13 15:24
 */

require_once(dirname(__FILE__) . '/PluginHandler.class.php');

if(!class_exists( 'WP_Http' )){
	require_once(ABSPATH . WPINC. '/class-http.php');
}
 
class AstrologyPlugin extends PluginHandler{
	private $requestURL		= 'https://primary.astrowebserver.net/v1/';	// base URL for requests
	private $reportURI		= 'reports/CreateXML/%s/';					// URI for reports (charts)
	private $locationURI	= 'atlas/CheckLocation/';					// URI for location checks
	private $reportCode		= 'NEW-CHARTWHEEL';							// Report code for reports

	private $apiKey = 'e7bc1e12-bba8-4274-9b0a-ea4bef4179d0';			// API key

	private $errors	= array();	// records the last occurred error

	public function __construct($name, $varName = null, $dbPrefix = null, $debug = false){
		parent::__construct($name, $varName, $dbPrefix, $debug);
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
				$this->setError($status->Code, $status->Message);
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
	 * @param $code
	 * @param $error
	 */
	private function setError($code, $error){
		$this->errors[] = array(
			'code'	=> $code,
			'message' => $error
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
			// details exist - make the request
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
			'CountryISO'	=> (($state != '') ? '-' . $state : $country)
		);

		$response = $this->makeRequest($this->buildRequest($this->locationURI, $vars, $format));

		if($this->checkResponse($response, $format)){
			// response is okay
			$response = $this->parseData($response, $format);
			return $response['body']->ResponseData[0];
		}

		return null;
	}
}