<?php

/**
 * Changes the position of a field in a report 
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

//the id of the report  that the field will be in 
$report_id = $PARSER->required_param('report_id', PARAM_INT);

//the id of the reportfield used when editing
$reportfield_id = $PARSER->required_param('reportfield_id' ,PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

//change field required 


//get the field record
$reportfield =	$dbc->get_report_field_data($reportfield_id);

//if the report field is currently required set it to 0 not required and vice versa 
$reportfield->req	=	(empty($reportfield->req)) ? 1 : 0;

$resulttext = ($dbc->update_report_field($reportfield)) ? get_string("fieldreqsuc", 'block_ilp') : get_string("fieldreqerror", 'block_ilp');

$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_prompt.php?report_id='.$report_id;
redirect($return_url, $resulttext, ILP_REDIRECT_DELAY);

?>