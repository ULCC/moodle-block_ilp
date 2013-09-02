<?php
if (!defined('MOODLE_INTERNAL')) {
    // this must be included from a Moodle page
    die('Direct access to this script is forbidden.');
}


//include the ilp ajax table class
require_once ($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_tablelib.class.php');

//create the field table

//instantiate the flextable table class
$flextable = new ilp_flexible_table("configurationreport_id{$report_id}user_id".$USER->id);

//define the base url that the table will return to
$flextable->define_baseurl($CFG->wwwroot."/blocks/ilp/actions/edit_report_configuration.php?report_id={$report_id}");


//setup the array holding the column ids
$columns	=	array();
$columns[]	=	'reportname';
$columns[]	=	'moveup';
$columns[]	=	'movedown';
$columns[]	=	'editreport';
$columns[]	=	'editprompts';
$columns[]	=	'editgraphs';
$columns[]	=	'editpermission';
$columns[]	=	'changestatus';
$columns[]	=	'send_to_vault';
$columns[]	=	'clone_report';
$columns[]	=	'delete';


//setup the array holding the header texts
$headers	=	array();
$headers[]	=	'';
$headers[]	=	'';
$headers[]	=	'';
$headers[]	=	'';
$headers[]	=	'';
$headers[]	=	'';
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

$minreport = $dbc->upperlower_report_position('MIN');
$maxreport = $dbc->upperlower_report_position('MAX');

$totalreportfields	=	count($reports);
if (!empty($reports)) {
    foreach ($reports as $row) {
        $data = array();

        $form_name = $row->name;
        if (!is_numeric($row->vault)) {
            $form_name .= ' [' . get_string('warning_vault_has_no_value', 'block_ilp') . ']';
        }
        $data[] 		=	$form_name;
        if ($row->position != $minreport) {
            //if the field is in any position except 1 it needs a up icon
            $title 	=	get_string('moveup','block_ilp');
            $icon	=	$OUTPUT->pix_url("/t/up");
            $movetype	=	"up";

            $data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/move_report.php?report_id={$row->id}&move=".ILP_MOVE_UP."&position={$row->position}'>
									<img class='move' src='{$icon}' alt='{$title}' title='{$title}' />
								 	</a>";
        } else {
            $data[] 	=	"";
        }

        if ($row->position != $maxreport) {
            //if the field is in any position except last it needs a down icon
            $title 	=	get_string('movedown','block_ilp');
            $icon	=	$OUTPUT->pix_url("/t/down");
            $movetype	=	"down";

            $data[] 			=	"<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/move_report.php?report_id={$row->id}&move=".ILP_MOVE_DOWN."&position={$row->position}'>
									<img class='move' src='{$icon}' alt='{$title}' title='{$title}' />
								 	</a></div>";
        } else {
            $data[] 	=	"";
        }

        //set the edit report link
        $data[] 		=	"<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report.php?report_id={$row->id}'>
									<img class='edit' src='".$OUTPUT->pix_url("/i/edit")."' alt='".get_string('edit')."' title='".get_string('edit')."' />
								 </a></div>";

        //set the edit report prompts link
        $data[] 		=	"<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_prompt.php?report_id={$row->id}'>
									<img class='prompt' src='".$OUTPUT->pix_url('i/questions')."' alt='".get_string('editfields','block_ilp')."' title='".get_string('editfields','block_ilp')."' />
								 </a></div>";

        //set the edit report graph link
        $data[] 		=	"<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_graphs.php?report_id={$row->id}'>
									<img class='graphs' src='{$CFG->wwwroot}/blocks/ilp/pix/graphicon.jpg' alt='".get_string('editgraphs','block_ilp')."' title='".get_string('editgraphs','block_ilp')."' height='20' width='20' />
								 </a></div>";

        //set the edit report permissions link
        $data[] 		=	"<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_permissions.php?report_id={$row->id}'>
									<img class='permissions' src='".$OUTPUT->pix_url('i/roles')."' alt='".get_string('editpermissions','block_ilp')."' title='".get_string('editpermissions','block_ilp')."' />
								 </a></div>";

        //decide whether the report is enabled or disabled and set the image and link accordingly
        $title 			= 	(!empty($row->status)) ? get_string('disablereport','block_ilp')  : get_string('enablereport','block_ilp');

        $icon	= 	(!empty($row->status)) ? "hide" : "show";

        $data[] 		=	"<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_status.php?report_id={$row->id}'>
									<img class='status' src=".$OUTPUT->pix_url("/i/".$icon)." alt='".$title."' title='".$title."' />
							</a></div>";

        // set the send_to_vault field.
        $title_vault    = 	(empty($row->vault)) ? get_string('send_to_vault','block_ilp')  : get_string('bring_from_vault','block_ilp');

        $icon_vault	= 	(empty($row->vault)) ? $CFG->wwwroot."/blocks/ilp/pix/bring_from_vault.png" : $CFG->wwwroot."/blocks/ilp/pix/send_to_vault.png";
        $data[] = "<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_status_vault.php?report_id={$row->id}'>
									<img class='send_to_vault' src='". $icon_vault ."' alt='$title_vault' title='$title_vault' />
								 </a></div>";

        // set the clone report field.
        $data[] 			=	"<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/clone_report.php?report_id={$row->id}'>
									<img class='clone_report' src='".$OUTPUT->pix_url("/t/copy")."' alt='".get_string('clone_form', 'block_ilp')."' title='".get_string('clone_form', 'block_ilp')."' />
								 </a></div>";

        //set the delete field this is not enabled at the moment
        $data[] 			=	"<div align='center'><a href='{$CFG->wwwroot}/blocks/ilp/actions/delete_report.php?report_id={$row->id}'>
									<img class='delete' src='".$OUTPUT->pix_url("/t/delete")."' alt='".get_string('delete')."' title='".get_string('delete')."' />
								 </a></div>";


        $flextable->add_data($data);

    }
}


require_once($CFG->dirroot.'/blocks/ilp/views/view_reports_table.html');
