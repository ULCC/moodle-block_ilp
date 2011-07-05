<?php
/*
* include the standard Adodb library
* if adodb is removed from moodle in the future, we might need
* to include it specially within ILP
*/
$adodb_dir = $CFG->dirroot . '/lib/adodb';
require_once( "$adodb_dir/adodb.inc.php" );
require_once( "$adodb_dir/adodb-exceptions.inc.php" );
require_once( "$adodb_dir/adodb-errorhandler.inc.php" );
//require_once($CFG->dirroot.'/lib/adodb/adodb.inc.php');

/*
* This class is intended for querying the student database for attendance data.
* The attendance information may be in a different db, and possibly on a different platform, from the moodle db.
* Therefore we invoke a fresh Adodb for this purpose.
* The type of db and connection details are configured on the configuration page /admin/settings.php
* Names of user table and attendance table can be sent in as params.
* For example usage see ilp/actions/dbtest.php.
*
* We assume that the owner of the system will have made available a view or table of student attendence data
* Each row will represent one expected attendance by one student at one lecture
* Each row needs student id, course id, lecture id, lecture time, attendence code
* A lists of codes representing presence, lates and absences are defined in ilp/db/mis_constants.php
*/
class ilp_mis_connection{

    protected $db;
    protected $prefix;
    protected $student_table;          //student tablename
    protected $attendance_table;       //student_id, lecture_id, attendancecode
    protected $student_unique_key;     //student primary key fieldname
    protected $attendance_studentid;   //fk fieldname in attendance table matching student_id
    protected $lecture_table;          //name of table listing all lectures
    protected $lecture_courseid;       //fk fieldname in lecture_table identifying a course
    public $errorlist;                   //collect a list of errors

    /*
    * $params array should have keys corresponding to the class variable names listed in the foreach
    * @param array $params
    * @param boolean $debug
    */
    public function __construct( $params=array(), $debug=false ){
        global $CFG;
        $this->db = false;
        $this->errorlist = array();
        //$this->prefix = $CFG->prefix;
        $this->params = array(
		    'dbconnectiontype' => get_config( 'block_ilp', 'dbconnectiontype' ),
		    'host' => get_config( 'block_ilp', 'dbhost' ),
		    'user' => get_config( 'block_ilp', 'dbuser' ),
		    'pass'=> get_config( 'block_ilp', 'dbpass' ),
		    'dbname' => get_config( 'block_ilp', 'dbname' )
        );
        //also allow other class variables to be set optionally from input params
        $this->settable_params = array(
            'prefix',                           //table prefix (yet unused)
            'student_table',                    //student tablename
            'student_unique_key',               //student table primary key
            'attendance_table',                 //attendance-at-lectures tablename
            'attendance_table_unique_key',      //attendance table primary key
            'attendance_studentid',             //fk field in attendance table matching student id
            'attendance_lectureid',             //fk field in attendance table matching lecture id
            'lecture_table',                    //lecture tablename
            'lecture_unique_key',               //lecture table primary key
            'lecture_courseid',                 //fk field in lecture table matching a course id
            'lecture_attendance_id',            //fk field in lecture table matching an attendance code id
            'attendancecode_table',             //attendance code tablename
            'attendancecode_unique_key',        //attendance code table primary key
            'attendancecode_id_field',          //attendance code table code field
            'course_table',                     //course tablename
            'course_table_unique_key',          //course table primary key
            'course_table_namefield',           //course title field 
            'student_course_table',             //link table linking student_ids with course_ids
            'student_course_table_unique_key',  //student-course link table primary key
            'student_course_student_key',       //fk field in student-course link table matching student id
            'student_course_course_key',        //fk firld in student_course link table matching course id
            'present_code_list',                //array of attendance codes classified as present
            'absent_code_list',                 //array of attendance codes classified as absent
            'late_code_list',                   //array of attendance codes classified as late (should be subset of present_code_list)
            'timetable_table',                  //table containing info about the date of each lecture (will be set same as lecture_table if not given)
            'lecture_time_field',               //name of field giving the date/time of each lecture, for time-limiting reports
            'start_date',                       //start date to be applied generically to queries
            'end_date',                          //end date to be applied generically to queries

            'attendance_view',                  //view or table containing all the relevant attendance data
            'studentlecture_attendance_id',      //primary key of attendance view - unique identifier for a single student-lecture attendance event
            'student_id_field',                 //fieldname in attendance_view identifying a student
            'student_name_field',                 //fieldname in attendance_view giving a student name for display
            'course_id_field',                  //fieldname in attendance_view identifying a course
            'course_label_field',               //fieldname in attendance_view giving a course display name
            'lecture_id_field',               //fieldname in attendance_view giving a lecture id
            'timefield',                        //fieldname in attendance_view giving the date of a lecture
            'code_field'                        //fieldname in attendance_veiw containing the attendance code
        );
        foreach( $this->settable_params as $var ){
                $this->params[ $var ] = false;
        }
        $this->set_params( $params );
        $connectioninfo = $this->get_mis_connection( 
            $this->params[ 'dbconnectiontype' ],  
            $this->params[ 'host' ], 
            $this->params[ 'user' ], $this->params[ 'pass' ], 
            $this->params[ 'dbname' ] 
        );
        if( $errorlist = $connectioninfo[ 'errorlist' ] ){
            //var_crap( $errorlist );exit;
        }
        $this->errorlist = array_merge( $this->errorlist, $connectioninfo[ 'errorlist' ] );
        if( $this->errorlist ){
            //var_crap( $this->errorlist );
            return false;
        }
        $this->db = $connectioninfo[ 'db' ];
        return $this->db;
    }

