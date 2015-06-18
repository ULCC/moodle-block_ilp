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


require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER, $OUTPUT;

// Meta includes
require_once($CFG->dirroot . '/blocks/ilp/actions_includes.php');

//include the default class
require_once($CFG->dirroot . '/blocks/ilp/classes/tables/ilp_ajax_table.class.php');

//get the id of the course that is currently being used if set
$course_id = $PARSER->optional_param('course_id', 0, PARAM_INT);

//get the tutor flag
$tutor = $PARSER->optional_param('tutor', 0, PARAM_INT);

//get the status_id if set
$status_id = $PARSER->optional_param('status_id', 0, PARAM_INT);

//get the group_id if set
$group_id = $PARSER->optional_param('group_id', 0, PARAM_INT);

// instantiate the db
$dbc = new ilp_db();

// set up the flexible table for displaying the portfolios
$flextable = new ilp_ajax_table("student_listcourse_id{$course_id}tutor{$tutor}status_id{$status_id}");


$flextable->define_baseurl($CFG->wwwroot . "/blocks/ilp/actions/view_studentlist.php?course_id={$course_id}&tutor={$tutor}&status_id={$status_id}&group_id={$group_id}");
$flextable->define_ajaxurl($CFG->wwwroot . "/blocks/ilp/actions/view_studentlist.ajax.php?course_id={$course_id}&tutor={$tutor}&status_id={$status_id}&group_id={$group_id}");

// set the basic details to dispaly in the table
$headers = array(
    '',
    get_string('name', 'block_ilp'),
    get_string('status', 'block_ilp')
);

$columns = array('picture', 'fullname', 'u_status');


$nosorting = array('picture', 'u_status','view');
$expandcollapse =   array('picture', 'u_status');
//we need to check if the mis plugin has been setup if it has we will get the attendance and punctuality figures

$attendanceclass				=	get_config('block_ilp','attendplugin');
$misavailable 					= 	false;
$misattendavailable				=	false;
$mispunctualityavailable		=	false;

if (!empty($attendanceclass)) {
	$misclassfile = $CFG->dirroot . "/blocks/ilp/plugins/mis/{$attendanceclass}.php";
	if (file_exists($misclassfile)) {
		include_once $misclassfile;

		$misavailable	=	true;

		//create an instance of the MIS class
    	$misclass = new $attendanceclass();

    	//check if the methods exists
    	if (method_exists($misclass, 'getAttendance'))	{
           $headers[] = get_string('attendance', 'block_ilp');
           $columns[] = 'u_attendcance';
           $expandcollapse[]   = 'u_attendcance';
           $nosorting[] = 'u_attendcance';
           $misattendavailable = true;
    	}

    	//check if the methods exists
        if (method_exists($misclass, 'getPunctuality'))	{
           $headers[] = get_string('punctuality', 'block_ilp');
           $columns[] = 'u_punctuality';
           $expandcollapse[]   = 'u_punctuality';
           $nosorting[] = 'u_punctuality';
           $mispunctualityavailable = true;
    	}
	}
}

//get all enabled reports in this ilp
$reports = $dbc->get_reports(ILP_ENABLED, 'position ASC');
$vaulted_reports = $dbc->get_reports_vaulted();
$vaulted_report_ids = array_keys($vaulted_reports);

$warningstatus_pl = $dbc->get_plugin_by_name('block_ilp_plugin', 'ilp_element_plugin_warningstatus');
$entries_with_warningstatus = $dbc->get_enabledreports_with_entry(null, $warningstatus_pl->id);

if ($entries_with_warningstatus) {
    $headers[] = get_string('warningstatus_title','block_ilp');
    $columns[] = 'warningstatus_title';
    $expandcollapse[]   = 'warningstatus_title';
}

//get the mamximum reports that can be displayed on the screen in the list
$maxreports = get_config('block_ilp', 'ilp_max_reports');

