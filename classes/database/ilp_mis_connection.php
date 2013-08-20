<?php
/**
 * Class used to create a connection to a mis database and to perform subsequent queries needed to extract data
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

/**
* include the standard Adodb library
* if adodb is removed from moodle in the future, we might need
* to include it specially within ILP
*/
$adodb_dir = $CFG->dirroot . '/lib/adodb';
require_once( "$adodb_dir/adodb.inc.php" );
require_once( "$adodb_dir/adodb-exceptions.inc.php" );
require_once( "$adodb_dir/adodb-errorhandler.inc.php" );


class ilp_mis_connection{

    protected $db;
    public 	$errorlist;                   //collect a list of errors
    public	$prelimcalls;				  //calls to be executed before the sql is called

    /**
     * Constructor function
    * @param array $cparams arguments used to connect to a mis db. array keys:
    * 			type: the type of connection mssql, mysql etc
    * 			host: host connection string
    * 			user: the username used to connect to db
    * 			pass: the password used to connect to the db
    * 			dbname: the dbname
    *
    * @return bool true if not errors encountered false if otherwise
    */
    public function __construct( $cparams=array()){
        global $CFG;
        $this->db = false;
        $this->errorlist = array();
        $this->prelimcalls	=	array();

        $dbconnectiontype	=	(!empty($cparams['type'])) 	? $cparams['type']	: 	get_config( 'block_ilp', 'dbconnectiontype' );

        //if the dbconnection is empty return false
       if (empty($dbconnectiontype)) return false;

        $host	=	(!empty($cparams['host'])) 	? $cparams['host']	: 	get_config( 'block_ilp', 'dbhost' );
        $user	=	(!empty($cparams['user'])) 	? $cparams['user']	: 	get_config( 'block_ilp', 'dbuser' );
        $pass	=	(!empty($cparams['pass'])) 	? $cparams['pass']	: 	get_config( 'block_ilp', 'dbpass' );
        $dbname	=	(!empty($cparams['dbname'])) 	? $cparams['dbname']	: 	get_config( 'block_ilp', 'dbname' );

        //build the connection
        $connectioninfo = $this->get_mis_connection($dbconnectiontype,$host,$user,$pass,$dbname);

        //return false if any errors have been found (we can display errors if wanted)
        $this->errorlist = $connectioninfo[ 'errorlist' ] ;
        if( !empty($this->errorlist))	return false;

        //give the connection to the db var
        $this->db = $connectioninfo[ 'db' ];
        return true;
    }

