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


class js_loader_mform extends ilp_moodleform {

		/**
     	 * TODO comment this
     	 */
		function __construct() {
            global $CFG;
            parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_reportentry.php");
		}
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG, $DB;

        	$mform =& $this->_form;
	        
	        // DESCRIPTION element
	        $mform->addElement(
	            'editor',
	            'value',
	            get_string('comment', 'block_ilp'),
	            array('class' => 'form_input', 'rows'=> '10', 'cols'=>'65')
	        );

            $mform->addElement('filemanager', 'loader_file_filemanager', 'loader_filemanager', null,
                array());

		}
	
}

	
?>