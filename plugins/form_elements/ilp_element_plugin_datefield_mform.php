<?php

    require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_mform.class.php');

class ilp_element_plugin_datefield_mform  extends ilp_element_plugin_mform {

   /*
    * Function to specify extra form elements appearing in report settings
    */
    protected function specific_definition($mform) {

        // deadline/review date
        $optionlist = array(
            ILP_DATEFIELD_DATE          => get_string('ilp_element_plugin_datefield_date', 'block_ilp'),
            ILP_DATEFIELD_DEADLINE      => get_string('ilp_element_plugin_datefield_deadline', 'block_ilp'),
            ILP_DATEFIELD_REVIEWDATE    => get_string('ilp_element_plugin_datefield_reviewdate', 'block_ilp'),
         );

       $mform->addElement(
            'select',
            'datetype',
            get_string('ilp_element_plugin_datefield_datetype', 'block_ilp'),
            $optionlist
        );

       // $mform->addRule('datetype', null, 'required', null, 'client');
        $mform->setType('datetype', PARAM_INT);

        //disable choice if one of datetype selected
      $mform->disabledIf('datetype', 'reportfield_id', 'neq', '');



        $calendargroup[] =&$mform->createElement(
               'advcheckbox',
               'scalendar',
                '',
               get_string('ilp_element_plugin_datefield_scalendar', 'block_ilp'), array('group' => 1), array(0, 1)
        );

        $calendargroup[] =&$mform->createElement(
				'advcheckbox',
				'ucalendar',
				'',
				get_string('ilp_element_plugin_datefield_ucalendar', 'block_ilp'), array('group' => 1), array(0, 1)
        );

        $mform->addGroup($calendargroup, 'calendargroup', get_string('ilp_element_plugin_datefield_calendar', 'block_ilp'), ' ', false);

        //disable calendar choice if 'Date' datetype selected
       $mform->disabledIf('calendargroup', 'datetype', 'eq', 0);

        //reminder checkbox (0-selected, 1-not selected)
        $mform->addElement(
            'advcheckbox',
            'reminder',
            get_string('ilp_element_plugin_datefield_reminder', 'block_ilp'),
            get_string('yes'), array('group' => 1), array(0, 1)
        );
        //disable reminder if 'Date' datetype selected
        $mform->disabledIf('reminder', 'datetype', 'eq', 0);


    }


/*
 * Function to specify validations for extra fields
 */
    protected function specific_validation($data) {
        $data = (object) $data;
        return $this->errors;
    }


    protected function specific_process_data($data) {

        $plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record("block_ilp_plu_datf",$data->reportfield_id) : false;

     
        if (empty($plgrec)) {
            return $this->dbc->create_plugin_record("block_ilp_plu_datf",$data);
        } else {
            //get the old record from the elements plugins table
            $oldrecord				=	$this->dbc->get_form_element_by_reportfield("block_ilp_plu_datf",$data->reportfield_id);

            //create a new object to hold the updated data
            $pluginrecord 				=	new stdClass();
            $pluginrecord->id			=	$oldrecord->id;
            $pluginrecord->datetype		=	$data->datetype;
            $pluginrecord->scalendar    =   $data->scalendar;
            $pluginrecord->ucalendar    =   $data->ucalendar;
            $pluginrecord->reminder		=	$data->reminder;

            //update the plugin with the new data
            return $this->dbc->update_plugin_record("block_ilp_plu_datf",$pluginrecord);
        }
    }








}