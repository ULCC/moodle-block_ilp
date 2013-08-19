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

require_once($CFG->libdir.'/adminlib.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_mform.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/predefined_reports.class.php');

// instantiate the db
$dbc = new ilp_db();

if(!$dbc->ilp_admin())
{
    print_error(get_string('nopermission'));
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
$PAGE->navbar->add(get_string('predefinedreports', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/add_predefined_reports.php",'title');


// setup the page title and heading
//$SITE	=	$dbc->get_course_by_id(SITEID);
//$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
//$PAGE->set_heading($SITE->fullname);
//$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/add_predefined_report.php', $PARSER->get_params());

$p = new ilp_predefined_reports();
$opresult	=	$p->main() ;


//require the add_predefined_reports.html file
require_once($CFG->dirroot.'/blocks/ilp/views/add_predefined_reports.html');

?>
