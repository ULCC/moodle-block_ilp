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

require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//if set get the id of the report
$report_id	= $PARSER->required_param('report_id',PARAM_INT);


//get the id of the course that is currently being used
$user_id = $PARSER->required_param('user_id', PARAM_INT);


//if set get the id of the report entry to be edited
$entry_id	= $PARSER->optional_param('entry_id',NULL,PARAM_INT);

//get the id of the course that is currently being used
$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

//get the current page variable if it exists
$currentpage    =   optional_param('current_page',1,PARAM_INT);

//unset the current page variable otherwise moodleform will take it and use it in the
//in the current form (which will overwrite any changes we make to the current page element)
unset($_POST['current_page']);

$page_data        =   optional_param('page_data',0,PARAM_RAW);

//is there a next page button param?
$nextpressed        =   optional_param('nextbutton',0,PARAM_RAW);

//is there a previous page button param?
$previouspressed    =   optional_param('previousbutton',0,PARAM_RAW);

$processing    =   optional_param('processing',0,PARAM_INT);

$editing    =   optional_param('editing',0,PARAM_INT);

$selectedtab    =   optional_param('selectedtab',null,PARAM_TEXT);

$selectedtab    =   optional_param('selectedtab',null,PARAM_TEXT);

$tabitem    =   optional_param('tabitem',null,PARAM_INT);


// instantiate the db
$dbc = new ilp_db();

//get the report
$report		=	$dbc->get_report_by_id($report_id);

$access_report_createreports = $report->has_cap($USER->id,$PAGE->context,'block/ilp:addreport');
$access_report_editreports = $report->has_cap($USER->id,$PAGE->context,'block/ilp:editreport');
$access_viewotherilp = $report->has_cap($USER->id,$PAGE->context,'block/ilp:viewotherilp');

//if the report is not found throw an error of if the report has a status of disabled
if (empty($report) || empty($report->status) || !empty($report->deleted)) {
	print_error('reportnotfouund','block_ilp');
}


//check if the any of the users roles in the
//current context has the create report capability for this report

if (empty($access_report_createreports))	{
	//the user doesnt have the capability to create this type of report entry

	print_error('userdoesnothavecreatecapability','block_ilp');
}


if (!empty($entry_id))	{
	if (empty($access_report_editreports))	{
		//the user doesnt have the capability to edit this type of report entry

		print_error('userdoesnothavedeletecapability','block_ilp');
	}
}

$reportfields		=	$dbc->get_report_fields_by_position($report_id);

//we will only attempt to display a report if there are elements in the
//form. if not we will send the user back to the dashboard
if (empty($reportfields)) {
	//send the user back to the dashboard page telling them that the report is not ready for display
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/view_main.php?user_id='.$user_id.'&course_id='.$course_id;
    redirect($return_url, get_string("reportnotready", 'block_ilp'), ILP_REDIRECT_DELAY);
}

//require the reportentry_mform so we can display the report
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/reportentry_mform.php');

//The page_data element is part of all forms if it is not found and there is a session var for this report
//then it must be for old data unset it
if (empty($page_data) && isset($SESSION->pagedata[$report_id])) unset($SESSION->pagedata[$report_id]);

//if the next button has been pressed increment the page number by 1
if (!empty($nextpressed))   {
    $currentpage++;
}

//if the previous button has been pressed decrease the page number by 1
if (!empty($previouspressed))   {
    $currentpage--;
}

$jsarguments = array();

$jsmodule = array(
    'name'     	=> 'ilp_edit_reportentry',
    'fullpath' 	=> '/blocks/ilp/views/js/edit_reportentry.js',
    'requires'  	=> array('event','dom','node','io-form','anim-base','anim-xy','anim-easing','anim', 'node-event-simulate')
);

$PAGE->requires->js_init_call('M.ilp_edit_reportentry.init', $jsarguments, true, $jsmodule);

$mform	= new	report_entry_mform($report_id,$user_id,$entry_id,$course_id, $currentpage);

//was the form cancelled?
if ($mform->is_cancelled()) {
	//send the user back to dashboard
	$return_url = $CFG->wwwroot.'/blocks/ilp/actions/view_main.php?course_id='.$course_id.'&user_id='.$user_id;
    redirect($return_url, '', ILP_REDIRECT_DELAY);
}


