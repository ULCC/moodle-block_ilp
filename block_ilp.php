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

      // include  db class
      require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

      // include the parser class
      require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_parser.class.php');

      // include the lib file
      require_once($CFG->dirroot.'/blocks/ilp/lib.php');

      // db class manager
      $dbc = new ilp_db();

      // get the course id
      $course_id = optional_param('id', $SITE->id, PARAM_INT);

      //this is to handle the /user/view.php page where id is reserved for the userid ...
      //allow the current course to be course=XX
      $current_course_id = optional_param('course', null, PARAM_INT);
      if( !$current_course_id ){
         $current_course_id = optional_param('id', null, PARAM_INT); //if there's no explicit course id, id might be a course id
      }

      // get the course
      $course = $dbc->get_course($course_id);

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
         $c->id		=	$course_id;
         $my_courses	=	array($c);
      }

      //we are going to loop through all the courses the user is enrolled in so that we can
      //choose which display they will see

      $found_current_course = false;
      $sitecontext = get_context_instance(CONTEXT_SYSTEM);
      $viewall=(has_capability('block/ilp:ilpviewall', $sitecontext,$USER->id,false) or ilp_is_siteadmin($USER));
      $initial_course_id=0;

      foreach($my_courses as $c) {
         $coursecontext = get_context_instance(CONTEXT_COURSE, $c->id);
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
            if( $c->id == $current_course_id ){
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
         }

      } else {

         //additional check to stop users from being able to access the ilp in course context
         //from the front page
         $courseurl	=	(!empty($course_id) && $course_id != 1) ? "&course_id={$course_id}" : '';

         $this->content->text	= "";

         $label = get_string('mypersonallearningplan', 'block_ilp');
         $url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$USER->id}$courseurl";
         $this->content->items[] = "<p><a href='{$url}'>{$label}</a><p/>";
         $this->content->icons[] = "";
      }

      if($dbc->ilp_admin())
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
