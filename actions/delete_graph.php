<?php

/**
 * Deletes a report field from a report 
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

//the id of the report  that the field will be in 
$report_id = $PARSER->required_param('report_id', PARAM_INT);

//the id of the reportfield used when editing
$reportgraph_id = $PARSER->required_param('reportgraph_id' ,PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//get the report field record
$reportgraph		=	$dbc->get_report_graph_data($reportgraph_id);

//check if the report field was found
if (!empty($reportgraph)) {
	//get the plugin used for the report field

	$pluginrecord	=	$dbc->get_graph_plugin_by_id($reportgraph->plugin_id);

	$classname = $pluginrecord->name;

	// include the moodle form for this table
	include_once("{$CFG->dirroot}/blocks/ilp/plugins/graph/{$classname}.php");
	
	if(!class_exists($classname)) {
	 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
	}
	
	$pluginclass	=	new $classname();

	if ($pluginclass->delete_graph($reportgraph_id)) {
        $resulttext =   get_string('deletesuccess','block_ilp');
	}	else {
		$resulttext	=	get_string('deleteerror','block_ilp');
	}
} else {
	$resulttext	=	get_string('deleteerror','block_ilp');
}

$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_graphs.php?report_id='.$report_id;
redirect($return_url, $resulttext, ILP_REDIRECT_DELAY);

