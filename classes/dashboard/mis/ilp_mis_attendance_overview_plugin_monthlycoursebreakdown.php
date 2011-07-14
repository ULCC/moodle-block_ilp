<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_overview_plugin_monthlycoursebreakdown extends ilp_mis_attendance_plugin{

    protected $monthlist = array();

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        $this->monthlist = array(
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'
        );
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
        if( is_string( $this->data ) ){
            echo $this->data;
        }
        elseif( is_array( $this->data ) ){
            echo $this->flexentable( $this->data );
        }
    }

    protected function flexentable( $data ){
        // set up the flexible table for displaying the portfolios
        $flextable = new ilp_ajax_table( 'attendance_plugin_simple' );
        $headers = array_shift( $data );
        $columns = $headers;

        //convert headers to monthnames
        foreach( $headers as &$monthid ){
            if( is_numeric( $monthid ) ){
                //@todo internationalise the month name
                $monthid = $this->monthlist[ $monthid ];
            }
        }       

        $flextable->define_columns($columns);
        $flextable->define_headers($headers);
        $flextable->initialbars(false);
        $flextable->setup();
        foreach( $data as $row ){
            $tablerow = array();
            $i = 0;
            foreach( $columns as $col ){
                $tablerow[ $col ] = $row[ $i++ ];
            }
            $flextable->add_data_keyed( $tablerow );
        }
		ob_start();
        $flextable->print_html();
		$pluginoutput = ob_get_contents();
        ob_end_clean();
        
        return $pluginoutput;
    }

    protected function get_month_headerlist( $data ){
        $monthid_field = 'monthid';
        $monthlist = array();
        foreach( $data as $row ){
            $monthid = $row[ $monthid_field ];
            if( !in_array( $monthid, $monthlist ) ){
                $monthlist[] = $monthid;
            }
        }
        return $monthlist;
    }

    public function set_data( $student_id, $display_style ){
	        $this->data = $this->get_monthly_course_breakdown( $student_id );
    }
    public function plugin_type(){
        return 'overview';
    }

    public function get_monthly_course_breakdown( $student_id ){
        $table = $this->params[ 'coursestudentmonth_table' ];
        $studentid_field = $this->params[ 'coursestudentmonth_table_student_id_field' ];
        $courseid_field = $this->params[ 'coursestudentmonth_table_course_id_field' ];
        $coursename_field = $this->params[ 'coursestudentmonth_table_month_coursename_field' ]; 
        $monthid_field = $this->params[ 'coursestudentmonth_table_month_id_field' ];
        $markstotal_field = $this->params[ 'coursestudentmonth_table_term_marksTotal_field' ];
        $markspresent_field = $this->params[ 'coursestudentmonth_table_month_marksPresent_field' ];
        $marksabsent_field = $this->params[ 'coursestudentmonth_table_month_marksAbsent_field' ];
        $marksauthabsent_field = $this->params[ 'coursestudentmonth_table_month_marksAuthAbsent_field' ];
        $markslate_field = $this->params[ 'coursestudentmonth_table_month_marksLate_field' ];
        $where = array( $studentid_field => $student_id );
        $fieldstr = "$studentid_field studentid, $courseid_field courseid, $coursename_field coursename, $monthid_field monthid, $markstotal_field marksTotal, $markspresent_field marksPresent, $marksabsent_field marksAbsent, $markslate_field marksLate, $marksauthabsent_field marksAuthAbsent";
        $data = $this->dbquery( $table, $where, $fieldstr, array( 'sort' => "{$courseid_field}, {$monthid_field} " ) );


        $headerrow = array( 'Subject', 'Attendance' );
        $headerkeylist = array( 'Attendance' );
        $courseidlist = array();
        $tablerowlist = array();
        $prevcourseid = false;
        $prevsubject = false;
        foreach( $data as $row ){
            $subject = "{$row[ 'courseid' ]} {$row[ 'coursename' ]}";
            if( $prevcourseid === $row[ 'courseid' ] ){
                //just another month
                if( $index = array_search( $row[ 'monthid' ] , $headerrow ) ){
                }
                else{
                    //new month column
                    $headerrow[] = $row[ 'monthid' ];
                    $headerkeylist[] = $row[ 'monthid' ];
                    //$index=( count( $headerrow ) - 1 );
                }
                //$tablerow[ $index ] = $this->calcScore( $row, 'attendance' );
            }
            elseif( $prevcourseid ){
                //new tablerow
                $tablerow = array( $prevsubject );
                $courseidlist[] = $prevcourseid;
                $tablerowlist[] = $tablerow;
                $tablerow = array( $subject );
                $courseidlist[] = $row[ 'courseid' ];
            }
            $prevcourseid = $row[ 'courseid' ];
            $prevsubject = $subject;
        }
        //insert calculated values into the row
        $tablerowlist[] = $tablerow;
        foreach( $tablerowlist as &$tablerow ){
            $courseid = array_shift( $courseidlist );
            foreach( $headerkeylist as $column ){
                $tablerow[] = $this->calculate_attendance( $courseid, $column, $data );
            }
        }

        
        //prepend the header row
        array_unshift( $tablerowlist, $headerrow );
        return $tablerowlist;
/*
        echo $this->test_entable( $tablerowlist );
        var_crap($data);
        exit;
*/
    }
    protected function calculate_attendance( $courseid, $col, $data ){
        $field_total_list = array(
            'marksTotal' => 0,
            'marksPresent' => 0,
            'marksAbsent' => 0,
            'marksAuthAbsent' => 0,
            'marksLate' => 0
        );
        foreach( $data as $row ){
            if( $row[ 'courseid' ] == $courseid ){
                if( 'Attendance' == $col || $row[ 'monthid' ] == $col ){
                    foreach( array_keys( $field_total_list ) as $key ){
                        $field_total_list[ $key ] += $row[ $key ];
                    }
                }
            }
        }
        return $this->calcScore( $field_total_list, 'attendance' );
    }
}
