<?php

//require_once($CFG->dirroot.'/blocks/ilp/plugins/form_elements/ilp_element_plugin_dd.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist.class.php');

class ilp_element_plugin_state extends ilp_element_plugin_itemlist{

	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	public $selecttype;
	
    /**
     * Constructor
     */
    function __construct() {
    	
    	
    	$this->tablename = "block_ilp_plu_ste";
    	$this->data_entry_tablename = "block_ilp_plu_ste_ent";
		$this->items_tablename = "block_ilp_plu_ste_items";
		$this->selecttype = ILP_OPTIONSINGLE;	
		
		parent::__construct();
    }

    /*
    * should not be able to add a state selector if there is already one one the form
    */
    public function can_add( $report_id ){
        return !$this->dbc->element_type_exists( $report_id, $this->tablename );
    }

    protected function get_creator_id( $entry ){
        return $entry->creator_id;
    }

    static function language_strings(&$string) {
        $string['ilp_element_plugin_state'] 			= 'Select';
        $string['ilp_element_plugin_state_type'] 		= 'State select';
        $string['ilp_element_plugin_state_description'] 	= 'A state selector';
		$string[ 'ilp_element_plugin_state_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_state_single' ] 		= 'Single select';
		$string[ 'ilp_element_plugin_state_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_state_typelabel' ] 	= 'Select type (single/multi)';
		$string[ 'ilp_element_plugin_state_fail' ] 	        = 'fail';
		$string[ 'ilp_element_plugin_state_pass' ] 	        = 'pass';
        $string[ 'ilp_element_plugin_state_unset' ] 	    = 'unset';
        $string[ 'ilp_element_plugin_state_notcounted' ] 	= 'not counted';
        $string[ 'ilp_element_plugin_error_not_valid_item' ]= 'Not a valid option';
        
        return $string;
    }
	

    /*
    * get options from the items table for this plugin, and concatenate them into a string
    * @param int $reportfield_id
    * @param string $sep
    * @param string $field - optional additional field to retrieve, along with value and name
    */
	protected function get_option_list_text( $reportfield_id , $sep="\n", $field=false ){
		$option_data = $this->get_option_list( $reportfield_id, $field );
		$optionlist = $option_data[ 'optlist' ];
		$rtn = '';
		if( !empty( $optionlist ) ){
			foreach( $optionlist as $key=>$value ){
				$rtn .= "$key:$value$sep";
			}
		}
		return array(
            'options' => $rtn,
            'pass' => implode( $sep, $option_data[ 'pass' ] ),
            'fail' => implode( $sep, $option_data[ 'fail' ] ),
            'notcounted' => implode( $sep, $option_data[ 'notcounted' ] )

        );
	}

    /*
    * read rows from item table and return them as array of key=>value
    * @param int $reportfield_id
    * @param string $field - the name of a extra field to read from items table: used by ilp_element_plugin_state
    */
	protected function get_option_list( $reportfield_id, $field = false, $useid = true ){

		$outlist        = array();
		$passlist       = array();
        $faillist       = array();
        $notcountlist   = array();

		if( $reportfield_id ){
			//get the list of options for this reportfield in the given table from the db 
			$objlist = $this->dbc->get_optionlist($reportfield_id , $this->tablename, $field );
			
			foreach( $objlist as $obj ){
				//place the name into an array with value as key
				$outlist[ $obj->value ] = $obj->name;
				
				//if the the name of the extra field is passfail then 
                if( 'passfail' == $field ){

                	//if the field value is fail add to fail list
                    if( ILP_STATE_FAIL == $obj->passfail ){
                        $faillist[] = $obj->name;
                    }

                    if( ILP_STATE_PASS == $obj->passfail ){
                        $passlist[] = $obj->name;
                    }

                    if( ILP_STATE_NOTCOUNTED == $obj->passfail ){
                        $notcountlist[] = $obj->name;
                    }
                }
			}
		}
		if( !count( $outlist ) ){
			//echo "no items in {$this->items_tablename}";
		}

		$adminvalues = array(
            'optlist' => $outlist,
            'pass' => $passlist,
            'fail' => $faillist,
            'notcounted' => $notcountlist
        );
        
        //we only need to return the admin values if the $field value
        //is not false (it should be set to passfail to get admin values)
        return (!empty($field)) ? $adminvalues : $outlist; 
	}

