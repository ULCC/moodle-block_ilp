<?php
/**
 *
 * a mis class to hold methods common to all the attendance plugins
 *
 *
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


//require the ilp_plugin.php class
require_once($CFG->dirroot . '/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');

//require the ilp_mis_connection.php file 
require_once($CFG->dirroot . '/blocks/ilp/db/ilp_mis_connection.php');


abstract class ilp_mis_attendance_plugin extends ilp_mis_plugin	{
    
	public function __construct($params = array())	{
        parent::__construct($params);
    }

}
