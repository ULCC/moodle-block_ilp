<?php
/**
 * This class provides a mform that allows a plugins config to be saved
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */




class edit_mis_plugin_config_mform extends ilp_moodleform {
		
		public		$pluginname;
		public 		$plugintable;
		public 		$plugindirectory;
		public		$dbc;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($pluginname,$type) {

			global $CFG,$PARSER;
			
			
			$this->pluginname	=	$pluginname;
			$this->dbc			=	new ilp_db();
			
			
			//choose the plugins class directory based on the table 
			switch ($type) {
				
				case 'mis':
				$this->plugintable	=	'block_ilp_mis_plugin';
				$this->plugindirectory	=	'mis/';
				break;
				
				case 'formelement':
				$this->plugintable	=	'block_ilp_mis_plugin';
				$this->plugindirectory	=	'form_elements/';
				break;
				
				case 'templateplugin':
				$this->plugintable	=	'block_ilp_dash_plugin';
				$this->plugindirectory	=	'dashboard/';
				break;

				case 'tab':
				$this->plugintable	=	'block_ilp_dash_tab';					
				$this->plugindirectory	=	'tabs/';
				break;
				
				case 'template':
				$this->plugintable	=	'block_ilp_dash_temp';
				$this->plugindirectory	=	'dash_templates/';
				break;				
				
				default:
				$this->plugintable	=	'';
				$this->plugindirectory	=	'';
				break;
			}
			
			$params		=	$PARSER->get_params();
			$urlparams	=	"";
			
			foreach ($params as $k => $v) {
				$urlparams	.= "{$k}={$v}&";
			}	
			
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_plugin_config.php?$urlparams");
		}
	
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG;

	         // include the assmgr db
        	require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');
			 
        	$dbc = new ilp_db;

        	$mform =& $this->_form;
        	
        	$pluginrecord		=	$this->dbc->get_plugin_record_by_classname($this->plugintable,$this->pluginname);
			
			//create a new fieldset
        	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');

            
        	//take the name field from the plugin as it will be used to call the instantiate the plugin class
			$classname = $pluginrecord->name;
				
			// include the class for the plugin
			include_once("{$CFG->dirroot}/blocks/ilp/plugins/{$this->plugindirectory}{$classname}.php");
				
			if(!class_exists($classname)) {
			 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
			}
				
			//instantiate the plugin class
			$pluginclass	=	new $classname();

			//call the plugins config_form function which will add the plugins config top the form
			$pluginclass->config_form($mform);
			
			$buttonarray[] = $mform->createElement('submit', 'saveanddisplaybutton', get_string('submit'));
	        $buttonarray[] = &$mform->createElement('cancel');
	        
	        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
			
	        //close the fieldset
	        $mform->addElement('html', '</fieldset>');
		}
	
		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			global $CFG;
			
			$pluginrecord		=	$this->dbc->get_plugin_record_by_classname($this->plugintable,$this->pluginname);
			
			//take the name field from the plugin as it will be used to call the instantiate the plugin class
			$classname = $pluginrecord->name;
				
			// include the class for the plugin
			include_once("{$CFG->dirroot}/blocks/ilp/plugins/{$this->plugindirectory}{$classname}.php");
				
			if(!class_exists($classname)) {
			 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
			}
				
			//instantiate the plugin class
			$pluginclass	=	new $classname();

			//call the plugins config_form function which will add the plugins config top the form
			return $pluginclass->config_save($data);
		}
		
		/**
     	 * TODO comment this
     	 */
    	function definition_after_data() {
    		
    	}
	
	
	
}