<?php
/*
* include the standard Adodb library
* if adodb is removed from moodle in the future, we might need
* to include it specially within ILP
*/
require_once($CFG->dirroot.'/lib/adodb/adodb.inc.php');

/*
* This class is intended for querying the student database for attendance data.
* The attendance information may be in a different db, and possibly on a different platform, from the moodle db.
* Therefore we invoke a fresh Adodb for this purpose.
* The type of db and connection details are configured on the configuration page /admin/settings.php
* Names of user table and attendance table can be sent in as params.
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

    /*
    * $params array should have keys corresponding to the class variable names listed in the foreach
    * @param array $params
    * @param boolean $debug
    */
    public function __construct( $params=array(), $debug=false ){
        global $CFG;
        $this->db = false;
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
            'absent_code_list'                  //array of attendance codes classified as absent
        );
        foreach( $this->settable_params as $var ){
                $this->params[ $var ] = false;
        }
        $this->set_params( $params );
        $this->db = ADONewConnection( $this->params[ 'dbconnectiontype' ] );
        $this->db->Connect( $this->params[ 'host' ], $this->params[ 'user' ], $this->params[ 'pass' ], $this->params[ 'dbname' ] );
        return $this->db;
    }

    public function get_report( $student_id ){
        $courselist = $this->get_courselist( $student_id );
        $reportlist = array();
        foreach( $courselist as $course ){
            $course_id = $course[ 'course_id' ];
            $course_title = $course[ 'course_name' ];
            $reportlist[] = $this->get_attendance_report( $course_id, $student_id, $course_title );
        }
        return array(
            'student_details' => $this->get_student_details( $student_id ),
            //'courselist' => $courselist,
            'reportlist' => $reportlist
        );
    }

    protected function get_student_details( $student_id ){
        $student = $this->params[ 'student_table' ];
        $id = $this->params[ 'student_unique_key' ];
        $sql = " SELECT * FROM $student WHERE $id = $student_id ";
        $res = $this->execute( $sql )->getRows();
        return array_shift( $res );
    }

    protected function get_attendance_report( $course_id, $student_id, $course_name='un-named' ){
        $nof_lectures = $this->get_lecturecount( $course_id );
        $nof_attended = $this->get_attendance_details( $course_id, $student_id, $this->params[ 'present_code_list' ], true );
        $attendance = $this->format_percentage( $nof_attended / $nof_lectures );
        return array(
            'course_name' => $course_name,
            'course_id' => $course_id,
            'no. of lectures' => $nof_lectures,
            'lectures attended' => $nof_attended,
            'attendance' => $attendance
        );
    }

    public function format_percentage( $r ){
        return number_format( round( $r * 100 ) ) . '%';
    }

    protected function get_lecturecount( $course_id ){
        $lecture_table = $this->params[ 'lecture_table' ];
        $course_fk_field = $this->params[ 'lecture_courseid' ];
        $sql = "
            SELECT COUNT(*) n 
            FROM $lecture_table
            WHERE $lecture_table.$course_fk_field = $course_id
        ";
        $res = $this->execute( $sql )->getRows();
        $toprow = array_shift( $res );
        return $toprow[ 'n' ];
    }

    protected function get_courselist( $student_id ){
        $id = $this->params[ 'attendance_table_unique_key' ];
        $student_course = $this->params[ 'student_course_table' ];
        $student_id_fieldname = $this->params[ 'attendance_studentid' ];
        $course = $this->params[ 'course_table' ];
        $course_id_field = $this->params[ 'course_table_unique_key' ];
        $studentcourse_id_field = $this->params[ 'student_course_student_key' ];
        $studentcourse_course_id_field = $this->params[ 'student_course_course_key' ];
        $name = $this->params[ 'course_table_namefield' ];
        $student_id_fieldname = $this->params[ 'attendance_studentid' ];
        $sql = <<<EOQ
            SELECT $course.$course_id_field course_id, $course.$name course_name
            FROM $student_course
            JOIN $course ON $course.$course_id_field = $student_course.$studentcourse_course_id_field
            WHERE $student_course.$student_id_fieldname = $student_id
            ORDER BY course_name
EOQ;
        $res = $this->execute( $sql )->getRows();
        return $res;
    }

    public function get_attendance_details( $course_id, $student_id=0, $attendancecode_list=array(), $countonly=false ){
        if( $countonly ){
            $selectclause = "COUNT(*) n";
        }
        else{
            $selectclause = "sl.id, acode.{$this->params[ 'attendancecode_id_field' ]}";
        }
        $whereandlist = array(
            "sl.{$this->params[ 'attendance_studentid' ]} = $student_id",
            "{$this->params[ 'lecture_table' ]}.{$this->params[ 'lecture_courseid' ]} = $course_id"
        );
        if( count( $attendancecode_list ) ){
            //$whereandlist[] = "{$this->params[ 'attendancecode_table' ]}.{$this->params[ 'attendancecode_id_field' ]} IN  ('" . implode( "','" , $attendancecode_list ) . "')";
            $whereandlist[] = "acode.{$this->params[ 'attendancecode_id_field' ]} IN  ('" . implode( "','" , $attendancecode_list ) . "')";
        }
        $whereclause = implode( ' AND ' , $whereandlist );
        $sql = <<<EOQ
            SELECT $selectclause
            FROM {$this->params[ 'attendance_table' ]} sl
            JOIN {$this->params[ 'attendancecode_table' ]} acode ON acode.{$this->params[ 'attendancecode_unique_key' ]} = sl.{$this->params[ 'lecture_attendance_id' ]}
            JOIN {$this->params[ 'lecture_table' ]} lecture ON lecture.{$this->params[ 'lecture_unique_key' ]} = sl.{$this->params[ 'attendance_lectureid' ] }
            WHERE $whereclause
EOQ;
        $res = $this->execute( $sql )->getRows();
        if( $countonly ){
            $toprow = array_shift( $res );
            return $toprow[ 'n' ];
        }
        return $res;
    }

    public function set_params( $params ){
        foreach( $params as $key=>$value ){
            if( in_array( $key, $this->settable_params ) ){
                $this->params[ $key ] = $value;  
            }
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
    public function execute( $sql ){
        $res = $this->db->Execute( $sql ) or die( 'db error' );
        return $res;
    }
        
}
