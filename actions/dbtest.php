<?php
/* test the ilp_mis_connection class */
require_once('../configpath.php');
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_mis_connection.php');

$params = array(
            'prefix' => '',
            'student_table' => 'student',
            'student_unique_key' => 'id',
            'attendance_table' => 'student_lecture',
            'attendance_table_unique_key' => 'id',
            'attendance_studentid' => 'student_id',
            'lecture_table' => 'lecture',
            'attendance_lectureid' => 'lecture_id',
            'lecture_unique_key' => 'id',
            'lecture_courseid' => 'course_id',
            'lecture_attendance_id' => 'attendancecode_id',
            'attendancecode_table' => 'attendancecode',
            'attendancecode_unique_key' => 'id',
            'attendancecode_id_field' => 'code',
            'course_table' => 'course',
            'course_table_unique_key' => 'id',
            'course_table_namefield' => 'title',
            'student_course_table' => 'student_course',
            'student_course_table_unique_key' => 'id',
            'student_course_student_key' => 'student_id',
            'student_course_course_key' => 'course_id',
            'present_code_list' => array( 's', 'e', 'c' ),
            'absent_code_list' => array( 'x' )
);
$db = new ilp_mis_connection( $params );
//$rs = $db->Execute( "SELECT * FROM mdl_block_ilp_plu_dd_items" );
//$rs = $db->execute( "SELECT * FROM mdl_block_ilp_plu_dd_items" );
$studentid = 3; $courseid = 6;
//$rs = $db->get_attendance_details( $studentid , $courseid , array( 's' , 'e' ));
//var_crap( $rs );exit;

//var_crap( $rs->GetRows() );
var_crap( $db->get_report( 6 ) );


