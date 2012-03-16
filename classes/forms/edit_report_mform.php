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


class edit_report_mform extends ilp_moodleform {

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
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_report.php?report_id={$this->report_id}");
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
           $mform->addElement('html', '<legend >'.$fieldsettitle.'</legend>');
        	
        	$mform->addElement('hidden', 'id');
        	$mform->setType('id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'creator_id', $USER->id);
        	$mform->setType('creator_id', PARAM_INT);

            //the id of the form element creator
            $mform->addElement('hidden', 'position');
            $mform->setType('position', PARAM_INT);
            //set the field position of the field
            $mform->setDefault('position', $this->dbc->get_new_report_position());

        	// NAME element
	        $mform->addElement(
	            'text',
	            'name',
	            get_string('name', 'block_ilp'),
	            array('class' => 'form_input')
	        );
	        $mform->addRule('name', null, 'maxlength', 255, 'client');
	        $mform->addRule('name', null, 'required', null, 'client');
	        $mform->setType('name', PARAM_RAW);
	        
	        $mform->addElement('checkbox', 'maxedit',get_String('maxedit','block_ilp'),null);
	        
	        $mform->addElement('checkbox', 'comments',get_String('allowcomments','block_ilp'),null);
	        
	       	$mform->addElement('checkbox', 'frequency', get_String('multipleentries','block_ilp'),null);
	        
	        // DESCRIPTION element
	        $mform->addElement(
	            'htmleditor',
	            'description',
	            get_string('description', 'block_ilp'),
	            array('class' => 'form_input', 'rows'=> '10', 'cols'=>'65')
	        );
	        
	        $mform->addRule('description', null, 'maxlength', 65535, 'client');

	        // commented out as causing problems with double submitting 
	        // $mform->addRule('description', null, 'required', null, 'client');

	        $mform->setType('description', PARAM_RAW);
        	
	        //TODO add the elements to implement the frequency functionlaity
			if (stripos($CFG->release,"2.") !== false) {
				$mform->addElement('filepicker', 'binary_icon',get_string('binary_icon', 'block_ilp'), null, array('maxbytes' => ILP_MAXFILE_SIZE, 'accepted_types' => ILP_ICON_TYPES));
			} else {
				$this->set_upload_manager(new upload_manager('binary_icon', false, false, 0, false, ILP_MAXFILE_SIZE, true, true, false));
        		$mform->addElement('file', 'binary_icon', get_string('binary_icon', 'block_ilp'));				
			}
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
			
			if (empty($data->id)) {
				
				if (!empty($data->binary_icon)) {
					//moodle 1.9 doesnt add slashes so we need to do this
					if (stripos($CFG->release,"2.") === false) {
						$data->binary_icon = addslashes($data->binary_icon);
					}
				}
				
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
			
				//check to stop report icons from being overwritten
				//if the binary_icon param is empty unset it that will stop 
				//any data that is currently present from being overwritten
				if (empty($data->binary_icon)) unset($data->binary_icon); 

				if (!empty($data->binary_icon)) {
					//moodle 1.9 doesnt add slashes so we need to do this
					if (stripos($CFG->release,"2.") === false) {
						$data->binary_icon = addslashes($data->binary_icon);
					}
				}
				
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
