<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_detail_plugin_class extends ilp_mis_attendance_plugin{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
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
                'Class',
                'Day',
                'Room',
                'Start',
                'End',
                'Tutor',
                'Att'
        );
        $headers = array_merge( $headers, array_values( $this->params[ 'extra_numeric_fieldlist' ] ) );
        $columns = $headers;
        $flextable->define_columns($columns);
        $flextable->define_headers($headers);
        $flextable->initialbars(false);
        $flextable->setup();
        foreach( $this->data as $row ){
            $data = array();
            $data[ 'Class' ] = $row[ 0 ];
            $data[ 'Day' ] = $row[ 1 ];
            $data[ 'Room' ] = $row[ 2 ];
            $data[ 'Start' ] = $row[ 3 ];
            $data[ 'End' ] = $row[ 4 ];
            $data[ 'Tutor' ] = $row[ 5 ];
            $data[ 'Att' ] = $row[ 6 ];
            $i = 6;
            foreach( $this->params[ 'extra_numeric_fieldlist' ] as $nfield ){
                $data[ $nfield ] = $row[ ++$i ];
            }
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

    public function set_data( $student_id, $term_id ){
        $this->data = $this->get_aggregated_register_entries( $student_id , $term_id );
    }
    public function plugin_type(){
        return 'detail';
    }

    /*
    * get the weekly attendance data for a user and return an array for display in a table
    * $term_id would be 1 for autumn term, 2 for spring term or 3 for summer term
    * @param int student_id
    * @param int $term_id
    * @return array of arrays
    */
    public function get_aggregated_register_entries( $student_id , $term_id=false ){
        $blankcell = '&nbsp;';
        //$data = array();
        $tablerowlist = array();    //this will build into a list of lists of display values - top row for table headers etc
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
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
            'Day',
            'Room',
            'Start',
            'End',
            'Tutor',
            'Att'
        );       
        $toprow = array_merge( $toprow, $this->params[ 'extra_numeric_fieldlist' ] );
        //$weekrow = array_fill( 0, count( $toprow ) - 1 , '&nbsp;' );
        $weekrow[] = 'Week';
        foreach( $weeklist as $week ){
            //$toprow[] = $cal->calc_weekno( $this->params[ 'week1' ], $week[ 0 ] );
            $weekrow[] = $cal->getreadabletime( $cal->getutime( $week[ 0 ] ), 'd/m' );
        }
        $weeknolist = $toprow;


        //$tablerowlist[ 'headers' ] = $toprow;
        //$tablerowlist[ 'weeks' ] = $weekrow;

        $courselist = $this->get_courselist( $student_id );
        $timefield = $this->params[ 'timefield_start' ];
        $timefield_end = $this->params[ 'timefield_end' ];
        $attendance_data = array();     //will build into a list of stats for each course-weekday
        $aggregate_list = array();
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
                            $tablerowlist[ $row_id ] = array( $row_visible_id );    //class
		                    $tablerowlist[ $row_id ][] = $cal->getreadabletime( $cal->getutime( $row[ 'dayname' ] ) , 'D' ); 
                            $tablerowlist[ $row_id ][] = $row[ 'room' ];            //room
		                    $tablerowlist[ $row_id ][] = $cal->get_time( $row[ 'clocktime' ] );       //start
                            $tablerowlist[ $row_id ][] = $cal->get_time( $row[ 'clocktime_end' ] );    //end
                            $tablerowlist[ $row_id ][] = $row[ 'tutor' ];            //tutor
		                    $tablerowlist[ $row_id ][] = false;                     //att

                            $attendance_data[ $row_id ] = array(
                                'possible' => 0,
                                'present' => 0,
                                'late' => 0,
                                'absent' => 0,
                                'authabsent' => 0
                            );
                            $aggregate_row = array();
                            foreach( $this->params[ 'extra_numeric_fieldlist' ] as $fieldname ){
                                if( $value = $row[ $fieldname ] ){
                                    $aggregate_row[ $fieldname ] = $value;
                                }
                                else{
                                    $aggregate_row[ $fieldname ] = 0;
                                }
                            }
                            $aggregate_list[ $row_id ] = $aggregate_row;
                        }
                        else{
                            foreach( $this->params[ 'extra_numeric_fieldlist' ] as $fieldname ){
                                $aggregate_list[ $row_id ][ $fieldname ] += $row[ $fieldname ];
                            }
                        }

