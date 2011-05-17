<?php
/**
 * Perfrorms permissions checks against the user to see what they are allowed to
 * do, which are stored as boolean values in local variables.
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
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


// get the current course context
$coursecontext = get_context_instance(CONTEXT_COURSE, $course_id);

// bail if we couldn't find the course context
if(!$coursecontext) {
    print_error('incorrectcourseid', 'block_ilp');
}

