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

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

// Include the report permissions file
require_once($CFG->dirroot.'/blocks/ilp/report_permissions.php');

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
$selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

//get the id the comment if one is being edited
$tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_INT);



$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/edit_entrycomment.php",array('report_id'=>$report_id,'user_id'=>$user_id,'course_id'=>$course_id,'entry_id'=>$entry_id,'comment_id'=>$comment_id,'selectedtab'=>$selectedtab,'tabitem'=>$tabitem));

// instantiate the db
$dbc = new ilp_db();

//get the report 
$report		=	$dbc->get_report_by_id($report_id);

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

if (empty($access_report_createreports))	{
	//the user doesnt have the capability to create this type of report entry
	print_error('userdoesnothavecreatecapability','block_ilp');	
}

if (empty($access_report_editreports))	{
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

//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back to dashboard
	$return_url = $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&selectedtab={$selectedtab}&tabitem={$tabitem}";
    redirect($return_url, '', REDIRECT_DELAY);
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
            $return_url = $CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&selectedtab={$selectedtab}&tabitem={$tabitem}";
        	redirect($return_url, get_string("commentcreationsuc", 'block_ilp'), REDIRECT_DELAY);
        }
    }
}


if (!empty($comment_id)) {
	
	$comment	=	$dbc->get_comment_by_id($comment_id);
	
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

$plpuser	=	$dbc->get_user_by_id($user_id);

// setup the navigation breadcrumbs
//block name
$PAGE->navbar->add(get_string('ilpname', 'block_ilp'),null,'title');

//user intials
$PAGE->navbar->add(fullname($plpuser),null,'title');

//section name
$PAGE->navbar->add(get_string('dashboard','block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&selectedtab={$selectedtab}&tabitem={$tabitem}",'title');

$title	=	(empty($comment_id))?	get_string('addcomment','block_ilp')	:	get_string('editcomment','block_ilp');

//section name
$PAGE->navbar->add($title);

$PAGE->set_title($title);
//require edit_reportentry html
require_once($CFG->dirroot.'/blocks/ilp/views/edit_entrycomment.html');
