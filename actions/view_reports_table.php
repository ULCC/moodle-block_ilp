<?php
if (!defined('MOODLE_INTERNAL')) {
    // this must be included from a Moodle page
    die('Direct access to this script is forbidden.');
}


//include the ilp ajax table class
require_once ($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_tablelib.class.php');

//create the field table

//instantiate the flextable table class
$flextable = new flexible_table("configurationreport_id{$report_id}user_id".$USER->id);

//define the base url that the table will return to
$flextable->define_baseurl($CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php?report_id={$report_id}");


//setup the array holding the column ids
$columns	=	array();
$columns[]	=	'reportname';
$columns[]	=	'editreport';
$columns[]	=	'editprompts';
$columns[]	=	'editpermission';
$columns[]	=	'changestatus';


//setup the array holding the header texts
$headers	=	array();
$headers[]	=	'';
$headers[]	=	'';
$headers[]	=	'';
$headers[]	=	'';
$headers[]	=	'';



//pass the columns to the table
$flextable->define_columns($columns);

//pass the headers to the table
$flextable->define_headers($headers);

//tell the table it is not sortable
$flextable->sortable(false);

//set the attributes of the table
$flextable->set_attribute('id', 'reportfields-table');
$flextable->set_attribute('cellspacing', '0');
$flextable->set_attribute('class', 'reportfieldstable flexible boxaligncenter generaltable');
$flextable->set_attribute('summary', get_string('reportfields', 'block_ilp'));

$flextable->column_class('label', 'leftalign');

// setup the table - now we can use it
$flextable->setup();

//get the data on fields to be used in the table
$reports		=	$dbc->get_reports_table($flextable);

if (!empty($reports)) {
	foreach ($reports as $row) {
		$data = array();
		
		$data[] 		=	$row->name;

		//set the edit report link
		$data[] 		=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report.php?report_id={$row->id}'>
									<img class='edit' src='".$OUTPUT->pix_url("/i/edit")."' alt='".get_string('edit')."' title='".get_string('edit')."' />
								 </a>";
		
		//set the edit report prompts link
		$data[] 		=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_prompt.php?report_id={$row->id}'>
									<img class='prompt' src='".$OUTPUT->pix_url('i/questions')."' alt='".get_string('editfields','block_ilp')."' title='".get_string('editfields','block_ilp')."' />
								 </a>";
		
		//set the edit report permissions link
		$data[] 		=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_permissions.php?report_id={$row->id}'>
									<img class='permissions' src='".$OUTPUT->pix_url('i/roles')."' alt='".get_string('editpermissions','block_ilp')."' title='".get_string('editpermissions','block_ilp')."' />
								 </a>";

		//decide whether the report is enabled or disabled and set the image and link accordingly
		$title 			= 	(!empty($row->status)) ? get_string('disablereport','block_ilp')  : get_string('enablereport','block_ilp');
		
		$icon	= 	(!empty($row->status)) ? "hide" : "show";
		
		$data[] 		=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_status.php?report_id={$row->id}'>
									<img class='status' src=".$OUTPUT->pix_url("/i/".$icon)." alt='".$title."' title='".$title."' />
							</a>";
		
		/*
		//set the delete field this is not enabled at the moment
		$data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/delete_field.php?reportfield_id={$row->id}&report_id={$report_id}&course_id={$course_id}'>
									<img class='delete' src='".$OUTPUT->pix_url("/t/delete")."' alt='".get_string('delete')."' title='".get_string('delete')."' />
								 </a>";
		
		*/
		$flextable->add_data($data);
		
	}
}


require_once($CFG->dirroot.'/blocks/ilp/views/view_reports_table.html');
