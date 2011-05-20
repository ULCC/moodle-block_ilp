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

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//get the id of the course that is currently being used
$course_id = $PARSER->required_param('course_id', PARAM_INT);

//the id of the report  that the field will be in 
$report_id = $PARSER->required_param('report_id', PARAM_INT);

//the id of the reportfield used when editing
$reportfield_id = $PARSER->required_param('reportfield_id' ,PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//get the report field record
$reportfield		=	$dbc->get_report_field_data($reportfield_id);

//check if the report field was found
if (!empty($reportfield)) {
	//get the plugin used for the report field
	$pluginrecord	=	$dbc->get_plugin_by_id($reportfield->plugin_id);
	
	$classname = $pluginrecord->name;
	
	// include the moodle form for this table
	include_once("{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/{$classname}.php");
	
	if(!class_exists($classname)) {
	 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
	}
	
	$pluginclass	=	new $classname();
	
	if ($pluginclass->delete_form_element($reportfield_id)) {
		$resulttext	=	get_string('formelementdeletesuc','block_ilp');
	}	else {
		$resulttext	=	get_string('formelementdeleteerror','block_ilp');
	}
} else {
	$resulttext	=	get_string('formelementdeleteerror','block_ilp');	
}

$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_prompt.php?report_id='.$report_id.'&amp;course_id='.$course_id;
redirect($return_url, $resulttext, REDIRECT_DELAY);

