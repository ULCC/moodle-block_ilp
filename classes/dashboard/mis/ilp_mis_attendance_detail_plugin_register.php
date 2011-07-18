<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_detail_plugin_register extends ilp_mis_attendance_plugin{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
        global $CFG;
        require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');
        if( is_string( $this->data ) ){
            echo $this->data;
        }
        elseif( is_array( $this->data ) ){
		    echo self::test_entable( $this->data );
		    echo $this->flexentable( $this->data );
        }
    }

    public function set_data( $student_id, $term_id ){
        $this->data = $this->get_register_entries( $student_id , $term_id );
    }
    public function plugin_type(){
        return 'detail';
    }

    protected function flexentable( $data ){
        // set up the flexible table for displaying the portfolios
        $flextable = new ilp_ajax_table( 'attendance_plugin_simple' );
        $headers = array_shift( $data );
        $columns = $headers;
        $flextable->define_columns($columns);
        $flextable->define_headers($headers);
        $flextable->initialbars(false);
        $flextable->setup();

        foreach( $data as $row ){
            $tablerow = array();
            $i = 0;
            foreach( $columns as $col ){
                if( $i < count( $row ) ){
                    $tablerow[ $col ] = $row[ $i++ ];
                }
                else{
                    $tablerow[ $col ] = '';
                }
            }
            $flextable->add_data_keyed( $tablerow );
        }

		ob_start();
        $flextable->print_html();
		$pluginoutput = ob_get_contents();
        ob_end_clean();
        
        return $pluginoutput;
    }
    /*
    * get the weekly attendance data for a user and return an array for display in a table
    * $term_id would be 1 for autumn term, 2 for spring term or 3 for summer term
    * @param int student_di
    * @param int $term_id
    * @return array of arrays
    */
    public function get_register_entries( $student_id , $term_id=false ){
        $blankcell = '&nbsp;';
        //$data = array();
        $tablerowlist = array();    //this will build into a list of lists of display values - top row for table headers etc
        //$cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        $cal = new calendarfuncs();
        if( !$term_id ){    //term_id is 1-based, so 0 or false indicates no specific term
                            //default bahaviour is defined in this block - at the moment we set start and end to show the whole academic year
            $report_start = $this->params[ 'start_date' ];
            $report_end = $this->params[ 'end_date' ];
        }
        else{
            list( $report_start, $report_end ) = $this->params[ 'termdatelist' ][ $term_id ];
        }
        $weeklist = $cal->calc_sub_week_limits( $report_start, $report_end );
        $toprow = array(
            'Class',
            'Late',
            'Att',
            'Day',
            'Time'
        );       
        $weekrow = array_fill( 0, count( $toprow ) - 1 , '&nbsp;' );
        $weekrow[] = 'Week';
        foreach( $weeklist as $week ){
            $toprow[] = $cal->calc_weekno( $this->params[ 'week1' ], $week[ 0 ] );
            $weekrow[] = $cal->getreadabletime( $cal->getutime( $week[ 0 ] ), 'd/m' );
        }
        $weeknolist = $toprow;

        $tablerowlist[ 'headers' ] = $toprow;
        $tablerowlist[ 'weeks' ] = $weekrow;

        $courselist = $this->get_courselist( $student_id );
        $timefield = $this->params[ 'timefield_start' ];
        $attendance_data = array();     //will build into a list of stats for each course-weekday
        $table = $this->params[ 'table' ];
        foreach( $courselist as $course ){
            foreach( $weeklist as $week ){
                if( $rowlist = $this->get_attendance_details( $table, $student_id, $course[ 'course_id' ], array(), false, $week[ 0 ], $week[ 1 ] ) ){
                    //var_crap( $cal->calc_day_of_week( $row[ $timefield ] ) );
                    foreach( $rowlist as $row ){
                        $weekno = $cal->calc_weekno( $this->params[ 'week1' ], $week[ 0 ] );
	                    $row_id = $course[ 'course_id' ] . " " . $course[ 'course_title' ] . " " . $row[ 'dayname' ];
                        if( !in_array( $row_id, array_keys( $tablerowlist ) ) ){
                            //new row
	                        $row_visible_id = $course[ 'course_id' ] . " " . $course[ 'course_title' ]; 
                            $tablerowlist[ $row_id ] = array( $row_visible_id ); 
		                    $tablerowlist[ $row_id ][] = false;     //late
		                    $tablerowlist[ $row_id ][] = false;     //att
		                    $tablerowlist[ $row_id ][] = $cal->getreadabletime( $cal->getutime( $row[ 'dayname' ] ) , 'D' ); 
		                    $tablerowlist[ $row_id ][] = $cal->get_time( $cal->getutime( $row[ 'clocktime' ] ) ); 

                            $attendance_data[ $row_id ] = array(
                                'possible' => 0,
                                'present' => 0,
                                'late' => 0,
                                'absent' => 0
                            );
                        }
                        else{
                        }
                        $col = count( $tablerowlist[ $row_id ] );
                        //match table column to week no
                        while( $weeknolist[ $col ] < $weekno ){
                            $col++;
                            $tablerowlist[ $row_id ][] = $blankcell;
                        }
                        //$tablerowlist[ $row_id ][] = $this->decide_attendance_symbol( $row[ 'attendance_code' ] );
                        $attendance_data[ $row_id ] = $this->modify_attendance_data( $attendance_data[ $row_id ], $row[ 'attendance_code' ] );
                        $tablerowlist[ $row_id ][] = $row[ 'attendance_code' ];

                    }
                }
            }
        }
        foreach( $tablerowlist as $row_id=>$row ){
            //calc late and attendence percentages for each row
            if( in_array( $row_id, array_keys( $attendance_data ) ) ){
                $attendance = $attendance_data[ $row_id ];
                //$tablerowlist[ $row_id ][ 1 ] = $this->format_percentage( $attendance[ 'late' ] / $attendance[ 'present' ] );
                //$tablerowlist[ $row_id ][ 2 ] = $this->format_percentage( $attendance[ 'present' ] / $attendance[ 'possible' ] );
                $tablerowlist[ $row_id ][ 1 ] = $this->calc_attendance_metric( $attendance , 'late' );
                $tablerowlist[ $row_id ][ 2 ] =  $this->calc_attendance_metric( $attendance , 'attendance' );

                //pad the row
                while( count( $tablerowlist[ $row_id ] ) < count( $toprow ) ){
                    $tablerowlist[ $row_id ][] = $blankcell;
                } 
            }
        }
        return $tablerowlist;
        //return $data;
    }

    protected function calc_attendance_metric( $attendance_data , $metric ){
        $denominatorkey = 0;
        if( 'late' == $metric ){
            $numeratorkey = 'late';
            $denominatorkey = 'present';
        }
        elseif( 'attendance' == $metric ){
            $numeratorkey = 'present';
            $denominatorkey = 'possible';
        }
        if( $denominator = $attendance_data[ $denominatorkey ] ){
            $numerator = $attendance_data[ $numeratorkey ];
            return $this->format_percentage( $numerator / $denominator );
        }
        return get_string( 'not_applicable' , 'block_ilp' );
    }

    protected function modify_attendance_data( $attendance_data, $code ){
        $attendance_data[ 'possible' ]++;
        if( in_array( $code, $this->params[ 'late_code_list' ] ) ){
            $attendance_data[ 'late' ]++;
        }
        if( in_array( $code, $this->params[ 'present_code_list' ] ) ){
            $attendance_data[ 'present' ]++;
        }
        if( in_array( $code, $this->params[ 'absent_code_list' ] ) ){
            $attendance_data[ 'absent' ]++;
        }
        return $attendance_data;
    }

    protected function decide_attendance_symbol( $code ){
        if( in_array( $code, $this->params[ 'late_code_list' ] ) ){
            return 'L';
        }
        elseif( in_array( $code, $this->params[ 'present_code_list' ] ) ){
            return '/';
        }
        elseif( in_array( $code, $this->params[ 'absent_code_list' ] ) ){
            return '#';
        }
        else{
            return $code;
        }
    }

	function language_strings(&$string) {
        $string[ 'ilp_mis_attendance_plugin_register_pluginname' ] =       'Detail Report by Register';
        $string[ 'ilp_mis_attendance_plugin_register_table' ]      =       'Table';
        $string[ 'ilp_mis_attendance_plugin_register_tabledesc' ]  =       'Table containing attendance register entries';
        $string[ 'ilp_mis_attendance_plugin_register_week1' ]      =       'Week 1 start date (yyyy-mm-dd)';
        $string[ 'ilp_mis_attendance_plugin_register_timefield' ]  =       'Register table datetime fieldname';
        $string[ 'ilp_mis_attendance_plugin_register_latecodes' ]  =       'Attendance codes late (comma separated)';
        $string[ 'ilp_mis_attendance_plugin_register_presentcodes' ]=      'Attendance codes present (comma separated)';
        $string[ 'ilp_mis_attendance_plugin_register_absentcodes' ]=       'Attendance codes absent (comma separated)';
        $string[ 'ilp_mis_attendance_plugin_register_startdate' ]  =       'First date of academic year (yyyy-mm-dd)';
        $string[ 'ilp_mis_attendance_plugin_register_enddate' ]    =       'Last date of academic year (yyyy-mm-dd)';

        $string[ 'ilp_mis_plugin_register_studentid_field' ]        =       'Student id field';
        $string[ 'ilp_mis_plugin_register_courseid_field' ]        =       'Course id field';
        $string[ 'ilp_mis_plugin_register_courselabel_field' ]        =       'Course label field';
        $string[ 'ilp_mis_plugin_register_attendancecode_field' ]   =       'Attendance code field';
        $string[ 'ilp_mis_plugin_register_lectureid' ]              =       'Lecture id field';
        $string[ 'ilp_mis_plugin_register_timefieldstart' ]              =       'Field with lecture start time';
        $string[ 'ilp_mis_plugin_register_timefieldend' ]              =       'Field with lecture end time';
    }
    public function config_settings(&$settings)	{
    	$settingsheader 	= new admin_setting_heading('block_ilp/mis_attendance_plugin_register', get_string('ilp_mis_attendance_plugin_register_pluginname', 'block_ilp'), '');
    	$settings->add($settingsheader);
    	
    	$registertable		=	new admin_setting_configtext('block_ilp/mis_plugin_register_table',get_string( 'ilp_mis_attendance_plugin_register_table', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_register_tabledesc', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($registertable);
    	
    	$week1		=	new admin_setting_configtext('block_ilp/mis_plugin_register_week1',get_string( 'ilp_mis_attendance_plugin_register_week1', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_register_week1', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($week1);
    	
    	$timefield		=	new admin_setting_configtext('block_ilp/mis_plugin_register_timefield',get_string( 'ilp_mis_attendance_plugin_register_timefield', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_register_timefield', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($timefield);

    	$startdate		=	new admin_setting_configtext('block_ilp/mis_plugin_register_startdate',get_string( 'ilp_mis_attendance_plugin_register_startdate', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_register_startdate', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($startdate);

    	$enddate		=	new admin_setting_configtext('block_ilp/mis_plugin_register_enddate',get_string( 'ilp_mis_attendance_plugin_register_enddate', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_register_enddate', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($enddate);

        $latecodes  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_latecodes' , get_string( 'ilp_mis_attendance_plugin_register_latecodes' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_register_latecodes' , 'block_ilp' ), PARAM_RAW );
		$settings->add($latecodes);

        $presentcodes  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_presentcodes' , get_string( 'ilp_mis_attendance_plugin_register_presentcodes' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_register_presentcodes' , 'block_ilp' ), PARAM_RAW );
		$settings->add($presentcodes);

        $absentcodes  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_absentcodes' , get_string( 'ilp_mis_attendance_plugin_register_absentcodes' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_register_absentcodes' , 'block_ilp' ), PARAM_RAW );
		$settings->add($absentcodes);
///////////////////////////////////////////////////////////////
        $lectureid  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_lectureid' , get_string( 'ilp_mis_plugin_register_lectureid' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_lectureid' , 'block_ilp' ), PARAM_RAW );
		$settings->add($lectureid);

        $studentid  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_studentid_field' , get_string( 'ilp_mis_plugin_register_studentid_field' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_studentid_field' , 'block_ilp' ), PARAM_RAW );
		$settings->add($studentid);

        $courseid  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_courseid_field' , get_string( 'ilp_mis_plugin_register_courseid_field' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_courseid_field' , 'block_ilp' ), PARAM_RAW );
		$settings->add($courseid);

        $courselabel  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_courselabel_field' , get_string( 'ilp_mis_plugin_register_courselabel_field' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_courselabel_field' , 'block_ilp' ), PARAM_RAW );
		$settings->add($courselabel);

        $attendancecode  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_attendancecode_field' , get_string( 'ilp_mis_plugin_register_attendancecode_field' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_attendancecode_field' , 'block_ilp' ), PARAM_RAW );
		$settings->add($attendancecode);

        $timefieldstart  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_timefieldstart' , get_string( 'ilp_mis_plugin_register_timefieldstart' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_timefieldstart' , 'block_ilp' ), PARAM_RAW );
		$settings->add($timefieldstart);

        $timefieldend  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_timefieldend' , get_string( 'ilp_mis_plugin_register_timefieldend' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_timefieldend' , 'block_ilp' ), PARAM_RAW );
		$settings->add($timefieldend);


