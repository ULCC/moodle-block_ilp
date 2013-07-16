<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Joseph.Cape
 * Date: 16/07/13
 * Time: 15:25
 * To change this template use File | Settings | File Templates.
 */

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER;

require_once($CFG->dirroot . '/blocks/ilp/db/admin_accesscheck.php');

$sectionname	=	get_string('administrationsite');

$PAGE->navbar->add($sectionname,null,'title');

$sectionname = get_string('tab_block_items', 'block_ilp');

$PAGE->navbar->add($sectionname,null,'title');

$url	=	$CFG->wwwroot."/admin/settings.php?section=blocksettingilp";

$PAGE->set_url(new moodle_url($url));

$PAGE->navbar->add(get_string('blockname', 'block_ilp'),$url,'title');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('pluginconfig', 'block_ilp'));

//$mform->display();

echo $OUTPUT->footer();