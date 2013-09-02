<?php
if (!defined('MOODLE_INTERNAL')) {
    // this must be included from a Moodle page
    die('Direct access to this script is forbidden.');
}


//include the ilp ajax table class
require_once ($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_tablelib.class.php');

//create the field table

//instantiate the flextable table class
$flextable = new ilp_flexible_table("report_id{$report_id}user_id".$USER->id);

//define the base url that the table will return to
$flextable->define_baseurl($CFG->wwwroot."/blocks/ilp/actions/edit_prompt.php?report_id={$report_id}");


//setup the array holding the column ids
$columns	=	array();
$columns[]	=	'label';
$columns[]	=	'type';
$columns[]	=	'moveup';
$columns[]	=	'movedown';
$columns[]	=	'edit';
$columns[]	=	'summary';
$columns[]	=	'required';
$columns[]	=	'delete';

//setup the array holding the header texts
$headers	=	array();
$headers[]	=	get_string('label','block_ilp');
$headers[]	=	get_string('type','block_ilp');
$headers[]	=	get_string('moveup','block_ilp');
$headers[]	=	get_string('movedown','block_ilp');
$headers[]	=	get_string('edit','block_ilp');
$headers[]	=	get_string('required','block_ilp');
$headers[]	=	get_string('summary','block_ilp');
$headers[]	=	get_string('delete','block_ilp');

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

//get the data on fields to be used in the table
$reportfields		=	$dbc->get_report_fields_by_position($report_id);
$min_position = $dbc->upperlower_report_field_position($report_id, 'MIN');
$max_position = $dbc->upperlower_report_field_position($report_id, 'MAX');
$totalreportfields	=	count($reportfields);

if (!empty($reportfields)) {
	foreach ($reportfields as $row) {
		$data = array();
		
		$data[] 		=	$row->label;
		
		$plugin 		=	$dbc->get_form_element_plugin($row->plugin_id);
				
		//use the plugin name param to get the type field  
		$plugintype		=	$plugin->name."_type";
				
		$data[] 		=	get_string($plugintype,'block_ilp');
		
		if ($row->position != $min_position) {
			//if the field is in any position except 1 it needs a up icon 
			$title 	=	get_string('moveup','block_ilp');
			$icon	=	$OUTPUT->pix_url("/t/up");
			$movetype	=	"up";
			
			$data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/move_field.php?reportfield_id={$row->id}&report_id={$report_id}&move=".ILP_MOVE_UP."&position={$row->position}'>
									<img class='move' src='{$icon}' alt='{$title}' title='{$title}' />
								 	</a>";
		} else {
			$data[] 	=	"";
		}
		
		if ($row->position != $max_position) {
			//if the field is in any position except last it needs a down icon
			$title 	=	get_string('movedown','block_ilp');
			$icon	=	$OUTPUT->pix_url("/t/down");
			$movetype	=	"down";
			
			$data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/move_field.php?reportfield_id={$row->id}&report_id={$report_id}&move=".ILP_MOVE_DOWN."&position={$row->position}'>
									<img class='move' src='{$icon}' alt='{$title}' title='{$title}' />
								 	</a>";
		} else {
			$data[] 	=	""; 
		}
	
	
		//set the edit field
		$data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_field.php?reportfield_id={$row->id}&report_id={$report_id}&plugin_id={$row->plugin_id}'>
									<img class='edit' src='".$OUTPUT->pix_url("/i/edit")."' alt='".get_string('edit')."' title='".get_string('edit')."' />
								 </a>";
		
		//set the required field
        $title 	= 	(!empty($row->req)) ? get_string('required','block_ilp') : get_string('notrequired','block_ilp');
        $icon	= 	$CFG->wwwroot."/blocks/ilp/pix/icons/";
        $icon	.= 	(!empty($row->req)) ? "required.gif" : "notrequired.gif";

        $data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_field_summary_or_req.php?required_setting=1&reportfield_id={$row->id}&report_id={$report_id}'>
									<img class='required' src='{$icon}' alt='{$title}' title='{$title}' />
								</a>";
        //set the summary row
        $title 	= 	(!empty($row->summary)) ? get_string('insummary','block_ilp') : get_string('notinsummary','block_ilp');
        $icon	= 	$CFG->wwwroot."/blocks/ilp/pix/icons/";
        $icon	.= 	(!empty($row->summary)) ? "summary.png" : "notinsummary.png";

        $data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_field_summary_or_req.php?reportfield_id={$row->id}&report_id={$report_id}'>
									<img class='required' src='{$icon}' alt='{$title}' title='{$title}' height='16' width='16'/>
								</a>";


        $data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/delete_field.php?reportfield_id={$row->id}&report_id={$report_id}'>
									<img class='delete' src='".$OUTPUT->pix_url("/t/delete")."' alt='".get_string('delete')."' title='".get_string('delete')."' />
								 </a>";
		
		$flextable->add_data($data);
		
	}
}

require_once($CFG->dirroot.'/blocks/ilp/views/view_reportfields_table.html');

?>