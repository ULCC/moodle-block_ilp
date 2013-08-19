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

global $CFG, $PARSER,$USER,$PAGE;

// the user must be logged in
require_login(0, false);

//get the user context of the current user
$usercontext = context_user::instance($USER->id);

//get the system context
$sitecontext = context_system::instance();

//if there is no user context then throw an error
if (!$usercontext) {
    print_error("incorrectuserid",'block_ilp');
}

//make sure that the user has the ability to manipulate reports if not throw an error
if (!has_capability('block/ilp:creeddelreport', $usercontext) ) {
    print_error('incorrectcreatereportpermissions', 'block_ilp');
}

//TODO: we will should not be in the course context change to another context
$PAGE->set_context($sitecontext);
?>