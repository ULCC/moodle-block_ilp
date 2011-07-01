<?php
/* test the ilp_mis_connection class */
require_once('../configpath.php');
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');
require_once($CFG->dirroot.'/blocks/ilp/db/calendarfuncs.php');
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_mis_connection.php');
require_once($CFG->dirroot.'/blocks/ilp/db/mis_constants.php');

$params = array(
            'prefix' => '',
            'student_table' => 'student',
            'student_unique_key' => 'id',
            //'attendance_table' => 'student_lecture',
            //'attendance_table_unique_key' => 'id',
            //'attendance_studentid' => 'student_id',
            //'lecture_table' => 'lecture',
            //'attendance_lectureid' => 'lecture_id',
            //'lecture_unique_key' => 'id',
            //'lecture_courseid' => 'course_id',
            //'lecture_attendance_id' => 'attendancecode_id',
            //'attendancecode_table' => 'attendancecode',
            //'attendancecode_unique_key' => 'id',
            //'attendancecode_id_field' => 'code',
            //'course_table' => 'course',
            //'course_table_unique_key' => 'id',
            //'course_table_namefield' => 'title',
            //'student_course_table' => 'student_course',
            //'student_course_table_unique_key' => 'id',
            //'student_course_student_key' => 'student_id',
            //'student_course_course_key' => 'course_id',
            'present_code_list' => $PRESENT_CODE,
            'absent_code_list' => $ABSENT_CODE,
            'late_code_list' => $LATE_CODE,
            'start_date' => '2011-01-01',
            'end_date' => '2011-02-28',
            'lecture_time_field' => 'start',

            'attendance_view' => 'attendance_overview',
            'studentlecture_attendance_id' => 'slid',
            'student_id_field' => 'student_id',
            'student_name_field' => 'student_name',
            'course_id_field' => 'course_id',
            'course_label_field' => 'course_title',
            'lecture_id_field' => 'lecture_id',
            'timefield' => 'start',
            'code_field' => 'attendance_code'
);
$db = new ilp_mis_connection( $params );
//$studentid = 3; $courseid = 6;

var_crap( $db->get_report( 6 ) );
//var_crap( $db->test_mis_connection() );
//var_crap( $db->get_student_list() );

//$cal = new calendarfuncs();
//var_crap( $cal->display_calendar() );
