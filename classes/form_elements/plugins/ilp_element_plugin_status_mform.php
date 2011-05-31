<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform_itemlist.php');

class ilp_element_plugin_status_mform  extends ilp_element_plugin_mform_itemlist {

	public $tablename;
	public $items_tablename;
	
	function __construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null) {
		
		parent::__construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id);
		$this->tablename = "block_ilp_plu_sts";
		$this->items_tablename = "block_ilp_plu_sts_items";
		
	}
}
