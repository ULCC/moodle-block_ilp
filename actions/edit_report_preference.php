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
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//include the report form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_preference_mform.php');


//get the id of the report the preference wil be added to
$report_id	= $PARSER->required_param('report_id',PARAM_INT);

//get the id of the user who the preference wil be created for
$user_id	= $PARSER->required_param('user_id',PARAM_INT);

//get the id of the course if it is set
$course_id	= $PARSER->optional_param('course_id',null,PARAM_INT);


// instantiate the db
$dbc = new ilp_db();

//instantiate the edit_report_mform class
$mform	=	new edit_report_preference_mform($report_id,$course_id,$user_id,null);


//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back
	$return_url = $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&course_id={$course_id}";
    redirect($return_url, '', ILP_REDIRECT_DELAY);
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

        }

        //decide whether the user has chosen to save and exit or save or display
        if (isset($formdata->saveanddisplaybutton)) { 
        	$return_url = $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&course_id={$course_id}";
        	redirect($return_url, get_string("preferencecreationsuc", 'block_ilp'), ILP_REDIRECT_DELAY);
        }
    }
}


// setup the navigation breadcrumbs


$plpuser	=	$dbc->get_user_by_id($user_id);

$dashboardurl	=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&course_id={$course_id}";
$userprofileurl	=	$CFG->wwwroot."/user/profile.php?id={$user_id}";
if ($user_id != $USER->id) {
    if (!empty($access_viewotherilp) && !empty($course_id)) {
        $listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$course_id}";
    } else {
        $listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
    }

    $PAGE->navbar->add(get_string('ilps', 'block_ilp'),$listurl,'title');
    $PAGE->navbar->add(get_string('ilpname', 'block_ilp'),$dashboardurl,'title');
} else {
    $PAGE->navbar->add(get_string('myilp', 'block_ilp'),$dashboardurl,'title');
}



// setup the navigation breadcrumbs


//user intials
$PAGE->navbar->add(fullname($plpuser),$userprofileurl,'title');

//section name
$PAGE->navbar->add(get_string('addextension','block_ilp'),null,'title');


// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/edit_report_preference.php', $PARSER->get_params());


require_once($CFG->dirroot.'/blocks/ilp/views/edit_report_preference.html');

?>
