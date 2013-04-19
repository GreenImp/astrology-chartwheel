<?php
/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 20/03/13 21:16
 */
if(!class_exists('PluginHandler')){
	// load some extra files, if they exist
	include_once(dirname(__FILE__) . '/FormValidation.class.php');
	include_once(dirname(__FILE__) . '/Message.class.php');
	include_once(dirname(__FILE__) . '/Page.class.php');

	class PluginHandler{
		public $version = 0.1;			// version number
		public $name = 'My Plugin';		// plugin name
		public $varName = 'myPlugin';	// variable name
		public $dbPrefix = 'myplugin_';	// db prefix

		private static $singleton = null;

		public $debug = false;		// bool - whether we're debugging or not
		public $directory = '';		// the plugin directory
		public $uri = '';			// the relative URI to the plugin directory (for loading CSS, JS etc)
		public $pluginFile = '';	// the plugin file name

		private $lib = array();

		private function __construct($debug = false){
			// define whether we are in debug mode or not
			$this->debug = ($debug === true);

			// set required directory/path variables
			$this->directory = realpath(rtrim(dirname(__FILE__), '/') . '/../') . '/';								// plugin directory
			$this->pluginFile = $this->directory . substr(strrchr(rtrim($this->directory, '/'), '/'), 1) . '.php';	// plugin file
			$this->uri = plugin_dir_url($this->pluginFile);															// relative URI to the plugin directory

			// set up the activation hook to run the installation function
			register_activation_hook($this->pluginFile, array($this, 'install'));

			// set up the de-activation hook to run the un-installation function
			register_deactivation_hook($this->pluginFile, array($this, 'uninstall'));


			// check for any plugin libraries
			if(class_exists('FormValidation')){
				$this->lib['FormValidation'] = FormValidation::getInstance();
			}
			if(class_exists('Message')){
				$this->lib['Message'] = new Message();
			}
			if(class_exists('Page')){
				$this->lib['Page'] = new Page($this->directory . 'pages/', $this->varName, $this->debug);
			}
		}

		/**
		 * Returns an instance of the plugin
		 *
		 * @param bool $debug
		 * @return PluginHandler
		 */
		public static function getInstance($debug = false){
			if(!is_a(self::$singleton, 'PluginHandler')){
				self::$singleton = new PluginHandler($debug);
			}

			return self::$singleton;
		}

		/**
		 * Runs the initial installation functionality
		 */
		public function install(){
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

			// add the version number
			add_option($this->varName . '_version', $this->version);
		}

		/**
		 * Runs the un-install functionality
		 */
		public function uninstall(){
			// delete the version number
			delete_option($this->varName . '_version');

			// flush the rewrite rules
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}

		/**
		 * Returns the requested library.
		 * Returns null if the library isn't found
		 *
		 * @param $name
		 * @return null
		 */
		public function library($name){
			return isset($this->lib[$name]) ? $this->lib[$name] : null;
		}
	}
}