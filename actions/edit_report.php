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
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_mform.php');


//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);

$PAGE->set_url($CFG->wwwroot . '/blocks/ilp/actions/edit_report.php?report_id=' . $report_id);
// instantiate the db
$dbc = new ilp_db();

//instantiate the edit_report_mform class
$mform	=	new edit_report_mform($report_id);


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

        //get the form data submitted
    	$formdata = $mform->get_data();
    	
    	//only try to change the icon if a file was submitted
    	if ($mform->get_file_content('binary_icon') != false) {
    	  		$formdata->binary_icon	=	$mform->get_file_content('binary_icon');
    	} else {
    		$formdata->binary_icon	=	'';
    	}
   	
    	$formdata->maxedit		=	(empty($formdata->maxedit)) ? 0 : $formdata->maxedit;
    	
    	$formdata->comments		=	(empty($formdata->comments)) ? 0 : $formdata->comments;
    	
    	$formdata->frequency	=	(empty($formdata->frequency)) ? 0 : $formdata->frequency;

        if (!isset($formdata->vault)) {
            $formdata->vault = 0;
        }
    	
        // process the data
    	$success = $mform->process_data($formdata);

    	//if saving the data was not successful
        if(!$success) {
			//print an error message	
            print_error(get_string("reportcreationerror", 'block_ilp'), 'block_ilp');
        }

        //if the report_id has not already been set
        $report_id	= (empty($report_id)) ? $success : $report_id;
        
        //decide whether the user has chosen to save and exit or save or display
        if (isset($formdata->saveanddisplaybutton)) { 
        	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_prompt.php?report_id='.$report_id;
        	redirect($return_url, get_string("reportcreationsuc", 'block_ilp'), ILP_REDIRECT_DELAY);
        }
    }
}

//set the page title
$pagetitle	=	(empty($report_id)) ? get_string('createreport', 'block_ilp') : get_string('editreport', 'block_ilp');


if (!empty($report_id)) {
	$reportrecord	=	$dbc->get_report_by_id($report_id);

    //converts back variable stored in database to those on the form
    //TODO: this needs to be tidied up, it should use constant deifnitions - ND
    if ($reportrecord->reporttype==1){
        $reportrecord->reptype = 1;
    }

    if ($reportrecord->reporttype==2){
        $reportrecord->reptype = 1;
        $reportrecord->recurrent =1;
    }

    if ($reportrecord->reporttype==3){
        $reportrecord->reptype = 2;
        $reportrecord->recurrent =1;
    }

    if ($reportrecord->reporttype==4){
        $reportrecord->reptype = 2;
    }

    $mform->set_data($reportrecord);

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
