<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');
class ilp_mis_attendance_detail_plugin_register extends ilp_mis_plugin{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
        if( is_string( $this->data ) ){
            echo $this->data;
        }
        elseif( is_array( $this->data ) ){
		    echo self::test_entable( $this->data );
        }
    }

    public function set_data( $student_id, $term_id ){
        $this->data = $this->db->get_register_entries( $student_id , $term_id );
    }
}
