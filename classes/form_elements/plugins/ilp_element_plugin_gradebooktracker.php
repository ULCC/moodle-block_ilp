<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');
$gradetrackerfuncsfile = $CFG->dirroot . '/grade/report/tracker/gradetrackerfuncs.php' ;
if( file_exists( $gradetrackerfuncsfile ) ){
    require_once( $gradetrackerfuncsfile );
}
else{
    //not much point - maybe throw an error
}

$gradebooktracker_file = $CFG->dirroot.'/grade/report/tracker/gradetrackerfuncs.php';
$gradetracker_exists = false;
if( file_exists( $gradebooktracker_file ) ){
    $gradetracker_exists = true;
    require_once($CFG->dirroot.'/grade/report/tracker/gradetrackerfuncs.php');
}

class ilp_element_plugin_gradebooktracker extends ilp_element_plugin_itemlist {
	
	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	
	    /**
     * Constructor
     */
    function __construct() {
    	$this->tablename = "block_ilp_plu_gradebooktracker";
    	$this->data_entry_tablename = "block_ilp_plu_gradebooktracker_ent";
    	$this->items_tablename = "block_ilp_plu_gradebooktracker_items";
    	
    	parent::__construct();
    }
	
    /*
    * record data for an actual report
    */
    public function entry_process_data( $reportfield_id, $entry_id, $data ){
        $expected_gradelist_label = "{$reportfield_id}_gradeitem_list"; 'reportfield_id';
        $valuefieldname = "{$reportfield_id}_field";
        $courseidfieldname = "{$reportfield_id}_subjectid";
        //@todo
        //list type data is not captured in $data object, so I am having to get it from global $_POST which is very bad
        //must find better way
        $data->$valuefieldname = $_POST[ $expected_gradelist_label ];


	  		$result	=	true;
	  		
		 	//get the plugin table record that has the reportfield_id 
		 	$pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
		 	if (empty($pluginrecord)) {
		 		print_error('pluginrecordnotfound');
		 	}
		 	
		 	//check to see if a entry record already exists for the reportfield in this plugin
            $multiple = !empty( $this->items_tablename );
		 	$entrydata 	=	$this->dbc->get_pluginentry($this->tablename, $entry_id,$reportfield_id,$multiple);
		 	
		 	//if there are records connected to this entry in this reportfield_id 
			if (!empty($entrydata)) {
                /***********************************************************************/
                //maybe this should never happen
                /***********************************************************************/
                if(0){
				//delete all of the entries
                $extraparams = array( 'audit_type' => $this->audit_type() );
				foreach ($entrydata as $e)	{
					$this->dbc->delete_element_record_by_id( $this->data_entry_tablename, $e->id, $extraparams );
				}
				}
			}  
		 	
			//create new entries
			$pluginentry			=	new stdClass();
            $pluginentry->audit_type = $this->audit_type();
			$pluginentry->entry_id  = 	$entry_id;
	 		$pluginentry->value		=	$data->$valuefieldname;
	 		$pluginentry->user_id	=	$data->user_id;
	 		$pluginentry->parent_id	=	$reportfield_id;
	 		$pluginentry->course_id	=	$data->$courseidfieldname;
	 		$pluginentry->review	=	$data->review;

		    $pluginentry->parent_id	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);

			if( is_string( $pluginentry->value ))	{
                //this should never happen for this plugin type: $pluginentry->value should always be array
	 		    $state_item				=	$this->dbc->get_state_item_id($this->tablename,$pluginrecord->id,$data->$valuefieldname, $this->external_items_keyfield, $this->external_items_table );
	 		    $pluginentry->parent_id	=	$state_item->id;	
	 			$result	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
			} else if (is_array( $pluginentry->value ))	{
                //THIS should happen
				$result	=	$this->write_multiple( $this->data_entry_tablename, $pluginentry );
			}
 
	 	
			return	$result;
    }

	/*
	* called by entry_process_data
	* allows multi-select values to be written as multiple rows in entry table
	* @param string $tablename
	* @param object $multi_pluginentry ($multi_pluginentry->value is array of strings)
	* @return boolean
	*/
	 protected function write_multiple( $tablename, $multi_pluginentry ){
		//if we're here, assume $pluginentry->value is array
		$pluginentry = $multi_pluginentry;
		$result		=	true;
		foreach( $multi_pluginentry->value as $value ){
			$pluginentry->gradeitem_id = $value;
			$pluginentry->value = grade_tracker_funcs::get_fgrade( $pluginentry->user_id, $pluginentry->gradeitem_id );
			$pluginentry->name = grade_tracker_funcs::get_gradeitem_name( $value );//$value;///sould be the title of the grade item
			if (!$this->dbc->create_plugin_entry( $this->items_tablename, $pluginentry )) $result = false;
		}
		//if any of the didn't work $result will be false
		return $result;
	 }


