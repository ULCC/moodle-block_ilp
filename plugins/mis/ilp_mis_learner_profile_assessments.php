<?php 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

class ilp_mis_learner_profile_assessments extends ilp_mis_plugin	{

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
 		
 		$this->tabletype	=	get_config('block_ilp','mis_learner_assessments_tabletype');
 		$this->fields		=	array();
 	}
 	
 	/**
 	 * 
 	 * @see ilp_mis_plugin::display()
 	 */
 	function display()	{
 		global $CFG;
 		
 		if (!empty($this->data)) {
 			//buffer output   
			ob_start();
 			
 			require_once($CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_learner_profile_assessments.html');
 			
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
    		
    		$table	=	get_config('block_ilp','mis_learner_assessments_table');
    		
			if (!empty($table)) {

 				$sidfield	=	get_config('block_ilp','mis_learner_assessments_studentid');
 				
	    		//is the id a string or a int
    			$idtype	=	get_config('block_ilp','mis_learner_assessments_idtype');
    			$mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

                $keyfields	=   array();

                $useyearfilter = get_config('block_ilp', 'mis_learner_assessments_yearfilter');

                if (!empty($useyearfilter)) {

                    $yearfilterfield = get_config('block_ilp', 'mis_learner_assessments_yearfilter_field');
                    $yearfilteryear = get_config('block_ilp', 'mis_learner_assessments_yearfilter_year');

                    $keyfields[$yearfilterfield] = array('=' => $yearfilteryear);
                }

                //create the key that will be used in sql query
                $keyfields[$sidfield] = array('=' => $mis_user_id);
 				
 				$this->fields		=	array();
 				
 				if 	(get_config('block_ilp','mis_learner_assessments_studentid')) 	$this->fields['studentid']	=	get_config('block_ilp','mis_learner_assessments_studentid');
 				if 	(get_config('block_ilp','mis_learner_assessments_maths')) 	$this->fields['maths']	=	get_config('block_ilp','mis_learner_assessments_maths');
 				if 	(get_config('block_ilp','mis_learner_assessments_english')) 		$this->fields['english']	=	get_config('block_ilp','mis_learner_assessments_english');
 				if 	(get_config('block_ilp','mis_learner_assessments_freewriting')) 		$this->fields['freewriting']	=	get_config('block_ilp','mis_learner_assessments_freewriting');
 				if 	(get_config('block_ilp','mis_learner_assessments_ict')) 		$this->fields['ict']	=	get_config('block_ilp','mis_learner_assessments_ict');
 				if 	(get_config('block_ilp','mis_learner_assessments_study')) 		$this->fields['study']	=	get_config('block_ilp','mis_learner_assessments_study');
 				
 				
 				$prelimdbcalls	=	get_config('block_ilp','mis_learner_assessments_prelimcalls');
 				
 				$this->data	=	$this->dbquery( $table, $keyfields, $this->fields,null,$prelimdbcalls);
 				
 				//we only need the first record so pass it back 
 				$this->data	= (!empty($this->data)) ?  array_shift($this->data)	:	$this->data; 	
 				
 			} else {

 			}
    }
 	
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_profile_assessments&plugintype=mis">'.get_string('ilp_mis_learner_assessments_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_learner_assessments', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_learner_assessments_table',get_string('ilp_mis_learner_assessments_table', 'block_ilp'),get_string('ilp_mis_learner_assessments_tabledesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_assessments_studentid',get_string('ilp_mis_learner_assessments_studentid', 'block_ilp'),get_string('ilp_mis_learner_assessments_studentiddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_assessments_maths',get_string('ilp_mis_learner_assessments_maths', 'block_ilp'),get_string('ilp_mis_learner_assessments_mathsdesc', 'block_ilp'),'mathsResult');

 	 	$this->config_text_element($mform,'mis_learner_assessments_english',get_string('ilp_mis_learner_assessments_english', 'block_ilp'),get_string('ilp_mis_learner_assessments_englishdesc', 'block_ilp'),'englishResult');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_assessments_freewriting',get_string('ilp_mis_learner_assessments_freewriting', 'block_ilp'),get_string('ilp_mis_learner_assessments_freewritingdesc', 'block_ilp'),'freewritingResult');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_assessments_ict',get_string('ilp_mis_learner_assessments_ict', 'block_ilp'),get_string('ilp_mis_learner_assessments_ictdesc', 'block_ilp'),'ictResult');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_assessments_study',get_string('ilp_mis_learner_assessments_study', 'block_ilp'),get_string('ilp_mis_learner_assessments_studydesc', 'block_ilp'),'studySupport');

          $this->config_text_element($mform,'mis_learner_assessments_prelimcalls',get_string('ilp_mis_learner_assessments_prelimcalls', 'block_ilp'),get_string('ilp_mis_learner_assessments_prelimcallsdesc', 'block_ilp'),'');

          $options = array(
              ILP_DISABLED => get_string('disabled', 'block_ilp'),
              ILP_ENABLED => get_string('enabled', 'block_ilp')
          );

          $this->config_select_element($mform, 'mis_learner_assessments_yearfilter', $options, get_string('ilp_mis_learner_assessments_yearfilter', 'block_ilp'), get_string('ilp_mis_learner_assessments_yearfilterdesc', 'block_ilp'), 0);

          $this->config_text_element($mform, 'mis_learner_assessments_yearfilter_field', get_string('ilp_mis_learner_assessments_yearfilter_field', 'block_ilp'), get_string('ilp_mis_learner_assessments_yearfilter_fielddesc', 'block_ilp'), 'year');

          $this->config_text_element($mform, 'mis_learner_assessments_yearfilter_year', get_string('ilp_mis_learner_assessments_yearfilter_year', 'block_ilp'), get_string('ilp_mis_learner_assessments_yearfilter_yeardesc', 'block_ilp'), date('Y'));



 	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_learner_assessments_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	 	 	
 	 	
 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_learner_assessments_tabletype',$options,get_string('ilp_mis_learner_assessments_tabletype', 'block_ilp'),get_string('ilp_mis_learner_assessments_tabletypedesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_learner_profile_assessments_pluginstatus',$options,get_string('ilp_mis_learner_profile_assessments_pluginstatus', 'block_ilp'),get_string('ilp_mis_learner_profile_assessments_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {

        $string['ilp_mis_learner_assessments_pluginname']						= 'Learner Profile Initial Assessment';
        $string['ilp_mis_learner_assessments_pluginnamesettings']				= 'Initial Assessment Configuration';
        
        $string['ilp_mis_learner_assessments_table']							= 'MIS table';
        $string['ilp_mis_learner_assessments_tabledesc']						= 'The table in the MIS where the data for this plugin will be retrieved from';
        
        $string['ilp_mis_learner_assessments_studentid']						= 'Student ID field';
        $string['ilp_mis_learner_assessments_studentiddesc']					= 'The field that will be used to find the student';
        
        $string['ilp_mis_learner_assessments_maths']							= 'Maths data field';
        $string['ilp_mis_learner_assessments_mathsdesc']						= 'The field that holds maths data';
        
        $string['ilp_mis_learner_assessments_english']							= 'English data field';
        $string['ilp_mis_learner_assessments_englishdesc']						= 'English data';
        
        $string['ilp_mis_learner_assessments_freewriting']						= 'freewriting data field';
        $string['ilp_mis_learner_assessments_freewritingdesc']					= 'Freewriting data';
        
        
        
        $string['ilp_mis_learner_assessments_ict']								= 'ICT data field';
        $string['ilp_mis_learner_assessments_ictdesc']							= 'The field that holds ICT data';
        
        $string['ilp_mis_learner_assessments_study']							= 'Study support field';
        $string['ilp_mis_learner_assessments_studydesc']						= 'The field that holds study support data';
                
        $string['ilp_mis_learner_assessments_tabletype']						= 'Table type';
        $string['ilp_mis_learner_assessments_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';        
        
        $string['ilp_mis_learner_profile_assessments_pluginstatus']				= 'Status';
        $string['ilp_mis_learner_profile_assessments_pluginstatusdesc']			= 'Is the block enabled or disabled';

        $string['ilp_mis_learner_assessments_prelimcalls']						= 'Preliminary db calls';
        $string['ilp_mis_learner_assessments_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';

         $string['ilp_mis_learner_assessments_yearfilter']                      = 'Year filter';
         $string['ilp_mis_learner_assessments_yearfilterdesc']                  = 'Is a year filter used when selecting data from the MIS';

         $string['ilp_mis_learner_assessments_yearfilter_field']                = 'Year filter field';
         $string['ilp_mis_learner_assessments_yearfilter_fielddesc']            = 'If a MIS year filter is being used enter the field that will be filter on. (if stored procedure and field not needed leave field as year)';

         $string['ilp_mis_learner_assessments_yearfilter_year']                 = 'Year filter date';
         $string['ilp_mis_learner_assessments_yearfilter_yeardesc']             = 'The date that will be filtered on';
        
        
        $string['ilp_mis_learner_profile_assessments_disp_assessments']				= 'Initial Assessments';
        $string['ilp_mis_learner_profile_assessments_disp_maths']					= 'Maths';
        $string['ilp_mis_learner_profile_assessments_disp_english']					= 'English';
        $string['ilp_mis_learner_profile_assessments_disp_ict']						= 'ICT';
        $string['ilp_mis_learner_profile_assessments_disp_study']					= 'Study Support';
        $string['ilp_mis_learner_profile_assessments_disp_freewriting']				= 'Free Writing';
        $string['ilp_mis_learner_profile_assessments_tab_name']				= 'Initial Assessment';

        
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
        return get_string('ilp_mis_learner_profile_assessments_tab_name','block_ilp');
    }


}

?>
