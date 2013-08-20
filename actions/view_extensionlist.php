<?php

/**
 * Allows the user to view a list of extensions for the given report
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER;


// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/actions_includes.php');

//include the report form class
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_preference_mform.php');


//get the id of the report the preference wil be added to
$report_id	= $PARSER->required_param('report_id',PARAM_INT);

//get the id of the user who the preference wil be created for
$user_id	= $PARSER->required_param('user_id',PARAM_INT);

//get the id of the course if it is set
$course_id	= $PARSER->optional_param('course_id',null,PARAM_INT);


// instantiate the db
$dbc = new ilp_db();


// setup the navigation breadcrumbs
$plpuser	=	$dbc->get_user_by_id($user_id);

$dashboardurl	=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$user_id}&course_id={$course_id}";
$userprofileurl	=	$CFG->wwwroot."/user/profile.php?id={$user_id}";
if ($user_id != $USER->id) {
    if (!empty($access_viewotherilp) && !empty($course_id)) {
        $listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$course_id}";
    } else {
        $listurl	=	"{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
    }

    $PAGE->navbar->add(get_string('ilps', 'block_ilp'),$listurl,'title');
    $PAGE->navbar->add(get_string('ilpname', 'block_ilp'),$dashboardurl,'title');
} else {
    $PAGE->navbar->add(get_string('myilp', 'block_ilp'),$dashboardurl,'title');
}



// setup the navigation breadcrumbs


//user intials
$PAGE->navbar->add(fullname($plpuser),$userprofileurl,'title');

//section name
$PAGE->navbar->add(get_string('viewextension','block_ilp'),null,'title');


// setup the page title and heading
$SITE	=	$dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-configuration');
$PAGE->set_pagelayout(ILP_PAGELAYOUT);
$PAGE->set_url('/blocks/ilp/actions/edit_report_preference.php', $PARSER->get_params());

//include the ilp ajax table class
require_once ($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_tablelib.class.php');

//create the field table

//instantiate the flextable table class
$flextable = new ilp_flexible_table("extensionreport_id{$report_id}user_id".$USER->id);

//define the base url that the table will return to
$flextable->define_baseurl($CFG->wwwroot."/blocks/ilp/actions/view_extensionlist.php?report_id={$report_id}&user_id={$user_id}");


//setup the array holding the column ids
$columns	=	array();
$columns[]	=	'param';
$columns[]	=	'value';
$columns[]	=	'delete';

//setup the array holding the header texts
$headers	=	array();
$headers[]	=	get_string('type','block_ilp');
$headers[]	=	get_string('value','block_ilp');
$headers[]	=	'';

//pass the columns to the table
$flextable->define_columns($columns);

//pass the headers to the table
$flextable->define_headers($headers);

//set the attributes of the table
$flextable->set_attribute('id', 'reportfields-table');
$flextable->set_attribute('cellspacing', '0');
$flextable->set_attribute('class', 'reportfieldstable flexible boxaligncenter generaltable');
$flextable->set_attribute('summary', get_string('reportfields', 'block_ilp'));

$flextable->column_class('label', 'leftalign');

// setup the table - now we can use it
$flextable->setup();

$action =   'report_extension';

//get the data on fields to be used in the table
$preferences		=	$dbc->get_preferences($report_id,null,$action,$user_id);


if (!empty($preferences)) {
	foreach ($preferences as $row) {
		$data = array();
		
        switch  ($row->param)  {

            case 'reportmaxentries':
                $data[] 		=   get_string('maxentries','block_ilp');
                $data[] 			=	$row->value;
                break;

            case 'reportlockdate':
                $data[] 		=   get_string('reportlockdate','block_ilp');
                $data[] 	    =	date("d-m-Y H:i ",$row->value);
                break;

            case 'recurmax':
                $data[] 		=   get_string('recurringmax','block_ilp');
                $data[] 			=	$row->value;
                break;
        }

		//set the edit field


		$data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/delete_preference.php?pref_id={$row->id}&user_id={$user_id}&course_id={$course_id}&report_id={$report_id}'>
									<img class='delete' src='".$OUTPUT->pix_url("/t/delete")."' alt='".get_string('delete')."' title='".get_string('delete')."' />
								 </a>";
		
		$flextable->add_data($data);
	}
}

require_once($CFG->dirroot.'/blocks/ilp/views/view_extensionlist.html');

?>