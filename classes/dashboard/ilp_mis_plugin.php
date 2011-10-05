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
require_once($CFG->dirroot . '/blocks/ilp/classes/dashboard/ilp_plugin.php');

//require the ilp_mis_connection.php file 
require_once($CFG->dirroot . '/blocks/ilp/db/ilp_mis_connection.php');


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
    function __construct($params)
    {
        global $CFG;

        //set the directory where plugin files of type ilp_dashboard_tab are stored
        $this->plugin_class_directory = $CFG->dirroot . "/blocks/ilp/classes/dashboard/mis";

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
    protected function dbquery($table, $params = null, $fields = '*', $addionalargs = null,$prelimcalls = null)
    {
    	if (!empty($prelimcalls))	$this->db->prelimcalls[]	=	$prelimcalls;
    	    	
        return ($this->tabletype == ILP_MIS_STOREDPROCEDURE)
                ? $this->db->return_stored_values($table, $params)
                : $this->db->return_table_values($table, $params, $fields, $addionalargs);
    }


    /**
     * Installs any new plugins
     */
    public function install_new_plugins()
    {
        global $CFG;

        // include the ilp db
        require_once($CFG->dirroot . '/blocks/ilp/db/ilp_db.php');

        // instantiate the ilp db class needed as this function will be called 
        //when not in object context
        $dbc = new ilp_db();

        //call the install new plugins function from the parent class
        //pass the list of plugins currently installed to it
        parent::install_new_plugins($dbc->get_mis_plugins(), $CFG->dirroot . "/blocks/ilp/classes/dashboard/mis");

    }


    /**
     * This fucntion updates the install plugin record it sets the plugin type (overview or detail)
     */
    function install($plugin_id)
    {
        $misplugin = $this->dbc->get_mis_plugin_by_id($plugin_id);

        $misplugin->type = $this->plugin_type();

        $this->dbc->update_mis_plugin($misplugin);
    }

    /**
     * Force extending class to implement a display function
     */
    abstract function display();

    /**
     * Force extending class to implement the plugin type function
     */
    abstract function plugin_type();

    protected function set_params($params)
    {
        $this->params = $params;
    }

    public function set_data()
    {
    }

    function config_settings(&$settings)
    {
        return $settings;
    }

    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return 'MIS Plugin';
    }


}
