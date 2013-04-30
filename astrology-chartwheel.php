<?php
if(!function_exists('add_action')){ exit; }

/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 19/04/13 10:26
 */
/**
 * @package astrology-chartwheel
 * @version 0.1
 */
/*
Plugin Name: Astrology Chartwheel
Plugin URI:
Description: A plugin that integrates 'Horoscope Services' white label Astrology Charts, into Wordpress. Horoscope Services website: http://stardm.com/
Author: Lee Langley
Version: 0.1
Author URI: greenimp.co.uk
*/

require_once(dirname(__FILE__) . '/classes/AstrologyPlugin.class.php');
require_once(dirname(__FILE__) . '/classes/AstrologyAdmin.class.php');

// initialise the base class
$astrologyPlugin = new AstrologyPlugin('Astrology Chartwheel', 'astrologyChartwheel', null, true);

// set up the pages
$astrologyPlugin->library('Page')->addURLs(array(
	'service'
));

$astrologyAdmin = new AstrologyAdmin($astrologyPlugin, $astrologyPlugin->debug);


function is_gender($val){
	$val = strtoupper($val);
	return (($val == 'M') || ($val == 'F')) ? $val : false;
}