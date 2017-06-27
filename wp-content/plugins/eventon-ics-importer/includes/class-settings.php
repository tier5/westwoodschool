<?php
/**
 * Admin Settings for ICS importer
 * @version 0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class EVOICS_settings{
	function __construct(){
		$this->options = get_option('evcal_options_evoics');
		$this->options_2 = get_option('evcal_options_evoics_2');
		echo $this->content();
	}
	function content(){
		global $ajde;
		$ajde->load_ajde_backender();

		// Settings Tabs array
		$tabs = array(
			'evoics_1'=>__('Import ICS File','eventon'), 
			'evoics_2'=>__('General Settings','eventon'), 
		);

		$focus_tab = (isset($_GET['tab']) )? sanitize_text_field( urldecode($_GET['tab'])):'evoics_1';

		// Update or add options
			if( isset($_POST['evoics_noncename']) && isset( $_POST ) ){				
				if ( wp_verify_nonce( $_POST['evoics_noncename'], AJDE_EVCAL_BASENAME ) ){

					foreach($_POST as $pf=>$pv){
						$pv = (is_array($pv))? $pv: (htmlspecialchars ($pv) );
						$evo_options[$pf] = $pv;					
					}
					update_option('evcal_options_'.$focus_tab, $evo_options);
					$_POST['settings-updated']='Successfully updated values.';
				
				//nonce check	
				}else{
					die( __( 'Action failed. Please refresh the page and retry.', 'eventon' ) );
				}	
			}
		?>
		<div class="wrap" id='evoics_settings'>
			<div id='eventon'><div id="icon-themes" class="icon32"></div></div>
			<h1><?php _e('Settings for Importing Events','eventon');?> </h1>
			<h2 class='nav-tab-wrapper' id='meta_tabs'>
				<?php					
					foreach($tabs as $nt=>$ntv){	
						echo "<a href='?page=evoics&tab=".$nt."' class='nav-tab ".( ($focus_tab == $nt)? 'nav-tab-active':null)."' evo_meta='evoics_1'>".$ntv."</a>";
					}			
				?>
			</h2>	
		<div class='metabox-holder evo_settings_box'>		
		<?php			
		$updated_code = (isset($_POST['settings-updated']))? '<div class="updated fade"><p>'.$_POST['settings-updated'].'</p></div>':null;
		echo $updated_code;
				
		//TABS	
		switch ($focus_tab):	
		
		// Import step
			case "evoics_1":
				
				echo "<div id='evoics_1' class='postbox'><div class='inside'>";
				$steps = (!isset($_GET['steps']))?'ichi':$_GET['steps'];	
				echo $this->import_content($steps);
				echo "</div></div>";
			break;

			case "evoics_2":
				
				?>
				<form method="post" action=""><?php settings_fields('evoics_field_group'); 
					wp_nonce_field( AJDE_EVCAL_BASENAME, 'evoics_noncename' );
				
				echo "<div id='evoics_2' class='evcal_admin_meta evcal_focus'>";
				?>
				<div class="inside">					
				<?php
					// ARRAY
					$cutomization_pg_array = array(
						array(
							'id'=>'EVOICSa',
							'name'=>'ICS Function Settings','display'=>'show',
							'tab_name'=>'Settings','icon'=>'gears',
							'fields'=>array(
								array('id'=>'EVOICS_status_publish','type'=>'yesno','name'=>'Publish fetched events. (Default event post publish status is Draft)'),
								array('id'=>'EVOICS_auto_allday_dis','type'=>'yesno','name'=>'Disable auto detect all day events based on ICS event time','legend'=>'Timezone for the website need to be set as timezone string in wordpress settings. If not you can manually adjust times after import.'),
								array('id'=>'EVOICS_auto_timezone','type'=>'yesno','name'=>'Enable event time  adjusting based on this website\'s timezone'),	
						)),
					);					
					

					$opt2 = get_option('evcal_options_evoics_2');

					$updated_code = (isset($_POST['settings-updated']) && $_POST['settings-updated']=='true')? '<div class="updated fade"><p>Settings Saved</p></div>':null;
					echo $updated_code;
					
					print_ajde_customization_form($cutomization_pg_array, $opt2);
				?>

				</div>
				</div>
				<div class='evo_diag'>
					<input type="submit" class="evo_admin_btn btn_prime" value="<?php _e('Save Changes') ?>" /><br/><br/>
					<a target='_blank' href='http://www.myeventon.com/support/'><img src='<?php echo AJDE_EVCAL_URL;?>/assets/images/myeventon_resources.png'/></a>
				</div>
				</form>
				<?php
			break;
		endswitch;
		echo "</div>";
	}

	// import
		function import_content($step){
			global $evoics;

			switch ($step) {
				// reading file and showing results
				case 'ni':
					$this->display_events();
				break;
				case 'ichi':					
					ob_start();
				 	echo "<h2>".__('Get Started, Select a ICS file','eventon')."</h2>";
					echo "<p>".__('Select the properly formated ICS file with events to process before importing.','eventon')."</p>";
					echo "<form action='".admin_url()."admin.php?page=evoics&steps=ni' method='post' enctype='multipart/form-data'>";

						settings_fields('eventon_ics_field_grp'); 
						wp_nonce_field( $evoics->plugin_path, 'eventon_ics_noncename' );

					echo "<input type='file' name='events_ics_file'/><br/><br/>";
					echo "<input type='submit' name='' class='btn_prime evo_admin_btn' value='Upload .ICS file'/>";
					
					$this->print_guidelines();

					echo "</form>";

					echo ob_get_clean();
					
				break;
			}

		}

	// display fetched events list
		function display_events(){
			global $evoics;

			if( !$this->ics_verify_nonce_post( 'eventon_ics_noncename'))
				return false;

			// verified nonce
			if (empty($_FILES['events_ics_file']['tmp_name'])) {
				$this->log['error'][] = 'No file uploaded, Please try again!.';

				$this->print_messages();
				$this->import_content('ichi');
				return;
			}

			// load uploaded file content
			require_once('lib/class.iCalReader.php');
			$time_start = microtime(true);
			
			$file = $_FILES['events_ics_file']['tmp_name'];
			$ICS = new ical($file);

			$icsEVENTS = $ICS->events();

			$COUNT = count($icsEVENTS);
			
			echo "<h2>".__('Verify Processed Events & Import','eventon')."</h2>";
			echo "<p>".__('Please look through the events processed from the uploaded ICS file and select the ones you want to import into your website calendar.','eventon'). '<br/>Processed <b>'.$COUNT.'</b> items total.'."</p>";

			// if no items present on processed
			if($COUNT==0)
				echo "<p style='padding:4px 10px; background-color:#F9E5E1'>".__('IMPORTANT! We could not process any events from the ICS file provided by you. Either the ICS file is not properly built or you have no items in the ICS file. Please make sure you have constructed the ICS file according the the guidelines.','eventon')."</p>";

			if($COUNT==0) return false;

			echo "<form class='evoics_import_form' action='' method='post' enctype='multipart/form-data'>

				<p id='select_row_options'>
					<a class='deselect btn_triad evo_admin_btn'><span></span>Deselect All</a> <a class='select btn_triad evo_admin_btn'><span></span>Select All</a> <input id='evoics_import_selected' style='display:none; float:right' type='submit' class='btn_prime evo_admin_btn' value='".__('Import Selected Events','eventon')."'/>
					<a id='evoics_import_selected_items' class='btn_prime evo_admin_btn'><span></span>IMPORT</a>
				</p>

				<div id='evoics_import_progress' style='display:none'>
					<p class='bar'><span></span></p>
					<p class='text'><em>0</em> out of <i>".$COUNT."</i> processed. <b class='loading'></b><span class='failed' style='display:none'><em></em> Failed</span></p>					
				</div>

				<div id='evoics_import_results' style='display:none'>
					<p class='results'><b></b>Import complete! <span class='good'><em>1</em> Imported</span> <span class='bad'><em>0</em> Failed</span></p>
					<p><a href='".admin_url()."edit.php?post_type=ajde_events'>View all imported events</a></p>
				</div>

				<p id='evoics_import_errors' style='display:none'>Error</p>
			
				<div id='evoics_fetched_events'>";
				settings_fields('eventon_ics_field_grp'); 
				wp_nonce_field( $evoics->plugin_path, 'eventon_ics_noncename' );

			echo "<table id='evoics_events' class='wp-list-table widefat'>
				<thead><tr>
					<th>".__('Status','eventon')."</th>
					<th>".__('Event Name','eventon')."</th>
					<th>".__('Description','eventon')."</th>
					<th>".__('Start Date & Time','eventon')."</th>
					<th>".__('End Date & Time','eventon')."</th>
					<th>".__('Location','eventon')."</th>
					</tr>
				</thead><tbody>";

			$count = 1; $skipped = 0;
			
			foreach($icsEVENTS as $ics_data){
				//print_r($ics_data);

				$ics_data = $this->validate_base_fields($ics_data);

				// check if event has valid event date
				if( empty($ics_data['event_start_date'])){
					$skipped ++; continue;
				}

				echo "<tr class='row' data-status='ss'><td>";
				
				$this->hidden_fields($ics_data, $count);

				// event times
					$startTime = $ics_data['evcal_allday']=='yes'?'All Day': $ics_data['event_start_time'];
					$endTime = $ics_data['evcal_allday']=='yes'?'All Day': $ics_data['event_end_time'];

				echo "<span class='status ss' title='Selected'></span></td>";
				echo "<td><span>".$ics_data['event_name']."</span></td>";
				echo "<td class='event_desc'><span class='".(!empty($ics_data['DESCRIPTION'])?'check':'bar')."'></span></td>";

				echo "<td>{$ics_data['event_start_date']}<br/>{$startTime}</td>";
				echo "<td>{$ics_data['event_end_date']}<br/>{$endTime}</td>";
				?>
				<td title='<?php echo (!empty($ics_data['LOCATION'])? $ics_data['LOCATION']:'');?>'><span class='<?php echo (!empty($ics_data['LOCATION'])?'check':'bar'); ?> eventon_ics_icons'></span></td>
				<?php
				echo "</tr>";

				$count ++;
			}
			echo "</tbody></table></div>";

			// skipped ics processed events notice
			if($skipped>0) echo "<p>Skipped events due to lack of required information: ".$skipped. "</p>";

			echo "</form>";
		}

		// validate base fields such as date, time description
			function validate_base_fields($ics_data){
				// defaults
					$ics_data['evcal_allday'] ='no';
					$timezoneADJ = (!empty($this->options_2['EVOICS_auto_timezone']) && $this->options_2['EVOICS_auto_timezone']='yes')? true: false;
					$alldayADJ = (!empty( $this->options_2['EVOICS_auto_allday_dis']) && $this->options_2['EVOICS_auto_allday_dis']=='yes')? false: true;
					$WPtimezone = get_option( 'timezone_string');
						$WPtimezone = (empty($WPtimezone)? false:$WPtimezone );

				// event date validation
					if(!empty($ics_data['DTSTART'])){
						$dt = new DateTime($ics_data['DTSTART']);

						if($timezoneADJ && $WPtimezone){
							$dt->setTimeZone( new DateTimezone($WPtimezone) );
						}
						
						$event_start_date_val= $dt->format('m/d/Y');
						$event_start_time_val= $dt->format('g:i:a');
					}else{ 
						$event_start_date_val =null;	
						$event_start_time_val =null;
					}
				
				// End time
					if(!empty($ics_data['DTEND'])){
						$dt = new DateTime($ics_data['DTEND']);
						
						if($timezoneADJ && $WPtimezone){
							$dt->setTimeZone( new DateTimezone($WPtimezone) );
						}

						$event_end_date_val = $dt->format('m/d/Y');
						$event_end_time_val = $dt->format('g:i:a');
					}else{ 
						$event_end_time_val =$event_start_time_val;	
						$event_end_date_val = $event_start_date_val;
					}								
				
				// description
					$event_description = (!empty($ics_data['DESCRIPTION']))? 
						html_entity_decode(convert_chars(addslashes($ics_data['DESCRIPTION'] ))): null;

				// Auto detect all day event
					if($event_start_time_val == '12:00:am' && $event_end_time_val =='12:00:am' && $alldayADJ)
						$ics_data['evcal_allday'] = 'yes';

				$ics_data['event_start_date'] = $event_start_date_val;
				$ics_data['event_start_time'] = $event_start_time_val;
				$ics_data['event_end_date'] = $event_end_date_val;
				$ics_data['event_end_time'] = $event_end_time_val;
				$ics_data['event_description'] = $event_description;

				// event name
					$eventName = (!empty($ics_data['SUMMARY']))?
						html_entity_decode($ics_data['SUMMARY']):	$event_start_date_val;
					$ics_data['event_name'] = $eventName;

				// Location 
					if(!empty($ics_data['LOCATION']))
						$ics_data['evcal_location_name'] = $ics_data['LOCATION'];

				// pluggable
					$ics_data = apply_filters('evoics_additional_data_validation', $ics_data);

				return $ics_data;
			}

		// throw input and textfields hidden fields
			function hidden_fields($ics_data, $count){	
				global $evoics;

				$textarea_fields = array('event_description');

				?><input class='input_status' type='hidden' name='events[<?php echo $count;?>][status]' value='ss'/><?php
				foreach($evoics->admin->get_all_fields() as $field){
					if(empty( $ics_data[$field])) continue;

					if(in_array($field, $textarea_fields)){
						echo "<textarea class='evoics_event_data_row' style='display:none' name='events[{$count}][{$field}]'>". (!empty($ics_data[$field])? addslashes($ics_data[$field]):'')."</textarea>";
					}else{
						echo "<input class='evoics_event_data_row' type='hidden' name='events[{$count}][{$field}]' ". 'value="'. ( addslashes($ics_data[$field]) ).'"/>';
					}	
				}
			}

	    /** function to verify wp nonce and the $_POST array submit values	 */
			function ics_verify_nonce_post($post_field){
				global $_POST, $evoics;

				if(isset( $_POST ) && !empty($_POST[$post_field]) && $_POST[$post_field]  ){
					if ( wp_verify_nonce( $_POST[$post_field],  $evoics->plugin_path )){
						return true;
					}else{	
						$this->log['error'][] =__("Could not verify submission. Please try again.",'eventon');
						$this->print_messages();
						return false;	}
				}else{	
					$this->log['error'][] =__("Could not verify submission. Please try again.",'eventon');
					$this->print_messages();
					return false;	
				}
			}

		/** Print the messages for the ics settings	 */
			function print_messages(){
				if (!empty($this->log)) {
					
					if (!empty($this->log['error'])): ?>
					
					<div class="error">
						<?php foreach ($this->log['error'] as $error): ?>
							<p class=''><?php echo $error; ?></p>
						<?php endforeach; ?>
					</div>			
					<?php endif; ?>
					
					
					<?php if (!empty($this->log['notice'])): ?>
					<div class="updated fade">
						<?php foreach ($this->log['notice'] as $notice): ?>
							<p><?php echo $notice; ?></p>
						<?php endforeach; ?>
					</div>
					<?php endif; 
								
					$this->log = array();
				}
			}
		/** Print guidelines messages	 */
			function print_guidelines(){
				global $eventon, $evoics;
				
				ob_start();
				
				require_once($evoics->plugin_path.'/guide.php');
				
				$content = ob_get_clean();
				
				echo $eventon->output_eventon_pop_window( 
					array('content'=>$content, 'title'=>'How to use ICS Importer', 'type'=>'padded')
				);
				?>					
					<h3><?php _e('**ICS file guidelines','eventon')?></h3>
					<p><?php _e('Please read the below guide for proper .ICS file that is acceptable with this addon. Along with this addon, in the main addon file folder you should find a <b>sample.ics</b> file that can be used to help guide for creation of ics file.','eventon');?></p>
					<a type='submit' name='' id='eventon_ics_guide_trig' class=' ajde_popup_trig btn_secondary evo_admin_btn'>Guide for ICS File</a>

				<?php
			}	

	// SUPPRORTING
		//process string ids into an array
			function process_ids($ids){
				if(empty($ids))
					return false;

				$uids = str_replace(' ', '', $ids);
				if(strpos($uids, ',')=== false){
					$uids = array($uids);
				}else{
					$uids = explode(',', $uids);
				}
				return $uids;
			}

		// ics file stripping
			function stripBOM($fname) {
		        $res = fopen($fname, 'rb');
		        if (false !== $res) {
		            $bytes = fread($res, 3);
		            if ($bytes == pack('CCC', 0xef, 0xbb, 0xbf)) {
		                $this->log['notice'][] = 'Getting rid of byte order mark...';
		                fclose($res);

		                $contents = file_get_contents($fname);
		                if (false === $contents) {
		                    trigger_error('Failed to get file contents.', E_USER_WARNING);
		                }
		                $contents = substr($contents, 3);
		                $success = file_put_contents($fname, $contents);
		                if (false === $success) {
		                    trigger_error('Failed to put file contents.', E_USER_WARNING);
		                }
		            } else {
		                fclose($res);
		            }
		        } else {
		            $this->log['error'][] = 'Failed to open file, aborting.';
		        }
		    }
}
new EVOICS_settings();