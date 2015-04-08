<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist.class.php');

class ilp_element_plugin_course extends ilp_element_plugin_itemlist{

	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;	//false - this class will use the course table for its optionlist
	public $selecttype;

    /**
     * Constructor
     */
    function __construct() {

    	parent::__construct();
    	$this->tablename = "block_ilp_plu_crs";
    	$this->data_entry_tablename = "block_ilp_plu_crs_ent";
		$this->items_tablename = false;		//items tablename is the course table
    	$this->selecttype = ILP_OPTIONSINGLE;
		$this->optionlist = false;
        $this->external_items_table = 'course';
        $this->external_items_keyfield = 'id';
    }

    static function language_strings(&$string) {
        $string['ilp_element_plugin_course'] 			= 'Select';
        $string['ilp_element_plugin_course_type'] 		= 'Course select';
        $string['ilp_element_plugin_course_description'] 	= 'A course selector';
		$string[ 'ilp_element_plugin_course_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_course_single' ] 		= 'Single select';
		$string[ 'ilp_element_plugin_course_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_course_typelabel' ] 	= 'Select type (single/multi)';
		$string[ 'ilp_element_plugin_course_noparticular' ] 	= 'no particular course';

        return $string;
    }

	function get_option_list( $reportfield_id, $field = false, $user_id = true  ){
		$courseoptions = array();

		$courseoptions['-1']	=	get_string('personal','block_ilp');
		$courseoptions[0]		=	get_string('allcourses','block_ilp');
		//check if the user_id has been set
		$courselist = (!empty($user_id)) ? $this->dbc->get_user_courses($user_id) : $this->dbc->get_courses();

		foreach( $courselist as $c ){
			$courseoptions[ $c->id ] = $c->fullname;
		}

		return $courseoptions;
	}


	/*
	* get the list options with which to populate the edit element for this list element
    * this type is unusual in that the item table is 'course' (not the usual item table for list elements)
    * so we have to call plugin_data_item_exists with extra args
	*/
	public function return_data( &$reportfield ){
        global $CFG;
        $item_table = $CFG->prefix . 'course';
		$data_exists = $this->dbc->plugin_data_item_exists( $this->tablename, $reportfield->id , $item_table, '', 'id' );
		if( empty( $data_exists ) ){
			//if no, get options list
			$reportfield->optionlist = $this->get_option_list_text( $reportfield->id );
		}
		else{
			$reportfield->existing_options = $this->get_option_list_text( $reportfield->id , '<br />' );
		}
	}
    /**
     *
     */
    public function audit_type() {
        return get_string('ilp_element_plugin_course_type','block_ilp');
    }


	 /**
	  * places entry data formated for viewing for the report field given  into the
	  * entryobj given by the user. By default the entry_data function is called to provide
	  * the data. This is a specific instance of the view_data function for the
	  *
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
      * @param bool returnvalue should a label or value be returned
	  */
    public function view_data( $reportfield_id,$entry_id, &$entryobj, $returnvalue=false ){
	  		$fieldname	=	$reportfield_id."_field";

	 		$entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id,false);

            if (!empty($entry->value)) {
               if($course	=	$this->dbc->get_course($entry->value))
               {
                  $entryobj->$fieldname	.=	$course->shortname;
               }
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
	 	//this function will suffix for 90% of plugins who only have one value field (named value) i
	 	//in the _ent table of the plugin. However if your plugin has more fields you should override
	 	//the function

		//default entry_data
		$fieldname	=	$reportfield_id."_field";


	 	$entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id,false);

		if (!empty($entry)) {
		 	$fielddata	=	array();

		 	//loop through all of the data for this entry in the particular entry
		 		$fielddata[]	=	$entry->value;

		 	//save the data to the objects field
	 		$entryobj->$fieldname	=	$fielddata;
	 	}
	 }
		/**
	    * this function saves the data entered on a entry form to the plugins _entry table
		* the function expects the data object to contain the id of the entry (it should have been
		* created before this function is called) in a param called id.
		* as this is a select element, possibly a multi-select, we have to allow
		* for the possibility that the input is an array of strings
	    */
	  	public	function entry_process_data($reportfield_id,$entry_id,$data) {

	  		$result	=	true;

		  	//create the fieldname
			$fieldname =	$reportfield_id."_field";

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
				//delete all of the entries
                    $extraparams = array( 'audit_type' => $this->audit_type() );
					$this->dbc->delete_element_record_by_id($this->data_entry_tablename,$entrydata->id,$extraparams);

			}

			//create new entries
			$pluginentry			=	new stdClass();
            $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
			$pluginentry->entry_id  = 	$entry_id;
	 		$pluginentry->value		=	$data->$fieldname;

			if( is_string( $pluginentry->value ))	{
	 		    $state_item				=	$this->dbc->get_state_item_id($this->tablename,$pluginrecord->id,$data->$fieldname, $this->external_items_keyfield, $this->external_items_table );
	 		    $pluginentry->parent_id	=	$pluginrecord->id;
	 			$result	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
			} else if (is_array( $pluginentry->value ))	{
                $pluginentry->parent_id = $reportfield_id;
				$result	=	$this->write_multiple( $this->data_entry_tablename, $pluginentry );
			}


			return	$result;
	 }

   /**
    * this function returns the mform elements that will be added to a report form
	*
    */

    public function entry_form( &$mform ) {

    	global	$PARSER;


    	//get the id of the course that is currently being used
		$user_id = $PARSER->optional_param('user_id', NULL, PARAM_INT);

		//get the id of the course that is currently being used
		$course_id = $PARSER->optional_param('course_id', NULL, PARAM_INT);

    	//create the fieldname
    	$fieldname	=	"{$this->reportfield_id}_field";

		//definition for user form
		$optionlist = $this->get_option_list( $this->reportfield_id, false, $user_id );

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

		if (!empty($course_id)) $select->setValue($course_id);

        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'server');

        $mform->setType('label', PARAM_RAW);

    }

}
