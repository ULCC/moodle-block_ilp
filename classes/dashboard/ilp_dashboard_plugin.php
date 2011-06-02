<?php
/**
 * An abstract class that holds methods and attributes common to all element form plugin
 * classes.
 *
 * @abstract
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */
//abstract class assmgr_resource {
class ilp_element_plugin {

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
     * The plugins id
     *
     * @var int
     */
	var $plugin_id;
	
	var $xmldb_table;

    var $xmldb_field;

    var $xmldb_key;

    var $dbman;

    var $set_attributes;
    
    var $req;

	/*
	* local file for pre-populating particular types
	* filename is classname . '_pre_items.config'
	* eg ilp_element_plugin_category_pre_items.conf
	* in the local plugins directory
	*/
    public $local_config_file;	

    /**
     * Constructor
     */
    function __construct() {
        global $CFG,$DB;

        
        // include the assmgr db
        require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

        // instantiate the assmgr db
        $this->dbc = new ilp_db();
        
        $this->name = get_class($this);

        // include the xmldb classes
        require_once($CFG->libdir.'/ddllib.php');

        $this->dbman = $DB->get_manager();

        // if 2.0 classes are available then use them
        $this->xmldb_table = class_exists('xmldb_table') ? 'xmldb_table' : 'XMLDBTable';
        $this->xmldb_field = class_exists('xmldb_field') ? 'xmldb_field' : 'XMLDBField';
        $this->xmldb_key   = class_exists('xmldb_key')   ? 'xmldb_key'   : 'XMLDBKey';

	$local_config_filename = get_class( $this ) . '_pre_items.conf';
	$this->local_config_file = realpath( __DIR__ . '/plugins/' . $local_config_filename );
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
     * Delete the plugin
     */
    public final function delete($plugin_id) {
        return false;
    }

    /**
     * Installs any new plugins
     */
    public static function install_new_plugins() {
        global $CFG;

        // instantiate the assmgr db
        $dbc = new ilp_db();

        // get all the currently installed evidence resource types
        $plugins = ilp_records_to_menu($dbc->get_dashboard_plugins(), 'id', 'name');
        
        
        $plugins_directory = $CFG->dirroot.'/blocks/ilp/classes/dashboard/plugins';
        
        
        // get the folder contents of the resource plugin directory
        $files = scandir($plugins_directory);

        foreach($files as $file) {
            // look for plugins
            if(preg_match('/^([a-z_]+)\.php$/i', $file, $matches)) {

                if(!in_array($matches[1], $plugins) && substr($matches[1], -3)  != 'tab') {
                    // include the class

                	require_once($plugins_directory.'/'.$file);

                    // instantiate the object
                    $class = basename($file, ".php");
                    
                    $dashpluginobj = new $class();

                    // install the plugin
                    $dashpluginobj->install();

                    // update the resource_types table
                    $dbc->create_dashboard_plugin($dashpluginobj->get_name(),$dashpluginobj->get_directory(),$dashpluginobj->get_type());
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
		 
	 /**
     * Force extending class to implement a display function
     */
     abstract function display();
}
?>
