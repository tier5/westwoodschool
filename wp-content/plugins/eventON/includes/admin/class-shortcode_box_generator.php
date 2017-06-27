<?php
/**
 * EventON Admin Include
 *
 * Include for EventON related events in admin.
 *
 * @author 		AJDE
 * @category 	Admin
 * @package 	EventON/Admin
 * @version     2.3.21
 */
class eventon_admin_shortcode_box{
	
	private $_in_select_step=false;
	private $evopt;

	function __construct(){
		$this->evopt =  get_option('evcal_options_evcal_1');
	}
	
	public function shortcode_default_field($key){
		$options_1 = $this->evopt;

		// Additional Event Type taxonomies 
			$event_types_sc = array();
			for( $x=1; $x <= (apply_filters('evo_event_type_count',5)); $x++){
				if($x <=2 ) continue;
				if(!empty($options_1['evcal_ett_'.$x]) && $options_1['evcal_ett_'.$x]=='yes' && !empty($options_1['evcal_eventt'.$x])){
				 	$event_types_sc['event_type_'.$x] = array(
						'name'=>'Event Type '.$x,
						'type'=>'taxonomy',
						'guide'=>'Event Type '.$x.' category IDs - seperate by commas (eg. 3,12)',
						'placeholder'=>'eg. 3, 12',
						'var'=>'event_type_'.$x,
						'default'=>'0'
					);
				}else{ $event_types_sc['event_type_'.$x] = array(); }
			}

		
		$SC_defaults = array(
			'cal_id'=>array(
				'name'=>'Calendar ID (optional)',
				'type'=>'text',
				'var'=>'cal_id',
				'default'=>'0',
				'placeholder'=>'eg. 1'
			),
			'number_of_months'=>array(
				'name'=>'Number of Months',
				'type'=>'text',
				'var'=>'number_of_months',
				'default'=>'0',
				'placeholder'=>'eg. 5'
			),		
			'show_et_ft_img'=>array(
				'name'=>'Show Featured Image',
				'type'=>'YN',
				'var'=>'show_et_ft_img',
				'default'=>'no'
			),
			'hide_past'=>array(
				'name'=>'Hide Past Events',
				'type'=>'YN',
				'var'=>'hide_past',
				'default'=>'no'
			),'hide_past_by'=>array(
				'name'=>'Hide Past Events by',
				'guide'=>'You can choose which date (start or end) to use to decide when to clasify them as past events.',
				'type'=>'select',
				'var'=>'hide_past_by',
				'default'=>'ee',
				'options'=>array( 
					'ss'=>'Start Date/time',
					'ee'=>'End Date/Time',
				)
			),
			'ft_event_priority'=>array(
				'name'=>'Feature event priority',
				'type'=>'YN',
				'guide'=>'Move featured events above others',
				'var'=>'ft_event_priority',
				'default'=>'no',
			),
			'event_count'=>array(
				'name'=>'Event count limit',
				'placeholder'=>'eg. 3',
				'type'=>'text',
				'guide'=>'Limit number of events for each month eg. 3',
				'var'=>'event_count',
				'default'=>'0'
			),
			'month_incre'=>array(
				'name'=>'Month Increment',
				'type'=>'text',
				'placeholder'=>'eg. +1',
				'guide'=>'Change starting month (eg. +1)',
				'var'=>'month_incre',
				'default'=>'0'
			),
			'event_type'=>array(
				'name'=>'Event Type',
				'type'=>'taxonomy',
				'guide'=>'Event Type category IDs - seperate by commas (eg. 3,12)',
				'placeholder'=>'eg. 3, 12',
				'var'=>'event_type',
				'default'=>'0'
			),'event_type_2'=>array(
				'name'=>'Event Type 2',
				'type'=>'taxonomy',
				'guide'=>'Event Type 2 category IDs - seperate by commas (eg. 3,12)',
				'placeholder'=>'eg. 3, 12',
				'var'=>'event_type_2',
				'default'=>'0'
			),
			'event_type_3'=>$event_types_sc['event_type_3'],
			'event_type_4'=>$event_types_sc['event_type_4'],
			'event_type_5'=>$event_types_sc['event_type_5'],
			'fixed_month'=>array(
				'name'=>'Fixed Month',
				'type'=>'text',
				'guide'=>'Set fixed month for calendar start (integer)',
				'var'=>'fixed_month',
				'default'=>'0',
				'placeholder'=>'eg. 10'
			),
			'fixed_year'=>array(
				'name'=>'Fixed Year',
				'type'=>'text',
				'guide'=>'Set fixed year for calendar start (integer)',
				'var'=>'fixed_year',
				'default'=>'0',
				'placeholder'=>'eg. 2013'
			),
			'event_order'=>array(
				'name'=>'Event Order',
				'type'=>'select',
				'guide'=>'Select ascending or descending order for event. By default it will be Ascending order.',
				'var'=>'event_order',
				'default'=>'ASC',
				'options'=>array('ASC'=>'ASC','DESC'=>'DESC')
			),
			'pec'=>array(
				'name'=>'Event Cut-off',
				'type'=>'select',
				'guide'=>'Past or upcoming events cut-off time. This will allow you to override past event cut-off settings for calendar events. Current date = today at 12:00am',
				'var'=>'pec',
				'default'=>'Current Time',
				'options'=>array( 
					'ct'=>'Current Time: '.date('m/j/Y g:i a', current_time('timestamp')),
					'cd'=>'Current Date: '.date('m/j/Y', current_time('timestamp')),
				)
			),
			'lang'=>array(
				'name'=>'Language Variation (<a href="'.get_admin_url().'admin.php?page=eventon&tab=evcal_2">Update Language Text</a>)',
				'type'=>'select',
				'guide'=>'Select which language variation text to use',
				'var'=>'lang',
				'default'=>'L1',
				'options'=>array('L1'=>'L1','L2'=>'L2','L3'=>'L3')
			),
			'hide_mult_occur'=>array(
				'name'=>'Hide multiple occurence (HMO)',
				'type'=>'YN',
				'guide'=>'Hide events from showing more than once between months',
				'var'=>'hide_mult_occur',
				'default'=>'no',
			),
			'show_repeats'=>array(
				'name'=>'Show all repeating events while HMO',
				'type'=>'YN',
				'guide'=>'If you are hiding multiple occurence of event but want to show all repeating events set this to yes',
				'var'=>'show_repeats',
				'default'=>'no',
			),
			'fixed_mo_yr'=>array(
				'name'=>'Fixed Month/Year',
				'type'=>'fmy',
				'guide'=>'Set fixed month and year value (Both values required)(integer)',
				'var'=>'fixed_my',
			),'fixed_d_m_y'=>array(
				'name'=>'Fixed Date/Month/Year',
				'type'=>'fdmy',
				'guide'=>'Set fixed date, month and year value (All values required)(integer)',
				'var'=>'fixed_my',
			),'evc_open'=>array(
				'name'=>'Open eventCards on load',
				'type'=>'YN',
				'guide'=>'Open eventCards when the calendar first load on the page by default. This will override the settings saved for default calendar.',
				'var'=>'evc_open',
				'default'=>'no',
			),'UIX'=>array(
				'name'=>'User Interaction',
				'type'=>'select',
				'guide'=>'Select the user interaction option to override individual event user interactions',
				'var'=>'ux_val',
				'default'=>'0',
				'options'=>apply_filters('eventon_uix_shortcode_opts', array('0'=>'None','X'=>'Do not interact','1'=>'Slide Down EventCard','3'=>'Lightbox popup window'))
			),'etc_override'=>array(
				'name'=>'Event type color override',
				'type'=>'YN',
				'guide'=>'Select this option to override event colors with event type colors, if they exists',
				'var'=>'etc_override',
				'default'=>'no',
			),'only_ft'=>array(
				'name'=>'Show only featured events',
				'type'=>'YN',
				'guide'=>'Display only featured events in the calendar',
				'var'=>'only_ft',
				'default'=>'no',
			),'jumper'=>array(
				'name'=>'Show jump months option',
				'type'=>'YN',
				'guide'=>'Display month jumper on the calendar',
				'var'=>'jumper',
				'default'=>'no',
			),'accord'=>array(
				'name'=>'Accordion effect on eventcards','type'=>'YN',
				'guide'=>'This will close open events when new one clicked','var'=>'accord','default'=>'no',
			),'sort_by'=>array(
				'name'=>'Default Sort by',
				'type'=>'select',
				'guide'=>'Sort calendar events by on load',
				'var'=>'sort_by',
				'default'=>'sort_date',
				'options'=>array( 
					'sort_date'=>'Date',
					'sort_title'=>'Title',
					'sort_posted'=>'Posted Date',
					'sort_rand'=>'Random Order',
				)
			),'hide_sortO'=>array(
				'name'=>'Hide sort options section',
				'type'=>'YN',
				'guide'=>'This will hide sort options section on the calendar.',
				'var'=>'hide_so',
				'default'=>'no',
			),'expand_sortO'=>array(
				'name'=>'Expand sort options by default',
				'type'=>'YN',
				'guide'=>'This will expand sort options section on load for calendar.',
				'var'=>'exp_so',
				'default'=>'no',
			),'rtl'=>array(
				'name'=>'* RTL can now be changed from eventON settings',
				'type'=>'note',
				'var'=>'rtl',
				'default'=>'no',
			),'show_limit'=>array(
				'name'=>'Show load more events button',
				'type'=>'YN',
				'guide'=>'Require "event count limit" to work, then this will add a button to show rest of the events for calendar in increments',
				'var'=>'show_limit',
				'default'=>'no',
			),'show_limit_redir'=>array(
				'name'=>'Redirect load more events button',
				'type'=>'text',
				'guide'=>'http:// URL the load more events button will redirect to instead of loading more events on the same calendar.',
				'var'=>'show_limit_redir',
				'default'=>'no',
			),'members_only'=>array(
				'name'=>'Make this calendar only visible to loggedin user',
				'type'=>'YN',
				'guide'=>'This will make this calendar only visible to loggedin users',
				'var'=>'members_only',
				'default'=>'no',
			),'layout_changer'=>array(
				'name'=>'Show calendar layout changer',
				'type'=>'YN',
				'guide'=>'Show layout changer on calendar so users can choose between tiles or rows layout',
				'var'=>'layout_changer',
				'default'=>'no',
			)

		);
		
		return $SC_defaults[$key];
	
	}	
	
