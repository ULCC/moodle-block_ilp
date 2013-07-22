<?php
include_once(__DIR__.'/../configpath.php');
global $USER, $CFG, $SESSION, $PARSER;

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//include the form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/upload_seal_mform.php');

$mform=new upload_seal_mform();

if($mform->is_cancelled())
{
   print "Arse";
   exit;
}
if($data=$mform->get_data())
{

   $seal_params=upload_seal_mform::seal_file_params();

   file_save_draft_area_files($data->attachments,$seal_params->context,$seal_params->component,
                              $seal_params->file_area,$seal_params->item_id,$seal_params->form_options);

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
