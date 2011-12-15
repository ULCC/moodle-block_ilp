<?php
/**
 * This is the ilp version file used by moodle 2.0 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

global $CFG;

//require the ilp_settings class
require_once "$CFG->dirroot/blocks/ilp/classes/ilp_settings.class.php";

//instantiate the ilp settings class
$ilpsettings = new ilp_settings();

//get the current version number of the ilp
$plugin->version = $ilpsettings->version();


//get the time setting for the ilp cron
$plugin->cron = $ilpsettings->cron();