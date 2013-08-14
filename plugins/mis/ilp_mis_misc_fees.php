<?php 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');




class ilp_mis_misc_fees extends ilp_mis_plugin	{

	protected 	$fields;
	protected 	$mis_user_id;
	protected 	$userfullname;
	
	/**
	 * 
	 * Constructor for the class
	 * @param array $params should hold any vars that are needed by plugin. can also hold the 
	 * 						the connection string vars if they are different from those specified 
	 * 						in the mis connection
	 */
	
 	function	__construct($params=array())	{
 		parent::__construct($params);
 		
 		$this->tabletype	=	get_config('block_ilp','mis_misc_fees_tabletype');
 		$this->fields		=	array();
 		$this->userfullname =	false;
 	}
 	
 	/**
 	 * 
 	 * @see ilp_mis_plugin::display()
 	 */
 	function display()	{
 		global $CFG;

        // set up the flexible table for displaying the data
 		
 		if (!empty($this->data)) {
 			
			
 			
	        //buffer output  
			ob_start();
			
			//call the html file for the plugin 
			require_once($CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_misc_fees.html');
			
			$pluginoutput = ob_get_contents();
			
	        ob_end_clean();
 			
 			return $pluginoutput;
 			
 			
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
 	
 	
    public function set_data( $mis_user_id, $user_id=null ){
    		
    		$this->mis_user_id	=	$mis_user_id;
    		if (!empty($user_id))	{ 
    			$user	=	$this->dbc->get_user_by_id($user_id);
    			$this->userfullname	=	fullname($user);
    		}
    		
    		$table	=	get_config('block_ilp','mis_misc_fees_table');
    		
			if (!empty($table)) {

 				$sidfield	=	get_config('block_ilp','mis_misc_fees_studentid');
 				
	    		//is the id a string or a int
    			$idtype	=	get_config('block_ilp','mis_misc_fees_idtype');
    			$mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

                $useyearfilter = get_config('block_ilp', 'mis_misc_fees_yearfilter');

                if (!empty($useyearfilter)) {

                    $yearfilterfield = get_config('block_ilp', 'mis_misc_fees_yearfilter_field');
                    $yearfilteryear = get_config('block_ilp', 'mis_misc_fees_yearfilter_year');

                    $keyfields[$yearfilterfield] = array('=' => $yearfilteryear);
                }

                //create the key that will be used in sql query
                $keyfields[$sidfield] = array('=' => $mis_user_id);

 								
 				$this->fields		=	array();
 				
 				if 	(get_config('block_ilp','mis_misc_fees_totalfees')) 	$this->fields['totalfees']	=	get_config('block_ilp','mis_misc_fees_totalfees');
 				if 	(get_config('block_ilp','mis_misc_fees_feesdue')) 		$this->fields['feesdue']	=	get_config('block_ilp','mis_misc_fees_feesdue');
 				if 	(get_config('block_ilp','mis_misc_fees_totalpaid')) 		$this->fields['totalpaid']	=	get_config('block_ilp','mis_misc_fees_totalpaid');
 				if 	(get_config('block_ilp','mis_misc_fees_outstanding')) 		$this->fields['outstanding']	=	get_config('block_ilp','mis_misc_fees_outstanding');
 				if 	(get_config('block_ilp','mis_misc_fees_overdue')) 		$this->fields['overdue']	=	get_config('block_ilp','mis_misc_fees_overdue');

                $prelimdbcalls	=	get_config('block_ilp','mis_misc_fees_prelimcalls');

                $this->data	=	$this->dbquery( $table, $keyfields, $this->fields,null,$prelimdbcalls);

 				if (!empty($this->data)) {
 					$this->data	=	(is_array($this->data)) ? array_shift($this->data)	:	$this->data;
 				}
 			} 
    }

	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_misc_fees&plugintype=mis">'.get_string('ilp_mis_misc_fees_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_misc_fees', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_misc_fees_table',get_string('ilp_mis_misc_fees_table', 'block_ilp'),get_string('ilp_mis_misc_fees_tabledesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_fees_studentid',get_string('ilp_mis_misc_fees_studentid', 'block_ilp'),get_string('ilp_mis_misc_fees_studentiddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_fees_totalfees',get_string('ilp_mis_misc_fees_totalfees', 'block_ilp'),get_string('ilp_mis_misc_fees_totalfeesdesc', 'block_ilp'),'totalFees');

 	 	$this->config_text_element($mform,'mis_misc_fees_feesdue',get_string('ilp_mis_misc_fees_feesdue', 'block_ilp'),get_string('ilp_mis_misc_fees_feesduedesc', 'block_ilp'),'feesDue');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_fees_totalpaid',get_string('ilp_mis_misc_fees_totalpaid', 'block_ilp'),get_string('ilp_mis_misc_fees_totalpaiddesc', 'block_ilp'),'totalPaid');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_fees_outstanding',get_string('ilp_mis_misc_fees_outstanding', 'block_ilp'),get_string('ilp_mis_misc_fees_outstandingdesc', 'block_ilp'),'outstanding');
 	 	
 	 	$this->config_text_element($mform,'mis_misc_fees_overdue',get_string('ilp_mis_misc_fees_overdue', 'block_ilp'),get_string('ilp_mis_misc_fees_overduedesc', 'block_ilp'),'overdue');

        $this->config_text_element($mform,'mis_misc_fees_prelimcalls',get_string('ilp_mis_misc_fees_prelimcalls', 'block_ilp'),get_string('ilp_mis_misc_fees_prelimcallsdesc', 'block_ilp'),'');

          $options = array(
              ILP_DISABLED => get_string('disabled', 'block_ilp'),
              ILP_ENABLED => get_string('enabled', 'block_ilp')
          );

          $this->config_select_element($mform, 'mis_misc_fees_yearfilter', $options, get_string('ilp_mis_misc_fees_yearfilter', 'block_ilp'), get_string('ilp_mis_misc_fees_yearfilterdesc', 'block_ilp'), 0);

          $this->config_text_element($mform, 'mis_misc_fees_yearfilter_field', get_string('ilp_mis_misc_fees_yearfilter_fielddesc', 'block_ilp'), 'year');

          $this->config_text_element($mform, 'mis_misc_fees_yearfilter_year', get_string('ilp_mis_misc_fees_yearfilter_year', 'block_ilp'), get_string('ilp_mis_misc_fees_yearfilter_yeardesc', 'block_ilp'), date('Y'));


 	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_misc_fees_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	 	 	
 	 	
  	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_misc_fees_tabletype',$options,get_string('ilp_mis_misc_fees_tabletype', 'block_ilp'),get_string('ilp_mis_misc_fees_tabletypedesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_misc_fees_pluginstatus',$options,get_string('ilp_mis_misc_fees_pluginstatus', 'block_ilp'),get_string('ilp_mis_misc_fees_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {

        $string['ilp_mis_misc_fees_pluginname']						= 'Fees';
        $string['ilp_mis_misc_fees_pluginnamesettings']						= 'Fees Payment Configuration';
        
        $string['ilp_mis_misc_fees_table']								= 'MIS table';
        $string['ilp_mis_misc_fees_tabledesc']							= 'The table in the MIS where the data for this plugin will be retrieved from';
        
        $string['ilp_mis_misc_fees_studentid']							= 'Student ID field';
        $string['ilp_mis_misc_fees_studentiddesc']						= 'The field that will be used to find the student';
        
        $string['ilp_mis_misc_fees_totalfees']								= 'Total fees data field';
        $string['ilp_mis_misc_fees_totalfeesdesc']							= 'The field that holds total fees data';
        
        $string['ilp_mis_misc_fees_feesdue']							= 'Fees due data field';
        $string['ilp_mis_misc_fees_feesduedesc']						= 'Fees due data';
        
        
        
        $string['ilp_mis_misc_fees_totalpaid']								= 'Total paid data field';
        $string['ilp_mis_misc_fees_totalpaiddesc']							= 'The field that holds total paid data';
        
        $string['ilp_mis_misc_fees_outstanding']									= 'Outstanding data field';
        $string['ilp_mis_misc_fees_outstandingdesc']								= 'The field that holds outstanding data field';
        
        $string['ilp_mis_misc_fees_overdue']									= 'Over due data field';
        $string['ilp_mis_misc_fees_overduedesc']								= 'The field that holds over due data';
                
        $string['ilp_mis_misc_fees_tabletype']								= 'Table type';
        $string['ilp_mis_misc_fees_tabletypedesc']							= 'Does this plugin connect to a table or stored procedure';        
        
        $string['ilp_mis_misc_fees_pluginstatus']						= 'Status';
        $string['ilp_mis_misc_fees_pluginstatusdesc']					= 'Is the block enabled or disabled';
        
        $string['ilp_mis_misc_fees_debtor_disp']						= 'Debtor';
        $string['ilp_mis_misc_fees_totalfees_disp']						= 'Total Fees';
        $string['ilp_mis_misc_fees_feesdue_disp']						= 'Due to Date';
        $string['ilp_mis_misc_fees_totalpaid_disp']						= 'Total Paid';
        $string['ilp_mis_misc_fees_outstanding_disp']					= 'Outstanding';
        $string['ilp_mis_misc_fees_overdue_disp']						= 'Payments Overdue';
        
        $string['ilp_mis_misc_fees']											=	'Fee Payment';

         $string['ilp_mis_misc_prelimcalls']						= 'Preliminary db calls';
         $string['ilp_mis_misc_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';

         $string['ilp_mis_misc_fees_yearfilter']                      = 'Year filter';
         $string['ilp_mis_misc_fees_yearfilterdesc']                  = 'Is a year filter used when selecting data from the MIS';

         $string['ilp_mis_misc_fees_yearfilter_field']                = 'Year filter field';
         $string['ilp_mis_misc_fees_yearfilter_fielddesc']            = 'If a MIS year filter is being used enter the field that will be filter on. (if stored procedure and field not needed leave field as year)';

         $string['ilp_mis_misc_fees_yearfilter_year']                 = 'Year filter date';
         $string['ilp_mis_misc_fees_yearfilter_yeardesc']             = 'The date that will be filtered on';

		 $string['ilp_mis_misc_fees_prelimcalls']						= 'Preliminary db calls';
         $string['ilp_mis_misc_fees_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';
        

         return $string;
    }

    
    static function plugin_type()	{
    	return 'misc';
    }
 	
    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors 
     * 
     */
    function tab_name() {
    	return 'Fees';
    }


}

?>