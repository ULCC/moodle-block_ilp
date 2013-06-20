<?php 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');



class ilp_mis_misc_timetable extends ilp_mis_plugin	{

	protected 	$fields;

	/*
	 * will hold the mis id of the user we want data on 
	 */
	protected 	$mis_user_id;
	
	/*
	 * Will hold the current term week
	 */
	protected 	$termweek;
	
	/*
	 * Will hold the week that will be retrieved
	 */
	protected 	$timetableweek;
	
	
	/**
	 * 
	 * Constructor for the class
	 * @param array $params should hold any vars that are needed by plugin. can also hold the 
	 * 						the connection string vars if they are different from those specified 
	 * 						in the mis connection
	 */
	
 	function	__construct($params=array())	{
 		parent::__construct($params);
 		
 		$this->tabletype	=	get_config('block_ilp','mis_misc_timetable_tabletype');
 		$this->fields		=	array();
 		
 	 	//get the current week in the year
 		$realweek	=	date('W',time());
 			
 		//get the term start week from config
 		$termstart		=   get_config('block_ilp','mis_misc_timetable_termstart');
			 			
 		//get the week in the year of termstart
 		$termstartweek	=	(!empty($termstart)) ? date('W',strtotime($termstart)) : 40; 
 		$this->termweek		=	0;
 			
 		if ($realweek < $termstartweek) {
 				//if the termstartweek is less than the term week should be 
 				$offset	=	56 - $termstartweek;
				$this->termweek	=	$realweek + $offset;
 				
 		} else {
 			$this->termweek	=	$realweek - $termstartweek;
 		}
 		
 	}
 	
 	/**
 	 * 
 	 * @see ilp_mis_plugin::display()
 	 */
 	function display()	{
 		global $CFG, $PARSER;
        
        // set up the flexible table for displaying the data

 			// set up the flexible table for displaying the portfolios
			
	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'timetable',true ,'ilp_mis_misc_timetable');
	
	        //create headers
	        
	        //setup the headers and columns with the fields that have been requested 
	        $headers		=	array();
	        $columns		=	array();
	        
	        if (get_config('block_ilp','mis_misc_timetable_date')) 		$headers[]		=	get_string('ilp_mis_misc_timetable_day_disp','block_ilp');
	        if (get_config('block_ilp','mis_misc_timetable_date')) 		$headers[]		=	get_string('ilp_mis_misc_timetable_date_disp','block_ilp');
	        if (get_config('block_ilp','mis_misc_timetable_register'))  $headers[]		=	get_string('ilp_mis_misc_timetable_register_disp','block_ilp');
	        if (get_config('block_ilp','mis_misc_timetable_room'))  	$headers[]		=	get_string('ilp_mis_misc_timetable_room_disp','block_ilp');
	        if (get_config('block_ilp','mis_misc_timetable_starttime')) $headers[]		=	get_string('ilp_mis_misc_timetable_starttime_disp','block_ilp');
	        if (get_config('block_ilp','mis_misc_timetable_endtime'))  	$headers[]		=	get_string('ilp_mis_misc_timetable_endtime_disp','block_ilp');
	        if (get_config('block_ilp','mis_misc_timetable_tutor'))  	$headers[]		=	get_string('ilp_mis_misc_timetable_tutor_disp','block_ilp');
	        
	        if (get_config('block_ilp','mis_misc_timetable_date'))		$columns[]		=	'day';
	        if (get_config('block_ilp','mis_misc_timetable_date'))		$columns[]		=	'date';
	        if (get_config('block_ilp','mis_misc_timetable_register'))	$columns[]		=	'register';
	        if (get_config('block_ilp','mis_misc_timetable_room'))		$columns[]		=	'room';
	        if (get_config('block_ilp','mis_misc_timetable_starttime'))	$columns[]		=	'starttime';
	        if (get_config('block_ilp','mis_misc_timetable_endtime'))	$columns[]		=	'endtime';
	        if (get_config('block_ilp','mis_misc_timetable_tutor'))		$columns[]		=	'tutor';

	        //define the columns in the tables
	        $flextable->define_columns($columns);
	        
	        //define the headers in the tables
	        $flextable->define_headers($headers);
	        
	        //we do not need the intialbars
	        $flextable->initialbars(false);
	        
	        $flextable->set_attribute('class', 'flexible generaltable');
	        
	        
	        $flextable->wrap_start_extra	=	"<h3>".get_string('ilp_mis_misc_timetable_timetable_disp','block_ilp')." ".get_string('ilp_mis_misc_timetable_week_disp','block_ilp')." {$this->timetableweek} </h3>";
	        
			$params		=	explode('&',$_SERVER['QUERY_STRING']);

