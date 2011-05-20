<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');

class ilp_element_plugin_status extends ilp_element_plugin_dd{

	public $tablename;
	public $data_entry_tablename;
	
    /**
     * Constructor
     */
    function __construct() {
    	
    	parent::__construct();
    	$this->tablename = "block_ilp_plu_sts";
    	$this->data_entry_tablename = "block_ilp_plu_sts_ent";
    	
    }

    function language_strings(&$string) {
        $string['ilp_element_plugin_status'] 			= 'Select';
        $string['ilp_element_plugin_status_type'] 		= 'select';
        $string['ilp_element_plugin_status_description'] 	= 'A status selector';
	$string[ 'ilp_element_plugin_status_optionlist' ] 	= 'Option List';
	$string[ 'ilp_element_plugin_status_single' ] 		= 'Single select';
	$string[ 'ilp_element_plugin_status_multi' ] 		= 'Multi select';
	$string[ 'ilp_element_plugin_status_typelabel' ] 	= 'Select type (single/multi)';
        
        return $string;
    }
}
