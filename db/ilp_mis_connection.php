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
    public function __construct( $cparams=array(), $debug=false ){
        global $CFG;
        $this->db = false;
        $this->errorlist = array();

        //set default conenction settings        
        $this->connectionparams = array(
		    'dbconnectiontype' => get_config( 'block_ilp', 'dbconnectiontype' ),
		    'host' => get_config( 'block_ilp', 'dbhost' ),
		    'user' => get_config( 'block_ilp', 'dbuser' ),
		    'pass'=> get_config( 'block_ilp', 'dbpass' ),
		    'dbname' => get_config( 'block_ilp', 'dbname' )
        );
       
/*
        foreach( $this->settable_params as $var ){
                $this->params[ $var ] = false;
        }
*/
        
        // take the params given and override the default settings if necessary 
        $this->set_params( $this->connectionparams, $cparams );
        
        //build the connection
        $connectioninfo = $this->get_mis_connection( 
            $this->connectionparams[ 'dbconnectiontype' ],  
            $this->connectionparams[ 'host' ], 
            $this->connectionparams[ 'user' ], 
            $this->connectionparams[ 'pass' ], 
            $this->connectionparams[ 'dbname' ] 
        );
        
        //check if there was an error when connecting
        if( $errorlist = $connectioninfo[ 'errorlist' ] ){
            //var_crap( $errorlist );exit;
        }
        
        //merge errors from ? and the connection then return false (we can display errors if wanted)
        $this->errorlist = array_merge( $this->errorlist, $connectioninfo[ 'errorlist' ] );
        if( $this->errorlist ){
            //var_crap( $this->errorlist );
            return false;
        }
        
        //give the connection to the db var
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
    function arraytostring($paramarray)	{
    	$str	=	'';
        $and = ' AND ';
    	if (!empty($paramarray) && is_array($paramarray)) {
	    	foreach ($paramarray as $k => $v) {
	    		$str	=	"{$str} {$and} ";
	    		$str	.=	(is_array($v)) ?	$k." ".$this->arraytostring($v) :	" $k = $v";
	    		//$and	=	' AND ';
	    	}
    	}
    	
    	return "WHERE $str";
    }
*/


    protected function arraytostring( $paramarray, $and=' AND ' ){
        $whereandlist = array();
        foreach( $paramarray as $key=>$value ){
            if( is_array( $value ) ){
                //not sure what to do here
            }
            elseif( is_numeric( $value ) ){
                $whereandlist[] = "$key = $value";
            }
            elseif( is_string( $value ) ){
                $whereandlist[] = "$key = '$value'";
            }
        }
        return implode( $and, $whereandlist );
    }
    
    
    
    /**
     * builds an sql query using the given parameter and returns the results of the query 
     * 
     * @param string $table the name of the table or view that will be queried
     * @param array  $whereparams array holding params that should be used in the where statement
     * 				 format should be $k = field => array( $k= operand $v = field value) 
     * 				 e.g array('id'=>array('='=>'1')) produces id = 1  
     * @param array $fields 
     * @param  $addionalargs
     */
    
    
    function return_table_values($table,$whereparams=null,$fields='*',$addionalargs=null) {
    	
    	//check if the fields param is an array if it is implode  
    	$fields 	=	(is_array($fields))		?	implode(',',$fields)	:	$fields;		
    	   	
    	//create the select statement
    	$select		=	"SELECT		{$fields} ";
    	
    	//create the from 
    	$from		=	"FROM		{$table} ";
    	
    	//get the 
    	$wheresql		=	$this->arraytostring($whereparams);
    	
    	$where			=	(!empty($wheresql)) ? "WHERE $wheresql "	: 	"";
    	
    	$sort		=	'';
    	if (isset($addionalargs['sort']))	$sort		=	(!empty($addionalargs['sort']))	? "ORDER BY {$addionalargs['sort']} "	: "";

    	$group		=	'';
    	if (isset($addionalargs['group']))	$group		=	(!empty($addionalargs['group']))	? "GROUP BY {$addionalargs['group']} "	: "";
    	
    	$limit		=	'';
    	if (isset($addionalargs['lowerlimt']))	$limit		=	(!empty($addionalargs['lowerlimit']))	? "LIMIT {$addionalargs['lowerlimit']} "	: "";
    	
    	if (isset($addionalargs['upperlimt']))	{
    		if (empty($limit)) {
    			$limit		=	(!empty($addionalargs['upperlimt']))	? "LIMIT {$addionalargs['upperlimt']} "	: "";		
    		} else {
    			$limit		.=	(!empty($addionalargs['upperlimt']))	? ", {$addionalargs['upperlimt']} "	: "";
    		}
   		}
   	
    	$sql		=	$select.$from.$where.$sort.$group.$limit;
        return $this->Execute( $sql )->getRows();
    }
    
    
    function return_stored_values($table,$args=null) {

    	
    	
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
    * if there is no problem, this function returns false
    * thus example usage:
    * if( !$errorlist = $mis->find_mis_connection_problem() ){
    *       //do stuff
    * }
    *  else{
    *       echo implode( "\n", $errorlist );   
    * }
    * test the db connection configured from the settings and produce a helpful message
    * @return mixed
    */
    public function find_mis_connection_problem(){
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
        if( $valid ){
            return false;   //ie no problem found
        }
        else{
            return $msglist;//return helpful message
        }
    }

    /**
    * step through an array of $key=>$value and assign them 
    * to the class $params array
    * @param array $arrayvar the array that will hold the params 
    * @param array $params the params that will be passed to $arrayvar
    * @return 
    */
    protected function set_params( &$arrayvar,$params ){
        foreach( $params as $key=>$value ){
            $arrayvar[ $key ] = $value;  
        }
    }

    /* 
    * @param string $sql
    * @return array of arrays      
    */
    public function execute( $sql , $arg=false ){
        $res = $this->db->Execute( $sql, $arg ) or die( $this->db->ErrorMsg() );
        return $res;
    }

    /*
    * intended to return just the front item from an array of arrays (eg a recordset)
    * if just the array is sent, just the first row will be returned
    * if 2nd argument sent, then just the value of that field in the first row will be returned
    * @param array $a
    * @param string $fieldname
    * @return mixed (array or single value)
    */
    public static function get_top_item( $a , $fieldname=false ){
        $toprow = array_shift( $a );
        if( $fieldname ){
            return $toprow[ $fieldname ];
        }
        return $toprow;
    }

        
}
