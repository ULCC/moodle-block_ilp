<?php
/**
 * An abstract class that holds methods and attributes common to all element form plugin
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
class ilp_plugin {

	/**
	* table to store the properties of the element
	*/
	public $tablename;

	 /**
     * The name of the plugin
     *
     * @var string
     */
    var $name;

    /**
     * The moodle form for editing the plugin data
     *
     * @var moodleform
     */
    var $mform;

    
    /**
     * The  directory in which the plugin classes reside
     *
     * @var string
     */
     var	$plugin_class_directory;
     
     /**
     * The name of the table that the plugins details will be saved to
     *
     * @var string
     */
     var	$plugintable;
     
    
    /**
     * The plugins id
     *
     * @var int
     */
	var $plugin_id;
	
	var	$dbc;
	
	var $xmldb_table;

    var $xmldb_field;

    var $xmldb_key;

    var $dbman;

    var $set_attributes;

    /**
     * Constructor
     */
    function __construct() {
        global $CFG,$DB;

        
        // include the ilp db
        require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

        // instantiate the ilp db
        $this->dbc = new ilp_db();
        
        $this->name = get_class($this);

        // include the xmldb classes
        require_once($CFG->libdir.'/ddllib.php');

        $this->dbman = $DB->get_manager();

        // if 2.0 classes are available then use them
        $this->xmldb_table = class_exists('xmldb_table') ? 'xmldb_table' : 'XMLDBTable';
        $this->xmldb_field = class_exists('xmldb_field') ? 'xmldb_field' : 'XMLDBField';
        $this->xmldb_key   = class_exists('xmldb_key')   ? 'xmldb_key'   : 'XMLDBKey';

    }

	/**
     * Returns the name of the plugin
     */
    public function get_name() {
        return $this->name;
    }

	/**
     * Returns the directory in which the files for this plugin reside
     */
    public function get_directory() {
        return $this->directory;
    }
    
	/**
     * Returns the name of the plugin
     */
    public function get_plugin_class_directory() {
        return $this->plugin_class_directory;
    }
    
	/**
     * Returns the name of the plugin
     */
    public function get_plugin_table() {
        return $this->plugintable;
    }
    
	/**
     * Delete the plugin
     */
    public final function delete($plugin_id) {
        return false; 
    }
    
    
    /**
     * Install function can be used to install any additional tables or files, records etc
     */
    public function install($id)	{
    	return false;
    }

    /**
     * Installs any new plugins
     */
    public function install_new_plugins($dbplugins,$plugin_class_directory) {
        global $CFG;

        // instantiate the assmgr db
        $dbc = new ilp_db();

        // get all the currently installed evidence resource types
        $plugins = ilp_records_to_menu($dbplugins, 'id', 'name');
        
        // get the folder contents of the resource plugin directory
        $files = scandir($plugin_class_directory);

        foreach($files as $file) {
            // look for plugins
            if(preg_match('/^([a-z_]+)\.php$/i', $file, $matches)) {

                if(!in_array($matches[1], $plugins)) {
                    // include the class

                	require_once($plugin_class_directory.'/'.$file);

                    // instantiate the object
                    $class = basename($file, ".php");
                    
                    $dashpluginobj = new $class();

                    // update the resource_types table
                    $id	=	$dbc->create_plugin($dashpluginobj->get_plugin_table(),$dashpluginobj->get_name());
                    
                    // any additional functions that must be carried that are specific to a child class can be carried out in the install function
                    $dashpluginobj->install($id);
                }
            }
        }

    }


     /**
     * function used to return configuration settings for a plugin
     */
    function config_settings(&$settings) {
        return $settings;
    }

    /**
     * function used to return the language strings for the plugin
     */
    function language_strings(&$string) {
        return $string;
    }

}
?>
