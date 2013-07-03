<?php
/**
 * Allows the user to view a list of students
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any necessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//get the id of the course that is currently being used
$course_id  = $PARSER->optional_param('course_id', 0, PARAM_INT);

//get the tutor flag
$tutor = $PARSER->optional_param('tutor', 0, PARAM_RAW);

//get the status_id if set
$status_id = $PARSER->optional_param('status_id', 0, PARAM_INT);

//get the group if set
$group_id = $PARSER->optional_param('group_id', 0, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//check if the any of the users roles in the
//current context has the create report capability for this report

if (empty($access_viewotherilp)  && !empty($course_id)) {
   //the user doesnt have the capability to create this type of report entry
   print_error('userdoesnothavecapability','block_ilp');
}

//check if any tutess exist

// setup the navigation breadcrumbs

//add the page title
$PAGE->navbar->add(get_string('print'),"{$CFG->wwwroot}/blocks/ilp/actions/define_batch_print.php",'title');

$title = get_string('print');

//block name
$PAGE->navbar->add($title,null,'title');

// setup the page title and heading
$SITE = $dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('ilp');
$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/define_batch_print.php",$PARSER->get_params());

$baseurl = $CFG->wwwroot."/blocks/ilp/actions/define_batch_print.php";

require_once($CFG->dirroot.'/blocks/ilp/views/batch_print.html');
