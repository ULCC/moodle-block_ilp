<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform.php');

class ilp_element_plugin_gradebooktracker_mform  extends ilp_element_plugin_mform {

	public $tablename;
    function __construct() {
    	parent::__construct();
    	$this->tablename = "block_ilp_plu_gradebooktracker";
    	//$this->data_entry_tablename = "block_ilp_plu_gradebooktracker_ent";
    }
	
	protected function specific_definition($mform) {
	}
	
	protected function specific_validation($data) {
	 }

	protected function specific_process_data($data) {
	 		$element_id = $this->dbc->create_plugin_record($this->tablename,$data);
    }
}
