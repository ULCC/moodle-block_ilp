<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_overview_plugin_term extends ilp_mis_attendance_plugin{

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        $this->params[ 'stored_procedure' ] = false;
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
    * take raw data from mis db connection and rearrange it into a sequence of rows for displaying in a table
    * @param int $student_id
    */
    public function set_data( $student_id ){
	        $data = $this->get_summary_by_term( $student_id );
            //we now have the raw data for each term: now we have to calculate the scores, and make a readable table
            $tablerowlist = array();
            $blankcell = '&nbsp;';
            $toprow = array(
                $blankcell=>false,
                'Overall'=>3,
                'Autumn'=>0,
                'Spring'=>1,
                'Summer'=>2
            );
            $tablerowlist[] = array_keys( $toprow );
            foreach( array( 'attendance' , 'punctuality' ) as $metric ){
                $outrow = array( $metric );
                foreach( $toprow as $termname=>$key ){
                    if( false !== $key ){
                        $inrow = $data[ $key ];
                        $outrow[] = $this->calcScore( $inrow, $metric );
                    }
                }
                $tablerowlist[] = $outrow;
            }
            $this->data = $tablerowlist;
    }

    protected function get_summary_by_term( $student_id, $term_id=null ){
        $table = $this->params[ 'termstudent_table' ];
        $student_idfield = $this->params[ 'termstudent_table_student_id_field' ];
        $term_idfield = $this->params[ 'termstudent_table_term_id_field' ];
        
        $conditions = array( $student_idfield => $student_id );
        if( $term_id ){
            $conditions[ $term_idfield ] = $term_id;
        }
        $data = $this->dbquery( $table, $conditions, '*', array( 'sort' => "$term_idfield" ) );
        //we now have terms 1 t0 3, but we need to calculate the totals
        $overall = array(
            "studentID" => $student_id,
            "term" => "overall",
            "marksTotal" => 0,
            "marksPresent" => 0,
            "marksAbsent" => 0,
            "marksAuthAbsent" => 0,
            "marksLate" => 0
        );
        foreach( $data as $row ){
            foreach( array( 'marksTotal', 'marksPresent', 'marksAbsent', 'marksAuthAbsent', 'marksLate' ) as $key ){
                $overall[ $key ] += $row[ $key ];
            }
        }
        $data[] = $overall;
        return $data;
    }

    /*
    * get all details for a particular student in a keyed array
    * @param int $student_id
    * @return array of $key=>$value
    */
    protected function get_student_data( $student_id ){
        $table = $this->params[ 'student_table' ];
        $idfield = $this->params[ 'student_unique_key' ];
        $conditions = array( $idfield => $student_id );
        return $this->dbquery( $table, $conditions );
    }
	
/*
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
*/
    public function plugin_type(){
        return 'detail';
    }
}
