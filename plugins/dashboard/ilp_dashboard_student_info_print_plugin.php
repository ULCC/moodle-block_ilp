<?php
/**
 * A class used to batch display information on a particular student in the ilp
 *
 *  *
 * @copyright &copy; 2013 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once($CFG->dirroot.'/blocks/ilp/plugins/dashboard/ilp_dashboard_student_info_plugin.php');
require_once("$CFG->dirroot/blocks/ilp/plugins/tabs/ilp_dashboard_reports_tab.php");
require_once("$CFG->dirroot/blocks/ilp/classes/ilp_report_rules.class.php");

class ilp_dashboard_student_info_print_plugin extends ilp_dashboard_student_info_plugin {

   protected $formdata;

   function __construct($student_id = null, $formdata = null)
   {
      parent::__construct($student_id);
      $this->formdata=$formdata;
   }

   function display($ajax_settings = array())	{
      global	$CFG, $DB, $OUTPUT, $PAGE, $PARSER, $USER, $SESSION;

      //set any variables needed by the display page

      $courseid=(isset($formdata->courseid))?$this->formdata->courseid: 0;

      $reportselect=(isset($this->formdata->reportselect))? array_flip($this->formdata->reportselect) : array();

      //get students full name
      if(!$student	=	$this->dbc->get_user_by_id($this->student_id))
      {
         //the student was not found display and error
         print_error('studentnotfound','block_ilp');
      }

      if($seal=ilp_report::seal_url())
      {
         print html_writer::empty_tag('img',array('src'=>$seal,'class'=>'seal_image'));
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

      $reporter=new ilp_dashboard_reports_tab($this->student_id,$courseid);

      foreach($reports as $r)
      {
         if(isset($reportselect[$r->id]))
         {
            print $reporter->display("-1:$r->id",array(),true,isset($this->formdata->showcomments));
         }
      }
   }
}