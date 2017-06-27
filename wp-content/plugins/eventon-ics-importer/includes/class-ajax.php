<?php
/**
 * AJAX functions for the CSV importer
 * @version 1.0
 */
class EVOICS_ajax{
	// construct
		public function __construct(){
			$ajax_events = array(
				'evoics_001'=>'evoics_001',
			);
			foreach ( $ajax_events as $ajax_event => $class ) {				
				add_action( 'wp_ajax_'.  $ajax_event, array( $this, $class ) );
				add_action( 'wp_ajax_nopriv_'.  $ajax_event, array( $this, $class ) );
			}
		}
	// import individual event
		public function evoics_001(){

			if(!is_admin()) exit;

			if(!isset($_POST['events'])){
				$return_content = array(	'status'=>'No events submitted'	);				
				echo json_encode($return_content);		
				exit;
			}else{
				global $evoics;
				$menuitem_data = $_POST['events'];
				foreach($menuitem_data as $menudata){
					//print_r($menudata);
					
					$processedDATA = '';
					foreach($menudata as $MDK=>$MD){
						$processedDATA[$MDK] = urldecode($MD);
					}
					
					$status = $evoics->admin->import_event($processedDATA);
				}
			}

			$return_content = array('content'=> '','status'=>$status	);				
			echo json_encode($return_content);		
			exit;
		}

	// supporting stuff
		function sanitize_csv_field($value){
			return '"' . addslashes(str_replace('"',"'",$value)) . '"';
		}
}
new EVOICS_ajax();