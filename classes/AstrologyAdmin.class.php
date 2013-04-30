<?php
/**
 * Author: leelangley
 * Date Created: 30/04/2013 17:37
 */
 
class AstrologyAdmin{
	public $debug = false;	// bool - whether we're debugging or not
	private $plugin = null;	// reference to the main plugin

	public function __construct(&$plugin = null, $debug = false){
		if(is_a($plugin, 'AstrologyPlugin')){
			$this->plugin =& $plugin;
		}

		add_action('admin_menu', array($this, 'addMenu'));
		add_action('admin_print_scripts', array($this, 'enqueueScripts'));
	}

	/**
	 * Adds the admin navigation
	 */
	public function addMenu(){
		add_menu_page(
			$this->plugin->name,
			$this->plugin->name,
			'publish_pages',
			$this->plugin->varName,
			array($this, 'page'),
			$this->plugin->uri . 'assets/images/icn-menu.png'
		);
	}

	/**
	 * Enqueue any required CSS/JS
	 */
	public function enqueueScripts(){}

	/**
	 * Loads an admin page
	 */
	public function page(){
		$pageLoader = $this->plugin->library('Page');
		$pageLoader->load('admin/settings');
	}
}