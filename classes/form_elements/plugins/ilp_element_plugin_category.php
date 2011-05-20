<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');
class ilp_element_plugin_category extends ilp_element_plugin_dd{

	public $tablename;
	public $data_entry_tablename;
	
    /**
     * Constructor
     */
    function __construct() {
    	
    	parent::__construct();
    	$this->tablename = "block_ilp_plu_cat";
    	$this->data_entry_tablename = "block_ilp_plu_cat_ent";
    	
    }

    function language_strings(&$string) {
        $string['ilp_element_plugin_dd'] 			= 'Select';
        $string['ilp_element_plugin_category_type'] 		= 'select';
        $string['ilp_element_plugin_category_description'] 	= 'A category selector';
	$string[ 'ilp_element_plugin_category_optionlist' ] 	= 'Option List';
	$string[ 'ilp_element_plugin_category_single' ] 	= 'Single select';
	$string[ 'ilp_element_plugin_category_multi' ] 		= 'Multi select';
	$string[ 'ilp_element_plugin_category_typelabel' ] 	= 'Select type (single/multi)';
        
        return $string;
    }
}
