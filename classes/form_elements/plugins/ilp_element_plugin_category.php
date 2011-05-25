<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');
//require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');
//class ilp_element_plugin_category extends ilp_element_plugin_dd{
class ilp_element_plugin_category extends ilp_element_plugin_itemlist{

	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	public $selecttype;
	
    /**
     * Constructor
     */
    function __construct() {
    	
    	parent::__construct();
    	$this->tablename = "block_ilp_plu_cat";
    	$this->data_entry_tablename = "block_ilp_plu_cat_ent";
	$this->items_tablename = "block_ilp_plu_cat_items";
	$this->selecttype = OPTIONSINGLE;
    }


	

    function language_strings(&$string) {
        $string['ilp_element_plugin_category'] 			= 'Category Select';
        $string['ilp_element_plugin_category_type'] 		= 'category select';
        $string['ilp_element_plugin_category_description'] 	= 'A category selector';
	$string[ 'ilp_element_plugin_category_optionlist' ] 	= 'Option List';
	$string[ 'ilp_element_plugin_category_single' ] 	= 'Single select';
	$string[ 'ilp_element_plugin_category_multi' ] 		= 'Multi select';
	$string[ 'ilp_element_plugin_category_typelabel' ] 	= 'Select type (single/multi)';
        
        return $string;
    }
/*
	protected function get_option_list(){
		var_crap( $this->dbc->get_optionlist( $this->reportfield_id, $this->tablename ) );exit ;
//ultimately should take options from a table
		return array(
			1 => 'first cat',
			2 => 'second cat',
			3 => 'third cat',
			10 => 'tenth cat'
		);
	}
*/

    public function audit_type() {
        return get_string('ilp_element_plugin_category_type','block_ilp');
    }
	
	public function entry_form( &$mform ) {
    	//text field for element label
		$optionlist = $this->get_option_list( $this->reportfield_id );
       		$select = &$mform->addElement(
       			'select',
     			$this->reportfield_id,
       			$this->label,
			$optionlist,
        		array('class' => 'form_input')
       	 	);
        
        if (!empty($this->req)) $mform->addRule("$this->reportfield_id", null, 'required', null, 'client');
        $mform->setType('label', PARAM_RAW);
    	
        //return $mform;
    	
    	
	}
}