			$urlparams	=	"";
			
			foreach ($params as $v) {
				if (strpos($v,'timetableweek') === FALSE) {
					$urlparams	.= "{$v}&";
				}
			}	       
	        									
	        $url	=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?$urlparams";						
				        
	        $previous						=	$this->timetableweek	-	1;
			$next							=	$this->timetableweek	+	1;
				        
	        $currentweeklink				=	"<a href='{$url}&timetableweek={$this->termweek}'>Current</a>" ;
	        $nextweeklink					=	"<a href='{$url}&timetableweek={$next}'>".get_string('ilp_mis_misc_timetable_next_disp','block_ilp')." &#62;&#62;</a>";
	        $previousweeklink				=	($previous > 1) ?  "<a href='{$url}&timetableweek={$previous}'>&#60;&#60; ".get_string('ilp_mis_misc_timetable_previous_disp','block_ilp'). "</a>" : 
	        									get_string('ilp_mis_misc_timetable_previous_disp','block_ilp'); 									
	        									
			$flextable->wrap_finish_extra	=	"<span id='ilp_mis_misc_timetable_footer'> {$previousweeklink} | {$currentweeklink} | {$nextweeklink}</span>";	        									
	        									
	        //setup the flextable
	        $flextable->setup();
	        
	        $i	=	0;
	        $total	=	0;
	        if (!empty($this->data)) {
		        //add the row to table
		        foreach( $this->data as $row ){
		        	
		        	if (get_config('block_ilp','mis_misc_timetable_date'))	{
		        		$date				=	$row[get_config('block_ilp','mis_misc_timetable_date')];
		        	   	$datetimestamp		=	strtotime($date);
		        	
		        		$data['day']		=	date('D',$datetimestamp);
		        		$data['date']		=	date('d/m',$datetimestamp);
		        	}
		        	
		        	if (get_config('block_ilp','mis_misc_timetable_register'))	$data['register']	=	$row[get_config('block_ilp','mis_misc_timetable_register')];
		        	
		        	if (get_config('block_ilp','mis_misc_timetable_room'))		$data['room']		=	$row[get_config('block_ilp','mis_misc_timetable_room')];
		        	
		        	if (get_config('block_ilp','mis_misc_timetable_starttime')) {
		        		$start				=	strtotime($row[get_config('block_ilp','mis_misc_timetable_starttime')]);
		        		$data['starttime']	=	date('G:i',$start);
		        	}
		        	
		        	if (get_config('block_ilp','mis_misc_timetable_endtime'))	{
		        		$end				=	strtotime($row[get_config('block_ilp','mis_misc_timetable_endtime')]);
		        		$data['endtime']	=	date('G:i',$end);
		        	}
		        	
		        	if (get_config('block_ilp','mis_misc_timetable_tutor'))		$data['tutor']		=	$row[get_config('block_ilp','mis_misc_timetable_tutor')];
		        	
		            $flextable->add_data_keyed( $data );
		        }
	        }
	        //buffer out as flextable sends its data straight to the screen we dont want this  
			ob_start();
			
			//call the html file for the plugin which has the flextable print statement
 
			$flextable->print_html();
			
			$pluginoutput = ob_get_contents();
			
	        ob_end_clean();
 			
