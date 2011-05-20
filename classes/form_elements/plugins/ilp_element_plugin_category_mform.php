<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd_mform.php');

class ilp_element_plugin_category_mform  extends ilp_element_plugin_dd_mform {

	public $tablename;
	
	function __construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null) {
		parent::__construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null);
		$this->tablename = "block_ilp_plu_cat";
	}
}
