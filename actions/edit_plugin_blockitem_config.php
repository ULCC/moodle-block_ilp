<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Joseph.Cape
 * Date: 16/07/13
 * Time: 15:25
 * To change this template use File | Settings | File Templates.
 */

require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER;

require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

require_once($CFG->dirroot . '/blocks/ilp/constants.php');

require_once($CFG->dirroot . '/blocks/ilp/actions/edit_plugin_blockitem_config_mform.php');

$sectionname = get_string('tab_block_items', 'block_ilp');

$url	=	$CFG->wwwroot."/admin/settings.php?section=blocksettingilp";

$PAGE->set_url(new moodle_url($url));

$PAGE->navbar->add($sectionname);

$PAGE->set_pagelayout(ILP_PAGELAYOUT);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('tab_block_items_cfg', 'block_ilp'));

$mform = new blockitem_config_form();

if ($mform->is_submitted()) {
    $data = $mform->get_data();

    $name = 'show_current_status';
    $value = $data->currentstatus_yesno;
    set_config($name, $value, 'block_ilp');

    $name = 'show_progressbar';
    $value = $data->progressbar_yesno;
    set_config($name, $value, 'block_ilp');

    $name = 'show_linked_name';
    $value = $data->linked_name_yesno;
    set_config($name, $value, 'block_ilp');

    $name = 'show_userpicture';
    $value = $data->userpicture_yesno;
    set_config($name, $value, 'block_ilp');

    $name = 'show_attendancepunctuality';
    $value = $data->attendancepunctuality_yesno;
    set_config($name, $value, 'block_ilp');

    $name = 'show_attendancepunctuality_mis_plugin';
    $value = $data->attendancepunctuality_mis_plugin;
    set_config($name, $value, 'block_ilp');

}
$mform->display();

echo $OUTPUT->footer();