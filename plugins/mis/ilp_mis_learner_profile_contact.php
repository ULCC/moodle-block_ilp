<?php 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

class ilp_mis_learner_profile_contact extends ilp_mis_plugin	{

	protected 	$fields;
	protected 	$mis_user_id;
	protected 	$user_id;
	
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
 		$this->fields		=	array();
 	}
 	
 	/**
 	 * 
 	 * @see ilp_mis_plugin::display()
 	 */
 	function display()	{
 		global $CFG;
 		
 		if (!empty($this->data)) {
			
 			//get the moodle user record of the user
 			$user	=	$this->dbc->get_user_by_id($this->user_id);
 			
 			//buffer output  
			ob_start();
			
			//call the html file 
			require_once($CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_learner_profile_contact.html');
			
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
 	
 	
    public function set_data( $mis_user_id, $user_id=NULL ){

            //this check is in place as we have to make sure the userid is populated
            if (empty($user_id))  return false;

    		$this->mis_user_id	=	$mis_user_id;
    		$this->user_id		=	$user_id;
    		
    		$table	=	get_config('block_ilp','mis_learner_contact_table');
    		
			if (!empty($table)) {

 				$sidfield	=	get_config('block_ilp','mis_learner_contact_studentid');
 				
	 			//is the id a string or a int
	    		$idtype	=	get_config('block_ilp','mis_learner_contact_idtype');
	    		$mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;
 			
 				$keyfields	=	array($sidfield	=> array('=' => $mis_user_id));
 				
 				$this->fields		=	array();
 				
 				if 	(get_config('block_ilp','mis_learner_contact_studentid')) 	$this->fields['studentid']	=	get_config('block_ilp','mis_learner_contact_studentid');
 				if 	(get_config('block_ilp','mis_learner_contact_enrolmentdate')) $this->fields['enrolmentdate']	=	get_config('block_ilp','mis_learner_contact_enrolmentdate');
 				if 	(get_config('block_ilp','mis_learner_contact_dob')) 		$this->fields['dob']	=	get_config('block_ilp','mis_learner_contact_dob');
 				if 	(get_config('block_ilp','mis_learner_contact_email')) 		$this->fields['email']	=	get_config('block_ilp','mis_learner_contact_email');
 				if 	(get_config('block_ilp','mis_learner_contact_phone')) 		$this->fields['phone']	=	get_config('block_ilp','mis_learner_contact_phone');
 				if 	(get_config('block_ilp','mis_learner_contact_mobile')) 		$this->fields['mobile']	=	get_config('block_ilp','mis_learner_contact_mobile');
 				if 	(get_config('block_ilp','mis_learner_contact_emercontact')) $this->fields['emercontact']	=	get_config('block_ilp','mis_learner_contact_emercontact');
 				if 	(get_config('block_ilp','mis_learner_contact_emernumber'))	 $this->fields['emernumber']	=	get_config('block_ilp','mis_learner_contact_emernumber');
 				if 	(get_config('block_ilp','mis_learner_contact_addressone')) 	$this->fields['addressone']	=	get_config('block_ilp','mis_learner_contact_addressone');
 				if 	(get_config('block_ilp','mis_learner_contact_addresstwo')) 	$this->fields['addresstwo']	=	get_config('block_ilp','mis_learner_contact_addresstwo');
 				if 	(get_config('block_ilp','mis_learner_contact_addressthree')) $this->fields['addressthree']	=	get_config('block_ilp','mis_learner_contact_addressthree');
 				if 	(get_config('block_ilp','mis_learner_contact_addressfour')) $this->fields['addressfour']	=	get_config('block_ilp','mis_learner_contact_addressfour');
 				if 	(get_config('block_ilp','mis_learner_contact_postcode')) 	$this->fields['postcode']	=	get_config('block_ilp','mis_learner_contact_postcode');
                if  (get_config('block_ilp','mis_learner_contact_tutor'))   $this->fields['tutor']      =   get_config('block_ilp','mis_learner_contact_tutor');

                $prelimdbcalls   =    get_config('block_ilp','mis_learner_contact_prelimcalls');

 				$data	=	$this->dbquery( $table, $keyfields, $this->fields, null, $prelimdbcalls);
                $data   =   $this->populate_from_usertable( array_shift( $data ) , $user_id );
 				
 				//$this->data	=	(!empty($data)) ? array_shift($data) : false;
 				$this->data	=	(!empty($data)) ? $data : false;
				
 			} 
    }

    /*
    * not all contact fields may be available from MIS db
    * here we can add some of the missing data from the moodle user data
    * @param keyed array $data
    * @param int $user_id - this should be the moodle user id
    * @return keyed array
    */
    public function populate_from_usertable( $data , $user_id ){
        global $DB;
        $user = $DB->get_record( 'user' , array( 'id' => $user_id ) );
        //keys match keys in $this->fields which could be substituted from mdl_user
        //values are corresponding fieldnames in mdl_user
        $fieldlist = array(
            'email' => 'email',
            'phone' => 'phone1',
            'mobile' => 'phone2',
            'addressone' => 'address',
            'addresstwo' => 'city',
            'addressthree' => 'country'
        );
        foreach( $fieldlist as $fieldname => $userfieldname ){
            if( empty( $this->fields[ $fieldname ] ) && !empty( $user->$userfieldname ) ){
                $this->fields[ $fieldname ] = $userfieldname;
                $data[ $this->fields[ $fieldname ] ] = $user->$userfieldname;
            }
        }
        $data[ 'addressone' ] = 'a house';
        return $data;
    }
 	
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_profile_contact&plugintype=mis">'.get_string('ilp_mis_learner_contact_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_learner_contact', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_table',get_string('ilp_mis_learner_contact_table', 'block_ilp'),get_string('ilp_mis_learner_contact_tabledesc', 'block_ilp'),'');

          $this->config_text_element($mform,'mis_learner_contact_prelimcalls',get_string('ilp_mis_learner_contact_prelimcalls', 'block_ilp'),get_string('ilp_mis_learner_contact_prelimcallsdesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_studentid',get_string('ilp_mis_learner_contact_studentid', 'block_ilp'),get_string('ilp_mis_learner_contact_studentiddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_enrolmentdate',get_string('ilp_mis_learner_contact_enrolmentdate', 'block_ilp'),get_string('ilp_mis_learner_contact_enrolmentdatedesc', 'block_ilp'),'enrolmentDate');

 	 	$this->config_text_element($mform,'mis_learner_contact_dob',get_string('ilp_mis_learner_contact_dob', 'block_ilp'),get_string('ilp_mis_learner_contact_dobdesc', 'block_ilp'),'dob');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_email',get_string('ilp_mis_learner_contact_email', 'block_ilp'),get_string('ilp_mis_learner_contact_emaildesc', 'block_ilp'),'Email');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_phone',get_string('ilp_mis_learner_contact_phone', 'block_ilp'),get_string('ilp_mis_learner_contact_phonedesc', 'block_ilp'),'Phone');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_mobile',get_string('ilp_mis_learner_contact_mobile', 'block_ilp'),get_string('ilp_mis_learner_contact_mobiledesc', 'block_ilp'),'Mobile');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_emercontact',get_string('ilp_mis_learner_contact_emercontact', 'block_ilp'),get_string('ilp_mis_learner_contact_emercontactdesc', 'block_ilp'),'emergencyContact');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_emernumber',get_string('ilp_mis_learner_contact_emernumber', 'block_ilp'),get_string('ilp_mis_learner_contact_emernumberdesc', 'block_ilp'),'emergencyNumber');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_addressone',get_string('ilp_mis_learner_contact_addressone', 'block_ilp'),get_string('ilp_mis_learner_contact_addressonedesc', 'block_ilp'),'Address1');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_addresstwo',get_string('ilp_mis_learner_contact_addresstwo', 'block_ilp'),get_string('ilp_mis_learner_contact_addresstwodesc', 'block_ilp'),'Address2');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_addressthree',get_string('ilp_mis_learner_contact_addressthree', 'block_ilp'),get_string('ilp_mis_learner_contact_addressthreedesc', 'block_ilp'),'Address3');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_addressfour',get_string('ilp_mis_learner_contact_addressfour', 'block_ilp'),get_string('ilp_mis_learner_contact_addressfourdesc', 'block_ilp'),'Address4');
 	 	
 	 	$this->config_text_element($mform,'mis_learner_contact_postcode',get_string('ilp_mis_learner_contact_postcode', 'block_ilp'),get_string('ilp_mis_learner_contact_postcodedesc', 'block_ilp'),'postcode');

        $this->config_text_element($mform,'mis_learner_contact_tutor',get_string('ilp_mis_learner_contact_tutor', 'block_ilp'),get_string('ilp_mis_learner_contact_tutordesc', 'block_ilp'),'tutor');

 	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_learner_contact_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	
 	 	
 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_learner_contact_tabletype',$options,get_string('ilp_mis_learner_contact_tabletype', 'block_ilp'),get_string('ilp_mis_learner_contact_tabletypedesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_learner_profile_contact_pluginstatus',$options,get_string('ilp_mis_learner_profile_contact_pluginstatus', 'block_ilp'),get_string('ilp_mis_learner_profile_contact_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }

    
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {

        $string['ilp_mis_learner_contact_pluginname']						= 'Contact Details';

        $string['ilp_mis_learner_contact_prelimcalls']						= 'Preliminary db calls';

        $string['ilp_mis_learner_contact_pluginnamesettings']				= 'Contact Details Configuration';
        
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

        $string['ilp_mis_learner_contact_tutor']              = 'Tutor field';
        $string['ilp_mis_learner_contact_tutordesc']            = 'The field that holds tutor data';

        $string['ilp_mis_learner_contact_tabletype']						= 'Table type';
        $string['ilp_mis_learner_contact_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';        
        
        $string['ilp_mis_learner_profile_contact_pluginstatus']				= 'Status';
        $string['ilp_mis_learner_profile_contact_pluginstatusdesc']			= 'Is the block enabled or disabled';
        
        
        $string['ilp_mis_learner_profile_contact_disp_personal']				= 'Personal';
        $string['ilp_mis_learner_profile_contact_disp_contact']					= 'Contact';
        $string['ilp_mis_learner_profile_contact_disp_address']					= 'Address';
        $string['ilp_mis_learner_profile_contact_disp_studentid']				= 'Student ID';
        $string['ilp_mis_learner_profile_contact_disp_enrolmentdate']			= 'Enrolment Date';
        $string['ilp_mis_learner_profile_contact_disp_dob']						= 'Date of birth';
        $string['ilp_mis_learner_profile_contact_disp_email']					= 'Email';
        $string['ilp_mis_learner_profile_contact_disp_phone']					= 'Phone';
        $string['ilp_mis_learner_profile_contact_disp_mobile']					= 'Mobile';
        $string['ilp_mis_learner_profile_contact_disp_postcode']				= 'Postcode';
        $string['ilp_mis_learner_profile_contact_disp_emercontact']				= 'Emergency Contact';
        $string['ilp_mis_learner_profile_contact_disp_emernumber']				= 'Emergency Number';

         $string['ilp_mis_learner_contact_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';
         $string['ilp_mis_learner_profile_contact_tab_name']					= 'Contact Details';
        $string['ilp_mis_learner_profile_contact_disp_tutor']                   = 'Tutor';
        $string['ilp_mis_learner_profile_contact_prelimcalls']					= 'Preliminary db calls';

         return $string;
    }

    
    public static function plugin_type()	{
    	return 'learnerprofile';
    }
 	
    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors 
     * 
     */
    function tab_name() {
        return get_string('ilp_mis_learner_profile_contact_tab_name','block_ilp');
    }

    /*
    * different MIS systems will have different ways of representing dates
    * @param mixed $value
    * @return string
    */
    function interpret_date( $value , $format='d-m-Y' ){
        if( is_string( $value ) ){
        	
        	$value	=	str_replace("/", "-", $value);
            //use generic method for turning strings to numerical dates
            $unixtime = strtotime( $value );
        }
        else{
            //assume we have a unix time already
            $unixtime = $value;
        }
        return date( $format, $unixtime );
    }


}

?>
