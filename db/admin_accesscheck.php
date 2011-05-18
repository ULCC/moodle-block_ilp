<?php
/**
 * Perfrorms permissions checks against the user to see what they are allowed to
 * do, which are stored as boolean values in local variables.
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

global $CFG, $PARSER,$USER;

// get the id of the course
$course_id = $PARSER->required_param('course_id', PARAM_INT);



// the user must be logged in
require_login(0, false);

//get the user context of the current user
$usercontext = get_context_instance(CONTEXT_USER, $USER->id);

//get the system context
$sitecontext = get_context_instance(CONTEXT_SYSTEM);


if (!$usercontext) {
    error("User ID is incorrect");
}

if (!has_capability('moodle/site:doanything', $sitecontext) || !has_capability('moodle/ilp:ilp:creeddelreport', $usercontext) ) {  // are we god ?
   print_error('incorrectcreatereportpermissions', 'block_ilp');
}
 

?>