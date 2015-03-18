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
require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

// instantiate the assmgr db
$dbc = new ilp_db();

$ilp_is_installed = in_array('block_ilp', $DB->get_tables());


require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');
//install new plugins
if ($ilp_is_installed) {
    ilp_element_plugin::install_new_plugins();
}


require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_template.class.php');
//install new templates
if ($ilp_is_installed) {
    ilp_dashboard_template::install_new_plugins();
}

require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_plugin.class.php');
//install new dashboard plugins
if ($ilp_is_installed) {
    ilp_dashboard_plugin::install_new_plugins();
}

require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');
//install new tabs
if ($ilp_is_installed) {
    ilp_dashboard_tab::install_new_plugins();
}

require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');
//install new mis plugins
if ($ilp_is_installed) {
    ilp_mis_plugin::install_new_plugins();
}

require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_graph_plugin.class.php');
//install new graphs
if ($ilp_is_installed) {
    ilp_graph_plugin::install_new_plugins();
}


$globalsettings 	= new admin_setting_heading('block_ilp/reportconfig', get_string('reports', 'block_ilp'), '');

$settings->add($globalsettings);

$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_report_configuration.php">'.get_string('reportconfigurationsection', 'block_ilp').'</a>';
$settings->add(new admin_setting_heading('block_ilp_report_configuration', '', $link));

$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_status_items.php">'.get_string('editstatusitems', 'block_ilp').'</a>';
$settings->add(new admin_setting_heading('block_ilp_statusitems', '', $link));

$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_secondstatus_items.php">' . get_string('editsecondstatusitems', 'block_ilp') . '</a>';
$settings->add(new admin_setting_heading('editsecondstatusitems', '', $link));

$globalsettings 	= new admin_setting_heading('block_ilp/userstatus', get_string('userstatus', 'block_ilp'), '');

$settings->add($globalsettings);

$options = array();
if ($ilp_is_installed) {
    $items				=	$dbc->get_status_items(ILP_DEFAULT_USERSTATUS_RECORD);
    if (!empty($items)) {
        foreach ($items as $i) {
            $options[$i->id]	=	$i->name;
        }
    }
}

//$pagelayout			=	new admin_setting_configtext('block_ilp/pagelayout',get_string('pagelayout','block_ilp'),get_string('pagelayoutconfig','block_ilp'),'ilp');
//$settings->add($pagelayout);

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
$plugins = $CFG->dirroot . '/blocks/ilp/plugins/mis';

$options = array();

$options[0]	=	get_string('noplugin','block_ilp');

foreach ($mis_plugins as $plugin_file) {

   if (file_exists($plugins . '/' . $plugin_file . ".php")) {

      require_once($plugins . '/' . $plugin_file . ".php");

      if ($plugin_file::plugin_type() == 'attendance' || $plugin_file::plugin_type() == 'overview'
          || $plugin_file::plugin_type() == 'learnerprofile') {
         // instantiate the object
         $class = basename($plugin_file, ".php");
         $pluginobj = new $class();
         //we only want to display plugins that are enabled (if they are enabled they should be configured)
         $pluginstatus	=	get_config('block_ilp',"{$plugin_file}_pluginstatus");
         if (!empty($pluginstatus)) {
            $mismisc = $dbc->get_mis_plugin_by_name($plugin_file);
            $options[$mismisc->name] = $pluginobj->tab_name();
         }
      }
   }
}

$attendplugin			= 	new admin_setting_configselect('block_ilp/attendplugin',get_string('attendaceplugin','block_ilp'),get_string('attendaceplugindesc','block_ilp'), '',$options);
$settings->add($attendplugin);

$options = array(
    5   => 5,
    10  => 10,
    20  => 20,
    50  => 50,
    100 => 100
);

$display			= 	new admin_setting_configselect('block_ilp/display',get_string('display','block_ilp'),get_string('displaydesc','block_ilp'), '',$options);
$settings->add($display);


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

$dashborddesc 	= new admin_setting_heading('block_ilp/dashborddesc', get_string('dashborddesc', 'block_ilp'), '');
$settings->add($dashborddesc);

