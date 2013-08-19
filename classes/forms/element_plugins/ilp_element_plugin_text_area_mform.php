<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_mform.class.php');

class ilp_element_plugin_text_area_mform  extends ilp_element_plugin_mform {
	
	  	
	
	  protected function specific_definition($mform) {

	  	//set the maximum length of the field default to 255
        $mform->addElement(
            'text',
            'minimumlength',
            get_string('ilp_element_plugin_text_area_minimumlength', 'block_ilp'),
            array('class' => 'form_input')
        );
        
        $mform->addRule('minimumlength', null, 'maxlength', 3, 'client');
        //$mform->addRule('minimumlength', null, 'required', null, 'client');
        $mform->setType('minimumlength', PARAM_INT);
	  	
        //set the maximum length of the field default to 255
        $mform->addElement(
            'text',
            'maximumlength',
            get_string('ilp_element_plugin_text_area_maximumlength', 'block_ilp'),
            array('class' => 'form_input')
        );
        
        $mform->addRule('maximumlength', null, 'maxlength', 4, 'client');
        //$mform->addRule('maximumlength', null, 'required', null, 'client');
        $mform->setType('maximumlength', PARAM_INT);
	}
	
	 protected function specific_validation($data) {
 	
	 	$data = (object) $data;
 	
	 	if ($data->maximumlength < 0 || $data->maximumlength > 9999) $this->errors['maximumlength'] = get_string('ilp_element_plugin_text_area_maxlengthrange','block_ilp');
	 	if ($data->maximumlength < $data->minimumlength) $this->errors['maximumlength'] = get_string('ilp_element_plugin_text_area_maxlessthanmin','block_ilp');
	 	
	 	return $this->errors;
	 }
	 
	 protected function specific_process_data($data) {
	  	
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record("block_ilp_plu_are",$data->reportfield_id) : false;
	 	
	 	if (empty($plgrec)) {
	 		return $this->dbc->create_plugin_record("block_ilp_plu_are",$data);
	 	} else {
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield("block_ilp_plu_are",$data->reportfield_id);
	
	 		//create a new object to hold the updated data
	 		$pluginrecord 					=	new stdClass();
	 		$pluginrecord->id				=	$oldrecord->id;
	 		$pluginrecord->minimumlength	=	$data->minimumlength;
	 		$pluginrecord->maximumlength	=	$data->maximumlength;
	 			
	 		//update the plugin with the new data
	 		return $this->dbc->update_plugin_record("block_ilp_plu_are",$pluginrecord);
	 	}
	 }
	 
	 function definition_after_data() {
	 	
	 }
	
}
