<?php 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');


class ilp_mis_learner_profile_qualifications extends ilp_mis_plugin	{

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
 		
 		$this->tabletype	=	get_config('block_ilp','mis_learner_qualifications_tabletype');
 		$this->fields		=	array();
 	}

    /*
    * calculate average points on entry for a student, in isolation from outputting the flexable table
    * used in the grade tracker
    * @return int
    */
    public function get_qca_stats(){
 		global $CFG;
 		if (!empty($this->data)) {
	        $i	=	0;
	        $total	=	0;
	        foreach( $this->data as $row ){
	            if (!empty($row[get_config('block_ilp','mis_learner_qualifications_points')])) {
	            	$i++;
	            	$weight	=	$row[get_config('block_ilp','mis_learner_qualifications_weight')];
	            	$points	=	$row[get_config('block_ilp','mis_learner_qualifications_points')];
	            	$total 	+=	(!empty($weight))	? $points * $weight	: $points ; 
	            }
            }
        }
		$average	=	(!empty($total)) ? $total	/$i : 0;
        return array(
            'average' => $average,
            'total' => $total
        );
    }
 	
 	/**
 	 * 
 	 * @see ilp_mis_plugin::display()
 	 */
 	function display()	{
 		global $CFG;


        // set up the flexible table for displaying the data
 		
 		if (!empty($this->data)) {
 			
		   // set up the flexible table for displaying the portfolios

	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'learner_profile_qualifications',true,'ilp_mis_learner_profile_qualifications' );
	
	        //create headers
	        $headers	=	array();
	        $columns	=	array();
	        //setup the headers and columns with the fields that have been requested 

	        foreach ($this->fields as $k => $v) {
	        		        	
	        	if ("mis_learner_qualifications_{$k}" != 'mis_learner_qualifications_weight') { 
	        		$string		=	"ilp_mis_learner_qualifications_{$k}_disp";
	        		$headers[] = get_string($string,'block_ilp');
	        		$string	=	"mis_learner_qualifications_{$k}";
	        	    $columns[] = get_config('block_ilp',$string);
	        	}
	        }
	        
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

	            foreach($this->fields as $k => $v) {
	            	$string		=	"mis_learner_qualifications_{$k}";
	            	$configvar	=	get_config('block_ilp',$string);
	            	
	            	//we dont need to display the weight data
	            	if ($configvar != get_config('block_ilp','mis_learner_qualifications_weight')) {  
	            	      	$data[ $configvar ] = $row[$configvar];
	            	}	
	            }
	             
	            $flextable->add_data_keyed( $data );
	            if (!empty($row[get_config('block_ilp','mis_learner_qualifications_points')])) {
	            	$i++;
	            	$weight	=	$row[get_config('block_ilp','mis_learner_qualifications_weight')];
	            	$points	=	$row[get_config('block_ilp','mis_learner_qualifications_points')];
	            	
	            	$total 	+=	(!empty($weight))	? $points * $weight	: $points ; 
	            }
	        }
	        
	        //calculate the average of the students qualification points
			$average	=	(!empty($total)) ? $total	/$i : 0;
	        
	        //buffer out as flextable sends its data straight to the screen we dont want this  
			ob_start();
			
			$flextable->wrap_finish_extra	=	(!empty($row[get_config('block_ilp','mis_learner_qualifications_points')])) ? "<div id='ilp_mis_learner_profile_qualifications_average'><label>".get_string( 'ilp_mis_learner_qualifications_average', 'block_ilp' )."</label>".number_format($average,0)."</div>" : "";
						
			//call the html file for the plugin which has the flextable print statement
			//require_once($CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_learner_profile_qualifications.html');
 
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
    		
    		$table	=	get_config('block_ilp','mis_learner_qualifications_table');

    		
			if (!empty($table)) {

 				$sidfield	=	get_config('block_ilp','mis_learner_qualifications_studentid');
 				
	    		//is the id a string or a int
    			$idtype	=	get_config('block_ilp','mis_learner_qualifications_idtype');
    			$mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id; 				
 			
 				$keyfields	=	array($sidfield	=> array('=' => $mis_user_id));
 				
 				$this->fields		=	array();
 				
 				if 	(get_config('block_ilp','mis_learner_qualifications_qual')) 		$this->fields['qual']	=	get_config('block_ilp','mis_learner_qualifications_qual');
 				if 	(get_config('block_ilp','mis_learner_qualifications_subject')) 		$this->fields['subject']	=	get_config('block_ilp','mis_learner_qualifications_subject');
 				if 	(get_config('block_ilp','mis_learner_qualifications_grade')) 		$this->fields['grade']	=	get_config('block_ilp','mis_learner_qualifications_grade');
 				if 	(get_config('block_ilp','mis_learner_qualifications_points')) 		$this->fields['points']	=	get_config('block_ilp','mis_learner_qualifications_points');
 				if 	(get_config('block_ilp','mis_learner_qualifications_year')) 		$this->fields['year']	=	get_config('block_ilp','mis_learner_qualifications_year');
 				if 	(get_config('block_ilp','mis_learner_qualifications_weight')) 		$this->fields['weight']	=	get_config('block_ilp','mis_learner_qualifications_weight');

                $prelimdbcalls   =    get_config('block_ilp','mis_learner_qualifications_prelimcalls');

 				$this->data	=	$this->dbquery( $table, $keyfields, $this->fields, null, $prelimdbcalls);
 				
 			} 
    }
 	
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_profile_qualifications&plugintype=mis">'.get_string('ilp_mis_learner_qualifications_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_learner_qualifications', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_learner_qualifications_table',get_string('ilp_mis_learner_qualifications_table', 'block_ilp'),get_string('ilp_mis_learner_qualifications_tabledesc', 'block_ilp'),'');

        $this->config_text_element($mform,'mis_learner_qualifications_prelimcalls',get_string('ilp_mis_learner_qualifications_prelimcalls', 'block_ilp'),get_string('ilp_mis_learner_qualifications_prelimcallsdesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_qualifications_studentid',get_string('ilp_mis_learner_qualifications_studentid', 'block_ilp'),get_string('ilp_mis_learner_qualifications_studentiddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_qualifications_qual',get_string('ilp_mis_learner_qualifications_qual', 'block_ilp'),get_string('ilp_mis_learner_qualifications_qualdesc', 'block_ilp'),'qualTitle');

 	 	$this->config_text_element($mform,'mis_learner_qualifications_subject',get_string('ilp_mis_learner_qualifications_subject', 'block_ilp'),get_string('ilp_mis_learner_qualifications_subjectdesc', 'block_ilp'),'subject');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_qualifications_grade',get_string('ilp_mis_learner_qualifications_grade', 'block_ilp'),get_string('ilp_mis_learner_qualifications_gradedesc', 'block_ilp'),'grade');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_qualifications_points',get_string('ilp_mis_learner_qualifications_points', 'block_ilp'),get_string('ilp_mis_learner_qualifications_pointsdesc', 'block_ilp'),'points');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_qualifications_year',get_string('ilp_mis_learner_qualifications_year', 'block_ilp'),get_string('ilp_mis_learner_qualifications_yeardesc', 'block_ilp'),'year');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_qualifications_weight',get_string('ilp_mis_learner_qualifications_weight', 'block_ilp'),get_string('ilp_mis_learner_qualifications_weightdesc', 'block_ilp'),'weight');

 	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_learner_qualifications_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	 	 	
 	 	
		$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_learner_qualifications_tabletype',$options,get_string('ilp_mis_learner_qualifications_tabletype', 'block_ilp'),get_string('ilp_mis_learner_qualifications_tabletypedesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_learner_profile_qualifications_pluginstatus',$options,get_string('ilp_mis_learner_profile_qualifications_pluginstatus', 'block_ilp'),get_string('ilp_mis_learner_profile_qualifications_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }

    
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {

        $string['ilp_mis_learner_qualifications_pluginname']						= 'Learner Profile Qualifications On Entry';
        $string['ilp_mis_learner_qualifications_pluginnamesettings']				= 'Qualifications On Entry Configuration';
        
        $string['ilp_mis_learner_qualifications_table']								= 'MIS table';
        $string['ilp_mis_learner_qualifications_tabledesc']							= 'The table in the MIS where the data for this plugin will be retrieved from';
        
        $string['ilp_mis_learner_qualifications_studentid']							= 'Student ID field';
        $string['ilp_mis_learner_qualifications_studentiddesc']						= 'The field that will be used to find the student';
        
        $string['ilp_mis_learner_qualifications_qual']								= 'Qualification data field';
        $string['ilp_mis_learner_qualifications_qualdesc']							= 'The field that holds qualification data';
        
        $string['ilp_mis_learner_qualifications_subject']								= 'Subject data field';
        $string['ilp_mis_learner_qualifications_subjectdesc']							= 'The field that holds subject data';
        
        $string['ilp_mis_learner_qualifications_grade']							= 'Grade data field';
        $string['ilp_mis_learner_qualifications_gradedesc']						= 'Grade data';
        
        
        
        $string['ilp_mis_learner_qualifications_points']								= 'Points data field';
        $string['ilp_mis_learner_qualifications_pointsdesc']							= 'The field that holds Points data';
        
        $string['ilp_mis_learner_qualifications_year']									= 'Year data field';
        $string['ilp_mis_learner_qualifications_yeardesc']								= 'The field that holds year data';
        
        $string['ilp_mis_learner_qualifications_weight']									= 'Weight data field';
        $string['ilp_mis_learner_qualifications_weightdesc']								= 'The field that holds weight data';
                
        $string['ilp_mis_learner_qualifications_tabletype']								= 'Table type';
        $string['ilp_mis_learner_qualifications_tabletypedesc']							= 'Does this plugin connect to a table or stored procedure';        
        
        $string['ilp_mis_learner_profile_qualifications_pluginstatus']					= 'Status';
        $string['ilp_mis_learner_profile_qualifications_pluginstatusdesc']				= 'Is the block enabled or disabled';

        $string['ilp_mis_learner_qualifications_qual_disp']						= 'Qualification';
        $string['ilp_mis_learner_qualifications_subject_disp']					= 'Subject';
        $string['ilp_mis_learner_qualifications_grade_disp']					= 'Grade';
        $string['ilp_mis_learner_qualifications_points_disp']					= 'Points';
        $string['ilp_mis_learner_qualifications_year_disp']						= 'Year';
        
        $string['ilp_mis_learner_qualifications_average']							= 'Average Points:';

        $string['ilp_mis_learner_qualifications_tab_name']							= 'Qualification of Entry';
		$string['ilp_mis_learner_qualifications_prelimcalls']						= 'Preliminary db calls';
        $string['ilp_mis_learner_qualifications_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';
        
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
    	return get_string('ilp_mis_learner_qualifications_tab_name','block_ilp');
    }


}

?>
