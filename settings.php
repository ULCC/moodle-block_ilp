<?php 

/**
 * Global config file for the ILP 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */



global $CFG;

// include the assmgr db
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

// instantiate the assmgr db
$dbc = new ilp_db();

$globalsettings 	= new admin_setting_heading('block_ilp/userstatus', get_string('userstatus', 'block_ilp'), '');

$settings->add($globalsettings);

$items				=	$dbc->get_status_items(ILP_DEFAULT_USERSTATUS_RECORD);

$options			=	array();
if (!empty($items)) {
	foreach ($items as $i) {
		$options[$i->id]	=	$i->name;
	}
}

$userstatus			= 	new admin_setting_configselect('block_ilp/defaultstatusitem',get_string('defaultstatusitem','block_ilp'),get_string('defaultstatusitemconfig','block_ilp'), 'simulationassignment',$options);

$settings->add($userstatus);


$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php">'.get_string('reportconfigurationsection', 'block_ilp').'</a>';
$settings->add(new admin_setting_heading('block_ilp_reportconfiguration', '', $link));






?>