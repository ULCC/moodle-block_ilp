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
    	
    }

    function language_strings(&$string) {
        $string['ilp_element_plugin_course'] 			= 'Select';
        $string['ilp_element_plugin_course_type'] 		= 'select';
        $string['ilp_element_plugin_course_description'] 	= 'A course selector';
	$string[ 'ilp_element_plugin_course_optionlist' ] 	= 'Option List';
	$string[ 'ilp_element_plugin_course_single' ] 		= 'Single select';
	$string[ 'ilp_element_plugin_course_multi' ] 		= 'Multi select';
	$string[ 'ilp_element_plugin_course_typelabel' ] 	= 'Select type (single/multi)';
        
        return $string;
    }
}
