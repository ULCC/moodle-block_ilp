<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');
class ilp_mis_attendance_detail_plugin_course extends ilp_mis_plugin{

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
        $this->data = $this->format_for_display( $this->get_attendance_summary_by_course( $student_id ) );
    }

    private function format_for_display( $data ){
        $tablerowlist = array();
        $tablerowlist[] = array(
            'Subject',
            'Attendance',
            'Punctuality'
        );
        foreach( $data as $subject => $row ){
            $tablerowlist[] = array(
                $subject, $row[ 'attendance' ], $row[ 'punctuality' ]
            );
        }
        return $tablerowlist;
    }

    public function plugin_type(){}
}
