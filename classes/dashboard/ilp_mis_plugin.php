<?php
/**
 * An abstract class that holds methods and attributes common to all mis plugin
 * classes.
 *
 * @abstract
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */



//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_plugin.php');

//require the data connection class
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');

abstract class ilp_mis_plugin extends ilp_plugin {
	
	public 		$templatefile;
	
	/*
	 * This var should hold the connection to the mis database
	 */
	public		$db; 

    protected $params;  //initialisation params set at invocation time
    protected $data=array();    //array of arrays for displaying as table rows
    protected $blank="&nbsp;";    //filler for blank table cells - test only
	
	/**
     * Constructor
     */
    function __construct( $params ) {
    	global	$CFG;
    	
		//set the directory where plugin files of type ilp_dashboard_tab are stored  
    	$this->plugin_class_directory	=	$CFG->dirroot."/blocks/ilp/classes/dashboard/mis";
    	
    	//set the table that the details of these plugins are stored in
    	$this->plugintable	=	"block_ilp_mis_plugin";
    	
    	//call the parent constructor
    	parent::__construct();
    	
    	//set the name of the template file should be a html file with the same name as the class
    	$this->templatefile		=	$this->plugin_class_directory.'/'.$this->name.'.html';

        $this->set_params( $params );
        $this->db = new ilp_mis_connection( $params );
        $this->set_params( $params );
        $this->db = new ilp_mis_connection( $params );
    }
	
   	 /**
     * Force extending class to implement a display function
     */
     abstract function display();
     
     /**
     * Force extending class to implement the plugin type function
     */
     abstract function plugin_type();
     
     

    protected function set_params( $params ){
        $this->params = $params;
    }

    public function set_data(){}
	
    function config_settings(&$settings) {
        return $this->params;
    }

    protected function get_attendance_summary( $student_id , $start=null, $end=null ){
        return $this->get_attendance_report( $student_id, null, 'unnamed', $start, $end );
    }

    /*
    * get student's attendance percentages broken down by course
    * @param int $student_id
    * @return array
    */
    public function get_attendance_summary_by_course( $student_id ){
        //initialise date limits
	    $this->params[ 'start_date' ] = null;
	    $this->params[ 'end_date' ] = null;
        $reportlist = array();
        //step through this student's courses
        foreach( $this->get_courselist( $student_id ) as $course ){
            $reportlist[ $course[ 'course_title' ] ] = $this->get_attendance_report( $student_id, $course[ 'course_id' ] );
        }
        return $reportlist;
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
        $sql = <<<EOQ
            SELECT $course_id_field, $course_label_field 
            FROM $table 
            WHERE $student_id_field = "$student_id"
            GROUP BY $course_id_field
EOQ;
        return $this->db->execute( $sql )->getRows();
    }

    /*
    * @param int $student_id
    * @param int $course_id
    * @param string $course_name
    * @param string $startdate
    * @param string $enddate
    * @return array of scalars
    */
    protected function get_attendance_report( $student_id, $course_id=null, $course_name='un-named', $startdate=null, $enddate=null ){
        //return $this->db->get_attendance_report( $student_id, $course_id, $course_name, $start, $end );
        if( $startdate && $enddate ){
            //not the expected use, but if the time limits are sent in as arguments, use them
            $this->params[ 'start_date' ] = $startdate;
            $this->params[ 'end_date' ] = $enddate;
        }
        $nof_lectures = $this->get_lecturecount_by_student( $student_id , $course_id );
        $nof_present = $this->get_attendance_details( $student_id, $course_id, $this->params[ 'present_code_list' ], true );
        $nof_late = $this->get_attendance_details( $student_id, $course_id, $this->params[ 'late_code_list' ], true );
        $nof_attended = $nof_present;
        if( $nof_lectures ){
            $attendance = $this->format_percentage( $nof_attended / $nof_lectures );
            if( $nof_attended > 0 ){
                $punctuality = self::format_percentage( 1 - ( $nof_late / $nof_attended ) );
            }
            else{
                $punctuality = 'n/a';
            }
        }
        else{
            //division by 0
            $attendance = 'n/a';
            $punctuality = 'n/a';
        }
        return array(
            'course_name' => $course_name,
            'course_id' => $course_id,
            'no. of lectures' => $nof_lectures,
            'lectures attended' => $nof_attended,
            'late' => $nof_late,
            'attendance' => $attendance,
            'punctuality' => $punctuality
        );
    }

