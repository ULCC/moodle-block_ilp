<?php

/**
 * Set report to deleted in the database and set it to unviewable  
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
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);

$PAGE->set_url($CFG->wwwroot . '/blocks/ilp/actions/delete_report.php?report_id=' . $report_id);

// instantiate the db
$dbc = new ilp_db();

//get the report 
$report		=	$dbc->get_report_by_id($report_id);

//if the report is not found throw an error
if (empty($report)) {
	print_error('reportnotfouund','block_ilp');
}

$deletedposition	=	$report->position;

$reports		=	$dbc->get_reports_by_position();

if (!empty($reports)) {
    foreach($reports as $field) {

        if ($field->position > $deletedposition) {
            //if the field is being moved up all other fields have postion value increased
            //if the field is being moved down all other fields have postion value decreased
            //move up = 1 move down = 0
            if(!$dbc->set_new_report_position($field->id,$field->position - 1));

        }
    }
}

$reportfields		=	$dbc->get_report_fields_by_position($report_id);

if (!empty($reportfields))	{
	foreach ($reportfields as $field) {

    $pluginrecord	=	$dbc->get_plugin_by_id($field->plugin_id);
    include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$pluginrecord->name}.php");

    $delete_report = new $pluginrecord->name();
    $delete_report->delete_report($report_id);
    }
}

//if the report satatus is currently disabled (0) set it to enabled (1)
$res = $dbc->set_report_status($report_id,0);
$res = $dbc->delete_report($report_id,1);
$res = $dbc->set_new_report_position($report_id,0);
//save the changes to the report
if (!empty($res)) {
	$resulttext	=	get_string('reportdeleted','block_ilp');
} else {
	$resulttext	=	get_string('reportdeleteerror','block_ilp');
}

$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php?report_id='.$report_id;
redirect($return_url, $resulttext, ILP_REDIRECT_DELAY);

?>