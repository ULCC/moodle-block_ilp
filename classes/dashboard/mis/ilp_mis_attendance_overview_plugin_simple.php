<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');
class ilp_mis_attendance_overview_plugin_simple extends ilp_mis_plugin{


    public function __construct( $params=array() ) {
        parent::__construct( $params );
    }

    /*
    * display the current state of $this->data
    */
    public function display( $withlinks=false ){
        if( is_string( $this->data ) ){
            $output = $this->data;
        }
        elseif( is_array( $this->data ) ){
		    $output = self::test_entable( $this->data );
            if( $withlinks ){
                $output .= $this->get_links();
            }
        }
        echo $output;
    }

    protected function get_links(){
        $output = '';
        $link_list = array(
            '?display_type=class' => 'by class',
            '?display_type=register' => 'by week'
        );
        foreach( $link_list as $url => $label ){
            $output .= "
                <a href=\"$url\">$label</a>
            ";
        }
        return $output;
    }

    public function set_data( $student_id ){
	        $this->data = $this->get_simple_summary( $student_id );
    }
    protected function get_simple_summary( $student_id ){
        //$data = $this->get_attendance_summary( $student_id );
        $tablename = $this->params[ 'student_table' ];
        $keyfield = $this->params[ 'student_unique_key' ];
        $attendance_field = $this->params[ 'student_attendance_field' ];
        $punctuality_field = $this->params[ 'student_punctuality_field' ];
        $data = array_shift( $this->dbquery( $tablename, array( $keyfield => $student_id ), "$attendance_field, $punctuality_field" ) );
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
        return 'overview';
    }
}