/*
    public function old_entry_process_data( $reportfield_id, $entry_id, $data ){
        $expected_gradelist_label = "{$reportfield_id}_gradeitem_list"; 'reportfield_id';
        
        $fieldname = "{$reportfield_id}_field";
        $data->$fieldname = $_POST[ $expected_gradelist_label ];
        $data->user_id = 999;
        return parent::entry_process_data( $reportfield_id, $entry_id, $data );
        global $DB;
        //prepare entry
		  	//create the fieldname
			$fieldname =	$reportfield_id."_field";
		 	$pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
var_dump($pluginrecord);exit;

		$result	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
var_dump($result);exit;
        
        //prepare item list
        if( empty( $data->id ) ){
            //new record
 			$entry_id	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$data);
            //write a row in items for each grade item
            foreach( $data->gradeitem_list as $gradeitem ){
                $data->gradeitem_id = $gradeitem;
                $data->name = $this->get_gradeitem_name( $gradeitem );
                $data->value = $this->get_gradevalue( $data->user_id, $gradeitem );
                $this->dbc->create_plugin_entry( $this->items_tablename, $data );
            }
        }
        else{
            //update existing record
            //maybe this will never happen
        }
    }
*/

    protected function get_gradeitem_name( $gradeitem_id ){
        return grade_tracker_funcs::get_gradeitem_name( $gradeitem_id );
    }
    
    protected function get_gradevalue( $student_id, $gradeitem_id ){
        return grade_tracker_funcs::get_fgrade( $student_id, $gradeitem_id );
    }

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
				$this->position			=	$reportfield->position;
                $this->audit_type       =   $this->audit_type();
				return true;	
			}
		}
    }
    public	function entry_form( &$mform ) {
        global $CFG,$PAGE,$PARSER,$DB,$USER;
        
        //$pluginentry = $DB->get_record( $this->tablename, array( 'id' => $entry_id ) );

        $parentid = $this->reportfield_id;

        $gradebooktracker_file = $CFG->dirroot.'/grade/report/tracker/gradetrackerfuncs.php';
        if( file_exists( $gradebooktracker_file ) ){
			
			$user_id	=	optional_param('user_id', NULL, PARAM_INT);
            if( !$user_id ){
                $user_id = $USER->id;
            }
			
            $mform->addElement( 'hidden', 'parent_id', $parentid );
	        //$courselist = grade_tracker_funcs::collect_option_list( 'course' );
            $course_selector_name = "{$this->reportfield_id}_subjectid";
            $courselist = grade_tracker_funcs::build_option_array( enrol_get_users_courses( $user_id, true ) , 'id' , 'fullname' );
	        $courseselect = &$mform->addElement(
	            'select',
	            $course_selector_name,
	            'Subject',
		    	$courselist,
	            array(
                    'class' => 'form_input',
                    'onchange' => 'javascript:document.location=M.gradebooktracker_construct_url( document.location, \'' . $course_selector_name . '\', this.value )'
                )
	        );

			$subject_id	=	optional_param( $course_selector_name , NULL, PARAM_INT );
            if( empty( $subject_id ) ) $subject_id = optional_param( 'course_id', NULL, PARAM_INT );
            if (!empty($subject_id)) $mform->setDefault( $course_selector_name , $subject_id );
			
            $mform->setDefault( 'review', 'random comment ' . date( 'Y-m-d H:i:s' ) );
	
	        $fieldname = "{$this->reportfield_id}_gradeitem_list";
	        
	        $label =  'Grades';

	        $optionlist = $this->get_grade_item_list( $subject_id, true );
	        $select = &$mform->addElement(
	            'select',
	            $fieldname,
	            $label,
		    	$optionlist,
	            array(
                    'class' => 'form_input'
                )
	        );
			$select->setMultiple(true);
	
/*
	        $ta = &$mform->addElement(
	            'textarea',
	            'review',
	            'Review',
	            ''
	        );
*/
	    }
	    
	    //js function for entry form
		$localdir = '/blocks/ilp/classes/form_elements/plugins/';
		$module = array(
    		'name' => 'ilp_element_plugin_gradebooktracker',
    		'fullpath' => $localdir . 'ilp_element_plugin_gradebooktracker.js',
    		'requires' => array()
		);
		$PAGE->requires->js_init_call( 'M.ilp_element_plugin_gradebooktracker_construct_url', array(), true, $module );
    }


    protected function get_grade_item_list( $courseid , $gradetracker_exists ){
        if( $gradetracker_exists ){
	        $objlist = grade_tracker_funcs::get_grade_items_for_course( $courseid );
	        $optionlist = array();
	        foreach( $objlist as $row ){
	            $optionlist[ $row->id ] = $row->id . ':' . $row->itemname;
	        }
	        return $optionlist;
        }
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    function language_strings(&$string) {
        $string['ilp_element_plugin_gradebooktracker'] 		= 'Gradebooktracker';
        $string['ilp_element_plugin_gradebooktracker_type'] = 'Gradebooktracker Field';
        $string['ilp_element_plugin_gradebooktracker_description'] = 'A gradebooktracker field';
        $string['ilp_element_plugin_gradebooktracker_course_select_label'] = 'Course';
        
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

        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        // create the table to store report fields
        $table = new $this->xmldb_table( $this->tablename );

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_report = new $this->xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
        
/*
        $table_courseid = new $this->xmldb_field('course_id');
        $table_courseid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED);
        $table->addField($table_courseid);
*/
        
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

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        //entryid only seems necessary to make the logging happy - this seems a bit wag-the-dog
        $table_entryid = new $this->xmldb_field('entry_id');
        $table_entryid->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
        $table->addField($table_entryid);
        
        $table_title = new $this->xmldb_field('course_id');
        $table_title->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
        $table->addField($table_title);
        
        $table_maxlength = new $this->xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);
        
        $table_userid = new $this->xmldb_field('user_id');
        $table_userid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_userid);
        
