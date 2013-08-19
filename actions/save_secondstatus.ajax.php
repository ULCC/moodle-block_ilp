<?php 

/**
 * Saves a change in a users status to the database  
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//get the id of the user that is currently being used
$student_id = $PARSER->required_param('student_id', PARAM_INT);

//get the changed status
$secondstatus_val		= $PARSER->required_param('secondstatus_val',PARAM_RAW);

// instantiate the db
$dbc = new ilp_db();

$secondstatus_userrecord = $dbc->get_secondstatus_userrecord('block_ilp_plu_wsts_ent', $student_id);
if ($secondstatus_userrecord) {
    $secondstatus_userrecord->value = $secondstatus_val;
    $dbc->update_secondstatus($secondstatus_userrecord);
}

$wname = $dbc->get_warning_status_name($secondstatus_val);
echo json_encode($wname);

?>
