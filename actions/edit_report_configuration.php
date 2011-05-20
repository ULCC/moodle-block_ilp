<?php 

/**
 * Allows the user to create and edit reports 
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
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//include the report form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_mform.php');


//get the id of the course that is currently being used
$course_id = $PARSER->required_param('course_id', PARAM_INT);

//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);	;


// instantiate the db
$dbc = new ilp_db();

//instantiate the edit_report_mform class
$mform	=	new edit_report_mform($course_id,$report_id);


$course	=	$dbc->get_course($course_id);


//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back
	
	
	
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
            print_error(get_string("reportcreationerror", 'block_ilp'), 'block_ilp');
        }

        //if the report_id ahs not already been set
        $report_id	= (empty($report_id)) ? $success : $report_id;
        
        //decide whether the user has chosen to save and exit or save or display
        if (!isset($formdata->saveanddisplaybutton)) { 
            //return the user to the 
        	$return_url = $CFG->wwwroot.'/course/view.php?id='.$course_id;
        	redirect($return_url, get_string("reportcreationsuc", 'block_ilp'), REDIRECT_DELAY);
        } else {
        	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/edit_prompt.php?report_id='.$report_id.'&course_id='.$course_id;
        	redirect($return_url, get_string("reportcreationsuc", 'block_ilp'), REDIRECT_DELAY);
        }
    }
}


//set the page title
$pagetitle	=	(empty($report_id)) ? get_string('createreport', 'block_ilp') : get_string('editreport', 'block_ilp');


if (!empty($report_id)) {
	$reportrecord	=	$dbc->get_report_by_id($report_id);
	$mform->set_data($reportrecord);
} 



// setup the navigation breadcrumbs
//block name
$PAGE->navbar->add(get_string('blockname', 'block_ilp'),null,'title');

//section name
$PAGE->navbar->add(get_string('reportconfiguration', 'block_ilp'),$CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php?course_id={$course_id}",'title');


// setup the page title and heading
$PAGE->set_title($course->shortname.': '.get_string('blockname','block_ilp'));
$PAGE->set_heading($course->fullname);
$PAGE->set_url('/blocks/ilp/', $PARSER->get_params());


require_once ($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin.php');

//install new plugins
ilp_element_plugin::install_new_plugins();

require_once($CFG->dirroot.'/blocks/ilp/views/edit_report_configuration.html');

?>
