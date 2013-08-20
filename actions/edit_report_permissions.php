<?php
/**
 * Previews a report to the user
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

//include the report entry preview mform class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_permissions_mform.php');

//get the id of the report that is currently in use
$report_id = $PARSER->required_param('report_id', PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

$report_details = $dbc->get_report_by_id($report_id);

// setup the navigation breadcrumbs

//siteadmin or modules
//we need to determine which moodle we are in and give the correct area name
$sectionname	=	get_string('administrationsite');

$PAGE->navbar->add($sectionname,null,'title');


//plugins or modules
//we need to determine which moodle we are in and give the correct area name
$sectionname	=	get_string('plugins','admin');

$PAGE->navbar->add($sectionname,null,'title');

$PAGE->navbar->add(get_string('blocks'),null,'title');


//block name
$url	=	$CFG->wwwroot."/admin/settings.php?section=blocksettingilp";
$PAGE->navbar->add(get_string('blockname', 'block_ilp'),$url,'title');

//section name
$PAGE->navbar->add(get_string('reportconfiguration', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php",'title');

$PAGE->navbar->add($report_details->name,null,'report_name');

//get string for create report
$PAGE->navbar->add(get_string('reportpermissions', 'block_ilp'),null,'title');


// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/edit_report_permissions.php', $PARSER->get_params());

$svgcleverness = can_use_rotated_text();

if ($svgcleverness) {
    $csslink = new moodle_url($CFG->wwwroot . '/blocks/ilp/css/textrotate.css');
    $PAGE->requires->css($csslink);
}

$blockcapabilities	=	$dbc->get_block_capabilities();

$report		=	$dbc->get_report_by_id($report_id);


//if the report is not found throw an error of if the report has a status of disabled
if (empty($report) || !empty($report->deleted)) {
	print_error('reportnotfouund','block_ilp');
}


$mform	=	new edit_report_permissions_mform($report_id);

//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back to report configuration page
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php?course_id=' . (isset($course_id)) ? $course_id : '';
    redirect($return_url, null, ILP_REDIRECT_DELAY);
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
            print_error(get_string("reportpermissionserror", 'block_ilp'), 'block_ilp');
        }
        //if the report_id ahs not already been set
        $report_id	= (empty($report_id)) ? $success : $report_id;
        
        //return the user to the report configuration page
        $return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php';
        redirect($return_url, get_string("reportpermissionsuc", 'block_ilp',$report), ILP_REDIRECT_DELAY);
    }
}


$reportpermissions		=	$dbc->get_report_permissions($report_id);

if (!empty($reportpermissions)) {
	//get the form variables and
	$rp		=	reportformpermissions($reportpermissions);
	
	$mform->set_data($rp);
}




require_once($CFG->dirroot.'/blocks/ilp/views/edit_report_permissions.html');
?>
