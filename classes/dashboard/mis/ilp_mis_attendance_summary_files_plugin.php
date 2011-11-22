<?php

/**
 * Creates an entry for an report 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk, Greg Pasciak
 * @author Greg Pasciak
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

// instantiate the db
$dbc = new ilp_db();
$plpuser	=	$dbc->get_user_by_id($user_id);

class ilp_mis_attendance_summary_files_plugin extends ilp_mis_attendance_plugin	{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        
        //find out whether a table or stored procedure is used in queries 
        $this->tabletype	=	get_config('block_ilp','mis_plugin_simple_tabletype');
        $this->data			=	false;
          
    }

    
    public function display(){
        global $CFG;
        
		
        if (!empty($this->data)) {
	        // set up the flexible table for displaying the data
	
	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'attendance_plugin_simple',true ,'ilp_mis_attendance_summary_files_plugin'); 
	
	        //create headers
	        $headers = array();
	        $headers[] = 'Report Type';
	        $headers[] = 'Report File';

	        //create columns
	        $columns = array();
	        $columns[] =  'reportType';
	        $columns[] =  'reportFile';
	        
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
	            $data[ 'reportType' ]  = $row[ 0 ] ;
	            $data[ 'reportFile' ]  = $row[ 1 ] ;
	            $flextable->add_data_keyed( $data );
	        }
	        
	        //buffer out as flextable sends its data straight to the screen we dont want this  
			ob_start();
			
			//call the html file for the plugin which has the flextable print statement
			require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/mis/ilp_mis_attendance_summary_files_plugin.html');
			
			$pluginoutput = ob_get_contents();
	        ob_end_clean();
	        
	        //echo the output
	        return $pluginoutput;
        } else {
            if( $msg = get_string('nodataornoconfig', 'block_ilp') ){
                echo '<div id="plugin_nodata">' . $msg . '</div>';
            }
    	}
    }

	/**
     * Retrieves user reports from the "/ilp_files" directory
     * and sets up data in the table
     */
    
    public function set_data( ){
    	global $CFG, $USER, $plpuser;
    	
    	$ilp_filesDir = str_replace('/docroot', "", $CFG->dirroot)."/ilp_files/";
    	$ilp_filesDir = str_replace('\docroot', "", $ilp_filesDir);  // on Win server
    	$dirNames = array_diff(scandir($ilp_filesDir), array('.', '..'));
    	
    	foreach ($dirNames as $dir) {
			
    		//create flexible table data
    		if ($dir == 'Registers') 
    			$fileName = $plpuser->username.'_ilp_Register.pdf';
    		elseif ($dir == 'Timetables') 
    			$fileName = $plpuser->username.'_ilp_Timetable.pdf';
    		else 	
      			$fileName = $plpuser->username.'_ilp_'.$dir.'.pdf';
     			    		
      		if (file_exists($ilp_filesDir.$dir.'/'.$fileName))
    			$link =	'<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/download_reports.php?'.$dir.'/'.$fileName.'">'.$fileName.''.'</a>'.'<img src="'.$CFG->wwwroot.'/pix/f/pdf.gif"/>';
    		else 
    			$link = 'No Report File found';   		
    		
    		$this->data[]	=	 array( $dir, $link);   		
    	}    	
    }
       
    
    public function plugin_type(){
        return 'overview';
    }
    
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_summary_files_plugin&plugintype=mis">'.get_string('ilp_mis_attendance_summary_files_plugin_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_plugin_simple', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	 	
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_attendance_summary_files_plugin_pluginstatus',$options,get_string('ilp_mis_attendance_summary_files_plugin_pluginstatus', 'block_ilp'),get_string('ilp_mis_attendance_summary_files_plugin_pluginstatusdesc', 'block_ilp'),0);
	
 	 }
    
    
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 function language_strings(&$string) {
	 	
        $string['ilp_mis_attendance_summary_files_plugin_pluginname']				= 'Summary files';
        $string['ilp_mis_attendance_summary_files_plugin_pluginnamesettings']		= 'Deeside Attendance and Reports in Summary Files - Configuration';
        
        $string['ilp_mis_attendance_summary_files_plugin_pluginstatus']				= 'Status';
        $string['ilp_mis_attendance_summary_files_plugin_pluginstatusdesc']			= 'Is the block enabled or disabled';
        
        return $string;
    }
    
    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors 
     * 
     */
    function tab_name() {
    	return 'Simple Overview';
    }

    function getAttendance()
    {
        return '';

    }

    function getPunctuality()
    {
        return '';
    }


}
