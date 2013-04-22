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

	private $error	= '';	// records the last occurred error

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

	private function checkResponse($response, $format){
		die(var_dump($response));
		if(isset($response['body']) && ($response['body'] != '')){
			// response returned
			$response = json_decode($response['body']);

			if($response->ResponseStatus->Code == 1){
				// status is okay
				return true;
			}
		}

		return false;
	}

	/**
	 * Takes an XML request response and returns it as an object
	 *
	 * @param $request
	 * @return SimpleXMLElement
	 */
	private function parseRequest($request){
		return simplexml_load_string($request);
	}

	/**
	 * Sets the last error
	 *
	 * @param $error
	 */
	private function setError($error){
		$this->error = $error;
	}

	/**
	 * Returns the last specified error
	 *
	 * @return string
	 */
	public function getError(){
		return $this->error;
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
			//$vars['P' . $i . 'LocationCode'] = $person['locationCode'];
			$vars['P' . $i . 'LocationCode'] = 8338967;

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
				if($format == 'JSON'){
					$response = json_decode($response['body']);
				}elseif($format == 'XML'){
					$response = $this->parseRequest($response)->body;
				}

				return $this->parseRequest($response->ResponseData->XML);
			}
		}


		/*$response = $this->makeRequest($this->buildRequest($details, $format));

		if($response['body'] != ''){
			// response returned
			$response = json_decode($response['body']);

			if($response->ResponseStatus->Code == 1){
				// status is okay
				return $this->parseRequest($response->ResponseData->XML);
			}
		}*/

		return null;
	}

	public function getLocationCode($town, $country, $state = '', $format = 'JSON'){
		// loop through each person's details and add them to the list
		$vars = array(
			'CityTown'		=> $town,
			'CountryISO'	=> (($state != '') ? '-' . $state : $country)
		);

		$response = $this->makeRequest($this->buildRequest($this->locationURI, $vars, $format));

		if($this->checkResponse($response, $format)){
			// response is okay
		}

		return null;
	}
}