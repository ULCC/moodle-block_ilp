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
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_mis_plugin_config_mform.php');

require_once($CFG->libdir.'/adminlib.php');

//admin_externalpage_setup('block_ilp');

//if set get the id of the plugin config that will be edited
$pluginname	= $PARSER->required_param('pluginname',PARAM_RAW);	;


$plugintype	= $PARSER->required_param('plugintype',PARAM_RAW);	;

// instantiate the db
$dbc = new ilp_db();




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

if (get_string_manager()->string_exists($pluginname . '_pluginnamesettings', 'block_ilp')) {
    $pageheading = get_string($pluginname . '_pluginnamesettings', 'block_ilp');
} else {
    $pageheading = get_string('pluginconfig', 'block_ilp');
}
$PAGE->navbar->add($pageheading,$CFG->wwwroot."/blocks/ilp/actions/edit_plugin_config.php?pluginnane={$pluginname}&plugintype={$plugintype}",'title');


// setup the page title and heading
//$SITE	=	$dbc->get_course_by_id(SITEID);
//$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
//$PAGE->set_heading($SITE->fullname);
//$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/edit_plugin_config.php', $PARSER->get_params());

//instantiate the plugin config mform
$mform		=	new	edit_mis_plugin_config_mform($pluginname,$plugintype);


//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back
	$return_url = $CFG->wwwroot."/admin/settings.php?section=blocksettingilp";
    redirect($return_url, '', ILP_REDIRECT_DELAY);
}

// has the form been submitted?
if($mform->is_submitted()) {
    // check the validation rules
    if($mform->is_validated()) {

    	$formdata	=	$mform->get_data();
    	
        // process the data
    	$success = $mform->process_data($formdata);

    	//if saving the data was not successful
        if(!$success) {
			//print an error message	
            print_error(get_string("configsaveerror", 'block_ilp'), 'block_ilp');
        }
        
        //decide whether the user has chosen to save and exit or save or display
        if (isset($formdata->saveanddisplaybutton)) { 
        	$return_url = $CFG->wwwroot."/admin/settings.php?section=blocksettingilp";
        	redirect($return_url, get_string("configsuc", 'block_ilp'), ILP_REDIRECT_DELAY);
        }
    }
}




//require the edit_plugin_config.html file
require_once($CFG->dirroot.'/blocks/ilp/views/edit_plugin_config.html');

?>
