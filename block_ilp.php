<?php
/**
 * Block class for the ilp
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

class block_ilp extends block_list {
   /**
    * Sets initial block variables. Part of the blocks API
    *
    * @return void
    */
   function init() {
      $this->title = get_string('blockname', 'block_ilp');
   }

   /**
    * Sets up the content for the block.
    *
    * @return object The content object
    */
   function get_content() {
      global $CFG, $USER, $COURSE, $SITE;

      if(!isloggedin())
	return $this->content;

      // include  db class
      require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

      // include the parser class
      require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_parser.class.php');

      // include the lib file
      require_once($CFG->dirroot.'/blocks/ilp/lib.php');

      // db class manager
       $dbc = new ilp_db();

       // get the course
       $course = $dbc->get_course($COURSE->id);

       // cache the content of the block
       if($this->content !== null) {
           return $this->content;
       }

       //get all course that the current user is enrolled in
       $my_courses		=	$dbc->get_user_courses($USER->id);
       $access_viewilp		=	false;
       $access_viewotherilp	= 	false;

       if (empty($my_courses))	{
           $c		=	new stdClass();
           $c->id		=	$COURSE->id;
           $my_courses	=	array($c);
       }

      //we are going to loop through all the courses the user is enrolled in so that we can
      //choose which display they will see

      $found_current_course = false;
      $sitecontext = context_system::instance();
      $viewall=(has_capability('block/ilp:ilpviewall', $sitecontext,$USER->id,false) or ilp_is_siteadmin($USER));
      $initial_course_id=0;

      foreach($my_courses as $c) {
         $coursecontext = context_course::instance($c->id);
         $set_course_groups_link = false;

         //we need to get the capabilites of the current user so we can deceide what to display in the block
         if (!empty($coursecontext) && has_capability('block/ilp:viewilp', $coursecontext,$USER->id,false) ) {
            $access_viewilp		=	true;
         }

         if ($viewall or (!empty($coursecontext) and has_capability('block/ilp:viewotherilp', $coursecontext,$USER->id,false))) {
            $access_viewotherilp	=	true;
            $set_course_groups_link = true;
         }

         if( $set_course_groups_link and !$found_current_course ){
            $initial_course_id	=	$c->id;
            if( $c->id == $COURSE->id ){
               //current course is part of my_courses, so this should be the preselection for the linked page
               //so stop changing the value for the link
               $found_current_course = true;
            }
         }

         if($found_current_course and $access_viewilp)
            break;  //Nothing more to be learnt
      }

      $usertutees=$dbc->get_user_tutees($USER->id);
      $this->content = new stdClass;
      $this->content->footer = '';

      //check if the user has the viewotherilp capability
      if (!empty($access_viewotherilp) || !empty($usertutees)) {

         $tutor = 0;
         if (!empty($access_viewotherilp)) {
            $label = get_string('mycoursegroups', 'block_ilp');
            $url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$initial_course_id}";
            $this->content->items[] = "<a href='{$url}'>{$label}</a>";
            $this->content->icons[] = "";
         }

         if (!empty($usertutees)) {
            $label = get_string('mytutees', 'block_ilp');
            $url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
            $this->content->items[] = "<a href='{$url}'>{$label}</a>";
            $this->content->icons[] = "";
            $tutor = 1;
         }

         $course_id=$initial_course_id;
         $printlink = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/define_batch_print.php?course_id=' . $course_id . '&tutor=' . $tutor . '">';
         $printicon = get_string("print","block_ilp") . '</a>';
         $allow_batch_print = get_config('block_ilp', 'allow_batch_print');
          if ($allow_batch_print !== '0') {
             $this->content->items[] = $printlink . $printicon;
             $this->content->icons[] = '';
          }

      } else if(isloggedin()) {
         // Show additional items (current status, progress bar etc. based on config
         require_once($CFG->dirroot . '/blocks/ilp/plugins/dashboard/ilp_dashboard_student_info_plugin.php');
         $student_info_plugin = new ilp_dashboard_student_info_plugin($USER->id);
         $blockitems = $student_info_plugin->display(null, true);

          $course_id = (!empty($COURSE->id)) ? $COURSE->id : $initial_course_id;
          $courseurl	=	(!empty($course_id) && $course_id != 1) ? "&course_id={$course_id}" : '';
          $url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$USER->id}$courseurl";
          $coreprofileurl  = "{$CFG->wwwroot}/user/profile.php?id={$USER->id}";

          if (get_config('block_ilp', 'show_userpicture')) {
              $this->content->items[] = $blockitems['picture'];
          }

          if (get_config('block_ilp', 'show_linked_name')) {
              $this->content->items[] = html_writer::link($coreprofileurl, $blockitems['name']);
          }

         //additional check to stop users from being able to access the ilp in course context
         //from the front page


         $this->content->text	= "";

         $label = get_string('mypersonallearningplan', 'block_ilp');

         $this->content->items[] = "<p><a href='{$url}'>{$label}</a><p/>";
         $this->content->icons[] = "";

          if (get_config('block_ilp', 'show_current_status')) {
              $this->content->items[] = $blockitems['status'];
          }

          if (get_config('block_ilp', 'show_progressbar')) {
              $this->content->items[] = $blockitems['progress'];
          }

          if (get_config('block_ilp', 'show_attendancepunctuality')) {
              if ($blockitems['att_percent']) {
                  $att_line = get_string('attendance', 'block_ilp') . ': ' . $blockitems['att_percent'];
                  $this->content->items[] = $att_line;
              }
              if ($blockitems['pun_percent']) {
                  $pun_line = get_string('punctuality', 'block_ilp') . ': ' . $blockitems['pun_percent'];
                  $this->content->items[] = $pun_line;
              }

          }
      }

       $allow_export = get_config('block_ilp', 'allow_export');

      if($dbc->ilp_admin() && $allow_export !== '0')
      {
         $label=get_string('export','block_ilp');
         $this->content->items[] = "<a href='$CFG->wwwroot/blocks/ilp/actions/define_batch_export.php?course_id=$course_id'>$label</a>";
         $this->content->icons[] = "";
      }

      return $this->content;
   }



   /**
    * Allow the user to set sitewide configuration options for the block.
    *
    * @return bool true
    */
   function has_config() {
      return true;
   }


   /**
    * Allow the user to set specific configuration options for the instance of
    * the block attached to a course.
    *
    * @return bool true
    */
   function instance_allow_config() {

      return false;
   }




   /**
    * Only allow this block to be mounted to a course or the home page.
    *
    * @return array
    */
   function applicable_formats() {
      return array(
         'site-index'  => true,
         'course-view' => true,
         'my' => true
         );
   }

   /**
    * Prevent the user from having more than one instance of the block on each
    * course.
    *
    * @return bool false
    */
   function instance_allow_multiple() {
      return false;
   }

   /*
    * Functions that we want to run directly after the block has been installed
    *
    */
   function after_install() {

      global $CFG;

      //call the install.php script (used for moodle 2) that has the operations that need to be carried
      //out after installation
      require_once($CFG->dirroot.'/blocks/ilp/db/install.php');

      //call the block_ilp_install function used by moodle 2.0
      xmldb_block_ilp_install();

   }

   function cron() {
      global $CFG;
      require_once($CFG->dirroot."/blocks/ilp/classes/ilp_cron.class.php");
      $cron	=	 new ilp_cron();
      $cron->run();
   }

}
?>
