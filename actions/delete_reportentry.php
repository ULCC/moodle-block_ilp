<?php

/**
 * Deletes a report
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


//if set get the id of the report entry to be edited
$entry_id	= $PARSER->required_param('entry_id',PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);

//get the id the comment if one is being edited
$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);

// instantiate the db
$dbc = new ilp_db();

//get the report 
$report		=	$dbc->get_report_by_id($report_id);

$access_report_deletereports = $report->has_cap($USER->id,$PAGE->context,'block/ilp:deletereport');

//if the report is not found throw an error of if the report has a status of disabled
if (empty($report) || empty($report->status)) {
	print_error('reportnotfouund','block_ilp');
}

//get the entry 
$entry		=	$dbc->get_entry_by_id($entry_id);

//if the report is not found throw an error of if the report has a status of disabled
if (empty($entry)) {
	print_error('entrynotfouund','block_ilp');
}
// Removed the below coding to enable delete link for single entry in reports tab.
// if (empty($report->frequency))	{
//	entries can only be deleted from reports that allow multiple entries
//	print_error('entrycannotbedeleted','block_ilp');
//}
 
//check if the user has the delete record capability
if (empty($access_report_deletereports))	{
	//the user doesnt have the capability to create this type of report entry
	print_error('userdoesnothavedeletecapability','block_ilp');	
}



// instantiate the db
$dbc = new ilp_db();

//get all of the fields in the current report, they will be returned in order as
//no position has been specified
$reportfields		=	$dbc->get_report_fields_by_position($report_id);
			
if (!empty($reportfields))	{ 
	foreach ($reportfields as $field) {
		//get the plugin record that for the plugin 
		$pluginrecord	=	$dbc->get_plugin_by_id($field->plugin_id);
        include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$pluginrecord->name}.php");

        $delete_entry = new $pluginrecord->name();
        $delete_entry->delete_entry_record($entry_id);

	}
}



$dbc->delete_entry_by_id($entry_id);

$return_url = $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&course_id={$course_id}&selectedtab=$selectedtab&tabitem={$tabitem}";
redirect($return_url, get_string('entrydeleted','block_ilp'), ILP_REDIRECT_DELAY);



?>