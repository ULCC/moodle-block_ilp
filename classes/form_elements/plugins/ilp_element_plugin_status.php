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
}
