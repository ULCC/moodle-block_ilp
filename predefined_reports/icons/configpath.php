<?php
/*
 * This file should only be used during development and testing as it is needed to allow one copy of the ILP 
 * to be used on two copies of Moodle 
 */

$path_to_config = dirname($_SERVER['SCRIPT_FILENAME']).'/../../../config.php';
while (($collapsed = preg_replace('|/[^/]+/\.\./|','/',$path_to_config,1)) !== $path_to_config) {
    $path_to_config = $collapsed;
}

//require_once($path_to_config);



//when testing and development is over replace with the code below
require_once('../../../config.php');