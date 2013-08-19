<?php

/**
 * Changes the status of a report 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);	

// instantiate the db
$dbc = new ilp_db();

//get the report 
$report		=	$dbc->get_report_by_id($report_id);

//if the report is not found throw an error
if (empty($report)) {
	print_error('reportnotfouund','block_ilp');
}

//if the report satatus is currently disabled (0) set it to enabled (1)
if ($report->vault == 0) {
	$res = $dbc->set_report_vault_status($report_id,1);
} else {
	$res = $dbc->set_report_vault_status($report_id,0);
}

$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php?report_id='.$report_id;
redirect($return_url);

?>