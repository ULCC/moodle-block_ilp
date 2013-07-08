<?php
/**
 * A class used to display information on a particular student in the ilp
 *
 *  *
 * @copyright &copy; 2013 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once($CFG->dirroot.'/blocks/ilp/plugins/dashboard/ilp_dashboard_student_info_plugin.php');

class ilp_dashboard_student_info_print_plugin extends ilp_dashboard_student_info_plugin {

   function display($ajax_settings = array())	{
      global	$CFG, $DB, $OUTPUT, $PAGE, $PARSER, $USER, $SESSION;

      //set any variables needed by the display page

      //get students full name
      if(!$student	=	$this->dbc->get_user_by_id($this->student_id))
      {
         //the student was not found display and error
         print_error('studentnotfound','block_ilp');
      }

      $studentname	=	fullname($student);
      $studentpicture	=	$OUTPUT->user_picture($student,array('size'=>100,'return'=>'true'));

      $tutors	=	$this->dbc->get_student_tutors($this->student_id);

      $tutorslist	=	array();
      if (!empty($tutors)) {
         foreach ($tutors as $t) {
            $tutorslist[]	=	fullname($t);
         }
      } else {
         $tutorslist		=	"";
      }

      //get the student's current status
      $studentstatus	=	$this->dbc->get_user_status($this->student_id);

      if (!empty($studentstatus)) {
         $statusitem		=	$this->dbc->get_status_item_by_id($studentstatus->parent_id);
      }

      $userstatuscolor	=	get_config('block_ilp', 'passcolour');

      if (!empty($statusitem))	{
         if ($statusitem->passfail == 1) $userstatuscolor	=	get_config('block_ilp', 'failcolour');
         //that's all very well, but if the ilp is up to date, status hex colour is defined, so actually we should always do this...
         //the above logic only allows 2 colours, so is inadequate to the task
         if( !empty( $statusitem->hexcolour ) ){
            $userstatuscolor = $statusitem->hexcolour;
         }
         //ah that's better
      }

      //TODO place percentage bar code into a class

      $percentagebars	=	array();

      //set the display attendance flag to false
      $displayattendance	= false;

      $access_viewotherilp	=	has_capability('block/ilp:viewotherilp', $PAGE->context);

      //include the attendance
      $misclassfile	=	$CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_attendance_percentbar_plugin.php';

      $misoverviewplugins	=	false;

      if ($this->dbc->get_mis_plugins() !== false) {

         $misoverviewplugins	=	array();

         //get all plugins that mis plugins
         $plugins = $CFG->dirroot.'/blocks/ilp/plugins/mis';

         $mis_plugins = ilp_records_to_menu($this->dbc->get_mis_plugins(), 'id', 'name');

         foreach ($mis_plugins as $plugin_file) {

            if (file_exists($plugins.'/'.$plugin_file.".php")) {
               require_once($plugins.'/'.$plugin_file.".php");

               // instantiate the object
               $class = basename($plugin_file, ".php");
               $pluginobj = new $class();
               $method = array($pluginobj, 'plugin_type');

               if (is_callable($method,true)) {
                  //we only want mis plugins that are of type overview
                  if ($pluginobj->plugin_type() == 'overview') {

                     //get the actual overview plugin
                     $misplug	=	$this->dbc->get_mis_plugin_by_name($plugin_file);

                     //if the admin of the moodle has done their job properly then only one overview mis plugin will be enabled
                     //otherwise there may be more and they will all be displayed

                     $status =	get_config('block_ilp',$plugin_file.'_pluginstatus');

                     $status	=(!empty($status)) ?  $status: ILP_DISABLED;

                     if (!empty($misplug) & $status == ILP_ENABLED ) {
                        $misoverviewplugins[]	=	$pluginobj;
                     }
                  }
               }
            }
         }
      }

      //get all enabled reports in this ilp
      $reports		=	$this->dbc->get_reports(ILP_ENABLED);

      //we are going to output the add any reports that have state fields to the percentagebar array
      if (!empty($reports) ) {
         foreach ($reports as $r) {
            if ($this->dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {

               $reportinfo				=	new stdClass();
               $reportinfo->total		=	$this->dbc->count_report_entries($r->id,$this->student_id);
               $reportinfo->actual		=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_STATE_PASS);
               //retrieve the number of entries that have the not counted state
               $reportinfo->notcounted	=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_STATE_NOTCOUNTED);

               //if total_possible is empty then there will be nothing to report
               if (!empty($reportinfo->total)) {
                  $reportinfo->total     =   $reportinfo->total -  $reportinfo->notcounted;
                  //calculate the percentage
                  $reportinfo->percentage	=	$reportinfo->actual/$reportinfo->total	* 100;

                  $reportinfo->name	=	$r->name;

                  $percentagebars[]	=	$reportinfo;
               }

            }
         }
      }
      else
      {
         $reports=array(); // Tch!
      }

      //instantiate the percentage bar class in case there are any percentage bars
      $pbar	=	new ilp_percentage_bar();

      include("$CFG->dirroot/blocks/ilp/plugins/dashboard/$this->directory/ilp_dashboard_student_info_batch.html");

      //get all of the users roles in the current context and save the id of the roles into
      //an array
      $role_ids	=	 array();

      $authuserrole	=	$this->dbc->get_role_by_name(ILP_AUTH_USER_ROLE);
      if (!empty($authuserrole)) $role_ids[]	=	$authuserrole->id;

      if ($roles = get_user_roles($PAGE->context, $USER->id)) {
         foreach ($roles as $role) {
            $role_ids[]	= $role->roleid;
         }
      }

      $capability	=	$this->dbc->get_capability_by_name('block/ilp:viewreport');

      foreach($reports as $report)
      {
         $report_id=$report->id;

         $access_report_viewreports	= false;

         if (!empty($capability)) $access_report_viewreports		=	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

         if (!empty($access_report_viewreports)) {
            $reportname	=	$report->name;
            //get all of the fields in the current report, they will be returned in order as
            //no position has been specified
            $reportfields		=	$this->dbc->get_report_fields_by_position($report_id);

            $reporticon	= (!empty($report->iconfile)) ? '' : '';

            //does this report give user the ability to add comments
            $has_comments	=	!empty($report->comments);

            //this will hold the ids of fields that we dont want to display
            $dontdisplay	=	 array();

            //does this report allow users to say it is related to a particular course
            $has_courserelated	=	($this->dbc->has_plugin_field($report_id,'ilp_element_plugin_course')) ? true : false;

            if (!empty($has_courserelated))	{
               $courserelated	=	$this->dbc->has_plugin_field($report_id,'ilp_element_plugin_course');
               //the should not be anymore than one of these fields in a report
               foreach ($courserelated as $cr) {
                  $dontdisplay[] 	=	$cr->id;
                  $courserelatedfield_id	=	$cr->id;
               }
            }

            //Make everything read-only
            //find if the current user can add reports
            $access_report_addreports	= false;
            $access_report_editreports	= false;
            $access_report_deletereports	=	false;
            $access_report_addcomment	= false;
            $access_report_editcomment	=	false;
            $access_report_deletecomment	= false;
            $access_report_viewcomment	=	false;
            $candelete =	false;

            //check to see whether the user can add/view extension for the specific report
            $capability		=	$this->dbc->get_capability_by_name('block/ilp:addviewextension');
            if (!empty($capability))	$access_report_addviewextension	 =	$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);

            //get all of the entries for this report
            $reportentries	=	$this->dbc->get_user_report_entries($report_id,$this->student_id,$state_id);

            //does the current report allow multiple entries
            $multiple_entries   =   !empty($report->frequency);

            //instantiate the report rules class
            $reportrules    =   new ilp_report_rules($report_id,$this->student_id);

            //output html elements to screen

            $icon=(!empty($report->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report_id=".$report->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";

            echo $this->get_header($report->name,$icon);

            $stateselector=$this->stateselector($report_id);

            //find out if the rules set on this report allow a new entry to be created
            $reportavailable =   $reportrules->report_availabilty();

            echo "<div id='report-entries'>";
            $addnewentry_url = "{$CFG->wwwroot}/blocks/ilp/actions/edit_reportentry.ajax.php?user_id={$this->student_id}&report_id={$report_id}&course_id={$this->course_id}";

            $addnew_span = html_writer::tag('span', get_string('addnew','block_ilp'), array('data-link'=>$addnewentry_url, 'class'=>'_addnewentry'));
            $addnew_area = html_writer::tag('div','', array('class'=>'_addnewentryarea'));
            $loader_icon = $this->get_loader_icon('addnewentry-loader', 'span');
            if (!empty($access_report_addreports)   && !empty($multiple_entries) && !empty($reportavailable['result'])) {
               echo    "<div class='add' style='float :left'>
                                     $loader_icon $addnew_span
                                        </div> $addnew_area";
            }

            if (!empty($access_report_viewothers)) {
               if (!empty($access_report_addviewextension) && $reportrules->can_add_extensions()) {
                  echo "<div class='add' style='float :left'>
                                        <a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_preference.php?user_id={$this->student_id}&report_id={$report_id}&course_id={$this->course_id}' >".get_string('addextension','block_ilp')."</a>&nbsp;
                                      </div>

                                    <div class='add' style='float :left'>
                                        <a href='{$CFG->wwwroot}/blocks/ilp/actions/view_extensionlist.php?user_id={$this->student_id}&report_id={$report_id}&course_id={$this->course_id}' >".get_string('viewextension','block_ilp')."</a>
                                    </div>";
               }

            }
            echo "</div>
                            <br />";

            //output the print icon
            echo "{$stateselector}<div class='entry_floatright'><a href='#' onclick='M.ilp_standard_functions.printfunction()' ><img src='{$CFG->wwwroot}/blocks/ilp/pix/icons/print_icon_med.png' alt='".get_string("print","block_ilp")."' class='ilp_print_icon' width='32px' height='32px' ></a></div>
								 ";

            //create the entries list var that will hold the entry information
            $entrieslist	=	array();

            if (!empty($reportentries)) {

//Mini caches for items that are looked at repeatedly in the loops below
               $creators=$pluginRecords=$pluginInstances=array();

               echo html_writer::start_tag('div', array('class'=>'reports-container-container'));
               if ($return_refreshed_list) {
                  ob_end_clean();
                  ob_start();
               }
               foreach ($reportentries as $entry)	{
                  //TODO: is there a better way of doing this?
                  //I am currently looping through each of the fields in the report and get the data for it
                  //by using the plugin class. I do this for two reasons it may lock the database for less time then
                  //making a large sql query and 2 it will also allow for plugins which return multiple values. However
                  //I am not naive enough to think there is not a better way!

                  $entry_data	=	new stdClass();

                  //get the creator of the entry with caching
                  if(!isset($creators[$entry->creator_id]))
                  {
                     $creators[$entry->creator_id]          =       $this->dbc->get_user_by_id($entry->creator_id);
                  }
                  $creator=$creators[$entry->creator_id];

                  //get comments for this entry
                  $comments				=	$this->dbc->get_entry_comments($entry->id);

                  //
                  $entry_data->creator		=	(!empty($creator)) ? fullname($creator)	: get_string('notfound','block_ilp');
                  $entry_data->created		=	userdate($entry->timecreated);
                  $entry_data->modified		=	userdate($entry->timemodified);
                  $entry_data->user_id		=	$entry->user_id;
                  $entry_data->entry_id		=	$entry->id;

                  if ($has_courserelated) {
                     $coursename	=	false;
                     $crfield	=	$this->dbc->get_report_coursefield($entry->id,$courserelatedfield_id);
                     if (empty($crfield) || empty($crfield->value)) {
                        $coursename	=	get_string('allcourses','block_ilp');
                     } else if ($crfield->value == '-1') {
                        $coursename	=	get_string('personal','block_ilp');
                     } else {
                        $crc	=	$this->dbc->get_course_by_id($crfield->value);
                        if (!empty($crc)) $coursename	=	$crc->shortname;
                     }
                     $entry_data->coursename = (!empty($coursename)) ? $coursename : '';
                  }

                  foreach ($reportfields as $field) {

                     //get the plugin record that for the plugin, with cacheing
                     if(!isset($pluginRecords[$field->plugin_id]))
                     {
                        $pluginRecords[$field->plugin_id]=$this->dbc->get_plugin_by_id($field->plugin_id);
                     }

                     $pluginrecord=$pluginRecords[$field->plugin_id];

                     //take the name field from the plugin as it will be used to call the instantiate the plugin class
                     $classname = $pluginrecord->name;

                     if(!isset($pluginInstances[$classname]))
                     {
                        // include the class for the plugin
                        include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

                        if(!class_exists($classname)) {
                           print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
                        }

                        //instantiate the plugin class

                        $pluginInstances[$classname]=new $classname;
                        $pluginFieldsLoaded[$classname]=array();
                     }

                     $pluginclass	=   $pluginInstances[$classname];

                     if ($pluginclass->is_viewable() != false)	{
                        if(!isset($pluginFieldsLoaded[$classname][$field->id]))
                        {
                           $pluginclass->load($field->id);
                           $pluginFieldsLoaded[$classname][$field->id]=true;
                        }

                        //call the plugin class entry data method
                        $pluginclass->view_data($field->id,$entry->id,$entry_data);
                     } else	{
                        $dontdisplay[]	=	$field->id;
                     }
                  }

                  if ($return_only_newest) {
                     ob_end_clean();
                     ob_start();
                  }
                  if ($return_left_report_only || $return_right_report_only) {
                     ob_end_clean();
                     ob_start();
                     if ($return_left_report_only) {
                        echo $this->generate_left_reports($reportfields, $dontdisplay, $displaysummary, $entry_data);
                     } else if ($return_right_report_only) {
                        $has_deadline = (isset($has_deadline)) ? $has_deadline : null;
                        echo $this->generate_right_reports($has_courserelated, $has_deadline, $entry_data);
                     }
                     $pluginoutput = ob_get_contents();
                     ob_end_clean();
                     print $pluginoutput;
                  }
                  include($CFG->dirroot.'/blocks/ilp/plugins/tabs/ilp_dashboard_reports_tab.html');
                  if ($return_only_newest) {
                     $pluginoutput = ob_get_contents();
                     ob_end_clean();
                     print $pluginoutput;
                  }

               }
               if ($return_refreshed_list) {
                  $pluginoutput = ob_get_contents();
                  ob_end_clean();
                  print $pluginoutput;
               }
               echo html_writer::end_tag('div');
            } else {

               echo get_string('nothingtodisplay');

            }
         }//end new if
      }
   }
}

