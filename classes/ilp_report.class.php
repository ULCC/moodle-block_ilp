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
 * Return an array of all the currently known reports
 * whether enabled or not
 * @param boolean includeDeleted
 *
 * @return array of report *objects*; may be empty
 */
   static function get_all_reports($includeDeleted=false)
   {
      global $DB;

      $r=array();

      foreach($DB->get_fieldset_select('block_ilp_report','id','1') as $rid)
      {
         $report=static::from_id($rid);
         if(!$report->deleted or $includeDeleted)
            $r[$rid]=$report;
      }

      return $r;
   }

/**
 * Get all the enabled reports except the ones passed in
 * @param array of ints $not_wanted the reports not to return
 *
 * @return array of report objects
 */
   static function get_enabledreports($not_wanted=array())
   {
      $all=static::get_all_reports();

      if(empty($all))
         return $all;

      return array_filter($all,function($report) use($not_wanted)
                          {
                             return (!in_array($report->id,$not_wanted) and $report->status==ILP_ENABLED);
                          });
   }

/**
 * Something has happened to the user; clear them from
 * the capability cache.
 *
 * @param $data an object of some kind, depending on event
 */
   static function userchanged($data)
   {
      $cache=cache::make('block_ilp','user_capability_cache');
      $cache->delete($data->userid);
      return true;
   }

///Instance

   protected $report_fields;
   protected $plugins;

   function __construct()
   {
      $this->dbc=new ilp_db();
      $this->cache=cache::make('block_ilp','user_capability_cache');
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
         $flag=$this->dbc->has_report_permission($this->id,$role_ids,$capability->id);
      }
      else
      {
         $flag=false;
      }

      $cacheline[$user][$context->id][$cap]=$flag;

      $this->cache->set($user,$cacheline);

      return $flag;
   }

   protected function set_my_report_fields()
   {
      global $DB;
      $this->fields=$DB->get_records('block_ilp_report_field',array('report_id'=>$this->id));
   }


   function get_report_fields_by_position($position=false,$type=false)
   {
      if(!isset($this->report_fields))
         $this->set_my_report_fields();

      if(!$position)
         return $this->fields;

      $otherfield=$type ? $position-1 : $position+1;

      return array_filter($this->fields,function($item) use($position,$otherfield)
                          {
                             return ($position===false or
                                     ($item->position==$position or $item->position==$otherfield));
                          });
   }

   function get_all_fields()
   {
      return $this->get_report_fields_by_position();
   }

    /**
     *
     * Returns whether the given report has a plugins field
     * @param int $report_id the id of the report that we will
     * check if it has a the plugins field
     *
     * @return	bool true or false
     */
    protected function set_plugin_fields()
    {
       global $DB;

       $this->plugins=array();

       $sql = "SELECT   *
         FROM   {block_ilp_plugin} as p,
             {block_ilp_report_field} as rf
         WHERE   rf.plugin_id = p.id
         AND   rf.report_id = :report_id";

       foreach($DB->get_records_sql($sql, array('report_id'=>$this->id)) as $item)
       {
          $this->plugins[$item->name]=$item;
       }
    }

    public function has_plugin_field($name)
    {
       if(!isset($this->plugins))
          $this->set_plugin_fields();

       if(isset($this->plugins[$name]))
       {
          return $this->plugins[$name];
       }
       return false;
    }

    function get_user_report_entries($user_id,$state_id=null,$createdby=null)
    {
       global $DB;
       $tables  = "";
       $where  = "";

       //if the the id of a status has been given then we need to add more conditions to
       //find the reports in this state

       $params = array('report_id'=>$this->id, 'user_id'=>$user_id);

       if (!empty($state_id)) {
          $tables  = ", {block_ilp_plu_ste_ent} as se";
          $params['state_id'] = $state_id;
          $where  = "  AND e.id = se.entry_id
           AND se.parent_id = :state_id";
       }

       if (!empty($createdby)) {
          if ($createdby  ==  ILP_CREATED_BY_USER)    {
             $params['user_id1'] = $user_id;
             $where .= "AND creator_id = :user_id1";
          } else if ($createdby  ==  ILP_NOTCREATED_BY_USER)    {
             $params['user_id1'] = $user_id;
             $where .= "AND creator_id != :user_id1";
          }
       }

       $sql="SELECT  e.*
                     FROM  {block_ilp_entry} as e
                           {$tables}
                     WHERE  e.report_id  = :report_id
                            AND   e.user_id     = :user_id
                            {$where}
                     ORDER BY   e.timemodified DESC";

       return $DB->get_records_sql($sql, $params);
    }

    function export_all_entries($userid)
    {
       global $DB;

       $creators=$pluginRecords=$pluginInstances=$pluginFieldsLoaded=array();

       foreach($this->get_user_report_entries($userid) as $report_entry)
       {
          $row=new stdClass;
          foreach ($this->get_all_fields() as $field) {
             //get the plugin record that for the plugin, with cacheing
             if(!isset($pluginRecords[$field->plugin_id]))
             {
                $pluginRecords[$field->plugin_id]=$this->dbc->get_plugin_by_id($field->plugin_id);
             }

             $pluginrecord=$pluginRecords[$field->plugin_id];

             $tablename=$pluginrecord->tablename;

             //Get the data from table name for the current entry
/*
             $sql="SELECT * FROM {$table}_ent ent join {$table} p on p.id=ent.parent_id
                            WHERE p.reportfield_id=:fieldid and ent.entry_id=:entryid";

             $data=$DB->get_record_sql($sql,array('fieldid'=>$field->id,
                                                  'entryid'=>$report_entry->id));
*/

             //take the name field from the plugin as it will be used to call the instantiate the plugin class
             $classname = $pluginrecord->name;

//More caching
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

             $pluginclass=$pluginInstances[$classname];

             if ($pluginclass->is_viewable() != false)
             {
                $pluginclass->view_data($field->id,$report_entry->id,$row);
             }
          }
          print_object($row);
       }
    }
}
