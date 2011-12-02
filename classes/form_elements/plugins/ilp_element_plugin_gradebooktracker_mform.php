<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_mform.php');

class ilp_element_plugin_gradebooktracker_mform  extends ilp_element_plugin_mform {

	public $tablename;
    function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {
    	parent::__construct($report_id,$plugin_id,$creator_id,$reportfield_id);
    	$this->tablename = "block_ilp_plu_gradebooktracker";
    	//$this->data_entry_tablename = "block_ilp_plu_gradebooktracker_ent";
    }
	
	protected function specific_definition($mform) {
/*
		$select = &$mform->addElement(
			'select',
			'courseid',
			get_string( 'ilp_element_plugin_gradebooktracker_course_select_label' , 'block_ilp' ),
			$this->courselist_flatten( $this->dbc->get_courses() ),
			array('class' => 'form_input')
		);
*/
	}
	
    protected function courselist_flatten( $objlist , $key='fullname' ){
        $outlist = array();
        foreach( $objlist as $row ){
            $outlist[ $row->id ] = $row->$key;
        }
        return $outlist;
    }
    
	protected function specific_validation($data) {
	 }

	protected function specific_process_data($data) {
	 	$plgrec = (!empty($data->reportfield_id)) ? $this->dbc->get_plugin_record( $this->tablename ,$data->reportfield_id ) : false;
        if( empty( $plgrec ) ){
            //$data->course_id = $data->course;
 		    return $this->dbc->create_plugin_record( $this->tablename,$data );
        }
        else{
	 		//get the old record from the elements plugins table 
	 		$oldrecord				=	$this->dbc->get_form_element_by_reportfield( $this->tablename ,$data->reportfield_id );
	
	 		//create a new object to hold the updated data
	 		$pluginrecord 					=	new stdClass();
	 		$pluginrecord->id				=	$oldrecord->id;
	 			
	 		//update the plugin with the new data
	 		return $this->dbc->update_plugin_record("block_ilp_plu_tex",$pluginrecord);
        }
    }
}
