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

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

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
if (empty($report) || empty($report->status) || !empty($report->deleted)) {
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

		print_error('userdoesnothavedeletecapability','block_ilp');	
	}	
} 

$reportfields		=	$dbc->get_report_fields_by_position($report_id);

//we will only attempt to display a report if there are elements in the 
//form. if not we will send the user back to the dashboard 
if (empty($reportfields)) {
	//send the user back to the dashboard page telling them that the report is not ready for display
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/view_main.php?user_id='.$user_id.'&course_id='.$course_id;
    redirect($return_url, get_string("reportnotready", 'block_ilp'), ILP_REDIRECT_DELAY);
} 

//require the reportentry_mform so we can display the report
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/reportentry_mform.php');

$mform	= new	report_entry_mform($report_id,$user_id,$entry_id,$course_id);

//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back to dashboard
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/view_main.php?course_id='.$course_id.'&user_id='.$user_id;
    redirect($return_url, '', ILP_REDIRECT_DELAY);
}


//was the form submitted?
// has the form been submitted?
if($mform->is_submitted()) {
    // check the validation rules
    if($mform->is_validated()) {

        //get the form data submitted
    	$formdata = $mform->get_data();
  	
        // process the data
    	$success = $mform->process_data($formdata);

    	//if saving the data was not successful
        if(!$success) {
			//print an error message	
            print_error(get_string("entrycreationerror", 'block_ilp'), 'block_ilp');
        }

        if (!isset($formdata->saveanddisplaybutton)) { 
            $return_url = $CFG->wwwroot.'/blocks/ilp/actions/view_main.php?user_id='.$user_id.'&course_id='.$course_id;
        	redirect($return_url, get_string("reportcreationsuc", 'block_ilp'), ILP_REDIRECT_DELAY);
        }
    }
}


if (!empty($entry_id)) {
	
	//create a entry_data object this will hold the data that will be passed to the form
	$entry_data		=	new stdClass();
	
	//get the main entry record
	$entry	=	$dbc->get_entry_by_id($entry_id);

	if (!empty($entry)) 	{
		//check if the maximum edit field has been set for this report
		if (!empty($report->maxedit)) 	{
			//calculate the age of the report entry
			$entryage	=	time() 	-	$entry->timecreated;

			//if the entry is older than the max editing time 
			//then return the user to the 
			if ($entryage > $CFG->maxeditingtime)	{
				 $return_url = $CFG->wwwroot.'/blocks/ilp/actions/view_main.php?user_id='.$user_id.'&course_id='.$course_id;
        		redirect($return_url, get_string("maxeditexceed", 'block_ilp'), ILP_REDIRECT_DELAY);
			}
			
		}
		
		
		//get all of the fields in the current report, they will be returned in order as
		//no position has been specified
		$reportfields		=	$dbc->get_report_fields_by_position($report_id);
				
		foreach ($reportfields as $field) {
			
			//get the plugin record that for the plugin 
			$pluginrecord	=	$dbc->get_plugin_by_id($field->plugin_id);
			
			//take the name field from the plugin as it will be used to call the instantiate the plugin class
			$classname = $pluginrecord->name;
			
			// include the class for the plugin
			include_once("{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/{$classname}.php");
			
			if(!class_exists($classname)) {
			 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
			}
			
			//instantiate the plugin class
			$pluginclass	=	new $classname();
			
			$pluginclass->load($field->id);
	
			//create the fieldname
			$fieldname	=	$field->id."_field";		
			
			
			$pluginclass->load($field->id);
			
			//call the plugin class entry data method
			$pluginclass->entry_data($field->id,$entry_id,$entry_data);
		}

		//loop through the plugins and get the data for each one
		$mform->set_data($entry_data);
	}	
} 



$plpuser	=	$dbc->get_user_by_id($user_id);

$dashboardurl	=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&course_id={$course_id}";
$userprofileurl	=	$CFG->wwwroot."/user/profile.php?id={$user_id}";
if ($user_id != $USER->id) {
	if (!empty($access_viewotherilp) && !empty($course_id)) {
		$listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$course_id}";
	} else {
		$listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
	}
	
	$PAGE->navbar->add(get_string('ilps', 'block_ilp'),$listurl,'title');
	$PAGE->navbar->add(get_string('ilpname', 'block_ilp'),$dashboardurl,'title');
} else {
	$PAGE->navbar->add(get_string('myilp', 'block_ilp'),$dashboardurl,'title');
}

//user intials
$PAGE->navbar->add(fullname($plpuser),$userprofileurl,'title');

//section name
$PAGE->navbar->add($report->name,null,'title');

$titleprefix	=	 (!empty($entry_id)) ? get_string('edit') : get_string('add');	

// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp')." : ".fullname($plpuser));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-entry');
//$PAGE->set_pagelayout('ilp');
$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/edit_reportentry.php",$PARSER->get_params());



//require edit_reportentry html
require_once($CFG->dirroot.'/blocks/ilp/views/edit_reportentry.html');
?>
