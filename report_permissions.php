<?php
/*
 * Determines the permissions of the current user in the current report
 * by looking at the users roles in the current context  
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

global	$CFG,$USER;

require_once($CFG->dirroot."/blocks/ilp/lib.php");

//get the user id if it is not set then we will pass the global $USER->id 
$user_id   = $PARSER->optional_param('user_id',$USER->id,PARAM_INT);

//get the id of the report 
$report_id   = $PARSER->required_param('report_id',PARAM_INT);

if (!isset($context)) {
	print_error('contextnotset');
} 

$dbc	=	new ilp_db();

//get all of the users roles in the current context and save the id of the roles into
//an array 
$role_ids	=	 array();


$authuserrole	=	$dbc->get_role_by_name(ILP_AUTH_USER_ROLE);
if (!empty($authuserrole)) $role_ids[]	=	$authuserrole->id;

if ($roles = get_user_roles($context, $USER->id)) {
 	foreach ($roles as $role) {
 		$role_ids[]	= $role->roleid;
 	}
}




//REPORT CAPABILITIES

$access_report_createreports	=	0;
$access_report_editreports		=	0;
$access_report_deletereports	=	0;
$access_report_viewreports		=	0;	
$access_report_viewilp			=	0;
$access_report_viewotherilp		=	0;

//comment capabilites
$access_report_addcomment		=	0;
$access_report_editcomment		=	0;
$access_report_deletecomment	=	0;
$access_report_viewcomment		=	0;



//we only need to check if a report permission has been assigned 
//if the user has the capability in the current context 


if (!empty($access_createreports)) { 

	//moodle 2.0 throws an error whena comparison is carried out for the context name in
    //pure sql. This could have something to do with the /: in the context name. So I am
    //having to get the capability record id first and then pass it to the 
    $capability	=	$dbc->get_capability_by_name('block/ilp:addreport');

	$access_report_createreports	=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}	


if ($access_editreports) { 
	
	$capability	=	$dbc->get_capability_by_name('block/ilp:editreport');
	if (!empty($capability)) $access_report_editreports		=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}	

if ($access_deletereports) { 
	
	$capability	=	$dbc->get_capability_by_name('block/ilp:deletereport');
	if (!empty($capability))	$access_report_deletereports	=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}

if ($access_viewreports) { 
	
	$capability	=	$dbc->get_capability_by_name('block/ilp:viewreport');
	if (!empty($capability))	$access_report_viewreports		=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}

if ($access_viewilp) { 
	
	$capability	=	$dbc->get_capability_by_name('block/ilp:viewilp');
	if (!empty($capability))	$access_report_viewilp			=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
} 

if ($access_viewotherilp) {

	$capability	=	$dbc->get_capability_by_name('block/ilp:viewotherilp');
	if (!empty($capability))	$access_report_viewotherilp		=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}


if ($access_addcomment) {

	$capability	=	$dbc->get_capability_by_name('block/ilp:addcomment');
	if (!empty($capability))	$access_report_addcomment		=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}

if ($access_editcomment) {

	$capability	=	$dbc->get_capability_by_name('block/ilp:editcomment');
	if (!empty($capability))	$access_report_editcomment		=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}

if ($access_deletecomment) {

	$capability	=	$dbc->get_capability_by_name('block/ilp:deletecomment');
	if (!empty($capability))	$access_report_deletecomment		=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}

if ($access_viewcomment) {

	$capability	=	$dbc->get_capability_by_name('block/ilp:viewcomment');
	if (!empty($capability))	$access_report_viewcomment		=	$dbc->has_report_permission($report_id,$role_ids,$capability->id);
}


//check for the ilpviewall capability at site level this gives the user rights to view all
$ilpadmin				=	has_capability('block/ilp:ilpviewall',$sitecontext);

//this is only in for debug and testing purposes 
if (ilp_is_siteadmin($USER->id) || $ilpadmin) {
    $access_report_createreports	=	1;
    $access_report_editreports		=	1;
    $access_report_deletereports	=	1;
    $access_report_viewreports		=	1;
    $access_report_viewilp			=	1;
    $access_report_viewotherilp		=	1;
	
}


if (empty($access_report_viewotherilp) && $USER->id != $user_id) {
	//the user doesnt have the capability to create this type of report entry
	print_error('accessnotallowed','block_ilp');	

}
 
