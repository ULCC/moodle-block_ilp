<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist.class.php');

class ilp_element_plugin_category extends ilp_element_plugin_itemlist{

	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	public $selecttype;
	
    /**
     * Constructor
     */
    function __construct() {
    	$this->tablename = "block_ilp_plu_cat";
    	$this->data_entry_tablename = "block_ilp_plu_cat_ent";
		$this->items_tablename = "block_ilp_plu_cat_items";
		$this->selecttype = ILP_OPTIONSINGLE;
		parent::__construct();
    }
    
    /*
    * should not be able to add a category selector if there is already one one the form
    */
    public function can_add( $report_id ){
        return !$this->dbc->element_type_exists( $report_id, $this->tablename );
    }

    static function language_strings(&$string) {
        $string['ilp_element_plugin_category'] 			= 'Category Select';
        $string['ilp_element_plugin_category_type'] 		= 'Category select';
        $string['ilp_element_plugin_category_description'] 	= 'A category selector';
		$string[ 'ilp_element_plugin_category_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_category_single' ] 	= 'Single select';
		$string[ 'ilp_element_plugin_category_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_category_typelabel' ] 	= 'Select type (single/multi)';
        
        return $string;
    }


    public function audit_type() {
        return get_string('ilp_element_plugin_category_type','block_ilp');
    }
	
	public function entry_form( &$mform ) {
		
		//create the fieldname
    	$fieldname	=	"{$this->reportfield_id}_field";

    	if (!empty($this->description)) {
    		$mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description,
                                                                                                          ENT_QUOTES,
                                                                                                          'UTF-8'),ILP_STRIP_TAGS_DESCRIPTION));
    		$this->label = '';
    	} 
    	
		//definition for user form
		$optionlist = $this->get_option_list( $this->reportfield_id );
       	$select = $mform->addElement(
				       			'select',
				     			$fieldname,
				       			$this->label,
								$optionlist,
				        		array('class' => 'form_input')
				       	 	);




        $mform->setType($fieldname, PARAM_RAW);

        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'server');

	}
}
