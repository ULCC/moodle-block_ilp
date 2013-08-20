<?php
/**
 * Carries out functions needed after installation
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


function xmldb_block_ilp_install() {

global $USER, $CFG, $SESSION, $PARSER;


		// include the ilp db
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');
		
		// instantiate the db
		$dbc = new ilp_db();
		
//install the various plugins and templates into the database
		
		require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');

		//install new plugins
		ilp_element_plugin::install_new_plugins();
		
		
		require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_template.class.php');
		//install new templates
		ilp_dashboard_template::install_new_plugins();
		
		require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_plugin.class.php');
		//install new dashboard plugins
		ilp_dashboard_plugin::install_new_plugins();
		
		require_once ($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');
		//install new tabs
		ilp_dashboard_tab::install_new_plugins();
		
//create relationships betweendashboard plugins and template regions
		
	//get the enabled template should be the default temmplate at this stage
	$enabled_template	=	$dbc->get_enabled_template();
		
		$regions			=	$dbc->get_template_regions($enabled_template->id);

		$region_plugins	= array();
		
	//create the association between the plugin and the first region
		$plugin				=	$dbc->get_dashboard_plugin_by_name('ilp_dashboard_student_info_plugin');
$rp					=	new	stdClass();
		$rp->plugin_id 		= $plugin->id;
		
		$region_plugins[]	=	$rp;
		
		$plugin				=	$dbc->get_dashboard_plugin_by_name('ilp_dashboard_main_plugin');
$rp					=	new	stdClass();
		$rp->plugin_id 		= $plugin->id;	
		
		$region_plugins[]	=	$rp;
		
	//loop through the regions and assign the region to a plugin 
		$i	=	0;
		foreach($regions as $r) {
			$region_plugins[$i]->region_id	=	$r->id;
			//create the record
			$dbc->create_region_plugin($region_plugins[$i]);
			$i++;
		} 
		
		//create default user status record and subsequent items
		$statusitem		=	new stdClass();
		$statusitem->selecttype	=	NULL;
				
		$id	=	$dbc->create_plugin_record('block_ilp_plu_sts',$statusitem);
		
		$statusitems	=	array('red'=>'1','orange'=>'0','green'=>'2');
				
		foreach($statusitems as $key => $passfail) {
			$si				=	new stdClass();
			$si->name		=	$key;
			$si->value		=	$key;
			$si->passfail	=	$passfail;
			$si->parent_id	=	$id;
            $si->icon       =   '';
            $si->display_option = 'text';
            $si->description    = '';
            $si->bg_colour      = '#ffffff';
			
			$dbc->create_plugin_record('block_ilp_plu_sts_items',$si);
		}

}



?>