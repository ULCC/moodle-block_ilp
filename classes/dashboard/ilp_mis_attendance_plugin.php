<?php
/**
 * 
 * a mis class to hold methods common to all the attendance plugins
 *
 * 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */



//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');

//require the ilp_mis_connection.php file 
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_mis_connection.php');


abstract class ilp_mis_attendance_plugin extends ilp_mis_plugin {
	
	protected $blank="&nbsp;";    //filler for blank table cells - test only

	
    public function __construct( $params=array() ){
        parent::__construct( $params );
    }
    /*
    * @param keyed array $list
    * @param string $metric
    * @return string
    */
    protected function calcScore( $list, $metric ){
        switch( $metric ){
            case 'attendance':
                $value = $this->calc_attendance( $list );
                break;
            case 'punctuality':
                $value = $this->calc_punctuality( $list );
                break;
        }
        if( $value ){
            return intval( 100 * $value ) . '%';
        }
        return get_string( 'not_applicable' , 'block_ilp' );
    }

    /*
    * different institutions will have different ways of counting present and absent
    * we need some configurability of this function 
    * @param keyed array $list
    * @return float
    */
    protected function calc_attendance( $list ){
        $present = $list[ 'marksPresent' ];
        $absent = $list[ 'marksAbsent' ];
        $total = $list[ 'marksTotal' ];
        $authabsent = $list[ 'marksAuthAbsent' ];
        $late = $list[ 'marksLate' ];
        
        $totalpresent = $present + $late;
        if( $total ){
            return $totalpresent / $total;
        }
        return false;
    }

    /*
    * different institutions will have different ways of counting present and absent
    * we need some configurability of this function 
    * @param keyed array $list
    * @return float
    */
    protected function calc_punctuality( $list ){
        $present = $list[ 'marksPresent' ];
        $absent = $list[ 'marksAbsent' ];
        $total = $list[ 'marksTotal' ];
        $authabsent = $list[ 'marksAuthAbsent' ];
        $late = $list[ 'marksLate' ];
        
        $totallate = $late;
        $totalpresent = $present + $late;
        if( $totalpresent ){
            return 1 - ( $totallate / $totalpresent );
        }
        return false;
    }
    
    /**
    * @param float $r
    * @return string
    */
    public static function format_percentage( $r ){
        return number_format( round( $r * 100 ) ) . '%';
    }

    /*
    * @param int student_id
    * @return array of arrays
    */
    public function get_courselist( $student_id ){
        $course_id_field = $this->params[ 'course_id_field' ];
        $course_label_field = $this->params[ 'course_label_field' ];
        $student_id_field = $this->params[ 'student_id_field' ];
        $table = $this->params[ 'attendance_view' ];
        $sql = "
            SELECT $course_id_field, $course_label_field 
            FROM $table 
            WHERE $student_id_field = '$student_id'
            GROUP BY $course_id_field
        ";
        return $this->db->execute( $sql )->getRows();
        //return $this->dbquery( $table,
    }

	
    /*
    * generate a list of sql where conditions applying time limits to the query
    * if optional $start and $end are not supplied, the values of the class variables will be used (beware of side-effects oh best beloved)
    * @param string $fieldalias
    * @param boolean $english
    * @param string $start
    * @param string $end
    * @return array
    */
    public function generate_time_conditions( $fieldalias=false, $english=false, $start=null, $end=null ){
        $rtn = array();
        if( !$fieldalias ){
            $timetable_table = $this->params[ 'attendance_view' ];
            $timefield = $this->params[ 'timefield_start' ];
            $fieldalias = "$timetable_table.$timefield";
        }
        //$fieldalias = "`$fieldalias`";  //backtick the fieldname
        if( ( $param_start = $start ) || ( $param_start = $this->params[ 'start_date' ] ) ){
            if( $english ){
                $rtn[] = "from $param_start";
            }
            else{
                $rtn[] = "$fieldalias >= '$param_start'";
            }
        }
        if( ( $param_end = $end ) || ( $param_end = $this->params[ 'end_date' ] ) ){
            if( $english ){
                $rtn[] = "to $param_end";
            }
            else{
                $rtn[] = "$fieldalias <= '$param_end'";
            }
        }
        if( $english ){
            return implode( ' ' , $rtn );
        }
        return $rtn;
    }