    /*
    * @param float $r
    * @return string
    */
    public static function format_percentage( $r ){
        return number_format( round( $r * 100 ) ) . '%';
    }

    /*

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
    * query the data for a particular student on a particular course
    * if $attendancecode_list is defined, the query will be restricted to those codes
    * if $countonly=true, a simple integer willb be returned instead of a nested array
    * @param int $course_id
    * @param int $student_id
    * @param array of strings $attendancecode_list
    * @param boolean $countonly
    * @return int if $countonly, array of arrays otherwise
    */
    public function get_attendance_details( $student_id, $course_id=null, $attendancecode_list=array(), $countonly=false, $start=null, $end=null ){
        $table = $this->params[ 'attendance_view' ];
        $slid_field = $this->params[ 'studentlecture_attendance_id' ];
        $acode_field = $this->params[ 'code_field' ];
        $student_id_field = $this->params[ 'student_id_field' ];
        $course_id_field = $this->params[ 'course_id_field' ];
        $timefield = $this->params[ 'timefield' ];
        
        if( $countonly ){
            $selectclause = "COUNT( $slid_field ) n";
        }
        else{
            $selectclause = "$slid_field id, $acode_field, $timefield, CONCAT( date_format( $timefield , '%I' ), ':' , DATE_FORMAT( $timefield , '%i' ) ) clocktime, date_format( $timefield , '%a' ) dayname";
            if( $this->params[ 'extra_fieldlist' ] ){
                foreach( $this->params[ 'extra_fieldlist' ] as $field=>$alias ){
                    $selectclause .= ", $field $alias";
                }
            }
        }
        $whereandlist = array(
            "$student_id_field= '$student_id'",
        );
        if( $course_id ){
            $whereandlist[] = "$course_id_field = '$course_id'";
        }
        $whereandlist = array_merge( $whereandlist, $this->generate_time_conditions( $timefield, false, $start, $end ) );
        if( count( $attendancecode_list ) ){
            $whereandlist[] = "$acode_field IN  ('" . implode( "','" , $attendancecode_list ) . "')";
        }
        $whereclause = implode( ' AND ' , $whereandlist );
        $sql = <<<EOQ
            SELECT $selectclause
            FROM $table
            WHERE $whereclause
EOQ;
        $res = $this->db->execute( $sql )->getRows();
        if( $countonly ){
            return ilp_mis_connection::get_top_item( $res, 'n' );
        }
        return $res;
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
            $timefield = $this->params[ 'timefield' ];
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
    * count distinct lectures for a particular student within time limits
    * @param int $student_id
    * @return int
    */
    public function get_lecturecount_by_student( $student_id, $course_id=null, $start=null, $end=null ){
        $table = $this->params[ 'attendance_view' ];
        $lecture_id_field = $this->params[ 'lecture_id_field' ];
        $student_id_field = $this->params[ 'student_id_field' ];
        $course_id_field = $this->params[ 'course_id_field' ];
        $timefield = $this->params[ 'timefield' ];
        if( $student_id ){
            $whereandlist = array( "$student_id_field = '$student_id'" );
        }
        else{
            $whereandlist = array( 1 );
        }
        if( $course_id ){
            $whereandlist[] = "$course_id_field = '$course_id'";
        }
        $whereandlist = array_merge( $whereandlist, $this->generate_time_conditions( $this->params[ 'timefield' ], false, $start, $end ) );
        $whereclause = implode( ' AND ' , $whereandlist );
        $sql = "SELECT COUNT( DISTINCT( $lecture_id_field ) ) n
                FROM $table
                WHERE $whereclause";
        $res = $this->db->execute( $sql )->getRows();
        return $this->db->get_top_item( $res, 'n' );
    }

}
