<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist.class.php');

class ilp_element_plugin_warningstatus extends ilp_element_plugin_itemlist{

    public $tablename;
    public $data_entry_tablename;
    public $optionlist_keyfield;
    public $selecttype;
    public $user_id;

    /**
     * Constructor
     */
    function __construct() {
        $this->tablename = "block_ilp_plu_wsts";
        $this->data_entry_tablename =  "block_ilp_plu_wsts_ent";
        $this->userstatus_tablename = "block_ilp_plu_wsts_ent";
        $this->items_tablename = "block_ilp_plu_wsts_items";
        $this->optionlist_keyfield = "status_id";

        $this->selecttype = ILP_OPTIONSINGLE;
        parent::__construct();
    }

    public function set_user_id($user_id) {
        $this->user_id = $user_id;
    }
    /**
     * Loads the data for this field
     *
     * @param $reportfield_id
     * @return bool
     */
    public function load($reportfield_id) {
        $reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
        if (!empty($reportfield)) {
            $this->reportfield_id	=	$reportfield_id;
            $this->plugin_id		=	$reportfield->plugin_id;
            $plugin					=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
            $pluginrecord			=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield->id);
            if (!empty($pluginrecord)) {
                $this->id			    =	$pluginrecord->id;
                $this->savetype			=	$pluginrecord->savetype;
                $this->label			=	$reportfield->label;
                $this->description		=	$reportfield->description;
                $this->req			    =	$reportfield->req;
                $this->position			=	$reportfield->position;
            }
        }
        return false;
    }


    /**
     * places entry data for the report field given into the entryobj given by the user
     *
     * @param int $reportfield_id the id of the reportfield that the entry is attached to
     * @param int $entry_id the id of the entry
     * @param object $entryobj an object that will add parameters to
     */
    public function entry_data( $reportfield_id,$entry_id,&$entryobj ){


        //default entry_data
        $fieldname	=	$reportfield_id."_field";

        $entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id,true);
        if (!empty($entry)) {
            $fielddata	=	array();
            //loop through all of the data for this entry in the particular entry
            foreach($entry as $e) {
                $fielddata[]	=	$e->parent_id;
            }
            //save the data to the objects field
            $entryobj->$fieldname	=	$fielddata;
        }
    }

    /**
     * Deletes a form element and any items that it may have
     *
     *  @param int $reportfield_id the id of the reportfield
     */
    public function delete_form_element($reportfield_id, $tablename=null, $extraparams=null) {
        //get the record for the field
        $pluginrecord			=	$this->dbc->get_form_element_by_reportfield($this->tablename,$reportfield_id);

        //also delete any submitted data - it'll survive in ghostly form in the log table
        $this->dbc->delete_items($this->data_entry_tablename,$pluginrecord->id);

        $reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
        $extraparams = array(
            'audit_type' => $this->audit_type(),
            'label' => $reportfield->label,
            'description' => $reportfield->description,
            'id' => $reportfield_id
        );
        return parent::delete_form_element($this->tablename, $reportfield_id, $extraparams);
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
    public function view_data( $reportfield_id,$entry_id,&$entryobj, $returnvalue=false ){
        $fieldname	=	$reportfield_id."_field";

        $entry	=	$this->dbc->get_entry_data($this->data_entry_tablename,'user_id',$entryobj->user_id);

        if (!empty($returnvalue)) $entryobj->$fieldname = array();
        if (!empty($entry)) {
            $comma	= "";
            //loop through all of the data for this entry in the particular entry
            foreach($entry as $e) {
                $name = $this->dbc->get_warning_status_name($e->value);
                if (empty($returnvalue)) {
                    $entryobj->$fieldname	.=	"{$comma}{$name}";
                    $comma	=	",";
                } else {
                    array_push($entryobj->$fieldname,$name);
                }

            }
        }

    }


    public function install(){

        // create the table to store report fields
        $table = new $this->xmldb_table( $this->tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_report = new $this->xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null);
        $table->addField($table_report);

        //1=single, 2=multi cf blocks/ilp/constants.php
        $table_optiontype = new $this->xmldb_field('selecttype');
        $table_optiontype->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, null);
        $table->addField($table_optiontype);

        //0= save to sts_ent, 2= save to userstatus (update user status)
        $table_optiontype = new $this->xmldb_field('savetype');
        $table_optiontype->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, null);
        $table->addField($table_optiontype);

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

        $table = new $this->xmldb_table( $this->items_tablename );

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_textfieldid = new $this->xmldb_field('parent_id');
        $table_textfieldid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_textfieldid);

        $table_itemvalue = new $this->xmldb_field('value');
        $table_itemvalue->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
        $table->addField($table_itemvalue);

        $table_itemname = new $this->xmldb_field('name');
        $table_itemname->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
        $table->addField($table_itemname);

        $table_hexcolour = new $this->xmldb_field('hexcolour');
        $table_hexcolour->$set_attributes(XMLDB_TYPE_CHAR, 255, null);
        $table->addField($table_hexcolour);

        $table_icon = new $this->xmldb_field('icon');
        $table_icon->$set_attributes(XMLDB_TYPE_CHAR, 45, null);
        $table->addField($table_icon);

        $table_display_option = new $this->xmldb_field('display_option');
        $table_display_option->$set_attributes(XMLDB_TYPE_CHAR, 4, null);
        $table->addField($table_display_option);

        $table_description = new $this->xmldb_field('description');
        $table_description->$set_attributes(XMLDB_TYPE_CHAR, 255, null);
        $table->addField($table_description);

        $table_bg_colour = new $this->xmldb_field('bg_colour');
        $table_bg_colour->$set_attributes(XMLDB_TYPE_CHAR, 45, null);
        $table->addField($table_bg_colour);

        //special field to categorise states as pass or fail
        //0=unset,1=fail,2=pass
        $table_itempassfail = new $this->xmldb_field( 'passfail' );
        $table_itempassfail->$set_attributes( XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, '0', null, null, '0' );
        $table->addField( $table_itempassfail );

        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('listplugin_unique_fk');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), $this->tablename, 'id');
        $table->addKey($table_key);

        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }

        // create the new table to store responses to fields
        $table = new $this->xmldb_table( $this->data_entry_tablename );

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_maxlength = new $this->xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);

        $table_item_id = new $this->xmldb_field('value');	//foreign key -> $this->items_tablename
        $table_item_id->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_item_id);

        $table_report = new $this->xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);

        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_userid = new $this->xmldb_field('user_id');
        $table_userid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_userid);

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

    static function language_strings(&$string) {
        $string['ilp_element_plugin_status'] 			= 'Select';
        $string['ilp_element_plugin_status_type'] 		= 'Status select';
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

    /*
     * get the list options with which to populate the edit element for this list element
     */
    public function return_data( &$reportfield ){
        $data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $reportfield->id );
        if( empty( $data_exists ) ){
            //if no, get options list
            $options_data = $this->get_option_list_text( $reportfield->id, "\n", 'passfail' );
            $reportfield->optionlist = $options_data[ 'options' ];
        }
        else{
            $options_data = $this->get_option_list_text( $reportfield->id , '<br />', 'passfail' );
            $reportfield->existing_options = $options_data[ 'options' ];
        }
        $reportfield->fail = $options_data[ 'fail' ];
        $reportfield->pass = $options_data[ 'pass' ];
    }


    /*
    * get options from the items table for this plugin, and concatenate them into a string
    * @param int $reportfield_id
    * @param string $sep
    * @param string $field - optional additional field to retrieve, along with value and name
    */
    protected function get_option_list_text( $reportfield_id , $sep="\n", $field=false ){
        $option_data = $this->get_option_list( $reportfield_id, $field );
        $optionlist = $option_data[ 'optlist' ];
        $rtn = '';
        if( !empty( $optionlist ) ){
            foreach( $optionlist as $key=>$value ){
                $rtn .= "$key:$value$sep";
            }
        }
        return array(
            'options' => $rtn,
            'optionlist' => $optionlist,
            'pass' => implode( $sep, $option_data[ 'pass' ] ),
            'fail' => implode( $sep, $option_data[ 'fail' ] ),
            'objlist' => $option_data[ 'objlist' ]
        );
    }


    /*
    * read rows from item table and return them as array of key=>value
    * @param int $reportfield_id
    * @param string $field - the name of a extra field to read from items table: used by ilp_element_plugin_state
    */
    public function get_option_list( $reportfield_id, $field = false, $useid = true ){
        //return $this->optlist2Array( $this->get_optionlist() );
        $outlist = array();
        $passlist = array();
        $faillist = array();
        if( $reportfield_id ){
            //get the list of options for this reportfield in the given table from the db
            //$objlist = $this->dbc->get_optionlist($reportfield_id , $this->tablename, $field );
            $objlist = $this->dbc->listelement_item_exists( $this->items_tablename, array( 'parent_id' => ILP_DEFAULT_USERSTATUS_RECORD ) );

            foreach( $objlist as $obj ){
                //place the name into an array with value as key
                $outlist[ $obj->value ] = $obj->name;

                //if the the name of the extra field is passfail then
                if( 'passfail' == $field ){
                    //if the field value is fail add to fail list
                    if( ILP_STATE_FAIL == $obj->passfail ){
                        $faillist[] = $obj->name;
                    }
                    if( ILP_STATE_PASS == $obj->passfail ){
                        $passlist[] = $obj->name;
                    }
                }
            }
        }

        if( !count( $outlist ) ){
            //echo "no items in {$this->items_tablename}";
        }

        $adminvalues = array(
            'objlist' => $objlist,
            'optlist' => $outlist,
            'pass' => $passlist,
            'fail' => $faillist
        );

        //we only need to return the admin values if the $field value is not false (it should be set to passfail to get admin values)
        return (!empty($field)) ? $adminvalues : $outlist;
    }


    /**
     * this function saves the data entered on a entry form to the plugins _entry table
     * the function expects the data object to contain the id of the entry (it should have been
     * created before this function is called) in a param called id.
     * as this is a select element, possibly a multi-select, we have to allow
     * for the possibility that the input is an array of strings
     */
    public	function entry_process_data($reportfield_id,$entry_id,$data) {
        global  $USER;
        $result	=	true;

        //create the fieldname
        $fieldname =	$reportfield_id."_field";
        //get the plugin table record that has the reportfield_id
        $pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
        if (empty($pluginrecord)) {
            print_error('pluginrecordnotfound');
        }

        //get the id of the item chosen

        $ev         	        =	( !empty( $data->$fieldname ) ) ? $data->$fieldname : '' ;

        //get selected state item record from items table
        $state_item				=	$this->dbc->get_state_item_id($this->tablename,$pluginrecord->id,$ev, 'value', $this->items_tablename );

        if (!empty($state_item)){

            //delete the status record attached to this entry if it exists
            $this->dbc->delete_element_record_by_id( $this->data_entry_tablename, $data->user_id, array(), 'user_id' );

            //save the new status into the table
            $pluginentry     =   new stdClass();
            $pluginentry->parent_id	=	$state_item->id;
            $pluginentry->value 	= 	$state_item->value;
            $pluginentry->entry_id 	= 	-1;
            $pluginentry->user_id 	= 	$data->user_id;

            return 	$this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
        }

        return false;

    }

    /**
     * this function returns the mform elements that will be added to a report form
     *
     */
    public function entry_form( &$mform ) {

        //create the fieldname
        $fieldname	=	"{$this->reportfield_id}_field";

        //definition for user form
        $optionlist = $this->get_option_list( $this->reportfield_id );

        if (!empty($this->description)) {
            $mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description,
                                                                                                          ENT_QUOTES,
                                                                                                          'UTF-8'),ILP_STRIP_TAGS_DESCRIPTION));
            $this->label = '';
        }


        //text field for element label
        $select = &$mform->addElement(
            'select',
            $fieldname,
            $this->label,
            $optionlist,
            array('class' => 'form_input')
        );

        if( ILP_OPTIONMULTI == $this->selecttype ){
            $select->setMultiple(true);
        }


        $warning_status = $this->dbc->get_current_warning_status($this->user_id);
        if ($warning_status) {
            $select->setSelected($warning_status->value);
        }

        $fieldname  =   "id_".$fieldname;

        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        $mform->setType('label', PARAM_RAW);

    }


    protected function config_format_option_list( $list ){
        $sep = '<br />';
        return implode( $sep, array_values( $list ) );
    }

    /**
    textarea element to contain the options the admin wishes to add to the user form
    admin will be instructed to insert value/label pairs in the following plaintext format:
    value1:label1\nvalue2:label2\nvalue3:label3
    or some such
     */
    public function config_specific_definition(&$mform) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/filestorage/file_storage.php');
        require_once($CFG->dirroot.'/lib/filelib.php');
        //if any rows in status entry table, then data exists, so existing options should nt be editable
        $data_exists = $this->dbc->listelement_item_exists( $this->userstatus_tablename, array() );

        $info = $this->get_option_list_text( ILP_DEFAULT_USERSTATUS_RECORD , "\n", 'passfail' ) ;

        foreach( $info[ 'objlist' ] as $option ){
            $A = $mform->addElement(
                'text',
                'itemvalue_' . $option->id,
                'value',
                array('class' => 'form_input')
            );
            $A->setValue( $option->value );

            $B = $mform->addElement(
                'text',
                'itemname_' . $option->id,
                'label',
                array('class' => 'form_input')
            );
            $B->setValue( $option->name );

            $C = $mform->addElement(
                'text',
                'itemhexcolour_' . $option->id,
                'hex colour',
                array('class' => 'form_input')
            );

            $hexcolour 	= (isset($option->hexcolour)) ? $option->hexcolour : "";

            $C->setValue( $hexcolour );

            $description = $mform->addElement(
                'text',
                'description_' . $option->id,
                get_string('status_description','block_ilp'),
                array('class' => 'form_input')
            );
            $description->setValue( html_entity_decode($option->description, ENT_QUOTES, 'UTF-8'));

            $bg_colour = $mform->addElement(
                'text',
                'bg_colour_' . $option->id,
                get_string('bg_colour','block_ilp'),
                array('class' => 'form_input')
            );
            $bg_colour->setValue( ($option->bg_colour ));

            $status_display_option = array('text'=>'Text','icon'=>'Icon');
            $display_option = $mform->addElement('select', 'display_option_' . $option->id, get_string('display_option', 'block_ilp'), $status_display_option);
            $display_option->setSelected($option->display_option);

            // below code is for upload icon for status
            $icon_options = array('subdirs'=>0, 'maxbytes'=>$CFG->userquota, 'maxfiles'=>1, 'accepted_types'=>array('*.ico', '*.png', '*.jpg', '*.gif', '*.jpeg'));

            $context = context_system::instance();
            $component = 'block_ilp';
            $file_area = 'icon';
            $item_id = $option->id;

            $data = new stdClass();
            $data = file_prepare_standard_filemanager($data, $option->id . '_files', $icon_options, $context, $component, $file_area, $item_id);
            $icon4status = $mform->addElement('filemanager', $option->id . '_files_filemanager', get_string('change_icon', 'block_ilp'), null, $icon_options);
            $icon4status->setValue( $data->{$option->id . '_files_filemanager'} );
            //above code is for upload icon status.
            $mform->addElement( 'html', '<hr />');

            if (!$data_exists){
                $deleteurl = $CFG->wwwroot . '/blocks/ilp/actions/edit_status_items?delete_item&id=' . $option->id;
                $mform->addElement(
                    'static',
                    'delete_link',
                    '<a href="' . $deleteurl . '">X</a>'
                );
            }

        /*
        $mform->addElement(
            'static',
            'description',
            get_string( 'existing_options', 'block_ilp' ),
            $this->config_format_option_list( $info[ "optionlist" ] )
        );
        */
        }


        $E = $mform->addElement(
            'textarea',
            'optionlist',
            get_string( 'ilp_element_plugin_dd_optionlist_additional', 'block_ilp' ),
            array('class' => 'form_input')
        );

        $F = $mform->addElement(
            'textarea',
            'fail',
            get_string( 'ilp_element_plugin_state_fail', 'block_ilp' ),
            array('class' => 'form_input')
        );
        $F->setValue( $info[ 'fail' ] );
        $mform->setType('fail', PARAM_TEXT);

        $G = $mform->addElement(
            'textarea',
            'pass',
            get_string( 'ilp_element_plugin_state_pass', 'block_ilp' ),
            array('class' => 'form_input')
        );
        $G->setValue( $info[ 'pass' ] );
        $mform->setType('pass', PARAM_TEXT);


        //$mform->addRule('optionlist', null, 'minlength', 1, 'client');

    }
    protected function config_specific_validation($data) {
        $optionlist = array();
        if( in_array( 'optionlist' , array_keys( (array) $data ) ) ){
            $optionlist = ilp_element_plugin_itemlist::optlist2Array( $data[ 'optionlist' ] );
        }
        //all contents of $data->fail and $data->pass must match valid keys or values in $optionlist
        $sep = "\n";
        $keysep = ":";
        $fail_item_list = explode( $sep, $data[ 'fail' ] );
        $pass_item_list = explode( $sep, $data[ 'pass' ] );
        foreach( array( $fail_item_list, $pass_item_list ) as $item_list ){
            foreach( $item_list as $submitted_item ){
                if( trim( $submitted_item ) && !$this->is_valid_item( $submitted_item , $optionlist, $keysep ) ){
                    $this->errors[] = get_string( 'ilp_element_plugin_error_not_valid_item' , 'block_ilp' ) . ": <em>$submitted_item</em>";
                }
            }
        }
    }


    /*
     * take input from the management form and write the element info
     */
    protected function config_specific_process_data($data) {
        $optionlist = array();
        if( in_array( 'optionlist' , array_keys( (array) $data ) ) ){
            //dd type needs to take values from admin form and write them to items table
            $optionlist = ilp_element_plugin_itemlist::optlist2Array( $data->optionlist );
        }

        $sep = "\n";
        $keysep = ":";
        //entries from data to go into $this->tablename and $this->items_tablename

        $gradekeylist = array(
            'pass', 'fail'
        );
        foreach( $gradekeylist as $key ){
            $v = $key . '_list';
            $$v = explode( $sep, $data->$key );
            //deal with pesky whitespace
            foreach( $$v as &$entry ){
                $entry = trim( $entry );
                $entryparts = explode( $keysep , $entry );
                if( 1 < count( $entryparts ) ){
                    //admin has copied a whole key:value string into the pass or fail textarea
                    //so throw away the key
                    $entry = $entryparts[1];
                }
            }
        }
        //we now have 2 lists: $pass_list and $fail_list

        $plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record($this->tablename,$data->reportfield_id) : false;

        if (empty($plgrec)) {
            //options for this dropdown need to be written to the items table
            //each option is one row
            $element_id = $this->dbc->create_plugin_record($this->tablename,$data);

            //$itemrecord is a container for item data
            $itemrecord = new stdClass();
            $itemrecord->parent_id = $element_id;
            foreach( $optionlist as $key=>$itemname ){
                //one item row inserted here
                $itemrecord->value = $key;
                $itemrecord->name = $itemname;
                $itemrecord->passfail = $this->deducePassFailFromLists( array( $itemname, $key ), $fail_list, $pass_list );
                $this->dbc->create_plugin_record($this->items_tablename,$itemrecord);
            }
        } else {
            //get the old record from the elements plugins table
            $oldrecord				=	$this->dbc->get_form_element_by_reportfield($this->tablename,$data->reportfield_id);
            $data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $data->reportfield_id );
            $element_id = $this->dbc->get_element_id_from_reportfield_id( $this->tablename, $data->reportfield_id );
            //$itemrecord is a container for item data
            $itemrecord = new stdClass();
            $itemrecord->parent_id = $element_id;

            if( empty( $data_exists ) ){
                //no user data - go ahead and delete existing items for this element, to be replaced by the submitted ones in $data
                $delstatus = $this->dbc->delete_element_listitems( $this->tablename, $data->reportfield_id );
                //if $delstatus false, there has been an error - alert the user
            } else {
                //user data has been submitted already - don't delete existing items, but add new ones if they are in $data
                //purge $optionlist of already existing item_keys
                //then it will be safe to write the items to the items table
                foreach( $optionlist as $key=>$itemname ){
                    if( $this->dbc->listelement_item_exists( $this->items_tablename, array( 'parent_id' => $element_id, 'value' => $key ) ) ){
                        //this should never happen, because it shouldn't have passed validation, but you never know
                        unset( $optionlist[ $key ] );
                        //alert the user
                    }
                }
            }
            //now write fresh options from $data
            foreach( $optionlist as $key=>$itemname ){
                //one item row inserted here
                $itemrecord->value = $key;
                $itemrecord->name = $itemname;
                $itemrecord->passfail = $this->deducePassFailFromLists( array( $itemname, $key ), $fail_list, $pass_list );
                $this->dbc->create_plugin_record($this->items_tablename,$itemrecord);
            }

            //create a new object to hold the updated data
            $pluginrecord 			=	new stdClass();
            $pluginrecord->id		=	$oldrecord->id;
            $pluginrecord->optionlist	=	$data->optionlist;
            $pluginrecord->selecttype 	= 	ILP_OPTIONSINGLE;

            //update the plugin with the new data
            //return $this->dbc->update_plugin_record($this->tablename,$pluginrecord);
        }
    }

}
