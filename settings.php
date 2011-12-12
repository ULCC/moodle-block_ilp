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

require_once ($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin.php');

//install new plugins
ilp_element_plugin::install_new_plugins();


require_once ($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_template.php');
//install new templates
ilp_dashboard_template::install_new_plugins();

require_once ($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_plugin.php');
//install new dashboard plugins
ilp_dashboard_plugin::install_new_plugins();

require_once ($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_tab.php');
//install new tabs
ilp_dashboard_tab::install_new_plugins();

require_once ($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');
//install new tabs
ilp_mis_plugin::install_new_plugins();


$globalsettings 	= new admin_setting_heading('block_ilp/reportconfig', get_string('reports', 'block_ilp'), '');

$settings->add($globalsettings);

$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php">'.get_string('reportconfigurationsection', 'block_ilp').'</a>';
$settings->add(new admin_setting_heading('block_ilp_report_configuration', '', $link));

$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_status_items.php">'.get_string('editstatusitems', 'block_ilp').'</a>';
$settings->add(new admin_setting_heading('block_ilp_statusitems', '', $link));

$globalsettings 	= new admin_setting_heading('block_ilp/userstatus', get_string('userstatus', 'block_ilp'), '');

$settings->add($globalsettings);

$items				=	$dbc->get_status_items(ILP_DEFAULT_USERSTATUS_RECORD);

$options			=	array();
if (!empty($items)) {
	foreach ($items as $i) {
		$options[$i->id]	=	$i->name;
	}
}



$userstatus			= 	new admin_setting_configselect('block_ilp/defaultstatusitem',get_string('defaultstatusitem','block_ilp'),get_string('defaultstatusitemconfig','block_ilp'), '',$options);
$settings->add($userstatus);

$progressbarcolour			=	new admin_setting_configtext('block_ilp/progressbarcolour',get_string('progressbarcolour','block_ilp'),get_string('progressbarcolour','block_ilp'), '#999',PARAM_RAW);
$settings->add($progressbarcolour);


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

//get all mis_plugins
$mis_plugins = ilp_records_to_menu($dbc->get_mis_plugins(), 'id', 'name');
$plugins = $CFG->dirroot . '/blocks/ilp/classes/dashboard/mis';

$options = array();

$options[0]	=	get_string('noplugin','block_ilp');

foreach ($mis_plugins as $plugin_file) {

	 if (file_exists($plugins . '/' . $plugin_file . ".php")) {
	
      	require_once($plugins . '/' . $plugin_file . ".php");
        // instantiate the object
        $class = basename($plugin_file, ".php");
        $pluginobj = new $class();
        $method = array($pluginobj, 'plugin_type');

        //check whether the config_settings method has been defined
         if (is_callable($method, true)) {
         	if ($pluginobj->plugin_type() == 'attendance' || $pluginobj->plugin_type() == 'overview') {
         		
         		//we only want to display plugins that are enabled (if they are enabled they should be configured)
         		$pluginstatus	=	get_config('block_ilp',"{$plugin_file}_pluginstatus");
         		if (!empty($pluginstatus)) {
               		$mismisc = $dbc->get_mis_plugin_by_name($plugin_file);
               		$options[$mismisc->name] = $pluginobj->tab_name();
         		}
            }
         }
	 } else	{
	 	
	 }
}

$attendplugin			= 	new admin_setting_configselect('block_ilp/attendplugin',get_string('attendaceplugin','block_ilp'),get_string('attendaceplugindesc','block_ilp'), '',$options);
$settings->add($attendplugin);


$mis_settings 	= new admin_setting_heading('block_ilp/mis_connection', get_string('mis_connection', 'block_ilp'), '');
$settings->add($mis_settings);
$options = array(
    ' '     => get_string('noconnection','block_ilp'),
    'mssql' => 'Mssql',
    'mysql' => 'Mysql',
    'odbc' => 'Odbc',
    'oci8' => 'Oracle',
    'postgres' => 'Postgres',
    'sybase' => 'Sybase'
);
$mis_connection			= 	new admin_setting_configselect('block_ilp/dbconnectiontype',get_string('db_connection','block_ilp'),get_string('reportconfigurationsection','block_ilp'), '', $options);
$settings->add( $mis_connection );
/*
*/

$dbname			=	new admin_setting_configtext('block_ilp/dbname',get_string( 'db_name', 'block_ilp' ),get_string( 'set_db_name', 'block_ilp' ),'',PARAM_RAW);
$settings->add($dbname);

$dbprefix			=	new admin_setting_configtext('block_ilp/dbprefix',get_string( 'db_prefix', 'block_ilp' ),get_string( 'prefix_for_tablenames', 'block_ilp' ),'',PARAM_RAW);
$settings->add($dbprefix);

$dbhost			=	new admin_setting_configtext('block_ilp/dbhost',get_string( 'db_host', 'block_ilp' ), get_string( 'host_name_or_ip', 'block_ilp' ),'',PARAM_RAW);
$settings->add($dbhost);

$dbuser			=	new admin_setting_configtext('block_ilp/dbuser',get_string( 'db_user', 'block_ilp' ), get_string( 'db_user', 'block_ilp' ),'',PARAM_RAW);
$settings->add( $dbuser );

$dbpass			=	new admin_setting_configtext('block_ilp/dbpass',get_string( 'db_pass', 'block_ilp' ), get_string( 'db_pass', 'block_ilp' ),'',PARAM_RAW);
$settings->add($dbpass);

$miscsettings 	= new admin_setting_heading('block_ilp/miscoptions', get_string('miscoptions', 'block_ilp'), '');

$settings->add($miscsettings);

$maxreports			=	new admin_setting_configtext('block_ilp/maxreports',get_string('maxreports','block_ilp'),get_string('maxreportsconfig','block_ilp'),ILP_DEFAULT_LIST_REPORTS,PARAM_INT);
$settings->add($maxreports);


	
	$options	=	array();
	
	for($i = 0;$i < 11 ;$i++)	{
		$options[$i]	=	($i == 0) ? get_string('none','block_ilp') : " {$i} days";
	} 		

	$deadlinenotification			= 	new admin_setting_configselect('block_ilp/deadlinenotification',get_string('deadlinenotification','block_ilp'),get_string('deadlinenotificationconfig','block_ilp'), 7,$options);
	$settings->add($deadlinenotification);


$misplugin_settings 	= new admin_setting_heading('block_ilp/mis_plugins', get_string('mis_pluginsettings', 'block_ilp'), '');
// -----------------------------------------------------------------------------
// Get MIS plugin settings
// -----------------------------------------------------------------------------

$settings->add($misplugin_settings);
global $CFG;

$plugins = $CFG->dirroot.'/blocks/ilp/classes/dashboard/mis';

if ($dbc->get_mis_plugins() !== false) {
	
	
	$mis_plugins = ilp_records_to_menu($dbc->get_mis_plugins(), 'id', 'name');
	
	foreach ($mis_plugins as $plugin_file) {
		if (file_exists($plugins.'/'.$plugin_file.".php")) {
		    require_once($plugins.'/'.$plugin_file.".php");
		    
		    // instantiate the object
		    $class = basename($plugin_file, ".php");
		    $pluginobj = new $class();
		    $method = array($pluginobj, 'config_settings');
			
		    //check whether the config_settings method has been defined
	
		    if (is_callable($method,true)) {
		        $pluginobj->config_settings($settings);
		        
		    }
		}
	}
}

$tabplugin_settings 	= new admin_setting_heading('block_ilp/tab_plugins', get_string('tab_pluginsettings', 'block_ilp'), '');

// -----------------------------------------------------------------------------
// Get Dashboard Tab plugin settings
// -----------------------------------------------------------------------------

$settings->add($tabplugin_settings);
global $CFG;

$plugins = $CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs';

if ($dbc->get_tab_plugins() !== false) {
	
	
	$tab_plugins = ilp_records_to_menu($dbc->get_tab_plugins(), 'id', 'name');
	
	foreach ($tab_plugins as $plugin_file) {
		if (file_exists($plugins.'/'.$plugin_file.".php")) {
		    require_once($plugins.'/'.$plugin_file.".php");
		    
		    // instantiate the object
		    $class = basename($plugin_file, ".php");
		    $pluginobj = new $class();
		    $method = array($pluginobj, 'config_settings');
			
		    //check whether the config_settings method has been defined
	
		    if (is_callable($method,true)) {
		        $pluginobj->config_settings($settings);
		    }
		}
	}
}

/********************************
 * Misc config
 */

$globalsettings 	= new admin_setting_heading('block_ilp/miscconfig', get_string('miscconfig', 'block_ilp'), '');

$settings->add($globalsettings);


?>
