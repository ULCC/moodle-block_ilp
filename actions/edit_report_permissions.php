<?php
/**
 * Previews a report to the user
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

//include the report entry preview mform class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_permissions_mform.php');

//get the id of the report that is currently in use
$report_id = $PARSER->required_param('report_id', PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

// setup the navigation breadcrumbs
//block name
$PAGE->navbar->add(get_string('blockname', 'block_ilp'),null,'title');

//section name
$PAGE->navbar->add(get_string('reportconfiguration', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php",'title');

//get string for create report
$PAGE->navbar->add(get_string('reportpermissions', 'block_ilp'),null,'title');

// setup the page title and heading
$PAGE->set_title(get_string('blockname','block_ilp'));
$PAGE->set_heading(get_string('reportconfiguration', 'block_ilp'));
$PAGE->set_url('/blocks/ilp/', $PARSER->get_params());

$blockcapabilities	=	$dbc->get_block_capabilities();

$report		=	$dbc->get_report_by_id($report_id);

$mform	=	new edit_report_permissions_mform($report_id);

//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back to report configuration page
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php?course_id='.$course_id;
    redirect($return_url, null, REDIRECT_DELAY);
}


//was the form submitted?
// has the form been submitted?
if($mform->is_submitted()) {
    // check the validation rules
    if($mform->is_validated()) {

        //get the form data submitted
    	$formdata = $mform->get_data();
    	    	 	
        // process the data
    	$success = $mform->process_data($formdata);

    	//if saving the data was not successful
        if(!$success) {
			//print an error message	
            print_error(get_string("reportpermissionserror", 'block_ilp'), 'block_ilp');
        }
        //if the report_id ahs not already been set
        $report_id	= (empty($report_id)) ? $success : $report_id;
        
        //return the user to the report configuration page
        $return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php?course_id='.$course_id;
        redirect($return_url, get_string("reportpermissionsuc", 'block_ilp',$report), REDIRECT_DELAY);
    }
}


$reportpermissions		=	$dbc->get_report_permissions($report_id);

if (!empty($reportpermissions)) {
	//get the form variables and
	$rp		=	reportformpermissions($reportpermissions);
	
	$mform->set_data($rp);
}




require_once($CFG->dirroot.'/blocks/ilp/views/edit_report_permissions.html');
?>
