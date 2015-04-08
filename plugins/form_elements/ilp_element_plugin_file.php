<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');



class ilp_element_plugin_file extends ilp_element_plugin {
	
	public $tablename;
	public $data_entry_tablename;
	public $acceptedtypes;
    public $maxsize;
    public $maxfiles;
    public $multiple;
	
	 /**
     * Constructor
     */
    function __construct() {
    	
    	$this->tablename = "block_ilp_plu_file";
    	$this->data_entry_tablename = "block_ilp_plu_file_ent";
    	
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
				$this->req			    =	$reportfield->req;
				$this->acceptedtypes	=	unserialize(base64_decode($pluginrecord->acceptedtypes));
                $this->maxsize          =   $pluginrecord->maxsize;
                $this->multiple         =   $pluginrecord->multiple;
                $this->maxfiles         =   $pluginrecord->maxfiles;
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
        
        $table_acceptedtypes = new $this->xmldb_field('acceptedtypes');
        $table_acceptedtypes->$set_attributes(XMLDB_TYPE_TEXT, 1500, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_acceptedtypes);

        $table_maxsize = new $this->xmldb_field('maxsize');
        $table_maxsize->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxsize);

        $table_maxfiles = new $this->xmldb_field('maxfiles');
        $table_maxfiles->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxfiles);

        $table_multiple = new $this->xmldb_field('multiple');
        $table_multiple->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_multiple);

        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('fileplugin_unique_reportfield');
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
     * drop tables
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
        return get_string('ilp_element_plugin_file_type','block_ilp');
    }


    
    /**
    * function used to return the language strings for the plugin
    */
    static function language_strings(&$string) {
        $string['ilp_element_plugin_file'] 		         = 'File upload';
        $string['ilp_element_plugin_file_type']          = 'File upload';
        $string['ilp_element_plugin_file_description']   = 'A file upload';
        $string['ilp_element_plugin_file_acceptedfiles'] = 'Accepted types';
        $string['ilp_element_plugin_file_maxsize']       = 'Maximum file size';
        $string['ilp_element_plugin_file_multiple']      = 'Multiple Files';
        $string['ilp_element_plugin_file_maxfiles']      = 'Maximum Files (if multiple files selected)';

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
    	return parent::delete_form_element($reportfield_id,$this->tablename,$extraparams);
    }

     /**
    * this function returns the mform elements that will be added to a report form
	*
    */
    public	function entry_form( &$mform ) {

        $fieldname	=	"{$this->reportfield_id}_field";
    	if (!empty($this->description)) {
    		$mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description,
                                                                                                          ENT_QUOTES,
                                                                                                          'UTF-8'),ILP_STRIP_TAGS_DESCRIPTION));
    		$this->label = '';
    	}

            $max_files = (empty($this->multiple)) ? 1 : $this->maxfiles;
            $filemanager_config = array('subdirs' => 0,
                                      'maxbytes' => $this->maxsize,
                                      'maxfiles' => $max_files );
            if (!in_array('all', $this->acceptedtypes)) {
                $filemanager_config['accepted_types'] = $this->acceptedtypes;
            }

            $mform->addElement('filemanager',
                                $fieldname,
                                $this->label,
                                null,
                                $filemanager_config
                              );

        if (!empty($this->required)) $mform->addRule($fieldname, null, 'required', null, 'client');
        if (!empty($this->required)) $mform->addRule($fieldname, null, 'required', null, 'server');
	 }


   function entry_process_data($reportfield_id,$entry_id,$data)   {
       return $this->entry_specific_process_data($reportfield_id,$entry_id,$data);
   }

	/**
	* handle user input
	**/
	 public	function entry_specific_process_data($reportfield_id,$entry_id,$data) {
         global $USER;

		$fieldname =	$reportfield_id."_field";

         //get the value for this element in the data returned. The value is the id of the files save location
         $draftid = $data->$fieldname;

         if (!empty($draftid) )  {

             //instantiate file storage
             $fs = get_file_storage();

             //get the current users context as this is where the file will have been saved
             $context = context_user::instance($USER->id);

             //check if the file exists
             if ($files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
                 //get system context as this is the area of file storage we will be saving the file into
                 $sitecontext   =   context_system::instance();

                 $test = file_save_draft_area_files($draftid,$sitecontext->id,'form_elements','ilp_element_plugin_file',$draftid);

                 return parent::entry_process_data($reportfield_id,$entry_id,$data);
             }

             return true;
         } else {
             return true;
         }
	 }


    public function return_data(&$data)   {
        $data->acceptedtypes    =   unserialize(base64_decode($data->acceptedtypes));
    }

    public function entry_data( $reportfield_id,$entry_id,&$entryobj ){

        //default entry_data
        $fieldname	=	$reportfield_id."_field";

        $entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id);

        if (!empty($entry)) {
            $entryobj->$fieldname	=	html_entity_decode($entry->value, ENT_QUOTES, 'UTF-8');

            $sitecontext   =   context_system::instance();

            //prepare the file to be used
            file_prepare_draft_area($entryobj->$fieldname, $sitecontext->id, 'form_elements', '', $entryobj->$fieldname);
        }
    }


    public function view_data($reportfield_id, $entry_id, &$entryobj, $returnvalue=false){
        global $CFG;

        $fieldname	=	$reportfield_id."_field";

        $sitecontext   =   context_system::instance();

        $fs = get_file_storage();

        $entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id);
        if(!empty($entry)){

        $entryobj->$fieldname	=	html_entity_decode($entry->value, ENT_QUOTES, 'UTF-8');

        $files = $fs->get_area_files($sitecontext->id, 'form_elements', 'ilp_element_plugin_file',$entryobj->$fieldname);

        $list = array();

        foreach ($files as $file) {
            if ($file->get_filename() !== '.')   {
                $url = "{$CFG->wwwroot}/blocks/ilp/plugins/form_elements/ilp_element_plugin_file/filedownloads.php/{$file->get_contextid()}/form_elements/ilp_element_plugin_file";
                $filename = $file->get_filename();
                $fileurl = $url.$file->get_filepath().$file->get_itemid().'/'.$filename;
                $out[] = html_writer::link($fileurl, $filename);
            }
        }

        $br = html_writer::empty_tag('br');

        $entryobj->$fieldname   =   implode($br, $out);
        }
    }

    public function is_exportable()
    {
       return false;
    }
}
