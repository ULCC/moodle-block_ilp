<?php
/**
 * This class provides a mform that allows the user to select
 * what to include in a batch print job
 *
 * @copyright &copy; 2013 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


class batch_print_setup_mform extends ilp_moodleform
{
   protected $dbc;
   protected $tutor;

   function __construct($url,$tutor=false)
   {
      $this->dbc=new ilp_db();

      $this->tutor=$tutor;

      parent::__construct($url);
   }

   function definition()
   {
      global $USER,$CFG;

      $dbc=$this->dbc;

      $mform=&$this->_form;

//get all enabled reports in this ilp
      $reports = $dbc->get_reports(ILP_ENABLED);

      $reportoptions=array();

      foreach($reports as $r)
      {
         print $reportoptions[$r->id]=$r->name;
      }

      if(empty($reportsoptions))
      {
         print_error(get_string('noreports','block_ilp'));
      }

      if(!$this->tutor)
      {
//get all courses that the current user is enrolled in

         $courseoptions=$groupoptions=array();

         $allcourses = $dbc->get_user_courses($USER->id);

         foreach($allcourses as $id=>$c)
         {
            $courseoptions[$id]=$c->fullname;

            $groups = groups_get_all_groups($id);
            foreach($groups as $g)
            {
               $groupoptions[$g->id]=$g->name;
            }
         }

//Sort courses by name and create drop down.
         asort($courseoptions);
         $mform->addElement('select','course',get_string('course'),$courseoptions);

         if(!empty($groupoptions))
         {
//Put the groups in to name order and create drop down
            asort($groupoptions);
            $mform->addElement('select','group',get_string('group'),$groupoptions);
         }
      }

      if(true)
      {
      } else {
         //get the list of tutees for this user
         $student = $dbc->get_user_tutees($USER->id);

         $pagetitle = get_string('mytutees','block_ilp');
      }

//      $states = $dbc->get_status_items(ILP_DEFAULT_USERSTATUS_RECORD);

//      print_object($states);

      asort($reportoptions);
      $s=$mform->addElement('select','reportselect',get_string('printreports','block_ilp'),$reportoptions);
      $s->setMultiple(true);
      $mform->addHelpButton('reportselect','batchreportselect','block_ilp');

      $mform->addElement('checkbox','showattendance',get_string('showattendance','block_ilp'));

      $mform->addElement('checkbox','showpuntuality',get_string('showpunctuality','block_ilp'));

      $this->add_action_buttons();

   }
}