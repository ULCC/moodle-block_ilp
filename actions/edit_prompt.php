<?php 

/**
 * Allows the user to create and edit prompts 
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
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/add_prompt_mform.php');

//get the id of the course that is currently being used
$course_id = $PARSER->required_param('course_id', PARAM_INT);

//get the id of the report that is currently in use
$report_id = $PARSER->required_param('report_id', PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

$course	=	$dbc->get_course($course_id);

//set the required level of permission needed to view this page

//

// setup the navigation breadcrumbs
//block name
$PAGE->navbar->add(get_string('blockname', 'block_ilp'),null,'title');

//section name
$PAGE->navbar->add(get_string('reportconfiguration', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php?course_id={$course_id}",'title');

//get string for create report
$PAGE->navbar->add(get_string('reportfields', 'block_ilp'),null,'title');

// setup the page title and heading
$PAGE->set_title($course->shortname.': '.get_string('blockname','block_ilp'));
$PAGE->set_heading($course->fullname);
$PAGE->set_url('/blocks/ilp/', $PARSER->get_params());

$promptmform	= new	add_prompt_mform($course_id,$report_id);

// has the form been submitted?
if($promptmform->is_submitted()) {
	//get the form data submitted
	$formdata = $promptmform->get_data();
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_field.php?report_id='.$report_id.'&course_id='.$course_id.'&plugin_id='.$formdata->plugin_id;
    redirect($return_url, get_string("addfield", 'block_ilp'), REDIRECT_DELAY);
}


$previewreporturl	= "{$CFG->wwwroot}/blocks/ilp/actions/report_entry_preview.php?course_id={$course_id}&report_id={$report_id}";

require_once($CFG->dirroot.'/blocks/ilp/views/edit_prompt.html');

?>