<?php 

/**
 * Creates an entry for an report 
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

//get the id of the course that is currently being used
$user_id = $PARSER->required_param('user_id', PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);




// instantiate the db
$dbc = new ilp_db();


// setup the page title and heading
$user	=	$dbc->get_user_by_id($user_id);


$PAGE->set_title(fullname($user).': '.get_string('ilpname','block_ilp'));

$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/view_main.php",array('user_id'=>$user_id,'course_id'=>$course_id));

//get the enabled template
$temp	=	$dbc->get_enabled_template();


$classname	=	$temp->name;

//include the class file for the enabled template
require_once($CFG->dirroot."/blocks/ilp/classes/dashboard/templates/{$classname}.php");

$template	=	new $classname();



//require the view_main.html file
require_once($CFG->dirroot."/blocks/ilp/views/view_main.html");
?>
