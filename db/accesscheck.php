<?php
/**
 * Perfrorms permissions checks against the user to see what they are allowed to
 * do, which are stored as boolean values in local variables.
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

global $CFG, $PARSER,$USER,$PAGE;

require_once $CFG->dirroot . "/blocks/ilp/lib.php";
require_once($CFG->dirroot."/blocks/ilp/classes/database/ilp_db.php");

//get the user id if it is not set then we will pass the global $USER->id 
$user_id   = optional_param('user_id',$USER->id,PARAM_INT);

// get the id of the course
$course_id = optional_param('course_id', 0,PARAM_INT);
if (!$course_id) {
    $course_id = optional_param('courseid', 0,PARAM_INT);
}

// the user must be logged in
require_login(0, false);

$sitecontext	=	context_system::instance();

//get the user context
$usercontext	=   context_user::instance($user_id);


//if there is no user context then we must throw an error as the user context is the 
//least that is needed in order to display the ilp
if (empty($usercontext)) {
	print_error('useridisincorrect', 'block_ilp');
}

//if the course id is set then we can get the course context
if (!empty($course_id)) {
	
	// get the current course context
	$coursecontext =    context_course::instance($course_id);

	if ($course_id == SITEID)	{
		$coursecontext =	$sitecontext;
	}

	// bail if we couldn't find the course context
	if(!$coursecontext) {
	    print_error('incorrectcourseid', 'block_ilp');
	}
}

//by default we will be in the sitecontext
$context	=	$sitecontext;

//if we are in the coursecontext
if(isset($coursecontext)){
	$context		=	$coursecontext;
} else  if (has_capability('block/ilp:viewotherilp', $usercontext)) {
	$context		=	$usercontext;	
} else if ($user_id == $USER->id) {
	$context		=	$sitecontext;
} 


//CAPABILITIES
$access_createreports	=	has_capability('block/ilp:addreport', $context);
$access_editreports		=	has_capability('block/ilp:editreport', $context);
$access_deletereports	=	has_capability('block/ilp:deletereport', $context);
$access_viewreports		=	has_capability('block/ilp:viewreport', $context);
$access_viewilp			=	has_capability('block/ilp:viewilp', $context);
$access_viewotherilp	=	has_capability('block/ilp:viewotherilp', $context);

$access_addcomment		=	has_capability('block/ilp:addcomment', $context);
$access_editcomment		=	has_capability('block/ilp:editcomment', $context);
$access_deletecomment	=	has_capability('block/ilp:deletecomment', $context);
$access_viewcomment		=	has_capability('block/ilp:viewcomment', $context);

$access_addviewextension	=	has_capability('block/ilp:addviewextension', $context);

//check if the current user is an admin or has the ilpviewall capabilty at site level
$ilpadmin						=	has_capability('block/ilp:ilpviewall',$sitecontext);
    	
$access_ilp_admin				=	(ilp_is_siteadmin($USER->id) || $ilpadmin) ? true : false;

if (!empty($access_ilp_admin)) {
	$access_createreports	=	true;
	$access_editreports		=	true;
	$access_deletereports	=	true;
	$access_viewreports		=	true;	
	$access_viewilp			=	true;
	$access_viewotherilp	=	true;
	
	$access_addcomment		=	true;
	$access_editcomment		=	true;
	$access_deletecomment	=	true;
	$access_viewcomment		=	true;

    $access_addviewextension   = true;
}

//TODO: we should not be in the course context change to another context

$PAGE->set_context($context);

?>
