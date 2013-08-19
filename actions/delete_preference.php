<?php

/**
 * Deletes a report field from a report 
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

//the id of the report  that the field will be in 
$report_id = $PARSER->required_param('report_id', PARAM_INT);

//the id of the reportfield used when editing
$user_id = $PARSER->required_param('user_id' ,PARAM_INT);

//the id of the reportfield used when editing
$pref_id = $PARSER->required_param('pref_id' ,PARAM_INT);

//the id of the reportfield used when editing
$course_id = $PARSER->optional_param('course_id',null ,PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//check if the report field was found

if ($dbc->delete_record('block_ilp_preferences',array('id'=>$pref_id))) {
    $resulttext =   get_string('deletesuccess','block_ilp');
}	else {
    $resulttext	=	get_string('deleteerror','block_ilp');
}


$return_url = $CFG->wwwroot."/blocks/ilp/actions/view_extensionlist.php?report_id={$report_id}&user_id={$user_id}&course_id={$course_id}";
redirect($return_url, $resulttext, ILP_REDIRECT_DELAY);

