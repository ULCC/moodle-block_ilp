<?php
/**
 * A class that holds methods and attributes common to all element dashboard tab
 * classes.
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

/**Quick note on how the tab ids and the tab hierachy ($selecttab) *****
 *  
 *  Tab ids should be set using an unique identifier the highest level tab should have the id of the plugin in 
 *  the ilp_dash_tab table (plugin_id). This ensures that all tabs no matter where they are will have a unique id. All subsequent
 *  levels of tab should have plugin_id and a ':' prefixed to it Again this will ensure that all other tabs have unique values 
 */

//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_plugin.class.php');

class ilp_dashboard_tab extends ilp_plugin {
	
	public		$secondrow;
	public		$thirdrow;
	public		$plugin_id;
	

    /**
     * Constructor
     */
    function __construct() {
    	global	$CFG;
    	
		//set the directory where plugin files of type ilp_dashboard_tab are stored  
    	$this->plugin_class_directory	=	$CFG->dirroot."/blocks/ilp/plugins/tabs";
    	
    	//set the table that the details of these plugins are stored in
    	$this->plugintable	=	"block_ilp_dash_tab";

    	//call the parent constructor
    	parent::__construct();
    	
    	//get the id of the record for this plugin in the db
		$pluginrecord		=	$this->dbc->get_plugin_by_name('block_ilp_dash_tab',get_class($this));
		
		$this->plugin_id	= (!empty($pluginrecord)) ?	$pluginrecord->id : false;
		
		//call the define_second_row function
    	$this->define_second_row();
		
    	//call the define_third_row function  
		$this->define_third_row();
    }

    /**
     * Installs any new plugins
     */
    public static function install_new_plugins($dbplugins=array(),$plugin_class_directory="") {
    	global $CFG;
    	
        // include the ilp db
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the ilp db class needed as this function will be called 
        //when not in object context
        $dbc = new ilp_db();
    	
    	//call the install new plugins function from the parent class
    	//pass the list of plugins currently installed to it
        parent::install_new_plugins($dbc->get_dashboard_tabs(),$CFG->dirroot."/blocks/ilp/plugins/tabs");

    }
    
    /**
     * Override this to define the second tab row should be defined in this function  
     */
    function define_second_row()	{
    	$this->secondrow	=	false;
    }
    
    
    /**
     * Override this to define the third tab row should be defined in this function  
     */
    function define_third_row()	{
    	$this->thirdrow		=	false;    	
    }
    
    
    /**
     * 
     * Returns the secondrow of this plugins tabs 
     */
    function second_row()	{
    	//call the define third row function to define the third row
		//$this->define_second_row();
    	$row	=	false;

		//if the secondrow var is not empty then 
		if (!empty($this->secondrow))	{
			$row = array();
			
			//get each tab
			foreach($this->secondrow	as	$key => $value)	{
				//this is the unique id of the tab
				$tabitem_id	=	"{$this->plugin_id}:{$value['id']}";
				
				//this sets up the query string for the tab
				$params		=	"&tabitem={$tabitem_id}&selectedtab={$this->plugin_id}";
				
				//make the tab object 
				$row[]		=	new tabobject($tabitem_id, $value['link'].$params, $value['name']);
			}
		}
		return	$row;
	}
	
	
	/**
     * 
     * Returns the thirdrow of this plugins tab 
     */
	function third_row()		{
		
		//call the define third row function to define the third row
		//$this->define_third_row();
		
		$row	=	false;
		
		//if the thirdrow var is not empty then 
		if (!empty($this->thirdrow))	{
			$row = array();
			
			//get each 
			foreach($this->thirdrow	as	$key => $value)	{
				//this is the unique id of the tab
				$tabitem_id	=	"{$this->plugin_id}:{$value['id']}";
				
				//this sets up the query string for the tab
				$params		=	"&tabitem={$tabitem_id}&selectedtab={$this->plugin_id}";
				
				//make the tab object 
				$row[]		=	new tabobject($tabitem_id, $value['link'].$params, $value['name']);
			}
		}
		
		
		return	$row;
	}

	/**
	 * Returns true or false depending on whether the given $selectedtab var
	 * is the id of any the tabs in this class.
	 * 
	 * @param string $selectedtab the id of the tab that is current in use 
	 */
	function is_selected($selectedtab)	{
		return in_array($selectedtab,$this->tabids());
	}
	
	
	/**
	 * Produces a array full of all tab ids for this class 
	 */
	function tabids()	{
				
		$tabids	=	array();
		
		//first id should be the main tabs - which is always set to the id of the class in the block_ilp_dash_tab table  
		$tabids[] = 	$this->plugin_id;
		
		//if the thirdrow var is not empty then 
		if (!empty($this->secondrow))	{
			//get each 
			foreach($this->secondrow	as	$key => $value)	{
				$tabids[]		=	$this->plugin_id.":".$value['id'];
			}
		}
		
		//if the thirdrow var is not empty then 
		if (!empty($this->thirdrow))	{
			//get each 
			foreach($this->thirdrow	as	$key => $value)	{
				$tabids[]		=	$this->plugin_id.":".$value['id'];
			}
		}
		
		return $tabids;
	}
	
	
	private	function reconcile_tab_id($tabs,$pos)	{
		
		$return_value	=	'';
		
		//first check that the current position in the array is greater than 
		//zero
		if ($pos > 0) {	
			//make sure the position has been set
			if (isset($tabs[$pos]))	{
				$return_value	=	$tabs[$pos];
				if (isset($tabs[$pos-1])) $return_value	=	$this->reconcile_tab_id($tabs, $pos-1).":".$return_value;
			} 
		} else {
			//if the current position is zero we have no deeper to go just return zero
			$return_value	=	$tabs[$pos]; 
		} 
		
		return $return_value;
	}
	
	/**
	 * Returns an array contain all of the tabs that should be deactivated based on the given 
	 * $selectedtab variable
	 * 
	 * @param	string $selectedtab	the id of the tab that has been selected
	 * 
	 * return  mixed array contain the tab ids that should be deactivated or false
	 */
	function deactivated_tabs($selectedtabs)	{
		
		$deactivate		=	null;
		//break apart the selectedtabs on the :
		$tabids	=	explode(':',$selectedtabs);
		
		if (!empty($tabids)) {
			for($i = 0; $i < count($tabids); $i++)	{
				$deactivate[]= $this->reconcile_tab_id($tabids,$i);
			}
		}
		return $deactivate;
	}
	
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	    	
    	$classname	=	get_class($this);
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname='.$classname.'&plugintype=tab">'.get_string($classname.'_name', 'block_ilp').' '.get_string('tabsettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('mis_'.$classname, '', $link));
 	 }
	
	
	/**
 	  * Adds config settings for the plugin to the given mform
 	  * by default this allows config option allows a tab to be enabled or dispabled
 	  * override the function if you want more config options REMEMBER TO PUT _pluginstatus in it 
 	  * 
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	//get the name of the current class
 	 	$classname	=	get_class($this);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,$classname.'_pluginstatus',$options,get_string($classname.'_name', 'block_ilp'),get_string('tabstatusdesc', 'block_ilp'),0);
 	 	
 	 }
	
	
}
?>
