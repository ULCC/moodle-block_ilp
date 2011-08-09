<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');

class ilp_mis_attendance_plugin_mcb extends ilp_mis_attendance_plugin{

	public 	$fields;
	public	$mcbdata;
	public	$courselist;
	
	
    protected $monthlist = array();

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        
        $this->mcbdata		=	false;
        $this->courselist	= 	false;
        $this->tabletype	=	get_config('block_ilp','mis_plugin_mcb_tabletype');
       
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
       
    	if (!empty($this->courselist) && !empty($this->mcbdata)) {
    		
    		//set up the flexible table for displaying

	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'monthly_breakdown',true ,'ilp_mis_attendance_plugin_mcb');
	
	        //setup the headers and columns with the fields that have been requested 

	        $headers		=	array();
	        $columns		=	array();

			$headers[]		=	get_string('ilp_mis_attendance_plugin_mcb_course','block_ilp');
			$headers[]		=	get_string('ilp_mis_attendance_plugin_mcb_attendance','block_ilp');
	        
	        $columns[]		=	'course';
	        $columns[]		=	'overall';
	        
	        $startmonth		=	get_config('block_ilp','mis_plugin_mcb_startmonth');
	        $endmonth		=	get_config('block_ilp','mis_plugin_mcb_endmonth');	

	        //we start the month counter from the first month
	        $month			=	$startmonth;
	        
	        do {
	        	//get a string representation of the month 
	        	$monthstr		=	strtolower(date('M',strtotime("1-$month-2011")));
	        	
	        	//pass the lang string for the month
	        	$headers[]		=	get_string($monthstr,'block_ilp');
	        	
	        	//cast the month to a int 
	        	$columns[]		=	 "{$month}month";
	        	
	        	$month++;
	        	if ($month >= 13) 	$month	=	1; 
	        } while($month != $endmonth+1);
	        
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
	        	$data['overall']	=	$this->mcbdata[$cid]['overallpercent'].'%';
	        		        	
	       		do {
					$data["{$month}month"]	=	(!empty($this->mcbdata[$cid][$month])) ? $this->mcbdata[$cid][$month]['percent']."%" : "0%";
	       				
		        	$month++;
		        	if ($month >= 13) 	$month	=	1; 
	        	} while($month != $endmonth+1);
	        	