//check if maxreports is empty if yes then set to
$maxreports = (!empty($maxreports)) ? $maxreports : ILP_DEFAULT_LIST_REPORTS;

//set the number of report columns to display

//removed as we no longer need the horizonatal scrolling
//$reports	=	$flextable->limitcols($reports,$maxreports);


//we are going to create headers and columns for all enabled reports
foreach ($reports as $reportid => $r) {
    $report		=	$dbc->get_report_by_id($reportid);
    if (in_array($reportid, $vaulted_report_ids) || !$report->has_cap($USER->id,$PAGE->context,'block/ilp:viewreport')) {
        unset($reports[$reportid]);
        continue;
    }
    $headers[] = "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_studentreports.php?course_id={$course_id}&tutor={$tutor}&report_id={$r->id}&group_id={$group_id}'>".$r->name."</a>";
    $columns[] = $r->id;
    $expandcollapse[]   = $r->id;
    $nosorting[] = $r->id;
}

$flextable->hoz_string = 'displayingreports';

$headers[] = get_string('lastupdated', 'block_ilp');
$columns[] = 'lastupdated';
$nosorting[] = 'lastupdated';

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
$flextable->set_attribute('id', "student_listcourse_id={$course_id}tutor={$tutor}status_id={$status_id}");
$flextable->use_expandcollapselinks(true);
$flextable->define_expandcollapse($expandcollapse);
$flextable->initialbars(true);
$flextable->pageable(true);

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

$prevnextstudents   =   array();


//get the default status item which will be used as the status for students who
//have not entered their ilp and have not had a status assigned
$defaultstatusitem_id = get_config('block_ilp', 'defaultstatusitem');

//get the status item record
$defaultstatusitem = $dbc->get_status_item_by_id($defaultstatusitem_id);


if(!empty($defaultstatusitem)){
    if($defaultstatusitem->display_option == 'icon'){
       $filename=ilp_get_status_icon($defaultstatusitem->id);
        $path="$CFG->wwwroot/pluginfile.php/1/block_ilp/icon/$defaultstatusitem->id/$filename";
        //$this_file = "<img src=\"$path\" alt=\"\" width='50px' />";
        $this_file = "<tooltip class='tooltip'>
                                    <img src='$path' alt='$defaultstatusitem->description'  width='50px'/>
                                    <span " . ((empty($defaultstatusitem->description)) ? "class='hiddenelement'" : "") . ">
                                    <img class='callout' src='$CFG->wwwroot/blocks/ilp/pix/callout.gif'/>";
        $this_file .= html_entity_decode($defaultstatusitem->description, ENT_QUOTES, 'UTF-8');
        $this_file .="</span></tooltip>";
        $status_item = '<div align="center" style="background: '. $defaultstatusitem->bg_colour .';" class="ilp_user_status">' . $this_file . '</div>';
    }else{
        $this_file = "<tooltip class='tooltip'>
                                    $defaultstatusitem->name
                                    <span " . ((empty($defaultstatusitem->description)) ? "class='hiddenelement'" : "") . ">
                                    <img class='callout' src='$CFG->wwwroot/blocks/ilp/pix/callout.gif'/>";
        $this_file .= html_entity_decode($defaultstatusitem->description, ENT_QUOTES, 'UTF-8');
        $this_file .="</span></tooltip>";
        $status_item = '<div align="center" style="background: '. $defaultstatusitem->bg_colour .';" class="ilp_user_status">' . $this_file . '</div>';
    }
}else {
    $status_item = get_string('unknown', 'block_ilp');
}
//this is needed if the current user has capabilities in the course context, it allows view_main page to view the user
//in the course context
$course_param   = (!empty($course_id)) ? "&course_id={$course_id}" : '';

$coursearg      = ( $course_id ) ? "&course=$course_id" : '' ;


