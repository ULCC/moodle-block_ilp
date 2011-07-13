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
        foreach( $courselist as $course ){
            foreach( $weeklist as $week ){
                if( $rowlist = $this->get_attendance_details( $student_id, $course[ 'course_id' ], array(), false, $week[ 0 ], $week[ 1 ] ) ){
                    //var_crap( $cal->calc_day_of_week( $row[ $timefield ] ) );
                    foreach( $rowlist as $row ){
                        $weekno = $cal->calc_weekno( $this->params[ 'week1' ], $week[ 0 ] );
	                    $row_id = $course[ 'course_id' ] . " " . $course[ 'course_title' ] . " " . $row[ 'dayname' ];
                        if( !in_array( $row_id, array_keys( $tablerowlist ) ) ){
                            //new row
	                        $row_visible_id = $course[ 'course_id' ] . " " . $course[ 'course_title' ]; 
                            $tablerowlist[ $row_id ] = array( $row_visible_id );    //class
		                    $tablerowlist[ $row_id ][] = $row[ 'dayname' ]; 
                            $tablerowlist[ $row_id ][] = $row[ 'room' ];            //room
		                    $tablerowlist[ $row_id ][] = $row[ 'clocktime' ];       //start
                            $tablerowlist[ $row_id ][] = $row[ 'clocktime_end' ];    //end
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

    /*
    * query the data for a particular student on a particular course
    * if $attendancecode_list is defined, the query will be restricted to those codes
    * if $countonly=true, a simple integer willb be returned instead of a nested array
    * @param int $course_id
    * @param int $student_id
    * @param array of strings $attendancecode_list
    * @param boolean $countonly
    * @return int if $countonly, array of arrays otherwise
    */
    public function get_attendance_details( $student_id, $course_id=null, $attendancecode_list=array(), $countonly=false, $start=null, $end=null ){
        $table = $this->params[ 'attendance_view' ];
        $slid_field = $this->params[ 'studentlecture_attendance_id' ];
        $acode_field = $this->params[ 'code_field' ];
        $student_id_field = $this->params[ 'student_id_field' ];
        $course_id_field = $this->params[ 'course_id_field' ];
        $timefield = $this->params[ 'timefield_start' ];
        $timefield_end = $this->params[ 'timefield_end' ];
        
        if( $countonly ){
            $selectclause = "COUNT( $slid_field ) n";
            $fields = "COUNT( $slid_field ) n";
        }
        else{
            $selectclause = "$slid_field id, $acode_field, $timefield, date_format( $timefield , '%I:%i' ) clocktime, DATE_FORMAT( $timefield_end, '%I:%i' ) clocktime_end, date_format( $timefield , '%a' ) dayname,
            room, tutor
            ";
            if( $this->params[ 'extra_fieldlist' ] ){
                foreach( $this->params[ 'extra_fieldlist' ] as $field=>$alias ){
                    $selectclause .= ", $field $alias";
                }
            }
            if( $this->params[ 'extra_numeric_fieldlist' ] ){
                foreach( $this->params[ 'extra_numeric_fieldlist' ] as $field=>$alias ){
                    $selectclause .= ", $field $alias";
                }
            }
        }
        $whereandlist = array(
            "$student_id_field= '$student_id'",
        );
        if( $course_id ){
            $whereandlist[] = "$course_id_field = '$course_id'";
        }
        $whereandlist = array_merge( $whereandlist, $this->generate_time_conditions( $timefield, false, $start, $end ) );
        if( count( $attendancecode_list ) ){
            $whereandlist[] = "$acode_field IN  ('" . implode( "','" , $attendancecode_list ) . "')";
        }
        $whereclause = implode( ' AND ' , $whereandlist );
        $sql = "
            SELECT $selectclause
            FROM $table
            WHERE $whereclause
        ";
        $res = $this->db->execute( $sql )->getRows();
        if( $countonly ){
            return ilp_mis_connection::get_top_item( $res, 'n' );
        }
        return $res;
    }

    /*
    * generate a list of sql where conditions applying time limits to the query
    * if optional $start and $end are not supplied, the values of the class variables will be used (beware of side-effects oh best beloved)
    * @param string $fieldalias
    * @param boolean $english
    * @param string $start
    * @param string $end
    * @return array
    */
    public function generate_time_conditions( $fieldalias=false, $english=false, $start=null, $end=null ){
        $rtn = array();
        if( !$fieldalias ){
            $timetable_table = $this->params[ 'attendance_view' ];
            $timefield = $this->params[ 'timefield_start' ];
            $fieldalias = "$timetable_table.$timefield";
        }
        //$fieldalias = "`$fieldalias`";  //backtick the fieldname
        if( ( $param_start = $start ) || ( $param_start = $this->params[ 'start_date' ] ) ){
            if( $english ){
                $rtn[] = "from $param_start";
            }
            else{
                $rtn[] = "$fieldalias >= '$param_start'";
            }
        }
        if( ( $param_end = $end ) || ( $param_end = $this->params[ 'end_date' ] ) ){
            if( $english ){
                $rtn[] = "to $param_end";
            }
            else{
                $rtn[] = "$fieldalias <= '$param_end'";
            }
        }
        if( $english ){
            return implode( ' ' , $rtn );
        }
        return $rtn;
    }
    /*
    * @param int student_id
    * @return array of arrays
    */
    public function get_courselist( $student_id ){
        $course_id_field = $this->params[ 'course_id_field' ];
        $course_label_field = $this->params[ 'course_label_field' ];
        $student_id_field = $this->params[ 'student_id_field' ];
        $table = $this->params[ 'attendance_view' ];
/*
        $sql = "
            SELECT $course_id_field, $course_label_field 
            FROM $table 
            WHERE $student_id_field = '$student_id'
            GROUP BY $course_id_field
        ";
*/
        return $this->dbquery( $table, array( $student_id_field => $student_id ), "$course_id_field, $course_label_field", array( 'group' => $course_id_field ) );
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

}
