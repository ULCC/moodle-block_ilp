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
            'end_date',                         //end date to be applied generically to queries
            'week1',                            //date of the first day of week 1 in the particular institution's calendar

            'attendance_view',                  //view or table containing all the relevant attendance data
            'studentlecture_attendance_id',     //primary key of attendance view - unique identifier for a single student-lecture attendance event
            'student_id_field',                 //fieldname in attendance_view identifying a student
            'student_name_field',               //fieldname in attendance_view giving a student name for display
            'course_id_field',                  //fieldname in attendance_view identifying a course
            'course_label_field',               //fieldname in attendance_view giving a course display name
            'lecture_id_field',                 //fieldname in attendance_view giving a lecture id
            'timefield',                        //fieldname in attendance_view giving the date of a lecture
            'code_field',                        //fieldname in attendance_veiw containing the attendance code
            'extra_fieldlist',                   //array of fieldname=>label which can be added to data retrieved to show on a page

            'termdatelist'                      //array of term dates - each member is an array containing start date and end date
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

//@todo - recast as problem-seeking test function
    /*
    * test the db connection configured from the settings and produce a helpful message
    * @return boolean
    */
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

//@todo refactor remaining functions to the plugin
    /*
    * generate a list of sql where conditions applying time limits to the query
    * if optional $start and $end are not supplied, the values of the class variables will be used (beware of side-effects oh best beloved)
    * @param string $fieldalias
    * @param boolean $english
    * @param string $start
    * @param string $end
    * @return array
    */
    protected function generate_time_conditions( $fieldalias=false, $english=false, $start=null, $end=null ){
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

    public function get_attendance_summary_by_term( $student_id ){
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        $reportlist = array();
        $termlist = array( 'Overall'=>null, 'Autumn'=>0, 'Spring'=>1, 'Summer'=>2 );
        //foreach( $cal->termdatelist as $startend ){
        foreach( $termlist as $termname=>$termindex ){
            if( $termindex ){
                $startend = $cal->termdatelist[ $termindex ];
	            $start = $startend[ 0 ];
	            $end = $startend[ 1 ];
                $suffix = "from $start to $end";
	            $this->params[ 'start_date' ] = $start;
	            $this->params[ 'end_date' ] = $end;
            }
            else{
                $suffix = '';
	            $this->params[ 'start_date' ] = null;
	            $this->params[ 'end_date' ] = null;
            }
            $reportlist[ $termname ] = $this->get_attendance_summary( $student_id );
        }
        return $reportlist;
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
    * step through a student's courses, and for each course return attendance percentage for each month
    * @param int $student_id
    * @return array of arrays
    */
    public function get_monthly_course_breakdown( $student_id ){
        $reportlist = array();
        $tablerowlist = array();
        $headerrow = array( 
                            'Subject',
                            'Attendance'
        );
        
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        $monthlist = $cal->calc_sub_month_limits( $this->params[ 'start_date' ] , $this->params[ 'end_date' ] );
        foreach($monthlist as $monthdates ){
            $headerrow[] = date( 'M' , $cal->getutime( $monthdates[ 0 ] ) );
        }
        $tablerowlist[] = $headerrow;
        //step through this student's courses
        foreach( $this->get_courselist( $student_id ) as $course ){
            //get monthly breakdown for this course
            $reportlist[ $course[ 'course_title' ] ] = $this->get_percentage_by_month( $student_id, $course[ 'course_id' ] );

            
            $tablerowlist[] = array_merge( array( $course[ 'course_title' ] ), $this->get_percentage_by_month( $student_id, $course[ 'course_id' ] ) );
            $row = array();
        }
        return $tablerowlist;
        //return $reportlist;
    }

    public function get_attendance_summary( $student_id ){
        return $this->get_attendance_report( $student_id );
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
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
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
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
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
    * within the class time limits, give percentage attendance broken down by month
    * @param int $student_id
    * @param int $course_id
    * @return array of strings
    */
    protected function get_percentage_by_month( $student_id, $course_id ){
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        $start = $this->params[ 'start_date' ];
        $end = $this->params[ 'end_date' ];

        $data = array();
        $data[ 'Attendance' ] = $this->get_attendance_percentage( $student_id, $course_id, $start, $end );

        $monthlist = $cal->calc_sub_month_limits( $start, $end );
        foreach($monthlist as $startend ){
            $start = $startend[ 0 ];
            $end = $startend[ 1 ];
            $info = $this->get_attendance_percentage( $student_id, $course_id, $start, $end );
            $month = date( 'M' , strtotime( $start ) ) ;
            $data[ $month ] = $info;
        }
        return $data;
    }
    

    /*
    * @param int $course_id
    * @param int $student_id
    * @param string $course_name
    * @return array of arrays
    */
    public function get_attendance_report_by_week( $course_id, $student_id, $course_name ){
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
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
    * @param int $student_id
    * @param int $course_id
    * @param string $course_name
    * @param string $startdate
    * @param string $enddate
    * @return array of scalars
    */
    protected function get_attendance_report( $student_id, $course_id=null, $course_name='un-named', $startdate=null, $enddate=null ){
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
    * @param int $student_id
    * @param int $course_id
    * @param mixed $start
    * @param mixed $end
    * @return string
    */
    protected function get_attendance_percentage( $student_id, $course_id, $start, $end ){
        $nof_lectures = $this->get_lecturecount_by_student( $student_id , $course_id , $start, $end );
        $nof_present = $this->get_attendance_details( $student_id, $course_id, $this->params[ 'present_code_list' ], true, $start, $end  );
        if( is_numeric( $nof_lectures ) && $nof_lectures ){
            return $this->format_percentage( $nof_present / $nof_lectures );
        }
        return 'n/a';
    }

    /*
    * @param float $r
    * @return string
    */
    public function format_percentage( $r ){
        return number_format( round( $r * 100 ) ) . '%';
    }

    /*
    * count distinct lectures for a particular student within time limits
    * @param int $student_id
    * @return int
    */
    protected function get_lecturecount_by_student( $student_id, $course_id=null, $start=null, $end=null ){
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
        $res = $this->execute( $sql )->getRows();
        return $this->get_top_item( $res, 'n' );
    }

    /*
    * count distinct lectures for a particular course within time limits
    * @param int $course_id
    * @return int
    */
    protected function get_lecturecount_by_course( $course_id ){
        $table = $this->params[ 'attendance_view' ];
        $lecture_id_field = $this->params[ 'lecture_id_field' ];
        $course_id_field = $this->params[ 'course_id_field' ];
        $timefield = $this->params[ 'timefield' ];
        if( $course_id ){
            $whereandlist = array( "$course_id_field = '$course_id'" );
        }
        else{
            $whereandlist = array( 1 );
        }
        $whereandlist = array_merge( $whereandlist, $this->generate_time_conditions( $timefield ) );
        $whereclause = implode( ' AND ' , $whereandlist );
        $sql = "SELECT COUNT( DISTINCT( $lecture_id_field ) ) n
                FROM $table
                WHERE $whereclause";
        $res = $this->execute( $sql )->getRows();
        return $this->get_top_item( $res, 'n' );
    }

    /*
    * for test only - take an array of arrays and render as an html table
    * @param array of arrays $list
    * @return string of arrays
    */
    public function test_entable( $list ){
        //construct an html table and return it
        $rowlist = array();
        $celltag = 'th';
        foreach( $list as $row ){
            $row_items = array();
            foreach( $row as $item ){
                $row_items[] = $this->entag( $celltag, $item, array( 'align'=>'LEFT' ) );
            }
            $rowlist[] = $this->entag( 'tr' , implode( '' , $row_items ) );
            $celltag = 'td';
        }
        return $this->entag( 'table' , implode( "\n", $rowlist ) , $params=array( 'border'=>1 ) );
    }

    /*
    * for test only - enclose a value in html tags
    * @param string $tag
    * @param string  or boolean $meat
    * @param $params array of $key=>$value
    * @return string
    */
    public function entag( $tag, $meat=false , $params=false ){
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
    * get the weekly attendance data for a user and return an array for display in a table
    * $term_id would be 1 for autumn term, 2 for spring term or 3 for summer term
    * @param int student_di
    * @param int $term_id
    * @return array of arrays
    */
    public function get_register_entries( $student_id , $term_id=false ){
        $blankcell = '&nbsp;';
        //$data = array();
        $tablerowlist = array();    //this will build into a list of lists of display values - top row for table headers etc
        $cal = new calendarfuncs( $this->params[ 'termdatelist' ] );
        if( false === $term_id ){
            $report_start = $this->params[ 'start_date' ];
            $report_end = $this->params[ 'end_date' ];
        }
        else{
            list( $report_start, $report_end ) = $this->params[ 'termdatelist' ][ $term_id ];
        }
        $weeklist = $cal->calc_sub_week_limits( $report_start, $report_end );
        $toprow = array(
            'Class',
            'Late',
            'Att',
            'Day',
            'Time'
        );       
        $weekrow = array_fill( 0, count( $toprow ) - 1 , '&nbsp;' );
        $weekrow[] = 'Week';
        foreach( $weeklist as $week ){
            $toprow[] = $cal->calc_weekno( $this->params[ 'week1' ], $week[ 0 ] );
            $weekrow[] = $cal->getreadabletime( $cal->getutime( $week[ 0 ] ), 'd/m' );
        }
        $weeknolist = $toprow;

        $tablerowlist[ 'headers' ] = $toprow;
        $tablerowlist[ 'weeks' ] = $weekrow;

        $courselist = $this->get_courselist( $student_id );
        $timefield = $this->params[ 'timefield' ];
        $attendance_data = array();     //will build into a list of stats for each course-weekday
        foreach( $courselist as $course ){
            foreach( $weeklist as $week ){
                if( $rowlist = $this->get_attendance_details( $student_id, $course[ 'course_id' ], array(), false, $week[ 0 ], $week[ 1 ] ) ){
                    //var_crap( $cal->calc_day_of_week( $row[ $timefield ] ) );
                    foreach( $rowlist as $row ){
                        $weekno = $cal->calc_weekno( $this->params[ 'week1' ], $week[ 0 ] );
	                    $row_id = $course[ 'course_id' ] . " " . $course[ 'course_title' ] . " " . $row[ 'dayname' ];
                        if( !in_array( $row_id, array_keys( $tablerowlist ) ) ){
                            //new row
	                        $row_visible_id = $course[ 'course_id' ] . " " . $course[ 'course_title' ]; 
                            $tablerowlist[ $row_id ] = array( $row_visible_id ); 
		                    $tablerowlist[ $row_id ][] = false;     //late
		                    $tablerowlist[ $row_id ][] = false;     //att
		                    $tablerowlist[ $row_id ][] = $row[ 'dayname' ]; 
		                    $tablerowlist[ $row_id ][] = $row[ 'clocktime' ]; 

                            $attendance_data[ $row_id ] = array(
                                'possible' => 0,
                                'present' => 0,
                                'late' => 0,
                                'absent' => 0
                            );
                        }
                        else{
                        }
                        $col = count( $tablerowlist[ $row_id ] );
                        //match table column to week no
                        while( $weeknolist[ $col ] < $weekno ){
                            $col++;
                            $tablerowlist[ $row_id ][] = $blankcell;
                        }
                        $tablerowlist[ $row_id ][] = $this->decide_attendance_symbol( $row[ 'attendance_code' ] );
                        $attendance_data[ $row_id ] = $this->modify_attendance_data( $attendance_data[ $row_id ], $row[ 'attendance_code' ] );

                    }
                }
            }
        }
        foreach( $tablerowlist as $row_id=>$row ){
            //calc late and attendence percentages for each row
            if( in_array( $row_id, array_keys( $attendance_data ) ) ){
                $attendance = $attendance_data[ $row_id ];
                $tablerowlist[ $row_id ][ 1 ] = $this->format_percentage( $attendance[ 'late' ] / $attendance[ 'present' ] );
                $tablerowlist[ $row_id ][ 2 ] = $this->format_percentage( $attendance[ 'present' ] / $attendance[ 'possible' ] );
            }
        }
        return $tablerowlist;
        //return $data;
    }

    protected function modify_attendance_data( $attendance_data, $code ){
        $attendance_data[ 'possible' ]++;
        if( in_array( $code, $this->params[ 'late_code_list' ] ) ){
            $attendance_data[ 'late' ]++;
        }
        if( in_array( $code, $this->params[ 'present_code_list' ] ) ){
            $attendance_data[ 'present' ]++;
        }
        if( in_array( $code, $this->params[ 'absent_code_list' ] ) ){
            $attendance_data[ 'absent' ]++;
        }
        return $attendance_data;
    }

    function decide_attendance_symbol( $code ){
        if( in_array( $code, $this->params[ 'late_code_list' ] ) ){
            return 'L';
        }
        elseif( in_array( $code, $this->params[ 'present_code_list' ] ) ){
            return '/';
        }
        elseif( in_array( $code, $this->params[ 'absent_code_list' ] ) ){
            return '#';
        }
        else{
            return $code;
        }
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
