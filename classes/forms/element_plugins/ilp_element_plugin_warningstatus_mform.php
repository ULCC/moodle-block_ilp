<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin_itemlist_mform.class.php');


class ilp_element_plugin_warningstatus_mform  extends ilp_element_plugin_itemlist_mform {

    public 	$tablename;
    public 	$items_tablename;


    function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {
        parent::__construct($report_id,$plugin_id,$creator_id,$reportfield_id);
        $this->tablename = "block_ilp_plu_wsts";
        $this->items_tablename = "block_ilp_plu_wsts_items";
    }


    function specific_validation($data) {

    }


    function specific_definition($mform) {

        //the id of the statusfield, we should only be using the default status field
        $mform->addElement('hidden', 'status_id');
        $mform->setType('status_id', PARAM_INT);

        //THE status id should be the first status item id 1
        $mform->setDefault('status_id', '1');

    }


    function specific_process_data($data) {
        global 	$USER;

            $statusfield							=	new stdClass();
            $statusfield->reportfield_id			=	$data->reportfield_id;
            $statusfield->selecttype			    =	ILP_OPTIONSINGLE;
            $statusfield->savetype			        =	$data->savetype;
            $statusfield->creator_id				=	$USER->id;

            $this->dbc->create_statusfield($statusfield, $this->tablename);

    }


    protected function is_valid_item( $item, $item_list, $keysep=":" ){
        $item = trim( $item );
        $itemparts = explode( $keysep, $item );
        foreach( $itemparts  as $item ){
            //$item should be either a key or value of $item_list
            if( in_array( $item, array_values( $item_list ) ) || in_array( $item, array_keys( $item_list ) ) ){
                return true;
            }
        }
        return false;
    }
}
