<?php 
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');

class ilp_mis_learner_profile_contact extends ilp_mis_plugin	{

	/**
	 * 
	 * Constructor for the class
	 * @param array $params should hold any vars that are needed by plugin. can also hold the 
	 * 						the connection string vars if they are different from those specified 
	 * 						in the mis connection
	 */
	
 	function	__construct($params=array())	{
 		parent::__construct($params);
 		
 		$this->tabletype	=	get_config('block_ilp','mis_learner_contact_tabletype');
 	}
 	
 	/**
 	 * 
 	 * @see ilp_mis_plugin::display()
 	 */
 	function display()	{
 		
 		if (!empty($this->data)) {

 			
 		} else {
 			//print configuration needed message 
 		}
 		
 	} 
 	
    public function set_data( $student_id ){
    	
    		$table	=	get_config('block_ilp','mis_learner_contact_table');
    		
			if (!empty($table)) {

 				$sidfield	=	get_config('block_ilp','mis_learner_contact_studentid');
 			
 				$keyfields	=	array($sidfield	=> array('=' => $student_id));
 				
 				$fields		=	array();
 				
 				if 	(get_config('block_ilp','mis_learner_contact_studentid')) 	$fields[]	=	get_config('block_ilp','mis_learner_contact_studentid');
 				if 	(get_config('block_ilp','mis_learner_contact_enrolmentdate')) $fields[]	=	get_config('block_ilp','mis_learner_contact_enrolmentdate');
 				if 	(get_config('block_ilp','mis_learner_contact_dob')) 		$fields[]	=	get_config('block_ilp','mis_learner_contact_dob');
 				if 	(get_config('block_ilp','mis_learner_contact_email')) 		$fields[]	=	get_config('block_ilp','mis_learner_contact_email');
 				if 	(get_config('block_ilp','mis_learner_contact_phone')) 		$fields[]	=	get_config('block_ilp','mis_learner_contact_phone');
 				if 	(get_config('block_ilp','mis_learner_contact_mobile')) 		$fields[]	=	get_config('block_ilp','mis_learner_contact_mobile');
 				if 	(get_config('block_ilp','mis_learner_contact_emercontact')) $fields[]	=	get_config('block_ilp','mis_learner_contact_emercontact');
 				if 	(get_config('block_ilp','mis_learner_contact_emernumber'))	 $fields[]	=	get_config('block_ilp','mis_learner_contact_emernumber');
 				if 	(get_config('block_ilp','mis_learner_contact_addressone')) 	$fields[]	=	get_config('block_ilp','mis_learner_contact_addressone');
 				if 	(get_config('block_ilp','mis_learner_contact_addresstwo')) 	$fields[]	=	get_config('block_ilp','mis_learner_contact_addresstwo');
 				if 	(get_config('block_ilp','mis_learner_contact_addressthree')) $fields[]	=	get_config('block_ilp','mis_learner_contact_addressthree');
 				if 	(get_config('block_ilp','mis_learner_contact_addressfour')) $fields[]	=	get_config('block_ilp','mis_learner_contact_addressfour');
 				if 	(get_config('block_ilp','mis_learner_contact_postcode')) 	$fields[]	=	get_config('block_ilp','mis_learner_contact_postcode');
				
 				
 				$this->data	=	$this->dbquery( $table, $keyfields, $fields);
 				
 				var_dump($this->data);
 				
 			} else {
 				var_dump('table not set');
 			}
    }
 	
 	
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	
    	$settingsheader 	= new admin_setting_heading('block_ilp/mis_learner_contact', get_string('ilp_mis_learner_contact_pluginname', 'block_ilp'), '');
    	$settings->add($settingsheader);
    	
