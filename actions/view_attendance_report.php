<?php
require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');
require_once($CFG->dirroot.'/blocks/ilp/db/calendarfuncs.php');
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_mis_connection.php');
require_once($CFG->dirroot.'/blocks/ilp/db/mis_constants.php');

//refactor exotic mis functions to plugin
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');
//require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/mis/ilp_mis_attendance_detail_plugin_simple.php');

$student_id 	= $PARSER->optional_param('student_id', 0, PARAM_INT);
$term_id 	= $PARSER->optional_param('term_id', 0, PARAM_INT);
$display_style  = $PARSER->optional_param( 'display_style', 'simple', PARAM_CLEAN );

$overview_list = array(
    'simple',
    'term',
    'course',
    'monthlycoursebreakdown'
);
$detail_list = array(
    'class',
    'register'
);
/*
$plugin_name = "ilp_mis_attendance_detail_plugin_$display_style";
require_once( $CFG->dirroot . "/blocks/ilp/classes/dashboard/mis/$plugin_name.php" );
*/

/*
keys are referred to within the plugin
values are fieldnames in the db table or view
*/
$params = array(
            'prefix' => '',
            'student_table' => 'student',
            'student_unique_key' => 'id',
            'student_attendance_field' => 'attendance',
            'student_punctuality_field' => 'punctuality',

            'termstudent_table' => 'student_term',
            'termstudent_table_student_id_field' => 'studentID',
            'termstudent_table_term_id_field' => 'term',
            'termstudent_table_term_marksTotal_field' => 'term',
            'termstudent_table_term_marksPresent_field' => 'marksPresent',
            'termstudent_table_term_marksAbsent_field' => 'marksAbsent',
            'termstudent_table_term_marksAuthAbsent_field' => 'marksAuthAbsent',
            'termstudent_table_term_marksLate_field' => 'marksLate',

            'coursestudent_table' => 'student_course',
            'coursestudent_table_student_id_field' => 'student_id',
            'coursestudent_table_course_id_field' => 'course_id',
            'coursestudent_table_term_marksTotal_field' => 'term',
            'coursestudent_table_term_marksPresent_field' => 'marksPresent',
            'coursestudent_table_term_marksAbsent_field' => 'marksAbsent',
            'coursestudent_table_term_marksAuthAbsent_field' => 'marksAuthAbsent',
            'coursestudent_table_term_marksLate_field' => 'marksLate',
            'coursestudent_table_term_grade_field' => 'Grade',
            'coursestudent_table_term_performance_field' => 'Performance',

            'coursestudentmonth_table' => 'student_course_month',
            'coursestudentmonth_table_student_id_field' => 'studentID',
            'coursestudentmonth_table_course_id_field' => 'courseID',
            'coursestudentmonth_table_month_id_field' => 'month',
            'coursestudentmonth_table_month_marksTotal_field' => 'marksTotal',
            'coursestudentmonth_table_month_marksPresent_field' => 'marksPresent',
            'coursestudentmonth_table_month_marksAbsent_field' => 'marksAbsent',
            'coursestudentmonth_table_month_marksAuthAbsent_field' => 'marksAuthAbsent',
            'coursestudentmonth_table_month_marksLate_field' => 'marksLate',
            'coursestudentmonth_table_month_coursename_field' => 'courseName',

            'present_code_list' => $PRESENT_CODE,
            'absent_code_list' => $ABSENT_CODE,
            'auth_absent_code_list' => $AUTH_ABSENT_CODE,
            'late_code_list' => $LATE_CODE,
            'start_date' => '2010-08-09',
            'end_date' => '2011-07-30',
            'week1' => '2010-08-09',
            'lecture_time_field' => 'start',

            'attendance_view' => 'attendance_overview',
            'studentlecture_attendance_id' => 'slid',
            'student_id_field' => 'student_id',
            'student_name_field' => 'student_name',
            'course_id_field' => 'course_id',
            'course_label_field' => 'course_title',
            'lecture_id_field' => 'lecture_id',
            'timefield_start' => 'start',
            'timefield_end' => 'end',
            'room' => 'room',
            'tutor' => 'tutor',
            'code_field' => 'attendance_code',
            'extra_fieldlist' => array( 'attendance_cat' => 'cat' ),
            'extra_numeric_fieldlist' => array( 'marksPresent' => 'P' ,
                                     			 'marksAbsent' => 'A' ,
                                        	     'marksAuthAbsent' => 'U' ,
                                        		'marksLate' => 'L' ),
			

            'termdatelist' => array(
                array(),                                //just here to force 1-based indexing
                array( '2010-10-01', '2010-12-17' ),
                array( '2011-01-04', '2011-03-25' ),
                array( '2011-04-13', '2011-06-30' )
            ),
            'stored_procedure' => false
);

foreach( array(
            '8.1/8.2' => 'simple',
            '8.3' => 'term',
            '8.4/8.6' => 'course',
            '8.5' => 'monthlycoursebreakdown',
            '9.1' => 'class',
            //'9.2' => 'register'
        ) as $ref => $display_style ){
            if( in_array( $display_style, $overview_list ) ){
                $plugin_name = "ilp_mis_attendance_overview_plugin_$display_style";
            }
            else{
                $plugin_name = "ilp_mis_attendance_detail_plugin_$display_style";
            }
            echo "<h3>$ref $plugin_name</h3>";
            require_once( $CFG->dirroot . "/blocks/ilp/classes/dashboard/mis/$plugin_name.php" );
			//$mis = new $plugin_name( $params );
			$mis = new $plugin_name();
			$mis->set_data( $student_id, $term_id , true );     //3rd arg turns performance on or off in plugin_course
			$mis->display( true );                              //argument turns links on or off in plugin_simple
}
exit;

$mis = new $plugin_name( $params );
$mis->set_data( $student_id, $term_id );
$mis->display();
exit;

/////////just test stuff below here ////////////////////////////////

/*
$cal = new calendarfuncs();
var_crap(  $cal->calc_weekno( '2010-08-09' , '2010-08-27 10:00:00' ) );
*/

$db = new ilp_mis_connection( $params );
//var_crap( $db->get_report( $student_id ) );
switch( $display_style ){
    case 'simple':
        $data = $db->get_attendance_summary( $student_id );
        break;
    case 'term':
        $data = $db->get_attendance_summary_by_term( $student_id );
        break;
    case 'course':
        $data = $db->get_attendance_summary_by_course( $student_id );
        break;
    case 'monthly-course-breakdown':
        $data = $db->get_monthly_course_breakdown( $student_id );
        break;
    case 'register':
        $data = $db->get_register_entries( $student_id , $term_id );
        break;
}
echo( $db->test_entable( $data ) );
