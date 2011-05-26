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

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

// Include the report permissions file
require_once($CFG->dirroot.'/blocks/ilp/report_permissions.php');

//if set get the id of the report 
$report_id	= $PARSER->required_param('report_id',PARAM_INT);	


//get the id of the course that is currently being used
$user_id = $PARSER->required_param('user_id', PARAM_INT);

//if set get the id of the report entry to be edited
$entry_id	= $PARSER->optional_param('entry_id',NULL,PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//get the report 
$report		=	$dbc->get_report_by_id($report_id);

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

$reportfields		=	$dbc->get_report_fields_by_position($report_id);

//we will only attempt to display a report if there are elements in the 
//form. if not we will send the user back to the dashboard 
if (empty($reportfields)) {
	//send the user back to the dashboard page telling them that the report is not ready for display
	//$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_prompt.php?report_id='.$report_id.'&course_id='.$course_id;
    //redirect($return_url, get_string("reportmustcontainfields", 'block_ilp'), REDIRECT_DELAY);
} 

//require the reportentry_mform so we can display the report
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/reportentry_mform.php');

$mform	= new	report_entry_mform($report_id,$user_id,$entry_id,$course_id);

//require edit_reportentry html
require_once($CFG->dirroot.'/blocks/ilp/views/edit_reportentry.html');
?>
