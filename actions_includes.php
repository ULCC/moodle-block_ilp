<?php

//include the moodle library
require_once($CFG->dirroot.'/lib/moodlelib.php');

//include the ilp parser class
require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_parser.class.php');

//include ilp db class
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

require_once($CFG->dirroot."/blocks/ilp/classes/ilp_formslib.class.php");

//include the library file
require_once($CFG->dirroot.'/blocks/ilp/lib.php');


//include the static constants
require_once($CFG->dirroot.'/blocks/ilp/constants.php');

//if this is moodle 1.9 then require the moodle 2 emulator
if (stripos($CFG->release,"2.") === false) require_once($CFG->dirroot.'/blocks/ilp/db/moodle2_emulator.php');

//include the access checks file
require_once($CFG->dirroot.'/blocks/ilp/db/accesscheck.php');
?>