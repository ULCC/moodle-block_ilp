<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');
class ilp_mis_attendance_detail_plugin_simple extends ilp_mis_plugin{

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

    public function set_data( $student_id ){
	        $this->data = $this->get_simple_summary( $student_id );
    }
    protected function get_simple_summary( $student_id ){
        $data = $this->get_attendance_summary( $student_id );
        return array(
            $this->get_local_student_header_row( $student_id ),
            array( 'attendance' , $data[ 'attendance' ] ),
            array( 'punctuality' , $data[ 'punctuality' ] )
        );
    }
    protected function get_local_student_header_row( $student_id ){
        return array( 'student id' , $student_id );
    }
    public function plugin_type(){
        return 'detail';
    }
}
