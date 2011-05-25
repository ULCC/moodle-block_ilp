<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd_mform.php');

class ilp_element_plugin_status_mform  extends ilp_element_plugin_dd_mform {

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
	 protected function specific_process_data($data) {
		if( in_array( 'optionlist' , array_keys( (array) $data ) ) ){
			//dd type needs to take values from admin form and writen them to items table
			$optionlist = ilp_element_plugin_itemlist::optlist2Array( $data->optionlist );
		}
		//entries from data to go into $this->tablename and $this->items_tablename
	  	
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record($this->tablename,$data->reportfield_id) : false;
	 	
	 	if (empty($plgrec)) {
			//options for this dropdown need to be written to the items table
			//each option is one row
	 		$element_id = $this->dbc->create_plugin_record($this->tablename,$data);
		
			//$itemrecord is a container for item data
			$itemrecord = new stdClass();	
			$itemrecord->parent_id = $element_id;
			foreach( $optionlist as $key=>$itemname ){
				//one item row inserted here
				$itemrecord->item_value = $key;
				$itemrecord->item_name = $itemname;
	 			$this->dbc->create_plugin_record($this->items_tablename,$itemrecord);
		}
	 	} else {
			//@todo make it possible to add items_tablename rows here
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield($this->tablename,$data->reportfield_id);
	
	 		//create a new object to hold the updated data
	 		$pluginrecord 			=	new stdClass();
	 		$pluginrecord->id		=	$oldrecord->id;
	 		$pluginrecord->optionlist	=	$data->optionlist;
			$pluginrecord->selecttype 	= 	OPTIONSINGLE;
	 			
	 		//update the plugin with the new data
	 		//return $this->dbc->update_plugin_record($this->tablename,$pluginrecord);
	 	}
	 }
}