/*
                        $col = count( $tablerowlist[ $row_id ] );
                        //match table column to week no
                        while( $weeknolist[ $col ] < $weekno ){
                            $col++;
                            $tablerowlist[ $row_id ][] = $blankcell;
                        }
*/

                        //$tablerowlist[ $row_id ][] = $this->decide_attendance_symbol( $row[ 'attendance_code' ] );
                        $attendance_data[ $row_id ] = $this->modify_attendance_data( $attendance_data[ $row_id ], $row[ 'attendance_code' ] );

                    }
                }
            }
        }
        foreach( $tablerowlist as $row_id=>$row ){
            //calc late and attendence percentages for each row
            if( in_array( $row_id, array_keys( $attendance_data ) ) ){
                $attendance = $attendance_data[ $row_id ];

                $attendance_params = array(
                    'marksPresent' => $attendance[ 'present' ],
                    'marksAbsent' => $attendance[ 'absent' ],
                    'marksTotal' => $attendance[ 'possible' ],
                    'marksAuthAbsent' => $attendance[ 'authabsent' ],
                    'marksLate' => $attendance[ 'late' ]
                );               

                //$tablerowlist[ $row_id ][ 6 ] = $this->format_percentage( $attendance[ 'late' ] / $attendance[ 'present' ] );
                $tablerowlist[ $row_id ][ 6 ] = $this->calcScore( $attendance_params, 'attendance' );
                            foreach( $this->params[ 'extra_numeric_fieldlist' ] as $fieldname ){
                                $tablerowlist[ $row_id ][] = $aggregate_list[ $row_id ][ $fieldname ];
                            }
                //$tablerowlist[ $row_id ][ 2 ] = $this->format_percentage( $attendance[ 'present' ] / $attendance[ 'possible' ] );
            }
        }
        return $tablerowlist;
        //return $data;
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
        if( in_array( $code, $this->params[ 'auth_absent_code_list' ] ) ){
            $attendance_data[ 'authabsent' ]++;
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
    
    protected function set_params( $params ){
        parent::set_params( $params );
		//$this->params[ 'extra_numeric_fieldlist' ] = get_config('block_ilp','mis_extra_numeric_fieldlist');
		//$this->params[ 'termdatelist' ] = get_config('block_ilp','mis_termdatelist');
		//$this->params[ 'start_date' ] = get_config('block_ilp','mis_start_date');
		//$this->params[ 'end_date' ] = get_config('block_ilp','mis_end_date');
/*
		$this->params[ 'late_code_list' ] = get_config('block_ilp','mis_late_code_list');
		$this->params[ 'present_code_list' ] = get_config('block_ilp','mis_present_code_list');
		$this->params[ 'absent_code_list' ] = get_config('block_ilp','mis_absent_code_list');
		$this->params[ 'auth_absent_code_list' ] = get_config('block_ilp','mis_auth_absent_code_list');
*/

        $this->params[ 'table' ] = get_config( 'block_ilp' , 'mis_attendance_plugin_class_studenttable' );
        $this->params[ 'attendance_view' ] = get_config( 'block_ilp' , 'mis_attendance_plugin_class_studenttable' );
		$this->params[ 'timefield_start' ] = get_config('block_ilp','mis_attendance_plugin_class_starttime');
		$this->params[ 'timefield_end' ] = get_config('block_ilp','mis_attendance_plugin_class_endtime');
		$this->params[ 'week1' ] = get_config('block_ilp','mis_plugin_class_firstday');

		$this->params[ 'mis_plugin_class_term1name' ] = get_config('block_ilp','mis_plugin_class_term1name');
		$this->params[ 'mis_plugin_class_term2name' ] = get_config('block_ilp','mis_plugin_class_term2name');
		$this->params[ 'mis_plugin_class_term3name' ] = get_config('block_ilp','mis_plugin_class_term3name');
		$this->params[ 'mis_plugin_class_term4name' ] = get_config('block_ilp','mis_plugin_class_term4name');
		$this->params[ 'mis_plugin_class_term5name' ] = get_config('block_ilp','mis_plugin_class_term5name');
		$this->params[ 'mis_plugin_class_term6name' ] = get_config('block_ilp','mis_plugin_class_term6name');
        $this->params[ 'termdatelist' ] = array(
			explode( ',', get_config('block_ilp','mis_plugin_class_term1startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term2startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term3startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term4startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term5startend') ),
			explode( ',', get_config('block_ilp','mis_plugin_class_term6startend') )
        );
        $this->params[ 'start_date' ] = $this->get_extreme_date( $this->params[ 'termdatelist' ] , 'first' );
        $this->params[ 'end_date' ] = $this->get_extreme_date( $this->params[ 'termdatelist' ] , 'last' );
        $this->params[ 'extra_numeric_fieldlist' ] = array( 'P', 'A', 'U', 'L' );
        $this->params[ 'course_id_field' ] = get_config( 'block_ilp' , 'mis_plugin_class_courseid_field' );
        $this->params[ 'course_label_field' ] = get_config( 'block_ilp' , 'mis_plugin_class_coursename_field' );
        $this->params[ 'student_id_field' ] = get_config( 'block_ilp' , 'mis_plugin_class_studentid_field' );
        $this->params[ 'studentlecture_attendance_id' ] = get_config( 'block_ilp' , 'mis_plugin_class_lectureid_field' );
        $this->params[ 'code_field' ] = get_config( 'block_ilp' , 'mis_plugin_class_codefield_name' );
        $this->params[ 'extra_fieldlist' ] = array();

		$this->params[ 'late_code_list' ] = explode( ',' , get_config( 'block_ilp', 'mis_plugin_class_late_codes' ) );
		$this->params[ 'present_code_list' ] = explode( ',' , get_config( 'block_ilp', 'mis_plugin_class_presentcodes'  ) );
		$this->params[ 'absent_code_list' ] = explode( ',' , get_config( 'block_ilp', 'mis_plugin_class_absentcodes'  ) );
		$this->params[ 'auth_absent_code_list' ] = array();
    }

    protected function get_extreme_date( $list, $firstlast ){
        global $CFG;
        require_once($CFG->dirroot.'/blocks/ilp/db/calendarfuncs.php');
        if( 'first' == $firstlast ){
            return $list[ 0 ][ 0 ];
        }
        elseif( 'last' == $firstlast ){
            $cal = new calendarfuncs();
            $max = 0;
            foreach( $list as $row ){
                foreach( $row as $date ){
                    if( trim( $date ) ){
                        $date = $cal->getutime( $date );
                        if( $date > $max ){
                            $max = $date;
                        }
                    }
                }
            }
            return $cal->getreadabletime( $max );
        }
    }
    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	$settingsheader 	= new admin_setting_heading('block_ilp/mis_attendance_plugin_class', get_string('ilp_mis_attendance_plugin_class_pluginname', 'block_ilp'), '');
    	$settings->add($settingsheader);
    	
    	$classstudenttable		=	new admin_setting_configtext('block_ilp/mis_attendance_plugin_class_studenttable',get_string( 'ilp_mis_attendance_plugin_class_table', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_tabledesc', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($classstudenttable);

        $lectureidfield = new admin_setting_configtext( 'block_ilp/mis_plugin_class_lectureid_field', get_string( 'ilp_mis_attendance_plugin_class_lectureid_field' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_class_lectureid_field' , 'block_ilp' ), '' , PARAM_RAW );
		$settings->add($lectureidfield);
		
        $courseidfield = new admin_setting_configtext( 'block_ilp/mis_plugin_class_courseid_field', get_string( 'ilp_mis_attendance_plugin_class_courseid_field' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_class_courseid_field' , 'block_ilp' ), '' , PARAM_RAW );
		$settings->add($courseidfield);
		
        $coursenamefield = new admin_setting_configtext( 'block_ilp/mis_plugin_class_coursename_field', get_string( 'ilp_mis_attendance_plugin_class_coursename_field' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_class_coursename_field' , 'block_ilp' ), '' , PARAM_RAW );
		$settings->add($coursenamefield);
		
        $codefieldname = new admin_setting_configtext( 'block_ilp/mis_plugin_class_codefield_name', get_string( 'ilp_mis_attendance_plugin_class_code_field' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_class_code_field' , 'block_ilp' ), '' , PARAM_RAW );
		$settings->add($codefieldname);
		
        $studentidfield = new admin_setting_configtext( 'block_ilp/mis_plugin_class_studentid_field', get_string( 'ilp_mis_attendance_plugin_class_studentid_field' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_class_studentid_field' , 'block_ilp' ), '' , PARAM_RAW );
		$settings->add($studentidfield);
		
		$starttimefield			=	new admin_setting_configtext('block_ilp/mis_attendance_plugin_class_starttime',get_string( 'ilp_mis_attendance_plugin_class_timefield_start', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_timefield_startdesc', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($starttimefield);
		
		$endtimefield			=	new admin_setting_configtext('block_ilp/mis_attendance_plugin_class_endtime',get_string( 'ilp_mis_attendance_plugin_class_timefield_end', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_timefield_enddesc', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($endtimefield);

		$firstdayfield			=	new admin_setting_configtext('block_ilp/mis_plugin_class_firstday',get_string( 'ilp_mis_attendance_plugin_class_week1', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_week1', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($firstdayfield);

		$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
		$pluginstatus			= 	new admin_setting_configselect('block_ilp/mis_plugin_class_tabletype',get_string('ilp_mis_attendance_plugin_class_tabletype','block_ilp'),get_string('ilp_mis_attendance_plugin_class_tabletypedesc','block_ilp'), 0, $options);
		$settings->add( $pluginstatus );
		
		$term1name			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term1name',get_string( 'ilp_mis_attendance_plugin_class_term1_name', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term1_name', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term1name);
		$term1startend			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term1startend',get_string( 'ilp_mis_attendance_plugin_class_term1_startend', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term1_startend', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term1startend);
		
		$term2name			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term2name',get_string( 'ilp_mis_attendance_plugin_class_term2_name', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term2_name', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term2name);
		$term2startend			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term2startend',get_string( 'ilp_mis_attendance_plugin_class_term2_startend', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term2_startend', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term2startend);
		
		$term3name			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term3name',get_string( 'ilp_mis_attendance_plugin_class_term3_name', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term3_name', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term3name);
		$term3startend			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term3startend',get_string( 'ilp_mis_attendance_plugin_class_term3_startend', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term3_startend', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term3startend);
		
		$term4name			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term4name',get_string( 'ilp_mis_attendance_plugin_class_term4_name', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term4_name', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term4name);
		$term4startend			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term4startend',get_string( 'ilp_mis_attendance_plugin_class_term4_startend', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term4_startend', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term4startend);
		
		$term5name			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term5name',get_string( 'ilp_mis_attendance_plugin_class_term5_name', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term5_name', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term5name);
		$term5startend			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term5startend',get_string( 'ilp_mis_attendance_plugin_class_term5_startend', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term5_startend', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term5startend);
		
		$term6name			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term6name',get_string( 'ilp_mis_attendance_plugin_class_term6_name', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term6_name', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term6name);
		$term6startend			=	new admin_setting_configtext('block_ilp/mis_plugin_class_term6startend',get_string( 'ilp_mis_attendance_plugin_class_term6_startend', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_class_term6_startend', 'block_ilp' ),'studentID',PARAM_RAW);
		$settings->add($term6startend);

        $latecodes  =   new admin_setting_configtext( 'block_ilp/mis_plugin_class_latecodes' , get_string( 'ilp_mis_attendance_plugin_class_latecodes' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_class_latecodes' , 'block_ilp' ), PARAM_RAW );
		$settings->add($latecodes);

        $presentcodes  =   new admin_setting_configtext( 'block_ilp/mis_plugin_class_presentcodes' , get_string( 'ilp_mis_attendance_plugin_class_presentcodes' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_class_presentcodes' , 'block_ilp' ), PARAM_RAW );
		$settings->add($presentcodes);

        $absentcodes  =   new admin_setting_configtext( 'block_ilp/mis_plugin_class_absentcodes' , get_string( 'ilp_mis_attendance_plugin_class_absentcodes' , 'block_ilp' ), get_string( 'ilp_mis_attendance_plugin_class_absentcodes' , 'block_ilp' ), PARAM_RAW );
		$settings->add($absentcodes);
    }

	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	function language_strings(&$string) {
        $string['ilp_mis_attendance_plugin_class_pluginname']		  = 'Class Overview';

        $string[ 'ilp_mis_attendance_plugin_class_table' ]            = 'register table';
        $string[ 'ilp_mis_attendance_plugin_class_tabledesc' ]        = 'table containing register entries for every every lecture';

        $string['ilp_mis_attendance_plugin_class_timefield_start']    = 'start time field';
        $string['ilp_mis_attendance_plugin_class_timefield_startdesc']= 'field containing the start time of a lecture';
        $string['ilp_mis_attendance_plugin_class_timefield_end']	  = 'end time field';
        $string['ilp_mis_attendance_plugin_class_timefield_enddesc']  = 'field containing the end time of a lecture';

        $string['ilp_mis_attendance_plugin_class_week1']			  = 'date of first day of week 1 (yyyy-mm-dd)';
        $string['ilp_mis_attendance_plugin_class_']			    = '';
        
        $string['ilp_mis_attendance_plugin_class_tabletype']				= 'Table type';
        $string['ilp_mis_attendance_plugin_class_tabletypedesc']			= 'Does this plugin connect to a table or stored procedure';        

        $string[ 'ilp_mis_attendance_plugin_class_lectureid_field' ]    =   'Lecture id field (PK)';
        $string[ 'ilp_mis_attendance_plugin_class_courseid_field' ]     = 'Course id field';
        $string[ 'ilp_mis_attendance_plugin_class_studentid_field' ]     = 'Student id field';
        $string[ 'ilp_mis_attendance_plugin_class_coursename_field' ]     = 'Course name field';
        $string[ 'ilp_mis_attendance_plugin_class_code_field' ]         =   'Attendance code field';

        $string[ 'ilp_mis_attendance_plugin_class_latecodes' ]         =   'List of register marks indicating late attendance (comma separated)';
        $string[ 'ilp_mis_attendance_plugin_class_presentcodes' ]         =   'List of register marks indicating presence (comma separated)';
        $string[ 'ilp_mis_attendance_plugin_class_absentcodes' ]         =   'List of register marks indicating absence (comma separated)';

        $string['ilp_mis_attendance_plugin_class_term1_name']			= 'Term 1 name';        
        $string['ilp_mis_attendance_plugin_class_term2_name']			= 'Term 2 name';        
        $string['ilp_mis_attendance_plugin_class_term3_name']			= 'Term 3 name';        
        $string['ilp_mis_attendance_plugin_class_term4_name']			= 'Term 4 name';        
        $string['ilp_mis_attendance_plugin_class_term5_name']			= 'Term 5 name';        
        $string['ilp_mis_attendance_plugin_class_term6_name']			= 'Term 6 name';        

        $string[ 'ilp_mis_attendance_plugin_class_term1_startend' ]     = 'Term 1 start and end dates (yyyy-mm-dd,yyyy-mm-dd)';
        $string[ 'ilp_mis_attendance_plugin_class_term2_startend' ]     = 'Term 2 start and end dates (yyyy-mm-dd,yyyy-mm-dd)';
        $string[ 'ilp_mis_attendance_plugin_class_term3_startend' ]     = 'Term 3 start and end dates (yyyy-mm-dd,yyyy-mm-dd)';
        $string[ 'ilp_mis_attendance_plugin_class_term4_startend' ]     = 'Term 4 start and end dates (yyyy-mm-dd,yyyy-mm-dd)';
        $string[ 'ilp_mis_attendance_plugin_class_term5_startend' ]     = 'Term 5 start and end dates (yyyy-mm-dd,yyyy-mm-dd)';
        $string[ 'ilp_mis_attendance_plugin_class_term6_startend' ]     = 'Term 6 start and end dates (yyyy-mm-dd,yyyy-mm-dd)';
    }

}
