<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_mform.class.php');


class ilp_element_plugin_file_mform  extends ilp_element_plugin_mform {
	
	  	
	
	protected function specific_definition($mform) {


        $mbsize =   1048576;

        for($i=1;$i<=20;$i++)    {
            $optionlist[$i * $mbsize]   =   $i.'mb' ;
        }


        $mform->addElement(
            'select',
            'maxsize',
            get_string('ilp_element_plugin_file_maxsize', 'block_ilp'),
            $optionlist,
            array('class' => 'form_input')
        );

        $mform->addElement('advcheckbox', 'multiple', get_string('ilp_element_plugin_file_multiple', 'block_ilp'), get_string('yes'), array('group' => 1), array(0, 1));

        $mform->addElement(
            'text',
            'maxfiles',
            get_string('ilp_element_plugin_file_maxfiles', 'block_ilp'),
            array('class' => 'form_input')
        );
        
        $mform->addRule('maxfiles', null, 'maxlength', 2, 'client');
        $mform->setType('maxfiles', PARAM_INT);

        $optionlist     =   array(
                                  'all' => get_string('all'),
                                  'web_image'=>'Web image',
                                  'non_web_image'=>'Non web image',
                                  'audio'=>'Audio',
                                  'non_web_audio'=>'Non web audio',
                                  'video'=>'Video',
                                  'non_web_video'=>'Non web video',
                                  'document'=>'Document',
                                  'spreadsheet,openoffice'=>'Spread sheet ',
                                  'spreadsheet, openoffice'=>'Open office',
                                  'text'=>'Text',
                                  'script'=>'Script',
                                  'plaintext'=>'Plain text',
                                  'moodle'=>'Moodle',
                                  'application'=>'Application',
                                  'script'=>'Script',
                                  'plaintext'=>'Plain text',
                                  '.pdf'=>'PDF',
                                  '.ppt'=>'Powerpoint (PPT)',
                                  '.pptx'=>'Powerpoint (PPTX)',
                                  '.zip'=>'Compressed (ZIP)',
                                  '.csv'=>'Comma Separated Values (CSV)'
                                  );

        $select =   $mform->addElement(
                                        'select',
                                        'acceptedtypes',
                                        get_string('ilp_element_plugin_file_acceptedfiles', 'block_ilp'),
                                        $optionlist,
                                        array('class' => 'form_input acceptedtypes')
                                    );

        $select->setMultiple(true);

    }
	
	protected function specific_validation($data) {
 	
	 	$data = (object) $data;

	 	return $this->errors;
	 }
	 
	 protected function specific_process_data($data) {
	  	
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record("block_ilp_plu_file",$data->reportfield_id) : false;

        if (!empty($data->acceptedtypes)) $data->acceptedtypes    =   base64_encode(serialize($data->acceptedtypes));

	 	if (empty($plgrec)) {
	 		return $this->dbc->create_plugin_record("block_ilp_plu_file",$data);
	 	} else {
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield("block_ilp_plu_file",$data->reportfield_id);
             $pluginrecord 					=	new stdClass();
             $pluginrecord->id				=	$oldrecord->id;
             $pluginrecord->maxsize	        =	$data->maxsize;
             $pluginrecord->maxfiles	    =	$data->maxfiles;
             $pluginrecord->multiple	    =	$data->multiple;
             $pluginrecord->acceptedtypes	=	$data->acceptedtypes;
	 		//update the plugin with the new data
	 		return $this->dbc->update_plugin_record("block_ilp_plu_file",$pluginrecord);
	 	}
	 }

	 function definition_after_data() {
	 	
	 }
	
}
