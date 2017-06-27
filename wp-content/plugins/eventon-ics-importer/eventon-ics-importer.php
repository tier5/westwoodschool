<?php
/*
 Plugin Name: EventON - ICS importer
 Plugin URI: http://plugins.ashanjay.com/event-calendar/
 Description: Import events from a .ICS file into eventON system
 Author: Ashan Jay
 Version: 0.1
 Author URI: http://www.ashanjay.com/
 */

class EVOICS_import{	
	
	public $version='0.1';
	public $eventon_version = '2.4.4';
	public $name = 'ICS Importer';
	
	public $addon_data = array();
	public $slug, $plugin_slug , $plugin_url , $plugin_path ;
	
	// Construct
		public function __construct(){
			$this->super_init();
			add_action('plugins_loaded', array($this, 'plugin_init'));
		}
		
		function plugin_init(){
			if(class_exists('EventON')){
				include_once( 'includes/class-admin_check.php' );
				$this->check = new addon_check($this->addon_data);
				$check = $this->check->initial_check();
				
				if($check){
					$this->addon = new evo_addon($this->addon_data);
					add_action( 'init', array( $this, 'init' ), 0 );
				}
			}else{	add_action('admin_notices', array($this, '_eventon_warning'));	}
		}
		function _eventon_warning(){
			?><div class="message error"><p><?php _e('EventON is required for ICS Importer addon to work properly.', 'eventon'); ?></p></div><?php
		}
	
	// SUPER init
		function super_init(){
			// PLUGIN SLUGS			
			$this->addon_data['plugin_url'] = path_join(WP_PLUGIN_URL, basename(dirname(__FILE__)));
			$this->addon_data['plugin_slug'] = plugin_basename(__FILE__);
			list ($t1, $t2) = explode('/', $this->addon_data['plugin_slug'] );
	        $this->addon_data['slug'] = $t1;
	        $this->addon_data['plugin_path'] = dirname( __FILE__ );
	        $this->addon_data['evo_version'] = '2.2.12';
	        $this->addon_data['version'] = $this->version;
	        $this->addon_data['name'] = 'ActionUser';

	        $this->plugin_url = $this->addon_data['plugin_url'];
	        $this->plugin_slug = $this->addon_data['plugin_slug'];
	        $this->slug = $this->addon_data['slug'];
	        $this->plugin_path = $this->addon_data['plugin_path'];
	        $this->assets_path = str_replace(array('http:','https:'), '',$this->addon_data['plugin_url']).'/assets/';
		}

	// INITIATE action user
		function init(){

	      	// Activation
			$this->addon->activate();
			
			// Deactivation
			register_deactivation_hook( __FILE__, array($this,'deactivate'));

			if ( is_admin() ){
				$this->addon->updater(); // run the addon updater
				include_once('includes/class-admin-init.php');
				$this->admin = new EVOICS_admin();		
			}

			if ( ! is_admin() || defined('DOING_AJAX') ){
				include_once('includes/class-ajax.php');
			}
		}

	// Deactivate addon
		function deactivate(){	$this->addon->remove_addon();	}		
}

// Initiate this addon within the plugin
$GLOBALS['evoics'] = new EVOICS_import();
?>