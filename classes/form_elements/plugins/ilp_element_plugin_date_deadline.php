<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin.php');

class ilp_element_plugin_date_deadline extends ilp_element_plugin {

	public $tablename;
	public $data_entry_tablename;
	public $datetense;	//offers the form creator 'past', 'present' and 'future' options to control validation of the user input	
	
    /**
     * Constructor
     */
    function __construct() {
    	$this->tablename = "block_ilp_plu_ddl";
    	$this->data_entry_tablename = "block_ilp_plu_ddl_ent";
    	parent::__construct();
    }
	
	
     /**
     * TODO comment this
     *
     */
    public function load($reportfield_id) {
		$reportfield		=	$this->dbc->get_report_field_data($reportfield_id);	
		if (!empty($reportfield)) {
			$this->reportfield_id	=	$reportfield_id;
			$plugin		=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
			$this->plugin_id=	$reportfield->plugin_id;
			$pluginrecord	=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);
			if (!empty($pluginrecord)) {
				$this->label			=	$reportfield->label;
				$this->description		=	$reportfield->description;
				$this->req			=	$reportfield->req;
				$this->datetense		=	$this->datetense;
				$this->position			=	$reportfield->position;
			}
		}
		return false;	
    }	

	
	/**
     *
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
        
        $table_datetense = new $this->xmldb_field('datetense');
        $table_datetense->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_datetense);
        
        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('date_unique_reportfield');
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
        return get_string('ilp_element_plugin_date_deadline_type','block_ilp');
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    function language_strings(&$string) {
        $string['ilp_element_plugin_date_deadline']		= 'Date deadline';
        $string['ilp_element_plugin_date_deadline_type'] 	= 'date deadline';
        $string['ilp_element_plugin_date_deadline_description']	= 'A date deadline entry element';
        $string['ilp_element_plugin_date_deadline_tense'] 	= 'Date tense';
        $string['ilp_element_plugin_date_deadline_past'] 	= 'past';
        $string['ilp_element_plugin_date_deadline_present'] 	= 'present';
        $string['ilp_element_plugin_date_deadline_future'] 	= 'future';
        $string['ilp_element_plugin_date_deadline_anydate'] 	= 'none of the above, or a mixture';
        
        return $string;
    }
    
    /**
    * this function returns the mform elements taht will be added to a report form
	*
    */
    public	function entry_form( &$mform ) {

    	//create the fieldname
    	$fieldname	=	"{$this->reportfield_id}_field";
    	
    	if (!empty($this->description)) {
    		$mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description),ILP_STRIP_TAGS_DESCRIPTION));
    		$this->label = '';
    	} 
    	
    	//text field for element label
        $mform->addElement(
            'date_selector',
            $fieldname,
            $this->label,
            array('class' => 'form_input', 'optional' => false )
        );
        
        
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
		
        //@todo decide correct PARAM type for date element
        $mform->setType($fieldname, PARAM_RAW);
    }

    
    function entry_process_data($reportfield_id,$entry_id,$data)	{
    	return $this->entry_specific_process_data($reportfield_id, $entry_id, $data);
    }
    
    
    
    
    /**
	* handle user input
	**/
	 public	function entry_specific_process_data($reportfield_id,$entry_id,$data) {
		global $CFG;
		
		/*
		* parent method is fine for simple form element types
		* dd types will need something more elaborate to handle the intermediate
		* items table and foreign key
		*/
	 	
	 	$fieldname =	$reportfield_id."_field";
	 	
	 	$report		=	$this->dbc->get_report_by_id($data->report_id);
	 	
	 	$title		=	(!empty($report))	? $report->name." ".get_string('deadline','block_ilp') : get_string('deadline','block_ilp');
	 	
	 	$event	=	$this->dbc->get_calendar_event($entry_id,$reportfield_id);
	 	
 
 	 	if (empty($event))		{
	 		$event = new stdClass();
	        $event->name        = $title;
	        //link to ilp has been removed due to moodle encoding html and outputing it.
	        $event->description = $title;
	        $event->format      = 0;
	        $event->courseid    = 0;
	        $event->groupid     = 0;
	        $event->userid      = $data->user_id;
	        $event->modulename  = '0';
	        $event->instance    = 0;
	        $event->eventtype   = 'due';
	        $event->timestart   = $data->$fieldname;
	        $event->timeduration = time();
	        
	        $event->id = $this->dbc->save_event($event);

	        $record					=	new stdClass();
	        $record->entry_id		=	$entry_id;
	        $record->reportfield_id	=	$reportfield_id;
	        $record->event_id		=	$event->id;
	        $record->timemodified	=	time();	
	        $record->timecreated	=	time();	
	        
	        //create the calendar cross reference record
	        $this->dbc->create_event_cross_reference($record);
	        
	 	}   else	{
	 		$event	=	$this->dbc->get_calendar_event($entry_id,$reportfield_id);
	 		if (!empty($event))	{	
	 			$event->timestart		=	$data->$fieldname;
	 			$event->timemodified	=	time();	
	 			$event->modulename  	= 	'0';
	 			$event->uuid  			= 	0;
	 			$this->dbc->update_event($event);
	 		} 
	 	}
	 	
	 	
	 	

	 	//before saving save a event in the users calendar for this report
	 	//Add Calendar event
	 	
	 	//if () {
	        
	 	//} 
	 	
	 	
	 	//$data->user_id
	 	//data->report_id - for report name
	 	

	 	
	 	//call the parent entry_process_data function to handle saving the field value	 	
	 	return parent::entry_process_data($reportfield_id,$entry_id,$data);
		 	
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

	  	$fieldname	=	$reportfield_id."_field";
	 	
	 	$entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id);
 	
	 	if (!empty($entry)) {
	 		
	 		//we can only judge whether a report entry is overdue or not if the report has a state field if it does not 
	 		//we can never correctly say that the entry is overdue. 
	 		
	 		$entryrecord	=	$this->dbc->get_entry_by_id($entry_id);
	 		//check if current report has state field
	 		$has_statefield 	= $this->dbc->has_plugin_field($entryrecord->report_id,'ilp_element_plugin_state');
	 		$img	=	"";
	 		
	 		if (!empty($has_statefield))	{ 
	 			//check if the entry is in a unset state 
				$recordstate	=	$this->dbc->count_report_entries_with_state($entryrecord->report_id,$entryrecord->user_id,ILP_STATE_UNSET,false,$entry_id);
	 			if (!empty($recordstate) && $entry->value < time()) {
  			 		$img	=	 "<img src='{$CFG->wwwroot}/blocks/ilp/pix/icons/overdue.jpg' alt='' width='32px' height='32px' />";
	 			} 
	 		}
	 		$entryobj->$fieldname	=	userdate(html_entity_decode($entry->value),'%a %d %B %Y') ." ".$img;
	 	}
	  	
	 }
	 
   	/**
     * Delete a form element
     */
    public function delete_form_element($reportfield_id) {
    	return parent::delete_form_element($this->tablename, $reportfield_id);
    }
	 
}

