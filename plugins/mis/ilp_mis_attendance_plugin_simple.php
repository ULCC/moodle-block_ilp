<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');

class ilp_mis_attendance_plugin_simple extends ilp_mis_attendance_plugin	{

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
        if (!empty($this->data)) {
	        // set up the flexible table for displaying the data

	        //buffer out as flextable sends its data straight to the screen we dont want this
                ob_start();

	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'attendance_plugin_simple',true ,'ilp_mis_attendance_plugin_simple');

                $flextable->set_attribute('class', 'flexible generaltable');
	        //create headers
	        $headers = array( get_string('ilp_mis_attendance_plugin_simple_attendance','block_ilp') , get_string('ilp_mis_attendance_plugin_simple_punctuality','block_ilp') );
	        //create columns
	        $columns = array( 'attendance' , 'punctuality' );

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
                $att = $this->percent_format( $row[ 0 ] );
                $punc = $this->percent_format( $row[ 1 ] );
	            $data[ 'attendance' ]  = $att;
	            $data[ 'punctuality' ] = $punc;
	            $flextable->add_data_keyed( $data );
	        }

                $flextable->finish_html();

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

    public function set_data( $student_id, $user_id=null ){
    	//get the plugins configuration and pass to variables
        $tablename 			= get_config('block_ilp','mis_plugin_simple_studenttable'); //$this->params[ 'student_table' ];
        if (!empty($tablename)) {
	        $keyfield 			= get_config('block_ilp','mis_plugin_simple_studentid');
	        $attendance_field 	= get_config('block_ilp','mis_plugin_simple_attendance');
	        $punctuality_field 	= get_config('block_ilp','mis_plugin_simple_punctuality');

	        //is the id a string or a int
    		$idtype	=	get_config('block_ilp','mis_plugin_course_idtype');
    		$student_id	=	(empty($idtype)) ? "'{$student_id}'" : $student_id;

            $prelimdbcalls   =    get_config('block_ilp','mis_plugin_simple_prelimcalls');

	        $querydata = $this->cached_dbquery( $tablename, array( $keyfield => array('=' => $student_id )), array($attendance_field, $punctuality_field), null, $prelimdbcalls );

	        $data = (is_array($querydata)) ? array_shift( $querydata ) : $querydata;

	        if (!empty($data)) {
	        	$this->data[]	=	 array( $data[ $attendance_field ] , $data[ $punctuality_field  ] );
	        }
        }
    }


    public static function plugin_type(){
        return 'overview';
    }

	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;

    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_simple&plugintype=mis">'.get_string('ilp_mis_attendance_plugin_simple_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_plugin_simple', '', $link));
 	 }

 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{

 	 	$this->config_text_element($mform,'mis_plugin_simple_studenttable',get_string('ilp_mis_attendance_plugin_simple_studenttable', 'block_ilp'),get_string('ilp_mis_attendance_plugin_simple_studenttabledesc', 'block_ilp'),'');

        $this->config_text_element($mform,'mis_plugin_simple_prelimcalls',get_string('ilp_mis_attendance_plugin_simple_prelimcalls', 'block_ilp'),get_string('ilp_mis_attendance_plugin_simple_prelimcallsdesc', 'block_ilp'),'');

 	 	$this->config_text_element($mform,'mis_plugin_simple_studentid',get_string('ilp_mis_attendance_plugin_simple_studentid', 'block_ilp'),get_string('ilp_mis_attendance_plugin_simple_studentiddesc', 'block_ilp'),'studentID');

 	 	$this->config_text_element($mform,'mis_plugin_simple_punctuality',get_string('ilp_mis_attendance_plugin_simple_punctuality', 'block_ilp'),get_string('ilp_mis_attendance_plugin_simple_punctualitydesc', 'block_ilp'),'punctuality');

 	 	$this->config_text_element($mform,'mis_plugin_simple_attendance',get_string('ilp_mis_attendance_plugin_simple_attendance', 'block_ilp'),get_string('ilp_mis_attendance_plugin_simple_attendancedesc', 'block_ilp'),'attendance');

        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_simple_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp')
    	);

 	 	$this->config_select_element($mform,'mis_plugin_simple_tabletype',$options,get_string('ilp_mis_attendance_plugin_simple_tabletype', 'block_ilp'),get_string('ilp_mis_attendance_plugin_simple_tabletypedesc', 'block_ilp'),1);

 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);

 	 	$this->config_select_element($mform,'ilp_mis_attendance_plugin_simple_pluginstatus',$options,get_string('ilp_mis_attendance_plugin_simple_pluginstatus', 'block_ilp'),get_string('ilp_mis_attendance_plugin_simple_pluginstatusdesc', 'block_ilp'),0);

 	 }


	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {

        $string['ilp_mis_attendance_plugin_simple_attendance']				= 'Attendance';
        $string['ilp_mis_attendance_plugin_simple_punctuality']				= 'Punctuality';
        $string['ilp_mis_attendance_plugin_simple_pluginname']				= 'Simple Overview';
        $string['ilp_mis_attendance_plugin_simple_pluginnamesettings']		= 'Simple Attendance Overview Configuration';

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

         $string['ilp_mis_attendance_plugin_simple_prelimcalls']						= 'Preliminary db calls';
         $string['ilp_mis_attendance_plugin_simple_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';


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
        return (!empty($this->data)) ? $this->percent_format( $this->data[0][0] ) : 0;
    }

    function getPunctuality()
    {
       return (!empty($this->data)) ? $this->percent_format( $this->data[0][1] ) : 0;
    }

    /**
     *
     * return the punctuality
     * @return int
     */
    public function get_student_punctuality()
    {
       if(isset($this->data[0][1]))
       {
          return $this->data[0][1];
       }
       return 0;
    }

    /**
     *
     * return the attendance
     * @return int
     */
    public function get_student_attendance()
    {
       if(isset($this->data[0][0]))
       {
          return $this->data[0][0];
       }
       return 0;
    }
}
