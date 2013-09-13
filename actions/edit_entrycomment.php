<?php
/**
 * Creates a comment on a report entry
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE, $DB;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//if set get the id of the report
$report_id	= $PARSER->required_param('report_id',PARAM_INT);	

//get the id of the user that the comment relates to
$user_id = $PARSER->required_param('user_id', PARAM_INT);

//if set get the id of the report entry 
$entry_id	= $PARSER->required_param('entry_id',PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$comment_id = $PARSER->optional_param('comment_id', NULL, PARAM_INT);

//get the id the comment if one is being edited
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);

//get the id the comment if one is being edited
$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);

$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/edit_entrycomment.php",array('report_id'=>$report_id,'user_id'=>$user_id,'course_id'=>$course_id,'entry_id'=>$entry_id,'comment_id'=>$comment_id,'selectedtab'=>$selectedtab,'tabitem'=>$tabitem));

// instantiate the db
$dbc = new ilp_db();

//get the report 
$report		=	$dbc->get_report_by_id($report_id);

$access_report_addcomment = $report->has_cap($USER->id,$PAGE->context,'block/ilp:addcomment');
$access_report_editcomment = $report->has_cap($USER->id,$PAGE->context,'block/ilp:editcomment');
$access_viewotherilp = $report->has_cap($USER->id,$PAGE->context,'block/ilp:viewotherilp');

//if the report is not found throw an error of if the report has a status of disabled
if (empty($report) || empty($report->status)) {
	print_error('reportnotfouund','block_ilp');
}

//get the report entry 
$entry		=	$dbc->get_entry_by_id($entry_id);

//if the report entry is not found throw an error 
if (empty($entry) ) {
	print_error('entrynotfouund','block_ilp');
}


//check if the any of the users roles in the 
//current context has the create report capability for this report

if (empty($comment_id) && empty($access_report_addcomment))	{
	//the user doesnt have the capability to create a comment
	print_error('userdoesnothavecreatecapability','block_ilp');	
}

if (!empty($comment_id) && empty($access_report_editcomment))	{
	//the user doesnt have the capability to edit this type of report entry
	print_error('userdoesnothaveeditcapability','block_ilp');	
}	

if (empty($report->comments))	{
	//the current report does not allow comments
	print_error('commentsnotallowed','block_ilp');	
}


//require the entrycomment_mform so we can display the report
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_entrycomment_mform.php');


$mform	= new	edit_entrycomment_mform($report_id,$entry_id,$user_id,$course_id,$comment_id,$selectedtab,$tabitem);
/*
if (!empty($comment_id)) {

    $comment	=	$dbc->get_comment_by_id($comment_id);
    //var_dump($comment);
    if (!empty($comment)) {
        //only the creator has the right to edit
        if ($comment->creator_id == $USER->id) {
            //set the form values to the current comment
            $mform->set_data($comment);
        } else {
            print_error('commentmayonlybeeditedbyowner','block_ilp');
        }
    } else {
        print_error('commentnotfound','block_ilp');
    }
}
*/


//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back to dashboard
    $return_url = $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&selectedtab={$selectedtab}&tabitem={$tabitem}&course_id={$course_id}";
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
            print_error('commentcreationerror', 'block_ilp');
        }

        if (!isset($formdata->saveanddisplaybutton)) {

           //notify the user that a comment has been made on one of their report entries
            if ($USER->id != $entry->user_id)   {
                $reportsviewtab             =   $dbc->get_tab_plugin_by_name('ilp_dashboard_reports_tab');
                $reportstaburl              =   (!empty($reportsviewtab)) ?  "&selectedtab={$reportsviewtab->id}&tabitem={$reportsviewtab->id}:{$report->id}" : "";

                $message                    =   new stdClass();
                $message->component         =   'block_ilp';
                $message->name              =   'ilp_comment';
                $message->subject           =   get_string('newreportcomment','block_ilp',$report);;
                $message->userfrom          =   $dbc->get_user_by_id($USER->id);
                $message->userto            =   $dbc->get_user_by_id($entry->user_id);
                $message->fullmessage       =   get_string('newreportcomment','block_ilp',$report);
                $message->fullmessageformat =   FORMAT_PLAIN;
                $message->contexturl        =   $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$entry->user_id}&course_id={$course_id}{$reportstaburl}";
                $message->contexturlname    =   get_string('viewreport','block_ilp');

                message_send($message);

            }

            $return_url = $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&selectedtab={$selectedtab}&tabitem={$tabitem}&course_id={$course_id}";
        	redirect($return_url, get_string("commentcreationsuc", 'block_ilp'), ILP_REDIRECT_DELAY);
        }
    }
}


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

//user intials
$PAGE->navbar->add(fullname($plpuser),$userprofileurl,'title');




//section name
$PAGE->navbar->add(get_string('dashboard','block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&selectedtab={$selectedtab}&tabitem={$tabitem}",'title');

//user intials
$PAGE->navbar->add($report->name,null,'title');


$title	=	(empty($comment_id))?	get_string('addcomment','block_ilp')	:	get_string('editcomment','block_ilp');

// setup the page specific variables
// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp')." : ".fullname($plpuser));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-entry');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/edit_entrycomment.php",$PARSER->get_params());
//section name
$PAGE->navbar->add($title);

//require edit_reportentry html
//require_once($CFG->dirroot.'/blocks/ilp/views/edit_entrycomment.html');
// removed unnecessary extra headaches

echo $OUTPUT->header();

echo '<div class="ilp yui-skin-sam">';

//render the form
$mform->display();
echo '</div>';

echo $OUTPUT->footer();

