<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform_itemlist.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');

class ilp_element_plugin_status_mform  extends ilp_element_plugin_mform_itemlist {

	public $tablename;
	public $items_tablename;
	
	function __construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null) {
		parent::__construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null);
		$this->tablename = "block_ilp_plu_sts";
		$this->items_tablename = "block_ilp_plu_sts_items";
	}
	protected function specific_definition($mform) {
		
		/**
		textarea element to contain the options the manager wishes to add to the user form
		manager will be instructed to insert value/label pairs in the following plaintext format:
		value1:label1\nvalue2:label2\nvalue3:label3
		or some such
		*/

		$mform->addElement(
			'textarea',
			'optionlist',
			get_string( 'ilp_element_plugin_dd_optionlist', 'block_ilp' ),
			array('class' => 'form_input')
	        );

		//manager must specify at least 1 option, with at least 1 character
        	$mform->addRule('optionlist', null, 'minlength', 1, 'client');

	}
}
