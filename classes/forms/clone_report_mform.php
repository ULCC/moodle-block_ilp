<?php


class clone_report_mform extends ilp_moodleform {

		public		$report_id;
		public		$dbc;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($report_id=null) {

			global $CFG;

			$this->report_id	=	$report_id;
			$this->dbc			=	new ilp_db();
			$this->report = $this->dbc->get_report_by_id($report_id);
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/clone_report.php?report_id={$this->report_id}");
		}
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG;

        	$dbc = new ilp_db;

        	$mform =& $this->_form;

        	$mform->addElement('html', '<legend>' . get_string('clone_form', 'block_ilp') . ' ' . $this->report->name . '</legend>');

            // Current name

            $mform->addElement('text', 'currentname', get_string('current_form_name', 'block_ilp'));
            $mform->setDefault('currentname', $this->report->name);

            // New name

            $mform->addElement('text', 'newname', get_string('new_form_name', 'block_ilp'));
            $mform->setDefault('newname', $this->report->name . ' [copy]');

            // Send current to vault

            $mform->addElement('advcheckbox', 'current_to_vault', get_string('current_form_to_vault', 'block_ilp'), '', array('group' => 1), array(0, 1));
            $mform->setDefault('current_to_vault', 1);

            // Set new to visible
            $mform->addElement('advcheckbox', 'new_to_visible', get_string('new_form_to_visible', 'block_ilp'), '', array('group' => 1), array(0, 1));

            $mform->setDefault('current_to_vault', 0);

            $this->add_action_buttons(true, get_string('clone_form', 'block_ilp'));
		}



        function validation( $data, $files ){
            $dbc = new ilp_db;

            $errors = array();

            if ($report = $dbc->get_report_by_other('name', $data['currentname'])) {
                if ($report->id != $this->report_id) {
                    $errors['currentname'] = get_string('form_already_exists', 'block_ilp', $data['currentname']);
                }
            }

            if ($report = $dbc->get_report_by_other('name', $data['newname'])) {
                if ($report->id != $this->report_id) {
                    $errors['newname'] = get_string('form_already_exists', 'block_ilp', $data['newname']);
                }
            }

            if ($data['currentname'] == $data['newname']) {
                $errors['newname'] = get_string('new_name_diff_old_name', 'block_ilp');
            }

            if (!$data['currentname']) {
                $errors['currentname'] = get_string('form_name_not_blank', 'block_ilp');
            }

            if (!$data['newname']) {
                $errors['newname'] = get_string('form_name_not_blank', 'block_ilp');
            }
            return $errors;
        }

}
?>
