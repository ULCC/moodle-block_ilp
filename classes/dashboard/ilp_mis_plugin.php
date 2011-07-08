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
