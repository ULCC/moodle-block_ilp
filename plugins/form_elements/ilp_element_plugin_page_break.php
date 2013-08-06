<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');

class ilp_element_plugin_page_break extends ilp_element_plugin {
	
	public $tablename;

	    /**
     * Constructor
     */
    function __construct() {
    	
    	$this->tablename = "block_ilp_plu_pb";

    	parent::__construct();
    }
	
	
	/**
     * TODO comment this
     * called when user form is submitted
     */
    public function load($reportfield_id) {
        $reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
		if (!empty($reportfield)) {
			//set the formfield_id var
			$this->reportfield_id	=	$reportfield_id;
			
			//get the record of the plugin used for the field 
			$plugin		=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
						
			$this->plugin_id	=	$reportfield->plugin_id;

			//get the form element record for the formfield
			$pluginrecord	=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);
			
			if (!empty($pluginrecord)) {
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;

                //required has no relevance to a page break so always have it set to false
				$this->required			=	0;
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

        // create the table to store form fields
        $table = new $this->xmldb_table( $this->tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_form = new $this->xmldb_field('reportfield_id');
        $table_form->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_form);

        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('pagebreakplugin_unique_reportfield');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('reportfield_id'),'block_ilp_library_report_field','id');
        $table->addKey($table_key);

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
    }
	
     /**
     *
     */
    public function audit_type() {
        return get_string('ilp_element_plugin_page_break_type','block_ilp');
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    static function language_strings(&$string) {
        $string['ilp_element_plugin_page_break'] 		= 'Page break';
        $string['ilp_element_plugin_page_break_type'] = 'Page break';
        $string['ilp_element_plugin_page_break_description'] = 'A page break';
        
        return $string;
    }

   	/**
     * Delete a form element
     */
    public function delete_form_element($reportfield_id, $tablename=null, $extraparams=null) {
        $extraparams = array(
            'audit_type' => $this->audit_type(),
            'label' => 'page break',
            'description' => 'page break',
            'id' => $reportfield_id
        );
    	return parent::delete_form_element(  $reportfield_id, $this->tablename, $extraparams );
    }

    /**
     * this function returns the mform elements taht will be added to a form form
     *
     */
    public	function entry_form( &$mform ) {
        $mform->addElement('hidden', "formsession[{$this->reportfield_id}]",'');
    }
	 
	/**
	* handle user input
	**/
	 public	function entry_specific_process_data($reportfield_id,$entry_id,$data) {
        //nothing to do in the page break class
	 }


    /**
     * The page break plugin doesn't need to do add anything
     * */
    public function entry_data( $reportfield_id,$entry_id,&$entryobj ){

    }

    /**
     * page breaks can not be processed
     * @return bool
     * */
    public function is_processable()	{
        return false;
    }

    /**
     * Page breaks are not viewable
     * @return bool
     */
    public function is_viewable()	{
        return false;
    }

    /**
     * Page breaks are not configurable
     *
     * @return bool
     */
    public function is_configurable()	{
        return false;
    }


    /**
     * Page breaks are not editable
     *
     * @return bool
     */
    public function is_editable()	{
        return false;
    }

    public function delete_entry_record($entryid = null){

    }
}
