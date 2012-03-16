<?php 
/**
 * This class acts a central place to hold the settings used by 
 * the ilp in both moodle 1.9 and 2.0 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

class ilp_settings {
	
/*
 * Majority of the class vars will be set to private as 
 * their values should not be changed during block execution 
 */
	
	private 		$version;
	private 		$cron;
	
	
   /*
    * constructor class sets the values of all settings used
    */
   function __construct() {
       $this->version = "2012030104";
       $this->cron 		= 86400; 	//run the cron at minimum once every 24 hours
   }
	
	
	
	 /**
     * Returns the current version number of the block
     *
     * @return the current version number of the ilp
     */
	function version() {
		return $this->version; 
	}
	
	 /**
     * Returns the current version number of the block
     *
     * @return the current version number of the ilp
     */
	function cron() {
		return $this->cron; 
	}
	
	
}

?>