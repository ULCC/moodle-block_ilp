<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');

class ilp_element_plugin_rdo_mform  extends ilp_element_plugin_mform{
	
	  	
	public $tablename;
	public $items_tablename;
	
	function __construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null) {
		parent::__construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null);
    		$this->tablename = "block_ilp_plu_rdo";
	    	$this->data_entry_tablename = "block_ilp_plu_rdo_ent";
		$this->items_tablename = "block_ilp_plu_rdo_items";
	}
	
	  protected function specific_definition($mform) {
		
		/**
		textarea element to contain the options the manager wishes to add to the user form
		manager will be instructed to insert value/label pairs in the following plaintext format:
		value1:label1\nvalue2:label2\nvalue3:label3
		or some such
		default option could be identified with '[default]' in the same line
		*/
		$html = <<<EOB
			<p>
				helllo
			</p>
EOB;
		//$mform->addElement( 'html', $html );
		
		$mform->addElement(
			'textarea',
			'optionlist',
			get_string( 'ilp_element_plugin_dd_optionlist', 'block_ilp' ),
			array('class' => 'form_input')
	        );

		//manager must specify at least 1 option, with at least 1 character
        	$mform->addRule('optionlist', null, 'minlength', 1, 'client');
		//@todo should we insist on a default option being chosen ?

	  }
	
	 protected function specific_validation($data) {
 	
	 	$data = (object) $data;
	 	return $this->errors;
	 }
	 
/*
	 protected function specific_process_data($data) {
	  	
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record("block_ilp_plu_rdo",$data->reportfield_id) : false;
	 	
	 	if (empty($plgrec)) {
	 		return $this->dbc->create_plugin_record("block_ilp_plu_rdo",$data);
	 	} else {
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield("block_ilp_plu_rdo",$data->reportfield_id);
	
	 		//create a new object to hold the updated data
	 		$pluginrecord 				=	new stdClass();
	 		$pluginrecord->id			=	$oldrecord->id;
	 		$pluginrecord->optionlist		=	$data->optionlist;
	 			
	 		//update the plugin with the new data
	 		return $this->dbc->update_plugin_record("block_ilp_plu_rdo",$pluginrecord);
	 	}
	 }
*/
	 protected function specific_process_data($data) {
		$optionlist = ilp_element_plugin_itemlist::optlist2Array( $data->optionlist );
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
	 
	 function definition_after_data() {
	 	
	 }
	
}
