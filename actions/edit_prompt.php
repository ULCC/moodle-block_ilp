<?php 

/**
 * Allows the user to create and edit prompts 
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
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//include the report form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/add_prompt_mform.php');


//get the id of the report that is currently in use
$report_id = $PARSER->required_param('report_id', PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//set the required level of permission needed to view this page



// setup the navigation breadcrumbs

//siteadmin or modules
//we need to determine which moodle we are in and give the correct area name
$sectionname	=	(stripos($CFG->release,"2.") !== false) ? get_string('administrationsite') : get_string('administration');

$PAGE->navbar->add($sectionname,null,'title');


//plugins or modules
//we need to determine which moodle we are in and give the correct area name
$sectionname	=	(stripos($CFG->release,"2.") !== false) ? get_string('plugins','admin') : get_string('managemodules');

$PAGE->navbar->add($sectionname,null,'title');

$PAGE->navbar->add(get_string('blocks'),null,'title');


//block name
$url	=	$CFG->wwwroot."/admin/settings.php?section=blocksettingilp";
$PAGE->navbar->add(get_string('blockname', 'block_ilp'),$url,'title');

//section name
$PAGE->navbar->add(get_string('reportconfiguration', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php",'title');

//get string for create report
$PAGE->navbar->add(get_string('reportfields', 'block_ilp'),null,'title');

// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout('ilp');
$PAGE->set_url('/blocks/ilp/actions/edit_prompt.php', $PARSER->get_params());


$promptmform	= new	add_prompt_mform($report_id);

// has the form been submitted?
if($promptmform->is_submitted()) {
	//get the form data submitted
	$formdata = $promptmform->get_data();
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_field.php?report_id='.$report_id.'&plugin_id='.$formdata->plugin_id;
    redirect($return_url, get_string("addfield", 'block_ilp'), ILP_REDIRECT_DELAY);
}


$previewreporturl	= "{$CFG->wwwroot}/blocks/ilp/actions/report_entry_preview.php?report_id={$report_id}";

require_once($CFG->dirroot.'/blocks/ilp/views/edit_prompt.html');

?>