    public function get_mis_connection( $type, $host, $user, $pass, $dbname ){
        $errorlist = array();
        $db = false;
        try{
            $db = ADONewConnection( $type );
        }
        catch( exception $e ){
            $errorlist[] = $e->getMessage();
        }
        if( $db ){
	        try{
	            $db->SetFetchMode(ADODB_FETCH_ASSOC);
	            $db->Connect( $host, $user, $pass, $dbname );
	        }
	        catch( exception $e ){
	            $errorlist[] = $e->getMessage();
	        }
        }
        return array(
            'errorlist' => $errorlist,
            'db' => $db
        );
    }


    public function get_student_list(){
    }

    public function test_mis_connection(){
        $msglist = array();
        $valid = false;
        if( $this->db ){
            $msglist[] = 'connection OK';
            $sql = $this->db->metaTablesSQL; 
            $view = $this->params[ 'attendance_view' ];
            $viewfound = false;
            foreach( $this->execute( $sql )->getRows() as $row ){
                if( in_array( $view, array_values( $row ) ) ){
                    $viewfound = true;break;
                }
            }
            if( $viewfound ){
                $msglist[] = 'Attendance overview table found OK';
                $testsql = "SELECT * FROM $view LIMIT 1";
                $testdata = $this->execute( $testsql )->getRows();
                if( count( $testdata ) > 0 ){
                    $foundfieldlist = array_keys( array_shift( $testdata ) );
                    $requiredlist = array(
                        'lecture_time_field', 
                        'studentlecture_attendance_id',
                        'student_id_field',
                        'student_name_field',
                        'course_id_field',
                        'course_label_field',
                        'lecture_id_field',
                        'timefield',
                        'code_field'
                    );
                    $missinglist = array();
                    foreach( $requiredlist as $param ){
                        $actualfieldname = $this->params[ $param ];
                        if( !in_array( $actualfieldname, $foundfieldlist ) ){
                            $missinglist[] = $param;
                            $msglist[] = "Could not find $param field (seeking  \"$actualfieldname\" defined in params)";
                        }
                    }
                    if( $missinglist ){}
                    else{
                        $msglist[] = "All necessary fields found OK";
                        $valid = true;
                    }
                }
                else{
                    $msglist[] = "View or table $view found, but contains no data.";
                }
            }
            else{
                $msglist[] = "No attendance data found. Seeking $view";
            }
        }
        else{
            $msglist = array_merge( $msglist, $this->errorlist );
        }
        var_crap( implode( "\n", $msglist ), 'Messages' );
        return $valid;
    }

