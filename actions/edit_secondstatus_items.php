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

require_once($CFG->dirroot . '/blocks/ilp/classes/database/ilp_db.php');

require_once($CFG->dirroot . '/blocks/ilp/actions/edit_secondstatus_items_mform.php');

$sectionname = get_string('tab_block_items', 'block_ilp');

$url	=	$CFG->wwwroot."/admin/settings.php?section=blocksettingilp";

$dbc = new ilp_db();

$PAGE->set_url(new moodle_url($url));

$PAGE->navbar->add($sectionname);

$PAGE->set_pagelayout(ILP_PAGELAYOUT);

echo $OUTPUT->header();

$items = $dbc->get_secondstatus_items();

$mform = new edit_secondstatus_items_form();

if ($mform->is_submitted()) {

    $data = $mform->get_data();

    foreach ($data as $newitemkey => $newitem) {
        if ($newitem) {
            foreach ($items as $item) {
                if ($item->value . '_name' == $newitemkey) {
                    $item->name = $newitem;
                    $dbc->update_secondstatus_item($item);
                }
            }
        }
    }

}
$mform->display();

echo $OUTPUT->footer();