    /**
     *
     * Creates a connection to a database using the values given in the arguments
     * @param string $type the type of connection to be used
     * @param string $host the hosts address
     * @param string $user the username that will be used to connect to db
     * @param string $pass the password used in conjunction with the username
     * @param string $dbname the name of the db that will be used
     */
    public function get_mis_connection( $type, $host, $user, $pass, $dbname ){
        $errorlist = array();
        $db = false;

        //trim any space chars (which seem to pass empty tests) and if empty return false
        $trimtype   =  trim($type);
        if (empty($trimtype))  return false;

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

    /**
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

    /**
     * Takes an array in the format array($a=>array($b=> $c)) and returns
     * a string in the format $a $b $c
     * @param array $paramarray the params that need to be converted to
     * a string
     */
    function arraytostring($paramarray)	{
    	$str	=	'';
    	$and	=	'';
    	if (!empty($paramarray) && is_array($paramarray))
    	foreach ($paramarray as $k => $v) {
    		$str	=	"{$str} {$and} ";
    		//$str	.=	(is_array($v)) ?	$k." ".$this->arraytostring($v) :	" $k $v";
			//remove all ~ from fieldname - this is so that when a field is used twice in a query,
			//you can use the ~ to make a unique array key, but still generate sql with the simple fieldname
			//this will cause problems if the underlying database table has a fieldname with a ~ in it
    		$str	.=	(is_array($v)) ?	str_replace( '~' , '', $k ) ." ".$this->arraytostring($v) :	" $k $v";
    		$and	=	' AND ';
    	}

    	return $str;
    }



    /**
     * builds an sql query using the given parameter
     *
     * @param string $table the name of the table or view that will be queried
     * @param array  $whereparams array holding params that should be used in the where statement
     * 				 format should be $k = field => array( $k= operand $v = field value)
     * 				 e.g array('id'=>array('='=>'1')) produces id = 1
     * @param mixed  $fields array or string of the fields that should be returned
     * @param array  $addionalargs additional arguments that may be used the:
     * 				 'sort' the field that should be sorted by and DESC or ASC
     * 				 'group' the field that results should be grouped by
     * 				 'lowerlimit' lower limit of results
     * 				 'upperlimit' should be used in conjunction with lowerlimt to limit results
     */
    function sql_for_table_values($table,$whereparams=null,$fields='*',$addionalargs=null) {

    	//check if the fields param is an array if it is implode
    	$fields 	=	(is_array($fields))		?	implode(', ',$fields)	:	$fields;

    	//create the select statement
    	$select		=	"SELECT		{$fields} ";

    	//create the from
    	$from		=	"FROM		{$table} ";

    	//get the
    	$wheresql		=	$this->arraytostring($whereparams);

    	$where			=	(!empty($wheresql)) ? "WHERE {$wheresql} "	: 	"";

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

    	return $select.$from.$where.$sort.$group.$limit;
    }

    /**
     * builds an sql query using the given parameter and returns result
     *
     * @param string $table the name of the table or view that will be queried
     * @param array  $whereparams array holding params that should be used in the where statement
     * 				 format should be $k = field => array( $k= operand $v = field value)
     * 				 e.g array('id'=>array('='=>'1')) produces id = 1
     * @param mixed  $fields array or string of the fields that should be returned
     * @param array  $addionalargs additional arguments that may be used the:
     * 				 'sort' the field that should be sorted by and DESC or ASC
     * 				 'group' the field that results should be grouped by
     * 				 'lowerlimit' lower limit of results
     * 				 'upperlimit' should be used in conjunction with lowerlimt to limit results
     */
    function return_table_values($table,$whereparams=null,$fields='*',$addionalargs=null) {
       return $this->dbquery_sql($this->sql_for_table_values($table,$whereparams,$fields,$addionalargs));
    }

    function arraytovar($val) {
    	if (is_array($val)) {
    		if (!is_array(current($val))) {
    			return current($val);
    		} else {
    			return $this->arraytovar(current($val));
    		}
    	}

    	return $val;
    }

    /**
     *
     * builds a stored procedure query using the arguments
     * @param string $procedurename the name of the stored proceudre being called
     * @param mixed array or string $procedureargs variables passed to stored procedure
     *
     * @return mixed
     */
    function sql_for_stored_values($procedurename,$procedureargs='') {
       if (is_array($procedureargs)) {
          $temp	=	array();
          foreach ($procedureargs as $p) {
             $val	=	$this->arraytovar($p);

             if (!empty($val)) {
                $temp[]	=	$val;
             }
          }

          $args	=	implode(', ',$temp);
       } else {
          $args	=	$procedureargs;
       }
       return "EXECUTE {$procedurename} {$args}";
    }

    /**
     *
     * builds a stored procedure query using the arguments given and returns the result
     * @param string $procedurename the name of the stored proceudre being called
     * @param mixed array or string $procedureargs variables passed to stored procedure
     *
     * @return mixed
     */
    function return_stored_values($procedurename,$procedureargs='') {
       return $this->dbquery_sql($this->sql_for_stored_values($procedurename,$procedureargs));
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

    /**
     * This function makes any calls to the database that need to be made before the sql statement is run
     * The function uses the $prelimcalls var
     */
    private function make_prelimcall()	{
    	if (!empty($this->prelimcalls))	{
    		foreach ($this->prelimcalls as $pc)	{
	    		try {
		        	$res = $this->db->Execute( $pc );
				} catch (exception $e) {
					//we wont do anything if these calls fail
				}
    		}
    	}
    }

    /**
    * executes the given sql query with safety checks
    * @param string $sql
    * @return array of arrays or false
    */
    function dbquery_sql($sql)
    {
    	$result		= (!empty($this->db)) ? $this->execute($sql) : false;
    	return		(!empty($result->fields))	?	$result->getRows() :	false;
    }

    /**
    * executes the given sql query
    * @param string $sql
    * @return array of arrays
    */
    public function execute( $sql){
    	$this->make_prelimcall();
    	try {
           $res = $this->db->Execute( $sql );
        } catch (exception $e) {
           return false;
        }
        return $res;
    }

    /**
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
