<?php

//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_tab.php');

class ilp_dashboard_entries_tab extends ilp_dashboard_tab {
	
	public		$student_id;
	public 		$filepath;
	public		$linkurl;
	public 		$selectedtab;
	
	
	function __construct($student_id=null)	{
		global 	$CFG;
		
		$this->linkurl				=	$CFG->wwwroot.$_SERVER["SCRIPT_NAME"]."?user_id=".$student_id;		
		
		$this->student_id	=	$student_id;
		$this->filepath		=	$CFG->dirroot."/blocks/ilp/classes/dashboard/tabs/entries/overview.php";

		
		//set the id of the tab that will be displayed first as default
		$this->default_tab_id	=	$this->plugin_id.'-1';
		
		//call the parent constructor
		parent::__construct();
	}
	
	/**
	 * Return the text to be displayed on the tab
	 */
	function display_name()	{
		return	get_string('ilp_dashboard_entries_tab_name','block_ilp');
	}
	
    /**
     * Override this to define the second tab row should be defined in this function  
     */
    function define_second_row()	{
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
		if (!empty($this->plugin_id)) {		
			$this->secondrow	=	array();
			
			//NOTE names of tabs can not be get_string as this causes a nesting error 
			$this->secondrow[]	=	array('id'=>'1','link'=>$this->linkurl,'name'=>'Overview');
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
		global 	$CFG;
		
		
		$pluginoutput	=	"";
		
		if ($this->dbc->get_user_by_id($this->student_id)) {
				
				//start buffering output
				ob_start();
			
				
					//get all enabled reports in this ilp
					$reports		=	$this->dbc->get_reports(ILP_ENABLED);
					$reportslist	=	array();
					if (!empty($reports)) {	
						
						//cycle through all reports and save the relevant details
						foreach ($reports	as $r) {
							$detail					=	new object();
							$detail->report_id		=	$r->id;
							$detail->name			=	$r->name;
							//does this report have a state field
							
							//get all entries for this student in report
							$detail->entries		=	($this->dbc->count_report_entries($r->id,$this->student_id)) ? $this->dbc->count_report_entries($r->id,$this->student_id) : 0;
							
							if ($this->dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {
								//get the number of entries achieved
								$detail->achieved	=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_PASSFAIL_PASS);
							}
		
							//get the last updated report entry
							$lastentry				=	$this->dbc->get_lastupdatedentry($r->id,$this->student_id);
							
							$detail->lastmod	=	(!empty($lastentry)) ?  userdate($lastentry->timemodified , get_string('strftimedate', 'langconfig')) : 'n/a';	
							$reportslist[]			=	$detail;
						}
					}

					//we need to buffer output to prevent it being sent straight to screen
					
					require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_entries_tab.html');
					
					
				//pass the output instead to the output var
				$pluginoutput = ob_get_contents();
			
				ob_end_clean();
				
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
        $string['ilp_dashboard_entries_tab'] 					= 'entries tab';
        $string['ilp_dashboard_entries_tab_name'] 				= 'Entries';
        $string['ilp_dashboard_entries_tab_overview'] 			= 'Overview';
        $string['ilp_dashboard_entries_tab_lastupdate'] 		= 'Last Update';
	        
        return $string;
    }
	
	
	
	
	
}
