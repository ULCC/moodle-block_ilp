<?php
/**
 * Allows the user to print a list of students' reports
 *
 * @copyright &copy; 2013 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../lib.php');
//include any necessary files
$allow_batch_print = get_config('block_ilp', 'allow_batch_print');

if ($allow_batch_print === '0') {
    print_error(get_string('batch_print_has_been_disabled', 'block_ilp'));
}

$jsmodule = array(
   'name'     	=> 'ilp_view_print_preview',
   'fullpath' 	=> '/blocks/ilp/views/js/print_preview.js',
   'requires'  => array('event','dom','node','io-form','anim-base','anim-xy','anim-easing','anim')
);

$PAGE->requires->js_init_call('M.ilp_view_print_preview.init', array(get_string('any')), true, $jsmodule);

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

require_once("$CFG->dirroot/blocks/ilp/classes/forms/batch_print_setup_mform.php");

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

$baseurl = new moodle_url($CFG->wwwroot."/blocks/ilp/actions/define_batch_print.php",$PARSER->get_params());

$mform=new batch_print_setup_mform($baseurl->out(false),array('course_id'=>optional_param('course_id',0,PARAM_INT),
                                                              'tutor'=>optional_param('tutor',0,PARAM_INT),
                                                              'group_id'=>optional_param('group_id',0,PARAM_INT),
                                                              'status_id'=>optional_param('status_id',0,PARAM_INT)));

if($mform->is_cancelled())
{
   redirect("$CFG->wwwroot");
}
elseif($data=$mform->get_data())
{
   ($tutor and $usertutees=$dbc->get_user_tutees($USER->id));
//Only possible if url has been twiddled, so slap them back to the front page
   if(empty($data->course_id) and !$usertutees)
   {
      redirect($CFG->wwwroot);
   }

   include("$CFG->dirroot/blocks/ilp/views/print_preview.php");
   exit;
}

//check if the any of the users roles in the
//current context has the create report capability for this report

if (empty($access_viewotherilp)  && !empty($course_id)) {
   //the user doesnt have the capability to create this type of report entry
   print_error('userdoesnothavecapability','block_ilp');
}

//check if any tutess exist

// setup the navigation breadcrumbs
if (!empty($course_id)) {
   $listurl="{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$course_id}";
} else {
   $listurl="{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
}

//add the page title
$PAGE->navbar->add(get_string('ilps','block_ilp'),$listurl,'title');

//add the page title
$title = get_string('print','block_ilp');

//block name
$PAGE->navbar->add($title,null,'title');

// setup the page title and heading

$SITE = $dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-reportlist');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url($baseurl);

require_once($CFG->dirroot.'/blocks/ilp/views/batch_print.html');