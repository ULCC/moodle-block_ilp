<?php

global $CFG;

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');

/**
 * Class ilp_element_plugin_html_editor
 */
class ilp_element_plugin_html_editor extends ilp_element_plugin {

    /**
     * @var string
     */
    public $tablename;
    /**
     * @var string
     */
    public $data_entry_tablename;
    /**
     * @var
     */
    public $minimumlength;		//defined by the form creator to validate user input
    /**
     * @var
     */
    public $maximumlength;		//defined by the form creator to validate user input

    /**
     * Constructor
     */
    function __construct() {
        $this->tablename = "block_ilp_plu_hte";
        $this->data_entry_tablename = "block_ilp_plu_hte_ent";

        parent::__construct();
    }

    /**
     * TODO comment this
     *
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
                return true;
            }
        }
        return false;
    }	


    /**
     *
     */
    public function install() {

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
        $table_minlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $table->addField($table_minlength);

        $table_maxlength = new $this->xmldb_field('maximumlength');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
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

        $table_key = new $this->xmldb_key('htmleditorplugin_unique_reportfield');
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
        return get_string('ilp_element_plugin_html_editor_type','block_ilp');
    }

    /**
     * function used to return the language strings for the plugin
     */
    static function language_strings(&$string) {
        $string['ilp_element_plugin_html_editor'] 		= 'Htmleditor';
        $string['ilp_element_plugin_html_editor_type'] = 'HTML editor';
        $string['ilp_element_plugin_html_editor_description'] = 'A html editor';
        $string['ilp_element_plugin_html_editor_minimumlength'] = 'Minimum Length';
        $string['ilp_element_plugin_html_editor_maximumlength'] = 'Maximum Length';
        $string['ilp_element_plugin_html_editor_maxlengthrange'] = 'The maximum length field must have a value between 0 and 255';
        $string['ilp_element_plugin_html_editor_maxlessthanmin'] = 'The maximum length field must have a greater value than the minimum length';

        return $string;
    }

    /**
     * Delete a form element
     */
    public function delete_form_element($reportfield_id, $tablename=null, $extraparams=null) {
        return parent::delete_form_element( $reportfield_id, $this->tablename);
    }

    /**
     * this function returns the mform elements that will be added to a report form
     *
     */
    public	function entry_form(MoodleQuickForm &$mform ) {
        global $DB;
        //create the fieldname
        $fieldname	=	"{$this->reportfield_id}_field";

        if (!empty($this->description)) {
            $mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description,
                                                                                                          ENT_QUOTES,
                                                                                                          'UTF-8'),ILP_STRIP_TAGS_DESCRIPTION));
            $this->label = '';
        }
        //text field for element label
        $mform->addElement(
               // To solve the validation error 'htmleditor' should be replaced to 'editor'.
               'editor',
                $fieldname,
                "$this->label",
                array('class' => 'form_input', 'canUseHtmlEditor'=>'detect', 'rows'=> '20', 'cols'=>'65')
        );
        $my_entry = $DB->get_record('block_ilp_plu_hte',array('reportfield_id'=>$this->reportfield_id));
        if ($my_entry && isset($_GET['entry_id'])){
            $my_entry_data = $DB->get_record('block_ilp_plu_hte_ent', array('parent_id'=>$my_entry->id, 'entry_id'=>$_GET['entry_id']));
            if($my_entry_data){
                $mform->setDefault($fieldname, array('text'=>html_entity_decode($my_entry_data->value, ENT_QUOTES, 'UTF-8'), 'format'=>FORMAT_HTML));
            }
        }

        $mform->setType($fieldname, PARAM_RAW);

        // Disable the min and max length to solve the validation issue for html editor and also
        // remove the same in mform
        // REF: http://tracker.moodle.org/browse/MDL-35402 is fixed as multiple rules is breaking it.
        //if (!empty($this->minimumlength)) $mform->addRule($fieldname, null, 'minlength', $this->minimumlength, 'client');
        //if (!empty($this->maximumlength)) $mform->addRule($fieldname, null, 'maxlength', $this->maximumlength, 'client');
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'server');


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

    /**
     * This function saves the data entered on a entry form to the plugins _entry table
     * the function expects the data object to contain the id of the entry (it should have been
     * created before this function is called) in a param called id.
     */
    public function entry_process_data($reportfield_id,$entry_id,$data) {

        //check to see if a entry record already exists for the reportfield in this plugin

        //create the fieldname
        $fieldname =	$reportfield_id."_field";

        //get the plugin table record that has the reportfield_id
        $pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
        if (empty($pluginrecord)) {
            print_error('pluginrecordnotfound');
        }

        //get the _entry table record that has the pluginrecord id
        $pluginentry 	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id);

        $my_data = (object) $data->$fieldname;
        //if no record has been created create the entry record
        if (empty($pluginentry)) {
            $pluginentry	=	new stdClass();
            $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
            $pluginentry->entry_id = $entry_id;
            $pluginentry->value	=	$my_data->text;
            $pluginentry->parent_id	=	$pluginrecord->id;
            $result	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
        } else {
            //update the current record
            $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
            $pluginentry->value	=	$my_data->text;
            $result	= $this->dbc->update_plugin_entry($this->data_entry_tablename,$pluginentry);
        }

        return (!empty($result)) ? true: false;
    }
}

