<?php
/**
 * Top-level report class for the ILP block module.
 *
 * Assumes Moodle 2.4
 *
 * @copyright &copy; 2013 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
include_once("$CFG->dirroot/blocks/ilp/db/ilp_db.php");

class ilp_report
{

// This is intended to produce an object which
// is backward compatible. So, for example, $report->status
// will still work anywhere in the code.
//
// Magic methods could be used to a similar effect but ultimately
// the class should be extended to a rigorous interface with
// type checking and so forth.
//
   static function from_id($id)
   {
      $dbc=new ilp_db();

      $r=$dbc->get_record('block_ilp_report', array('id' => $id));

      $report=new self();

      foreach($r as $name=>$value)
      {
         $report->$name=$value;
      }
      return $report;
   }

///Instance

   function __construct()
   {
      $this->dbc=new ilp_db();
   }

   function can_view($context,$user)
   {
      static $cached=array();

      if($this->status!=ILP_ENABLED)
         return false;

      if(is_object($user))
         $user=$user->id;

      if(isset($cached[$context->id][$user]))
         return $cached[$context->id][$user];

      $role_ids= array();

      $authuserrole=$this->dbc->get_role_by_name(ILP_AUTH_USER_ROLE);
      if (!empty($authuserrole)) $role_ids[]=$authuserrole->id;

      if ($roles = get_user_roles($context, $user))
      {
         foreach ($roles as $role)
         {
            $role_ids[]= $role->roleid;
         }
      }

      $access_report_viewreports= false;
      $capability=$this->dbc->get_capability_by_name('block/ilp:viewreport');

      if (!empty($capability))
         $access_report_viewreports=$this->dbc->has_report_permission($this->id,$role_ids,$capability->id);

      return $cached[$context->id][$user]=!empty($access_report_viewreports);
   }
}
