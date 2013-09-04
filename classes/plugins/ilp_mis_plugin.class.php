<?php
/**
 * An abstract class that holds methods and attributes common to all mis plugin
 * classes.
 *
 * @abstract
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


//require the ilp_plugin.php class
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_plugin.class.php');

//require the ilp_mis_connection.php file 
require_once($CFG->dirroot . '/blocks/ilp/classes/database/ilp_mis_connection.php');


abstract class ilp_mis_plugin extends ilp_plugin
{

    public $templatefile;

    /*
      * This var should hold the connection to the mis database
      */
    public $db;

    /*
      * This var should hold the tabletype used by the plugin in queries
      */
    public $tabletype;

    /*
      * This var should hold the data retrieved from the dbquery function
      */
    public $data;

    protected $params; //initialisation params set at invocation time


    /**
     * Constructor
     */
    function __construct($params)   {
        global $CFG;

        //set the directory where plugin files of type ilp_dashboard_tab are stored
        $this->plugin_class_directory = $CFG->dirroot . "/blocks/ilp/plugins/mis";

        //set the table that the details of these plugins are stored in
        $this->plugintable = "block_ilp_mis_plugin";

        //call the parent constructor
        parent::__construct();

        //set the name of the template file should be a html file with the same name as the class
        $this->templatefile = $this->plugin_class_directory . '/' . $this->name . '.html';

        $this->set_params($params);
        $this->db = new ilp_mis_connection($params);
    }

    /*
    * read data from the MIS db connection
    * @param string $table
    * @param array $whereparams
    * @param string $fields
    * @param array $additionalargs
    * @return array
    */
    protected function dbquery($table, $params = null, $fields = '*', $addionalargs = null,$prelimcalls = null) {
    	if (!empty($prelimcalls))	$this->db->prelimcalls[]	=	$prelimcalls;

        return ($this->tabletype == ILP_MIS_STOREDPROCEDURE)
                ? $this->db->return_stored_values($table, $params)
                : $this->db->return_table_values($table, $params, $fields, $addionalargs);
    }

//See the extended comment at the end of this file about using the $T timer for testing
/**
 * Construct an sql query and use that as a cache-key to see if the data has been cached.
 * Note that different caches can be passed in and that the default cache has a ttl of
 * 6 hours at the moment. If you want shorter term caching, then define a new cache
 * and pass it in.
 */
    protected function cached_dbquery($table, $params = null, $fields = '*', $addionalargs = null,$prelimcalls = null,$cachename='ilp_miscache')
    {
       global $CFG,$T;
       if (!empty($prelimcalls))	$this->db->prelimcalls[]	=	$prelimcalls;

       $CACHE=cache::make('block_ilp',$cachename);

       if($this->tabletype == ILP_MIS_STOREDPROCEDURE)
       {
          $sql=$this->db->sql_for_stored_values($table, $params);
       }
       else
       {
          $sql=$this->db->sql_for_table_values($table, $params, $fields, $addionalargs);
       }

       if(($r=$CACHE->get($sql))===false)
       {
          if(isset($CFG->mis_debug))
          {
             print "Cache Miss: running $sql<br>";
             $T->lap();
          }
          ($r=$this->db->dbquery_sql($sql) or $r=array());
          $CACHE->set($sql,$r);
          if(isset($CFG->mis_debug))
          {
             $T->plap('Took ');
             if($CFG->mis_debug>1)
                print_object($r);
          }
       }
       elseif(isset($CFG->mis_debug))
       {
          print "Cache hit on $sql<br>";
       }
       return $r;
    }

    /**
     * Installs any new plugins
     */
    public static function install_new_plugins($dbplugins=array(),$plugin_class_directory="")   {
        global $CFG;

        // include the ilp db
        require_once($CFG->dirroot . '/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the ilp db class needed as this function will be called 
        //when not in object context
        $dbc = new ilp_db();

        //call the install new plugins function from the parent class
        //pass the list of plugins currently installed to it
        parent::install_new_plugins($dbc->get_mis_plugins(), $CFG->dirroot . "/blocks/ilp/plugins/mis");

    }


    /**
     * This fucntion updates the install plugin record it sets the plugin type (overview or detail)
     */
    function install($plugin_id)    {
        $misplugin = $this->dbc->get_mis_plugin_by_id($plugin_id);

        $misplugin->type = static::plugin_type();

        $this->dbc->update_mis_plugin($misplugin);
    }

    /**
     * Force extending class to implement a display function
     */
    abstract function display();

    /**
     * Force extending class to implement the plugin type function
     */
//    abstract static function plugin_type(); //Abstract static is meaningless in PHP (but allowed syntactically)

    protected function set_params($params)  {
        $this->params = $params;
    }

    public function set_data($mis_user_id,$user_id=null) {

    }

    public function has_data()
    {
       return !empty($this->data);
    }

    function config_settings(&$settings)    {
        return $settings;
    }

    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name() {
        return 'MIS Plugin';
    }
}
/**
 * Stopwatch code. Put this at the start of config.php (immediately after the <?php
 * tag) to allow the $T code above to work. $T is a global, so it can be used anywere
 * if defined in this way.
 *
 * Then, after the unset($CFG); line add $CFG->mis_debug=1 (or 2)

class Stopwatch
{
   private $t;
   private $l;
   private $e;
   function __construct()
   {
      $this->start();
   }

   function start()
   {
      $this->e=0;
      $this->t=$this->l=microtime(true);
   }
// Store the time since the last "lap" start, and
// start a new lap
   function lap()
   {
      $n=microtime(true)-$this->l;
      $this->l=microtime(true);
      return $n;
   }

//Store the time since the start.
   function stop()
   {
      return $this->e=microtime(true)-$this->t;
   }

//Print messages followed by lap or final times.
   function plap($message='')
   {
      print $message.' '.$this->lap().' ';
   }

   function pstop($message='')
   {
      $this->stop();
      print $message.' '.$this->e.' ';
   }

}

$T=new Stopwatch();
$T->start();

*/