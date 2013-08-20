<?php
require_once('../lib.php');
global $USER, $CFG, $SESSION, $PARSER;

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//include the form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/upload_seal_mform.php');

$mform=new upload_seal_mform();

if($mform->is_cancelled())
{
   redirect("$CFG->wwwroot/admin/settings.php?section=blocksettingilp");
   exit;
}
if($data=$mform->get_data())
{

   $seal_params=upload_seal_mform::seal_file_params();

   file_save_draft_area_files($data->seal_file_filemanager,$seal_params->context->id,$seal_params->component,
                              $seal_params->file_area,$seal_params->item_id,$seal_params->form_options);

   set_config('sealname','','block_ilp');

   $fs=get_file_storage();
   foreach($fs->get_area_files(1, 'block_ilp', 'seal', 1) as $file)
   {
      if($file->get_filename()!='.')
      {
         set_config('sealname',$file->get_filename(),'block_ilp');
         break;
      }
   }

   redirect("$CFG->wwwroot/admin/settings.php?section=blocksettingilp");
   exit;
}
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/upload_seal.php', $PARSER->get_params());

print $OUTPUT->header();

$mform->display();

print $OUTPUT->footer();
