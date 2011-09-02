<?php

//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_tab.php');

class ilp_dashboard_archive_tab extends ilp_dashboard_tab {
	
	public		$student_id;
	public 		$filepath;	
	public		$linkurl;
	public 		$selectedtab;
	public		$role_ids;
	public 		$capability;
	
	
	function __construct($student_id=null,$course_id=null)	{
		global 	$CFG,$USER,$PAGE;
		
		$this->linkurl					=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id=".$student_id."&course_id={$course_id}";
		
		$this->student_id	=	$student_id;
		
		$this->course_id	=	$course_id;
		
		$this->selectedtab	=	false;
		
		//call the parent constructor
		parent::__construct();
		
	}
	
	/**
	 * Return the text to be displayed on the tab
	 */
	function display_name()	{
		return	get_string('ilp_dashboard_archive_tab_name','block_ilp');
	}
	
    /**
     * Override this to define the second tab row should be defined in this function  
     */
    function define_second_row()	{
    	global 	$CFG,$USER,$PAGE,$OUTPUT,$PARSER;
    	
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
		if (!empty($this->plugin_id)) {	
			$this->secondrow[]	=	array('id'=>2,'link'=>$this->linkurl,'name'=>'archive report name');
		}
    }
    
    
    /**
     * Override this to define the third tab row should be defined in this function  
     */
    function define_third_row()	{
    	
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
    	if (!empty($this->plugin_id) && !empty($this->selectedtab)) {	
    	
    		
    	}
    	    	
    }

	
	
	/**
	 * Returns the content to be displayed 
	 *
	 * @param	string $selectedtab the tab that has been selected this variable
	 * this variable should be used to determined what to display
	 * 
	 * @return none
	  */
	function display($selectedtab=null)	{
		global 	$CFG, $PAGE, $USER, $OUTPUT, $PARSER;
		
		//get the selecttab param if has been set
		$this->selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

		//get the tabitem param if has been set
		$this->tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_CLEAN);
		
		//split the selected tab id on up 3 ':'
		$seltab	=	explode(':',$selectedtab);
					
		//if the seltab is empty then the highest level tab has been selected
		if (empty($seltab))	$seltab	=	array($selectedtab); 
									
		//var_dump($this->tabitem);
		
		$pluginoutput	=	"";

		if ($this->dbc->get_user_by_id($this->student_id)) {
	
			//called by script to display	
			
			
			
		} else {
			$pluginoutput	=	get_string('studentnotfound','block_ilp');
		}
			
		return $pluginoutput;
	}

	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 function language_strings(&$string) {
        $string['ilp_dashboard_archive_tab'] 					= 'Archive';
        $string['ilp_dashboard_archive_tab_name'] 				= 'Archives';
        
	        
        return $string;
    }
	
	
	/**
 	  * Adds config settings for the plugin to the given mform
 	  * by default this allows config option allows a tab to be enabled or dispabled
 	  * override the function if you want more config options REMEMBER TO PUT 
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
