<?php
/**
 * A class used to display information on a particular student in the ilp
 *
 *  *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_plugin.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_percentage_bar.class.php');

class ilp_dashboard_student_info_plugin extends ilp_dashboard_plugin {

   public		$student_id;


   function __construct($student_id = null)	{
      //set the id of the student that will be displayed by this
      $this->student_id	=	$student_id;

      //set the name of the directory that holds any files for this plugin
      $this->directory	=	'studentinfo';

      parent::__construct();

   }

    function generate_middle_studentinfo_content($tutorslist, $statusitem, $can_editstatus, $percentagebars, $pbar, $userstatuscolor, $includeloader = true) {
        global $CFG;
        $o = '';
        if (!empty($tutorslist)) {
            $mytutor = '<strong>' .  get_string('mytutor','block_ilp') . '</strong><span>' . implode(', ',$tutorslist) . '</span>';
            $o .= html_writer::tag('p', $mytutor);
        }
        $o .= '<strong>' . get_string('studentstatus','block_ilp') . '</strong>';
        $o .= '<div class="ajaxstatuschange_wrapper">' . $this->generate_ajax_updatable($statusitem, $userstatuscolor) . '</div>';
        if (!empty($can_editstatus)) {
            $o .= html_writer::tag('div', $this->userstatus_select($statusitem->id), array('class'=>'edit_status'));
        }
        if ($includeloader) {
            $o .= html_writer::tag('img', '',
                array('src'=>$CFG->wwwroot . '/blocks/ilp/pix/loading.gif', 'id'=>'studentlistloadingicon', 'class'=>'hiddenelement'));
        }

        if (!empty($percentagebars)) {
            foreach($percentagebars	as $p) {
                $o .= $pbar->display_bar($p->percentage,$p->name,$p->total);
            }
        }
        return $o;
    }

    function generate_ajax_updatable($statusitem, $userstatuscolor) {
        global $CFG;
        $o = '';
        $textcolor = (!empty($statusitem->hexcolour)) ? $statusitem->hexcolour : $statusitem->name;
        if($statusitem->display_option == 'icon'){
            if($statusitem->icon){
                $path="$CFG->wwwroot/pluginfile.php/1/block_ilp/icon/$statusitem->id/".ilp_get_status_icon($statusitem->id);
                $this_file = "<a class='tooltip'>
                                    <img src=\"$path\" alt=\"$statusitem->description\" class='icon_file'/>
                                    <span " . ((empty($statusitem->description)) ? "class='hiddenelement'" : "") . ">
                                    <img class='callout' src='$CFG->wwwroot/blocks/ilp/pix/callout.gif'/>";
                $this_file .= html_entity_decode($statusitem->description, ENT_QUOTES, 'UTF-8');
                $this_file .="</span></a>";
                //we found there is a icon, so we need to display it
                $o .= html_writer::tag('div', $this_file, array('class'=>'dashboard_status_icon ajaxstatuschange',
                    'style'=>'background: '. $statusitem->bg_colour));
            } else {
                $o .= html_writer::tag('div', html_entity_decode($statusitem->description,
                                                                 ENT_QUOTES,
                                                                 'UTF-8'), array('class'=>'dashboard_status_icon ajaxstatuschange',
                    'style'=>'background: '. $statusitem->bg_colour));
            }
        } else {
            $o .= html_writer::tag(
                'div', $statusitem->name, array(
                    'class'=>'dashboard_status_icon ajaxstatuschange',
                    'style'=>'background:' . $statusitem->bg_colour . '; color:' . $textcolor
                )
            );

            //$userstatus = html_writer::tag('span', $statusitem->name, array('id'=>'user_status', 'style'=>'color: ' . $userstatuscolor));
            //$o .= html_writer::tag('div', $userstatus);
        }
        return $o;
    }

   /**
    * Returns the
    * @see ilp_dashboard_plugin::display()
    */
   function display($ajax_settings = array(), $fromblock = false)	{
      global	$CFG, $DB, $OUTPUT, $PAGE, $PARSER, $USER, $SESSION;

      //set any variables needed by the display page

      //get students full name
      if(!$student	=	$this->dbc->get_user_by_id($this->student_id))
      {
         //the student was not found display and error
         print_error('studentnotfound','block_ilp');
      }

      $display_only_middle_studentinfo = (!empty($ajax_settings) && isset($ajax_settings['middle_studentinfo'])
          && $ajax_settings['middle_studentinfo']) ? true : false;
      $nextstudent    =   false;
      $prevstudent    =   false;

      //get the details of the previous and next student in the list
      //from the $SESSION->ilp_prevnextstudents var if it has been set
      if (isset($SESSION->ilp_prevnextstudents))   {
         $studentlist    =   unserialize($SESSION->ilp_prevnextstudents);

         if (!empty($studentlist))   {

            $byid=array_flip($studentlist);

            if(isset($byid[$student->id]) && isset($studentlist[$byid[$student->id]+1]))
            {
               $nextstudent    =   $studentlist[$byid[$student->id]+1];
            }

            if(isset($byid[$student->id]) && isset($studentlist[$byid[$student->id]-1]))
            {
               $prevstudent    =   $studentlist[$byid[$student->id]-1];
            }

         }
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

      //get the students current status
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

      /****
       * This code is in place as moodle insists on calling the settings functions on normal pages
       *
       */

      $course_id = (is_object($PARSER)) ? $PARSER->optional_param('course_id', SITEID, PARAM_INT)  : SITEID;
      $user_id = (is_object($PARSER)) ? $PARSER->optional_param('user_id', $USER->id, PARAM_INT)  : $USER->id;

      //check if the set_context method exists
      if (!isset($PAGE->context) === false) {
         if ($course_id != SITEID && !empty($course_id))	{
               $PAGE->set_context(context_course::instance($course_id));
         } else {
               //check if the siteid has been set if not
               $PAGE->set_context(context_user::instance($user_id));
        }
      }

      $access_viewotherilp	=	has_capability('block/ilp:viewotherilp', $PAGE->context);

      //can the current user change the users status
      $can_editstatus	=	(!empty($access_viewotherilp) && $USER->id != $student->id) ? true : false;

      //include the attendance
      $misclassfile	=	$CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_attendance_percentbar_plugin.php';

      if (file_exists($misclassfile)) {

            include_once @$misclassfile;

         //create an instance of the MIS class
         $misclass	=	new ilp_mis_attendance_percentbar_plugin();

         //set the data for the student in question
         $misclass->set_data($this->student_id);


         $punch_method1 = array($misclass, 'get_student_punctuality');
         $attend_method1 = array($misclass, 'get_student_attendance');


         //check whether the necessary functions have been defined
         if (is_callable($punch_method1,true)) {
            $misinfo	=	new stdClass();


            if ($misclass->get_student_punctuality() != false) {
               //calculate the percentage

               $misinfo->percentage	=	$misclass->get_student_punctuality();

               $misinfo->name	=	get_string('punctuality','block_ilp');

               //pass the object to the percentage bars array
               $percentagebars[]	=	$misinfo;
            }
         }

         //check whether the necessary functions have been defined
         if (is_callable($attend_method1,true) ) {
            $misinfo	=	new stdClass();

            //if total_possible is empty then there will be nothing to report
            if ($misclass->get_student_attendance() != false) {
               //calculate the percentage
               $misinfo->percentage	=	$misclass->get_student_attendance();

               $misinfo->name	=	get_string('attendance','block_ilp');

               $percentagebars[]	=	$misinfo;
            }

         }

      }


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

                $allmis_plugins[$plugin_file] = $pluginobj;
               if (is_callable($method,true)) {
                  //we only want mis plugins that are of type overview
                  if ($pluginobj->plugin_type() == 'overview') {

                     //get the actual overview plugin
                     $misplug	=	$this->dbc->get_mis_plugin_by_name($plugin_file);

                     //if the admin of the moodle has done there job properly then only one overview mis plugin will be enabled
                     //otherwise there may be more and they will all be displayed

                     $status =	get_config('block_ilp',$plugin_file.'_pluginstatus');

                     $status	=	(!empty($status)) ?  $status: ILP_DISABLED;

                     if (!empty($misplug) & $status == ILP_ENABLED ) {
                        $misoverviewplugins[$plugin_file]	=	$pluginobj;
                     }
                  }
               }
            }
         }
      }


      //if the user has the capability to view others ilp and this ilp is not there own
      //then they may change the students status otherwise they can only view

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
                  $reportinfo->percentage	=	($reportinfo->actual) ? $reportinfo->actual/$reportinfo->total	* 100 : 0;

                  $reportinfo->name	=	$r->name;

                  $percentagebars[]	=	$reportinfo;
               }

            }
         }
      }

       // Get warning status if appropriate

       $second_status_file = $CFG->dirroot . '/blocks/ilp/plugins/form_elements/ilp_element_plugin_warningstatus.php';
       $second_status_file_exists = file_exists($second_status_file);
       $display_warning_status = false;
       $show_secondsts = !$fromblock && !$display_only_middle_studentinfo;
       if ($show_secondsts && $second_status_file_exists && $student_warning_info = $this->dbc->get_current_warning_status($this->student_id)) {
           $warningstatus_pl = $this->dbc->get_plugin_by_name('block_ilp_plugin', 'ilp_element_plugin_warningstatus');
           $enabled_reports_with_warning_status = $this->dbc->get_enabledreports_with_entry($student->id, $warningstatus_pl->id);

           if ($enabled_reports_with_warning_status) {
               $display_warning_status = true;
               $warning_status_name = $this->dbc->get_warning_status_name($student_warning_info->value);
               require_once($second_status_file);
               $warning_status = new ilp_element_plugin_warningstatus();
               $firstoption = array('' => get_string('warningstatus_title', 'block_ilp'));
               $optionlist = $warning_status->get_option_list(true);
               $second_sts_form = $this->generate_second_sts_form($optionlist, $student_warning_info->value);
               $second_sts_loader = html_writer::tag('img', '',
                   array('src'=>$CFG->wwwroot . '/blocks/ilp/pix/loading.gif', 'id'=>'secondstsloadingicon', 'class'=>'hiddenelement'));
           }
       }

      //instantiate the percentage bar class in case there are any percentage bars
      $pbar	=	new ilp_percentage_bar();

      if ($display_only_middle_studentinfo) {
         $toreturn = '';
         $toreturn .= $this->generate_ajax_updatable($statusitem, $userstatuscolor);
         return $toreturn;
      } else {
         //we need to buffer output to prevent it being sent straight to screen
         ob_start();

         if ($fromblock) {
             include($CFG->dirroot.'/blocks/ilp/plugins/dashboard/' . $this->directory . '/ilp_dashboard_student_info_block.html');
             $status = ob_get_contents();

             $pbarhtml = '';
             if (!empty($percentagebars)) {
                 foreach($percentagebars	as $p) {
                     $pbarhtml .= $pbar->display_bar($p->percentage,$p->name,$p->total);
                 }
             }

             $att_percent = '';
             $pun_percent = '';

             if (!empty($allmis_plugins) && isset($student->idnumber)) {
                 $count = 0;
                 foreach ($allmis_plugins as $plugin_file => $mp)	{
                     $att_punc_config = get_config('block_ilp', 'show_attendancepunctuality_mis_plugin');
                     // Before accessing the blockitem config, show the first enabled plug in; otherwise, only show the selected mis plugin.
                     $config_set_up_conditions = $att_punc_config && $plugin_file == $att_punc_config;
                     $pre_config_set_up_conditions = !$att_punc_config && $count < 1;
                     if (!$config_set_up_conditions && !$pre_config_set_up_conditions) {
                         continue;
                     }

                     $mp->set_data($student->idnumber);
                     if (method_exists($mp, 'getAttendance'))	{
                         $att_percent = $mp->getAttendance();
                     }
                     if (method_exists($mp, 'getPunctuality'))	{
                         $pun_percent = $mp->getPunctuality();
                     }

                     $count ++;
                 }
             }

             $blockitems = array(
                 'status' => $status,
                 'picture' => $studentpicture,
                 'name' => $studentname,
                 'progress' => $pbarhtml,
                 'att_percent' => $att_percent,
                 'pun_percent' => $pun_percent
             );
             ob_end_clean();
             return $blockitems;
         } else {
             include($CFG->dirroot.'/blocks/ilp/plugins/dashboard/'.$this->directory.'/ilp_dashboard_student_info.html');
         }

         $pluginoutput=ob_get_contents();

      }

      ob_end_clean();

      return $pluginoutput;
   }

   /**
    * Adds the string values from the tab to the language file
    *
    * @param	array &$string the language strings array passed by reference so we
    * just need to simply add the plugins entries on to it
    */
   function userstatus_select($selected_value =null)	{
      global	$USER, $CFG, $PARSER;


      $statusitems	=	$this->dbc->get_user_status_items();

      if (!empty($statusitems)) {
         $selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_RAW);
         $tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_RAW);
         $course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);
         $form	= "<form action='{$CFG->wwwroot}/blocks/ilp/actions/save_userstatus.php' method='GET' id='studentstatusform' >";

         $form	.=	"<input type='hidden' name='student_id' id='student_id' value='{$this->student_id}' >";
         $form	.=	"<input type='hidden' name='course_id' id='course_id' value='{$course_id}' >";
         $form	.=	"<input type='hidden' name='user_modified_id' id='user_modified_id' value='{$USER->id}' >";
         $form	.=	"<input type='hidden' name='ajax' id='ajax' value='false' >";
         $form	.=	"<input type='hidden' name='tabitem' id='tabitem' value='$tabitem' >";
         $form	.=	"<input type='hidden' name='selectedtab' id='selectedtab' value='$selectedtab' >";

         $form .= "<select id='select_userstatus'  name='select_userstatus' >";

         foreach ($statusitems	as  $s) {

            $selected	=	($s->id	==	$selected_value) ? 'selected="selected"' : '';

            $form .= "<option value='{$s->id}' $selected >{$s->name}</option>";
         }

         $form .= '</select>';

         $form .= '<input type="submit" value="Change Status" id="studentstatussub" />';

         $form .= '</form>';
      } else {

         $form	=	"<span id='studentstatusform'>";

         $form	.= 'STATUS ITEMS NOT SET PLEASE CONTACT ADMIN';

         $form 	.= '</span>';

      }

      return $form;

   }

    public function generate_second_sts_form($optionlist, $selected_value) {
        global $CFG;
        $form = "<form>";
        $form .= "<select id='select_usersecondstatus'  name='select_usersecondstatus' >";

        foreach ($optionlist	as  $value => $name) {

            $selected	=	($value	==	$selected_value) ? 'selected="selected"' : '';

            $form .= "<option value='" . $value . "' $selected >" . $name . "</option>";
        }

        $form .= '</select>';
        $form .= "</form>";
        return $form;
    }

   /**
    * Adds the string values from the tab to the language file
    *
    * @param	array &$string the language strings array passed by reference so we
    * just need to simply add the plugins entries on to it
    */
   static function language_strings(&$string) {
      $string['ilp_dashboard_student_info_plugin'] 					= 'student info plugin';
      $string['ilp_dashboard_student_info_plugin_name'] 				= 'student info';

      return $string;
   }
}
