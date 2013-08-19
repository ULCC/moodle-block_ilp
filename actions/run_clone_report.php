<?php 

/**
 * Allows the user to create and edit reports 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

require_once('../lib.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);

$currentname	= $PARSER->optional_param('currentname','',PARAM_TEXT);
$newname	= $PARSER->optional_param('newname','',PARAM_TEXT);
$current_to_vault	= $PARSER->optional_param('current_to_vault',0,PARAM_INT);
$new_to_visible	= $PARSER->optional_param('new_to_visible',0,PARAM_INT);

if (!$currentname || !$newname || $newname == $currentname) {
    die();
}

$PAGE->set_url($CFG->wwwroot . '/blocks/ilp/actions/_clone_report.php?report_id=' . $report_id);
// instantiate the db
$dbc = new ilp_db();

$currentreport = $dbc->get_report_by_id($report_id);

$report = clone $currentreport;
if ($current_to_vault || $currentname != $currentreport->name) {
    $currentreport->name = $currentname;
    if ($current_to_vault) {
        $currentreport->vault = 1;
    }
    $dbc->update_report($currentreport);
}

$report->name = $newname;

$report->status = (int) $new_to_visible;

$field_data = $dbc->get_report_fields($report_id);

unset($report->id);

$copy_id = $dbc->create_report($report);

if ($copy_id) {
    foreach ($field_data as $field) {
        $old_field_id = $field->id;
        unset($field->id);
        $field->report_id = $copy_id;
        $field_copy = $dbc->create_report_field($field);

        $plugin = $dbc->get_plugin_by_id($field->plugin_id);
        $specific_field_data = $dbc->get_plugin_record($plugin->tablename, $old_field_id);
        if ($specific_field_data) {
            $specific_field_data->reportfield_id = $field_copy;
            $old_field_data_id = $specific_field_data->id;
            unset($specific_field_data->id);
            $field_data_id = $dbc->special_insert($plugin->tablename, $specific_field_data);
            $dbc->add_old_items_to_new_field($old_field_data_id, $field_data_id, $plugin->tablename);
        }
    }

    redirect($CFG->wwwroot . '/blocks/ilp/actions/edit_report_configuration.php', get_string('new_cloned_form_created', 'block_ilp', $newname));
}

?>
