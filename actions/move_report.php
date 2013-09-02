<?php

/**
 * Changes the position of a report
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
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//the id of the report  that the field will be in
$report_id = $PARSER->required_param('report_id', PARAM_INT);

//the id of the reportfield used when editing
$position = $PARSER->required_param('position' ,PARAM_INT);

//the id of the reportfield used when editing
$move = $PARSER->required_param('move' ,PARAM_INT);

$PAGE->set_url($CFG->wwwroot."/blocks/ilp/actions/move_report.php");

// instantiate the db
$dbc = new ilp_db();

//change field position 

$reports 	= 	$dbc->get_reports_by_position($position,$move, true, true, true);


$movesuc	=	true;

//loop through fields returned
if (!empty($reports)) {
	foreach($reports as $r) {

        $newposition = manage_position($r, $report_id, $move);

		if (!$dbc->set_new_report_position($r->id,$newposition)) $movesuc = false;
	}
} else {
	$movesuc	=	false;
}

$resulttext = (!empty($movesuc)) ? get_string("reportmovesuc", 'block_ilp') : get_string("reportmoveerror", 'block_ilp');

$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php';
redirect($return_url, $resulttext, ILP_REDIRECT_DELAY);

?>