/*
        $codefield  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_attendancecode_field' , get_string( 'ilp_mis_plugin_register_attendancecode_field' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_attendancecode_field' , 'block_ilp' ), PARAM_RAW );
		$settings->add($codefield);

        $studentid  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_studentid_field' , get_string( 'ilp_mis_plugin_register_studentid_field' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_studentid_field' , 'block_ilp' ), PARAM_RAW );
		$settings->add($studentid);

        $courseid  =   new admin_setting_configtext( 'block_ilp/mis_plugin_register_courseid_field' , get_string( 'ilp_mis_plugin_register_courseid_field' , 'block_ilp' ), get_string( 'ilp_mis_plugin_register_courseid_field' , 'block_ilp' ), PARAM_RAW );
		$settings->add($courseid);
*/
    }

    protected function set_params( $params ){
        parent::set_params( $params );
/*
		$this->params[ 'termdatelist' ]
*/
        $this->params[ 'table' ] = get_config( 'block_ilp' , 'mis_plugin_register_table' );
		$this->params[ 'start_date' ] = get_config( 'block_ilp' , 'mis_plugin_register_startdate' );
		$this->params[ 'end_date' ] = get_config( 'block_ilp' , 'mis_plugin_register_enddate' );
		$this->params[ 'late_code_list' ] = explode( ',' , get_config( 'block_ilp', 'mis_plugin_register_late_codes' ) );
		$this->params[ 'present_code_list' ] = explode( ',' , get_config( 'block_ilp', 'mis_plugin_register_presentcodes'  ) );
		$this->params[ 'absent_code_list' ] = explode( ',' , get_config( 'block_ilp', 'mis_plugin_register_absentcodes'  ) );

        $this->params[ 'attendance_view' ] = get_config( 'block_ilp' , 'mis_plugin_register_table' );
		$this->params[ 'week1' ] = get_config( 'block_ilp', 'mis_plugin_register_week1' );
		$this->params[ 'timefield_start' ] = get_config( 'block_ilp', 'mis_plugin_register_timefield' );


        $this->params[ 'studentlecture_attendance_id' ] = get_config( 'block_ilp', 'mis_plugin_register_lectureid' );
        $this->params[ 'student_id_field' ] = get_config( 'block_ilp', 'mis_plugin_register_studentid_field' );
        $this->params[ 'course_id_field' ] = get_config( 'block_ilp', 'mis_plugin_register_courseid_field' );
        $this->params[ 'course_label_field' ] = get_config( 'block_ilp', 'mis_plugin_register_courselabel_field' );
        $this->params[ 'code_field' ] = get_config( 'block_ilp', 'mis_plugin_register_attendancecode_field' );
        $this->params[ 'timefield_start' ] = get_config( 'block_ilp', 'mis_plugin_register_timefieldstart' );
        $this->params[ 'timefield_end' ] = get_config( 'block_ilp', 'mis_plugin_register_timefieldend' );
        $this->params[ 'extra_numeric_fieldlist' ] = array( 'P', 'A', 'U', 'L' );
        $this->params[ 'extra_fieldlist' ] = array();
        $this->params[ 'termdatelist' ] = array(
			explode( ',', get_config('block_ilp','mis_plugin_class_term1startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term2startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term3startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term4startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term5startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term6startend') )
        );
/*
        $this->params[ 'studentlecture_attendance_id' ] = get_config( 'block_ilp', 'mis_plugin_register_table' );
        $this->params[ 'code_field' ] = get_config( 'block_ilp', 'mis_plugin_register_attendancecode_field' );
        $this->params[ 'student_id_field' ] = get_config( 'block_ilp', 'mis_plugin_register_studentid_field' );
        $this->params[ 'course_id_field' ] = get_config( 'block_ilp', 'mis_plugin_register_courseid_field' );
        $this->params[ 'timefield_start' ] = get_config( 'block_ilp', 'mis_plugin_register_timeield_start' );
        $this->params[ 'timefield_end' ] = get_config( 'block_ilp', 'mis_plugin_register_timefield_end' );
*/
    }
}
