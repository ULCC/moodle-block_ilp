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

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2015033100;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012120300;        // Requires this Moodle version

$plugin->component = 'block_ilp';      // Full name of the plugin (used for diagnostics)

$plugin->cron	= 86400; 	//run the cron at minimum once every 24 hours
