<?php

if (!defined('MOODLE_INTERNAL')) {
    // this must be included from a Moodle page
    die('Direct access to this script is forbidden.');
}


$has_state_field	=	 (!$dbc->has_state_fields($report_id)) ?	false	:	true;

//by default we will show the student status
$studentstatus	=	true;

$tabs	=	array();
$tabrows	=	array();

if (!empty($has_state_field)) {
	//student status is not displayed whena report has a state field 
	$studentstatus	=	false;
	
	//get the state field and load all of the various states so we can make an array with it
	$states			=		$dbc->get_report_stateitems($report_id);
	
	$i	=	0;
	
	//loop through the state items and make a tab for each item
	foreach($states as $s)	{
		$tabrows[]	=	new tabobject($i,"",$s->name);
		$i++;
	}
	
	$tabs[] = $tabrows;
	
} else {
	
	//get all enabled reports
	$userreports	=	$dbc->get_enabledreports();

	$capability	=	$dbc->get_capability_by_name('block/ilp:viewreport');
	//loop through all enabled reports and add a tab for each
	foreach($userreports as $r)	{
		//check if the current user has the capability to view the report if yes add tab
		if ($dbc->has_report_permission($r->id,$role_ids,$capability->id)) { 
			$tabrows[]	=	new tabobject($r->id,"",$r->name);
		}
	}
	
	$tabs[] = $tabrows;
}


//require report_tabs.html
require_once($CFG->dirroot.'/blocks/ilp/views/report_tabs.html');



?>
