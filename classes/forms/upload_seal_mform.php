<?php
class upload_seal_mform extends ilp_moodleform
{

   static function seal_file_params()
   {
      global $CFG;

      $r=new stdClass;

      $r->form_options = array('subdirs'=>0,
                               'maxbytes'=>$CFG->userquota,
                               'maxfiles'=>1,
                               'accepted_types'=>array('*.png', '*.jpg', '*.gif', '*.jpeg'));

      $r->context = context_system::instance();
      $r->component = 'block_ilp';
      $r->file_area = 'seal';
      $r->item_id = 1;

      return $r;
   }

   function definition()
   {
      global $CFG;

      $mform=&$this->_form;

      $seal_params=static::seal_file_params();

      $uploader = $mform->addElement('filemanager', 'seal_file_filemanager', get_string('upload_seal', 'block_ilp'), null,
                                     $seal_params->form_options);
      $uploader->setValue($data->{'seal_file_filemanager'});

      $this->add_action_buttons();
   }
}
