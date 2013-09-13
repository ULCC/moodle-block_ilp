<?php 

/**
 * Creates an entry for an report 
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

//if set get the id of the report
$report_id	= $PARSER->required_param('report_id',PARAM_INT);	


//get the id of the course that is currently being used
$user_id = $PARSER->required_param('user_id', PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/edit_reportentry.php",array('report_id'=>$report_id,'user_id'=>$user_id,'course_id'=>$course_id));

// instantiate the db
$dbc = new ilp_db();

//get the report 
$report		=	$dbc->get_report_by_id($report_id);

$access_report_createreports = $report->has_cap($USER->id,$PAGE->context,'block/ilp:addreport');
$access_report_editreports = $report->has_cap($USER->id,$PAGE->context,'block/ilp:editreport');

//if the report is not found throw an error of if the report has a status of disabled
if (empty($report) || empty($report->status)) {
	print_error('reportnotfouund','block_ilp');
}


//check if the any of the users roles in the 
//current context has the create report capability for this report

if (empty($access_report_createreports))	{
	//the user doesnt have the capability to create this type of report entry
	print_error('userdoesnothavecreatecapability','block_ilp');	
}


if (!empty($entry_id))	{
	if (empty($access_report_editreports))	{
		//the user doesnt have the capability to edit this type of report entry
		print_error('userdoesnothaveeditcapability','block_ilp');	
	}	
} 

$plpuser	=	$dbc->get_user_by_id($user_id);


if (!empty($plpuser)) {
	$userinitals	=	$plpuser->firstname." ".$plpuser->lastname;	
	
}

// setup the navigation breadcrumbs
//block name
$PAGE->navbar->add(get_string('ilpname', 'block_ilp'),null,'title');

//user intials
$PAGE->navbar->add($userinitals,null,'title');

//section name
$PAGE->navbar->add(get_string('reports', 'block_ilp'),null,'title');


$PAGE->set_url($CFG->wwwroot.'/blocks/ilp/actions/view_reportentry.php', $PARSER->get_params());

//require view_reportentry html
require_once($CFG->dirroot.'/blocks/ilp/views/view_reportentry.html');











?>