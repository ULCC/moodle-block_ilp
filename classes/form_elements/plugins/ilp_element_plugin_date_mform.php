<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform.php');

class ilp_element_plugin_date_mform  extends ilp_element_plugin_mform {
	
	  protected function specific_definition($mform) {
	  	//element to define a date as past, present or future
		$optionlist = array(
				ILP_PASTDATE => get_string( 'ilp_element_plugin_date_past' , 'block_ilp' ),
				ILP_PRESENTDATE => get_string( 'ilp_element_plugin_date_present' , 'block_ilp' ),
				ILP_FUTUREDATE => get_string( 'ilp_element_plugin_date_future' , 'block_ilp' ),
				ILP_ANYDATE => get_string( 'ilp_element_plugin_date_anydate' , 'block_ilp' )
		);

		$mform->addElement(
				'select',
				'datetense',
				get_string( 'ilp_element_plugin_date_tense' , 'block_ilp' ),
				$optionlist
		);

		$mform->addRule('datetense', null, 'required', null, 'client');
        $mform->setType('datetense', PARAM_INT);
	}
	
	 protected function specific_validation($data) {
	 	$data = (object) $data;
	 	return $this->errors;
	 }
	 
	 protected function specific_process_data($data) {
	  	
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record("block_ilp_plu_dat",$data->reportfield_id) : false;
	 	
	 	if (empty($plgrec)) {
	 		return $this->dbc->create_plugin_record("block_ilp_plu_dat",$data);
	 	} else {
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield("block_ilp_plu_dat",$data->reportfield_id);
	
	 		//create a new object to hold the updated data
	 		$pluginrecord 					=	new stdClass();
	 		$pluginrecord->id				=	$oldrecord->id;
			$pluginrecord->datetense		=	$data->datetense;
	 			
	 		//update the plugin with the new data
	 		return $this->dbc->update_plugin_record("block_ilp_plu_dat",$pluginrecord);
	 	}
	 }
	 
	 function definition_after_data() {
	 	
	 }
	
}
