<?php
/**
 * Copyright GreenImp Web - greenimp.co.uk
 * 
 * Author: GreenImp Web
 * Date Created: 26/03/13 21:49
 */
if(!class_exists('GreenPage')){
	class GreenPage{
		private $rewriteRules = array();
		private $directory = '';
		private $varName = '';

		public function __construct($dir, $varName, $debug = false){
			$this->directory = $dir;
			$this->varName = $varName;
		}

		/**
		 * Outputs a HTTP status code, in the page header.
		 * It can optionally output a matching status message
		 * and stop further loading of the page.
		 *
		 * @param $code
		 * @param bool $exit
		 * @return bool|void
		 */
		public function httpStatus($code, $exit = false){
			switch($code){
				case 404:
					$codeText = 'Not Found';
					$description = 'The page you are looking for could not be found';
				break;
				case 403:
					$codeText = 'Forbidden';
					$description = 'You do not have access to this page';
				break;
				default:
					// un-recognised code
					if(is_numeric($code) && ($code >= 100) && ($code <= 599)){
						// code is numeric and is within the outer bounds for status codes, so may be valid
						// allow it, as we aren't catching all codes above
						$code = (int) $code;
						$codeText = '';
						$description = '';
					}else{
						// code appears to be invalid
						return false;
					}
				break;
			}

			if(!headers_sent()){
				// header hasn't been set - okay to define
				header('HTTP/1.0 ' . $code . ' ' . $codeText);
			}

			if($exit){
				// we are exiting output - display the status message
				echo '<h1>' . $code . (($codeText != '') ? ' - ' . $codeText : '') . '</h1>' .
						(($description != '') ? '<p>' . $description . '</p>' : '');
				exit;
			}
		}

		/**
		 * Loads a plugin page
		 *
		 * @param $page
		 * @param array $data
		 * @param bool $surround
		 * @param null $status
		 */
		public function load($page, $data = array(), $surround = false, $status = null){
			$page = ltrim($page, '/');
			if(preg_match('/^[a-zA-Z0-9\-_\/]+$/', $page) && file_exists($page = $this->directory . $page . '.php')){
				// get the URL for this page
				$currentURL = reset(explode('&', $_SERVER['REQUEST_URI']));
				// define a generic 'add' url
				$addURL = $currentURL . '&amp;action=add';
				// define a generic edit URL
				$editURL = $currentURL . '&amp;action=edit&amp;id=%d';
				// define a generic delete URL
				$deleteURL = $currentURL . '&amp;action=delete&amp;id=%d';

				// store the $data into their own variables
				if(count($data) > 0){
					foreach($data as $key => $val){
						$$key = $val;
					}
				}


				if(is_numeric($status)){
					$this->httpStatus($status);
				}


				if($surround){
					// include the page header
					get_header();
				}

				// output the page
				require($page);

				if($surround){
					// include the page sidebar
					get_sidebar();
					// include the page footer
					get_footer();
					// stop the page from progressing
					exit;
				}
			}else{
				// requested file doesn't exist
				$this->httpStatus(404, true);
			}
		}

		/**
		 * Flushes existing rewrite rules
		 */
		public function flushRules(){
			global $wp_rewrite;
			$wp_rewrite->flush_rules();
		}

		/**
		 * Adds the rewrite rules
		 *
		 * @return mixed
		 */
		public function addRewriteRules(){
			global $wp_rewrite;

			if(count($rules = $this->rewriteRules) > 0){
				$rewrite_tag = '%' . $this->varName . '%';
				$wp_rewrite->add_rewrite_tag($rewrite_tag, '(.+?)', $this->varName . '=' );

				$urlPath = '';
				foreach($rules as $rule){
					$urlPath .= preg_quote($rule) . '|';
				}
				$urlPath = rtrim($urlPath, '|');
				
				$new_rule = array(
					$this->varName . '/(' . $urlPath . ')(/.*)?)$' => 'index.php?' . $this->varName . '=$matches[1]'
				);
				$wp_rewrite->rules = $new_rule + (($wp_rewrite->rules === null) ? array() : $wp_rewrite->rules);
			}

			return $wp_rewrite->rules;
		}

		/**
		 * Allows use of custom query variables
		 *
		 * @param $public_query_vars
		 * @return array
		 */
		public function addCustomPageVariables($public_query_vars){
			$public_query_vars[] = $this->varName;

			return $public_query_vars;
		}

		/**
		 * Handles re-direct/display of plugin pages
		 */
		public function redirectFile(){
			global $wp_query;

			if(isset($wp_query->query_vars[$this->varName])){
				$vars = explode('/', $wp_query->query_vars[$this->varName]);

				$page = array_shift($vars);

				$data = array();
				foreach($vars as $var){
					$var = explode('-', $var);
					$data[array_shift($var)] = implode('-', $var);
				}

				$this->load($page, $data, true);
			}
		}

		/**
		 * Adds rewrite URLs
		 *
		 * @param $urls
		 */
		public function addURLs($urls){
			$this->rewriteRules = $urls;

			add_filter('init', array($this, 'flushRules'));
			add_filter('generate_rewrite_rules', array($this, 'addRewriteRules'));
			add_filter('query_vars', array($this, 'addCustomPageVariables'));
			add_action('template_redirect', array($this, 'redirectFile'));
		}
	}
}