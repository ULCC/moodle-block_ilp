<?php
require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER;

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//include the report form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_status_item_mform.php');

//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);	;

// instantiate the db
$dbc = new ilp_db();

//instantiate the edit_status_item_mform class
$mform	=	new edit_status_item_mform($report_id);

/*
data processing
*/


// setup the navigation breadcrumbs

//siteadmin or modules
//we need to determine which moodle we are in and give the correct area name
$sectionname	=	(stripos($CFG->release,"2.") !== false) ? get_string('administrationsite') : get_string('administration');

$pagetitle = get_string( 'edit_status_items', 'block_ilp' );
$PAGE->navbar->add($sectionname,null,'title');


//plugins or modules
//we need to determine which moodle we are in and give the correct area name
$sectionname	=	(stripos($CFG->release,"2.") !== false) ? get_string('plugins','admin') : get_string('managemodules');

$PAGE->navbar->add($sectionname,null,'title');

$PAGE->navbar->add(get_string('blocks'),null,'title');


//block name
$url	=	"http://ilpdev2.local/admin/settings.php?section=blocksettingilp";
$PAGE->navbar->add(get_string('blockname', 'block_ilp'),$url,'title');

//section name
$PAGE->navbar->add(get_string('reportconfiguration', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php",'title');

//get string for create report
$PAGE->navbar->add($pagetitle,null,'title');

// setup the page title and heading
$PAGE->set_title(get_string('blockname','block_ilp'));
$PAGE->set_heading(get_string('reportconfiguration', 'block_ilp'));
$PAGE->set_url($CFG->wwwroot.'/blocks/ilp/edit_report.php', $PARSER->get_params());

require_once($CFG->dirroot.'/blocks/ilp/views/edit_report.html');
?>
