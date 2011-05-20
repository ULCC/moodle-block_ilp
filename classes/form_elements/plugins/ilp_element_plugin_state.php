<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');

class ilp_element_plugin_state extends ilp_element_plugin_dd{

	public $tablename;
	public $data_entry_tablename;
	
    /**
     * Constructor
     */
    function __construct() {
    	
    	parent::__construct();
    	$this->tablename = "block_ilp_plu_ste";
    	$this->data_entry_tablename = "block_ilp_plu_ste_ent";
    	
    }

    function language_strings(&$string) {
        $string['ilp_element_plugin_state'] 			= 'Select';
        $string['ilp_element_plugin_state_type'] 		= 'select';
        $string['ilp_element_plugin_state_description'] 	= 'A state selector';
	$string[ 'ilp_element_plugin_state_optionlist' ] 	= 'Option List';
	$string[ 'ilp_element_plugin_state_single' ] 		= 'Single select';
	$string[ 'ilp_element_plugin_state_multi' ] 		= 'Multi select';
	$string[ 'ilp_element_plugin_state_typelabel' ] 	= 'Select type (single/multi)';
        
        return $string;
    }
}
