<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin.php');
require_once($CFG->dirroot.'/grade/report/tracker/gradetrackerfuncs.php');

class ilp_element_plugin_gradebooktracker extends ilp_element_plugin {
	
	public $tablename;
	public $data_entry_tablename;
	
	    /**
     * Constructor
     */
    function __construct() {
    	
    	$this->tablename = "block_ilp_plu_gradebooktracker";
    	$this->data_entry_tablename = "block_ilp_plu_gradebooktracker_ent";
    	
    	parent::__construct();
    }
	

    public function load($reportfield_id) {
        //echo 'loading gradebooktracker';exit;
    }
    public	function entry_form( &$mform ) {
        $courselist = grade_tracker_funcs::collect_option_list( 'course' );
        $courseselect = &$mform->addElement(
            'select',
            'courseid',
            'Subject',
	    	$courselist,
            array('class' => 'form_input')
        );

        $fieldname = 'gradeitem_list';
        $label =  'Grades';
        $optionlist = $this->get_grade_item_list( 5 );
        $select = &$mform->addElement(
            'select',
            $fieldname,
            $label,
	    	$optionlist,
            array('class' => 'form_input')
        );
		$select->setMultiple(true);

        $ta = &$mform->addElement(
            'textarea',
            'review',
            'Review',
            ''
        );
    }


    protected function get_grade_item_list( $courseid ){
        $objlist = grade_tracker_funcs::get_grade_items_for_course( $courseid );
        $optionlist = array();
        foreach( $objlist as $row ){
            $optionlist[ $row->id ] = $row->id . ':' . $row->itemname;
        }
        return $optionlist;
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    function language_strings(&$string) {
        $string['ilp_element_plugin_gradebooktracker'] 		= 'Gradebooktracker';
        $string['ilp_element_plugin_gradebooktracker_type'] = 'Gradebooktracker Field';
        $string['ilp_element_plugin_gradebooktracker_description'] = 'A gradebooktracker field';
        
        return $string;
    }

   	/**
     * Delete a form element
     */
    public function delete_form_element($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
        $extraparams = array(
            'audit_type' => $this->audit_type(),
            'label' => $reportfield->label,
            'description' => $reportfield->description,
            'id' => $reportfield_id
        );
    	return parent::delete_form_element( $this->tablename, $reportfield_id, $extraparams );
    }
	 
    public function audit_type(){
        return get_string('ilp_element_plugin_gradebooktracker_type','block_ilp');
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
        
        $table_title = new $this->xmldb_field('course_id');
        $table_title->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
        $table->addField($table_title);

        $table_report = new $this->xmldb_field('gradeitem_id');
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
	 /**
	  * places entry data for the report field given into the entryobj given by the user 
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
	  */
	 public function entry_data( $reportfield_id,$entry_id,&$entryobj ){
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
        global $CFG;
        $trackerfile = $CFG->dirroot . '/grade/report/tracker/student_grade_tracker.php';
        if( file_exists( $trackerfile ) ){
            require_once( $trackerfile );
            $tracker = new student_grade_tracker( $entryobj->user_id );
            $tracker->display();
        }
        else{
            echo "missing module: grade/report/tracker";
        }
		//$this->entry_data( $reportfield_id,$entry_id, $entryobj );
	 }

}