	/*
	* get the list options with which to populate the edit element for this list element
	*/
	public function return_data( &$reportfield ){
		$data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $reportfield->id );
		if( empty( $data_exists ) ){
			//if no, get options list
            $options_data = $this->get_option_list_text( $reportfield->id, "\n", 'passfail' );
			$reportfield->optionlist = $options_data[ 'options' ];
		}   else{
			$options_data = $this->get_option_list_text( $reportfield->id , '<br />', 'passfail' );
            $reportfield->existing_options = $options_data[ 'options' ];
            $reportfield->existing_options_hidden = $options_data[ 'options' ];
		}
        $reportfield->fail          = $options_data[ 'fail' ];
        $reportfield->pass          = $options_data[ 'pass' ];
        $reportfield->notcounted    = $options_data[ 'notcounted' ];
	}
	

	  /**
	  * places entry data formated for viewing for the report field given  into the  
	  * entryobj given by the user. By default the entry_data function is called to provide
	  * the data. Any child class which needs to have its data formated should override this
	  * function. 
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
      * @param bool $selectenabled should a select box be returned if the user is the creator
	  */
	  public function view_data( $reportfield_id,$entry_id,&$entryobj,$selectenabled=true){
	  		global $CFG,$OUTPUT,$USER;
	  	
	  		$fieldname	=	$reportfield_id."_field";
	  		
	 		$pluginentry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id,true);
	 		
	 		$entry			=	$this->dbc->get_entry_by_id($entry_id);
	 		
			if (!empty($pluginentry)) {
		 		$fielddata	=	array();
		 		$comma	= "";
		 		
		 		$objlist = $this->dbc->get_optionlist($reportfield_id , $this->tablename, 'passfail');
		 		
		 		$optionslist	=	array();
		 		
		 		$failedoptions		=	array();
		 		$achievedoptions	=	array();
		 		
		 		if (!empty($objlist)) {
		 			foreach( $objlist as $obj ){
						//place the name into an array with value as key
						$optionslist[ $obj->id ] = $obj->name;
						
						if ($obj->passfail == 1) {
							$failedoptions[]	=	$obj->id;		
						} else if ($obj->passfail == 2) {
							$achievedoptions[]	=	$obj->id;
						}
		 			}
		 		}

		 		
		 		
			 	//loop through all of the data for this entry in the particular entry	

		 		$query_string	=	$_SERVER['QUERY_STRING'];
		 		
		 		$acheivedimg		=	"achieved.png";
		 		$overdueimg			=	"overdue.jpg";
		 		$failedimg			=	"failed.jpg";
		 		
			 	foreach($pluginentry as $e) {
			 		$entryobj->$fieldname	.=	($this->get_creator_id($entry) == $USER->id && !empty($selectenabled)) ? $OUTPUT->single_select($CFG->wwwroot."/blocks/ilp/actions/change_state.php?entry_id={$entry_id}&reportfield_id={$reportfield_id}&$query_string",'item_id',$optionslist,$e->parent_id,$nothing=array(''=>'choosedots'),'sc_rep{$reportfield_id}ent{$entry_id}') : $e->name;
			 		$img	=	false;
			 		
			 		//check if the report is in a failed or achieved state then add the appropriate icon
			 		if (in_array($e->parent_id,$failedoptions) || in_array($e->parent_id, $achievedoptions)) {
			 			$img	=	(in_array($e->parent_id,$failedoptions)) ? $failedimg: $acheivedimg;
			 		} 

			 		//add the icon
			 		$entryobj->$fieldname	.=	(!empty($img)) ?  "<img src='{$CFG->wwwroot}/blocks/ilp/pix/icons/{$img}' alt='' width='32px' height='32px' />" : '';
			 	}
	 		}
	  }


    /**************
     * @param int $reportfield_id
     * @param int $entry_id
     * @param object $data
     * @return bool
     *
     * Overrides the parent entry_process_data to allow the data fieldname value which is text to be converted into an id
     */

    public	function entry_process_data($reportfield_id,$entry_id,$data) {

        $fieldname =	$reportfield_id."_field";

        $pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);

        if (!empty($data->$fieldname)) {

            $values		=	$data->$fieldname;
            //pass the values given to $entryvalues as an array
            $entryvalues	=	(!is_array($values)) ? array($values): $values;

            foreach ($entryvalues as $ev) {
                $state_item   =   $this->dbc->get_state_item_id($this->tablename,$pluginrecord->id ,$ev, 'value', $this->external_items_table );
                //there should only be one entryvalues as this is a state_item if there are more there is a problem
                $temp           =   $state_item->id;
            }

            $data->$fieldname   =   $temp;
        }

        return parent::entry_process_data($reportfield_id,$entry_id,$data);
    }
	
    public function audit_type() {
        return get_string('ilp_element_plugin_state_type','block_ilp');
    }
    
    /*
    * similar to the other list types, but has an extra field in the items table
    */
    public function install() {
        global $CFG, $DB;

        // create the table to store report fields
        $table = new $this->xmldb_table( $this->tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_report = new $this->xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
        
        $table_optiontype = new $this->xmldb_field('selecttype');
        $table_optiontype->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, null);	//1=single, 2=multi cf blocks/ilp/constants.php
        $table->addField($table_optiontype);
        
        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('textplugin_unique_reportfield');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('reportfield_id'),'block_ilp_report_field','id');
        $table->addKey($table_key);
        

        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
	    // create the new table to store dropdown options
		if( $this->items_tablename ){
	        $table = new $this->xmldb_table( $this->items_tablename );
	
	        $table_id = new $this->xmldb_field('id');
	        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
	        $table->addField($table_id);
	        
	        $table_textfieldid = new $this->xmldb_field('parent_id');
	        $table_textfieldid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	        $table->addField($table_textfieldid);
	        
	        $table_itemvalue = new $this->xmldb_field('value');
	        $table_itemvalue->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
	        $table->addField($table_itemvalue);
	        
	        $table_itemname = new $this->xmldb_field('name');
	        $table_itemname->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
	        $table->addField($table_itemname);

            //special field to categorise states as pass or fail
            //0=unset,1=fail,2=pass
            $table_itempassfail = new $this->xmldb_field( 'passfail' );
	        $table_itempassfail->$set_attributes( XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, '0', null, null, '0' );
            $table->addField( $table_itempassfail );
	
	        $table_timemodified = new $this->xmldb_field('timemodified');
	        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	        $table->addField($table_timemodified);
	
	        $table_timecreated = new $this->xmldb_field('timecreated');
	        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	        $table->addField($table_timecreated);
	
	        $table_key = new $this->xmldb_key('primary');
	        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
	        $table->addKey($table_key);
	
	       	$table_key = new $this->xmldb_key('listplugin_unique_fk');
	        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), $this->tablename, 'id');
	        $table->addKey($table_key);

            $table_index = new $this->xmldb_index('passfail');
            $table_index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('passfail'));
            $table->addIndex($table_index);


	/*
	        $table_key = new $this->xmldb_key('textplugin_unique_entry');
	        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('entry_id'),'block_ilp_entry','id');
	        $table->addKey($table_key);
	*/
	        
	        if(!$this->dbman->table_exists($table)) {
	            $this->dbman->create_table($table);
	        }
	}
        
	    // create the new table to store responses to fields
        $table = new $this->xmldb_table( $this->data_entry_tablename );

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
       
        $table_maxlength = new $this->xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);
        
        $table_item_id = new $this->xmldb_field('value');	//foreign key -> $this->items_tablename
        $table_item_id->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_item_id);

        $table_report = new $this->xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
        
        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);
	
       	$table_key = new $this->xmldb_key('listpluginentry_unique_fk');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), $this->tablename, 'id');
        $table->addKey($table_key);

        $table_index = new $this->xmldb_index('ste_entry');
        $table_index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('entry_id'));
        $table->addIndex($table_index);

        $table_index = new $this->xmldb_index('ste_entry_parent');
        $table_index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('entry_id','parent_id'));
        $table->addIndex($table_index);

        
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
    }
}
