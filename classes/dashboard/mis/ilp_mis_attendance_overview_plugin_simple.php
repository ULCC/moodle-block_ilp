<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_overview_plugin_simple extends ilp_mis_attendance_plugin{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
    }

    /*
    * display the current state of $this->data
    */
    public function display( $withlinks=false ){
        global $CFG;
        require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');





        // set up the flexible table for displaying the portfolios
        $flextable = new ilp_ajax_table( 'attendance_plugin_simple' );
        $headers = array( '' , '' );
        $columns = array( 'metric' , 'score' );
        $flextable->define_columns($columns);
        $flextable->define_headers($headers);
        $flextable->initialbars(false);
        $flextable->setup();
        foreach( $this->data as $row ){
            $data = array();
            $data[ 'metric' ] = $row[ 0 ];
            $data[ 'score' ] = $row[ 1 ];
            $flextable->add_data_keyed( $data );
        }
		ob_start();
        $flextable->print_html();
		$pluginoutput = ob_get_contents();
        ob_end_clean();
        
        echo $pluginoutput;
        exit;





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
            //$this->get_local_student_header_row( $student_id ),
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
