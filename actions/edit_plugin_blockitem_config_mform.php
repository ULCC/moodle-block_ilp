<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Joseph.Cape
 * Date: 16/07/13
 * Time: 15:25
 * To change this template use File | Settings | File Templates.
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class blockitem_config_form extends moodleform {

    function definition() {
        global $CFG;
        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement('header', 'currentstatus', get_string('config_currentstatus', 'block_ilp'));

        $attributes = array();

        $radioarray=array();
        $radioarray[] =& $mform->createElement('radio', 'currentstatus_yesno', '', get_string('yes'), 1, $attributes);
        $radioarray[] =& $mform->createElement('radio', 'currentstatus_yesno', '', get_string('no'), 0, $attributes);
        $mform->addGroup($radioarray, 'currentstatus_radio', '', array(' '), false);

        $mform->addElement('header', 'progressbar', get_string('config_progressbar', 'block_ilp'));

        $show_progressbar = get_config('block_ilp', 'show_progressbar');
        $attributes = array();

        $radioarray=array();
        $radioarray[] =& $mform->createElement('radio', 'progressbar_yesno', '', get_string('yes'), 1, $attributes);
        $radioarray[] =& $mform->createElement('radio', 'progressbar_yesno', '', get_string('no'), 0, $attributes);
        $mform->addGroup($radioarray, 'progressbar_radio', '', array(' '), false);

        $mform->addElement('header', 'userpicture', get_string('config_userpicture', 'block_ilp'));

        $attributes = array();

        $radioarray=array();
        $radioarray[] =& $mform->createElement('radio', 'userpicture_yesno', '', get_string('yes'), 1, $attributes);
        $radioarray[] =& $mform->createElement('radio', 'userpicture_yesno', '', get_string('no'), 0, $attributes);
        $mform->addGroup($radioarray, 'userpicture_radio', '', array(' '), false);

        $mform->addElement('header', 'linked_name', get_string('config_linked_name', 'block_ilp'));

        $attributes = array();

        $radioarray=array();
        $radioarray[] =& $mform->createElement('radio', 'linked_name_yesno', '', get_string('yes'), 1, $attributes);
        $radioarray[] =& $mform->createElement('radio', 'linked_name_yesno', '', get_string('no'), 0, $attributes);
        $mform->addGroup($radioarray, 'linked_name_radio', '', array(' '), false);

        $mform->addElement('header', 'attpunct', get_string('config_attendancepunctuality', 'block_ilp'));

        $attributes = array();

        $radioarray=array();
        $radioarray[] =& $mform->createElement('radio', 'attendancepunctuality_yesno', '', get_string('yes'), 1, $attributes);
        $radioarray[] =& $mform->createElement('radio', 'attendancepunctuality_yesno', '', get_string('no'), 0, $attributes);
        $mform->addGroup($radioarray, 'attendancepunctuality_radio', '', array(' '), false);

        $enabled_mis_plugins = array();

        require_once($CFG->dirroot . '/blocks/ilp/classes/database/ilp_db.php');

        $dbc = new ilp_db();
        $mis_plugins = ilp_records_to_menu($dbc->get_mis_plugins(), 'id', 'name');
        foreach ($mis_plugins as $plugin_file) {
            if (get_config('block_ilp', $plugin_file . '_pluginstatus') == ILP_ENABLED) {
                $enabled_mis_plugins[$plugin_file] = $plugin_file;
            }
        }

        $mform->addElement('select', 'attendancepunctuality_mis_plugin', get_string('config_attendancepunctuality_misplugin', 'block_ilp'), $enabled_mis_plugins, $attributes);

        $mform->setDefault('currentstatus_yesno', (int) get_config('block_ilp', 'show_current_status'));
        $mform->setDefault('progressbar_yesno', (int) get_config('block_ilp', 'show_progressbar'));
        $mform->setDefault('linked_name_yesno', (int) get_config('block_ilp', 'show_linked_name'));
        $mform->setDefault('userpicture_yesno', (int) get_config('block_ilp', 'show_userpicture'));
        $mform->setDefault('attendancepunctuality_yesno', (int) get_config('block_ilp', 'show_attendancepunctuality'));
        $mform->setDefault('attendancepunctuality_mis_plugin', get_config('block_ilp', 'show_attendancepunctuality_mis_plugin'));

        $this->add_action_buttons();

    }

}