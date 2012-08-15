<?php

    require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist_mform.class.php');
    require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist.class.php');

class ilp_element_plugin_checkbox_mform  extends ilp_element_plugin_itemlist_mform {



    function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {
        parent::__construct($report_id,$plugin_id,$creator_id,$reportfield_id=null);
        $this->tablename = "block_ilp_plu_chb";
        $this->data_entry_tablename = "block_ilp_plu_chb_ent";
        $this->items_tablename = "block_ilp_plu_chb_items";
    }


    protected function specific_definition($mform) {

        /**
        textarea element to contain the options the admin wishes to add to the user form
        admin will be instructed to insert value/label pairs in the following plaintext format:
        value1:label1\nvalue2:label2\nvalue3:label3
        or some such
        default option could be identified with '[default]' in the same line
         */

        $mform->addElement(
            'textarea',
            'optionlist',
            get_string( 'ilp_element_plugin_checkbox_optionlist', 'block_ilp' ),
            array('class' => 'form_input')
        );

        //admin must specify at least 1 option, with at least 1 character
        $mform->addRule('optionlist', null, 'minlength', 1, 'client');

        //added the below so that exisiting options can be seen
        $mform->addElement(
            'static',
            'existing_options',
            get_string( 'ilp_element_plugin_checkbox_existing_options' , 'block_ilp' ),
            ''
        );

    }

    function definition_after_data() {

    }










}
	