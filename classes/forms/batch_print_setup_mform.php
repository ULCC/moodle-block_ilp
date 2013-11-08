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

   function __construct($url,$imports=array())
   {
      $this->dbc=new ilp_db();

      parent::__construct($url,$imports);
   }

   function definition()
   {
      global $USER,$CFG,$DB;

      $dbc=$this->dbc;

      $mform=&$this->_form;

      $imports=$this->_customdata;

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

      if(!$imports['tutor'])
      {
         $courseid=isset($imports['course_id'])? $imports['course_id'] : 0 ;

//get all courses that the current user is enrolled in
         $courseoptions=$groupoptions=array();

         if($dbc->ilp_admin())
         {
            $adminflag=true;
            $rawcourses=$DB->get_recordset('course');
         }
         else
         {
            $adminflag=false;
            $rawcourses=$dbc->get_user_courses($USER->id);
         }

         $adminflag=($adminflag or
                     has_capability('block/ilp:ilpviewall',context_system::instance(),$USER->id,false) or
                     ilp_is_siteadmin($USER));

         foreach($rawcourses as $id=>$c)
         {
            if(!$adminflag) //Need to check each course for permission
            {
               if(!has_capability('block/ilp:viewotherilp', context::instance_by_id($c->ctxid),$USER->id,false))
               {
                  unset($rawcourses[$id]);
                  continue;
               }
            }

            $courseoptions[$id]=$c->shortname;

            foreach(groups_get_all_groups($id) as $g)
            {
               if(!$courseid or $courseid==$g->courseid)
               {
                  $groupoptions[$g->id]=$g->name;
               }
            }
         }

         if(!$rawcourses)
         {
            print_error(get_string('nocoursesbatch','block_ilp'));
         }
//Sort courses by name and create drop down.
         natcasesort($courseoptions);
         $mform->addElement('select','course_id',get_string('course'),$courseoptions);
         $mform->setDefault('course_id',$imports['course_id']);

//Put the groups in to name order and create drop down
         natcasesort($groupoptions);
         $mform->addElement('select','group_id',get_string('group'),$groupoptions);
         $mform->setDefault('group_id',$imports['group_id']);

      }

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
      $s=$mform->addElement('select','reportselect',get_string('printreports','block_ilp'),$reportoptions);
      $s->setMultiple(true);
      $mform->addRule('reportselect',get_string('required'),'required',null,'client');
      $mform->addHelpButton('reportselect','batchreportselect','block_ilp');

      $mform->addElement('checkbox','showattendance',get_string('showattendance','block_ilp'));
      $mform->setDefault('showattendance',true);

      $mform->addElement('checkbox','showcomments',get_string('showcomments','block_ilp'));
      $mform->setDefault('showcomments',true);

      $buttonarray=array();
      $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('gotoprintpreview','block_ilp'));
      $buttonarray[] = &$mform->createElement('cancel');
      $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
      $mform->closeHeaderBefore('buttonar');

   }
}
