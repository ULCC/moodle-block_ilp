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

class edit_secondstatus_items_form extends moodleform {

    function definition() {
        global $CFG;
        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement('header', 'warningstatus_title', get_string('warningstatus_title', 'block_ilp'));

        $second_status_file = $CFG->dirroot . '/blocks/ilp/plugins/form_elements/ilp_element_plugin_warningstatus.php';
        require_once($second_status_file);
        $warning_status = new ilp_element_plugin_warningstatus();
        $optionlist = $warning_status->get_option_list(true);

        $attributes = array();
        foreach ($optionlist as $value => $name) {
            $mform->addElement('text', $value . '_name', get_string('warningstatus_namefor', 'block_ilp') . ' ' . $value, $attributes);
            $mform->setDefault($value . '_name', $name);
        }

        $this->add_action_buttons();

    }

}