<?php
/**
 * Admin class for ICS importer plugin
 *
 * @version 	0.2
 * @author  	AJDE
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class EVOICS_admin{
	var $log= array();
	public $evo_opt;

	function __construct(){
		global $evoics;
		add_action('admin_init', array($this, 'admin_scripts'));
		
		// settings link in plugins page
		add_filter("plugin_action_links_".$evoics->plugin_slug, array($this,'eventon_plugin_links' ));
		add_action( 'admin_menu', array( $this, 'menu' ),9);

		$evo_opt = get_option('evcal_options_evcal_1');
	}
	/**	Add the tab to settings page on myeventon	 */
		function tab_array($evcal_tabs){
			$evcal_tabs['evcal_ics']='ICS Import';
			return $evcal_tabs;
		}
	// EventON settings menu inclusion
		function menu(){
			add_submenu_page( 'eventon', 'ICS Import', __('ICS Import','eventon'), 'manage_eventon', 'evoics', array($this, 'page_content') );
		}

	/**	ICS settings content	 */
		function page_content(){
			require_once('class-settings.php');
		}

	// Styles and scripts for the page
		public function admin_scripts(){
			global $evoics, $pagenow, $eventon;

			if( (!empty($pagenow) && $pagenow=='admin.php')
			 && (!empty($_GET['page']) && $_GET['page']=='evoics') 
			){
				// LOAD ajde library
				if(isset($_REQUEST['tab']) && $_REQUEST['tab'] == 'evoics_2'){
					$eventon->load_ajde_backender();
				}

				wp_enqueue_style( 'ics_import_styles',$evoics->assets_path.'ics_import_styles.css');
				wp_enqueue_script('ics_import_script',$evoics->assets_path.'script.js', array('jquery'), 1.0, true );
				wp_localize_script( 
					'ics_import_script', 
					'evoics_ajax_script', 
					array( 
						'ajaxurl' => admin_url( 'admin-ajax.php' ) , 
						'postnonce' => wp_create_nonce( 'evoics_nonce' )
					)
				);
			}
		}
	
	// Supported variable names for event post meta values 
		function get_all_fields(){
			$fields =  array(
				'event_name',
				'evcal_location_name',
				'evcal_allday',
				'event_start_date',
				'event_start_time',
				'event_end_date',
				'event_end_time',
				'event_description',
			);
			
			// pluggable hook for additional fields
				$fields = apply_filters('evoics_additional_ics_fields', $fields);

			return $fields;
		}

	// IMPORTING EVENT
		function import_event($event){
			if(empty($event['status']) || $event['status']=='ns' )
				$status = 'failed';				
			
			if($post_id = $this->create_post($event) ){
				$this->save_event_post_data($post_id, $event);
				$status = 'success';				
			}else{
				$status = 'failed';
			}
			return $status;
		}

	// save custom meta fields
		function save_event_post_data($post_id,$post_data){
			global $evoics;

		 	
		 	// for all fields
		 	foreach($this->get_all_fields() as $fieldvar=>$field){

		 		// for empty values
		 		if(empty($post_data[$field])) continue;

		 		// adjust array field value
		 		$fieldvar = (is_numeric($fieldvar))? $field: $fieldvar;
		 		//$value = addslashes(htmlspecialchars_decode($post_data[$field]) );	
		 		$value = addslashes(html_entity_decode($post_data[$field]) );	

		 		$fieldSaved = false;		 		

		 		// skip fields
		 		if(in_array($field, array('event_description','event_name','event_start_date','event_start_time','event_end_date','event_end_time', 'evcal_location_name') 
		 		)) continue;

		 		// yes no fields
		 		if(in_array($field, array('all_day'))){
		 			$value = strtolower($value);
		 			$this->create_custom_fields($post_id, $fieldvar, $value);	
					$fieldSaved = true;
		 		}

		 		// save non saved fields as post type meta
		 		if(!$fieldSaved){
		 			$this->create_custom_fields($post_id, $fieldvar, $value);
		 		}

		 		// pluggable hook
		 		do_action('evoics_save_event_custom_data', $post_id, $post_data, $field);

		 	} // endforeach
		 	
		 	// save event date and time information
		 		if(isset($post_data['event_start_date'])&& isset($post_data['event_end_date']) ){
					$start_time = !empty($post_data['event_start_time'])?
						explode(":",$post_data['event_start_time']): false;
					$end_time = !empty($post_data['event_end_time'])?
						explode(":",$post_data['event_end_time']):false;
					
					$date_array = array(
						'evcal_start_date'=>$post_data['event_start_date'],
						'evcal_start_time_hour'=>( $start_time? $start_time[0]: ''),
						'evcal_start_time_min'=>( $start_time? $start_time[1]: ''),
						'evcal_st_ampm'=> ( $start_time? $start_time[2]: ''),
						'evcal_end_date'=>$post_data['event_end_date'], 										
						'evcal_end_time_hour'=>( $end_time? $end_time[0]:''),
						'evcal_end_time_min'=>( $end_time? $end_time[1]:''),
						'evcal_et_ampm'=>( $end_time? $end_time[2]:''),

						'evcal_allday'=>( !empty($post_data['all_day'])? $post_data['all_day']:'no'),
					);
					
					$proper_time = eventon_get_unix_time($date_array, 'm/d/Y');
					
					// save required start time variables
					$this->create_custom_fields($post_id, 'evcal_srow', $proper_time['unix_start']);
					$this->create_custom_fields($post_id, 'evcal_erow', $proper_time['unix_end']);		
				}
		
		 	// event location fields
		 		if( !empty($post_data['evcal_location_name']) ){

		 			$termName = esc_attr(stripslashes($post_data['evcal_location_name']));

		 			$term = term_exists( $termName, 'event_location');
		 			if($term !== 0 && $term !== null){
		 				// assign location term to the event		 			
		 				wp_set_object_terms( $post_id, $termName, 'event_location');		
		 			}else{
		 				$term_slug = str_replace(" ", "-", $termName);

						// create wp term
						$newTERMid = wp_insert_term( $termName, 'event_location', array('slug'=>$term_slug) );

						if(!is_wp_error($newTERMid)){
							$term_meta = array();

							// generate latLon
							if(isset($post_data['evcal_location_name']))
								$latlon = eventon_get_latlon_from_address($post_data['evcal_location_name']);

							// latitude and longitude
							$term_meta['location_lon'] = (!empty($_POST['evcal_lon']))? $_POST['evcal_lon']:
								(!empty($latlon['lng'])? floatval($latlon['lng']): null);
							$term_meta['location_lat'] = (!empty($_POST['evcal_lat']))? $_POST['evcal_lat']:
								(!empty($latlon['lat'])? floatval($latlon['lat']): null);
							
							$term_meta['location_address'] = $termName;
							update_option("taxonomy_".$newTERMid['term_id'], $term_meta);
							
							//wp_set_object_terms( $post_id, $termName , 'event_location');							
							wp_set_object_terms( $post_id,  $newTERMid['term_id'] , 'event_location', true);					
						}
		 			}

		 			// set location generation to yes
		 			$this->create_custom_fields( $post_id, 'evcal_gmap_gen', 'yes');
		 		}
		
		 	// Pluggable filter
		 		do_action('evoics_save_additional_data', $post_id, $post_data);
		}
	
	/** Create the event post */
		function create_post($data) {
			$evoHelper = new evo_helper();

			// content for the event
			$content = (!empty($data['event_description'])?$data['event_description']:null );
			$content = str_replace('\,', ",", stripslashes($content) );

			$ICSopt2 = get_option('evcal_options_evoics_2');

			$publishStatus = (!empty($ICSopt2['EVOICS_status_publish']) && $ICSopt2['EVOICS_status_publish']=='yes')? 'publish': 'draft';

			return $evoHelper->create_posts(array(
				'post_status'=>$publishStatus,
				'post_type'=>'ajde_events',
				'post_title'=>convert_chars(stripslashes($data['event_name'])),
				'post_content'=>$content
			));
	    }
		function create_custom_fields($post_id, $field, $value) {       
	        add_post_meta($post_id, $field, $value);
	    }
		function get_author_id() {
			$current_user = wp_get_current_user();
	        return (($current_user instanceof WP_User)) ? $current_user->ID : 0;
	    }
	    // upload and return event featured image
		    function upload_image($url, $event_name){
		    	if(empty($url))
		    		return false;

		    	// Download file to temp location
			      $tmp = download_url( $url );

			      // Set variables for storage
			      // fix file filename for query strings
			      preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $url, $matches );
			      $file_array['name'] = basename($matches[0]);
			      $file_array['tmp_name'] = $tmp;

			      // If error storing temporarily, unlink
			      if ( is_wp_error( $tmp ) ) {
			         @unlink($file_array['tmp_name']);
			         $file_array['tmp_name'] = '';
			      }

			      // do the validation and storage stuff
			      $post_id=0;
			      $desc="Featured image for '$event_name'";
			      $id = media_handle_sideload( $file_array, $post_id, $desc );
			      // If error storing permanently, unlink
			      if ( is_wp_error($id) ) {
			         @unlink($file_array['tmp_name']);
			         return false;
			      }

			      $src = wp_get_attachment_url( $id );
			      return array(0=>$id,1=>$src);

		    }
		
	// SECONDARY FUNCTIONS
    	function eventon_plugin_links($links){
			$settings_link = '<a href="admin.php?page=evoics">Settings</a>'; 
			array_unshift($links, $settings_link); 
	 		return $links; 	
		}
}
