<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_mform.class.php');

class ilp_element_plugin_page_break_mform  extends ilp_element_plugin_mform {

	protected function specific_definition($mform) {
        //no need for this in this form element
	}
	
	protected function specific_validation($data) {
 	
	 	//no need for this
	 	return $this->errors;
	 }
	 
	 protected function specific_process_data($data) {
	  	
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record("block_ilp_plu_pb",$data->reportfield_id) : false;

	 	if (empty($plgrec)) {
	 		return $this->dbc->create_plugin_record("block_ilp_plu_pb",$data);
	 	} else {
            return true;
	 	}
	 }
	 
	 function definition_after_data() {
	 	
	 }


    function unprocessed_data(&$data)   {
        $data->position         =   $this->dbc->get_new_report_field_position($this->report_id);
        $data->label            =   'Page Break '.$data->position;
        $data->report_id        =   $this->report_id;
        $data->creator_id       =   $this->creator_id;
        $this->summary          =   0;
    }
}
