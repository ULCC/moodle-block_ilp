<?php

//require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin.php');
//require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');

class ilp_element_plugin_rdo extends ilp_element_plugin_itemlist{
	
	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	public $selecttype;	//always single - it's a radio group

    /**
     * Constructor
     */
    function __construct() {
    	parent::__construct();
    	$this->tablename = "block_ilp_plu_rdo";
    	$this->data_entry_tablename = "block_ilp_plu_rdo_ent";
	$this->items_tablename = "block_ilp_plu_rdo_items";
	$this->selecttype = OPTIONSINGLE;
    }
	
	
    public function audit_type() {
        return get_string('ilp_element_plugin_rdo_type','block_ilp');
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    function language_strings(&$string) {
        $string['ilp_element_plugin_rdo'] 		= 'Radio group';
        $string['ilp_element_plugin_rdo_type'] 		= 'radio group';
        $string['ilp_element_plugin_rdo_description'] 	= 'A radio group';
        
        return $string;
    }

   	/**
     * Delete a form element
     */
    public function delete_form_element($reportfield_id) {
    	return parent::delete_form_element($this->tablename, $reportfield_id);
    }
    
    /**
    * this function returns the mform elements taht will be added to a report form
	*
    */
    public	function entry_form( &$mform ) {
    	
    	$fieldname	=	"{$this->reportfield_id}_field";
    	
		$optionlist = $this->get_option_list( $this->reportfield_id );
		$elementname = $fieldname;
		$radioarray = array();
		foreach( $optionlist as $key => $value ){
			$radioarray[] = &MoodleQuickForm::createElement( 'radio', $elementname, '', $value, $key );
		}


        $mform->addGroup(
            $radioarray,
            $elementname,
	    	$this->label,
			'',
			'',
            array('class' => 'form_input'),
		    false
        );
        
        if (!empty($this->req)) $mform->addRule($elementname, null, 'required', null, 'client');

        $mform->setType($elementname, PARAM_RAW);
    }
}

