<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');
class ilp_mis_attendance_overview_plugin_term extends ilp_mis_plugin{

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

    public function set_data( $student_id, $display_style ){
	        $this->data = $this->get_summary_by_term( $student_id );
    }
    protected function get_summary_by_term( $student_id ){
        $data = $this->get_attendance_summary_by_term( $student_id );
        $tablerowlist = array();
        $headerrow = array_merge( array( $this->blank ) , array_keys( $data ) );
        $tablerowlist[] = $headerrow;
        foreach( array( 'attendance', 'punctuality' ) as $key ){
            $row = array();
            foreach( $headerrow as $header ){
                if( in_array( $header, array_keys( $data ) ) ){
                    $row[] = $data[ $header ][ $key ];
                }
                else{
                    $row[] = $key;
                }
            }
            $tablerowlist[] = $row;
        }
        return $tablerowlist;
    }
    public function get_attendance_summary_by_term( $student_id ){
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        $reportlist = array();
        $termlist = array( 'Overall'=>null, 'Autumn'=>1, 'Spring'=>2, 'Summer'=>3 );
        foreach( $termlist as $termname=>$termindex ){
            if( $termindex ){
                $startend = $cal->termdatelist[ $termindex ];
	            $start = $startend[ 0 ];
	            $end = $startend[ 1 ];
                $suffix = "from $start to $end";
            }
            else{
                $suffix = '';
                $start = $this->params[ 'start_date' ];
                $end = $this->params[ 'end_date' ];
            }
            $reportlist[ $termname ] = $this->get_attendance_summary( $student_id, $start, $end );
        }
        return $reportlist;
    }
    public function plugin_type(){
        return 'detail';
    }
}
