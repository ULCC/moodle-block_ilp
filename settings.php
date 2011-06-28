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


//fail colour
$failcolour			=	new admin_setting_configtext('block_ilp/failcolour',get_string('failcsscolour','block_ilp'),get_string('failcsscolourconfig','block_ilp'),ILP_CSSCOLOUR_FAIL,PARAM_RAW);
$settings->add($failcolour);
//pass colour
$passcolour			=	new admin_setting_configtext('block_ilp/passcolour',get_string('passcsscolour','block_ilp'),get_string('passcsscolourconfig','block_ilp'),ILP_CSSCOLOUR_PASS,PARAM_RAW);
$settings->add($passcolour);

//mid colour
$midcolour			=	new admin_setting_configtext('block_ilp/midcolour',get_string('midcsscolour','block_ilp'),get_string('midcsscolourconfig','block_ilp'),ILP_CSSCOLOUR_MID,PARAM_RAW);
$settings->add($midcolour);

//the fail percentage
$failpercentage			=	new admin_setting_configtext('block_ilp/failpercent',get_string('failpercent','block_ilp'),get_string('failpercentconfig','block_ilp'),ILP_DEFAULT_FAIL_PERCENTAGE,PARAM_INT);
$settings->add($failpercentage);

//the fail percentage
$passpercentage			=	new admin_setting_configtext('block_ilp/passpercent',get_string('passpercent','block_ilp'),get_string('passpercentconfig','block_ilp'),ILP_DEFAULT_PASS_PERCENTAGE,PARAM_INT);
$settings->add($passpercentage);


$mis_settings 	= new admin_setting_heading('block_ilp/mis_connection', get_string('mis_connection', 'block_ilp'), '');
$settings->add($mis_settings);
$options = array(
    'msql',
    'mysql',
    'oracle',
    'sqlserver'
);
$mis_connection			= 	new admin_setting_configselect('block_ilp/dbconnectiontype',get_string('db_connection','block_ilp'),get_string('reportconfigurationsection','block_ilp'), 'mysql', $options);
$settings->add( $mis_connection );
/*
*/

$dbname			=	new admin_setting_configtext('block_ilp/dbname',get_string( 'db_name', 'block_ilp' ),get_string( 'set_db_name', 'block_ilp' ),'moodle',PARAM_RAW);
$settings->add($dbname);

$dbprefix			=	new admin_setting_configtext('block_ilp/dbprefix',get_string( 'db_prefix', 'block_ilp' ),get_string( 'prefix_for_tablenames', 'block_ilp' ),'mdl_',PARAM_RAW);
$settings->add($dbprefix);

$dbhost			=	new admin_setting_configtext('block_ilp/dbhost',get_string( 'db_host', 'block_ilp' ), get_string( 'host_name_or_ip', 'block_ilp' ),'mdl_',PARAM_RAW);
$settings->add($dbhost);

$dbpass			=	new admin_setting_configtext('block_ilp/dbpass',get_string( 'db_pass', 'block_ilp' ), get_string( 'db_pass', 'block_ilp' ),'',PARAM_RAW);
$settings->add($dbpass);
/*
*/
$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_status_items.php">'.get_string('editstatusitems', 'block_ilp').'</a>';
$settings->add(new admin_setting_heading('block_ilp_statusitems', '', $link));



?>
