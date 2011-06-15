<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform_itemlist.php');

class ilp_element_plugin_status_mform  extends ilp_element_plugin_mform_itemlist {

	public 	$tablename;
	public 	$items_tablename;
	public	$reporfield_link_table;
	
	function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {
		parent::__construct($report_id,$plugin_id,$creator_id,$reportfield_id);
		$this->tablename = "block_ilp_plu_sts";
		$this->items_tablename = "block_ilp_plu_sts_items";
		$this->reportfield_link_table	=	'block_ilp_plu_rf_sts';
	}
	
	
	function specific_validation($data) {
		
	}
	
	
	function specific_definition($mform) {
		
		//the id of the statusfield, we should only be using the default status field 
        $mform->addElement('hidden', 'status_id');
        $mform->setType('status_id', PARAM_INT);
        
        //THE status id should be the first status item id 1
        $mform->setDefault('status_id', '1');
	}
	
	
	function specific_process_data($data) {
		global 	$USER;		
		
		//if this field does not already have a status field add it otherwise do nothing
		if (!$this->dbc->has_statusfield($data->status_id,$data->report_id)) {
			$statusfield							=	new stdClass();
			$statusfield->status_id					=	$data->status_id;
			$statusfield->reportfield_id			=	$data->reportfield_id;
			$statusfield->creator_id				=	$USER->id;
			$this->dbc->create_statusfield($statusfield);
		}
	}
	
	
	
	
}
