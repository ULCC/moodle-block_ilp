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
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_plugin.php');

abstract class ilp_mis_plugin extends ilp_plugin {
	
	public 		$templatefile;
	
	/*
	 * This var should hold the connection to the mis database
	 */
	public		$mis; 
	
	/**
     * Constructor
     */
    function __construct() {
    	global	$CFG;
    	
		//set the directory where plugin files of type ilp_dashboard_tab are stored  
    	$this->plugin_class_directory	=	$CFG->dirroot."/blocks/ilp/classes/dashboard/mis";
    	
    	//set the table that the details of these plugins are stored in
    	$this->plugintable	=	"block_ilp_mis_plugin";
    	
    	//call the parent constructor
    	parent::__construct();
    	
    	//set the name of the template file should be a html file with the same name as the class
    	$this->templatefile		=	$this->plugin_class_directory.'/'.$this->name.'.html';
    }
	
	public function display(){
    }
	
	
	
}
