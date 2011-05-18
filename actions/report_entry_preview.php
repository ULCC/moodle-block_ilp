<?php

/**
 * Previews a report to the user
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
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//include the report entry preview mform class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/report_entry_preview_mform.php');

//get the id of the course that is currently being used
$course_id = $PARSER->required_param('course_id', PARAM_INT);

//get the id of the report that is currently in use
$report_id = $PARSER->required_param('report_id', PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

$course	=	$dbc->get_course($course_id);

// setup the navigation breadcrumbs
//block name
$PAGE->navbar->add(get_string('blockname', 'block_ilp'),null,'title');


//course shortname
$PAGE->navbar->add($course->shortname,null,'title');

//get string for create report
$PAGE->navbar->add(get_string('createreport', 'block_ilp'),null,'title');

// setup the page title and heading
$PAGE->set_title($course->shortname.': '.get_string('blockname','block_ilp'));
$PAGE->set_heading($course->fullname);
$PAGE->set_url('/blocks/ilp/', $PARSER->get_params());


$reportfields		=	$dbc->get_report_fields_by_position($report_id);



//we will only attempt to display the preview form if there are elements in the 
//form. if not we will send the user back to the edit_prompt page
if (empty($reportfields)) {
	//send the user back to the edit_prompt.php page telling them that the report must contain fields
	//before it may be previewed
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_prompt.php?report_id='.$report_id.'&course_id='.$course_id;
    redirect($return_url, get_string("reportmustcontainfields", 'block_ilp'), REDIRECT_DELAY);
} 

$mform	= new	report_entry_preview_mform($course_id,$report_id);

$editreporturl = "{$CFG->wwwroot}/blocks/ilp/actions/edit_report.php?course_id={$course_id}&report_id={$report_id}";
$editfieldsurl = "{$CFG->wwwroot}/blocks/ilp/actions/edit_prompt.php?course_id={$course_id}&report_id={$report_id}";
$editpermissionsurl = "{$CFG->wwwroot}/blocks/ilp/actions/edit_report_permissions.php?course_id={$course_id}&report_id={$report_id}";

require_once($CFG->dirroot.'/blocks/ilp/views/report_entry_preview.html');
?>