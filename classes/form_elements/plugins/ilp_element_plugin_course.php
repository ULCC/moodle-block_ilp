<?php
//require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins/ilp_element_plugin_dd.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist.php');
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
    	$this->selecttype = OPTIONSINGLE;
		$this->optionlist = false;
    }

    function language_strings(&$string) {
        $string['ilp_element_plugin_course'] 			= 'Select';
        $string['ilp_element_plugin_course_type'] 		= 'course select';
        $string['ilp_element_plugin_course_description'] 	= 'A course selector';
		$string[ 'ilp_element_plugin_course_optionlist' ] 	= 'Option List';
		$string[ 'ilp_element_plugin_course_single' ] 		= 'Single select';
		$string[ 'ilp_element_plugin_course_multi' ] 		= 'Multi select';
		$string[ 'ilp_element_plugin_course_typelabel' ] 	= 'Select type (single/multi)';
		$string[ 'ilp_element_plugin_course_noparticular' ] 	= 'no particular course';
        
        return $string;
    }
	
	protected function get_option_list( $reportfield_id ){
		//return $this->optlist2Array( $this->get_optionlist() );   	
		$outlist = array();
		if( $reportfield_id ){
			//$objlist = $this->dbc->get_optionlist($reportfield_id , $this->tablename );
			$objlist = $this->dbc->get_courses();
			foreach( $objlist as $obj ){
				$outlist[ $obj->id ] = $obj->shortname;
			}
		}
		if( !count( $outlist ) ){
			echo "no items in {$this->items_tablename}";
		}
		return $outlist;
	}
    
	
    /**
     *
     */
    public function audit_type() {
        return get_string('ilp_element_plugin_course_type','block_ilp');
    }
	
    /**
    * this function returns the mform elements taht will be added to a report form
	*
    public	function entry_form( &$mform ) {
    	//text field for element label
        $select = &$mform->addElement(
            'select',
            $this->reportfield_id,
            $this->label,
	    $this->get_option_list(),
            array('class' => 'form_input')
        );
        
        if (!empty($this->req)) $mform->addRule("$this->reportfield_id", null, 'required', null, 'client');
        $mform->setType('label', PARAM_RAW);
    	
        //return $mform;
    	
    	
    }
    */
}