	        	$flextable->add_data_keyed( $data );
	        }
			ob_start();
	        $flextable->print_html();
			$pluginoutput = ob_get_contents();
	        ob_end_clean();
	        
	        return $pluginoutput;
	        
    		
    		
    		
    	} else {
    		echo '<div id="plugin_nodata">'.get_string('nodataornoconfig','block_ilp').'</div>';
    	}
    	
    	
 
    	
    	
    }

    
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_mcb&plugintype=mis">'.get_string('ilp_mis_attendance_plugin_mcb_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('mis_plugin_mcb', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_table',get_string('ilp_mis_attendance_plugin_mcb_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_tabledesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_studentidfield',get_string('ilp_mis_attendance_plugin_mcb_studentidfield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_studentidfielddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_courseidfield',get_string('ilp_mis_attendance_plugin_mcb_course_idfield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_course_idfielddesc', 'block_ilp'),'courseID');

 	 	$this->config_text_element($mform,'mis_plugin_mcb_coursenamefield',get_string('ilp_mis_attendance_plugin_mcb_course_namefield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_course_namefielddesc', 'block_ilp'),'courseName');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_monthidfield',get_string('ilp_mis_attendance_plugin_mcb_monthidfield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_monthidfielddesc', 'block_ilp'),'month');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_monthorderfield',get_string('ilp_mis_attendance_plugin_mcb_monthorderfield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_monthorderfielddesc', 'block_ilp'),'monthOrder');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_markstotalfield',get_string('ilp_mis_attendance_plugin_mcb_markstotal', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_markstotaldesc', 'block_ilp'),'marksTotal');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_markspresentfield',get_string('ilp_mis_attendance_plugin_mcb_markspresent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_markspresentdesc', 'block_ilp'),'marksPresent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_marksabsentfield',get_string('ilp_mis_attendance_plugin_mcb_marksabsent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_marksabsentdesc', 'block_ilp'),'marksAbsent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_marksauthabsentfield',get_string('ilp_mis_attendance_plugin_mcb_marksauthabsent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_marksauthabsentdesc', 'block_ilp'),'marksAuthAbsent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_mcb_markslatefield',get_string('ilp_mis_attendance_plugin_mcb_markslate', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_markslatedesc', 'block_ilp'),'marksLate');
 	 	
 	 	
 	 	$options = array(
    		 1 => get_string('jan','block_ilp'),
    		 2 => get_string('feb','block_ilp'),
    		 3 => get_string('mar','block_ilp'),
    		 4 => get_string('apr','block_ilp'),
    		 5 => get_string('may','block_ilp'),
    		 6 => get_string('jun','block_ilp'),
    		 7 => get_string('jul','block_ilp'),
    		 8 => get_string('aug','block_ilp'),
    		 9 => get_string('sep','block_ilp'),
    		 10 => get_string('oct','block_ilp'),
    		 11 => get_string('nov','block_ilp'),
    		 12 => get_string('dec','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_plugin_mcb_startmonth',$options,get_string('ilp_mis_attendance_plugin_mcb_startmonth', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_startmonthdesc', 'block_ilp'),9);
 	 	
 	 	$options = array(
    		 1 => get_string('jan','block_ilp'),
    		 2 => get_string('feb','block_ilp'),
    		 3 => get_string('mar','block_ilp'),
    		 4 => get_string('apr','block_ilp'),
    		 5 => get_string('may','block_ilp'),
    		 6 => get_string('jun','block_ilp'),
    		 7 => get_string('jul','block_ilp'),
    		 8 => get_string('aug','block_ilp'),
    		 9 => get_string('sep','block_ilp'),
    		 10 => get_string('oct','block_ilp'),
    		 11 => get_string('nov','block_ilp'),
    		 12 => get_string('dec','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_plugin_mcb_endmonth',$options,get_string('ilp_mis_attendance_plugin_mcb_endmonth', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_endmonthdesc', 'block_ilp'),6);
	 	
    	$options = array(
    		 0 => get_string('ilp_mis_attendance_plugin_mcb_ignore','block_ilp'),
    		 1 => get_string('ilp_mis_attendance_plugin_mcb_positive','block_ilp'),
    		 2 => get_string('ilp_mis_attendance_plugin_mcb_negative','block_ilp'), 
    	);
    	
 	 	$this->config_select_element($mform,'mis_plugin_mcb_authorised',$options,get_string('ilp_mis_attendance_plugin_mcb_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_tabledesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_plugin_mcb_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	 	 	
 	 	
 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_plugin_mcb_tabletype',$options,get_string('ilp_mis_attendance_plugin_mcb_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_tabledesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_attendance_plugin_mcb_pluginstatus',$options,get_string('ilp_mis_attendance_plugin_mcb_pluginstatus', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }
    

        
    public function plugin_type(){
        return 'overview';
    }
    
	function language_strings(&$string) {
        $string['ilp_mis_attendance_plugin_mcb_pluginname']		  						= 'Monthly Course Breakdown Overview';
        $string['ilp_mis_attendance_plugin_mcb_pluginnamesettings']		  	= 'Monthly Course Breakdown Configuration';
        
        
        $string['ilp_mis_attendance_plugin_mcb_table']		  		= 'Month-course table';
        $string['ilp_mis_attendance_plugin_mcb_tabledesc']		  		= 'table containing overview of student attendence by course by month';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_studentidfield']   		= 'Student id field';
        $string[ 'ilp_mis_attendance_plugin_mcb_studentidfielddesc']  	= 'The field containing the mis user id';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_course_idfield']   		= 'Course id field';
        $string[ 'ilp_mis_attendance_plugin_mcb_course_idfielddesc']   	= 'The field containing course id data';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_course_namefield']  	= 'Course title field';
        $string[ 'ilp_mis_attendance_plugin_mcb_course_namefielddesc']  = 'The field containing course name data';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_monthidfield' ]   		= 'Month field';
        $string[ 'ilp_mis_attendance_plugin_mcb_monthidfielddesc' ]   	= 'The field containing the month';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_monthorderfield' ]  	= 'Month order field';
        $string[ 'ilp_mis_attendance_plugin_mcb_monthorderfielddesc' ]  = 'The field containing the month order';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_markstotal' ]   		= 'Marks total field';
        $string[ 'ilp_mis_attendance_plugin_mcb_markstotaldesc' ]   	= 'The field containing marks total data';
        
        
        $string[ 'ilp_mis_attendance_plugin_mcb_markspresent' ]   		= 'marks present field';
        $string[ 'ilp_mis_attendance_plugin_mcb_markspresentdesc' ]   	= 'The field containing the marks present data';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_marksabsent' ]   		= 'marks absent field';
        $string[ 'ilp_mis_attendance_plugin_mcb_marksabsentdesc' ]   	= 'The field containing the absents data';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_marksauthabsent' ] 	 	= 'marks authabsent field';
        $string[ 'ilp_mis_attendance_plugin_mcb_marksauthabsentdesc' ]  = 'the field containing the authorised absents data';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_markslate' ]   			= 'marks late field';
        $string[ 'ilp_mis_attendance_plugin_mcb_markslatedesc' ]   		= 'the field containing the marks late data';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_authorised' ]   			= 'Authorised Absents';
        $string[ 'ilp_mis_attendance_plugin_mcb_authoriseddesc' ]   		= 'What should be done with authorised absents? Positive - to add to present marks, Negative - to add to absents and ignore to not count';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_ignore' ]  				= 'Ignore';
        $string[ 'ilp_mis_attendance_plugin_mcb_positive' ]   			= 'Positive';
        $string[ 'ilp_mis_attendance_plugin_mcb_negative' ]   			= 'Negative';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_endmonth' ]   			= 'End month';
        $string[ 'ilp_mis_attendance_plugin_mcb_endmonthdesc' ]   		= 'The last month to be displayed on the monthly course breakdown table';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_startmonth' ]   		= 'Start Month';
        $string[ 'ilp_mis_attendance_plugin_mcb_startmonthdesc' ]   	= 'The first month to be displayed on the monthly course breakdown table';
        
        
        $string[ 'ilp_mis_attendance_plugin_mcb_pluginstatus' ]   			= 'Status';
        $string[ 'ilp_mis_attendance_plugin_mcb_pluginstatusdesc' ]   		= 'is the plugin enabled or disabled';
        
        $string[ 'ilp_mis_attendance_plugin_mcb_course' ] 		  		= 'Course';
        $string[ 'ilp_mis_attendance_plugin_mcb_attendance' ]   		= 'Attendance';
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

    		//is the id a string or a int
    		$idtype	=	get_config('block_ilp','mis_plugin_mcb_idtype');
    		$mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;    		
    		
    		//create the key that will be used in sql query
    		$keyfields	=	array($sidfield	=> array('=' => $mis_user_id));
    		
    		$this->fields		=	array();
    		
    		//get all of the fields that will be returned
    		if 	(get_config('block_ilp','mis_plugin_mcb_courseidfield')) 	$this->fields['courseid']	=	get_config('block_ilp','mis_plugin_mcb_courseidfield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_coursenamefield')) 	$this->fields['coursename']	=	get_config('block_ilp','mis_plugin_mcb_coursenamefield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_monthidfield')) 	$this->fields['month']		=	get_config('block_ilp','mis_plugin_mcb_monthidfield');
    		if 	(get_config('block_ilp','mis_plugin_mcb_monthorderfield')) 	$this->fields['monthorder']	=	get_config('block_ilp','mis_plugin_mcb_monthorderfield');
    		
    		if 	(get_config('block_ilp','mis_plugin_mcb_markstotalfield')) 	$this->fields['markstotal']	=	get_config('block_ilp','mis_plugin_mcb_markstotalfield');
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
    		
    		//get the current month
    		$month	=	$d[$this->fields['month']];	
    		
    		//check if an array position for the month exists in the course 
    		if (!isset($mcbdata[$courseid][$month])) {
    			$mcbdata[$courseid][$month]	=	array();
    		}
    		
    		//should authabsent not be counted as absent? and does this vary from site to site in which case a config option is needed
    		$present	=	$this->presents_cal($d[$this->fields['markspresent']],$d[$this->fields['marksauthabsent']]);
    		
    		//caculate the months attendance percentage 
    		$monthpercent 	=	($present / $d[$this->fields['markstotal']]) * 100;
    		
    		//fill the couse month array position with percentage for the month
    		$mcbdata[$courseid][$month]	=	array(
    											  'percent'			=>  $monthpercent,
    											  'markstotal'		=>	$d[$this->fields['markstotal']],
    											  'markspresent'	=>	$d[$this->fields['markspresent']],
    											  'marksabsent'		=>	$d[$this->fields['marksabsent']],
    											  'marksauthabsent'	=>	$d[$this->fields['marksauthabsent']],
    											  'markslate'		=>	$d[$this->fields['markslate']]);
    		
    		//check if the course has been added to the courselist array
    		if (!isset($courselist[$courseid])) {
    			$courselist[$courseid]	=	$d[$this->fields['coursename']];
    		}

    		//check if the month has been added  
    		if (!isset($monthlist[$month]))	{
    			$monthlist[$month]	=	$d[$this->fields['monthorder']];
    		} 
    	}
    	
    	//now we have all course data nicely in an array we can work the overall totals
    	foreach ($mcbdata as &$course)	{
    		$presents			=	0;
    		$absents			=	0;
    		$authabsents		=	0;
    		
    		foreach ($course as $monthdata) {
    			$presents		+=	$monthdata['markspresent'];
    			$absents		+=	$monthdata['marksabsent'];
    			$authabsents	+=	$monthdata['marksauthabsent'];
    		}
    		
    		$present	=	$this->presents_cal($presents,$authabsents);
    		$percent	=	($absents	/	$present) * 100;
    		
    		$course['overallpercent']		=	number_format($percent,0);
    		$course['overallabsents']		=	$absents;
    		$course['overallauthabsents']	=	$authabsents;
    		$course['overallpresents']		=	$present;
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
    
    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors 
     * 
     */
    function tab_name() {
    	return 'Monthly Course Breakdown';
    }
    
    
    
}
