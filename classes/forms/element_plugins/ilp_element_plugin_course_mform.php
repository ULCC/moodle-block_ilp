<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/forms/element_plugins/ilp_element_plugin_dd_mform.php');

class ilp_element_plugin_course_mform  extends ilp_element_plugin_dd_mform {

	public $tablename;
	
	function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {
		parent::__construct($report_id,$plugin_id,$creator_id,$reportfield_id=null);
		$this->tablename = "block_ilp_plu_crs";
	}
	  protected function specific_definition($mform) {
		//no action necessary
	  }


	 
	 protected function specific_process_data($data) {
	  	
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record( $this->tablename, $data->reportfield_id ) : false;
	 	
	 	if (empty($plgrec)) {
	 		return $this->dbc->create_plugin_record( $this->tablename, $data );
	 	} else {
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield( $this->tablename, $data->reportfield_id );
	
	 		//create a new object to hold the updated data
	 		$pluginrecord 					=	new stdClass();
	 		$pluginrecord->id				=	$oldrecord->id;
	 			
	 		//update the plugin with the new data
	 		return $this->dbc->update_plugin_record( $this->tablename, $pluginrecord );
	 	}
	 }

	 protected function specific_validation($data) {
	 	$data = (object) $data;
	 	return $this->errors;
	 }
}
