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

include_once("$CFG->libdir/tablelib.php");

class batch_export_setup_mform extends ilp_moodleform
{
   protected $dbc;

   function __construct($url,$imports=array())
   {
      $this->dbc=new ilp_db();

      parent::__construct($url,$imports);
   }

   function definition()
   {
      global $USER,$CFG;

      $dbc=$this->dbc;

      $mform=&$this->_form;

      $imports=$this->_customdata;

//We just include this to be able to get the download list
      $table=new flexible_table('dummy');

//get all enabled reports in this ilp
      $reportoptions=array();
      foreach($dbc->get_reports(ILP_ENABLED) as $r)
      {
         $reportoptions[$r->id]=$r->name;
      }

      if(empty($reportoptions))
      {
         print_error(get_string('noreports','block_ilp'));
      }

      $courseid=isset($imports['course_id'])? $imports['course_id'] : 0 ;

//get all courses that the current user is enrolled in
      $courseoptions=$groupoptions=array();

      foreach($dbc->get_courses() as $id=>$c)
      {
         $courseoptions[$id]=$c->shortname;

         foreach(groups_get_all_groups($id) as $g)
         {
            if(!$courseid or $courseid==$g->courseid)
            {
               $groupoptions[$g->id]=$g->name;
            }
         }
      }

//Get list of possible export formats
      $mform->addElement('select','format',get_string('format'),$table->get_download_menu());
      $mform->setDefault('format',$table->defaultdownloadformat);

//Sort courses by name and create drop down.
      natcasesort($courseoptions);
      $courseoptions=array(0=>'Any')+$courseoptions;
      unset($courseoptions[SITEID]);
      $mform->addElement('select','course_id',get_string('course'),$courseoptions);
      $mform->setDefault('course_id',$imports['course_id']);

//Put the groups in to name order and create drop down
      natcasesort($groupoptions);
      $mform->addElement('select','group_id',get_string('group'),$groupoptions);
      $mform->setDefault('group_id',$imports['group_id']);

      $status=array();
      foreach($dbc->get_status_items(ILP_DEFAULT_USERSTATUS_RECORD) as $s)
      {
         $status[$s->id]=$s->name;
      }

      $status=array(0=>get_string('anystatus','block_ilp'))+$status;

      if(!empty($status))
      {
         $mform->addElement('select','status_id',get_string('status','block_ilp'),$status);
         $mform->setDefault('status_id',$imports['status_id']);
      }

      natcasesort($reportoptions);
      $s=$mform->addElement('select','reportselect',get_string('report','block_ilp'),$reportoptions);

      $mform->addElement('checkbox','showattendance',get_string('showattendance','block_ilp'));

      $mform->disabledIf('reportselect','showattendance','checked');

      $buttonarray=array();
      $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('gotoprintpreview','block_ilp'));
      $buttonarray[] = &$mform->createElement('cancel');
      $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
      $mform->closeHeaderBefore('buttonar');

   }
}
