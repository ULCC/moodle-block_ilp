<?php
/**
 * This file has created with only intention of serving icon file for status.
 * This can be modified later on if needed
 */
require('../../config.php');
require_once($CFG->dirroot.'/lib/filestorage/file_storage.php');

require_login();
if (isguestuser()) {
    die();
}
$con = $_GET['con']; //context id
$com = $_GET['com']; // component
$a = $_GET['a']; // file area
$i = $_GET['i']; //item id
$f = $_GET['f']; //file name

$fs = get_file_storage();
$file = $fs->get_file($con, $com, $a, $i,'/',$f);
echo send_stored_file($file, 84000);

