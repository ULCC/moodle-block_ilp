<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');
class ilp_element_plugin_course extends ilp_element_plugin_dd{

	public $tablename;
	public $data_entry_tablename;
	
    /**
     * Constructor
     */
    function __construct() {
    	
    	parent::__construct();
    	$this->tablename = "block_ilp_plu_crs";
    	$this->data_entry_tablename = "block_ilp_plu_crs_ent";
    	$this->selecttype = OPTIONSINGLE;
    }

    function language_strings(&$string) {
        $string['ilp_element_plugin_course'] 			= 'Select';
        $string['ilp_element_plugin_course_type'] 		= 'course select';
        $string['ilp_element_plugin_course_description'] 	= 'A course selector';
	$string[ 'ilp_element_plugin_course_optionlist' ] 	= 'Option List';
	$string[ 'ilp_element_plugin_course_single' ] 		= 'Single select';
	$string[ 'ilp_element_plugin_course_multi' ] 		= 'Multi select';
	$string[ 'ilp_element_plugin_course_typelabel' ] 	= 'Select type (single/multi)';
	$string[ 'ilp_element_plugin_course_noparticular' ] 	= 'no particular course';
        
        return $string;
    }
	  protected function get_option_list(){
		//retrieve list of courses from the db
		$noparticular = get_string( 'ilp_element_plugin_course_noparticular' , 'block_ilp' );
		$db = new ilp_db_functions();
		$outlist = array( 0 => $noparticular );
		foreach( $db->get_courses() as $course ){
			$outlist[ $course->id ] = $course->shortname;
		}
		return $outlist;
	  }
    /**
    * this function returns the mform elements taht will be added to a report form
	*
    public	function entry_form( &$mform ) {
    	//text field for element label
        $select = &$mform->addElement(
            'select',
            $this->reportfield_id,
            $this->label,
	    $this->get_option_list(),
            array('class' => 'form_input')
        );
        
        if (!empty($this->req)) $mform->addRule("$this->reportfield_id", null, 'required', null, 'client');
        $mform->setType('label', PARAM_RAW);
    	
        //return $mform;
    	
    	
    }
    */
}
