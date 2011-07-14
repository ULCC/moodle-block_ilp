<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_overview_plugin_term extends ilp_mis_attendance_plugin{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        $this->params[ 'stored_procedure' ] = false;
        $this->tabletype	=	get_config('block_ilp','mis_plugin_term_studenttable');
    }

    
    protected function set_params( $params ){
        parent::set_params( $params );
        $this->params[ 'termstudent_table' ] = get_config('block_ilp','mis_attendance_plugin_term_studenttable');
        $this->params[ 'termstudent_table_student_id_field' ] = get_config('block_ilp', 'mis_attendance_plugin_term_studentid' );
        $this->params[ 'termstudent_table_term_id_field' ] = get_config('block_ilp', 'mis_plugin_term_termid' );
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
        global $CFG;
        require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');


        // set up the flexible table for displaying the portfolios
        $flextable = new ilp_ajax_table( 'attendance_plugin_simple' );
        $headers = array(
                '',
                'Overall',
                'Autumn',
                'Spring',
                'Summer'
         );
        $columns = array(
            'label', 'overall', 'autumn', 'spring', 'summer'
        );
        $flextable->define_columns($columns);
        $flextable->define_headers($headers);
        $flextable->initialbars(false);
        $flextable->setup();
        foreach( $this->data as $row ){
            $data = array();
            $data[ 'label' ] = $row[ 0 ];
            $data[ 'overall' ] = $row[ 1 ];
            $data[ 'autumn' ] = $row[ 2 ];
            $data[ 'spring' ] = $row[ 3 ];
            $data[ 'summer' ] = $row[ 4 ];
            $flextable->add_data_keyed( $data );
        }
		ob_start();
        $flextable->print_html();
		$pluginoutput = ob_get_contents();
        ob_end_clean();
        
        echo $pluginoutput;

/*
        if( is_string( $this->data ) ){
            echo $this->data;
        }
        elseif( is_array( $this->data ) ){
		    echo self::test_entable( $this->data );
        }
*/
    }

    /*
    * take raw data from mis db connection and rearrange it into a sequence of rows for displaying in a table
    * @param int $student_id
    */
    public function set_data( $student_id ){
	        $data = $this->get_summary_by_term( $student_id );
            //we now have the raw data for each term: now we have to calculate the scores, and make a readable table
            $tablerowlist = array();
            $blankcell = '&nbsp;';
            $toprow = array(
                $blankcell=>false,
                'Overall'=>3,
                'Autumn'=>0,
                'Spring'=>1,
                'Summer'=>2
            );
            //$tablerowlist[] = array_keys( $toprow );
            foreach( array( 'attendance' , 'punctuality' ) as $metric ){
                $outrow = array( $metric );
                foreach( $toprow as $termname=>$key ){
                    if( false !== $key ){
                        $inrow = $data[ $key ];
                        $outrow[] = $this->calcScore( $inrow, $metric );
                    }
                }
                $tablerowlist[] = $outrow;
            }
            $this->data = $tablerowlist;
    }

    protected function get_summary_by_term( $student_id, $term_id=null ){
        $table = $this->params[ 'termstudent_table' ];
        $student_idfield = $this->params[ 'termstudent_table_student_id_field' ];
        $term_idfield = $this->params[ 'termstudent_table_term_id_field' ];
        
        $conditions = array( $student_idfield => array( '=' => $student_id ) );
        if( $term_id ){
            $conditions[ $term_idfield ] = array( '=' => $term_id );
        }
        $data = $this->dbquery( $table, $conditions, '*', array( 'sort' => "$term_idfield" ) );
        //we now have terms 1 t0 3, but we need to calculate the totals
        $overall = array(
            "studentID" => $student_id,
            "term" => "overall",
            "marksTotal" => 0,
            "marksPresent" => 0,
            "marksAbsent" => 0,
            "marksAuthAbsent" => 0,
            "marksLate" => 0
        );
        foreach( $data as $row ){
            foreach( array( 'marksTotal', 'marksPresent', 'marksAbsent', 'marksAuthAbsent', 'marksLate' ) as $key ){
                $overall[ $key ] += $row[ $key ];
            }
        }
        $data[] = $overall;
        return $data;
    }

    /*
    * get all details for a particular student in a keyed array
    * @param int $student_id
    * @return array of $key=>$value
    */
    protected function get_student_data( $student_id ){
        $table = $this->params[ 'student_table' ];
        $idfield = $this->params[ 'student_unique_key' ];
        $conditions = array( $idfield => $student_id );
        return $this->dbquery( $table, $conditions );
    }
	
    public function plugin_type(){
        return 'detail';
    }
    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	$settingsheader 	= new admin_setting_heading('block_ilp/mis_attendance_plugin_term', get_string('ilp_mis_attendance_plugin_term_pluginname', 'block_ilp'), '');
    	$settings->add($settingsheader);
    	
    	$termstudenttable		=	new admin_setting_configtext('block_ilp/mis_attendance_plugin_term_studenttable',get_string( 'ilp_mis_attendance_plugin_term_studenttable', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_simple_studenttabledesc', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($termstudenttable);
		
		$studentkeyfield			=	new admin_setting_configtext('block_ilp/mis_attendance_plugin_term_studentid',get_string( 'ilp_mis_attendance_plugin_term_studentid', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_term_studentiddesc', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($studentkeyfield);

		$termkeyfield			=	new admin_setting_configtext('block_ilp/mis_plugin_term_termid',get_string( 'ilp_mis_attendance_plugin_term_termid', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_term_termiddesc', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($termkeyfield);

		$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
		$pluginstatus			= 	new admin_setting_configselect('block_ilp/mis_plugin_term_tabletype',get_string('ilp_mis_attendance_plugin_term_tabletype','block_ilp'),get_string('ilp_mis_attendance_plugin_term_tabletypedesc','block_ilp'), 0, $options);
		$settings->add( $pluginstatus );
		
    }
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	function language_strings(&$string) {
        $string['ilp_mis_attendance_plugin_term_pluginname']				= 'Term Overview';
        $string[ 'ilp_mis_attendance_plugin_term_studenttable' ]            = 'Student-term Table';
        $string[ 'ilp_mis_attendance_plugin_term_studenttabledesc' ]        = 'MIS table containing attendance data by term by student';
        $string['ilp_mis_attendance_plugin_term_studentid']				    = 'Student-term student id field';
        $string['ilp_mis_attendance_plugin_term_studentiddesc']				= 'The field that will be used to find the student';
        $string['ilp_mis_attendance_plugin_term_termid']				    = 'Student-term term id field';
        $string['ilp_mis_attendance_plugin_term_termiddesc']			    = 'The field that will identify the term';
        $string['ilp_mis_attendance_plugin_term_tabletype']                 = 'Table Type';
        $string['ilp_mis_attendance_plugin_term_tabletypedesc']             = 'Table or stored procedure';
    }
}
