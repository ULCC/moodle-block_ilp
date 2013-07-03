<?php

/**
 * Returns the icon file to be used with a report
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

//NOTE if icons are not being displayed a likely cause is output in one of the included files (within action_includes.php) check this as
//as well other factors

//require_once($path_to_config);

require_once('../../config.php');

global $USER, $CFG, $SESSION, $PARSER,$DB;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//if set get the id of the report to be edited
$report_id	= $PARSER->required_param('report_id',PARAM_INT);	


// instantiate the db
$dbc = new ilp_db();

$report		=	$dbc->get_report_by_id($report_id);



if (!empty($report))	{	
   if (!empty($report->binary_icon)) {
				
      header("Content-Type: image/jpeg");
      //header("Content-Length: 90000");
      header("Content-Disposition: attachment; filename=icon.jpeg");				
      // Print data
                
      //we have to use the raw moodle functions at this point and avoid the extra validation carried out by the sql classes 
      //as this breaks the export of reports
      $generic_report	=	$DB->get_record('block_ilp_report',array('id'=>$report_id));

      echo $generic_report->binary_icon;

	            exit;
	}
}
?>