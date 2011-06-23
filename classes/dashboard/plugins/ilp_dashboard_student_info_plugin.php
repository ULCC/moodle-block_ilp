
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
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_plugin.php');


class ilp_dashboard_student_info_plugin extends ilp_dashboard_plugin {
	
	public		$student_id;	
	
	
	function __construct($student_id = null)	{
		//set the id of the student that will be displayed by this 
		$this->student_id	=	$student_id;
		
		//set the name of the directory that holds any files for this plugin
		$this->directory	=	'studentinfo';
		
		parent::__construct();
		
	}
	
	
	
	/**
	 * Returns the 
	 * @see ilp_dashboard_plugin::display()
	 */
	function display()	{	
		global	$CFG,$OUTPUT,$PAGE;

		//set any variables needed by the display page	
		
		//get students full name
		$student	=	$this->dbc->get_user_by_id($this->student_id);
		
		if (!empty($student))	{ 
			$studentname	=	fullname($student);
			$studentpicture	=	$OUTPUT->user_picture($student,array('size'=>100,'return'=>'true')); 
			
			//get the students current status
			$studentstatus	=	$this->dbc->get_user_status($this->student_id);
			
			if (!empty($studentstatus)) {
				$statusitem		=	$this->dbc->get_status_item_by_id($studentstatus->id);
			}   
			
			//TODO place percentage bar code into a class 
			
			$percentagebars	=	array();
						
			//set the display attendance flag to false
			$displayattendance	= false;
			
			//can the current user change the users status
			$can_editstatus	=	(!empty($access_viewotherilp) && $USER->id != $user_id) ? true : false;
			
			$passpercentage	=	get_config('block_ilp', 'passpercent');
			
			//just in case the pass percentage has not been set 
			$passpercentage	=	(empty($passpercentage)) ? ILP_DEFAULT_PASS_PERCENTAGE : $passpercentage;
			
			$failpercentage	=	get_config('block_ilp', 'failpercent');
			
			//just in case the fail percentage has not been set 
			$failpercentage	=	(empty($failpercentage)) ? ILP_DEFAULT_FAIL_PERCENTAGE : $failpercentage;
			
			//include the attendance 
			$misclassfile	=	$CFG->docroot."/blocks/ilp/classes/mis.class.php";
			
			if (file_exists($misclassfile)) {
				
				//create an instance of the MIS class
				$misclass	=	new mis();
				
				//set the student in question
				$misclass->get_student_data($this->student_id);
				
				$punch_method1 = array($misclass, 'get_total_punchuality');
				$punch_method2 = array($misclass, 'get_student_punchuality');
				$attend_method1 = array($misclass, 'get_total_attendance');
				$attend_method2 = array($misclass, 'get_student_attendance');
        
					        //check whether the necessary functions have been defined
		        if (is_callable($punch_method1,true) && is_callable($punch_method2,true)) {
		        	$misinfo	=	new stdClass();
		        	//call the get_total_punchuality function to get the total number of times the student could have been on time
		  	        $misinfo->total	=	$misclass->get_total_punchuality();
		  	        //call the get_student_punchuality fucntion to get the total number of times the student was on time
	    	        $misinfo->actual	=	$misclass->get_student_punchuality();
	    	        
	    	        	    	        //if total_possible is empty then there will be nothing to report
	    	        if (!empty($misinfo->total)) {
		    	        //calculate the percentage
		    	        
		    	        $misinfo->percentage	=	$misinfo->actual/$misinfo->total	* 100;	
	    	        
	    		        $misinfo->name	=	get_string('punchuality','block_ilp');
	    	        
	    		        //sets the colour of the percentage bar
	    	        	if ($misinfo->percentage	<= $passpercentage) $misinfo->csscolor	=	 get_config('block_ilp','failcsscolour');	
	    	       	
	    	        	if ($misinfo->percentage	> $failpercentage && $misinfo->percentage < $passpercentage) $misinfo->csscolor	=	 get_config('block_ilp','midcsscolour');	
	    	        	
	    	        	if ($misinfo->percentage	>= $passpercentage) $misinfo->csscolor	=	get_config('block_ilp','passcsscolour');	
	    	       
	    	        	
	    		        //pass the object to the percentage bars array
	    	    	    $percentagebars[]	=	$misinfo;
	    	        }
	        	}
	        	
				//check whether the necessary functions have been defined
		        if (is_callable($attend_method1,true) && is_callable($attend_method2,true)) {
		        	$misinfo	=	new stdClass();
		        	//call the get_total_punchuality function to get the total number of times the student could have been on time
		  	        $misinfo->total	=	$misclass->get_total_attendance();
		  	        //call the get_student_punchuality fucntion to get the total number of times the student was on time
	    	        $misinfo->actual	=	$misclass->get_student_attendance();
	    	        
	    	        //if total_possible is empty then there will be nothing to report
	    	        if (!empty($misinfo->total)) {
	    	        	//calculate the percentage
	    	        	$misinfo->percentage	=	$misinfo->actual/$misinfo->total	* 100;
	    	        
	    	        	$misinfo->name	=	get_string('attendance','block_ilp');
	    	        		
   	    		        //sets the colour of the percentage bar
	    	        	if ($misinfo->percentage	<= $passpercentage) $misinfo->csscolor	=	 get_config('block_ilp','failcsscolour');	
	    	       	
	    	        	if ($misinfo->percentage	> $failpercentage && $misinfo->percentage < $passpercentage) $misinfo->csscolor	=	 get_config('block_ilp','midcsscolour');	
	    	        	
	    	        	if ($misinfo->percentage	>= $passpercentage) $misinfo->csscolor	=	get_config('block_ilp','passcsscolour');	
	    	       	
	    	        	$percentagebars[]	=	$misinfo;
	    	        }
	    	        
	        	}
				
				
			}
			
			//if the user has the capability to view others ilp and this ilp is not there own 
			//then they may change the students status otherwise they can only view 
			
			
			
			
			
			//get all enabled reports in this ilp
			$reports		=	$this->dbc->get_reports(ILP_ENABLED);
			
			
			//we are going to output the add any reports that have state fields to the percentagebar array 
			foreach ($reports as $r) {
				if ($this->dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {

					$reportinfo				=	new stdClass();
					$reportinfo->total		=	$this->dbc->count_report_entries($r->id,$this->student_id);
					$reportinfo->actual		=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_PASSFAIL_PASS);
					
					    	        //if total_possible is empty then there will be nothing to report
	    	        if (!empty($reportinfo->total)) {
	    	        	//calculate the percentage
	    	        	$reportinfo->percentage	=	$reportinfo->actual/$reportinfo->total	* 100;
	    	        
	    	        	$reportinfo->name	=	$r->name;
	    	        	
	    	        	     //sets the colour of the percentage bar
	    	        	if ($reportinfo->percentage	<= $passpercentage) $reportinfo->csscolor	=	 get_config('block_ilp','failcsscolour');	
	    	       	
	    	        	if ($reportinfo->percentage	> $failpercentage && $reportinfo->percentage < $passpercentage) $reportinfo->csscolor	=	 get_config('block_ilp','midcsscolour');	
	    	        	
	    	        	if ($reportinfo->percentage	>= $passpercentage) $reportinfo->csscolor	=	get_config('block_ilp','passcsscolour');	
	    	        	
	    	        	$percentagebars[]	=	$reportinfo;
	    	        }
					
				}
			}
			
			//we need to buffer output to prevent it being sent straight to screen
			ob_start();
			
			
			
			require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/plugins/'.$this->directory.'/ilp_dashboard_student_info.html');
			
			//pass the output instead to the output var
			$pluginoutput = ob_get_contents();
			
			ob_end_clean();
			
			
			return $pluginoutput;
			
		} else {
			//the student was not found display and error 
			print_error('studentnotfound','block_ilp');
		}
		
		
		
		
	}
	
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	function userstatus_select($selected_value =null)	{
		global	$USER, $CFG;
		$form	=	"<span id='studentstatusform'>";

		$statusitems	=	$this->dbc->get_user_status_items();
		
		if (!empty($statusitems)) {
			$form	.= "<form action='{$CFG->wwwroot}/blocks/ilp/actions/save_userstatus.php' method='GET' >";
					
			$form	.=	"<input type='hidden' name='student_id' id='student_id' value='{$this->student_id}' >";
			$form	.=	"<input type='hidden' name='user_modified_id' id='user_modified_id' value='{$USER->id}' >";
			$form	.=	"<input type='hidden' name='ajax' id='ajax' value='false' >";
			
			$form .= "<select id='select_userstatus'  >";

			foreach ($statusitems	as  $s) {
				
				$selected	=	($s->id	==	$selected_value) ? 'selected="selected"' : '';
				
				$form .= "<option value='{$s->id}' $selected >{$s->name}</option>";
			}
			
			$form .= '</select>';
			
			$form .= '<input type="submit" value="Change Status" id="studentstatussub" />';
			
			$form .= '</form>';
		} else {
			
			$form	.= 'STATUS ITEMS NOT SET PLEASE CONTACT ADMIN';
			
		}
		
		
		$form .= '</span>';
		
		return $form;
		
	}
	
	
	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	function language_strings(&$string) {
        $string['ilp_dashboard_student_info_plugin'] 					= 'student info plugin';
        $string['ilp_dashboard_student_info_plugin_name'] 				= 'student info';
	        
        return $string;
    }
	
	
	
	
	
	
	
	
}