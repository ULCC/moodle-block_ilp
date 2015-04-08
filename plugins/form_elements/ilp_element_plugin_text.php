<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');

class ilp_element_plugin_text extends ilp_element_plugin {
	
	public $tablename;
	public $data_entry_tablename;
	public $minimumlength;
	public $maximumlength;
	
	    /**
     * Constructor
     */
    function __construct() {
    	
    	$this->tablename = "block_ilp_plu_tex";
    	$this->data_entry_tablename = "block_ilp_plu_tex_ent";
    	
    	parent::__construct();
    }
	
	
	/**
     * TODO comment this
     * called when user form is submitted
     */
    public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
		if (!empty($reportfield)) {
			//set the reportfield_id var
			$this->reportfield_id	=	$reportfield_id;
			
			//get the record of the plugin used for the field 
			$plugin		=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
						
			$this->plugin_id	=	$reportfield->plugin_id;
			
			//get the form element record for the reportfield 
			$pluginrecord	=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);
			
			if (!empty($pluginrecord)) {
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;
				$this->req				=	$reportfield->req;
				$this->maximumlength	=	$pluginrecord->maximumlength;
				$this->minimumlength	=	$pluginrecord->minimumlength;
				$this->position			=	$reportfield->position;
                $this->audit_type       =   $this->audit_type();
				return true;	
			}
		}
		return false;	
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
        
        $table_minlength = new $this->xmldb_field('minimumlength');
        $table_minlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_minlength);
        
        $table_maxlength = new $this->xmldb_field('maximumlength');
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
        $table_title->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
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

        $table_index = new $this->xmldb_index('tex_entry');
        $table_index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('entry_id'));
        $table->addIndex($table_index);
        
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
        
        
    }

    /**
     *
     */
    public function uninstall() {
        $table = new $this->xmldb_table( $this->tablename );
        drop_table($table);
        
        $table = new $this->xmldb_table( $this->data_entry_tablename );
        drop_table($table);
    }
	
     /**
     *
     */
    public function audit_type() {
        return get_string('ilp_element_plugin_text_type','block_ilp');
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    static function language_strings(&$string) {
        $string['ilp_element_plugin_text'] 		= 'Text';
        $string['ilp_element_plugin_text_type'] = 'Text Field';
        $string['ilp_element_plugin_text_description'] = 'A text field';
        $string['ilp_element_plugin_text_maxlengthrange'] = 'The maximum length field must have a value between 0 and 255';
        $string['ilp_element_plugin_text_maxlessthanmin'] = 'The maximum length field must have a greater value than the minimum length';
        $string['ilp_element_plugin_text_maximumlength']  = 'Maximum Length';
        $string['ilp_element_plugin_text_minimumlength']  = 'Minimum Length';
        
        return $string;
    }

   	/**
     * Delete a form element
     */
    public function delete_form_element($reportfield_id, $tablename=null, $extraparams=null) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
        $extraparams = array(
            'audit_type' => $this->audit_type(),
            'label' => $reportfield->label,
            'description' => $reportfield->description,
            'id' => $reportfield_id
        );
    	return parent::delete_form_element( $reportfield_id, $this->tablename, $extraparams );
    }
    
    /**
    * this function returns the mform elements taht will be added to a report form
	*
    */
    public	function entry_form( &$mform ) {

        //IE has a problem validating the fields on the Moodle form generated by the ILP. Instead of validating the
        //form field by its given name it seems to be validating a form element with the orignal name e.g 2_field
        //is changed by Moodle into id_2_field. IE validates 2_field and always resolves that it contains a value.
        //This does not occur in Firefox. In order to

    	$fieldname	=	"{$this->reportfield_id}_field";

    	if (!empty($this->description)) {
    		$mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description,
                                                                                                          ENT_QUOTES,
                                                                                                          'UTF-8'),ILP_STRIP_TAGS_DESCRIPTION));
    		$this->label = '';
    	} 
    	//text field for element label
        $mform->addElement(
            'text',
            $fieldname,
            "$this->label",
            array('class' => 'form_input')
        );


        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        if (!empty($this->minimumlength)) $mform->addRule($fieldname, null, 'minlength', $this->minimumlength, 'client');
        if (!empty($this->maximumlength)) $mform->addRule($fieldname, null, 'maxlength', $this->maximumlength, 'client');

        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'server');
        if (!empty($this->minimumlength)) $mform->addRule($fieldname, null, 'minlength', $this->minimumlength, 'server');
        if (!empty($this->maximumlength)) $mform->addRule($fieldname, null, 'maxlength', $this->maximumlength, 'server');



        $mform->setType($fieldname, PARAM_RAW);
	 }
	 
	/**
	* handle user input
	**/
	 public	function entry_specific_process_data($reportfield_id,$entry_id,$data) {
		/*
		* parent method is fine for simple form element types
		* dd types will need something more elaborate to handle the intermediate
		* items table and foreign key
		*/
		return $this->entry_process_data($reportfield_id,$entry_id,$data); 	
	 }


    public function validation($data, $files) {

        $fieldname	=	"{$this->reportfield_id}_field";


        $errors[$fieldname] =   "Required";

        return $errors;

    }

}
