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

//get the group_id if set
$group_id = $PARSER->optional_param('group_id', 0, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

// set up the flexible table for displaying the portfolios
$flextable = new ilp_ajax_table("student_listcourse_id{$course_id}tutor{$tutor}status_id{$status_id}");


$flextable->define_baseurl($CFG->wwwroot . "/blocks/ilp/actions/view_studentlist.php?course_id={$course_id}&tutor={$tutor}&status_id={$status_id}&group_id={$group_id}");
$flextable->define_ajaxurl($CFG->wwwroot . "/blocks/ilp/actions/view_studentlist.ajax.php?course_id={$course_id}&tutor={$tutor}&status_id={$status_id}&group_id={$group_id}");

// set the basic details to dispaly in the table
$headers = array(
    get_string('userpicture', 'block_ilp'),
    get_string('name', 'block_ilp'),
    get_string('status', 'block_ilp')
);

$columns = array('picture', 'fullname', 'u_status');

$headers[] = '';
$columns[] = 'view';
$nosorting = array('picture', 'u_status','view');

//we need to check if the mis plugin has been setup if it has we will get the attendance and punctuality figures

$attendanceclass				=	get_config('block_ilp','attendplugin');
$misavailable 					= 	false;
$misattendavailable				=	false;
$mispunctualityavailable		=	false;

if (!empty($attendanceclass)) {
	$misclassfile = $CFG->dirroot . "/blocks/ilp/classes/dashboard/mis/{$attendanceclass}.php";	
	if (file_exists($misclassfile)) {
		include_once $misclassfile;
		
		$misavailable	=	true;
		
		//create an instance of the MIS class
    	$misclass = new $attendanceclass();
		
    	//check if the methods exists
    	if (method_exists($misclass, 'getAttendance'))	{
   		    $headers[] = get_string('attendance', 'block_ilp');
   			$columns[] = 'u_attendcance';
   			$nosorting[] = 'u_attendcance';
   			$misattendavailable = true;
    	}
    	
    	//check if the methods exists
	    if (method_exists($misclass, 'getAttendance'))	{
    		$headers[] = get_string('punctuality', 'block_ilp');
    		$columns[] = 'u_punctuality';
    		$nosorting[] = 'u_punctuality';
    		$mispunctualityavailable = true;    		
    	}
	}
}

//get all enabled reports in this ilp
$reports = $dbc->get_reports(ILP_ENABLED);

//get the mamximum reports that can be displayed on the screen in the list
$maxreports = get_config('block_ilp', 'ilp_max_reports');

//check if maxreports is empty if yes then set to 
$maxreports = (!empty($maxreports)) ? $maxreports : ILP_DEFAULT_LIST_REPORTS;

//set the number of report columns to display

//removed as we no longer need the horizonatal scrolling
//$reports	=	$flextable->limitcols($reports,$maxreports);


//we are going to create headers and columns for all enabled reports 
foreach ($reports as $r) {
    $headers[] = $r->name;
    $columns[] = $r->id;
    $nosorting[] = $r->id;
}

$flextable->hoz_string = 'displayingreports';

$headers[] = get_string('lastupdated', 'block_ilp');
$columns[] = 'lastupdated';
$nosorting[] = 'lastupdated';

$flextable->define_fragment('studentlist');
$flextable->collapsible(true);
//define the columns and the headers in the flextable
$flextable->define_columns($columns);
$flextable->define_headers($headers);

$flextable->column_nosort = $nosorting;
$flextable->sortable(true, 'lastname', 'DESC');
$flextable->set_attribute('summary', get_string('studentslist', 'block_ilp'));
$flextable->set_attribute('cellspacing', '0');
$flextable->set_attribute('class', 'generaltable fit');
$flextable->set_attribute('id', "student_listcourse_id={$course_id}tutor={$tutor}status_id={$status_id}");


$flextable->initialbars(true);

$flextable->setup();

if (!empty($course_id)) {
    $users = $dbc->get_course_users($course_id,$group_id);  
} else {
    $users = $dbc->get_user_tutees($USER->id);
}

$students = array();

foreach ($users as $u) {
    $students[] = $u->id;
}

$notstatus_ids = false;

if (!empty($status_id)) {

    $defaultstatusid = get_config('block_ilp', 'defaultstatusitem');

    if ($defaultstatusid == $status_id) {
        $notstatus_ids = true;
    }
}

//we only want to get the student matrix if students have been provided
$studentslist = (!empty($students)) ? $dbc->get_students_matrix($flextable, $students, $status_id, $notstatus_ids)
        : false;
//get the default status item which will be used as the status for students who
//have not entered their ilp and have not had a status assigned
$defaultstatusitem_id = get_config('block_ilp', 'defaultstatusitem');

//get the status item record
$defaultstatusitem = $dbc->get_status_item_by_id($defaultstatusitem_id);


$status_item = (!empty($defaultstatusitem)) ? $defaultstatusitem->name : get_string('unknown', 'block_ilp');

//this is needed if the current user has capabilities in the course context, it allows view_main page to view the user
//in the course context
$course_param = (!empty($course_id)) ? "&course_id={$course_id}" : '';

$coursearg = ( $course_id ) ? "&course=$course_id" : '' ;
if (!empty($studentslist)) {
    foreach ($studentslist as $student) {
        $data = array();
		
        $userprofile	=	(stripos($CFG->release,"2.") === false) ? 'view.php' : 'profile.php';
                
        $data['picture'] = $OUTPUT->user_picture($student, array('return' => true, 'size' => 50));
        $data['fullname'] = "<a href='{$CFG->wwwroot}/user/{$userprofile}?id={$student->id}{$coursearg}' class=\"userlink\">" . fullname($student) . "</a>";
        //if the student status has been set then show it else they have not had there ilp setup
        //thus there status is the default
        $data['u_status'] = (!empty($student->u_status)) ? $student->u_status : $status_item;

        $data['view'] = "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$student->id}{$course_param}' >" . get_string('viewplp', 'block_ilp') . "</a>";

		//we will only attempt to get MIS data if an attendace plugin has been selected in the settings page
		
        if (!empty($misavailable)) {
        	$misclass = new $attendanceclass();
	        //set the data for the student in question
	        $misclass->set_data($student->idnumber);
	        if (!empty($misattendavailable)) {
	        	$attendpercent	=	0;
	            $attendpercent = $misclass->getAttendance();
	            //we only want to try to find the percentage if we can get the total possible
	            // attendance else set it to 0;
	            $data['u_attendcance'] = (!empty($attendpercent)) ? $attendpercent : 0;
	        }
	
	        if (!empty($mispunctualityavailable)) {
	            $punctpercent	=	0;
	        	$punctpercent = $misclass->getPunctuality();
	            //we only want to try to find the percentage if we can get the total possible
	            // punctuality else set it to 0;
	            $data['u_punctuality'] = (!empty($punctpercent)) ? $punctpercent : 0;
	        }
        }
        
        foreach ($reports as $r) {

            //get the number of this report that have been created
            $createdentries = $dbc->count_report_entries($r->id, $student->id);

            $reporttext = "{$createdentries} " . $r->name;

            //check if the report has a state field
            if ($dbc->has_plugin_field($r->id, 'ilp_element_plugin_state')) {

                //count the number of entries with a pass state
                $achievedentries = $dbc->count_report_entries_with_state($r->id, $student->id, ILP_STATE_PASS);
                //we need to count the number of entries that have a notcounted status
                $notcountedentries = $dbc->count_report_entries_with_state($r->id, $student->id, ILP_STATE_NOTCOUNTED);
                $createdentries     =   $createdentries     -   $notcountedentries;

                $reporttext         =   $achievedentries . "/" . $createdentries . " " . get_string('achieved', 'block_ilp');
            }
            
        	
			if ($dbc->has_plugin_field($r->id,'ilp_element_plugin_date_deadline')) {
				$inprogressentries	=	$dbc->count_report_entries_with_state($r->id,$student->id,ILP_STATE_UNSET,false);
				$inprogentries 		=	array(); 
				
				if (!empty($inprogressentries)) {
					foreach ($inprogressent as $e) {
						$inprogentries[]	=	$e->id;
					}
				}
				
				//get the number of entries that are overdue
				$overdueentries			=	$dbc->count_overdue_report($r->id,$student->id,$inprogentries,time());
				$reporttext				.=	(!empty($overdueentries))?  "<br />".$overdueentries." ".get_string('reportsoverdue','block_ilp') : "";	
			}

            $data[$r->id] = $reporttext;
        }

        $lastentry = $dbc->get_lastupdate($student->id);
        $data['lastupdated'] = (!empty($lastentry->timemodified))
                ? userdate($lastentry->timemodified, get_string('strftimedate', 'langconfig'))
                : get_string('notapplicable', 'block_ilp');

        $flextable->add_data_keyed($data);
    }
}

$flextable->print_html();