	// array of shortcode variables
		public function get_shortcode_field_array(){
			$_current_year = date('Y');
			$shortcode_guide_array = apply_filters('eventon_shortcode_popup', array(
				array(
					'id'=>'s1',
					'name'=>'Main Calendar',
					'code'=>'add_eventon',
					'variables'=>apply_filters('eventon_basiccal_shortcodebox', array(
						$this->shortcode_default_field('cal_id')
						,$this->shortcode_default_field('show_et_ft_img')
						,$this->shortcode_default_field('ft_event_priority')
						,$this->shortcode_default_field('only_ft')
						,$this->shortcode_default_field('hide_past')	
						,$this->shortcode_default_field('hide_past_by')	
						,$this->shortcode_default_field('sort_by')
						,$this->shortcode_default_field('event_order')
						,$this->shortcode_default_field('event_count')
						,$this->shortcode_default_field('show_limit')
						,$this->shortcode_default_field('show_limit_redir')
						,$this->shortcode_default_field('month_incre')
						,$this->shortcode_default_field('event_type')
						,$this->shortcode_default_field('event_type_2')
						,$this->shortcode_default_field('event_type_3')
						,$this->shortcode_default_field('event_type_4')
						,$this->shortcode_default_field('event_type_5')
						,$this->shortcode_default_field('etc_override')
						,$this->shortcode_default_field('fixed_mo_yr')						
						,$this->shortcode_default_field('lang')
						,$this->shortcode_default_field('UIX')
						,$this->shortcode_default_field('evc_open')					
						,array(
								'name'=>'Show jump months option',
								'type'=>'YN',
								'guide'=>'Display month jumper on the calendar',
								'var'=>'jumper',
								'default'=>'no',
								'afterstatement'=>'jumper_offset'
							),array(
								'name'=>' Jumper Start Year',
								'type'=>'select',
								'options'=>array(
									'0'=>$_current_year-2,
									'1'=>$_current_year-1,
									'2'=>$_current_year,
									),
								'guide'=>'Select which year you want to set to start jumper options at relative to current year',
								'var'=>'jumper_offset','default'=>'0',
								'closestatement'=>'jumper_offset'
							)

						,$this->shortcode_default_field('hide_sortO')						
						,$this->shortcode_default_field('expand_sortO')
						,$this->shortcode_default_field('accord')
						,array(
								'name'=>'Hide Calendar Arrows',
								'type'=>'YN',
								'guide'=>'This will hide calendar arrow navigations',
								'var'=>'hide_arrows',
								'default'=>'no',
							)
						,
							array(
								'name'=>'Activate Tile Design',
								'type'=>'YN',
								'guide'=>'This will activate the tile event design for calendar instead of rows of events.',
								'default'=>'no',
								'var'=>'tiles',
								'afterstatement'=>'tiles'
							),
							array(
								'name'=>'Tile Box Height (px)',
								'placeholder'=>'eg. 200',
								'type'=>'text',
								'guide'=>'Set the fixed height of event tile for the tiled calendar design',
								'var'=>'tile_height','default'=>'0'
							),array(
								'name'=>'Tile Background Color',
								'type'=>'select',
								'options'=>array(
									'0'=>'Event Color',
									'1'=>'Featured Image',
									),
								'guide'=>'Select the type of background for the event tile design',
								'var'=>'tile_bg','default'=>'0'
							),array(
								'name'=>'Number of Tiles in a Row',
								'type'=>'select',
								'options'=>array(
									'2'=>'2',
									'3'=>'3',
									'4'=>'4',
									),
								'guide'=>'Select the number of tiles to show on one row',
								'var'=>'tile_count','default'=>'0'
							),
							/*array(
								'name'=>'Tile Style',
								'type'=>'select',
								'options'=>array(
									'0'=>'Default',
									'1'=>'Top bar',
									),
								'guide'=>'With this you can select different layout styles for tiles',
								'var'=>'tile_style','default'=>'0'
							),*/
							array(
								'name'=>'Custom Code',
								'type'=>'customcode', 'value'=>'',
								'closestatement'=>'tiles'
							)
						,
						$this->shortcode_default_field('members_only')
						
					))
				),
				array(
					'id'=>'s2',
					'name'=>'Events List',
					'code'=>'add_eventon_list',
					'variables'=>array(
						$this->shortcode_default_field('number_of_months')
						,array(
							'name'=>'Event count limit',
							'placeholder'=>'eg. 3',
							'type'=>'text',
							'guide'=>'Limit number of events per month (integer)',
							'var'=>'event_count',
							'default'=>'0'
						)
						,$this->shortcode_default_field('show_limit')
						,$this->shortcode_default_field('show_limit_redir')
						,$this->shortcode_default_field('month_incre')
						,$this->shortcode_default_field('fixed_mo_yr')
						,$this->shortcode_default_field('cal_id')
						,$this->shortcode_default_field('event_order')
						,$this->shortcode_default_field('hide_past')
						,$this->shortcode_default_field('hide_past_by')
						,$this->shortcode_default_field('event_type')
						,$this->shortcode_default_field('event_type_2')
						,$this->shortcode_default_field('event_type_3')
						,$this->shortcode_default_field('event_type_4')
						,$this->shortcode_default_field('event_type_5')	
						,$this->shortcode_default_field('hide_mult_occur'),
						array(
							'name'=>'Show all repeating events while HMO',
							'type'=>'YN',
							'guide'=>'If you are hiding multiple occurence of event but want to show all repeating events set this to yes',
							'var'=>'show_repeats',
							'default'=>'no',
						),array(
							'name'=>'Hide empty months',
							'type'=>'YN',
							'guide'=>'Hide months without any events on the events list',
							'var'=>'hide_empty_months',
							'default'=>'no',
						),array(
							'name'=>'Show year',
							'type'=>'YN',
							'guide'=>'Show year next to month name on the events list',
							'var'=>'show_year',
							'default'=>'no',
						),$this->shortcode_default_field('ft_event_priority'),
						$this->shortcode_default_field('only_ft'),
						$this->shortcode_default_field('etc_override'),
						$this->shortcode_default_field('accord'),
						array(
								'name'=>'Activate Tile Design',
								'type'=>'YN',
								'guide'=>'This will activate the tile event design for calendar instead of rows of events.',
								'default'=>'no',
								'var'=>'tiles',
								'afterstatement'=>'tiles'
							),
							array(
								'name'=>'Tile Box Height (px)',
								'placeholder'=>'eg. 200',
								'type'=>'text',
								'guide'=>'Set the fixed height of event tile for the tiled calendar design',
								'var'=>'tile_height','default'=>'0'
							),array(
								'name'=>'Tile Background Color',
								'type'=>'select',
								'options'=>array(
									'0'=>'Event Color',
									'1'=>'Featured Image',
									),
								'guide'=>'Select the type of background for the event tile design',
								'var'=>'tile_bg','default'=>'0'
							),array(
								'name'=>'Number of Tiles in a Row',
								'type'=>'select',
								'options'=>array(
									'2'=>'2',
									'3'=>'3',
									'4'=>'4',
									),
								'guide'=>'Select the number of tiles to show on one row',
								'var'=>'tile_count','default'=>'0'
							),array(
								'name'=>'Custom Code',
								'type'=>'customcode', 'value'=>'',
								'closestatement'=>'tiles'
							)
						
					)
				)
			));
			
			return $shortcode_guide_array;
		}

	// get content for shortcode generator
		public function get_content(){
			global $ajde, $eventon;

			if(!$eventon->evo_updater->kriyathmakada()) 
				return '<p style="padding:10px;text-align:center">'.$eventon->evo_updater->akriyamath_niwedanaya() .'</p>';
			
			return $ajde->wp_admin->get_content(
				$this->get_shortcode_field_array(),
				'add_eventon'
			);
		}
}

$GLOBALS['evo_shortcode_box'] = new eventon_admin_shortcode_box();
?>