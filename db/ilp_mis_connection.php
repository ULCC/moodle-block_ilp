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
    protected $user_table;
    protected $attendance_table;
    protected $user_unique_key;
    protected $attendance_foreign_key;

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
        foreach( array(
            'prefix',
            'user_table',
            'attendance_table',
            'user_unique_key',
            'attendance_foreign_key'
            ) as $var ){
                $this->params[ $var ] = false;
                if( in_array( $var, array_keys( $params ) ) ){
                    $this->params[ $var ] = $params[ $var ];
                }
        }
        $this->db = ADONewConnection( $this->params[ 'dbconnectiontype' ] );
        $this->db->Connect( $this->params[ 'host' ], $this->params[ 'user' ], $this->params[ 'pass' ], $this->params[ 'dbname' ] );
        return $this->db;
    }

    /* 
    * @param string $sql
    * @return array of arrays      
    */
    public function execute( $sql ){
        return $this->db->Execute( $sql );
    }
        
}
