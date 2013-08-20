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
       		 	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset"><div>');
       		    $mform->addElement('html', '<legend >'.$fieldsettitle.'</legend>');

        	
       		 	$mform->addElement( 'hidden', 'id', ILP_DEFAULT_USERSTATUS_RECORD );
       		 	$mform->setType('id', PARAM_INT);
        	
       		 	$mform->addElement('hidden', 'creator_id', $USER->id);
       		 	$mform->setType('creator_id', PARAM_INT);

	        
				//instantiate status class
				require_once( "{$CFG->dirroot}/blocks/ilp/plugins/form_elements/ilp_element_plugin_status.php" );
				$status = new ilp_element_plugin_status();
				//call the definition
				$status->config_specific_definition( $mform );
		
			        
		        $buttonarray[] = $mform->createElement('submit', 'saveanddisplaybutton', get_string('submit'));
		        $buttonarray[] = &$mform->createElement('cancel');
		        
		        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
	        
		        //close the fieldset
		        $mform->addElement('html', '</div></fieldset>');
		}
		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			
			if (empty($data->id)) {
            //we shouldn't be here
            } else {
                $this->errors = array();
                if( $this->specific_validation( $data ) ){
                    //valid input
                    //rewrite the options
                    $this->specific_process_data( $data );
                }
                else{

                }
            }
            return $data->id;
        }
        
        /*
        * @param object $item
        * @return object
        */
        protected function item_record_exists( $item ){
            //see if $item->value is already in items table
            $rst = $this->dbc->listelement_item_exists( $this->items_tablename, array( 'value' => $item->value ) );
            return array_shift( $rst );
        }

		function specific_process_data( $data ){

            global $CFG, $DB;
            require_once($CFG->dirroot.'/lib/filestorage/file_storage.php');
            require_once($CFG->dirroot.'/lib/filelib.php');
            $context = context_system::instance();
			//if we are here, we can assume $data is valid
			$optionlist = array();
			if( in_array( 'optionlist' , array_keys( (array) $data ) ) ){
				//dd type needs to take values from admin form and write them to items table
				$optionlist = ilp_element_plugin_itemlist::optlist2Array( $data->optionlist );
			}
			//check for existing user data - if there is any then we can't delete any status options
			//$data_exists = $this->dbc->listelement_item_exists( $this->data_entry_tablename, array( 'parent_id' => ILP_DEFAULT_USERSTATUS_RECORD ) );

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
                         //admin has copied a whole key:value string into the pass or fail textarea
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

			
			//now write fresh options from $data
            //keep a list of ids to be protected from deletion later
            $active_id_list = array();
			foreach( $optionlist as $key=>$itemname ){
				if( trim( $key ) ){
					//one item row inserted here
                    $itemrecord = new stdClass();
					$itemrecord->value = $key;
                    if( is_array( $itemname ) ){
					    $itemrecord->name = $itemname[0];
					    $itemrecord->hexcolour = $itemname[1];
                    }
                    else{
					    $itemrecord->name = $itemname;
                    }
	           		$itemrecord->passfail = $this->deducePassFailFromLists( array( $itemname, $key ), $fail_list, $pass_list );
                    if( $existing_record = $this->item_record_exists( $itemrecord ) ){
                        //update the record
                        $itemrecord->id = $existing_record->id;
                        $this->dbc->update_plugin_record( $this->items_tablename, $itemrecord );
                        $active_id_list[] = $existing_record->id;
                    }
                    else{
                        $itemrecord->parent_id = ILP_DEFAULT_USERSTATUS_RECORD;
			 		    $active_id_list[] = $this->dbc->create_plugin_record($this->items_tablename,$itemrecord);
                    }
				}
			}

            //that's dealt with the fresh options submitted
            //but we still need to re-assign pass and fail to the existing items, should they have changed
            foreach( $this->dbc->listelement_item_exists( $this->items_tablename, array() ) as $obj ){
                //below two lines is liable for saving icon files
                $icon_options = array('subdirs'=>0, 'maxbytes'=>$CFG->userquota, 'maxfiles'=>1, 'accepted_types'=>array('*.ico', '*.png', '*.jpg', '*.gif', '*.jpeg'));
                file_save_draft_area_files($data->{$obj->id.'_files_filemanager'}, $context->id, 'block_ilp', 'icon', $obj->id, $icon_options);

                //if an element has been submitted with blank name and value, delete existing record
                $itemid = $obj->id;
                $labelkey = "itemname_$itemid";
                $valuekey = "itemvalue_$itemid";
                if( isset( $data->$labelkey ) && empty( $data->$labelkey ) && isset( $data->$valuekey ) && empty( $data->$valuekey ) ){
                    //this block should only apply to previously written data - hence the issets in the condition
                    //form submit is asking for this item to be deleted ... first check if there is child data
                    $children = $DB->get_records( 'block_ilp_user_status', array( 'parent_id' => $itemid ) );
                    if( 0 == count($children) ){
                        //delete the record
                        $DB->delete_records( $this->items_tablename, array( 'id' => $itemid ) );
                    }
                }


                $update = false;    //set to true if data from form make it necessary to update the item record
                //actually this will set all the passfail values, so if we keep this block, we don't need the  line above involving $this->deducePassFailFromLists
                $old_passfail = $obj->passfail;
                $new_passfail = $this->deducePassFailFromLists( array( $obj->name, $obj->value ), $fail_list, $pass_list );               
                if( $old_passfail != $new_passfail ){
                    $obj->passfail = $new_passfail;
                    $update = true;
                }

                //keys correspond to field names, values correspond to element names from the form
                $editable_fields = array(
                    'value'         => 'itemvalue_' . $obj->id,
                    'hexcolour'     => 'itemhexcolour_' . $obj->id,
                    'name'          => 'itemname_' . $obj->id,
                    'icon'          => $obj->id . '_file_filemanager',
                    'display_option'=> 'display_option_' . $obj->id,
                    'description'   => 'description_' . $obj->id,
                    'bg_colour'     => 'bg_colour_' . $obj->id,
                );
                foreach( $editable_fields as $fieldname=>$form_element_name ){
                    if($fieldname == 'icon'){
                        $file_name = $DB->get_field_sql("SELECT filename FROM {files} where component = 'ilp' and filearea='icon' and itemid=$obj->id and filesize != 0");
                        if($file_name){
                            $newvalue = '';//???? get the icon file name
                            $obj->$fieldname = 'icon';
                            $update = true;
                        }else {
                            $obj->$fieldname = 'icon';
                            $update = true;
                        }
                    }else {
                        $oldvalue = trim( $obj->$fieldname );
                        if( isset( $data->$form_element_name ) ){
                            $newvalue = trim( $data->$form_element_name );
                            if( $oldvalue != $newvalue ){
                                $obj->$fieldname = $newvalue;
                                $update = true;
                            }
                        }
                    }
                }
                if( $update ){
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
            //optionlist is now the options just submitted from the mform, but this is not complete:
            //we must merge it with the options from the items table
			//$data_exists = $this->dbc->listelement_item_exists( $this->data_entry_tablename, array( 'parent_id' => ILP_DEFAULT_USERSTATUS_RECORD ) );
        
			    //$status = new ilp_element_plugin_status();
                $existing_options = $this->dbc->listelement_item_exists( $this->items_tablename, array( 'parent_id' => ILP_DEFAULT_USERSTATUS_RECORD ) ) ;
                foreach( $existing_options as $obj ){
                    //$optionlist[ $obj->value ] = $obj->name;
                    $optionlist[ $obj->value ] = $obj->name;
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
    * the admin has entered the states in the fail and pass textareas on the mform
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
	            return ILP_STATE_FAIL;
	        }
	        if( in_array( $grade, $pass_list ) ){
	            return ILP_STATE_PASS;
	        }
        }
        return ILP_STATE_UNSET;
    }
    /**
     * TODO comment this
     */
    function definition_after_data() {

    }
	
}

	
?>
