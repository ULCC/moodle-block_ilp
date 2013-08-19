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

global $USER, $CFG, $SESSION, $PARSER, $DB;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//the id of the report  that the field will be in 
$report_id = $PARSER->required_param('report_id', PARAM_INT);

//the id of the reportfield used when editing
$reportfield_id = $PARSER->required_param('reportfield_id' ,PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//get the report field record
// this will look to he table called "block_ilp_report_field" where id = $reportfield_id
$reportfield		=	$dbc->get_report_field_data($reportfield_id);
/**
 * we could delete this above record straight way. Need to find out why they didn't delete this.
 * I guess, we need to delete or do something with those data, which was already entered for this field,
 * which we are not doing here!
 * Anyway this delete operation supposed to be very straight forward, like below:
 */

$deleted = $DB->delete_records('block_ilp_report_field', array('id'=>$reportfield_id));
if($deleted){
    //now we need to work to reset the position.
    //I will do it later on
    // normally it should work without changing the position according previous order
}
/*
//check if the report field was found
if (!empty($reportfield)) {
	//get the related plugin information used for the report field
	$pluginrecord	=	$dbc->get_plugin_by_id($reportfield->plugin_id);

    //assuming for status field, the plugin name will be "ilp_element_plugin_status"
	$classname = $pluginrecord->name;
	
	// include the moodle form for this table
	include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");
	
	if(!class_exists($classname)) {
	 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
	}
	
	$pluginclass	=	new $classname();

	$deletedposition	=	$reportfield->position;
	
	
	if ($pluginclass->delete_form_element($reportfield_id)) {
		$resulttext	=	get_string('formelementdeletesuc','block_ilp');
		
		//we now need to change the positions of all fields in the report move everything under the deleted position up
		$reportfields 	= 	$dbc->get_report_fields_by_position($report_id);

		//loop through fields returned
		if (!empty($reportfields)) {
			foreach($reportfields as $field) {
				
				if ($field->position > $deletedposition) { 
					
					//if the field is being moved up all other fields have postion value increased
					//if the field is being moved down all other fields have postion value decreased 
					//move up = 1 move down = 0
					if (!$dbc->set_new_position($field->id,$field->position-1));
					
				}
			}
		} 

		
	}	else {
		$resulttext	=	get_string('formelementdeleteerror','block_ilp');
	}
} else {
	$resulttext	=	get_string('formelementdeleteerror','block_ilp');	
}
*/
$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_prompt.php?report_id='.$report_id;
redirect($return_url);

