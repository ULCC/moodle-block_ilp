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

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER;



//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//include the report form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_mform.php');

//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);	;

// instantiate the db
$dbc = new ilp_db();

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


// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout('ilp');
$PAGE->set_url('/blocks/ilp/actions/edit_report_configuration.php', $PARSER->get_params());



require_once ($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin.php');

//install new plugins
ilp_element_plugin::install_new_plugins();


require_once ($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_template.php');
//install new templates
ilp_dashboard_template::install_new_plugins();

require_once ($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_plugin.php');
//install new dashboard plugins
ilp_dashboard_plugin::install_new_plugins();

require_once ($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_tab.php');
//install new tabs
ilp_dashboard_tab::install_new_plugins();

require_once($CFG->dirroot.'/blocks/ilp/views/edit_report_configuration.html');

?>