    	$table		=	new admin_setting_configtext('block_ilp/mis_learner_contact_table',get_string( 'ilp_mis_learner_contact_table', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_tabledesc', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($table);
		
		$keyfield			=	new admin_setting_configtext('block_ilp/mis_learner_contact_studentid',get_string( 'ilp_mis_learner_contact_studentid', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_studentiddesc', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($keyfield);
		
		$enrolementfield			=	new admin_setting_configtext('block_ilp/mis_learner_contact_enrolmentdate',get_string( 'ilp_mis_learner_contact_enrolmentdate', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_enrolmentdatedesc', 'block_ilp' ),'enrolmentDate',PARAM_RAW);
		$settings->add($enrolementfield);
		
		$dobfield			=	new admin_setting_configtext('block_ilp/mis_learner_contact_dob',get_string( 'ilp_mis_learner_contact_dob', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_dobdesc', 'block_ilp' ),'dob',PARAM_RAW);
		$settings->add($dobfield);
		
		$emailfield			=	new admin_setting_configtext('block_ilp/mis_learner_contact_email',get_string( 'ilp_mis_learner_contact_email', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_emaildesc', 'block_ilp' ),'Email',PARAM_RAW);
		$settings->add($emailfield);
		
		$phonefield			=	new admin_setting_configtext('block_ilp/mis_learner_contact_phone',get_string( 'ilp_mis_learner_contact_phone', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_phonedesc', 'block_ilp' ),'Phone',PARAM_RAW);
		$settings->add($phonefield);
		
		$mobilefield			=	new admin_setting_configtext('block_ilp/mis_learner_contact_mobile',get_string( 'ilp_mis_learner_contact_mobile', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_mobiledesc', 'block_ilp' ),'Mobile',PARAM_RAW);
		$settings->add($mobilefield);
		
		$emergencycfield			=	new admin_setting_configtext('block_ilp/mis_learner_contact_emercontact',get_string( 'ilp_mis_learner_contact_emercontact', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_emercontactdesc', 'block_ilp' ),'emergencyContact',PARAM_RAW);
		$settings->add($emergencycfield);
		
		$emergencynfield			=	new admin_setting_configtext('block_ilp/mis_learner_contact_emernumber',get_string( 'ilp_mis_learner_contact_emernumber', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_emernumberdesc', 'block_ilp' ),'emergencyNumber',PARAM_RAW);
		$settings->add($emergencynfield);
		
		$addressfield				=	new admin_setting_configtext('block_ilp/mis_learner_contact_addressone',get_string( 'ilp_mis_learner_contact_addressone', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_addressonedesc', 'block_ilp' ),'Address1',PARAM_RAW);
		$settings->add($addressfield);
		
		$addressfield				=	new admin_setting_configtext('block_ilp/mis_learner_contact_addresstwo',get_string( 'ilp_mis_learner_contact_addresstwo', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_addresstwodesc', 'block_ilp' ),'Address2',PARAM_RAW);
		$settings->add($addressfield);
		
		$addressfield				=	new admin_setting_configtext('block_ilp/mis_learner_contact_addressthree',get_string( 'ilp_mis_learner_contact_addressthree', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_addressthreedesc', 'block_ilp' ),'Address3',PARAM_RAW);
		$settings->add($addressfield);
		
		$addressfield				=	new admin_setting_configtext('block_ilp/mis_learner_contact_addressfour',get_string( 'ilp_mis_learner_contact_addressfour', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_addressfourdesc', 'block_ilp' ),'Address4',PARAM_RAW);
		$settings->add($addressfield);
		
		$postcodefield				=	new admin_setting_configtext('block_ilp/mis_learner_contact_postcode',get_string( 'ilp_mis_learner_contact_postcode', 'block_ilp' ),get_string( 'ilp_mis_learner_contact_postcodedesc', 'block_ilp' ),'postcode',PARAM_RAW);
		$settings->add($postcodefield);
		
		$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
    	
		$pluginstatus			= 	new admin_setting_configselect('block_ilp/mis_learner_contact_tabletype',get_string('ilp_mis_learner_contact_tabletype','block_ilp'),get_string('ilp_mis_learner_contact_tabletypedesc','block_ilp'), 1, $options);
		$settings->add( $pluginstatus );
		
		$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
    	
		$pluginstatus			= 	new admin_setting_configselect('block_ilp/ilp_mis_learner_profile_contact_pluginstatus',get_string('ilp_mis_learner_profile_contact_pluginstatus','block_ilp'),get_string('ilp_mis_learner_profile_contact_pluginstatusdesc','block_ilp'), 0, $options);
		$settings->add( $pluginstatus );
 	 }
    
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 function language_strings(&$string) {

        $string['ilp_mis_learner_contact_pluginname']						= 'Learner Profile Contact Details';
        
        $string['ilp_mis_learner_contact_table']							= 'MIS table';
        $string['ilp_mis_learner_contact_tabledesc']						= 'The table in the MIS where the data for this plugin will be retrieved from';
        
        $string['ilp_mis_learner_contact_studentid']						= 'Student ID field';
        $string['ilp_mis_learner_contact_studentiddesc']					= 'The field that will be used to find the student';
        
        $string['ilp_mis_learner_contact_enrolmentdate']					= 'Enrolement date field';
        $string['ilp_mis_learner_contact_enrolmentdatedesc']				= 'The field that holds enrolement date data';
        
        $string['ilp_mis_learner_contact_dob']								= 'Date of birth field';
        $string['ilp_mis_learner_contact_dobdesc']							= 'The field that holds date of birth data';
        
        
        
        $string['ilp_mis_learner_contact_email']							= 'Email field';
        $string['ilp_mis_learner_contact_emaildesc']						= 'The field that holds email data';
        
        $string['ilp_mis_learner_contact_phone']							= 'Phone field';
        $string['ilp_mis_learner_contact_phonedesc']						= 'The field that holds phone data';
        
        $string['ilp_mis_learner_contact_mobile']							= 'Mobile field';
        $string['ilp_mis_learner_contact_mobiledesc']						= 'The field that holds mobile data';
        
        $string['ilp_mis_learner_contact_emercontact']						= 'Emergency contact field';
        $string['ilp_mis_learner_contact_emercontactdesc']					= 'The field that holds emergency contact data';
        
        $string['ilp_mis_learner_contact_emernumber']						= 'Emergency contact field';
        $string['ilp_mis_learner_contact_emernumberdesc']					= 'The field that holds emergency contact data';
        
        
        $string['ilp_mis_learner_contact_addressone']						= 'Address 1 field';
        $string['ilp_mis_learner_contact_addressonedesc']					= 'The field that holds address 1 data';
        
        $string['ilp_mis_learner_contact_addresstwo']						= 'Address 2 field';
        $string['ilp_mis_learner_contact_addresstwodesc']					= 'The field that holds address 2 data';
        
        $string['ilp_mis_learner_contact_addressthree']						= 'Address 3 field';
        $string['ilp_mis_learner_contact_addressthreedesc']					= 'The field that holds address 3 data';
        
        $string['ilp_mis_learner_contact_addressfour']						= 'Address 4 field';
        $string['ilp_mis_learner_contact_addressfourdesc']					= 'The field that holds address 4 data';
        
        $string['ilp_mis_learner_contact_postcode']							= 'Postcode field';
        $string['ilp_mis_learner_contact_postcodedesc']						= 'The field that holds postcode data';
                
        $string['ilp_mis_learner_contact_tabletype']						= 'Table type';
        $string['ilp_mis_learner_contact_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';        
        
        $string['ilp_mis_learner_profile_contact_pluginstatus']				= 'Status';
        $string['ilp_mis_learner_profile_contact_pluginstatusdesc']			= 'Is the block enabled or disabled';
        
        return $string;
    }

    
    function plugin_type()	{
    	return 'learnerprofile';
    }
 	



}

?>