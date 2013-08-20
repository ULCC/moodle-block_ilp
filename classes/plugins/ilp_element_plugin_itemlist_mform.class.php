<?php

require_once( $CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_element_plugin_mform.class.php' );

class ilp_element_plugin_itemlist_mform extends ilp_element_plugin_mform {

	public  $tablename;
	public  $items_tablename;
    public  $minoptions;

	
	function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {

        $this->minoptions   =   1; //default one option
		parent::__construct($report_id,$plugin_id,$creator_id,$reportfield_id=null);
        //remember to define $this->tablename and $this->items_tablename in the child class
	}

	 protected function specific_validation($data) {
	 	$data = (object) $data;
		$optionlist = array();
		if( in_array( 'optionlist' , array_keys( (array) $data ) ) ){
			//dd type needs to take values from admin form and writen them to items table
			$optionlist = ilp_element_plugin_itemlist::optlist2Array( $data->optionlist );
		}


         if (count($optionlist) < $this->minoptions && empty($data->reportfield_id))    {
                 $this->errors['optionlist']    =   get_string( 'ilp_element_plugin_error_minoptions' , 'block_ilp') . ":  $this->minoptions ";
         }



        $element_id = $this->dbc->get_element_id_from_reportfield_id( $this->tablename, $data->reportfield_id );
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record($this->tablename,$data->reportfield_id) : false;
	 	if (empty($plgrec)) {
			//new element
		} else {
			//existing element - check for user data
			$data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $data->reportfield_id );
			if( empty( $data_exists ) ){
				//no problem
			}
			else{
				//check for keys in $optionlist which clash with already existing keys in the element items
				foreach( $optionlist as $key=>$itemname ){
					if( $this->dbc->listelement_item_exists( $this->items_tablename, array( 'parent_id' => $element_id, 'value' => $key ) ) ){
						$this->errors['optionlist'] = get_string( 'ilp_element_plugin_error_item_key_exists', 'block_ilp' ) . ": $key";
					}
				}
			}
		}
		//check for duplicate keys in $optionlist
		$usedkeys = array();
		foreach( $optionlist as $key=>$itemname ){
			if( in_array( $key, $usedkeys ) ){
				$this->errors['optionlist'] = get_string( 'ilp_element_plugin_error_duplicate_key' , 'block_ilp' ) . ": $key";
			}
			else{
				$usedkeys[] = $key;
			}
		}
	 	return $this->errors;
	 }
	 
	protected function specific_definition($mform) {
		
		/**
		textarea element to contain the options the admin wishes to add to the user form
		admin will be instructed to insert value/label pairs in the following plaintext format:
		value1:label1\nvalue2:label2\nvalue3:label3
		or some such
		*/

		$mform->addElement(
			'textarea',
			'optionlist',
			get_string( 'ilp_element_plugin_dd_optionlist', 'block_ilp' ),
			array('class' => 'form_input')
	        );

		//admin must specify at least 1 option, with at least 1 character
        	$mform->addRule('optionlist', null, 'minlength', 1, 'client');

	}

	/*
	* take input from the management form and write the element info
	*/
	 protected function specific_process_data($data) {
		$optionlist = array();
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
            create_plugin_from_optionlist($optionlist, $itemrecord, $this->items_tablename, $this->dbc);
	 	} else {
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield($this->tablename,$data->reportfield_id);
			$data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $data->reportfield_id );
			$element_id = $this->dbc->get_element_id_from_reportfield_id( $this->tablename, $data->reportfield_id );
			//$itemrecord is a container for item data
			$itemrecord = new stdClass();	
			$itemrecord->parent_id = $element_id;

			if( empty( $data_exists ) ){
				//no user data - go ahead and delete existing items for this element, to be replaced by the submitted ones in $data
				$delstatus = $this->dbc->delete_element_listitems( $this->tablename, $data->reportfield_id );
					//if $delstatus false, there has been an error - alert the user
			} else {
				//user data has been submitted already - don't delete existing items, but add new ones if they are in $data
				//purge $optionlist of already existing item_keys
				//then it will be safe to write the items to the items table
				foreach( $optionlist as $key=>$itemname ){
					if( $this->dbc->listelement_item_exists( $this->items_tablename, array( 'parent_id' => $element_id, 'value' => $key ) ) ){
						//this should never happen, because it shouldn't have passed validation, but you never know
						unset( $optionlist[ $key ] );
						//alert the user
					}
				}
			}
			//now write fresh options from $data
            create_plugin_from_optionlist($optionlist, $itemrecord, $this->items_tablename, $this->dbc);
	
	 		//create a new object to hold the updated data
	 		$pluginrecord 			=	new stdClass();
	 		$pluginrecord->id		=	$oldrecord->id;
	 		$pluginrecord->optionlist	=	$data->optionlist;
			$pluginrecord->selecttype 	= 	ILP_OPTIONSINGLE;
	 			
	 		//update the plugin with the new data
	 		//return $this->dbc->update_plugin_record($this->tablename,$pluginrecord);
	 	}
	 }
}
?>
