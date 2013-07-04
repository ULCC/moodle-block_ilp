<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist.class.php');

class ilp_element_plugin_dd extends ilp_element_plugin_itemlist{
	
	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	protected $selecttype;	//1 for single, 2 for multi
	protected $id;		//loaded from pluginrecord
	
    /**
     * Constructor
     */
    function __construct() {
    	$this->tablename 			= "block_ilp_plu_dd";
    	$this->data_entry_tablename = "block_ilp_plu_dd_ent";
		$this->items_tablename 		= "block_ilp_plu_dd_items";
	parent::__construct();
    }
	
	
	/**
     * TODO comment this
     * beware - different from parent method because of variable select type
     * radio and other single-selects inherit from parent
     */
    public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);	
		if (!empty($reportfield)) {
			$this->reportfield_id	=	$reportfield_id;
			$this->plugin_id	=	$reportfield->plugin_id;
			$plugin			=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
			$pluginrecord		=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);
			if (!empty($pluginrecord)) {
				$this->id				=	$pluginrecord->id;
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;
				$this->req				=	$reportfield->req;
				$this->position			=	$reportfield->position;
				$this->selecttype		=	$pluginrecord->selecttype;

			}
		}
		return false;	
    }	

	

    public function audit_type() {
        return get_string('ilp_element_plugin_dd_type','block_ilp');
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    static function language_strings(&$string) {
        $string['ilp_element_plugin_dd'] 				= 'Select';
        $string['ilp_element_plugin_dd_type'] 			= 'Select box';
        $string['ilp_element_plugin_dd_description'] 	= 'A drop-down selector';
		$string[ 'ilp_element_plugin_dd_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_dd_single' ] 		= 'Single select';
		$string[ 'ilp_element_plugin_dd_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_dd_typelabel' ] 			= 'Select type (single/multi)';
		$string[ 'ilp_element_plugin_dd_existing_options' ] 	= 'existing options';
		$string[ 'ilp_element_plugin_error_item_key_exists' ]	= 'The following key already exists in this element';
		$string[ 'ilp_element_plugin_error_duplicate_key' ]		= 'Duplicate key';
	        
        return $string;
    }

	/**
	* handle user input
	**/
	 public	function entry_specific_process_data($reportfield_id,$entry_id,$data) {
	
		/*
		* parent method is fine for simple form element types
		* dd types will need something more elaborate to handle the intermediate
		* items table and foreign key
		*/
		return $this->entry_process_data($reportfield_id,$entry_id,$data); 	
	 }
}

