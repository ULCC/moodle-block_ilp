<?php
/**
 * An abstract class that holds methods and attributes common to all element dashboard plugin
 * classes.
 *
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_plugin.class.php');


abstract class ilp_dashboard_plugin extends ilp_plugin {

	
    /**
     * Constructor
     */
    function __construct() {
    	global	$CFG;
    	
		//set the directory where plugin files of type ilp_dashboard_tab are stored  
    	$this->plugin_class_directory	=	$CFG->dirroot."/blocks/ilp/plugins/dashboard";
    	
    	//set the table that the details of these plugins are stored in
    	$this->plugintable	=	"block_ilp_dash_plugin";
    	
    	//call the parent constructor
    	parent::__construct();
    }

    /**
     * Installs any new plugins
     */
    public static function install_new_plugins($dbplugins=array(),$plugin_class_directory="") {
    	global $CFG;
    	
        // include the ilp db
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the ilp db class needed as this function will be called 
        //when not in object context
        $dbc = new ilp_db();
    	
    	//call the install new plugins function from the parent class
    	//pass the list of plugins currently installed to it
        parent::install_new_plugins($dbc->get_dashboard_plugins(),$CFG->dirroot."/blocks/ilp/plugins/dashboard");

    }

    
    
   	 /**
     * Force extending class to implement a display function
     */
     abstract function display();

}
?>
