<?php 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');



class ilp_mis_misc_exam_timetable extends ilp_mis_plugin	{

	protected 	$fields;
	protected 	$mis_user_id;
	
	/**
	 * 
	 * Constructor for the class
	 * @param array $params should hold any vars that are needed by plugin. can also hold the 
	 * 						the connection string vars if they are different from those specified 
	 * 						in the mis connection
	 */
	
 	function	__construct($params=array())	{
 		parent::__construct($params);
 		
 		$this->tabletype	=	get_config('block_ilp','mis_misc_exam_timetable_tabletype');
 		$this->fields		=	array();
 	}
 	
 	/**
 	 * 
 	 * @see ilp_mis_plugin::display()
 	 */
 	function display()	{
 		global $CFG;
        
        // set up the flexible table for displaying the data
 		
 		if (!empty($this->data)) {
     		// set up the flexible table

	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'exam_timetable',true ,'ilp_mis_misc_exam_timetable');
	
	        //create headers
	        
	        //setup the headers and columns with the fields that have been requested 

	        $headers		=	array();
	        $columns		=	array();
	        
	        
	        $headers[]		=	get_string('ilp_mis_misc_exam_timetable_day_disp','block_ilp');
	        $headers[]		=	get_string('ilp_mis_misc_exam_timetable_date_disp','block_ilp');
	        $headers[]		=	get_string('ilp_mis_misc_exam_timetable_exam_disp','block_ilp');
	        $headers[]		=	get_string('ilp_mis_misc_exam_timetable_room_disp','block_ilp');
	        $headers[]		=	get_string('ilp_mis_misc_exam_timetable_starttime_disp','block_ilp');
	        $headers[]		=	get_string('ilp_mis_misc_exam_timetable_endtime_disp','block_ilp');
	        
	        
	        $columns[]		=	'day';
	        $columns[]		=	'date';
	        $columns[]		=	'exam';
	        $columns[]		=	'room';
	        $columns[]		=	'starttime';
	        $columns[]		=	'endtime';
	        
	        

	        //define the columns in the tables
	        $flextable->define_columns($columns);
	        
	        //define the headers in the tables
	        $flextable->define_headers($headers);
	        
	        //we do not need the intialbars
	        $flextable->initialbars(false);
	        
	        $flextable->set_attribute('class', 'flexible generaltable');
	        
	        //setup the flextable
	        $flextable->setup();
	        
	        $i	=	0;
	        $total	=	0;
	        
	        //add the row to table
	        foreach( $this->data as $row ){
	        	
	        	$date			=	$row[get_config('block_ilp','mis_misc_exam_timetable_date')];
	        	$datetimestamp	=	strtotime($date);

	        	
	        	$data['day']	=	date('D',$datetimestamp);
	        	$data['date']	=	date('d/m',$datetimestamp);
	        	
	        	$data['exam']	=	$row[get_config('block_ilp','mis_misc_exam_timetable_exam')];
	        	
	        	$data['room']	=	$row[get_config('block_ilp','mis_misc_exam_timetable_room')];
	        	
	        	$start			=	strtotime($row[get_config('block_ilp','mis_misc_exam_timetable_starttime')]);
	        	$data['starttime']	=	date('G:i',$start);
	        	
	        	$end			=	strtotime($row[get_config('block_ilp','mis_misc_exam_timetable_endtime')]);
	        	$data['endtime']	=	date('G:i',$end);
	        	
	            $flextable->add_data_keyed( $data );
	        }
	        
	        //calculate the average of the students qualification points
			$average	=	(!empty($total)) ? $total	/$i : 0;
	        
	        //buffer out as flextable sends its data straight to the screen we dont want this  
			ob_start();
			
			//call the html file for the plugin which has the flextable print statement
 
			$flextable->print_html();
			
			$pluginoutput = ob_get_contents();
			
	        ob_end_clean();
 			
 			return $pluginoutput;
 			
 			
 		} else {
            if( $msg = get_string('nodataornoconfig', 'block_ilp') ){
                echo '<div id="plugin_nodata">' . $msg . '</div>';
            }
    	}
 	} 
 	
 	/**
 	 * Retrieves data from the mis 
 	 * 
 	 * @param	$mis_user_id	the id of the user in the mis used to retrieve the data of the user
 	 * @param	$user_id		the id of the user in moodle
 	 *
 	 * @return	null
 	 */
 	
 	
    public function set_data( $mis_user_id,$user_id=null ){
    		
    		$this->mis_user_id	=	$mis_user_id;
    		
    		$table	=	get_config('block_ilp','mis_misc_exam_timetable_table');
    		
			if (!empty($table)) {
				
 				$sidfield	=	get_config('block_ilp','mis_misc_exam_timetable_studentid');

	    		//is the id a string or a int
    			$idtype	=	get_config('block_ilp','mis_misc_exam_timetable_idtype');
    			$mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id; 				
 			
 				$keyfields	=	array($sidfield	=> array('=' => $mis_user_id));
 				
 				$this->fields		=	array();
 				
 				if 	(get_config('block_ilp','mis_misc_exam_timetable_exam')) 		$this->fields['exam']	=	get_config('block_ilp','mis_misc_exam_timetable_exam');
 				if 	(get_config('block_ilp','mis_misc_exam_timetable_date')) 		$this->fields['date']	=	get_config('block_ilp','mis_misc_exam_timetable_date');
 				if 	(get_config('block_ilp','mis_misc_exam_timetable_room')) 		$this->fields['room']	=	get_config('block_ilp','mis_misc_exam_timetable_room');
 				if 	(get_config('block_ilp','mis_misc_exam_timetable_starttime')) 	$this->fields['starttime']	=	get_config('block_ilp','mis_misc_exam_timetable_starttime');
 				if 	(get_config('block_ilp','mis_misc_exam_timetable_endtime')) 	$this->fields['endtime']	=	get_config('block_ilp','mis_misc_exam_timetable_endtime');

                $prelimdbcalls   =    get_config('block_ilp','mis_misc_exam_timetable_prelimcalls');

 				$this->data	=	$this->dbquery( $table, $keyfields, $this->fields,null, $prelimdbcalls);
 				
 			} 
    }
 	
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_misc_exam_timetable&plugintype=mis">'.get_string('ilp_mis_misc_exam_timetable_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_misc_exam_timetable', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_misc_exam_timetable_table',get_string('ilp_mis_misc_exam_timetable_table', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_tabledesc', 'block_ilp'),'');

        $this->config_text_element($mform,'mis_misc_exam_timetable_prelimcalls',get_string('ilp_mis_misc_exam_timetable_prelimcalls', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_prelimcallsdesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_exam_timetable_studentid',get_string('ilp_mis_misc_exam_timetable_studentid', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_studentiddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_exam_timetable_exam',get_string('ilp_mis_misc_exam_timetable_exam', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_examdesc', 'block_ilp'),'examName');

 	 	$this->config_text_element($mform,'mis_misc_exam_timetable_date',get_string('ilp_mis_misc_exam_timetable_date', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_datedesc', 'block_ilp'),'dateTime');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_exam_timetable_room',get_string('ilp_mis_misc_exam_timetable_room', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_roomdesc', 'block_ilp'),'room');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_exam_timetable_starttime',get_string('ilp_mis_misc_exam_timetable_starttime', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_starttimedesc', 'block_ilp'),'starttime');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_exam_timetable_endtime',get_string('ilp_mis_misc_exam_timetable_endtime', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_endtimedesc', 'block_ilp'),'endtime');

	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_misc_exam_timetable_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	
 
 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_misc_exam_timetable_tabletype',$options,get_string('ilp_mis_misc_exam_timetable_tabletype', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_tabletypedesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_misc_exam_timetable_pluginstatus',$options,get_string('ilp_mis_misc_exam_timetable_pluginstatus', 'block_ilp'),get_string('ilp_mis_misc_exam_timetable_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }

    
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {

        $string['ilp_mis_misc_exam_timetable_pluginname']						= 'Exam Timetable';
        
        $string['ilp_mis_misc_exam_timetable_pluginnamesettings']						= 'Exam Timetable Configuration';
        
        $string['ilp_mis_misc_exam_timetable_table']							= 'MIS table';
        $string['ilp_mis_misc_exam_timetable_tabledesc']						= 'The table in the MIS where the data for this plugin will be retrieved from';
        
        $string['ilp_mis_misc_exam_timetable_studentid']						= 'Student ID field';
        $string['ilp_mis_misc_exam_timetable_studentiddesc']					= 'The field that will be used to find the student';
        
        $string['ilp_mis_misc_exam_timetable_exam']								= 'Exam name data field';
        $string['ilp_mis_misc_exam_timetable_examdesc']							= 'The field that holds exam name data';
        
        $string['ilp_mis_misc_exam_timetable_date']								= 'Exam date data field';
        $string['ilp_mis_misc_exam_timetable_datedesc']							= 'The field that holds exam date data';
        
        $string['ilp_mis_misc_exam_timetable_room']								= 'Exam room data field';
        $string['ilp_mis_misc_exam_timetable_roomdesc']							= 'The field that holds exam room data';
        
		$string['ilp_mis_misc_exam_timetable_starttime']						= 'Exam start time field';
        $string['ilp_mis_misc_exam_timetable_starttimedesc']					= 'The field that holds exam time data';
        
        $string['ilp_mis_misc_exam_timetable_endtime']							= 'Exam end time field';
        $string['ilp_mis_misc_exam_timetable_endtimedesc']						= 'The field that holds exam end data';
                
        $string['ilp_mis_misc_exam_timetable_tabletype']						= 'Table type';
        $string['ilp_mis_misc_exam_timetable_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';        
        $string['ilp_mis_misc_exam_timetable_pluginstatus']			= 'Status';
        $string['ilp_mis_misc_exam_timetable_pluginstatusdesc']		= 'Is the block enabled or disabled';

        $string['ilp_mis_misc_exam_timetable_day_disp']							= 'Day';
        $string['ilp_mis_misc_exam_timetable_date_disp']						= 'Date';
        $string['ilp_mis_misc_exam_timetable_exam_disp']						= 'Exam';
        $string['ilp_mis_misc_exam_timetable_room_disp']						= 'Room';
        $string['ilp_mis_misc_exam_timetable_starttime_disp']						= 'Start';
        $string['ilp_mis_misc_exam_timetable_endtime_disp']							= 'End';

         $string['ilp_mis_misc_exam_timetable_prelimcalls']						= 'Preliminary db calls';
         $string['ilp_mis_misc_exam_timetable_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';
         $string['ilp_mis_misc_exam_timetable_tab_name']					= 'Exam Timetable';

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
        return get_string('ilp_mis_misc_exam_timetable_tab_name','block_ilp');
    }


}

?>
