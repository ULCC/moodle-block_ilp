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

require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//get the id of the course that is currently being used
$course_id 	= $PARSER->optional_param('course_id', 0, PARAM_INT);

//get the tutor flag
$tutor		=	$PARSER->optional_param('tutor', 0, PARAM_RAW);

//get the status_id if set
$status_id		=	$PARSER->optional_param('status_id', 0, PARAM_INT);

//get the group if set
$group_id		=	$PARSER->optional_param('group_id', 0, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//check if the any of the users roles in the
//current context has the create report capability for this report

if (empty($access_viewotherilp)  && !empty($course_id))	{
   //the user doesnt have the capability to create this type of report entry
   print_error('userdoesnothavecapability','block_ilp');
}

//check if any tutess exist

// setup the navigation breadcrumbs

if (!empty($course_id)) {
   $listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$course_id}";
} else {
   $listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
}

//add the page title
$PAGE->navbar->add(get_string('ilps','block_ilp'),$listurl,'title');

//title
if (!empty($course_id)) {
   $course		=	$dbc->get_course_by_id($course_id);
   $title		=	$course->shortname;
} else {
   $title		=	get_string('mytutees','block_ilp');
}

//block name
$PAGE->navbar->add($title,null,'title');


// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-reportlist');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/view_studentlist.php",$PARSER->get_params());

//we need to list all of the students in the course with the given id
if (!empty($course_id)) {
   //get all of the students in this class
   $students	=	$dbc->get_course_users($course_id,$group_id);
   $course		=	$dbc->get_course_by_id($course_id);

   $groups		  =	groups_get_all_groups($course->id);

   if (!empty($groups))	{
      $groupmode    = groups_get_course_groupmode($course);   // Groups are being used
      $isseparategroups = ($course->groupmode == SEPARATEGROUPS &&
                           !has_capability('moodle/site:accessallgroups', $context));
   } else {
      $group_id	=	0;
   }

   $groupexists	=	groups_get_group($group_id);

   if (empty($groupexists))	{
      $group_id	=	0;
   } else	{
      $groupincourse	=	groups_get_group_by_name($course_id,$groupexists->name);

      if (empty($groupincourse)) {
         $group_id = 0;
      }
   }

   $pagetitle	=	$course->shortname;

   $ucourses	=	enrol_get_users_courses($USER->id, false,NULL,'shortname ASC');

   $user_courses	=	array();


   foreach ($ucourses as $uc) {
      $coursecontext = context_course::instance($uc->id);
      //if the user has the capability to view the course then add it to the array
      if (has_capability('block/ilp:viewotherilp', $coursecontext,$USER->id,false))	{
         $user_courses[]	=	$uc;
      }
   }

} else {
   //get the list of tutees for this user
   $student	=	$dbc->get_user_tutees($USER->id);

   $pagetitle	=	get_string('mytutees','block_ilp');
}

$status_items	=	$dbc->get_status_items(ILP_DEFAULT_USERSTATUS_RECORD);
$baseurl		=	$CFG->wwwroot."/blocks/ilp/actions/view_studentlist.php";
//require the view_studentlist.html page
require_once($CFG->dirroot.'/blocks/ilp/views/view_studentlist.html');

?>