//Saving this information on the students in this list in session var so it
//can be used on student page. not entirely happy about doing it this way
//this is possible a good place to use a caching class
if(!empty($students))  {
    $pagesize = $flextable->pagesize;
    $flextable->pagesize = count($students);
    $temp_student_list = $dbc->get_students_matrix($flextable, $students, $status_id, $notstatus_ids);
    $flextable->pagesize = $pagesize;

    // Create the list of 100 students ids to be passed to view_main page
    if (!empty($temp_student_list)) {
        foreach($temp_student_list   as $sl)   {
            $prevnextstudents[]   =   $sl->id;
        }
    }
}

$SESSION->ilp_prevnextstudents       =  serialize($prevnextstudents);
$CACHE=cache::make('block_ilp','ilp_miscache');
if (!empty($studentslist)) {

   $studentids=array_keys($studentslist);

   $cachekey='statelist:|'.implode($studentids,'|');
   if(($allStates=$CACHE->get($cachekey))===false)
   {
      $allStates=$dbc->fetch_all_report_entries_with_state($studentids);
      $CACHE->set($cachekey,$allStates);
      if(!$keysets=$CACHE->get('keysets'))
      {
         $keysets=array();
      }
      $keysets[$cachekey]=1;
      $CACHE->set('keysets',$keysets);
   }

    foreach ($studentslist as $student) {
        $data = array();

        $userprofile	=	'view.php' ;

        $data['picture'] = $OUTPUT->user_picture($student, array('return' => true, 'size' => 50));

        $data['fullname'] = "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$student->id}{$course_param}' class=\"userlink\">" . fullname($student) . "</a>";
        //if the student status has been set then show it else they have not had their ilp setup
        //thus their status is the default
        //$data['u_status'] = (!empty($student->u_status)) ? $student->u_status : $status_item;
        if(!empty($student->u_status)){
            $studentstatus = $dbc->get_status_item_by_id($student->u_status_id);
            $textcolor = (!empty($studentstatus->hexcolour)) ? $studentstatus->hexcolour : $studentstatus->name;
            if($student->u_display_option == 'icon'){
                $path="$CFG->wwwroot/pluginfile.php/1/block_ilp/icon/$student->u_status_id/".ilp_get_status_icon($student->u_status_id);
                //$this_file = "<img src=\"$path\" alt=\"\" width='50px' />";
                $this_file = "<tooltip class='tooltip'>
                                    <img src=\"$path\" alt=\"$student->u_status_description\"  width='50px'/>
                                    <span " . ((empty($student->u_status_description)) ? "class='hiddenelement'" : "") . ">
                                    <img class='callout' src='$CFG->wwwroot/blocks/ilp/pix/callout.gif'/>";
                $this_file .= html_entity_decode($student->u_status_description, ENT_QUOTES, 'UTF-8');
                $this_file .="</span></tooltip>";
                $data['u_status'] = '<div align="center" style="background: '. $studentstatus->bg_colour .';" class="ilp_user_status">' . $this_file . '</div>';
            }else {
                $this_file = "<tooltip class='tooltip'>";
                $this_file .= $student->u_status;
                $this_file .="<span " . ((empty($student->u_status_description)) ? "class='hiddenelement'" : "") . ">
                                    <img class='callout' src='$CFG->wwwroot/blocks/ilp/pix/callout.gif'/>";
                $this_file .= html_entity_decode($student->u_status_description, ENT_QUOTES, 'UTF-8');
                $this_file .="</span></tooltip>";
                $data['u_status'] = '<div align="center" style="background-color: '. $studentstatus->bg_colour .';color:' . $textcolor . '" class="ilp_user_status">' . $this_file . '</div>';
            }
        }else {
            $data['u_status'] = $status_item;
        }

        //$data['view'] = "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$student->id}{$course_param}' >" . get_string('viewplp', 'block_ilp') . "</a>";

		//we will only attempt to get MIS data if an attendace plugin has been selected in the settings page

        if (!empty($misavailable)) {
        	$misclass = new $attendanceclass();
	        //set the data for the student in question
	        $misclass->set_data($student->idnumber);
	        if (!empty($misattendavailable)) {
	        	$attendpercent	=	0;
	            $attendpercent = $misclass->getAttendance();
	            //we only want to try to find the percentage if we can get the total possible
	            // attendance else set it to 0;
	            $data['u_attendcance'] = (!empty($attendpercent)) ? $attendpercent : 0;
	        }

	        if (!empty($mispunctualityavailable)) {
	            $punctpercent	=	0;
	        	$punctpercent = $misclass->getPunctuality();
	            //we only want to try to find the percentage if we can get the total possible
	            // punctuality else set it to 0;
	            $data['u_punctuality'] = (!empty($punctpercent)) ? $punctpercent : 0;
	        }
        }

        if ($entries_with_warningstatus) {
            $student_warning_info = $dbc->get_current_warning_status($student->id);
            if ($student_warning_info) {
                $warningstatus = $dbc->get_warning_status_name($student_warning_info->value);
            } else {
                $warningstatus = '';
            }
            $data['warningstatus_title'] = $warningstatus;
        }
        foreach ($reports as $r) {
            //get the number of this report that have been created
           $datavalid=isset($allStates[$r->id][$student->id]);

           $createdentries = $datavalid ? count($allStates[$r->id][$student->id]) : 0 ;

            $reporttext = "{$createdentries} ";

            //TODO: abstract these out put a function within the ilp_element_plugin classes that allows a var to be passed
            //in and altered in a similar way to the entr_obj in the entry_data function


            //check if the report has a state field
            if ($dbc->has_plugin_field($r->id, 'ilp_element_plugin_state')) {
                //count the number of entries with a pass state

                $achievedentries=$datavalid ? count(array_filter($allStates[$r->id][$student->id],
                                                                 function($item){ return ($item->state==ILP_STATE_PASS);}))
                   : 0 ;

                //we need to count the number of entries that have a notcounted status

                $notcountedentries=$datavalid? count(array_filter($allStates[$r->id][$student->id],
                                                                  function($item){ return ($item->state==ILP_STATE_NOTCOUNTED);}))
                   : 0 ;

                $createdentries     =   $createdentries     -   $notcountedentries;

                $reporttext         =   $achievedentries . "/" . $createdentries . " " . get_string('achieved', 'block_ilp');
            }


            if ($dbc->has_plugin_field($r->id,'ilp_element_plugin_datefield')) {
               $inprogressentries	= $datavalid?	array_filter($allStates[$r->id][$student->id],
                                                                     function($item){return ($item->state==ILP_STATE_UNSET);})
                  : array() ;
               $inprogentries 		=	array();

               if (!empty($inprogressentries)) {
                  foreach ($inprogressentries as $e) {
                     $inprogentries[]	=	$e->id;
                  }
               }
               //get the number of entries that are overdue
               $overdueentries	=	$dbc->count_overdue_report($r->id,$student->id,$inprogentries,time());
               $reporttext	.=	(!empty($overdueentries))?  "<br />".$overdueentries." ".get_string('reportsoverdue','block_ilp') : "";

               $nextreview     =   $dbc->get_next_review($r->id,$student->id);
               $reporttext	.=	(!empty($nextreview->review))?  "<br />".get_string('nextreview','block_ilp')." ".userdate($nextreview->review,'%d-%m-%Y') : "";
            }

            $data[$r->id] = $reporttext;
        }

        $lastentry = $dbc->get_lastupdate($student->id);
        $data['lastupdated'] = (!empty($lastentry->timemodified))
                ? userdate($lastentry->timemodified, get_string('strftimedate', 'langconfig'))
                : get_string('notapplicable', 'block_ilp');

        $flextable->add_data_keyed($data);
    }
}

$flextable->print_html();
