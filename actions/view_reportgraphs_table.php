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
$flextable->define_baseurl($CFG->wwwroot."/blocks/ilp/actions/edit_report_graphs.php?report_id={$report_id}");


//setup the array holding the column ids
$columns	=	array();
$columns[]	=	'label';
$columns[]	=	'type';
$columns[]	=	'editlabels';
$columns[]	=	'edit';
$columns[]	=	'delete';

//setup the array holding the header texts
$headers	=	array();
$headers[]	=	'';
$headers[]	=	get_string('type','block_ilp');
$headers[]	=	'';
$headers[]	=	'';
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

//get the data on fields to be used in the table
$reportgraphs		=	$dbc->get_report_graphs($report_id);
//$totalreportfields	=	count($reportgraphs);

if (!empty($reportgraphs)) {
	foreach ($reportgraphs as $row) {
		$data = array();
		
		$data[] 		=	$row->name;
		
		$plugin 		=	$dbc->get_graph_plugin_by_id($row->plugin_id);
				
		//use the plugin name param to get the type field  
		$plugintype		=	$plugin->name."_type";
				
		$data[] 		=	get_string($plugintype,'block_ilp');


		//set the edit field
		$data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_graph.php?reportgraph_id={$row->id}&report_id={$report_id}&plugin_id={$row->plugin_id}'>
									<img class='edit' src='".$OUTPUT->pix_url("/i/edit")."' alt='".get_string('edit')."' title='".get_string('edit')."' />
								 </a>";



		$data[] 			=	"<a href='{$CFG->wwwroot}/blocks/ilp/actions/delete_graph.php?reportgraph_id={$row->id}&report_id={$report_id}'>
									<img class='delete' src='".$OUTPUT->pix_url("/t/delete")."' alt='".get_string('delete')."' title='".get_string('delete')."' />
								 </a>";
		
		$flextable->add_data($data);
	}
}

require_once($CFG->dirroot.'/blocks/ilp/views/view_reportgraphs_table.html');

?>