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

// If any reports exist with null values for position, give them a position that retains the current order.
$null_position_report = $dbc->null_position_reports();
$min_position = $dbc->upperlower_report_position('MIN');

if ($null_position_report) {
    $dbc->create_report_positions_where_null($null_position_report, $min_position);
}

if (!$dbc->report_position_sequence_is_continuous($min_position)) {
    $dbc->report_position_resequence(1);
}




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


// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/edit_report_configuration.php', $PARSER->get_params());



require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');

//install new plugins
ilp_element_plugin::install_new_plugins();


require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_template.class.php');
//install new templates
ilp_dashboard_template::install_new_plugins();

require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_plugin.class.php');
//install new dashboard plugins
ilp_dashboard_plugin::install_new_plugins();

require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');
//install new tabs
ilp_dashboard_tab::install_new_plugins();

require_once($CFG->dirroot.'/blocks/ilp/views/edit_report_configuration.html');

?>