 			return $pluginoutput;

 	} 
 	
 	/**
 	 * Retrieves data from the mis 
 	 * 
 	 * @param	$mis_user_id	the id of the user in the mis used to retrieve the data of the user
 	 * @param	$user_id		the id of the user in moodle
 	 *
 	 * @return	null
 	 */
 	
 	
    public function set_data( $mis_user_id, $user_id=null ){
    		global $PARSER;

    		//get the week we want 
    		$this->timetableweek 	=	$PARSER->optional_param('timetableweek',$this->termweek,PARAM_INT);
    	
    		//is the id a string or a int
    		$idtype	=	get_config('block_ilp','mis_plugin_course_idtype');
    		$mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;
    		
    		$this->mis_user_id	=	$mis_user_id;
    		
    		$table	=	get_config('block_ilp','mis_misc_timetable_table');
    		
			if (!empty($table)) {
				$sidfield	=	get_config('block_ilp','mis_misc_timetable_studentid');
				
				$wkfield	=	get_config('block_ilp','mis_misc_timetable_week');
 			
 				$keyfields	=	array($sidfield	=> array('=' => $mis_user_id),$wkfield => array('=' => $this->timetableweek));
 				
 				$this->fields		=	array();
 				
 				if 	(get_config('block_ilp','mis_misc_timetable_registerid')) 	$this->fields['registerid']	=	get_config('block_ilp','mis_misc_timetable_registerid');
 				if 	(get_config('block_ilp','mis_misc_timetable_week')) 		$this->fields['week']	=	get_config('block_ilp','mis_misc_timetable_week');
 				if 	(get_config('block_ilp','mis_misc_timetable_tutor')) 		$this->fields['tutor']	=	get_config('block_ilp','mis_misc_timetable_tutor');

 				if 	(get_config('block_ilp','mis_misc_timetable_register')) 	$this->fields['register']	=	get_config('block_ilp','mis_misc_timetable_register');
 				if 	(get_config('block_ilp','mis_misc_timetable_date')) 		$this->fields['date']	=	get_config('block_ilp','mis_misc_timetable_date');
 				if 	(get_config('block_ilp','mis_misc_timetable_room')) 		$this->fields['room']	=	get_config('block_ilp','mis_misc_timetable_room');
 				if 	(get_config('block_ilp','mis_misc_timetable_starttime')) 	$this->fields['starttime']	=	get_config('block_ilp','mis_misc_timetable_starttime');
 				if 	(get_config('block_ilp','mis_misc_timetable_endtime')) 		$this->fields['endtime']	=	get_config('block_ilp','mis_misc_timetable_endtime');

                $addionalargs = array();
                $addionalargs['sort'] = $this->fields['date'];
                $prelimdbcalls   =    get_config('block_ilp','mis_misc_timetable_prelimcalls');

 				$this->data	=	$this->dbquery( $table, $keyfields, $this->fields, $addionalargs, $prelimdbcalls);
 			} 
    }
 	
 	
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_misc_timetable&plugintype=mis">'.get_string('ilp_mis_misc_timetable_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_misc_timetable', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_table',get_string('ilp_mis_misc_timetable_table', 'block_ilp'),get_string('ilp_mis_misc_timetable_tabledesc', 'block_ilp'),'');

          $this->config_text_element($mform,'mis_misc_timetable_prelimcalls',get_string('ilp_mis_misc_timetable_prelimcalls', 'block_ilp'),get_string('ilp_mis_misc_timetable_prelimcallsdesc', 'block_ilp'),'');


          $this->config_text_element($mform,'mis_misc_timetable_studentid',get_string('ilp_mis_misc_timetable_studentid', 'block_ilp'),get_string('ilp_mis_misc_timetable_studentiddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_registerid',get_string('ilp_mis_misc_timetable_registerid', 'block_ilp'),get_string('ilp_mis_misc_timetable_registeriddesc', 'block_ilp'),'registerID');

 	 	$this->config_text_element($mform,'mis_misc_timetable_week',get_string('ilp_mis_misc_timetable_week', 'block_ilp'),get_string('ilp_mis_misc_timetable_weekdesc', 'block_ilp'),'week');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_register',get_string('ilp_mis_misc_timetable_register', 'block_ilp'),get_string('ilp_mis_misc_timetable_registerdesc', 'block_ilp'),'registerName');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_date',get_string('ilp_mis_misc_timetable_date', 'block_ilp'),get_string('ilp_mis_misc_timetable_datedesc', 'block_ilp'),'dateTime');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_room',get_string('ilp_mis_misc_timetable_room', 'block_ilp'),get_string('ilp_mis_misc_timetable_roomdesc', 'block_ilp'),'room');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_starttime',get_string('ilp_mis_misc_timetable_starttime', 'block_ilp'),get_string('ilp_mis_misc_timetable_starttimedesc', 'block_ilp'),'starttime');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_endtime',get_string('ilp_mis_misc_timetable_endtime', 'block_ilp'),get_string('ilp_mis_misc_timetable_endtimedesc', 'block_ilp'),'endtime');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_tutor',get_string('ilp_mis_misc_timetable_tutor', 'block_ilp'),get_string('ilp_mis_misc_timetable_tutordesc', 'block_ilp'),'tutor');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_termstart',get_string('ilp_mis_misc_timetable_termstart', 'block_ilp'),get_string('ilp_mis_misc_timetable_termstartdesc', 'block_ilp'));
 	 	
 	 	$this->config_text_element($mform,'mis_misc_timetable_termend',get_string('ilp_mis_misc_timetable_termend', 'block_ilp'),get_string('ilp_mis_misc_timetable_termenddesc', 'block_ilp'));

 	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_misc_timetable_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	 	 	
 	 	
 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_misc_timetable_tabletype',$options,get_string('ilp_mis_misc_timetable_tabletype', 'block_ilp'),get_string('ilp_mis_misc_timetable_tabletypedesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_misc_timetable_pluginstatus',$options,get_string('ilp_mis_misc_timetable_pluginstatus', 'block_ilp'),get_string('ilp_mis_misc_timetable_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }
 	 
 	 
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {

        $string['ilp_mis_misc_timetable_pluginname']					= 'Lesson Timetable';
        
        $string['ilp_mis_misc_timetable_pluginnamesettings']			= 'Lesson Timetable Configuration';
        
        $string['ilp_mis_misc_timetable_table']							= 'MIS table';
        $string['ilp_mis_misc_timetable_tabledesc']						= 'The table in the MIS where the data for this plugin will be retrieved from';
        
        $string['ilp_mis_misc_timetable_studentid']						= 'Student ID field';
        $string['ilp_mis_misc_timetable_studentiddesc']					= 'The field that will be used to find the student';
        
        $string['ilp_mis_misc_timetable_register']						= 'Lesson name data field';
        $string['ilp_mis_misc_timetable_registerdesc']					= 'The field that holds lesson name data';
        
        $string['ilp_mis_misc_timetable_registerid']					= 'Register Id data field';
        $string['ilp_mis_misc_timetable_registeriddesc']				= 'The field that holds the unique identifier for the lesson data';
        
        $string['ilp_mis_misc_timetable_week']							= 'Week data field';
        $string['ilp_mis_misc_timetable_weekdesc']				= 'The field that holds the academic week of the lesson';
        
        $string['ilp_mis_misc_timetable_date']							= 'Lesson date data field';
        $string['ilp_mis_misc_timetable_datedesc']						= 'The field that holds lesson date data';
        
        $string['ilp_mis_misc_timetable_room']							= 'Lesson room data field';
        $string['ilp_mis_misc_timetable_roomdesc']						= 'The field that holds lesson room data';
        
		$string['ilp_mis_misc_timetable_starttime']						= 'lessom start time field';
        $string['ilp_mis_misc_timetable_starttimedesc']					= 'The field that holds lesson start time data';
        
        $string['ilp_mis_misc_timetable_endtime']						= 'Lesson end time field';
        $string['ilp_mis_misc_timetable_endtimedesc']					= 'The field that holds lesson end data';
        
        $string['ilp_mis_misc_timetable_tutor']							= 'Tutor name field';
        $string['ilp_mis_misc_timetable_tutordesc']						= 'The field that holds tutor name data';
        
        $string['ilp_mis_misc_timetable_termstart']						= 'Term start date';
        $string['ilp_mis_misc_timetable_termstartdesc']					= 'Enter the term start date in format dd-mm-yyyy e.g 11-09-2012. This will be week 1';
        
        $string['ilp_mis_misc_timetable_termend']						= 'Term end date';
        $string['ilp_mis_misc_timetable_termenddesc']					= 'Enter the term end date in format dd-mm-yyyy e.g 21-07-2012';
                
        $string['ilp_mis_misc_timetable_tabletype']						= 'Table type';
        $string['ilp_mis_misc_timetable_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';        
        $string['ilp_mis_misc_timetable_pluginstatus']					= 'Status';
        $string['ilp_mis_misc_timetable_pluginstatusdesc']				= 'Is the block enabled or disabled';

        $string['ilp_mis_misc_timetable_day_disp']						= 'Day';
        $string['ilp_mis_misc_timetable_date_disp']						= 'Date';
        $string['ilp_mis_misc_timetable_exam_disp']						= 'Exam';
        $string['ilp_mis_misc_timetable_room_disp']						= 'Room';
        $string['ilp_mis_misc_timetable_starttime_disp']				= 'Start';
        $string['ilp_mis_misc_timetable_endtime_disp']					= 'End';
        $string['ilp_mis_misc_timetable_tutor_disp']					= 'Tutor';
        $string['ilp_mis_misc_timetable_register_disp']					= 'Register';
        
        
        
        $string['ilp_mis_misc_timetable_timetable_disp']				= 'Timetable';
        $string['ilp_mis_misc_timetable_week_disp']						= 'Week';
        $string['ilp_mis_misc_timetable_next_disp']						= 'Next';
        $string['ilp_mis_misc_timetable_current_disp']					= 'Current';
        $string['ilp_mis_misc_timetable_previous_disp']					= 'Previous';

         $string['ilp_mis_misc_timetable_prelimcalls']						= 'Preliminary db calls';
         $string['ilp_mis_misc_timetable_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';
         $string['ilp_mis_misc_timetable_tab_name']					= 'Lesson Timetable';


         return $string;
    }

    
    static function plugin_type()	{
    	return 'learnerprofile';
    }
 	
    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors 
     * 
     */
    function tab_name() {
        return get_string('ilp_mis_misc_timetable_tab_name','block_ilp');
    }



}

?>