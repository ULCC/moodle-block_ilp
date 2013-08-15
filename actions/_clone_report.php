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

require_once('../configpath.php');

global $USER, $CFG, $SESSION, $PARSER, $PAGE;

//include any neccessary files

// Meta includes
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');

//if set get the id of the report to be edited
$report_id	= $PARSER->optional_param('report_id',NULL,PARAM_INT);

$PAGE->set_url($CFG->wwwroot . '/blocks/ilp/actions/_clone_report.php?report_id=' . $report_id);
// instantiate the db
$dbc = new ilp_db();

$report = $dbc->get_report_by_id($report_id);

$report->name .= ' [copy]';

$field_data = $dbc->get_report_fields($report_id);

unset($report->id);

$copy_id = $dbc->create_report($report);

foreach ($field_data as $field) {
    $old_field_id = $field->id;
    unset($field->id);
    $field->report_id = $copy_id;
    $field_copy = $dbc->create_report_field($field);

    $plugin = $dbc->get_plugin_by_id($field->plugin_id);
    $specific_field_data = $dbc->get_plugin_record($plugin->tablename, $old_field_id);
    if ($specific_field_data) {
        $specific_field_data->reportfield_id = $field_copy;
        unset($specific_field_data->id);
        $dbc->special_insert($plugin->tablename, $specific_field_data);
    }
}


?>
