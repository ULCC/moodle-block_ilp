<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');
class ilp_mis_attendance_detail_plugin_register extends ilp_mis_plugin{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
        if( is_string( $this->data ) ){
            echo $this->data;
        }
        elseif( is_array( $this->data ) ){
		    echo self::test_entable( $this->data );
        }
    }

    public function set_data( $student_id, $term_id ){
        $this->data = $this->get_register_entries( $student_id , $term_id );
    }
    public function plugin_type(){}

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
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        if( false === $term_id ){
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
        $timefield = $this->params[ 'timefield' ];
        $attendance_data = array();     //will build into a list of stats for each course-weekday
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
                            $tablerowlist[ $row_id ] = array( $row_visible_id ); 
		                    $tablerowlist[ $row_id ][] = false;     //late
		                    $tablerowlist[ $row_id ][] = false;     //att
		                    $tablerowlist[ $row_id ][] = $row[ 'dayname' ]; 
		                    $tablerowlist[ $row_id ][] = $row[ 'clocktime' ]; 

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
                        $tablerowlist[ $row_id ][] = $this->decide_attendance_symbol( $row[ 'attendance_code' ] );
                        $attendance_data[ $row_id ] = $this->modify_attendance_data( $attendance_data[ $row_id ], $row[ 'attendance_code' ] );

                    }
                }
            }
        }
        foreach( $tablerowlist as $row_id=>$row ){
            //calc late and attendence percentages for each row
            if( in_array( $row_id, array_keys( $attendance_data ) ) ){
                $attendance = $attendance_data[ $row_id ];
                $tablerowlist[ $row_id ][ 1 ] = $this->format_percentage( $attendance[ 'late' ] / $attendance[ 'present' ] );
                $tablerowlist[ $row_id ][ 2 ] = $this->format_percentage( $attendance[ 'present' ] / $attendance[ 'possible' ] );
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
