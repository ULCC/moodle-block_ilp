<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');

class ilp_element_plugin_free_html extends ilp_element_plugin {

	public $tablename;
	public $data_entry_tablename;

    function __construct() {
    	$this->tablename = "block_ilp_plu_fre";
    	$this->data_entry_tablename = "block_ilp_plu_fre_ent";
    	parent::__construct();
    }

    public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
		if (!empty($reportfield)) {
			$this->reportfield_id	=	$reportfield_id;
			$this->plugin_id	=	$reportfield->plugin_id;
			$this->label			=	$reportfield->label;
			$this->description		=	$reportfield->description;
			$this->req				=	0;
			$this->position			=	$reportfield->position;
            $this->audit_type       =   $this->audit_type();
			return true;	
        }
		return false;	
    }

    /*
    * essential delete method
    * @param int $reportfield_id
    * @return boolean
    */
    public function delete_form_element($reportfield_id, $tablename=null, $extraparams=null) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
        $extraparams = array(
            'audit_type' => $this->audit_type(),
            'label' => $reportfield->label,
            'description' => $reportfield->description,
            'id' => $reportfield_id
        );
    	return parent::delete_form_element(  $reportfield_id, $this->tablename, $extraparams );
    }
    /**
    * this function returns the mform elements taht will be added to a report form
	*
    */
    public	function entry_form( &$mform ) {
    	
    	$fieldname	=	"{$this->reportfield_id}_field";
        //no description necessary for this class imho (jfp)

    	//html field for element label
        $mform->addElement(
            'html',
            html_entity_decode($this->description)
        );

        $mform->setType($fieldname, PARAM_RAW);

        $fieldname  =   "id_".$fieldname;

    }

    public function audit_type() {
        return get_string('ilp_element_plugin_free_html_type','block_ilp');
    }

    static function language_strings(&$string) {
        $string['ilp_element_plugin_free_html_type'] 		= 'Free markup';
        $string['ilp_element_plugin_free_html_description']	= 'Free HTML';
        $string['ilp_element_plugin_free_html_contents']	= 'Contents (any valid HTML)';
        $string[ 'ilp_element_plugin_free_html_markup_required' ]	= 'You need to enter some markup in the contents field';
    }

	/**
     * create tables for this plugin
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
        
        
	    // create the new table to store responses to fields
        $table = new $this->xmldb_table( $this->data_entry_tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_title = new $this->xmldb_field('value');
        $table_title->$set_attributes(XMLDB_TYPE_TEXT);
        $table->addField($table_title);

        $table_report = new $this->xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
        
        $table_maxlength = new $this->xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);
        
        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);
        
       	$table_key = new $this->xmldb_key($this->tablename.'_foreign_key');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), $this->tablename ,'id');
        $table->addKey($table_key);
        
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
    }

    
	public function is_processable()	{
    	return false;
    }
    
	public function is_viewable()	{
    	return false;
    }

    public function uninstall() {
        $table = new $this->xmldb_table( $this->tablename );
        drop_table($table);
        
        $table = new $this->xmldb_table( $this->data_entry_tablename );
        drop_table($table);
    }
    

}
