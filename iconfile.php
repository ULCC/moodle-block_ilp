<?php

/**
 * Returns the icon file to be used with a repo
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

$path_to_config = dirname($_SERVER['SCRIPT_FILENAME']).'/../../config.php';
while (($collapsed = preg_replace('|/[^/]+/\.\./|','/',$path_to_config,1)) !== $path_to_config) {
    $path_to_config = $collapsed;
}

require_once($path_to_config);

global $USER, $CFG, $SESSION, $PARSER;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//if set get the id of the report to be edited
$report_id	= $PARSER->required_param('report_id',PARAM_INT);	


// instantiate the db
$dbc = new ilp_db();

$report		=	$dbc->get_report_by_id($report_id);

if (!empty($report))	{	
			if (!empty($report->iconfile)) {
				header("Content-Type: image/jpeg");
				//header("Content-Length: ");
				header("Content-Disposition: attachment; filename=iconfile.jpeg");				
                // Print data
                echo $report->iconfile;
                exit;
	}
} 