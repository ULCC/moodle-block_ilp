<?php 

/**
 * Allows the user to create and edit fields 
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

//the id of the plugin ype the field will be
$plugin_id = $PARSER->required_param('plugin_id', PARAM_INT);

//the id of the reportfield used when editing
$reportfield_id = $PARSER->optional_param('reportfield_id',null ,PARAM_INT);

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
$url	=	$CFG->wwwroot . "/admin/settings.php?section=blocksettingilp";
$PAGE->navbar->add(get_string('blockname', 'block_ilp'),$url,'title');

//section name
$PAGE->navbar->add(get_string('reportconfiguration', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php",'title');

$PAGE->navbar->add($report_details->name, $CFG->wwwroot . '/blocks/ilp/actions/edit_prompt.php?report_id=' . $report_id,'report_name');

//get string for create report
$PAGE->navbar->add(get_string('reportfields', 'block_ilp'),null,'title');

$pagetitle	=	get_string('addfield','block_ilp');

//get string for page
$PAGE->navbar->add($pagetitle,null,'title');

// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/edit_field.php', $PARSER->get_params());

//get the plugin record that for the plugin 
$pluginrecord	=	$dbc->get_plugin_by_id($plugin_id);

//take the name field from the plugin as it will be used to call the instantiate the plugin class
$classname = $pluginrecord->name;
// include the class for the plugin
include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

if(!class_exists($classname)) {
 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
}

//instantiate the plugin class
$pluginclass	=	new $classname();

//has the maximum number of this field type in this report been reached? 
if (!$pluginclass->can_add($report_id) && empty($reportfield_id))	{
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_prompt.php?report_id='.$report_id;
    redirect($return_url, get_string("fieldmaximum", 'block_ilp',$pluginclass->audit_type()), ILP_REDIRECT_DELAY);
}

//call the plugin edit function inside of which the plugin configuration mform
$pluginclass->edit($report_id,$plugin_id,$reportfield_id);



require_once($CFG->dirroot.'/blocks/ilp/views/edit_field.html');

?>
