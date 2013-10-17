<?php
/**
 * This class provides a mform that previews the entry form 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */




class report_entry_preview_mform extends ilp_moodleform {

	
		public 		$course_id;
		public		$report_id;
		public		$dbc;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($report_id) {

			global $CFG;
			
			
			$this->report_id	=	$report_id;
			
			$this->dbc			=	new ilp_db();
			
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/report_entry_preview.php?report_id={$this->report_id}");
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
        	
			//get all of the fields in the current report, they will be returned in order as
			//no position has been specified
			$reportfields		=	$this->dbc->get_report_fields_by_position($this->report_id);

			$report				=	$this->dbc->get_report_by_id($this->report_id);
			
			//create a new fieldset
        	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
            $mform->addElement('html', '<legend class="ftoggler">'.$report->name.'</legend>');
            
            $desc	=	html_entity_decode($report->description, ENT_QUOTES, 'UTF-8');
			
			$mform->addElement('html', '<div class="descritivetext">'.$desc.'</div>');
                        
			foreach ($reportfields as $field) {
				
				//get the plugin record that for the plugin 
				$pluginrecord	=	$dbc->get_plugin_by_id($field->plugin_id);
				
				//take the name field from the plugin as it will be used to call the instantiate the plugin class
				$classname = $pluginrecord->name;
				
				// include the class for the plugin
				include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");
				
				if(!class_exists($classname)) {
				 	print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
				}
				
				//instantiate the plugin class
				$pluginclass	=	new $classname();
				
				$pluginclass->load($field->id);
				
				//call the plugins entry_form function which will add an instance of the plugin
				//to the form
				$pluginclass->entry_form($mform);
			}
        
	        //close the fieldset
	        $mform->addElement('html', '</fieldset>');
		}
	
		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			//no need to process data as this is just a preview of the final form
		}
		
		/**
     	 * TODO comment this
     	 */
    	function definition_after_data() {
    		
    	}
	
	
	
}