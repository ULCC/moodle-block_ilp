<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform.php');

class ilp_element_plugin_free_html_mform  extends ilp_element_plugin_mform {
	
	protected function specific_definition($mform) {

    }
    
	protected function specific_validation($data) {
        if( is_array( $data ) ){
            $data = (object) $data;
        }
	 	if ( empty( $data->description ) )  $this->errors['markup_required'] = get_string('ilp_element_plugin_free_html_markup_required','block_ilp');
        return $this->errors;
    }
    
	protected function specific_process_data($data) {
		
    }
}
