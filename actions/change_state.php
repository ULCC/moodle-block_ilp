<?php 

/**
 * Change the state of field in a   
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
$user_id = $PARSER->required_param('user_id', PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the id of the state selectors reportfield  
$reportfield_id = $PARSER->required_param('reportfield_id', PARAM_INT);

//get the id of the state selectors entry  
$entry_id = $PARSER->required_param('entry_id', PARAM_INT);

//get the id of the state selectors entry  
$item_id = $PARSER->required_param('item_id', PARAM_INT);

//get the selectedtab param if present
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

//get the tabitem param if present  
$tabitem 	= $PARSER->optional_param('tabitem', NULL, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();


//get the reportfield
$reportfield	=	$dbc->get_reportfield_by_id($reportfield_id);

//get the report
$report		=	(!empty($reportfield)) ? $dbc->get_report_by_id($reportfield->report_id) : false;  

//if the report is not found throw an error of if the report has a status of disabled
if (empty($report) || empty($report->status) || !empty($report->deleted)) {
	print_error('reportnotfouund','block_ilp');
}

/*
if ($USER->id != $user_id)	{
		//the user doesnt have the capability to edit this type of report entry
		print_error('userdoesnothaveeditcapability','block_ilp');	
}	
*/

$stateplugin	=	$dbc->get_plugin_by_name('block_ilp_plugin','ilp_element_plugin_state');
$resulttext		=	get_string('statenotchanged','block_ilp');

if ($stateplugin) {
	//get the entry
	$entry	=	$dbc->get_pluginentry($stateplugin->tablename,$entry_id,$reportfield_id,true);

	$entry	=	array_shift($entry);
	
	//get the value that matches the given one in the 
	$item	=	$dbc->get_state_item_id($stateplugin->tablename,false,$item_id,'id',$stateplugin->tablename.'_items');

	if (!empty($item)) {
		$entry->parent_id	=	$item->id;
		$entry->value		=	$item->value;
	}

	if ($dbc->update_plugin_record($stateplugin->tablename.'_ent',$entry)) {
		$resulttext		=	get_string('statechanged','block_ilp');
	}
} 


$return_url = $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&course_id={$course_id}&selectedtab={$selectedtab}&tabitem={$tabitem}";
redirect($return_url, $resulttext, ILP_REDIRECT_DELAY);










?>