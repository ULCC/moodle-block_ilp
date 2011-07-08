<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');

/*
* plugin for displaying attendance/performance data of a single student in tabular form
* example usage:
* $mis = new ilp_mis_attendance_plugin( $params ) ;
* $mis->set_data( $student_id, $display_style );
* $mis->display();
*/
class ilp_mis_attendance_plugin extends ilp_mis_plugin{

    protected $params;  //initialisation params set at invocation time
    protected $data=array();    //array of arrays for displaying as table rows
    protected $blank="&nbsp;";    //filler for blank table cells - test only

    public function __construct( $params=array() ) {
        parent::__construct();
        $this->set_params( $params );
        $this->db = new ilp_mis_connection( $params );
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
    
    /*
    * $display_style eg simple|term|course|monthly-course-breakdown|register
    * @param int student_id
    * @param string $display_style
    */
    public function set_data( $student_id, $display_style ){
		switch( $display_style ){
		    case 'simple':
		        $data = $this->get_simple_summary( $student_id );
		        break;
		    case 'term':
		        $data = $this->get_summary_by_term( $student_id );
		        break;
		    case 'course':
		        $data = $this->db->get_attendance_summary_by_course( $student_id );
		        break;
		    case 'monthly-course-breakdown':
		        $data = $this->db->get_monthly_course_breakdown( $student_id );
		        break;
		    case 'register':
		        $data = $this->db->get_register_entries( $student_id , $term_id );
		        break;
            default:
                $data = 'unknown view';
		}
        $this->data = $data;
    }

    protected function get_simple_summary( $student_id ){
	        $data = $this->db->get_attendance_summary( $student_id );
            return array(
                $this->get_local_student_header_row( $student_id ),
                array( 'attendance' , $data[ 'attendance' ] ),
                array( 'punctuality' , $data[ 'punctuality' ] )
            );
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
            $reportlist[ $termname ] = $this->db->get_attendance_summary( $student_id, $start, $end );
        }
        return $reportlist;
    }
    protected function get_local_student_header_row( $student_id ){
        return array( 'student id' , $student_id );
    }

    /*
    * for test only - take an array of arrays and render as an html table
    * @param array of arrays $list
    * @return string of arrays
    */
    public static function test_entable( $list ){
        //construct an html table and return it
        $rowlist = array();
        $celltag = 'th';
        foreach( $list as $row ){
            $row_items = array();
            foreach( $row as $item ){
                $row_items[] = self::entag( $celltag, $item, array( 'align'=>'LEFT' ) );
            }
            $rowlist[] = self::entag( 'tr' , implode( '' , $row_items ) );
            $celltag = 'td';
        }
        return self::entag( 'table' , implode( "\n", $rowlist ) , $params=array( 'border'=>1 ) );
    }

    /*
    * for test only - enclose a value in html tags
    * @param string $tag
    * @param string  or boolean $meat
    * @param $params array of $key=>$value
    * @return string
    */
    public static function entag( $tag, $meat=false , $params=false ){
        $pstring = '';
        if( $params ){
            foreach( $params as $key=>$value ){
                $pstring .= " $key=\"$value\"";
            }
        }
        if( false !== $meat ){
            return "<$tag$pstring>$meat</$tag>";
        }
        return "<$tag$pstring />";
    }

}