    /*
    * query the data for a particular student on a particular course
    * if $attendancecode_list is defined, the query will be restricted to those codes
    * if $countonly=true, a simple integer willb be returned instead of a nested array
    * @param int $course_id
    * @param int $student_id
    * @param array of strings $attendancecode_list
    * @param boolean $countonly
    * @return int if $countonly, array of arrays otherwise
    */
    public function get_attendance_details( $table, $student_id, $course_id=null, $attendancecode_list=array(), $countonly=false, $start=null, $end=null ){
        $slid_field = $this->params[ 'studentlecture_attendance_id' ];
        $acode_field = $this->params[ 'code_field' ];
        $student_id_field = $this->params[ 'student_id_field' ];
        $course_id_field = $this->params[ 'course_id_field' ];
        $timefield = $this->params[ 'timefield_start' ];
        $timefield_end = $this->params[ 'timefield_end' ];
        
        if( $countonly ){
            $selectclause = "COUNT( $slid_field ) n";
        }
        else{
            //$selectclause = "$slid_field id, $acode_field, $timefield, date_format( $timefield , '%I:%i' ) clocktime, DATE_FORMAT( $timefield_end, '%I:%i' ) clocktime_end, date_format( $timefield , '%a' ) dayname, room, tutor ";
            $selectclause = "$slid_field id, $acode_field, $timefield,  $timefield clocktime, $timefield_end clocktime_end,  $timefield dayname, room, tutor ";
            //$selectclause = "$slid_field id, $acode_field, $timefield,  $timefield clocktime, $timefield_end clocktime_end,  $timefield dayname, room, tutor ";
            if( $this->params[ 'extra_fieldlist' ] ){
                foreach( $this->params[ 'extra_fieldlist' ] as $field=>$alias ){
                    $selectclause .= ", $field $alias";
                }
            }
            if( $this->params[ 'extra_numeric_fieldlist' ] ){
                foreach( $this->params[ 'extra_numeric_fieldlist' ] as $field=>$alias ){
                    $selectclause .= ", $field $alias";
                }
            }
        }
        $whereandlist = array(
            "$student_id_field= '$student_id'"
        );
        $whereparams = array(
            $student_id_field => array( '=' => $student_id )
        );
        if( $course_id ){
            $whereandlist[] = "$course_id_field = '$course_id'";
            $whereparams[$course_id_field] = array( '=' => $course_id );
        }

        $whereandlist = array_merge( $whereandlist, $this->generate_time_conditions( $timefield, false, $start, $end ) );
        $whereparams[ $timefield ] = array( '>=' => "'$start'" );
        $whereparams[ "$timefield~" ] = array( '<' => "'$end'" );   //using ~ to make a unique array key - will be removed by ilp_mis_connection::arraytostring
        if( count( $attendancecode_list ) ){
            $whereandlist[] = "$acode_field IN  ('" . implode( "','" , $attendancecode_list ) . "')";
            $whereparams[ $acode_field ] = array( 'IN' => "('" . implode( "','" , $attendancecode_list ) . "')" );
        }
        $whereclause = implode( ' AND ' , $whereandlist );
/*
        $sql = "
            SELECT $selectclause
            FROM $table
            WHERE $whereclause
        ";
*/
        //$res = $this->db->execute( $sql )->getRows();
        $res = $this->dbquery( $table, $whereparams, $selectclause );
        if( $countonly ){
            return ilp_mis_connection::get_top_item( $res, 'n' );
        }
        return $res;
    }
    
    
//////////////////////////////////////////////////////////////////////////////
/////////   please leave these functions here for the moment:    /////////////
/////////         they are useful for testing                    /////////////
//////////////////////////////////////////////////////////////////////////////
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

    /*
    * shared by detail_plugin_class and detail_plugin_register
    */
    protected function get_extreme_date( $list, $firstlast ){
        global $CFG;
        require_once($CFG->dirroot.'/blocks/ilp/db/calendarfuncs.php');
        if( 'first' == $firstlast ){
            return $list[ 0 ][ 0 ];
        }
        elseif( 'last' == $firstlast ){
            $cal = new calendarfuncs();
            $max = 0;
            foreach( $list as $row ){
                foreach( $row as $date ){
                    if( trim( $date ) ){
                        $date = $cal->getutime( $date );
                        if( $date > $max ){
                            $max = $date;
                        }
                    }
                }
            }
            return $cal->getreadabletime( $max );
        }
    }
	
}
