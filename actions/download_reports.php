<?php

/**
 * Creates an entry for an report 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk, 
 * @author Greg Pasciak
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


require ('../../../config.php');
global $CFG, $USER;
require_once ($CFG->dirroot.'/lib/filelib.php');
require_once ($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//require_login();
if (($access_viewilp) || ($access_viewotherilp) ) { 

	$queryStr = $_SERVER["QUERY_STRING"];
	$args = explode('/', ltrim($queryStr , '/'));
	$dir = $args[0];
	$fileName = $args [1];

	//copy file to the sitedata/temp folder, send and delete temp file
	if ($dir)  {
    			
		$ilp_filesDir = str_replace('/docroot', "", $CFG->dirroot)."/ilp_files";
   		$ilp_filesDir = str_replace('\docroot', "", $ilp_filesDir);  // on Win server
		
		copy ($ilp_filesDir.'/'.$dir.'/'.$fileName, $CFG->dataroot.'/temp/'.$fileName);
		$temppath=$CFG->dataroot.'/temp/'.$fileName;
		send_temp_file($temppath, $fileName, false);  
	} 
}

?>