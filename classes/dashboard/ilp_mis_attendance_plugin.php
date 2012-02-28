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
require_once($CFG->dirroot . '/blocks/ilp/classes/dashboard/ilp_mis_plugin.php');

//require the ilp_mis_connection.php file 
require_once($CFG->dirroot . '/blocks/ilp/db/ilp_mis_connection.php');


abstract class ilp_mis_attendance_plugin extends ilp_mis_plugin	{
    
	public function __construct($params = array())	{
        parent::__construct($params);
    }

    /*
    * go to status item table to  get the background colours for table cells
    * sets class variables for use in format_background_by_value()
    */
    protected function init_bgcolours(){
/*
        these are the wrong colours !
        global $DB;
        $this->passcolour = $DB->get_record( 'block_ilp_plu_sts_items', array( 'passfail' => ILP_STATE_PASS ) )->value;
        $this->failcolour = $DB->get_record( 'block_ilp_plu_sts_items', array( 'passfail' => ILP_STATE_FAIL ) )->value;
        $this->middlecolour = $DB->get_record( 'block_ilp_plu_sts_items', array( 'passfail' => ILP_STATE_UNSET ) )->value;
*/
        $this->passcolour = get_config( 'block_ilp' , 'passcolour' );
        $this->failcolour = get_config( 'block_ilp' , 'failcolour' );
        $this->middlecolour = get_config( 'block_ilp' , 'midcolour' );
    }

    /*
    * take a table cell with a percentage and return a span with a background colour
    * according to config settings
    * @param string $percentage
    * @return string
    */
    protected function format_background_by_value( $percentage ){
        global $CFG;
        $n = intval( $percentage );
        $ceiling = get_config( 'block_ilp', 'passpercent' );
        $floor = get_config( 'block_ilp', 'failpercent' );
        $colour = $this->middlecolour;

        //get the colours for each status

        if( $n <= $floor ){
            $colour = $this->failcolour;
        }
        elseif( $n >= $ceiling ){
            $colour = $this->passcolour;
        }
        //return html_writer::tag( 'span', $percentage, array( 'style' => "background-color:$colour;display:block" ) );
        return  "<span style='background-color:$colour;display:block'>$percentage</span>";
    }

    /*
    * take number to be displayed in a table, and format it as a percentage
    * @param float $decimal
    * @return string
    */
    protected function percent_format( $inpdecimal , $percentagealready=false , $colourbg=true ){
        if( $percentagealready ){
            $decimal = str_replace( '%' , '' , $inpdecimal );
        }
        else{
            $decimal = $inpdecimal;
        }
        if( !is_numeric( $decimal ) ) return $inpdecimal;   //if input is not numeric, simply return it untouched
        if( $percentagealready ){
            $percentage = number_format( $decimal, 0 );
        }
        else{
            $percentage = number_format( 100 * $decimal, 0 );
        }
        $percentage .= '%';
        if( $colourbg ){
            $this->init_bgcolours();
            return $this->format_background_by_value( "$percentage" );
        }
        else{
            return "$percentage";
        }
    }


}
