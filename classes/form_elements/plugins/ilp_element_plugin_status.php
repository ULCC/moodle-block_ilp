<?php
//require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_plu_db.php');

/*
 * much of the guts of this class inherited from ilp_element_plugin_itemlist
*/
class ilp_element_plugin_status extends ilp_element_plugin_itemlist{

	public $tablename;
	public $data_entry_tablename;
	public $optionlist_keyfield;
	public $selecttype;
	
    /**
     * Constructor
     */
    function __construct() {
    	$this->tablename = "block_ilp_plu_sts";
    	$this->data_entry_tablename = "block_ilp_plu_sts_ent";
		$this->items_tablename = "block_ilp_plu_sts_items";
		$this->optionlist_keyfield = "status_id";
		$this->selecttype = OPTIONSINGLE;
		parent::__construct();
    }
    
    
	 /**
	  * places entry data for the report field given into the entryobj given by the user 
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
	  */
	 public function entry_data( $reportfield_id,$entry_id,&$entryobj ){
	 	//this function will suffix for 90% of plugins who only have one value field (named value) i
	 	//in the _ent table of the plugin. However if your plugin has more fields you should override
	 	//the function 
	 	
		//default entry_data 	
		$fieldname	=	$reportfield_id."_field";
	 	
	 	$entry	=	$this->dbc->get_entrystatus($entry_id,$reportfield_id);
 
		if (!empty($entry)) {
		 	$fielddata	=	array();

		 	//loop through all of the data for this entry in the particular entry		 	
		 	foreach($entry as $e) {
		 		$fielddata[]	=	$e->value;
		 	}
		 	
		 	//save the data to the objects field
	 		$entryobj->$fieldname	=	$fielddata;
	 	}
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
	  */
	  public function view_data( $reportfield_id,$entry_id,&$entryobj ){
	  		$fieldname	=	$reportfield_id."_field";
	  		
	 		$entry	=	$this->dbc->get_entrystatus($entry_id,$reportfield_id);
	 		
			if (!empty($entry)) {
		 		$fielddata	=	array();
		 		$comma	= "";
			 	//loop through all of the data for this entry in the particular entry		 	
			 	foreach($entry as $e) {
			 		$entryobj->$fieldname	.=	$e->name.$comma;
			 		$comma	=	",";
			 	}
	 		}
	  }
    

    public function install(){
    	
         // create the table to store report fields
        $table = new $this->xmldb_table( $this->tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_report = new $this->xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null);
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
        
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
        
        //this table records an instance creation of a status field in a report
        $tablename = "block_ilp_plu_rf_sts";
        $table = new $this->xmldb_table( $tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_uid = new $this->xmldb_field('reportfield_id');
        $table_uid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_uid);
        
        $table_sii = new $this->xmldb_field('status_id');
        $table_sii->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_sii);
        
        $table_umi = new $this->xmldb_field('creator_id');
        $table_umi->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_umi);
        
        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);
                
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
    }
    
    function language_strings(&$string) {
        $string['ilp_element_plugin_status'] 			= 'Select';
        $string['ilp_element_plugin_status_type'] 		= 'status select';
        $string['ilp_element_plugin_status_description'] 	= 'A status selector';
		$string[ 'ilp_element_plugin_status_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_status_single' ] 		= 'Single select';
		$string[ 'ilp_element_plugin_status_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_status_typelabel' ] 	= 'Select type (single/multi)';
	        
        return $string;
    }

    /*
    * should not be able to add a status selector if there is already one one the form
    */
    public function can_add( $report_id ){
        return !$this->dbc->element_type_exists( $report_id, $this->tablename );
    }
    
    protected function rst_flatten( $rst , $keyfield , $valuefield='value' ){
		$outlist = array();
		foreach( $rst as $row ){
			$outlist[ $row->$keyfield ] = $row->$valuefield;
		}
		return $outlist;
    }
    
    public function audit_type() {
        return get_string('ilp_element_plugin_status_type','block_ilp');
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
		}
		else{
			$options_data = $this->get_option_list_text( $reportfield->id , '<br />', 'passfail' );
			$reportfield->existing_options = $options_data[ 'options' ];
		}
        $reportfield->fail = $options_data[ 'fail' ];
        $reportfield->pass = $options_data[ 'pass' ];
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
            'fail' => implode( $sep, $option_data[ 'fail' ] )
        );
	}
    
    
    /*
    * read rows from item table and return them as array of key=>value
    * @param int $reportfield_id
    * @param string $field - the name of a extra field to read from items table: used by ilp_element_plugin_state
    */
	protected function get_option_list( $reportfield_id, $field=false ){
		//return $this->optlist2Array( $this->get_optionlist() );   	
		$outlist = array();
		$passlist = array();
		$faillist = array();
		if( $reportfield_id ){
			//get the list of options for this reportfield in the given table from the db 
			$objlist = $this->dbc->get_optionlist($reportfield_id , $this->tablename, $field );
			
			foreach( $objlist as $obj ){
				//place the name into an array with value as key
				$outlist[ $obj->value ] = $obj->name;
				
				//if the the name of the extra field is passfail then 
                if( 'passfail' == $field ){
                	//if the field value is fail add to fail list
                    if( ILP_PASSFAIL_FAIL == $obj->passfail ){
                        $faillist[] = $obj->name;
                    }
                    if( ILP_PASSFAIL_PASS == $obj->passfail ){
                        $passlist[] = $obj->name;
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
            'fail' => $faillist
        );
        
        //we only need to return the admin values if the $field value is not false (it should be set to passfail to get admin values) 
        return (!empty($field)) ? $adminvalues : $outlist; 
	}
}
