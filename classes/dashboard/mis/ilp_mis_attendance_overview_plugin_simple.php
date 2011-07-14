<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_overview_plugin_simple extends ilp_mis_attendance_plugin{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        
        //find out whether a table or stored procedure is used in queries 
        $this->tabletype	=	get_config('block_ilp','mis_plugin_simple_tabletype');
        $this->data			=	false;
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
        global $CFG;
        require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');

        // set up the flexible table for displaying the portfolios

        //instantiate the ilp_ajax_table class
        $flextable = new ilp_ajax_table( 'attendance_plugin_simple' );

        //create headers
        $headers = array( '' , '' );
        //create columns
        $columns = array( 'metric' , 'score' );
        
        //define the columns in the tables
        $flextable->define_columns($columns);
        
        //define the headers in the tables
        $flextable->define_headers($headers);
        
        //we do not need the intialbars
        $flextable->initialbars(false);
        
        //setup the flextable
        $flextable->setup();
        
        //add the row to table
        foreach( $this->data as $row ){
            $data = array();
            $data[ 'metric' ] = $row[ 0 ];
            $data[ 'score' ] = $row[ 1 ];
            $flextable->add_data_keyed( $data );
        }
        
        //buffer out as flextable sends its data straight to the screen we dont want this  
		ob_start();
		
		//call the html file for the plugin which has the flextable print statement
		require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/mis/ilp_mis_attendance_overview_plugin_simple.html');
		
		$pluginoutput = ob_get_contents();
        ob_end_clean();
        
        //echo the output
        echo $pluginoutput;
    }

    protected function get_links(){
        $output = '';
        $link_list = array(
            '?display_type=class' => 'by class',
            '?display_type=register' => 'by week'
        );
        foreach( $link_list as $url => $label ){
            $output .= "
                <a href=\"$url\">$label</a>
            ";
        }
        return $output;
    }

    public function set_data( $student_id ){
    	//get the plugins configuration and pass to variables 
        $tablename 			= get_config('block_ilp','mis_plugin_simple_studenttable'); //$this->params[ 'student_table' ];
        if (!empty($tablename)) {
	        $keyfield 			= get_config('block_ilp','mis_plugin_simple_studentid');
	        $punctuality_field  = get_config('block_ilp','mis_plugin_simple_punctuality');
	        $attendance_field 	= get_config('block_ilp','mis_plugin_simple_attendance');
	        
	        $data = array_shift( $this->dbquery( $tablename, array( $keyfield => array( '=' => $student_id ) ), "$attendance_field, $punctuality_field" ) ); 

	        if (!empty($data)) {
	        	$this->data	=	 array(
		        	    			array( get_string('ilp_mis_attendance_plugin_simple_attendance','block_ilp') , $data[ 'attendance' ] ),
		            				array( get_string('ilp_mis_attendance_plugin_simple_punctuality','block_ilp') , $data[ 'punctuality' ] )
		        				 );
	        } 
        }
    }
    
    protected function get_simple_summary( $student_id ){
    	
    }
    
    
    public function plugin_type(){
        return 'overview';
    }
    
    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	
    	$settingsheader 	= new admin_setting_heading('block_ilp/mis_attendance_plugin_simple', get_string('ilp_mis_attendance_plugin_simple_pluginname', 'block_ilp'), '');
    	$settings->add($settingsheader);
    	
    	$studenttable		=	new admin_setting_configtext('block_ilp/mis_plugin_simple_studenttable',get_string( 'ilp_mis_attendance_plugin_simple_studenttable', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_simple_studenttabledesc', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($studenttable);
		
		$keyfield			=	new admin_setting_configtext('block_ilp/mis_plugin_simple_studentid',get_string( 'ilp_mis_attendance_plugin_simple_studentid', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_simple_studentiddesc', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($keyfield);
		
		$punchfield			=	new admin_setting_configtext('block_ilp/mis_plugin_simple_punctuality',get_string( 'ilp_mis_attendance_plugin_simple_punctuality', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_simple_punctualitydesc', 'block_ilp' ),'punctuality',PARAM_RAW);
		$settings->add($punchfield);
		
		$attendfield			=	new admin_setting_configtext('block_ilp/mis_plugin_simple_attendance',get_string( 'ilp_mis_attendance_plugin_simple_attendance', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_simple_attendancedesc', 'block_ilp' ),'attendance',PARAM_RAW);
		$settings->add($attendfield);
		
		$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
    	
		$pluginstatus			= 	new admin_setting_configselect('block_ilp/mis_plugin_simple_tabletype',get_string('ilp_mis_attendance_plugin_simple_tabletype','block_ilp'),get_string('ilp_mis_attendance_plugin_simple_tabletypedesc','block_ilp'), 0, $options);
		$settings->add( $pluginstatus );
		
		$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
    	
		$pluginstatus			= 	new admin_setting_configselect('block_ilp/ilp_mis_attendance_plugin_simple_pluginstatus',get_string('ilp_mis_attendance_plugin_simple_pluginstatus','block_ilp'),get_string('ilp_mis_attendance_plugin_simple_pluginstatusdesc','block_ilp'), 0, $options);
		$settings->add( $pluginstatus );
 	 }
    
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 function language_strings(&$string) {
	 	
        $string['ilp_mis_attendance_plugin_simple_attendance']				= 'attendance';
        $string['ilp_mis_attendance_plugin_simple_punctuality']				= 'punctuality';
        $string['ilp_mis_attendance_plugin_simple_pluginname']				= 'Simple Overview';
        
        $string['ilp_mis_attendance_plugin_simple_studenttable']			= 'MIS table';
        $string['ilp_mis_attendance_plugin_simple_studenttabledesc']		= 'The table in the MIS where the data for this plugin will be retrieved from';
        
        $string['ilp_mis_attendance_plugin_simple_studentid']				= 'Student ID field';
        $string['ilp_mis_attendance_plugin_simple_studentiddesc']				= 'The field that will be used to find the student';
        
        $string['ilp_mis_attendance_plugin_simple_punctuality']				= 'Punctuality';
        $string['ilp_mis_attendance_plugin_simple_punctualitydesc']			= 'The field that holds punctuality data';
        
        $string['ilp_mis_attendance_plugin_simple_attendance']				= 'Attendance';
        $string['ilp_mis_attendance_plugin_simple_attendancedesc']			= 'The field that holds attendance data';
        
        $string['ilp_mis_attendance_plugin_simple_tabletype']				= 'Table type';
        $string['ilp_mis_attendance_plugin_simple_tabletypedesc']			= 'Does this plugin connect to a table or stored procedure';        
        
        $string['ilp_mis_attendance_plugin_simple_pluginstatus']			= 'Status';
        $string['ilp_mis_attendance_plugin_simple_pluginstatusdesc']			= 'Is the block enabled or disabled';
        
        return $string;
    }
}
