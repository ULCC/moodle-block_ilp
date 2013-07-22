<?php
class upload_seal_mform extends ilp_moodleform
{
   function definition()
   {
      global $CFG;

      $mform=&$this->_form;

      $seal_options = array('subdirs'=>0,
                            'maxbytes'=>$CFG->userquota,
                            'maxfiles'=>1,
                            'accepted_types'=>array('*.png', '*.jpg', '*.gif', '*.jpeg'));

      $context = context_system::instance();
      $component = 'ilp';
      $file_area = 'seal';
      $item_id = 1;

      $data = new stdClass();
      $data = file_prepare_standard_filemanager($data, 'seal_file', $seal_options, $context, $component, $file_area, $item_id);
      $uploader = $mform->addElement('filemanager', 'seal_file_filemanager', get_string('upload_seal', 'block_ilp'), null, $seal_options);
      $uploader->setValue($data->{'seal_file_filemanager'});

      $this->add_action_buttons();
   }
}
