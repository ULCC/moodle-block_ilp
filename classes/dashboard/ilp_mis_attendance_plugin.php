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
        return intval( 100 * $value ) . '%';
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
        return $totalpresent / $total;
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
        return 1 - ( $totallate / $totalpresent );
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
	
}
