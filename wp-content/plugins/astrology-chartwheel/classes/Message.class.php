<?php
/**
 * Author: GreenImp
 * Date Created: 05/09/2012 12:38
 */
if(!class_exists('Message')){
	class Message{
		private static $sessionName = 'message_class_messages';

		public function __construct(){
			self::init();
		}

		public static function init(){
			if(!session_id()){
				session_start();
			}

			add_action('admin_notices', 'Message::show');
		}

		public static function add($type, $message){
			$_SESSION[self::$sessionName][$type][] = $message;

			array_unique($_SESSION[self::$sessionName][$type]);
		}

		public static function show($return = false, $keep = false){
			$output = '';

			if(isset($_SESSION[self::$sessionName]) && is_array($_SESSION[self::$sessionName]) && (count($_SESSION[self::$sessionName]) > 0)){
				// messages found - loop through each message type and output them
				foreach($_SESSION[self::$sessionName] as $type => $messages){
					if(is_array($messages)){
						$output .= '<div id="message" class="' . $type . '">';
						$output .= '<p>' . implode('</p><p>', $messages) . '</p>';
						$output .= '</div>';
					}
				}
			}

			if(!$keep){
				unset($_SESSION[self::$sessionName]);
			}

			if($return){
				return $output;
			}else{
				echo $output;
			}
		}
	}
}