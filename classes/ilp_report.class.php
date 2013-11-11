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
include_once("$CFG->dirroot/blocks/ilp/classes/database/ilp_db.php");

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

      static $r;

      if(isset($r))
         return $r;

      $r = array();

      foreach($DB->get_fieldset_select('block_ilp_report','id','1 = 1 ') as $rid)
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

      $reports = array_filter($all,function($report) use($not_wanted)
                          {
                             return (!in_array($report->id,$not_wanted) and $report->status==ILP_ENABLED);
                          });

      usort( $reports, 'ilp_report::reportSortByPosition' );

      return $reports;
   }

   static function reportSortByPosition( $a, $b ) {
    return $a->position == $b->position ? 0 : ( $a->position > $b->position ) ? 1 : -1;
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

/**
 * Return the url for the current institute seal
 * @return string
 *
 */
   static function seal_url()
   {
      global $CFG;

      $filename=get_config('block_ilp','sealname');

      if($filename)
      {
         $context=context_system::instance();
         return "$CFG->wwwroot/pluginfile.php/$context->id/block_ilp/seal/1/$filename";
      }

      return '';
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
       global $CFG;
      static $userroles=array();

      if(is_object($user))
         $user=$user->id;

      if($this->dbc->ilp_admin())
      {
         return true;
      }

      $cacheline=$this->cache->get($user);
      if($cacheline!==false and isset($cacheline[$user][$context->id][$this->id][$cap]))
      {
         return $cacheline[$user][$context->id][$this->id][$cap];
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

         $authuserrole=$this->dbc->get_role_by_id($CFG->defaultuserroleid);
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

      $cacheline[$user][$context->id][$this->id][$cap]=$flag;

      $this->cache->set($user,$cacheline);

      return $flag;
   }

   protected function set_my_report_fields()
   {
      global $DB;
      $this->fields=$DB->get_records('block_ilp_report_field',array('report_id'=>$this->id),'position');
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

    function export_all_entries($users,$format='excel')
    {
       global $DB,$CFG;

       include_once("$CFG->libdir/tablelib.php");

       $userheaders=array('idnumber','username','firstname','lastname','email','status'=>'u_status','userid'=>'id');

       $rows=$headers=array();

       foreach($userheaders as $altstring=>$h)
       {
          if(!is_numeric($altstring))
          {
             $headers[$h]=get_string($altstring,'block_ilp');
          }
          else
          {
             $headers[$h]=get_string($h);
          }
       }

       foreach($users as $user)
       {
          if(is_numeric($user))
          {
             $user=$DB->get_record('user',array('id'=>$user));
          }

          $userid=$user->id;

          $creators=$pluginRecords=$pluginInstances=$pluginFieldsLoaded=array();

          foreach($this->get_user_report_entries($userid) as $report_entry)
          {
             $row=array();

             foreach($userheaders as $h)
             {
                $row[$h]=$user->$h;
             }

             foreach ($this->get_all_fields() as $field) {
                //get the plugin record that for the plugin, with cacheing
                if(!isset($pluginRecords[$field->plugin_id]))
                {
                   $pluginRecords[$field->plugin_id]=$this->dbc->get_plugin_by_id($field->plugin_id);
                }

                $pluginrecord=$pluginRecords[$field->plugin_id];

                $tablename=$pluginrecord->tablename;

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

                if ($pluginclass->is_viewable() and $pluginclass->is_exportable())
                {
                   $item=new stdClass;
                   $itemname=$field->id.'_field';
                   $headers[$itemname]=$this->strip_word_html($field->label);

                   $item->$itemname='';
                   $pluginclass->export_data($field->id,$report_entry->id,$item);

                   foreach((array)$item as $fieldname=>$value)
                   {
                      $row[$fieldname]=$this->strip_word_html($value);
                   }
                }
             }
             $rows[]=$row;
          }
       }

       $table=new flexible_table('exporter');

       $table->setup();
       $table->define_columns(array_keys($headers));
       $table->define_headers($headers);

       $exname="table_{$format}_export_format";

       $ex=new $exname($table);

       $ex->start_document($this->name);
       $ex->start_table('Sheet1');

       $ex->output_headers($headers);

       foreach($rows as $row)
       {
          $ex->add_data($table->get_row_from_keyed($row));
       }

       $ex->finish_table();

       $ex->finish_document();
    }

//From php.net
    function strip_word_html($text, $allowed_tags = '')
    {
       mb_regex_encoding('UTF-8');

       //replace MS special characters first
       $search = array('/&lsquo;/u', '/&rsquo;/u', '/&ldquo;/u', '/&rdquo;/u', '/&mdash;/u');
       $replace = array('\'', '\'', '"', '"', '-');
       $text = preg_replace($search, $replace, $text);

       //make sure _all_ html entities are converted to the plain ascii equivalents - it appears
       //in some MS headers, some html entities are encoded and some aren't
       $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

       //try to strip out any C style comments first, since these, embedded in html comments, seem to
       //prevent strip_tags from removing html comments (MS Word introduced combination)
       if(mb_stripos($text, '/*') !== FALSE){
          $text = mb_eregi_replace('#/\*.*?\*/#s', '', $text, 'm');
       }

       //introduce a space into any arithmetic expressions that could be caught by strip_tags so that they won't be
       //'<1' becomes '< 1'(note: somewhat application specific)
       $text = preg_replace(array('/<([0-9]+)/'), array('< $1'), $text);
       $text = strip_tags($text, $allowed_tags);

       //eliminate extraneous whitespace from start and end of line, or anywhere there are two or more spaces, convert it to one
       $text = preg_replace(array('/^\s\s+/', '/\s\s+$/', '/\s\s+/u'), array('', '', ' '), $text);

       //strip out inline css and simplify style tags
       $search = array('#<(strong|b)[^>]*>(.*?)</(strong|b)>#isu', '#<(em|i)[^>]*>(.*?)</(em|i)>#isu', '#<u[^>]*>(.*?)</u>#isu');
       $replace = array('<b>$2</b>', '<i>$2</i>', '<u>$1</u>');
       $text = preg_replace($search, $replace, $text);

       //on some of the ?newer MS Word exports, where you get conditionals of the form 'if gte mso 9', etc., it appears
       //that whatever is in one of the html comments prevents strip_tags from eradicating the html comment that contains
       //some MS Style Definitions - this last bit gets rid of any leftover comments */
       $num_matches = preg_match_all("/\<!--/u", $text, $matches);
       if($num_matches){
          $text = preg_replace('/\<!--(.)*--\>/isu', '', $text);
       }
       return $text;
    }

//Is it possible to do anything (other than delete) with this report
//in respect of this user.
//Needs refactored; the student is only involved in one test,
//Should be split into report_locked and report_available or
//something like that?
    function report_availabilty($student_id)
    {
       global $CFG;

       $now=time();

       //if a maximum number of entries has been set lets see if the student has reached this number
       if (!empty($this->reportmaxentries))  {
          $studententries   =   $this->dbc->count_report_entries($this->report_id,$student_id);

          if ($studententries >= $this->reportmaxentries) {
             $extension  =   $this->extension_check('reportmaxentries');

             if (empty($extension) || $studententries >= $extension->value) {
                $temp             =   new stdClass();
                $temp->entries    =   $studententries;
                $temp->maxentries    =  (!empty($extension))? $extension->value : $this->reportmaxentries;
                return false;
             }
          }
       }

       //if this report has a lock date check if the date has passed
       if ($this->reporttype ==  ILP_RT_RECURRING_FINALDATE  || $this->reporttype == ILP_RT_FINALDATE)   {

          if ( $this->reportlockdate < $now )   {
             //find out if this student has been given a report extension
             $extension  =   $this->extension_check('reportlockdate');
             if (empty($extension) || $extension->value < $now) {
                $temp               =   new stdClass();
                $temp->expiredate   =  (!empty($extension))? date('d-m-Y',$extension->value) : date('d-m-Y',$this->reportlockdate);
                return false;
             }
          }
       }

       //if the report is a recurring report
       if ($this->reporttype ==  ILP_RT_RECURRING || $this->reporttype ==  ILP_RT_RECURRING_FINALDATE)   {
          $recurringstart  =   0;

          if ($this->recurstart == ILP_RECURRING_REPORTCREATION)   {
             //rules started at report creation
             $recurringstart  =   $this->timecreated;
          }   else if ($this->recurstart == ILP_RECURRING_SPECIFICDATE) {
             //rules started at specific date
             $recurringstart  =   $this->recurdate;
          }  else {
             //rules started at first entry
             $studententries =   $this->dbc->get_user_report_entries($this->id,$student_id);

             if (!empty($studententries))    {
                //get the creation time of the first user entry
                $recurringstart  = reset($studententries);
                $recurringstart = $recurringstart->timecreated;
             }
          }

          if (!empty($recurringstart)) {
             $recurringperiod    =   $this->recurring_period($recurringstart,$this->recurfrequency);

             $entriescount       =   $this->dbc->count_report_entries($this->id,$student_id,$recurringperiod['start'],$recurringperiod['end']);

             if (!empty($entriescount) && $entriescount >= $this->recurmax) {
                $extension  =   $this->extension_check('recurmax');
                if (empty($extension) || $extension->value <= $entriescount) {
                   $temp             =   new stdClass();
                   $temp->entries    =   $entriescount;
                   $temp->maxentries   =  (!empty($extension))? $extension->value : $this->recurmax;
                   return false;
                }
             }
          }
       }
       return true;
    }

    function extension_check($param,$action="report_extension")
    {
       $preferencerecords =   $this->dbc->get_preferences($this->report_id,null,$action,$this->student_id);
       $preference =   false;

       foreach($preferencerecords as $p)
       {
          if ($p->param == $param)
          {
             $preference =   $p;
             break;
          }
       }
       return $preference;
    }

    /**
     * returns the start and end dates for the recurring period that the user is currently in
     *
     * @param $recurringstart        timestamp of the date when the recurring period started
     * @param $frequency    the frequency of the recurring period
     *
     */
    function recurring_period($recurringstart,$frequency)
    {
       if ($frequency  ==  ILP_RECURRING_DAY){
          //$start
          $strstart   =   date("d-m-Y",$recurringstart);
          $recurringend   =   strtotime("{$strstart} + 1 day");
          if ( time() > $recurringstart && time() < $recurringend )   {
             return array('start'=>$recurringstart,'end'=>$recurringend);
          } else  {
             return $this->recurring_period($recurringend,$frequency);
          }
       } else {
          $strstart   =   date("d-m-Y",$recurringstart);
          $recurringend   =   strtotime("{$strstart} + $frequency weeks");
          if ( time() > $recurringstart && time() < $recurringend )   {
             return array('start'=>$recurringstart,'end'=>$recurringend);
          } else  {
             return $this->recurring_period($recurringend,$frequency);
          }
       }
    }

    function can_add_extensions()
    {
       return !($this->frequency ==1 && $this->reporttype==1 && $this->reportmaxentries==null);
    }
}
