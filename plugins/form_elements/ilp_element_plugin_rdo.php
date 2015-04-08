<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist.class.php');

class ilp_element_plugin_rdo extends ilp_element_plugin_itemlist{
	
	public $tablename;
	public $data_entry_tablename;
	public $items_tablename;
	public $selecttype;	//always single - it's a radio group

    /**
     * Constructor
     */
    function __construct() {
    	$this->tablename = "block_ilp_plu_rdo";
    	$this->data_entry_tablename = "block_ilp_plu_rdo_ent";
		$this->items_tablename = "block_ilp_plu_rdo_items";
		$this->selecttype = ILP_OPTIONSINGLE;
		parent::__construct();
    }
	
	
    public function audit_type() {
        return get_string('ilp_element_plugin_rdo_type','block_ilp');
    }
    
    /**
    * function used to return the language strings for the plugin
    */
    static function language_strings(&$string) {
        $string['ilp_element_plugin_rdo'] 		= 'Radio group';
        $string['ilp_element_plugin_rdo_type'] 		= 'Radio group';
        $string['ilp_element_plugin_rdo_description'] 	= 'A radio group';
        
        return $string;
    }

    
    /**
    * this function returns the mform elements taht will be added to a report form
	*
    */
    public	function entry_form( &$mform ) {
    	
    	$fieldname	=	"{$this->reportfield_id}_field";
    	
		$optionlist = $this->get_option_list( $this->reportfield_id );
		$radioarray = array();

        $i  =   0;

		foreach( $optionlist as $key => $value ){
			$radioarray[] = $mform->createElement( 'radio', $fieldname, '', $value, $key );
            //this sets the first radio option tobe the default selected option
            if (empty($i))  {
                $mform->setDefault($fieldname,$key);
                $i++;
            }
		}

        $mform->addGroup(
            $radioarray,
            $fieldname,
	    	$this->label,
			'',
			'',
            array('class' => 'form_input'),
		    false
        );

        $mform->setType($fieldname, PARAM_RAW);


        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'client');
        if (!empty($this->req)) $mform->addRule($fieldname, null, 'required', null, 'server');
    }


    /**
     * places entry data for the report field given into the entryobj given by the user
     *
     * @param int $reportfield_id the id of the reportfield that the entry is attached to
     * @param int $entry_id the id of the entry
     * @param object $entryobj an object that will add parameters to
     */
    public function entry_data( $reportfield_id,$entry_id,&$entryobj ){
        //this function will suffice for 90% of plugins who only have one value field (named value) i
        //in the _ent table of the plugin. However if your plugin has more fields you should override
        //the function

        //default entry_data
        $fieldname	=	$reportfield_id."_field";

        $entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id,true);

        //loop through all of the data for this entry in the particular entry
        foreach($entry as $e) {
            $entryobj->$fieldname	=	$e->parent_id;
        }
    }



}

