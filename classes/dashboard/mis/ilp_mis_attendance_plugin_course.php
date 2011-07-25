<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');


class ilp_mis_attendance_plugin_course extends ilp_mis_attendance_plugin	{

	public 	$fields;
	public	$mcbdata;
	public	$courselist;
	
	
    protected $monthlist = array();

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        
        $this->mcbdata		=	false;
        $this->courselist	= 	false;
       
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
       
    	if (!empty($this->courselist) && !empty($this->mcbdata)) {
    		
    		//set up the flexible table for displaying

	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'monthly_breakdown',true ,'ilp_mis_attendance_overview_plugin_mcb');
	
	        //setup the headers and columns with the fields that have been requested 

	        $headers		=	array();
	        $columns		=	array();

			$headers[]		=	get_string('ilp_mis_attendance_plugin_course_course','block_ilp');
			$headers[]		=	get_string('ilp_mis_attendance_plugin_course_attendance','block_ilp');
			$headers[]		=	get_string('ilp_mis_attendance_plugin_course_punchuality','block_ilp');
			$headers[]		=	get_string('ilp_mis_attendance_plugin_course_grade','block_ilp');
			$headers[]		=	get_string('ilp_mis_attendance_plugin_course_performance','block_ilp');
	        
	        $columns[]		=	'course';
	        $columns[]		=	'attendance';
			$columns[]		=	'punchuality';
	        $columns[]		=	'grade';
	        $columns[]		=	'performance';
	        
	        
	        //define the columns in the tables
	        $flextable->define_columns($columns);
	        
	        //define the headers in the tables
	        $flextable->define_headers($headers);
	        
	        //we do not need the intialbars
	        $flextable->initialbars(false);
	        
	        $flextable->set_attribute('class', 'flexible generaltable');
	        
	        //setup the flextable
	        $flextable->setup();
	        
	        foreach( $this->courselist as $cid => $cname )	{
				//we start the month counter from the first month
	        	$month				=	$startmonth;
	        	$data['course']		=	$cname;
	        	$data['attendance']	=	$this->mcbdata[$cid]['attendance'];
	        	$data['punchuality'] =	$this->mcbdata[$cid]['punchuality'];
	        	$data['grade']		 =	$this->mcbdata[$cid]['grade'];
	        	$data['performance'] =	$this->mcbdata[$cid]['performance'];
	        	$flextable->add_data_keyed( $data );
	        }
			ob_start();
	        $flextable->print_html();
			$pluginoutput = ob_get_contents();
	        ob_end_clean();
	        
