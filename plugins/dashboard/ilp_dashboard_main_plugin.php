<?php
/**
 * A class used to display information on a particular student in the ilp 
 *
 *  *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_plugin.class.php');


class ilp_dashboard_main_plugin extends ilp_dashboard_plugin {
	
	public		$student_id;	
	
	
	function __construct($student_id = null,$course_id=null)	{
		//set the id of the student that will be displayed by this 
		$this->student_id	=	$student_id;
	 
		$this->course_id	=	$course_id;
		
		//set the name of the directory that holds any files for this plugin
		$this->directory	=	'main';
		
		parent::__construct();
		
	}
	
	
	
	/**
	 * Returns the 
	 * @see ilp_dashboard_plugin::display()
	 */
	function display()	{	
		global	$CFG,$OUTPUT,$PARSER, $PAGE;

		//set any variables needed by the display page	
		
		//get students full name
		$student	=	$this->dbc->get_user_by_id($this->student_id);
		
		//this variable will hold the content from the selected tab
		$tabcontent	=	"";
		
		if (!empty($student))	{ 
				
			$linkurl	=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id={$this->student_id}&course_id={$this->course_id}";
			
			//get the default tab from the settings page
            $defaulttab	= get_config('block_ilp', 'hometabdefault');

			//get the selectedtab param if it is in the url
			$selectedtab	=	$PARSER->optional_param('selectedtab',$defaulttab,PARAM_RAW);
			
			//get the actual tab item that was selected
			$tabitem		=	$PARSER->optional_param('tabitem',$defaulttab.':1',PARAM_RAW);

			$tabs = array();
   			$tabrows = array();
			
			//set the tab second row var to false
			$tabsecondrow	=	false;
				
			//set the tab third row var to false
			$tabthirdrow	=	false;
				
			//retrieve all dashboard tabs from the db
			$dashboardtabs		=	$this->dbc->get_dashboard_tabs();
			
			//set the $deactivatedtabs var to null
			$deactivatedtabs		=   null;	

			foreach	($dashboardtabs	as $dt)	{
				
				$classname	=	$dt->name;

				//find out if the tab is enabled
				$status	=	get_config('block_ilp',$classname.'_pluginstatus');
				
				$status	=	(!empty($status)) ? $status	:	0;
				
				if ($status	== ILP_ENABLED) {
	    			//include the dashboard_tab class file
	    	        include_once("{$CFG->dirroot}/blocks/ilp/plugins/tabs/{$classname}.php");
	
			        if(!class_exists($classname)) {
			            print_error('pluginclassnotfound', 'block_ilp', '', $classname);
			        }

					$dasttab	=	new $classname($this->student_id,$this->course_id);
                    $fulllink_url = $linkurl . "&selectedtab={$dt->id}&tabitem={$dt->id}";
                    $dash_tab_name = $dasttab->display_name();
					$tabrows[]	=	new tabobject($dt->id, $fulllink_url, $dash_tab_name);
	
					if ($dasttab->is_selected($selectedtab)) {

                        $PAGE->navbar->add($dash_tab_name, $fulllink_url, 'title');
						//this gets the display information from the tab plugin
						$tabcontent		=	$dasttab->display($tabitem);
	
						//returns tabs to be placed on second row
						$tabsecondrow	=	$dasttab->second_row();
						
						//returns tabs to be placed on third row
						$tabthirdrow	=	$dasttab->third_row();
						
						//get the list of tabs that should be deactivated 		
						$deactivatedtabs		=	$dasttab->deactivated_tabs($tabitem);
					} 
				
				}
			}
			
			$tabs[] = $tabrows;
			
			//if the second row var is not empty then add the second row
			if (!empty($tabsecondrow)) $tabs[] = $tabsecondrow;
			
			//if the third row var is not empty then add the second row
			if (!empty($tabthirdrow)) $tabs[] = $tabthirdrow;
			
			//we need to buffer output to prevent it being sent straight to screen
			ob_start();
			
			print_tabs($tabs,$selectedtab,$deactivatedtabs);
									
			//pass the output instead to the output var
			$pluginoutput = ob_get_contents();

			//add the content if  
			
			ob_end_clean();
			
			
			return $pluginoutput." ".$tabcontent;

		} else {
			//the student was not found display and error 
			print_error('studentnotfound','block_ilp');
		}
	}
}