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
        parent::install();
        //extra table for this plugin
        $tablename = "block_ilp_plu_user_status";
        $table = new $this->xmldb_table( $tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_uid = new $this->xmldb_field('user_id');
        $table_uid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_uid);
        
        $table_sii = new $this->xmldb_field('status_item_id');
        $table_sii->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_sii);
        
        $table_umi = new $this->xmldb_field('user_modified_id');
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
    * read rows from item table and return them as array of key=>value
    * @param int $reportfield_id
    * @param string $field - extra field to read from items table: used by ilp_element_plugin_state
    */
	protected function get_option_list( $reportfield_id ){
  	
		$outlist = array();
		if( $reportfield_id ){
			$objlist = $this->dbc->get_status_options($reportfield_id);
			foreach( $objlist as $obj ){
				$outlist[ $obj->value ] = $obj->name;
			}
		}
		if( !count( $outlist ) ){
			//echo "no items in {$this->items_tablename}";
		}
		return $outlist;
	}
}