$dashboardtext			= 	new admin_setting_confightmleditor('block_ilp/dashboardtext',get_string('dashboardtext','block_ilp'),get_string('dashboardtextconfig','block_ilp'),'',PARAM_RAW );
$settings->add($dashboardtext);

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

$allow_export = new admin_setting_configcheckbox('block_ilp/allow_export', get_string('settings_allow_export', 'block_ilp'), get_string('settings_allow_export_desc', 'block_ilp'), 1);

$settings->add($allow_export);

$allow_page_print = new admin_setting_configcheckbox('block_ilp/allow_page_print', get_string('settings_allow_page_print', 'block_ilp'), get_string('settings_allow_page_print_desc', 'block_ilp'), 1);

$settings->add($allow_page_print);

$allow_batch_print = new admin_setting_configcheckbox('block_ilp/allow_batch_print', get_string('settings_allow_batch_print', 'block_ilp'), get_string('settings_allow_batch_print_desc', 'block_ilp'), 1);

$settings->add($allow_batch_print);


//options for default tab in user's home
$tabs = $dbc->get_dashboard_tabs();
foreach ($tabs as $tab){

    $classname	=	$tab->name;

    //find out if the tab is enabled
    $status	= get_config('block_ilp',$classname.'_pluginstatus');
    if ($status	== ILP_ENABLED) {
         //include the dashboard_tab class file
        include_once("{$CFG->dirroot}/blocks/ilp/plugins/tabs/{$classname}.php");

        if(!class_exists($classname)) {
         print_error('pluginclassnotfound', 'block_ilp', '', $classname);
        }

        $dasttab	=	new $classname();
        $dash_tab_name = $dasttab->display_name();
        $taboptions[$tab->id] = $dash_tab_name;
    }
}
// if no tab is enabled, display the message
if (empty($taboptions)){
    $taboptions[0] = 'No tab is enabled';
}
$configure_home_tab_default = new admin_setting_configselect('block_ilp/hometabdefault',get_string('hometabdefault','block_ilp'),get_string('hometabdefaultconfig','block_ilp'),'',$taboptions);
$settings->add($configure_home_tab_default);

global $CFG;

$plugins = $CFG->dirroot.'/blocks/ilp/plugins/mis';

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

$plugins = $CFG->dirroot.'/blocks/ilp/plugins/tabs';

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



// -----------------------------------------------------------------------------
// Get graph plugin settings
// -----------------------------------------------------------------------------

$graphplugin_settings 	= new admin_setting_heading('block_ilp/graph_plugins', get_string('graph_pluginsettings', 'block_ilp'), '');

$settings->add($graphplugin_settings);
global $CFG;

$plugins = $CFG->dirroot.'/blocks/ilp/plugins/graph';

if ($dbc->get_graph_plugins() !== false) {

    $graph_plugins = ilp_records_to_menu($dbc->get_graph_plugins(), 'id', 'name');

    foreach ($graph_plugins as $plugin_file) {
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


$block_items_settings 	= new admin_setting_heading('block_ilp/block_items', get_string('tab_block_items', 'block_ilp'), '');
$settings->add($block_items_settings);

$link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_blockitem_config.php">' . get_string('tab_block_items_cfg', 'block_ilp') . '</a>';
$settings->add(new admin_setting_heading('block_ilp_block_items', '', $link));

/********************************
 * Misc config
 */

$globalsettings 	= new admin_setting_heading('block_ilp/miscconfig', get_string('miscconfig', 'block_ilp'), '');

$settings->add($globalsettings);

$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/upload_seal.php">'.get_string('config_uploadseal', 'block_ilp').'</a>';
$settings->add(new admin_setting_heading('block_ilp_upload_seal', '', $link));

$settings_add_predefined_link 	= new admin_setting_heading('block_ilp/add_predefined', get_string('settings_add_predefined_link', 'block_ilp'), get_string('settings_add_predefined_link_desc', 'block_ilp'));
$settings->add($settings_add_predefined_link);

?>
