<?php

    require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');

class ilp_element_plugin_datefield extends ilp_element_plugin {

    public $tablename;
    public $data_entry_tablename;
    public $datetype;
    public $scalendar;
    public $ucalendar;
    public $reminder;
    public $emailsent;


    /**
     * Constructor
     */
    function __construct() {
        $this->tablename = "block_ilp_plu_datf";
        $this->data_entry_tablename = "block_ilp_plu_datf_ent";
        parent::__construct();
    }


    /** Function to set data when user form is submitted
     * @param int $reportfield_id - the id of the reportfield
     * @return bool false if data for given reportfield does not exist, otherwise true
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
                $this->req			    =	$reportfield->req;
                $this->position			=	$reportfield->position;
                $this->datetype		    =	$pluginrecord->datetype;
                $this->scalendar        =   $pluginrecord->scalendar;
                $this->ucalendar        =   $pluginrecord->ucalendar;
                $this->reminder		    =	$pluginrecord->reminder;

                return true;
            }
        }
        return false;
    }



    /**
     * Function used to create tables for this plugin
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

        $table_datetype = new $this->xmldb_field('datetype');
        $table_datetype->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $table->addField($table_datetype);

        $table_scalendar = new $this->xmldb_field('scalendar');
        $table_scalendar->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $table->addField($table_scalendar);

        $table_ucalendar = new $this->xmldb_field('ucalendar');
        $table_ucalendar->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $table->addField($table_ucalendar);

        $table_reminder = new $this->xmldb_field('reminder');
        $table_reminder->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $table->addField($table_reminder);

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

        $table_emailsent = new $this->xmldb_field('emailsent');
        $table_emailsent->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, null,0);
        $table->addField($table_emailsent);


        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key($this->tablename.'_foreign_key');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), $this->tablename ,'id');
        $table->addKey($table_key);

        $table_index = new $this->xmldb_index('datf_entry');
        $table_index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('entry_id'));
        $table->addIndex($table_index);

        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }



    //check whether plugins to be deleted exist- bool true or false
        $check_ddl = $DB->get_record_sql('select * from {block_ilp_plugin} where tablename =:table',array("table"=>'block_ilp_plu_ddl'));
        $check_dat = $DB->get_record_sql('select * from {block_ilp_plugin} where tablename =:table',array("table"=>'block_ilp_plu_dat'));


     if ($check_ddl==true){
    //DATE DEADLINE
        //retrieve records from ddl table
        $ddl_records = $DB->get_records_sql	(
                             'SELECT     *
                              FROM      {block_ilp_plu_ddl}', array(null));


        foreach($ddl_records as $ddl){
            $datf				   = new stdClass();
            $datf->reportfield_id  = $ddl->reportfield_id;
            $datf->datetype        = 1;
            $datf->scalendar       = 1;
            $datf->ucalendar       = 0;
            $datf->reminder        = 0;
            $datf->timemodified    = $ddl->timemodified;
            $datf->timecreated     = $ddl->timecreated;

            $id =  $ddl->id; //id of record from old table

            //insert record to datf, return id of new entry, which will be a parent_id for datf_ent
            $parent_id = $DB->insert_record('block_ilp_plu_datf', $datf);


            //retrieve records from ddl_ent table that match the $id of ddl table
            $ddl_all = $DB->get_records_sql(
                             'SELECT    *, ent.timemodified AS enttimemod, ent.timecreated AS enttimecr
                              FROM      {block_ilp_plu_ddl} r,
                                        {block_ilp_plu_ddl_ent} ent
                              WHERE     r.id = ent.parent_id
							  AND		r.id = :id', array('id'=>$id));


            foreach($ddl_all as $ddl_ent){
                $datf_ent 			     = new stdClass();
                $datf_ent->value         = $ddl_ent->value;
                $datf_ent->entry_id      = $ddl_ent->entry_id;
                $datf_ent->parent_id     = $parent_id;
                $datf_ent->timemodified  = $ddl_ent->enttimemod;
                $datf_ent->timecreated   = $ddl_ent->enttimecr;
                $datf_ent->emailsent     = ($datf_ent->value<time())? 1:0;

                // insert record to datf_ent
                $DB->insert_record('block_ilp_plu_datf_ent', $datf_ent);
            }
        }
        //delete dll plugin
        $DB->delete_records( 'block_ilp_plugin', array('tablename'=>'block_ilp_plu_ddl'),array());

     }

     if ($check_dat==true){
      //DATE
        //retrieve records from dat table
        $dat_records = $DB->get_records_sql	(
                             'SELECT     *
                              FROM      {block_ilp_plu_dat}', array(null));


        foreach($dat_records as $dat){
            $datf				   = new stdClass();
            $datf->reportfield_id  = $dat->reportfield_id;
            $datf->datetype        = 0;
            $datf->scalendar       = 0;
            $datf->ucalendar       = 0;
            $datf->reminder        = 0;
            $datf->timemodified    = $dat->timemodified;
            $datf->timecreated     = $dat->timecreated;

            $id =  $dat->id; //id of record from old table

            //insert record to datf, return id of new entry, which will be a parent_id for datf_ent
            $parent_id = $DB->insert_record('block_ilp_plu_datf', $datf);

            //retrieve records from dat_ent table that match the $id of ddl table
            $dat_all = $DB->get_records_sql(
                             'SELECT    *, value, entry_id, ent.timemodified AS enttimemod, ent.timecreated AS enttimecr
                              FROM      {block_ilp_plu_dat} r,
                                        {block_ilp_plu_dat_ent} ent
                              WHERE     r.id = ent.parent_id
							  AND		r.id = :id', array('id'=>$id));


            foreach($dat_all as $dat_ent){
                $datf_ent			     = new stdClass();
                $datf_ent->value         = $dat_ent->value;
                $datf_ent->entry_id      = $dat_ent->entry_id;
                $datf_ent->parent_id     = $parent_id;
                $datf_ent->timemodified  = $dat_ent->enttimemod;
                $datf_ent->timecreated   = $dat_ent->enttimecr;
                $datf_ent->emailsent     = 0;

                // insert record to datf_ent
                $DB->insert_record('block_ilp_plu_datf_ent', $datf_ent);
            }
        }
        //delete add plugin
        $DB->delete_records( 'block_ilp_plugin', array('tablename'=>'block_ilp_plu_dat'),array());
     }
    }


    /** Function used to update plugin_id to which reportfield points
     * @param int $plugin_id  - the id of new plugin
     * @return bool|void
     */
    public function after_install($plugin_id){

        global $DB;


        //retrieve records from report_field table that match the $reportfield_id of datf table
        $report_fields = $DB->get_records_sql(
            'SELECT    *, rf.id as id
                              FROM      {block_ilp_report_field} rf,
                                        {block_ilp_plu_datf} r
                              WHERE     rf.id = r.reportfield_id', array(null));


        foreach($report_fields  as $rf){
            $plug_upd			     = new stdClass();
            $plug_upd->id    		 = $rf->id;
            $plug_upd->plugin_id     = $plugin_id;

            // update ilp_report_field table with new plugin id
            $DB->update_record('block_ilp_report_field',$plug_upd);

        }

    }

    /**
     * Function used to drop all tables created for this plugin
     */
    public function uninstall() {
        $table = new $this->xmldb_table( $this->data_entry_tablename );
        drop_table($table);

        $table = new $this->xmldb_table( $this->tablename );
        drop_table($table);
    }


    public function audit_type() {
        return get_string('ilp_element_plugin_datefield_type','block_ilp');
    }


    /** Function used to return the language strings for this plugin
     * @param $string
     * @return array $string with language strings
     */
    static function language_strings(&$string) {
        $string['ilp_element_plugin_datefield'] 		        = 'Date field';
        $string['ilp_element_plugin_datefield_type'] 	        = 'Date field';
        $string['ilp_element_plugin_datefield_description'] 	= 'A date field element';
        $string['ilp_element_plugin_datefield_date']            = 'Date';
        $string['ilp_element_plugin_datefield_deadline']        = 'Deadline';
        $string['ilp_element_plugin_datefield_reviewdate']      = 'Review Date';
        $string['ilp_element_plugin_datefield_datetype']        = 'Date type';
        $string['ilp_element_plugin_datefield_calendar']        = 'Calendar';
        $string['ilp_element_plugin_datefield_scalendar']       = 'Student calendar';
        $string['ilp_element_plugin_datefield_ucalendar']       = 'User calendar';
        $string['ilp_element_plugin_datefield_reminder']        = 'Reminder';
        $string['review']                                       = 'review';

        return $string;
    }


    /** Function used to delete a form element from the report
     * @param int $reportfield_id - the id of the reportfield
     */
    public function delete_form_element($reportfield_id, $tablename=null, $extraparams=null) {
        return parent::delete_form_element( $reportfield_id, $this->tablename);
    }


    /** Function that returns the mform elements that will be added to a report form
     * @param $mform
     */
    public	function entry_form(&$mform) {
        //create the fieldname
        $fieldname	=	"{$this->reportfield_id}_field";

        if (!empty($this->description)) {
            $mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description,
                                                                                                          ENT_QUOTES,
                                                                                                          'UTF-8'),ILP_STRIP_TAGS_DESCRIPTION));
            $this->label = '';
        }

        // date selector element for picking up the date
        $mform->addElement(
            'date_selector',
            $fieldname,
            $this->label,
            array('class' => 'form_input', 'optional' => false )
        );

        //@todo decide correct PARAM type for date element
        $mform->setType($fieldname, PARAM_RAW);

        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'server');
    }



    function entry_process_data($reportfield_id, $entry_id, $data)	{
        return $this->entry_specific_process_data($reportfield_id, $entry_id, $data);
    }


    /**  Function used to handle user input
     * This function checks whether user's or/and student's calendars were selected and create/update calendar events
     * accordingly
     * @param int $reportfield_id  - id of the reportfield
     * @param int $entry_id        - id of the entry record
     * @param array $data          - the element data
     */
    public	function entry_specific_process_data($reportfield_id, $entry_id, $data) {
        global $CFG;

        /*
          * parent method is fine for simple form element types
          * dd types will need something more elaborate to handle the intermediate
          * items table and foreign key
          */

        $fieldname  =	$reportfield_id."_field";

        $report		=	$this->dbc->get_report_by_id($data->report_id);

        $event	    =	$this->dbc->get_calendar_event($entry_id,$reportfield_id);

        //get datetype 1-deadline, 2-review
        $datetype  = $this->datetype;
        //check whether student calendar was selected 0-no, 1-yes
        $scalendar = $this->scalendar;
        //check whether user calendar was selected 0-no, 1-yes
        $ucalendar = $this->ucalendar;

        //set report name(title) for the report depending on its type
        if ($datetype==1){
            $title		=	(!empty($report))	? $report->name." ".get_string('deadline','block_ilp') : get_string('deadline','block_ilp');
        }
        elseif ($datetype==2){
            $title		=	(!empty($report))	? $report->name." ".get_string('review','block_ilp') : get_string('review','block_ilp');
        }

        //count how many times code for creating events should run (once for each calendar selected)
        $loops = $scalendar + $ucalendar;

        $course_id_param = '&course_id=';
        if (isset($this->course_id)) {
            $course_id_param .= $this->course_id;
        }

        $tabitem_param = '';
        $selectedtab_param = '';
        $tab_plugins = $this->dbc->get_tab_plugins();
        if ($tab_plugins) {
            foreach ($tab_plugins as $tab_plugin) {
                if ($tab_plugin->name == 'ilp_dashboard_reports_tab') {
                    break;
                }
            }
            $report_tab_id = $tab_plugin->id;
            $tabitem_param = '&tabitem=' . $report_tab_id . ':' . $data->report_id;
            $selectedtab_param = '&selectedtab=' . $report_tab_id;
        }
        $ilp_profile_url = $CFG->wwwroot . '/blocks/ilp/actions/view_main.php?user_id=' . $data->user_id . $course_id_param . $tabitem_param . $selectedtab_param;
        $ilp_profile_link = '<a href="' . $ilp_profile_url . '" class="ilp_cal_profile_link">' . $title . '</a>';

      if ($datetype=!0){
        //if event empty (false), create it
        if (empty($event)) {
            //run the code to create an event, loop will run twice if both calendars (student & user) are selected
            for ($i = 0; $i < $loops ; $i++) {
                global $USER;
                $event = new stdClass();
                $event->name        = $title;
                //link to ilp has been removed due to moodle encoding html and outputing it.
                $event->description = $ilp_profile_link;
                $event->format      = 0;
                $event->courseid    = 0;
                $event->groupid     = 0;
                //check the userid for the 1st event, (if two events are created, 2nd event always will be $USER->id)
                ($scalendar == 1)?$event->userid = $data->user_id : $event->userid = $USER->id;
                $event->modulename  = '0';
                $event->instance    = 0;
                $event->eventtype   = 'due';
                $event->timestart   = $data->$fieldname;
                $event->timeduration = 0;
                $event->id = $this->dbc->save_event($event);

                $this->dbc->update_event_description($event, $ilp_profile_link);
                $record					=	new stdClass();
                $record->entry_id		=	$entry_id;
                $record->reportfield_id	=	$reportfield_id;
                $record->event_id		=	$event->id;
                $record->timemodified	=	time();
                $record->timecreated	=	time();

                //create the calendar cross reference record
                $this->dbc->create_event_cross_reference($record);

                //set student user to 0, so when loop run for the 2nd time, the user will be $USER->id
                $scalendar = 0;

            }
        }  else	{
            $event	=	$this->dbc->get_calendar_events($entry_id, $reportfield_id);
                // update events
                if (!empty($event))	{
                    foreach($event as $ev) {
                    $ev->timestart		=	$data->$fieldname;
                    $ev->timemodified	=	time();
                    $ev->modulename  	= 	'0';
                    $ev->uuid  			= 	0;
                    $this->dbc->update_event($ev);
                 }
            }
         }
      }
     //call the parent entry_process_data function to handle saving the field value
     return parent::entry_process_data($reportfield_id, $entry_id, $data);
    }


    /** Function used to delete an entry record and event instance(s)
     * @param int $entry_id - the id of an entry
     */
    public function delete_entry_record($entry_id) {

       if($cal_events = $this->dbc-> get_calevent_reportfield_id($entry_id))
       {
          $event	=	$this->dbc->get_calendar_events($entry_id, $cal_events->reportfield_id);

          if (!empty($event))	{
             foreach($event as $ev) {
                $this->dbc->delete_event_entry($ev->id);
             }
          }
          // call to the parent delete_entry_record to delete an entry record
          return parent::delete_entry_record($entry_id);
       }
       return 0;
    }


    /** Function used to delete a report form and all events that depend on that report
     * In fact, the report form and dependant events are set to invisible
     * @param int $report_id - the id of the report
     */

    public function delete_report($report_id){

        $entry = $this->dbc->get_entries_by_report_id($report_id);

        foreach($entry as $e) {

        $cal_events = $this->dbc-> get_calevent_reportfield_id($e->id);


            $event	=	$this->dbc->get_calendar_events($e->id, $cal_events->reportfield_id);

            //setting report events as invisible
            foreach($event as $ev) {
                if (!empty($event))	{
                    $ev->timemodified	=	time();
                    $ev->modulename  	=  '0';
                    $ev->visible 	    = 	0;
                    $ev->uuid  			= 	0;
                    $this->dbc->update_event($ev);
                }
             }
        }
        //call to parent delete_report to set a report as invisible
        return parent::delete_report($report_id);
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
     * @param bool returnvalue should a label or value be returned
     */
    public function view_data( $reportfield_id,$entry_id,&$entryobj, $returnvalue = false){
global $CFG;
        $fieldname	=	$reportfield_id."_field";

        $entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id);
        if (!empty($entry)) {
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
            $entryobj->$fieldname	=	userdate(html_entity_decode($entry->value,
                                                                      ENT_QUOTES,
                                                                      'UTF-8'),'%a %d %B %Y')." ".$img;
        }

    }


}