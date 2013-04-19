<?php
if(!function_exists('add_action')){ exit; }

/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 19/04/13 10:26
 */
/*
Plugin Name: Astrology Chartwheel
Plugin URI:
Description: A plugin that integrates 'Horiscope Services' white label Astrology Charts, into Wordpress. Horiscope Services website: http://stardm.com/
Author: Lee Langley
Version: 0.1
Author URI: greenimp.co.uk
*/

require_once(dirname(__FILE__) . '/classes/PluginHandler.class.php');

// initialise the base class
$plugin = PluginHandler::getInstance(true);

// set up the pages
$plugin->library('Page')->addURLs(array(
	'service'
));