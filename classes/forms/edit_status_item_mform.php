<?php 

/**
 * This class makes the form that is used to create reports 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


class edit_status_item_mform extends ilp_moodleform {

		public		$report_id;
		public		$dbc;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($report_id=null) {

			global $CFG;

			$this->report_id	=	$report_id;
			$this->dbc			=	new ilp_db();
			
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_status_items.php?report_id={$this->report_id}");
		}
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG;

        	$dbc = new ilp_db;

        	$mform =& $this->_form;
        	
        	$fieldsettitle = (!empty($this->report_id)) ? get_string('editreport', 'block_ilp') : get_string('createreport', 'block_ilp');
        	
        	//create a new fieldset
        	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
            	$mform->addElement('html', '<legend class="ftoggler">'.$fieldsettitle.'</legend>');

        	
        	$mform->addElement('hidden', 'id');
        	$mform->setType('id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'creator_id', $USER->id);
        	$mform->setType('creator_id', PARAM_INT);

	        
//instantiate status class
		require_once( "{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_status.php" );
		$status = new ilp_element_plugin_status();
//call the definition
		$status->config_specific_definition( $mform );
		
	        
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
			
			if (empty($data->id)) {
            	$data->id = $this->dbc->create_report($data);
            	
            	//setup report default permissions. They will match the permissions
            	//that the block has for each role
            	
            	$report_id	=	$data->id;
            	
            	//get all roles in moodle 
            	$roles		=	$this->dbc->get_roles();
            	
            	//get all capabilities for the ilp block
				$blockcapabilities	=	$this->dbc->get_block_capabilities();

				//loop through roles
            	foreach ($roles as $r) {
            		//secondary loop through capabilities
            		foreach($blockcapabilities as $cap) {
           			
            			//if the capability is not in the array
            			if (!in_array($cap->name,array('block/ilp:creeddelreport'))) {
            				
            				//initialise capable as an array
            				$capable	=	array();
            				//get all roles with the capability
            				$capabilityroles	=	get_roles_with_capability($cap->name,CAP_ALLOW);
							
            				//put the ids of roles with the current capability into the capable array
            				foreach($capabilityroles as $cr) {
								$capable[]	=	$cr->id;
							}
							
							//if the current role is one who has the capability
							if (in_array($r->id,$capable)) {
								
								//create a permission for the report with this role
								$permission					=	new stdClass();
								$permission->role_id		=	$r->id;
								$permission->capability_id	=	$cap->id;
								$permission->report_id		=	$report_id;
								$this->dbc->create_permisssion($permission);
							}
            			}	
            		}
            	}
        	} else {
            	$this->dbc->update_report($data);
        	}
	
    	    return $data->id;
		}
		
		/**
     	 * TODO comment this
     	 */
    	function definition_after_data() {
    		
    	}
	
}

	
?>
