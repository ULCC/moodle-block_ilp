<?php

//require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');

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
		$this->selecttype = OPTIONSINGLE;	
		
		parent::__construct();
    }

    /*
    * should not be able to add a state selector if there is already one one the form
    */
    public function can_add( $report_id ){
        return !$this->dbc->element_type_exists( $report_id, $this->tablename );
    }

    function language_strings(&$string) {
        $string['ilp_element_plugin_state'] 			= 'Select';
        $string['ilp_element_plugin_state_type'] 		= 'state select';
        $string['ilp_element_plugin_state_description'] 	= 'A state selector';
		$string[ 'ilp_element_plugin_state_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_state_single' ] 		= 'Single select';
		$string[ 'ilp_element_plugin_state_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_state_typelabel' ] 	= 'Select type (single/multi)';
		$string[ 'ilp_element_plugin_state_fail' ] 	        = 'fail';
		$string[ 'ilp_element_plugin_state_pass' ] 	        = 'pass';
		$string[ 'ilp_element_plugin_state_unset' ] 	    = 'unset';
        
        return $string;
    }

    /*
    * similar to parent, but with extra field for grade
    */
     public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);	
		if (!empty($reportfield)) {
			$this->reportfield_id	=	$reportfield_id;
			$this->plugin_id		=	$reportfield->plugin_id;
			$plugin					=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
			$pluginrecord			=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);
			if (!empty($pluginrecord)) {
				$this->id			    =	$pluginrecord->id;
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;
				$this->req			    =	$reportfield->req;
				$this->position			=	$reportfield->position;
				$this->grade			=	$reportfield->grade;
			}
		}
		return false;	
    }	

    /*
    * get options from the items table for this plugin, and concatenate them into a string
    * @param int $reportfield_id
    * @param string $sep
    * @param string $field - optional additional field to retrieve, along with value and name
    */
	protected function get_option_list_text( $reportfield_id , $sep="\n", $field=false ){
		$option_data = $this->get_option_list( $reportfield_id, $field );
var_crap( $option_data );exit;
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
            'fail' => implode( $sep, $option_data[ 'fail' ] )
        );
	}

    /*
    * read rows from item table and return them as array of key=>value
    * @param int $reportfield_id
    * @param string $field - extra field to read from items table: used by ilp_element_plugin_state
    */
	protected function get_option_list( $reportfield_id, $field=false ){
		//return $this->optlist2Array( $this->get_optionlist() );   	
		$outlist = array();
		$passlist = array();
		$faillist = array();
		if( $reportfield_id ){
			$objlist = $this->dbc->get_optionlist($reportfield_id , $this->tablename, $field );
			foreach( $objlist as $obj ){
				$outlist[ $obj->value ] = $obj->name;
                if( 'grade' == $field ){
                    if( ILP_GRADE_FAIL == $obj->grade ){
                        $faillist[] = $obj->name;
                    }
                    if( ILP_GRADE_PASS == $obj->grade ){
                        $passlist[] = $obj->name;
                    }
                }
			}
		}
		if( !count( $outlist ) ){
			//echo "no items in {$this->items_tablename}";
		}
		return array(
            'optlist' => $outlist,
            'pass' => $passlist,
            'fail' => $faillist
        );
	}

	/*
	* get the list options with which to populate the edit element for this list element
	*/
	public function return_data( &$reportfield ){
		$data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $reportfield->id );
		if( empty( $data_exists ) ){
			//if no, get options list
            $options_data = $this->get_option_list_text( $reportfield->id, "\n", 'grade' );
			$reportfield->optionlist = $options_data[ 'options' ];
		}
		else{
			$options_data = $this->get_option_list_text( $reportfield->id , '<br />', 'grade' );
			$reportfield->existing_options = $options_data[ 'options' ];
		}
        $reportfield->fail = $options_data[ 'fail' ];
        $reportfield->pass = $options_data[ 'pass' ];
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
	        $table_itemvalue->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
	        $table->addField($table_itemvalue);
	        
	        $table_itemname = new $this->xmldb_field('name');
	        $table_itemname->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
	        $table->addField($table_itemname);

            //special field to categorise states as pass or fail
            //0=unset,1=fail,2=pass
            $table_itemgrade = new $this->xmldb_field( 'grade' );
	        $table_itemgrade->$set_attributes( XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0' );
            $table->addField( $table_itemgrade );
	
	        $table_timemodified = new $this->xmldb_field('timemodified');
	        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	        $table->addField($table_timemodified);
	
	        $table_timecreated = new $this->xmldb_field('timecreated');
	        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	        $table->addField($table_timecreated);
	
	        $table_key = new $this->xmldb_key('primary');
	        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
	        $table->addKey($table_key);
	  /*      
	   */     
	/*
	       	$table_key = new $this->xmldb_key('textplugin_unique_textfield');
	        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('textfield_id'), $this->tablename, 'id');
	        $table->addKey($table_key);
	 */               
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
        
/*
       	$table_key = new $this->xmldb_key('textplugin_unique_textfield');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('textfield_id'), $this->tablename, 'id');
        $table->addKey($table_key);
 */               
/*
        $table_key = new $this->xmldb_key('textplugin_unique_entry');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('entry_id'),'block_ilp_entry','id');
        $table->addKey($table_key);
*/
        
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
    }
}