    protected function generate_time_conditions( $fieldalias=false, $english=false ){
        $rtn = array();
        if( !$fieldalias ){
            $timetable_table = $this->params[ 'attendance_view' ];
            $timefield = $this->params[ 'timefield' ];
            $fieldalias = "$timetable_table.$timefield";
        }
        if( $param_start = $this->params[ 'start_date' ] ){
            if( $english ){
                $rtn[] = "from $param_start";
            }
            else{
                $rtn[] = "$fieldalias >= '$param_start'";
            }
        }
        if( $param_end = $this->params[ 'end_date' ] ){
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
    * the main function for returning data back to the controller for display
    * @param int $student_id
    * @return associative array
    */
    public function get_report( $student_id=false ){
        if( $student_id ){
            $student_id_list = array( $student_id );
        }
        else{
            $student_id_list = $this->get_student_id_list();
        }
        $uber_reportlist = array();
        $time_heading = '';
        if( $time_heading = $this->generate_time_conditions( false, true ) ){
            $time_heading = " " . $time_heading;
        }
        foreach( $student_id_list as $student_id ){
	        $courselist = $this->get_courselist( $student_id );
	        $reportlist = array();
	        foreach( $courselist as $course ){
	            $course_id = $course[ 'course_id' ];
	            $course_title = $course[ 'course_title' ];
	            $reportlist[] = $this->get_attendance_report( $course_id, $student_id, $course_title );
	            $report_by_term_list[] = $this->get_attendance_report_by_term( $course_id, $student_id, $course_title );
	            $report_by_week_list[] = $this->get_attendance_report_by_week( $course_id, $student_id, $course_title );
	            $report_by_month_list[] = $this->get_attendance_report_by_month( $course_id, $student_id, $course_title );
	        }
	        $uber_reportlist[] = array(
	            'student_details' => $this->get_student_details( $student_id ),
	            //'courselist' => $courselist,
	            "course attendance$time_heading" => $reportlist,
	            "course attendance$time_heading by term" => $report_by_term_list,
	            "course attendance$time_heading by month" => $report_by_month_list,
	            "course attendance$time_heading by week" => $report_by_week_list
	        );
        }
        return $uber_reportlist;
    }

    protected function get_student_id_list(){
        $id = $this->params[ 'student_unique_key' ];
        $student = $this->params[ 'student_table' ];
        $sql = "SELECT $id FROM $student";
        return $this->get_column_valuelist( $this->execute( $sql )->getRows() , $id );
    }

    /*
    * intended to return just the front item from an array of arrays (eg a recordset)
    * if just the array is sent, just the first row will be returned
    * if 2nd argument sent, then just the value of that field in the first row will be returned
    * @param array $a
    * @param string $fieldname
    * @return mixed (array or single value)
    */
    protected function get_top_item( $a , $fieldname=false ){
        $toprow = array_shift( $a );
        if( $fieldname ){
            return $toprow[ $fieldname ];
        }
        return $toprow;
    }

    /*
    * take a result array and return a list of the values in a single field
    * @param array of arrays $a
    * @param string $fieldname
    * @return array of scalars
    */
    protected function get_column_valuelist( $a, $fieldname ){
        $rtn = array();
        foreach( $a as $row ){
            $rtn[] = $row[ $fieldname ];
        }
        return $rtn;
    }

    /*
    * @param int $student_id
    * @return associative array
    */
    protected function get_student_details( $student_id ){
        $student = $this->params[ 'student_table' ];
        $id = $this->params[ 'student_unique_key' ];
        $sql = " SELECT * FROM $student WHERE $id = $student_id ";
        $res = $this->execute( $sql )->getRows();
        return $this->get_top_item( $res );
    }

    /*
    * @param int $course_id
    * @param int $student_id
    * @param string $course_name
    * @return array of arrays
    */
    public function get_attendance_report_by_term( $course_id, $student_id, $course_name ){
        $cal = new calendarfuncs();
        $reportlist = array();
        foreach( $cal->termdatelist as $startend ){
            $start = $startend[ 0 ];
            $end = $startend[ 1 ];
            $this->params[ 'start_date' ] = $start;
            $this->params[ 'end_date' ] = $end;
            $name = "Attendance at $course_name from $start to $end";
            $reportlist[] = $this->get_attendance_report( $course_id, $student_id, $name );
        }
        return $reportlist;
    }

    /*
    * @param int $course_id
    * @param int $student_id
    * @param string $course_name
    * @return array of arrays
    */
    public function get_attendance_report_by_month( $course_id, $student_id, $course_name ){
        $cal = new calendarfuncs();
        $datesinfo = $cal->generate_dates();
        $months = $datesinfo[1][ 'months' ];
        foreach($months as $startend ){
            $start = $startend[ 0 ];
            $end = $startend[ 1 ];
            $this->params[ 'start_date' ] = $start;
            $this->params[ 'end_date' ] = $end;
            $name = "Attendance at $course_name from $start to $end";
            $reportlist[] = $this->get_attendance_report( $course_id, $student_id, $name );
        }
        return $reportlist;
    }

    /*
    * @param int $course_id
    * @param int $student_id
    * @param string $course_name
    * @return array of arrays
    */
    public function get_attendance_report_by_week( $course_id, $student_id, $course_name ){
        $cal = new calendarfuncs();
        $datesinfo = $cal->generate_dates();
        $weeks = $datesinfo[1][ 'weeks' ];
        $reportlist = array();
        foreach( $weeks as $startend ){
            $start = $startend[ 0 ];
            $end = $startend[ 1 ];
            $this->params[ 'start_date' ] = $start;
            $this->params[ 'end_date' ] = $end;
            $name = "Attendance at $course_name from $start to $end";
            $reportlist[] = $this->get_attendance_report( $course_id, $student_id, $name );
        }
        return $reportlist;
    }

    /*
    * set class values for  start_date and end_date, then call this function to get the data within those time limits
    * optional $startdate and $enddate will take priority over previously set class value, if both are sent in
    * @param int $course_id
    * @param int $student_id
    * @param string $course_name
    * @param string $startdate
    * @param string $enddate
    * @return array of scalars
    */
    protected function get_attendance_report( $course_id, $student_id, $course_name='un-named', $startdate=null, $enddate=null ){
        if( $startdate && $enddate ){
            //not the expected use, but if the time limits are sent in as arguments, use them
            $this->params[ 'start_date' ] = $startdate;
            $this->params[ 'end_date' ] = $enddate;
        }
        $nof_lectures = $this->get_lecturecount( $course_id );
        $nof_attended = $this->get_attendance_details( $course_id, $student_id, $this->params[ 'present_code_list' ], true );
        $nof_late = $this->get_attendance_details( $course_id, $student_id, $this->params[ 'late_code_list' ], true );
        if( $nof_lectures ){
            $attendance = $this->format_percentage( $nof_attended / $nof_lectures );
            if( $nof_attended > 0 ){
                $punctuality = $this->format_percentage( 1 - ( $nof_late / $nof_attended ) );
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
    public function format_percentage( $r ){
        return number_format( round( $r * 100 ) ) . '%';
    }

    /*
    * count distinct lectures for a particular course within time limits
    * @param int $course_id
    * @return int
    */
    protected function get_lecturecount( $course_id ){
        $table = $this->params[ 'attendance_view' ];
        $lecture_id_field = $this->params[ 'lecture_id_field' ];
        $course_id_field = $this->params[ 'course_id_field' ];
        $timefield = $this->params[ 'timefield' ];
        $whereandlist = array( "$course_id_field = '$course_id'" );
        $whereandlist = array_merge( $whereandlist, $this->generate_time_conditions( $timefield ) );
        $whereclause = implode( ' AND ' , $whereandlist );
        $sql = "SELECT COUNT( DISTINCT( $lecture_id_field ) ) n
                FROM $table
                WHERE $whereclause";
        $res = $this->execute( $sql )->getRows();
        return $this->get_top_item( $res, 'n' );
    }

    /*
    * @param int student_id
    * @return array of arrays
    */
    protected function get_courselist( $student_id ){
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
        return $this->execute( $sql )->getRows();
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
    public function get_attendance_details( $course_id, $student_id, $attendancecode_list=array(), $countonly=false ){
        $table = $this->params[ 'attendance_view' ];
        $slid_field = $this->params[ 'studentlecture_attendance_id' ];
        $acode_field = $this->params[ 'code_field' ];
        $student_id_field = $this->params[ 'student_id_field' ];
        $course_id_field = $this->params[ 'course_id_field' ];
        $timefield = $this->params[ 'timefield' ];
        
        if( $countonly ){
            $selectclause = "COUNT(*) n";
        }
        else{
            $selectclause = "$slid_field id, $acode_field";
        }
        $whereandlist = array(
            "$student_id_field= '$student_id'",
            "$course_id_field = '$course_id'"
        );
        $whereandlist = array_merge( $whereandlist, $this->generate_time_conditions( $timefield ) );
        if( count( $attendancecode_list ) ){
            $whereandlist[] = "$acode_field IN  ('" . implode( "','" , $attendancecode_list ) . "')";
        }
        $whereclause = implode( ' AND ' , $whereandlist );
        $sql = <<<EOQ
            SELECT $selectclause
            FROM $table
            WHERE $whereclause
EOQ;
        $res = $this->execute( $sql )->getRows();
        if( $countonly ){
            return $this->get_top_item( $res, 'n' );
        }
        return $res;
    }

    /*
    * step through an array of $key=>$value and assign them to the class $params array
    * @param associative array $params
    */
    public function set_params( $params ){
        foreach( $params as $key=>$value ){
            if( in_array( $key, $this->settable_params ) ){
                $this->params[ $key ] = $value;  
            }
        }
        if( !( $this->params[ 'timetable_table' ] ) ){
            $this->params[ 'timetable_table' ] = $this->params[ 'lecture_table' ];
        }
    }

    public function test_query(){
        $sql = "
			SELECT student.id sid, student.name, course.`title`, attendancecode.code, attendancecode.cat, lecture.start, DAYNAME(DATE( lecture.start ) )
			FROM student 
			JOIN student_course sc ON sc.student_id = student.id 
			JOIN course ON course.id = sc.course_id 
			JOIN lecture ON lecture.`course_id` = course.id
			JOIN student_lecture sl ON sl.student_id = student.id AND sl.`lecture_id` = lecture.id
			JOIN attendancecode ON attendancecode.id = sl.`attendancecode_id`
        ";
        return $this->execute( $sql );
    }

    /* 
    * @param string $sql
    * @return array of arrays      
    */
    public function execute( $sql , $arg=false ){
        $res = $this->db->Execute( $sql, $arg ) or die( $this->db->ErrorMsg() );
        return $res;
    }
        
}
