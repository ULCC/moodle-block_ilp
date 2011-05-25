<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');

class ilp_element_plugin_category_mform  extends ilp_element_plugin_mform {

	public $tablename;
	
	function __construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null) {
		parent::__construct($report_id,$plugin_id,$course_id,$creator_id,$reportfield_id=null);
		$this->tablename = "block_ilp_plu_cat";
		$this->items_tablename = "block_ilp_plu_cat_items";
	}
	protected function specific_definition($mform) {
		//no action necessary
	}
	 protected function specific_validation($data) {
 	
	 	$data = (object) $data;
	 	return $this->errors;
	 }
	 private function get_cat_option_list(){
		return array(
			'catopt1' => 'catopt1',
			'catopt2' => 'catopt2',
			'catopt3' => 'catopt3'
		);
	}
	 protected function specific_process_data($data) {
		$optionlist = $this->get_cat_option_list();	//@todo replace this with function to get options out of items table
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
