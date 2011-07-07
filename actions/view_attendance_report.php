<?php
require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');
require_once($CFG->dirroot.'/blocks/ilp/db/calendarfuncs.php');
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_mis_connection.php');
require_once($CFG->dirroot.'/blocks/ilp/db/mis_constants.php');

$student_id 	= $PARSER->optional_param('student_id', 0, PARAM_INT);
$term_id 	= $PARSER->optional_param('term_id', 0, PARAM_INT);
$display_style  = $PARSER->optional_param( 'display_style', 'simple', PARAM_CLEAN );

$params = array(
            'prefix' => '',
            'student_table' => 'student',
            'student_unique_key' => 'id',
            'present_code_list' => $PRESENT_CODE,
            'absent_code_list' => $ABSENT_CODE,
            'late_code_list' => $LATE_CODE,
            'start_date' => '2011-01-01',
            'end_date' => '2011-02-28',
            'week1' => '2010-08-09',
            'lecture_time_field' => 'start',

            'attendance_view' => 'attendance_overview',
            'studentlecture_attendance_id' => 'slid',
            'student_id_field' => 'student_id',
            'student_name_field' => 'student_name',
            'course_id_field' => 'course_id',
            'course_label_field' => 'course_title',
            'lecture_id_field' => 'lecture_id',
            'timefield' => 'start',
            'code_field' => 'attendance_code',
            'extra_fieldlist' => array( 'attendance_cat' => 'cat' ),

            'termdatelist' => array(
                array(),                                //just here to force 1-based indexing
                array( '2010-10-01', '2010-12-17' ),
                array( '2011-01-04', '2011-03-25' ),
                array( '2011-04-13', '2011-06-30' )
            )
);

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
