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
      global $DB;

      $r=$DB->get_record('block_ilp_report', array('id' => $id));

      $report=new self();

      foreach($r as $name=>$value)
      {
         $report->$name=$value;
      }
      return $report;
   }

/**
 * Something has happened to the user; clear them from
 * the capability cache.
 *
 * @param $data an object of some kind, depending on event
 */
   static function userchanged($data)
   {
      $cache=cache::make('ilp_block','user_capability_cache');
      $cache->delete($data->userid);
      return true;
   }

///Instance

   function __construct()
   {
      $this->dbc=new ilp_db();
      $this->cache=cache::make('ilp_block','user_capability_cache');
   }


/**
 * Roles and caps are slow, so we'll cache the results.
 * The cache key is just $userid
 * and the value is a 3d array of [$contextid][$roleid][$cap]
 * This way we can invalidate a user in one easy step if role
 * assignment is detected.
 *
 * @param $user either user object or just id
 * @param $context a full context object
 * @param $cap a string representing a capability
 * @return boolean
 */
   function has_cap($user,$context,$cap)
   {
      static $userroles=array();

      $site=context_system::instance();

      if(is_object($user))
         $user=$user->id;

      if(ilp_is_siteadmin($user) or has_capability('block/ilp:ilpviewall',$site))
      {
         return true;
      }

      $cacheline=$this->cache->get($user);
      if($cacheline!==false and isset($cacheline[$user][$context->id][$cap]))
      {
         return $cacheline[$user][$context->id][$cap];
      }

// Not in cache, try the mini-cache for roles
      if(isset($useroles[$user]))
      {
         $role_ids=$userroles[$user];
      }
      else
      {
// Nope, so we do it the hard way
         $role_ids= array();
         $cacheline=array();

         $authuserrole=$this->dbc->get_role_by_name(ILP_AUTH_USER_ROLE);
         if (!empty($authuserrole)) $role_ids[]=$authuserrole->id;

         if ($roles = get_user_roles($context, $user))
         {
            foreach ($roles as $role)
            {
               $role_ids[]= $role->roleid;
            }
         }

         $userroles[$user]=$role_ids;
      }

      $capability=$this->dbc->get_capability_by_name($cap);
      if (!empty($capability))
      {
         $flag=$this->dbc->has_report_permission($report_id,$role_ids,$capability->id);
      }
      else
      {
         $flag=false;
      }

      $cacheline[$user][$context->id][$cap]=$flag;

      $cache->set($user,$cacheline);

      return $flag;
   }
}