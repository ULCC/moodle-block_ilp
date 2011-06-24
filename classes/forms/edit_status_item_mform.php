<?php 

/**
 * This class makes the form that is used to create reports 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


class edit_status_item_mform extends ilp_moodleform {

		public		$report_id;
		public		$dbc;
		public		$tablename;
		public		$items_tablename;
		public		$data_entry_tablename;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($report_id=null) {

			global $CFG;

			$this->report_id	=	$report_id;
			$this->dbc			=	new ilp_db();
			$this->tablename = "block_ilp_plu_sts";
			$this->items_tablename = "block_ilp_plu_sts_items";
			$this->data_entry_tablename = "block_ilp_plu_sts_ent";
			
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_status_items.php?report_id={$this->report_id}");
		}
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG;

	        	$dbc = new ilp_db;
	
	        	$mform =& $this->_form;
        	
       		 	$fieldsettitle = get_string('edit_status_items', 'block_ilp');
        	
       		 	//create a new fieldset
       		 	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
       		     	$mform->addElement('html', '<legend class="ftoggler">'.$fieldsettitle.'</legend>');

        	
       		 	$mform->addElement( 'hidden', 'id', ILP_DEFAULT_USERSTATUS_RECORD );
       		 	$mform->setType('id', PARAM_INT);
        	
       		 	$mform->addElement('hidden', 'creator_id', $USER->id);
       		 	$mform->setType('creator_id', PARAM_INT);

	        
//instantiate status class
			require_once( "{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_status.php" );
			$status = new ilp_element_plugin_status();
//call the definition
			$status->config_specific_definition( $mform );
		
			        
		        $buttonarray[] = $mform->createElement('submit', 'saveanddisplaybutton', get_string('submit'));
		        $buttonarray[] = &$mform->createElement('cancel');
		        
		        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
	        
		        //close the fieldset
		        $mform->addElement('html', '</fieldset>');
		}
		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			
			if (empty($data->id)) {
				//we shouldn't be here
	        	} else {
					//$status = new ilp_element_plugin_status();
					$this->errors = array();
					if( $this->specific_validation( $data ) ){
						//valid input
						//rewrite the options
						$this->specific_process_data( $data );
					}
					else{
						var_crap( $this->errors, 'Validation Errors' );
					}
	        	}
   	    		return $data->id;
	    	}

		function specific_process_data( $data ){
			//if we are here, we can assume $data is valid
			$optionlist = array();
			if( in_array( 'optionlist' , array_keys( (array) $data ) ) ){
				//dd type needs to take values from admin form and write them to items table
				$optionlist = ilp_element_plugin_itemlist::optlist2Array( $data->optionlist );
			}
			//check for existing user data - if there is any then we can't delete any status options
			$data_exists = $this->dbc->listelement_item_exists( $this->data_entry_tablename, array( 'parent_id' => ILP_DEFAULT_USERSTATUS_RECORD ) );

		        $sep = "\n";
		        $keysep = ":";
			//entries from data to go into $this->tablename and $this->items_tablename
	
		        $gradekeylist = array(
	       		     'pass', 'fail'
		        );
		        foreach( $gradekeylist as $key ){
	       		     $v = $key . '_list';
	       		     $$v = explode( $sep, $data->$key );
	       		     //deal with pesky whitespace
	       		     foreach( $$v as &$entry ){
	       		         $entry = trim( $entry );
	       		         $entryparts = explode( $keysep , $entry );
	       		         if( 1 < count( $entryparts ) ){
	       		             //manager has copied a whole key:value string into the pass or fail textarea
	       		             //so throw away the key 
	       		             $entry = $entryparts[1];
	       		         }
	       		     }
	       		 }
		        //we now have 2 lists: $pass_list and $fail_list 
	  	
			$element_id = ILP_DEFAULT_USERSTATUS_RECORD;
	 		$plgrec = $this->dbc->get_form_element_data( $this->tablename, $element_id );
			//$itemrecord is a container for item data
			$itemrecord = new stdClass();	
			$itemrecord->parent_id = $element_id;

			if( empty( $data_exists ) ){
				//no user data - go ahead and delete existing items for this element, to be replaced by the submitted ones in $data
				$delstatus = $this->dbc->delete_element_listitems_by_parent_id( $this->tablename, $element_id );
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
			foreach( $optionlist as $key=>$itemname ){
				if( trim( $key ) ){
					//one item row inserted here
					$itemrecord->value = $key;
					$itemrecord->name = $itemname;
	           		$itemrecord->passfail = $this->deducePassFailFromLists( array( $itemname, $key ), $fail_list, $pass_list );
			 		$this->dbc->create_plugin_record($this->items_tablename,$itemrecord);
				}
			}
	
            //that's dealt with the fresh options submitted
            //but we still need to re-assign pass and fail to the existing items, should they have changed
            foreach( $this->dbc->listelement_item_exists( $this->items_tablename, array() ) as $obj ){
                //actually this will set all the passfail values, so if we keep this block, we don't need the  line above involving $this->deducePassFailFromLists
                $old_passfail = $obj->passfail;
                $new_passfail = $this->deducePassFailFromLists( array( $obj->name, $obj->value ), $fail_list, $pass_list );               
                if( $old_passfail != $new_passfail ){
                    $obj->passfail = $new_passfail;
                    $this->dbc->update_plugin_record( $this->items_tablename, $obj );
                }
            }
			
		}
		
		//adapted from ilp_element_plugin_state_mform
		function specific_validation( $data ){
			$valid = true;
			$optionlist = array();

			if( in_array( 'optionlist' , array_keys( (array) $data ) ) ){
				//$optionlist = ilp_element_plugin_itemlist::optlist2Array( $data[ 'optionlist' ] );
				$optionlist = ilp_element_plugin_itemlist::optlist2Array( $data->optionlist );
			}
            //optionlist is now the options just submitted from the mform, but if user_data exists, this is not complete:
            //we must merge it with the options from the items table
			$data_exists = $this->dbc->listelement_item_exists( $this->data_entry_tablename, array( 'parent_id' => ILP_DEFAULT_USERSTATUS_RECORD ) );
            if( $data_exists ){
			    //$status = new ilp_element_plugin_status();
                $existing_options = $this->dbc->listelement_item_exists( $this->items_tablename, array( 'parent_id' => ILP_DEFAULT_USERSTATUS_RECORD ) ) ;
                foreach( $existing_options as $obj ){
                    $optionlist[ $obj->value ] = $obj->name;
                }
            }

		        //all contents of $data->fail and $data->pass must match valid keys or values in $optionlist
		        $sep = "\n";
		        $keysep = ":";
		        $fail_item_list = explode( $sep, $data->fail );
		        $pass_item_list = explode( $sep, $data->pass );
		        foreach( array( $fail_item_list, $pass_item_list ) as $item_list ){
		            foreach( $item_list as $submitted_item ){
		                if( trim( $submitted_item ) && !$this->is_valid_item( $submitted_item , $optionlist, $keysep ) ){
		                    $this->errors[] = get_string( 'ilp_element_plugin_error_not_valid_item' , 'block_ilp' ) . ": <em>$submitted_item</em>";
        				    $valid = false;
		                }
		            }
		        }
			return $valid;
		}
		
		//adapted from ilp_element_plugin_state_mform
		protected function is_valid_item( $item, $item_list, $keysep=":" ){
		        $item = trim( $item );
		        $itemparts = explode( $keysep, $item );
		        foreach( $itemparts  as $item ){
		            //$item should be either a key or value of $item_list
		            if( in_array( $item, array_values( $item_list ) ) || in_array( $item, array_keys( $item_list ) ) ){
		                return true;
		            }
		        }
		        return false;
		}
		
    /*
    * copied from ilp_element_plugin_state_mform
    * the manager has entered the states in the fail and pass textareas on the mform
    * the values in those textareas have been made into arrays and sent to this function, to be categorised as fail, pass or unset 
    * @param array $statelist - list of values - should be a key and value from the state selector, so that if either of them matches, we can return a pass or fail value
    * @param array $fail_list - list of values to be classified as fail
    * @param array $pass_list - list of values to be classified as pass
    * @param array $unset_list - not really necessary ... if nothing matches, we default to unset anyway
    */
    protected function deducePassFailFromLists( $state_list, $fail_list, $pass_list, $keysep=':' ){
        foreach( $state_list as $grade ){
	        $grade = trim( $grade );
	        if( in_array( $grade, $fail_list ) ){
	            return ILP_PASSFAIL_FAIL;
	        }
	        if( in_array( $grade, $pass_list ) ){
	            return ILP_PASSFAIL_PASS;
	        }
        }
        return ILP_PASSFAIL_UNSET;
    }
		/**
     	 * TODO comment this
     	 */
    	function definition_after_data() {
    		
    	}
	
}

	
?>
