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


class add_prompt_mform extends ilp_moodleform {
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
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_prompt.php?report_id={$this->report_id}");
		}
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG;

        	$dbc = new ilp_db;

        	$mform =& $this->_form;
        	
        	
        	
        	//get all of the installed form element plugins
			$formelementplugins		=	$dbc->get_form_element_plugins();
			
			$frmplugins				=	array(''=>get_string('addpromptdots','block_ilp'));
			
			//if no elements installed pass an empty array
			if (empty($formelementplugins)) {
			    $formelementplugins = array();
			}
			
			//append _description to the name field so there description can be picked up from lang file
			foreach ($formelementplugins as $plg) {
			    $frmplugins[$plg->id] = get_string($plg->name.'_description','block_ilp');
			}
			
        	
        	$fieldsettitle = get_string('addfield', 'block_ilp');
        	
        	//create a new fieldset
        	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset"><div>');
            $mform->addElement('html', '<legend class="ftoggler">'.$fieldsettitle.'</legend>');
        	
        	$mform->addElement('hidden', 'report_id',$this->report_id);
        	$mform->setType('report_id', PARAM_INT);

        	$mform->addElement('select', 'plugin_id', get_string('addfield', 'block_ilp'), $frmplugins);
        	$mform->addRule('plugin_id', null, 'required', null, 'client');
	        $mform->setType('plugin_id', PARAM_INT);
	        
	        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('addfield','block_ilp'));
        		        
	        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        
	        //close the fieldset
	        $mform->addElement('html', '</div></fieldset>');
		}
		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			return $data->plugin_id;
		}
		
		/**
     	 * TODO comment this
     	 */
    	function definition_after_data() {
    		
    	}
	
}

	
?>