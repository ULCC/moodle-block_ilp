<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_mform.class.php');

class ilp_element_plugin_free_html_mform  extends ilp_element_plugin_mform {
	
	protected function specific_definition($mform) {

    }
    
	protected function specific_validation($data) {
        if( is_array( $data ) ){
            $data = (object) $data;
        }
	 	if ( empty( $data->description ) )  $this->errors['description'] = get_string('ilp_element_plugin_free_html_markup_required','block_ilp');
        return $this->errors;
    }
    
	protected function specific_process_data($data) {
		
    }
}
