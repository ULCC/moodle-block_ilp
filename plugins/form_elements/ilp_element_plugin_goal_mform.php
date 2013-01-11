<?php

require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_element_plugin_mform.class.php');

class ilp_element_plugin_goal_mform extends ilp_element_plugin_mform
{
    protected function specific_definition($mform)
    {

//These are all for the field names to be looked up in mis; they are not the actual data
         foreach(ilp_element_plugin_goal::$fieldnames as $fieldname=>$required){
             $mform->addElement('text',
                                $fieldname,
                                get_string("ilp_element_plugin_goal_$fieldname", 'block_ilp'),
                                array('class' => 'form_input'));
             $mform->addRule($fieldname, null, 'maxlength', 100, 'client');

             if($required)
                 $mform->addRule($fieldname,get_string('required'),'required',null,'client');

         }

        $mform->addElement('select','tabletype',get_string('ilp_element_plugin_goal_table_type','block_ilp'),
                           array(ILP_MIS_TABLE => get_string('table','block_ilp'),
                                 ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure','block_ilp')));
    }

    protected function specific_validation($data)
    {
        return $this->errors;
    }

    protected function specific_process_data($data)
    {
        $plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record("block_ilp_plu_goal", $data->reportfield_id) : false;

        if (empty($plgrec)) {
            return $this->dbc->create_plugin_record("block_ilp_plu_goal", $data);
        } else {
            //get the old record from the elements plugins table
            $oldrecord = $this->dbc->get_form_element_by_reportfield("block_ilp_plu_goal", $data->reportfield_id);

            //create a new object to hold the updated data
            $pluginrecord = new stdClass();
            $pluginrecord->id = $oldrecord->id;

            foreach(ilp_element_plugin_goal::$fieldnames as $fieldname=>$required){
                $pluginrecord->$fieldname = $data->$fieldname;
            }

            $pluginrecord->tabletype=$data->tabletype;

            //update the plugin with the new data
            return $this->dbc->update_plugin_record("block_ilp_plu_goal", $pluginrecord);
        }
    }

    function definition_after_data()
    {

    }

}