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
	private $requestURL = 'https://primary.astrowebserver.net/v1/reports/';
	private $reportCode = 'NEW-CHARTWHEEL';
	private $apiKey = 'e7bc1e12-bba8-4274-9b0a-ea4bef4179d0';

	public function __construct($name, $varName = null, $dbPrefix = null, $debug = false){
		parent::__construct($name, $varName, $dbPrefix, $debug);
	}

	/**
	 * Builds and returns the request URL
	 *
	 * @param array $details
	 * @param string $format
	 * @return string
	 */
	private function buildRequest(array $details, $format = 'JSON'){
		$uri = 'CreateXML/' . $this->reportCode . '/?';

		// loop through each person's details and add them to the list
		$vars = array('APIKey' => $this->apiKey);
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

		foreach($vars as $k => $v){
			$uri .= urlencode($k) . '=' . urlencode($v) . '&';
		}

		$uri .= 'OutputFormat=' . $format;
		if($this->debug){
			$uri .= '&TestMode=1';
		}

		return $uri;
	}

	/**
	 * Takes a URI and makes an AJAX request to it
	 *
	 * @param $url
	 * @return array
	 */
	private function makeRequest($url){
		$request = new WP_Http();
		return $request->request($this->requestURL . ltrim($url, '/'));
	}

	/**
	 * @param $request
	 * @return SimpleXMLElement
	 */
	private function parseRequest($request){
		return simplexml_load_string($request);
	}

	/**
	 * Makes an Astrology request and
	 * returns the output as an object
	 *
	 * @param array $details
	 * @param string $format
	 * @return null|SimpleXMLElement
	 */
	public function getRequest(array $details, $format = 'JSON'){
		$response = $this->makeRequest($this->buildRequest($details, $format));

		if($response['body'] != ''){
			// response returned
			$response = json_decode($response['body']);

			if($response->ResponseStatus->Code == 1){
				// status is okay
				return $this->parseRequest($response->ResponseData->XML);
			}
		}

		return null;
	}
}