	        return $pluginoutput;
	        
    		
    		
    		
    	} 
    	
    	
 
    	
    	
    }

    
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_overview_plugin_mcb&plugintype=mis">'.get_string('ilp_mis_attendance_overview_plugin_mcb_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_misc_timetable', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_table',get_string('ilp_mis_attendance_plugin_course_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_tabledesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_studentidfield',get_string('ilp_mis_attendance_plugin_course_studentidfield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_studentidfielddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_courseidfield',get_string('ilp_mis_attendance_plugin_course_course_idfield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_course_idfielddesc', 'block_ilp'),'courseID');

 	 	$this->config_text_element($mform,'mis_plugin_mcb_coursenamefield',get_string('ilp_mis_attendance_plugin_course_course_namefield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_course_namefielddesc', 'block_ilp'),'courseName');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_markstotalfield',get_string('ilp_mis_attendance_plugin_course_markstotal', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_markstotaldesc', 'block_ilp'),'marksTotal');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_markspresentfield',get_string('ilp_mis_attendance_plugin_course_markspresent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_markspresentdesc', 'block_ilp'),'marksPresent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_marksabsentfield',get_string('ilp_mis_attendance_plugin_course_marksabsent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_marksabsentdesc', 'block_ilp'),'marksAbsent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_marksauthabsentfield',get_string('ilp_mis_attendance_plugin_course_marksauthabsent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_marksauthabsentdesc', 'block_ilp'),'marksAuthAbsent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_markslatefield',get_string('ilp_mis_attendance_plugin_course_markslate', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_markslatedesc', 'block_ilp'),'marksLate');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_grade',get_string('ilp_mis_attendance_plugin_course_grade', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_gradedesc', 'block_ilp'),'Grade');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_performance',get_string('ilp_mis_attendance_plugin_course_performance', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_performancedesc', 'block_ilp'),'perforamnce');

 	 	
 	 	
 	 	
    	$options = array(
    		 0 => get_string('ilp_mis_attendance_plugin_course_ignore','block_ilp'),
    		 1 => get_string('ilp_mis_attendance_plugin_course_positive','block_ilp'),
    		 2 => get_string('ilp_mis_attendance_plugin_course_negative','block_ilp'), 
    	);
    	
 	 	$this->config_select_element($mform,'mis_plugin_mcb_authorised',$options,get_string('ilp_mis_attendance_plugin_course_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_tabledesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_plugin_mcb_tabletype',$options,get_string('ilp_mis_attendance_plugin_course_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_tabledesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_attendance_plugin_course_pluginstatus',$options,get_string('ilp_mis_attendance_plugin_course_pluginstatus', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }
    

        
    public function plugin_type(){
        return 'overview';
    }
    
	function language_strings(&$string) {
        $string['ilp_mis_attendance_plugin_course_pluginname']		  						= 'Monthly Course Breakdown Overview';
        $string['ilp_mis_attendance_overview_plugin_mcb_pluginnamesettings']		  	= 'Monthly Course Breakdown Configuration';
        
        
        $string['ilp_mis_attendance_plugin_course_table']		  		= 'Month-course table';
        $string['ilp_mis_attendance_plugin_course_tabledesc']		  		= 'table containing overview of student attendence by course by month';
        
        $string[ 'ilp_mis_attendance_plugin_course_studentidfield']   		= 'Student id field';
        $string[ 'ilp_mis_attendance_plugin_course_studentidfielddesc']  	= 'The field containing the mis user id';
        
        $string[ 'ilp_mis_attendance_plugin_course_course_idfield']   		= 'Course id field';
        $string[ 'ilp_mis_attendance_plugin_course_course_idfielddesc']   	= 'The field containing course id data';
        
        $string[ 'ilp_mis_attendance_plugin_course_course_namefield']  	= 'Course title field';
        $string[ 'ilp_mis_attendance_plugin_course_course_namefielddesc']  = 'The field containing course name data';
        
        $string[ 'ilp_mis_attendance_plugin_course_gradeidfield' ]   		= 'Grade field';
        $string[ 'ilp_mis_attendance_plugin_course_gradeidfielddesc' ]   	= 'The field containing the grade data';
        
        $string[ 'ilp_mis_attendance_plugin_course_performance' ]   		= 'Performance field';
        $string[ 'ilp_mis_attendance_plugin_course_performancedesc' ]   	= 'The field containing the performance data';        

        
        $string[ 'ilp_mis_attendance_plugin_course_markstotal' ]   		= 'Marks total field';
        $string[ 'ilp_mis_attendance_plugin_course_markstotaldesc' ]   	= 'The field containing marks total data';
        
        
        $string[ 'ilp_mis_attendance_plugin_course_markspresent' ]   		= 'marks present field';
        $string[ 'ilp_mis_attendance_plugin_course_markspresentdesc' ]   	= 'The field containing the marks present data';
        
        $string[ 'ilp_mis_attendance_plugin_course_marksabsent' ]   		= 'marks absent field';
        $string[ 'ilp_mis_attendance_plugin_course_marksabsentdesc' ]   	= 'The field containing the absents data';
        
        $string[ 'ilp_mis_attendance_plugin_course_marksauthabsent' ] 	 	= 'marks authabsent field';
        $string[ 'ilp_mis_attendance_plugin_course_marksauthabsentdesc' ]  = 'the field containing the authorised absents data';
        
        $string[ 'ilp_mis_attendance_plugin_course_markslate' ]   			= 'marks late field';
        $string[ 'ilp_mis_attendance_plugin_course_markslatedesc' ]   		= 'the field containing the marks late data';
        
        $string[ 'ilp_mis_attendance_plugin_course_authorised' ]   			= 'Authorised Absents';
        $string[ 'ilp_mis_attendance_plugin_course_authoriseddesc' ]   		= 'What should be done with authorised absents? Positive - to add to present marks, Negative - to add to absents and ignore to not count';
        
        $string[ 'ilp_mis_attendance_plugin_course_endmonth' ]   			= 'End month';
        $string[ 'ilp_mis_attendance_plugin_course_endmonthdesc' ]   		= 'The last month to be displayed on the monthly course breakdown table';
        
        $string[ 'ilp_mis_attendance_plugin_course_startmonth' ]   		= 'Start Month';
        $string[ 'ilp_mis_attendance_plugin_course_startmonthdesc' ]   	= 'The first month to be displayed on the monthly course breakdown table';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_ignore' ]  				= 'Ignore';
        $string[ 'ilp_mis_attendance_plugin_mcb_positive' ]   			= 'Positive';
        $string[ 'ilp_mis_attendance_plugin_mcb_negative' ]   			= 'Negative';
        
        $string[ 'ilp_mis_attendance_plugin_course_pluginstatus' ]   			= 'Status';
        $string[ 'ilp_mis_attendance_plugin_course_pluginstatusdesc' ]   		= 'is the plugin enabled or disabled';
        
        $string[ 'ilp_mis_attendance_plugin_course_course' ] 		  	= 'Course';
        $string[ 'ilp_mis_attendance_plugin_course_attendance' ]   		= 'Attendance';
        $string[ 'ilp_mis_attendance_plugin_course_punchuality' ]   	= 'Punchuality';
        $string[ 'ilp_mis_attendance_plugin_course_attendance' ]   		= 'Grade';
        $string[ 'ilp_mis_attendance_plugin_course_performance' ]   		= 'Performance';
        
    }

    
    /**
     * Retrieves user data from the mis database
     * 
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data( $mis_user_id ) {
    	$table 		=		get_config( 'block_ilp', 'mis_plugin_mcb_table'  );
    	
    	$this->mis_user_id	=	$mis_user_id;

    	
    	if (!empty($table)) {
    		
    		$sidfield	=	get_config('block_ilp','mis_plugin_mcb_studentidfield');
    		
    		//create the key that will be used in sql query
    		$keyfields	=	array($sidfield	=> array('=' => $mis_user_id));
    		
    		$this->fields		=	array();
    		
    		//get all of the fields that will be returned
    		if 	(get_config('block_ilp','mis_plugin_mcb_courseidfield')) 	$this->fields['courseid']		=	get_config('block_ilp','mis_plugin_mcb_courseidfield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_coursenamefield')) 	$this->fields['coursename']		=	get_config('block_ilp','mis_plugin_mcb_coursenamefield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_gradeidfield')) 	$this->fields['grade']			=	get_config('block_ilp','mis_plugin_mcb_gradeidfield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_performance')) 		$this->fields['performance']	=	get_config('block_ilp','mis_plugin_mcb_performance');
     		
    		if 	(get_config('block_ilp','mis_plugin_mcb_markstotalfield')) 	$this->fields['markstotal']			=	get_config('block_ilp','mis_plugin_mcb_markstotalfield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_markspresentfield')) 	$this->fields['markspresent']	=	get_config('block_ilp','mis_plugin_mcb_markspresentfield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_marksabsentfield')) 	$this->fields['marksabsent']	=	get_config('block_ilp','mis_plugin_mcb_marksabsentfield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_marksauthabsentfield')) 	$this->fields['marksauthabsent']	=	get_config('block_ilp','mis_plugin_mcb_marksauthabsentfield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_markslatefield')) 	$this->fields['markslate']	=	get_config('block_ilp','mis_plugin_mcb_markslatefield');
    		
    		//get the users monthly attendance data
    		$this->data	=	$this->dbquery( $table, $keyfields, $this->fields);
    		
    		$this->normalise_data($this->data);
    	}
    }
    
    function normalise_data($data)	{
    	
    	$mcbdata		=	array();
    	$courselist		=	array();
    	
    	foreach ($data as $d) {
    		
    		//get the id of the current course
    		$courseid	=	$d[$this->fields['courseid']];
    		
    		
    		//check if an array position for the course exists 
    		if (!isset($mcbdata[$courseid])) {
    			$mcbdata[$courseid]	=	array();
    		}
    		
    		//check if an array position for the month exists in the course 
    		if (!isset($mcbdata[$courseid][$month])) {
    			$mcbdata[$courseid][$month]	=	array();
    		}
    		
    		//should authabsent not be counted as absent? and does this vary from site to site in which case a config option is needed
			$present	=	$this->presents_cal($presents,$authabsents);
    		
    		//calculate the months attendance percentage 
    		$monthpercent 	=	($present / $d[$this->fields['markstotal']]) * 100;
    		
    		$latepercent	=	($d[$this->fields['markslate']]/$present) * 100;
    		
    		//fill the couse month array position with percentage for the month
    		$mcbdata[$courseid]	=	array(
    											  'attendance'		=>  $monthpercent,
    											  'latepercent'		=>	$latepercent,
    											  'grade'			=>	$d[$this->fields['grade']],
    											  'performance'		=>	$d[$this->fields['performance']],	
    											  'markstotal'		=>	$d[$this->fields['markstotal']],
    											  'markspresent'	=>	$d[$this->fields['markspresent']],
    											  'marksabsent'		=>	$d[$this->fields['marksabsent']],
    											  'marksauthabsent'	=>	$d[$this->fields['marksauthabsent']],
    											  'markslate'		=>	$d[$this->fields['markslate']]);
    		
    		//check if the course has been added to the courselist array
    		if (!isset($courselist[$courseid])) {
    			$courselist[$courseid]	=	$d[$this->fields['coursename']];
    		}
    	}
    	
    	$this->mcbdata		=	$mcbdata;

    	asort($courselist);
    	
    	$this->courselist	=	$courselist;

    } 
    
    
    private function presents_cal($markspresent,$authabesent) {
  		
 	 		switch (get_config('block_ilp','mis_plugin_mcb_authorised')) {
    			
    			case 1 : 
    				//positive
					$present	=    $markspresent + $authabesent;				
    				break;
    				
    			case 2:
    				$present	=    $markspresent - $authabesent;
    				break;
    				
    			default:
    				$present	=	$markspresent;
    		}
    		
    	return $present;
  	} 
    
    
    
    
}





