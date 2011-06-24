<?php 
/**
 * Allows the user to view a list of students 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//get the id of the course that is currently being used
$course_id 	= $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the tutor flag
$tutor		=	$PARSER->optional_param('tutor', NULL, PARAM_INT);



// instantiate the db
$dbc = new ilp_db();

//check if the any of the users roles in the 
//current context has the create report capability for this report

if (empty($access_viewotherilp))	{
	//the user doesnt have the capability to create this type of report entry
	print_error('userdoesnothavecapability','block_ilp');	
}

// setup the navigation breadcrumbs
//block name
$PAGE->navbar->add(get_string('ilpname', 'block_ilp'),null,'title');

//title
if (!empty($course_id)) {
	$course		=	$dbc->get_course_by_id($course_id);
	$title		=	$course->shortname;	
} else {
	$title		=	get_string('mytutees','block_ilp');
}

//add the page title
$PAGE->navbar->add($title,null,'title');

//section name
$PAGE->navbar->add(get_string('dashboard','block_ilp'),null,'title');

$PAGE->set_title(get_string('ilpname','block_ilp')." : ".$title);

$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/view_studentlist.php",array('tutor'=>$tutor,'course_id'=>$course_id));


//we need to list all of the students in the course with the given id
if (!empty($course_id)) {

	//get all of the students in this class
	$students	=	$dbc->get_course_users($course_id);
} else {
	//get the list of tutess for this user	
	$student	=	$dbc->get_user_tutees($USER->id);
}

//require the view_studentlist.html page
require_once($CFG->dirroot.'/blocks/ilp/views/view_studentlist.html');

?>