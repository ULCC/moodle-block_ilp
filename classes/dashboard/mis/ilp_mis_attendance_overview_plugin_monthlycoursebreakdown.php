<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_overview_plugin_monthlycoursebreakdown extends ilp_mis_attendance_plugin{

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

    public function set_data( $student_id, $display_style ){
	        $this->data = $this->get_monthly_course_breakdown( $student_id );
    }
    public function plugin_type(){
        return 'overview';
    }

    /*
    * step through a student's courses, and for each course return attendance percentage for each month
    * @param int $student_id
    * @return array of arrays
    */
    public function get_monthly_course_breakdown( $student_id ){
        $reportlist = array();
        $tablerowlist = array();
        $headerrow = array( 
                            'Subject',
                            'Attendance'
        );
        
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        $monthlist = $cal->calc_sub_month_limits( $this->params[ 'start_date' ] , $this->params[ 'end_date' ] );
        foreach($monthlist as $monthdates ){
            $headerrow[] = date( 'M' , $cal->getutime( $monthdates[ 0 ] ) );
        }
        $tablerowlist[] = $headerrow;
        //step through this student's courses
        foreach( $this->get_courselist( $student_id ) as $course ){
            //get monthly breakdown for this course
            $reportlist[ $course[ 'course_title' ] ] = $this->get_percentage_by_month( $student_id, $course[ 'course_id' ] );

            
            $tablerowlist[] = array_merge( array( $course[ 'course_title' ] ), $this->get_percentage_by_month( $student_id, $course[ 'course_id' ] ) );
            $row = array();
        }
        return $tablerowlist;
        //return $reportlist;
    }

    /*
    * within the class time limits, give percentage attendance broken down by month
    * @param int $student_id
    * @param int $course_id
    * @return array of strings
    */
    public function get_percentage_by_month( $student_id, $course_id ){
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        $start = $this->params[ 'start_date' ];
        $end = $this->params[ 'end_date' ];

        $data = array();
        $data[ 'Attendance' ] = $this->get_attendance_percentage( $student_id, $course_id, $start, $end );

        $monthlist = $cal->calc_sub_month_limits( $start, $end );
        foreach($monthlist as $startend ){
            $start = $startend[ 0 ];
            $end = $startend[ 1 ];
            $info = $this->get_attendance_percentage( $student_id, $course_id, $start, $end );
            $month = date( 'M' , strtotime( $start ) ) ;
            $data[ $month ] = $info;
        }
        return $data;
    }

    /*
    * @param int $student_id
    * @param int $course_id
    * @param mixed $start
    * @param mixed $end
    * @return string
    */
    protected function get_attendance_percentage( $student_id, $course_id, $start, $end ){
        $nof_lectures = $this->get_lecturecount_by_student( $student_id , $course_id , $start, $end );
        $nof_present = $this->get_attendance_details( $student_id, $course_id, $this->params[ 'present_code_list' ], true, $start, $end  );
        if( is_numeric( $nof_lectures ) && $nof_lectures ){
            return $this->format_percentage( $nof_present / $nof_lectures );
        }
        return 'n/a';
    }
}
