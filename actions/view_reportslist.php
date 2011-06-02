<?php
if (!defined('MOODLE_INTERNAL')) {
    // this must be included from a Moodle page
    die('Direct access to this script is forbidden.');
}

//get the report
$report		=	$dbc->get_report_by_id($report_id);

//get all of the fields in the current report, they will be returned in order as
//no position has been specified
$reportfields		=	$dbc->get_report_fields_by_position($report_id);

//get all instances of this report for the user
$entries	= 	$dbc->get_user_report_entries($report_id,$user_id);

//does this report give user the ability to add comments 
$has_comments	=	(!empty($report->comments)) ? true	:	false;

//does this report allow users to say it is related to a particular course
$has_courserelated	=	(!$dbc->has_course_relation($report_id)) ? false : true;

//this will hold the ids of fields that we dont want to display
$dontdisplay	=	 array();

if (!empty($has_courserelated))	{
	$courserelated	=	$dbc->get_courserelated_field($report_id);
	//the should not be anymore than one of these fields in a report	
	foreach ($courserelated as $cr) {
			$dontdisplay[] 	=	$cr->id;	
	}
} 


$has_datedeadline	=	(!$dbc->has_datedeadline($report_id)) ? false : true;

if (!empty($has_datedeadline))	{
	$deadline	=	$dbc->get_datedeadline_field($report_id);
	//the should not be anymore than one of these fields in a report	
	foreach ($deadline as $d) {
			$dontdisplay[] 	=	$d->id;	
	}
} 

//require the view_reportlist.html [age
require_once($CFG->dirroot.'/blocks/ilp/views/view_reportslist.html');

?>