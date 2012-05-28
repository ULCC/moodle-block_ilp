<?php
/**
 * Ajax file for view_students
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $OUTPUT;

// Meta includes
require_once($CFG->dirroot . '/blocks/ilp/actions_includes.php');

//include the default class
require_once($CFG->dirroot . '/blocks/ilp/classes/tables/ilp_hiddenrow_ajax_table.class.php');

//get the id of the course that is currently being used if set
$report_id = $PARSER->required_param('report_id',  PARAM_INT);

//get the id of the course that is currently being used if set
$course_id = $PARSER->optional_param('course_id', 0, PARAM_INT);

//get the tutor flag
$tutor = $PARSER->optional_param('tutor', 0, PARAM_INT);

//get the status_id if set
$status_id = $PARSER->optional_param('status_id', 0, PARAM_INT);

//get the group_id if set
$group_id = $PARSER->optional_param('group_id', 0, PARAM_INT);

//get the status_id if set
$state_id = $PARSER->optional_param('state_id', 0, PARAM_INT);

//get the deadline_id if set
$deadline_id    =	$PARSER->optional_param('deadline_id', 0, PARAM_INT);

//get the summary param if set
$displaysummary  =	$PARSER->optional_param('summary', 0, PARAM_INT);

//display user entries
$displayuserentries  =	$PARSER->optional_param('userentries', 1, PARAM_INT);

//display non user entries
$displaynonuserentries  =	$PARSER->optional_param('nonuserentries', 1, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

// set up the flexible table for displaying the portfolios
$flextable = new ilp_hiddenrow_ajax_table("student_listcourse_id{$course_id}tutor{$tutor}status_id{$status_id}report_id{$report_id}");

$flextable->define_baseurl($CFG->wwwroot . "/blocks/ilp/actions/view_studentreports.php?report_id={$report_id}&course_id={$course_id}&tutor={$tutor}&status_id={$status_id}&group_id={$group_id}");
$flextable->define_ajaxurl($CFG->wwwroot . "/blocks/ilp/actions/view_studentreports.ajax.php?report_id={$report_id}&course_id={$course_id}&tutor={$tutor}&status_id={$status_id}&group_id={$group_id}");

// set the basic details to dispaly in the table
$headers = array(
    get_string('userpicture', 'block_ilp'),
    get_string('name', 'block_ilp'),
    get_string('status', 'block_ilp')
);

$columns = array('picture', 'fullname', 'u_status');

$headers[] = '';
$columns[] = 'view';
$nosorting = array('picture', 'u_status','view');


//get all enabled reports in this ilp
$reports = $dbc->get_reports(ILP_ENABLED);

//get the mamximum reports that can be displayed on the screen in the list
$maxreports = get_config('block_ilp', 'ilp_max_reports');

//check if maxreports is empty if yes then set to 
$maxreports = (!empty($maxreports)) ? $maxreports : ILP_DEFAULT_LIST_REPORTS;

//set the number of report columns to display

//removed as we no longer need the horizonatal scrolling
//$reports	=	$flextable->limitcols($reports,$maxreports);

$report        =            $dbc->get_report_by_id($report_id);
//we are going to create headers and columns for all enabled reports 
$headers[] = $report->name;
$columns[] = $report_id;
$nosorting[] = $report_id;


$flextable->hoz_string = 'displayingreports';



$flextable->define_fragment('studentlist');
$flextable->collapsible(true);
//define the columns and the headers in the flextable
$flextable->define_columns($columns);
$flextable->define_headers($headers);

$flextable->column_nosort = $nosorting;
$flextable->sortable(true, 'lastname', 'DESC');
$flextable->set_attribute('summary', get_string('studentslist', 'block_ilp'));
$flextable->set_attribute('cellspacing', '0');
$flextable->set_attribute('class', 'generaltable fit');
$flextable->set_attribute('id', "student_listcourse_id={$course_id}tutor={$tutor}status_id={$status_id}report_id={$report_id}");


$flextable->initialbars(true);

$flextable->setup();

if (!empty($course_id)) {
    $users = $dbc->get_course_users($course_id,$group_id);  
} else {
    $users = $dbc->get_user_tutees($USER->id);
}

$students = array();

foreach ($users as $u) {
    $students[] = $u->id;
}

$notstatus_ids = false;

if (!empty($status_id)) {

    $defaultstatusid = get_config('block_ilp', 'defaultstatusitem');

    if ($defaultstatusid == $status_id) {
        $notstatus_ids = true;
    }
}

//we only want to get the student matrix if students have been provided
$studentslist = (!empty($students)) ? $dbc->get_students_matrix($flextable, $students, $status_id, $notstatus_ids)
        : false;
//get the default status item which will be used as the status for students who
//have not entered their ilp and have not had a status assigned
$defaultstatusitem_id = get_config('block_ilp', 'defaultstatusitem');

//get the status item record
$defaultstatusitem = $dbc->get_status_item_by_id($defaultstatusitem_id);


$status_item = (!empty($defaultstatusitem)) ? $defaultstatusitem->name : get_string('unknown', 'block_ilp');

//this is needed if the current user has capabilities in the course context, it allows view_main page to view the user
//in the course context
$course_param = (!empty($course_id)) ? "&course_id={$course_id}" : '';

$coursearg = ( $course_id ) ? "&course=$course_id" : '' ;

$dontdisplay    =   array();

//get all report fields for this report
$reportfields		=	$dbc->get_report_fields_by_position($report_id);

//does this report allow users to say it is related to a particular course
$has_courserelated	=	(!$dbc->has_plugin_field($report_id,'ilp_element_plugin_course')) ? false : true;
$has_statefield     =   (!$dbc->has_plugin_field($report_id,'ilp_element_plugin_state')) ? false : true;
$has_deadline       =   (!$dbc->has_plugin_field($report_id,'ilp_element_plugin_date_deadline'))  ?  false : true  ;

if (!empty($has_courserelated))	{
    $courserelated	=	$dbc->has_plugin_field($report_id,'ilp_element_plugin_course');
    //the should not be anymore than one of these fields in a report
    foreach ($courserelated as $cr) {
        $dontdisplay[] 	=	$cr->id;
        $courserelatedfield_id	=	$cr->id;
    }
}

//get all report fields for this report
$reportfields		=	$dbc->get_report_fields_by_position($report_id);


if (!empty($studentslist)) {
    foreach ($studentslist as $student) {
        $data = array();
        $hiddenrowdata = array();


        $userprofile	=	(stripos($CFG->release,"2.") === false) ? 'view.php' : 'profile.php';
                
        $data['picture'] = $OUTPUT->user_picture($student, array('return' => true, 'size' => 50));
        $data['fullname'] = "<a href='{$CFG->wwwroot}/user/{$userprofile}?id={$student->id}{$coursearg}' class=\"userlink\">" . fullname($student) . "</a>";
        //if the student status has been set then show it else they have not had there ilp setup
        //thus there status is the default
        $data['u_status'] = (!empty($student->u_status)) ? $student->u_status : $status_item;

        $data['view'] = "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$student->id}{$course_param}' >" . get_string('viewilp', 'block_ilp') . "</a>";

        $temp   =   new stdClass();
        $temp->entries = $dbc->count_report_entries($report_id, $student->id);

        $data[$report_id] = (empty($temp->entries)) ? get_string('numberentries', 'block_ilp',$temp) : "<div id='row{$report_id}{$student->id}' class='entry_toggle'>".get_string('numberentries', 'block_ilp',$temp)."</div>";

        if (!empty($displayuserentries) && empty($displaynonuserentries))  {
            $createdby  =   ILP_CREATED_BY_USER;
        } else if (empty($displayuserentries) && !empty($displaynonuserentries))  {
            $createdby  =   ILP_NOTCREATED_BY_USER;
        } else {
            $createdby  =   null;
        }

        //get all of the entries for this report
        $reportentries	=	$dbc->get_user_report_entries($report_id,$student->id,$state_id,$createdby);

        //if the report has a state field and the user has chosen to show reports with a particular state
        //and the student has no reports with this state continue as we will not show them.
        if ($has_statefield && !empty($state_id) && empty($reportentries)) {
               continue;
        }   else if ($has_statefield && $has_deadline && !empty($deadline_id) ) {
            //see if any of the reports have the deadline state set

            $deadlinestate  = ($deadline_id == 1) ? ILP_STATE_UNSET : ILP_STATE_PASS;

            $entry_ids =array();
            foreach($reportentries as $re)  {
                $entry_ids[]    =   $re->id;
            }
            $reportentries  =   $dbc->get_deadline_entries($report_id,$student->id,time(),$deadlinestate,$entry_ids);

            if ( empty($reportentries)) continue;
        }



        $temp   =   new stdClass();
        $temp->entries = count($reportentries);

        $data[$report_id] = (empty($temp->entries)) ? get_string('numberentries', 'block_ilp',$temp) : "<div id='row{$report_id}{$student->id}' class='entry_toggle'>".get_string('numberentries', 'block_ilp',$temp)."</div>";

        $reportentry    =  "";

        if (!empty($reportentries)) {
            $reportentry    .=   '<div class="left-reports,hidden-entry"  id="row'.$report_id.''.$student->id.'_entry">';
            foreach ($reportentries as $entry)	{

                //TODO: is there a better way of doing this?
                //I am currently looping through each of the fields in the report and get the data for it
                //by using the plugin class. I do this for two reasons it may lock the database for less time then
                //making a large sql query and 2 it will also allow for plugins which return multiple values. However
                //I am not naive enough to think there is not a better way!

                $entry_data	=	new stdClass();

                //get the creator of the entry
                $creator				=	$dbc->get_user_by_id($entry->creator_id);

                //get comments for this entry
                $comments				=	$dbc->get_entry_comments($entry->id);

                //
                $entry_data->creator		=	(!empty($creator)) ? fullname($creator)	: get_string('notfound','block_ilp');
                $entry_data->created		=	userdate($entry->timecreated);
                $entry_data->modified		=	userdate($entry->timemodified);
                $entry_data->user_id		=	$entry->user_id;
                $entry_data->entry_id		=	$entry->id;

                if ($has_courserelated) {
                    $coursename	=	false;
                    $crfield	=	$dbc->get_report_coursefield($entry->id,$courserelatedfield_id);
                    if (empty($crfield) || empty($crfield->value)) {
                        $coursename	=	get_string('allcourses','block_ilp');
                    } else if ($crfield->value == '-1') {
                        $coursename	=	get_string('personal','block_ilp');
                    } else {
                        $crc	=	$dbc->get_course_by_id($crfield->value);
                        if (!empty($crc)) $coursename	=	$crc->shortname;
                    }
                    $entry_data->coursename = (!empty($coursename)) ? $coursename : '';
                }

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

                    if ($pluginclass->is_viewable() != false)	{
                        $pluginclass->load($field->id);

                        //call the plugin class entry data method
                        $pluginclass->view_data($field->id,$entry->id,$entry_data,false);
                    } else	{
                        $dontdisplay[]	=	$field->id;
                    }

                }

                if (!empty($reportfields)) {
                    $reportentry    .=   '<div class="report-entry" >';
                    foreach ($reportfields as $field) 	{
                        if (!in_array($field->id,$dontdisplay) && ((!empty($displaysummary) && !empty($field->summary) || empty($displaysummary)))) {
                            $fieldname	=	$field->id."_field";
                            $reportentry    .=   "<p><strong>$field->label: </strong>";
                            $reportentry    .=  (!empty($entry_data->$fieldname)) ? $entry_data->$fieldname : '&nbsp;';
                            $reportentry    .=  "</p>";
                        }
                    }


                    if (!empty($has_courserelated)) { $reportentry    .=  "<p><strong>".get_string('course','block_ilp')."</strong> : ".$entry_data->coursename." </p>";}
                    $reportentry    .=  "<p><strong>".get_string('addedby','block_ilp')."</strong>: {$entry_data->creator}</p>";
                    $reportentry    .=  "<p><strong>".get_string('date')."</strong>: {$entry_data->modified}</p>";
                    $reportentry    .=  "</div>";
                }
            }
            $reportentry    .=  "</div>";
            $hiddenrowdata[] = $reportentry;
        }

        $lastentry = $dbc->get_lastupdate($student->id);


        $flextable->add_data_keyed($data,'',null,$hiddenrowdata);
    }
}

$flextable->print_html();