/*
        $table_review = new $this->xmldb_field('review');
        $table_review->$set_attributes(XMLDB_TYPE_CHAR, 255);
        $table->addField($table_review);
*/
        
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

        //create the table to store individual grade items with scores
        $table = new $this->xmldb_table( $this->items_tablename );
        
        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_maxlength = new $this->xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);

        $table_report = new $this->xmldb_field('gradeitem_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);
	        
        $table_itemvalue = new $this->xmldb_field('value');
        $table_itemvalue->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
        $table->addField($table_itemvalue);
        
        $table_itemname = new $this->xmldb_field('name');
        $table_itemname->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
        $table->addField($table_itemname);
	
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
        
        
        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }
    }
    public function process_data( $formdata ){
    }
	 /**
	  * places entry data for the report field given into the entryobj given by the user 
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
	  */
	 public function entry_data( $reportfield_id,$entry_id,&$entryobj ){
        //var_dump($entryobj);exit;
        //return parent::entry_data( $reportfield_id,$entry_id,&$entryobj );
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
        global $CFG, $DB;
        //find grade tracker entries for this user
        $trackerfile = $CFG->dirroot . '/grade/report/tracker/student_grade_tracker.php';
        if( file_exists( $trackerfile ) ){
            require_once( $trackerfile );
            $tracker = new student_grade_tracker( $entryobj->user_id );
            $tracker->display_saved_reports( $this->data_entry_tablename, $this->items_tablename );
           
            //$tracker->display();
        }
        else{
            echo "missing module: grade/report/tracker";
        }
		//$this->entry_data( $reportfield_id,$entry_id, $entryobj );
	 }

}
