<?php 

/**
 * Allows the user to create and edit reports 
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

//include the report form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/clone_report_mform.php');


//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);

$PAGE->set_url($CFG->wwwroot . '/blocks/ilp/actions/clone_report.php?report_id=' . $report_id);
// instantiate the db
$dbc = new ilp_db();

//instantiate the edit_report_mform class
$mform	=	new clone_report_mform($report_id);


//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php';
    redirect($return_url, '', ILP_REDIRECT_DELAY);
}


//was the form submitted?
// has the form been submitted?
if($mform->is_submitted()) {
    // check the validation rules
    if($mform->is_validated()) {
        $data = $mform->get_data();
        $params = 'report_id=' . $report_id . '&currentname=' . $data->currentname . '&newname=' . $data->newname;
        $params .= '&current_to_vault=' . $data->current_to_vault . '&new_to_visible=' . $data->new_to_visible;
        redirect($CFG->wwwroot . '/blocks/ilp/actions/run_clone_report.php?' . $params);
    }
}

//set the page title
$pagetitle	=	get_string('clone_form', 'block_ilp');


$sectionname	=	get_string('administrationsite');

$PAGE->navbar->add($sectionname,null,'title');

$sectionname	=	get_string('plugins','admin');

$PAGE->navbar->add($sectionname,null,'title');

$PAGE->navbar->add(get_string('blocks'),null,'title');

$url	=	$CFG->wwwroot."/admin/settings.php?section=blocksettingilp";

$PAGE->navbar->add(get_string('blockname', 'block_ilp'),$url,'title');

//section name
$PAGE->navbar->add(get_string('reportconfiguration', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php",'title');

//get string for create report
$PAGE->navbar->add($pagetitle,null,'title');

// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/edit_report.php', $PARSER->get_params());


require_once($CFG->dirroot.'/blocks/ilp/views/edit_report.html');

?>
