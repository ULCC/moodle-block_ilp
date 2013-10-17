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


class edit_entrycomment_mform extends ilp_moodleform {
	
		public 		$course_id;
		public		$report_id;
		public		$entry_id;
		public		$user_id;
		public		$comment_id;
		public		$selectedtab;
		public		$tabitem;
		public		$dbc;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($report_id,$entry_id,$user_id,$course_id=NULL,$comment_id=NULL,$selectedtab=NULL,$tabitem=NULL) {

			global $CFG;
			
			$this->course_id	=	$course_id;
			$this->report_id	=	$report_id;
			$this->entry_id		=	$entry_id;
			$this->user_id		=	$user_id;
			
			$this->comment_id	=	$comment_id;
			$this->selectedtab	=	$selectedtab;
			$this->tabitem		=	$tabitem;
			$this->dbc			=	new ilp_db();
			
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_entrycomment.php?course_id={$this->course_id}&report_id={$this->report_id}&entry_id={$this->entry_id}&user_id={$this->user_id}&comment_id={$this->comment_id}&selectedtab={$this->selectedtab}&tabitem={$this->tabitem}");
		}
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG, $DB;

        	$dbc = new ilp_db;

        	$mform =& $this->_form;
        	
        	$fieldsettitle = (!empty($this->comment_id)) ? get_string('editcomment', 'block_ilp') : get_string('addcomment', 'block_ilp');
        	
        	//create a new fieldset
        	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
            $mform->addElement('html', '<legend class="ftoggler">'.$fieldsettitle.'</legend>');
        	
       	 	$mform->addElement('hidden', 'course_id', $this->course_id);
        	$mform->setType('course_id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'id', $this->comment_id);
        	$mform->setType('id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'creator_id', $USER->id);
        	$mform->setType('creator_id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'entry_id', $this->entry_id);
        	$mform->setType('entry_id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'user_id', $this->user_id);
        	$mform->setType('user_id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'tabitem', $this->tabitem);
        	$mform->setType('tabitem', PARAM_RAW);
        	
        	$mform->addElement('hidden', 'selectedtab', $this->selectedtab);
        	$mform->setType('selectedtab', PARAM_RAW);

	        
	        // DESCRIPTION element
	        $mform->addElement(
	            'editor',
	            'value',
	            get_string('comment', 'block_ilp'),
	            array('class' => 'form_input', 'rows'=> '10', 'cols'=>'65')
	        );
            if($this->comment_id){
                $my_data = $DB->get_record('block_ilp_entry_comment', array('id'=>$this->comment_id));
                if($my_data){
                    $mform->setDefault('value', array('text'=>html_entity_decode($my_data->value,
                                                                                 ENT_QUOTES,
                                                                                 'UTF-8'), 'format'=>FORMAT_HTML));
                }
            }


	        
	        //$mform->addRule('value', null, 'maxlength', 65535, 'client');
	        $mform->addRule('value', null, 'required', null, 'client');
	        $mform->setType('value', PARAM_RAW);
        	
	        //TODO add the elements to implement the frequency functionlaity
	        
	        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
        	$buttonarray[] = &$mform->createElement('cancel');
	        
	        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
	        
	        //close the fieldset
	        $mform->addElement('html', '</fieldset>');
		}
		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			
			if($data->id > 0){
                $data->value = $data->value['text'];
                $this->dbc->update_entry_comment($data);
            }else {
                $data->value = $data->value['text'];
            	$data->id = $this->dbc->create_entry_comment($data);
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