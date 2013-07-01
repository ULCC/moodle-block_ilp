<?php 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');



class ilp_mis_misc_performance_ind extends ilp_mis_plugin	{

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
 		
 		$this->tabletype	=	get_config('block_ilp','mis_misc_performance_ind_tabletype');
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
     		// set up the flexible table for displaying the portfolios

	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'exam_timetable',true ,'ilp_mis_misc_performance_ind');
	
	        //create headers
	        
	        //setup the headers and columns with the fields that have been requested 

	        $headers		=	array();
	        $columns		=	array();
	        
	        
	        $headers[]		=	get_string('ilp_mis_misc_performance_ind_title_disp','block_ilp');
	        $headers[]		=	get_string('ilp_mis_misc_performance_ind_score_disp','block_ilp');
	        	        
	        $columns[]		=	'title';
	        $columns[]		=	'score';
   

	        //define the columns in the tables
	        $flextable->define_columns($columns);
	        
	        //define the headers in the tables
	        $flextable->define_headers($headers);
	        
	        //we do not need the intialbars
	        $flextable->initialbars(false);
	        
	        $flextable->set_attribute('class', 'flexible generaltable');
	        
	        //setup the flextable
	        $flextable->setup();
        	        
	        //add the row to table
	        foreach( $this->data as $k => $v ){
	        		$data	=	array();
	        		$string	=	strtolower("ilp_mis_misc_performance_ind_{$k}_disp");
	        		$data['title']		=	get_string($string,'block_ilp');
	        		$data['score']		=	$v;
	        		$flextable->add_data_keyed( $data );			
	        }
	        
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

    		$table	=	get_config('block_ilp','mis_misc_performance_ind_table');
    		
			if (!empty($table)) {

                $idtype	=	get_config('block_ilp','mis_misc_performance_ind_idtype');

                $sidfield	=	get_config('block_ilp','mis_misc_performance_ind_studentid');

                $this->mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

 				$keyfields	=	($this->tabletype == ILP_MIS_STOREDPROCEDURE) ? array($this->mis_user_id) : array($sidfield	=> array('=' => $this->mis_user_id));
 				
 				$this->fields		=	array();
 				
 				if 	(get_config('block_ilp','mis_misc_performance_ind_atg')) 		$this->fields['atg']	=	get_config('block_ilp','mis_misc_performance_ind_atg');
 				if 	(get_config('block_ilp','mis_misc_performance_ind_mtg')) 		$this->fields['mtg']	=	get_config('block_ilp','mis_misc_performance_ind_mtg');
 				if 	(get_config('block_ilp','mis_misc_performance_ind_grade')) 		$this->fields['grade']	=	get_config('block_ilp','mis_misc_performance_ind_grade');
 				if 	(get_config('block_ilp','mis_misc_performance_ind_performancscore')) 	$this->fields['performancescore']	=	get_config('block_ilp','mis_misc_performance_ind_performancscore');

                $prelimdbcalls   =    get_config('block_ilp','mis_misc_performance_ind_prelimcalls');

 				$this->data	=	$this->dbquery( $table, $keyfields, $this->fields, null, $prelimdbcalls);

 				$this->data	=	(!empty($this->data)) ? array_shift($this->data) : false;
 				
 			} 
    }

    
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_misc_performance_ind&plugintype=mis">'.get_string('ilp_mis_misc_performance_ind_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_misc_performance_ind', '', $link));
 	 }
    
 	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_misc_performance_ind_table',get_string('ilp_mis_misc_performance_ind_table', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_tabledesc', 'block_ilp'),'');

        $this->config_text_element($mform,'mis_misc_performance_ind_prelimcalls',get_string('ilp_mis_misc_performance_ind_prelimcalls', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_prelimcallsdesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_performance_ind_studentid',get_string('ilp_mis_misc_performance_ind_studentid', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_studentiddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_performance_ind_atg',get_string('ilp_mis_misc_performance_ind_atg', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_atgdesc', 'block_ilp'),'Atg');

 	 	$this->config_text_element($mform,'mis_misc_performance_ind_mtg',get_string('ilp_mis_misc_performance_ind_mtg', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_mtgdesc', 'block_ilp'),'Mtg');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_performance_ind_grade',get_string('ilp_mis_misc_performance_ind_grade', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_gradedesc', 'block_ilp'),'Grade');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_performance_ind_performancscore',get_string('ilp_mis_misc_performance_ind_performancescore', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_performancescoredesc', 'block_ilp'),'performanceScore');

 	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_misc_performance_ind_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	 	 	
 	 	
 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_misc_performance_ind_tabletype',$options,get_string('ilp_mis_misc_performance_ind_tabletype', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_tabletypedesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_misc_performance_ind_pluginstatus',$options,get_string('ilp_mis_misc_performance_ind_pluginstatus', 'block_ilp'),get_string('ilp_mis_misc_performance_ind_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }
 	 
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {

        $string['ilp_mis_misc_performance_ind_pluginname']						= 'Performance Indicators';
        
        $string['ilp_mis_misc_performance_ind_pluginnamesettings']				= 'Performance Indicators Configuration';
        
        $string['ilp_mis_misc_performance_ind_table']							= 'MIS table';
        $string['ilp_mis_misc_performance_ind_tabledesc']						= 'The table in the MIS where the data for this plugin will be retrieved from';
        
        $string['ilp_mis_misc_performance_ind_studentid']						= 'Student ID field';
        $string['ilp_mis_misc_performance_ind_studentiddesc']					= 'The field that will be used to find the student';
        
        $string['ilp_mis_misc_performance_ind_atg']								= 'Atg data field';
        $string['ilp_mis_misc_performance_ind_atgdesc']							= 'The field that holds aspiration target grade data';
        
        $string['ilp_mis_misc_performance_ind_mtg']								= 'Mtg data field';
        $string['ilp_mis_misc_performance_ind_mtgdesc']							= 'The field that holds minimum target grade data';
        
        $string['ilp_mis_misc_performance_ind_grade']							= 'Grade data field';
        $string['ilp_mis_misc_performance_ind_gradedesc']						= 'The field that holds current grade data';
        
		$string['ilp_mis_misc_performance_ind_performancescore']				= 'Performance Score field';
        $string['ilp_mis_misc_performance_ind_performancescoredesc']			= 'The field that holds performance score data';
                
        $string['ilp_mis_misc_performance_ind_tabletype']						= 'Table type';
        $string['ilp_mis_misc_performance_ind_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';        
        $string['ilp_mis_misc_performance_ind_pluginstatus']					= 'Status';
        $string['ilp_mis_misc_performance_ind_pluginstatusdesc']				= 'Is the block enabled or disabled';

        $string['ilp_mis_misc_performance_ind_title_disp']						= 'Perfomance';
        $string['ilp_mis_misc_performance_ind_score_disp']						= 'Score';
        $string['ilp_mis_misc_performance_ind_gscore_disp']						= 'Grade Score';
        $string['ilp_mis_misc_performance_ind_mtg_disp']						= 'Minimum Target Grade';
        $string['ilp_mis_misc_performance_ind_atg_disp']						= 'Aspiration Target Grade';
        $string['ilp_mis_misc_performance_ind_grade_disp']						= 'Current Grade';
        $string['ilp_mis_misc_performance_ind_performancescore_disp']			= 'Performance';

         $string['ilp_mis_misc_performance_ind_prelimcalls']						= 'Preliminary db calls';
         $string['ilp_mis_misc_performance_ind_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';
         $string['ilp_mis_misc_performance_ind_tab_name']					= 'Performance Indicators';


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
        return get_string('ilp_mis_misc_performance_ind_tab_name','block_ilp');
    }


}

?>
