<?php
/**
 * Ajax file for view_students
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $OUTPUT;

// Meta includes
require_once($CFG->dirroot . '/blocks/ilp/actions_includes.php');

//include the default class
require_once($CFG->dirroot . '/blocks/ilp/classes/tables/ilp_ajax_table.class.php');

//get the id of the course that is currently being used if set
$course_id = $PARSER->optional_param('course_id', 0, PARAM_INT);

//get the tutor flag
$tutor = $PARSER->optional_param('tutor', 0, PARAM_INT);

//get the status_id if set
$status_id = $PARSER->optional_param('status_id', 0, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

// set up the flexible table for displaying the portfolios
$flex_table = new ilp_ajax_table("student_list");//course_id={$course_id}tutor={$tutor}status_id={$status_id}");

$flex_table->define_baseurl($CFG->wwwroot . "/blocks/ilp/actions/view_studentlist.php?course_id={$course_id}&tutor={$tutor}&status_id={$status_id}");
$flex_table->define_ajaxurl($CFG->wwwroot . "/blocks/ilp/actions/view_studentlist.ajax.php?course_id={$course_id}&tutor={$tutor}&status_id={$status_id}");

// set the basic details to dispaly in the table
$headers = array(
    get_string('userpicture', 'block_ilp'),
    get_string('name', 'block_ilp'),
    get_string('status', 'block_ilp')
);

$columns = array('picture', 'fullname', 'u_status');

$headers[] = '';
$columns[] = 'view';

//we need to check if the mis plugin has been setup if it has we will get the attendance and punctuality figures


//include the attendance 
$mis_class_file = $CFG->dirroot . '/blocks/ilp/classes/dashboard/mis/ilp_mis_attendance_percentbar_plugin.php';
$mis_available = false;

if (file_exists($mis_class_file)) {

    include_once $mis_class_file;

    //create an instance of the MIS class
    $mis_class = new ilp_mis_attendance_percentbar_plugin();


    $headers[] = get_string('attendance', 'block_ilp');
    $columns[] = 'u_attendcance';
    $mis_attend_available = true;

    $headers[] = get_string('punctuality', 'block_ilp');
    $columns[] = 'u_punctuality';
    $mis_punctuality_available = true;

}


//get all enabled reports in this ilp
$reports = $dbc->get_reports(ILP_ENABLED);

//get the mamximum reports that can be displayed on the screen in the list
$max_reports = get_config('block_ilp', 'ilp_max_reports');

//check if maxreports is empty if yes then set to 
$max_reports = (!empty($max_reports)) ? $max_reports : ILP_DEFAULT_LIST_REPORTS;

//set the number of report columns to display
$reports = $flex_table->limitcols($reports, $max_reports);

//we are going to create headers and columns for all enabled reports 
foreach ($reports as $r) {
    $headers[] = $r->name;
    $columns[] = $r->id;
}

$flex_table->hoz_string = 'displayingreports';

$headers[] = get_string('lastupdated', 'block_ilp');
$columns[] = 'lastupdated';


$flex_table->define_fragment('studentlist');
$flex_table->collapsible(true);
//define the columns and the headers in the flextable
$flex_table->define_columns($columns);
$flex_table->define_headers($headers);

$flex_table->set_attribute('summary', get_string('studentslist', 'block_ilp'));
$flex_table->set_attribute('cellspacing', '0');
$flex_table->set_attribute('class', 'generaltable fit overflowtable');
$flex_table->set_attribute('id', "student_listcourse_id={$course_id}tutor={$tutor}status_id={$status_id}");


$flex_table->initialbars(true);

$flex_table->setup();

if (!empty($course_id)) {
    $users = $dbc->get_course_users($course_id);
} else {
    $users = $dbc->get_user_tutees($USER->id);
}

$students = array();

foreach ($users as $u) {
    $students[] = $u->id;
}

$not_status_ids = false;

if (!empty($status_id)) {

    $default_status_id = get_config('block_ilp', 'defaultstatusitem');

    if ($default_status_id == $status_id) {
        $not_status_ids = true;
    }
}

//we only want to get the student matrix if students have been provided
$students_list = (!empty($students)) ? $dbc->get_students_matrix($flex_table, $students, $status_id, $not_status_ids)
        : false;

//get the default status item which will be used as the status for students who
//have not entered their ilp and have not had a status assigned
$default_status_item_id = get_config('block_ilp', 'defaultstatusitem');

//get the status item record
$default_status_item = $dbc->get_status_item_by_id($default_status_item_id);


$status_item = (!empty($default_status_item)) ? $default_status_item->name : get_string('unknown', 'block_ilp');

//this is needed if the current user has capabilities in the course context, it allows view_main page to view the user
//in the course context
$course_param = (!empty($course_id)) ? "&course_id={$course_id}" : '';

if (!empty($students_list)) {
    foreach ($students_list as $student) {
        $data = array();

        $data['picture'] = $OUTPUT->user_picture($student, array('return' => true, 'size' => 50));
        $data['fullname'] = "<a href='{$CFG->wwwroot}/user/view.php?id={$student->id}' class=\"userlink\">" . fullname($student) . "</a>";
        //if the student status has been set then show it else they have not had there ilp setup
        //thus there status is the default
        $data['u_status'] = (!empty($student->u_status)) ? $student->u_status : $status_item;

        $data['view'] = "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$student->id}{$course_param}' >" . get_string('viewplp', 'block_ilp') . "</a>";


        //set the data for the student in question
        $mis_class->set_data($student->id);
        if (!empty($mis_attend_available)) {
            $actual = $mis_class->getAttendance();
            //we only want to try to find the percentage if we can get the total possible
            // attendance else set it to 0;
            $data['u_attendcance'] = (!empty($actual)) ? $actual : 0;
        }

        if (!empty($mis_punctuality_available)) {
            $actual = $mis_class->getPunctuality();
            //we only want to try to find the percentage if we can get the total possible
            // punctuality else set it to 0;
            $data['u_punctuality'] = (!empty($actual)) ? $actual : 0;
        }

        foreach ($reports as $r) {

            //get the number of this report that have been created
            $createdentries = $dbc->count_report_entries($r->id, $student->id);

            $reporttext = "{$createdentries} " . $r->name;

            //check if the report has a state field
            if ($dbc->has_plugin_field($r->id, 'ilp_element_plugin_state')) {

                //count the number of entries with a pass state
                $achievedentries = $dbc->count_report_entries_with_state($r->id, $student->id, ILP_PASSFAIL_PASS);
                $reporttext = $achievedentries . "/" . $createdentries . " " . get_string('achieved', 'block_ilp');
            }

            $data[$r->id] = $reporttext;
        }

        $lastentry = $dbc->get_lastupdate($student->id);
        $data['lastupdated'] = (!empty($lastentry->timemodified))
                ? userdate($lastentry->timemodified, get_string('strftimedate', 'langconfig'))
                : get_string('notapplicable', 'block_ilp');

        $flex_table->add_data_keyed($data);
    }
}

$flex_table->print_html();