//was the form submitted?
// has the form been submitted?
if($mform->is_submitted()) {
    // check the validation rules
    //the server side validation checks have been taken out as they stop multipage forms from working I will reimplement
    //TODO: reimplement validation
   // if($mform->is_validated()) {


        //call the next function which will carry out the necessary actions if the next button was pressed
        $mform->next($report_id,$currentpage);

        //call the previous function which will carry out the necessary actions if the next button was pressed
        $mform->previous($report_id,$currentpage);

        /*
        $temp   =   new stdClass();
        $temp->currentpage  =   $currentpage;
        $mform->set_data($temp);
        */

        //get the form data submitted
        $formdata = $mform->get_multipage_data($report_id);


        if (isset($formdata->submitbutton))   {

            // process the data
            $success = $mform->submit($report_id);

            //if saving the data was not successful
            if(!$success) {
                //print an error message
                print_error(get_string("entrycreationerror", 'block_ilp'), 'block_ilp');
            }

            //we no longer need the form information for this page
            unset($SESSION->pagedata[$report_id]);

        }

    require_once($CFG->dirroot . '/blocks/ilp/plugins/tabs/ilp_dashboard_reports_tab.php');
    require_once($CFG->dirroot . '/blocks/ilp/classes/ilp_report_rules.class.php');
    $ilp_dashboard_reports_tab_instance = new ilp_dashboard_reports_tab($user_id, $course_id);
    $ajax_settings = array(
        'return_only_newest_entry' => true
    );
    if ($editing) {
        $ajax_settings['return_only_newest_entry'] = false;
        $ajax_settings['return_left_reports_for_single_entry'] = $entry_id;
        $left_report = $ilp_dashboard_reports_tab_instance->display($selectedtab, $ajax_settings, null, true, $tabitem, $report_id);
        unset($ajax_settings['return_left_reports_for_single_entry']);
        $ajax_settings['return_right_reports_for_single_entry'] = $entry_id;
        $right_report = $ilp_dashboard_reports_tab_instance->display($selectedtab, $ajax_settings, null, true, $tabitem, $report_id);
        $toreturn = array('left_report' => $left_report, 'right_report' => $right_report);
        echo json_encode($toreturn);
        exit;
    } else {
        $toreturn = $ilp_dashboard_reports_tab_instance->display($selectedtab, $ajax_settings, null, true, $tabitem, $report_id);
        echo json_encode($toreturn);
    }

}

$expired=false;
if (!empty($entry_id)) {
	//create a entry_data object this will hold the data that will be passed to the form
	$entry_data		=	new stdClass();

	//get the main entry record
	$entry	=	$dbc->get_entry_by_id($entry_id);

	if (!empty($entry)) 	{
       //check if the maximum edit field has been set for this report
       if (!empty($report->maxedit))
       {
          file_put_contents('ajax.log',print_r($report,true));
          //calculate the age of the report entry
          $expired=((time()-$entry->timecreated)>$CFG->maxeditingtime);
       }

		//get all of the fields in the current report, they will be returned in order as
		//no position has been specified
		$reportfields		=	$dbc->get_report_fields_by_position($report_id);

		foreach ($reportfields as $field) {
			//get the plugin record that for the plugin
			$pluginrecord	=	$dbc->get_plugin_by_id($field->plugin_id);

			//take the name field from the plugin as it will be used to call the instantiate the plugin class
			$classname = $pluginrecord->name;

			// include the class for the plugin
			include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

			if(!class_exists($classname)) {
			 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
			}

			//instantiate the plugin class
			$pluginclass	=	new $classname();

			$pluginclass->load($field->id);

			//create the fieldname
			$fieldname	=	$field->id."_field";

			$pluginclass->load($field->id);

			//call the plugin class entry data method
			$pluginclass->entry_data($field->id,$entry_id,$entry_data);
		}

		//loop through the plugins and get the data for each one
		$mform->set_data($entry_data);
	}
}

$plpuser	=	$dbc->get_user_by_id($user_id);

$dashboardurl	=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&course_id={$course_id}";
$userprofileurl	=	$CFG->wwwroot."/user/profile.php?id={$user_id}";
if ($user_id != $USER->id) {
	if (!empty($access_viewotherilp) && !empty($course_id)) {
		$listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$course_id}";
	} else {
		$listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
	}

	$PAGE->navbar->add(get_string('ilps', 'block_ilp'),$listurl,'title');
	$PAGE->navbar->add(get_string('ilpname', 'block_ilp'),$dashboardurl,'title');
} else {
	$PAGE->navbar->add(get_string('myilp', 'block_ilp'),$dashboardurl,'title');
}

//user intials
$PAGE->navbar->add(fullname($plpuser),$userprofileurl,'title');

//section name
$PAGE->navbar->add($report->name,null,'title');

$titleprefix	=	 (!empty($entry_id)) ? get_string('edit') : get_string('add');

require_once($CFG->dirroot . '/blocks/ilp/plugins/tabs/ilp_dashboard_reports_tab.php');
$ilp_dashboard_reports_tab_instance = new ilp_dashboard_reports_tab($user_id, $report_id);

ob_start();

if($expired or !$report->report_availabilty($user_id))
{
   $mform->expired();
}

$mform->display();

echo $ilp_dashboard_reports_tab_instance->get_loader_icon('ajaxloadicon-addingnewentrywrapper', 'span');

$formhtml = ob_get_clean();

// AJAX Includes for normal mform Javascript code
// ... First we get the script generated by the Form API

if (strpos($formhtml, '</script>') !== false) {
    $outputparts = explode('</script>', $formhtml);
    $html = $outputparts[1];
    $script = str_replace('<script type="text/javascript">', '', $outputparts[0]);
} else {
    $html = $formhtml;
    $script = '';
}
// Next we get the M.yui.loader call which includes the Javascript libraries
$headcode = $PAGE->requires->get_head_code($PAGE, $OUTPUT);
$loadpos = strpos($headcode, 'M.yui.loader');
$cfgpos = strpos($headcode, 'M.cfg');

$script .= substr($headcode, $loadpos, $cfgpos-$loadpos);
// And finally the initalisation calls for those libraries
$endcode = $PAGE->requires->get_end_code();
$script .= preg_replace('/<\/?(script|link)[^>]*>/', '', $endcode);

if (!$processing) {
    echo json_encode(array('html' => $formhtml, 'script' => $script));
}

?>
