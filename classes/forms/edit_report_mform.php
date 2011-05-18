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
	
		public 		$course_id;
		public		$report_id;
		public		$dbc;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($course_id,$report_id=null) {

			global $CFG;
			
			$this->course_id	=	$course_id;
			$this->report_id	=	$report_id;
			$this->dbc			=	new ilp_db();
			
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_report.php?course_id={$this->course_id}&report_id={$this->report_id}");
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
        	
       	 	$mform->addElement('hidden', 'course_id', $this->course_id);
        	$mform->setType('course_id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'id');
        	$mform->setType('id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'creator_id', $USER->id);
        	$mform->setType('creator_id', PARAM_INT);

        	// NAME element
	        $mform->addElement(
	            'text',
	            'name',
	            get_string('name', 'block_ilp'),
	            array('class' => 'form_input')
	        );
	        $mform->addRule('name', null, 'maxlength', 255, 'client');
	        $mform->addRule('name', null, 'required', null, 'client');
	        $mform->setType('name', PARAM_ALPHA);
	        
	        // DESCRIPTION element
	        $mform->addElement(
	            'htmleditor',
	            'description',
	            get_string('description', 'block_ilp'),
	            array('class' => 'form_input', 'rows'=> '10', 'cols'=>'65')
	        );
	        
	        $mform->addRule('description', null, 'maxlength', 65535, 'client');
	        $mform->addRule('description', null, 'required', null, 'client');
	        $mform->setType('description', PARAM_RAW);
        	
	        //TODO add the elements to implement the frequency functionlaity
	        
	        
	        $buttonarray[] = $mform->createElement('submit', 'saveanddisplaybutton', get_string('submitanddisplay','block_ilp'));
	        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
        	$buttonarray[] = &$mform->createElement('cancel');
	        
	        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
	        
	        //$this->add_action_buttons(true, get_string('submit'));
	        
	        //close the fieldset
	        $mform->addElement('html', '</fieldset>');
		}
		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			
			if (empty($data->id)) {
            	$data->id = $this->dbc->create_report($data);
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