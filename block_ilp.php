<?php
/**
 * Block class for the ilp
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

class block_ilp extends block_list {

    /**
     * Sets initial block variables. Part of the blocks API
     *
     * @return void
     */
    function init() {
    	global $CFG;
   	
    	//require the ilp_settings class
		require_once "$CFG->dirroot/blocks/ilp/classes/ilp_settings.class.php";

		//instantiate the ilp settings class
		$ilpsettings = new ilp_settings();
    	
        $this->title = get_string('blockname', 'block_ilp');
        $this->version = $ilpsettings->version();
        $this->cron = 43200; //run the cron at minimum once every 12 hours
    }
    
    /**
     * Sets up the content for the block.
     *
     * @return object The content object
     */
    function get_content() {
        global $CFG, $USER, $COURSE, $SITE;

        // include  db class
        require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

        // include the parser class
        require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_parser.class.php');

        // include the lib file
        require_once($CFG->dirroot.'/blocks/ilp/lib.php');

        // db class manager
        $dbc = new ilp_db();

        // get the course id
        $course_id = optional_param('id', $SITE->id, PARAM_INT);

        // get the course
        $course = $dbc->get_course($course_id);

        
        
        

        // cache the content of the block
        if($this->content !== null) {
            return $this->content;
        }
       
        //get all course that the current user is enrolled in 
		$my_courses				=	$dbc->get_user_courses($USER->id);
		$access_viewilp			=	false;
		$access_viewotherilp	= 	false;
		
		//we are going to loop through all the courses the user is enrolled in so that we can 
		//choose which display they will see 
		foreach($my_courses	as $c) {
        			$coursecontext = get_context_instance(CONTEXT_COURSE, $c->id);
			
			        //we need to get the capabilites of the current user so we can deceide what to display in the block 
        			if (has_capability('block/ilp:viewilp', $coursecontext,$USER->id,false)) {
        				$access_viewilp		=	true;
        			}
        			
        			if (has_capability('block/ilp:viewotherilp', $coursecontext,$USER->id,false)) {
        				$intial_course_id	=	$c->id;
        				$access_viewotherilp	=	true;
        								
        			}
        
		}
        
		//
		$usertutees	=	$dbc->get_user_tutees($USER->id);

		
		$this->content = new stdClass;
        $this->content->footer = '';
		
        //check if the user has the viewotherilp capability
        if (!empty($access_viewotherilp) || !empty($usertutees)) {
        
        	if (!empty($access_viewotherilp)) {    	
			 	$label = get_string('mycoursegroups', 'block_ilp');
	         	$url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$intial_course_id}";
	         	$this->content->items[] = "<a href='{$url}'>{$label}</a>";
	         	$this->content->icons[] = "";
    		}
    		
        	if (!empty($usertutees)) {    	
			 	$label = get_string('mytutees', 'block_ilp');
	         	$url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
	         	$this->content->items[] = "<a href='{$url}'>{$label}</a>";
	         	$this->content->icons[] = "";
    		}
        	
        
        } else {
			//TODO place percentage bar code into a class 
        	//the following code handles the creation of the percentage bars (this will be placed into a function)
        	
	        $percentagebars	=	array();
						
			//set the display attendance flag to false
			$displayattendance	= false;
			
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
        	
        	
        	//get all enabled reports in this ilp
			$reports		=	$dbc->get_reports(ILP_ENABLED);
			

			
			if (!empty($reports)) {
				//we are going to output the add any reports that have state fields to the percentagebar array 
				foreach ($reports as $r) {
					if ($dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {
	
						
						
						$reportinfo				=	new stdClass();
						$reportinfo->total		=	$dbc->count_report_entries($r->id,$USER->id);
						$reportinfo->actual		=	$dbc->count_report_entries_with_state($r->id,$USER->id,ILP_PASSFAIL_PASS);
					
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
			}	
			
	         $this->content->text	= "";
	         
	         foreach ($percentagebars as $p) {
	         	$this->content->items[]	=	"<br /><label style='font-size: 10px; font-size:normal;'>{$p->name}</label><div style='margin:	2px; border-style:	solid; border-color:	black; height: 10px; width : 100px;'  ><div class='ilppercentagebar' style='width: {$p->percentage}%' ></div></div>";
	         }
	         
        	$label = get_string('mypersonallearningplan', 'block_ilp');
	         $url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$USER->id}";
	         $this->content->items[] = "<a href='{$url}'>{$label}</a>";
	         $this->content->icons[] = "";
        	
        }
		
		
         
	//	if (!empty($access_viewotherilp)) {
        
        
      //  } else {

        //}
		
		
         
        return $this->content;
    }
    
    
    
    /**
     * Allow the user to set sitewide configuration options for the block.
     *
     * @return bool true
     */
    function has_config() {
        return true;
    }
    

    /**
     * Allow the user to set specific configuration options for the instance of
     * the block attached to a course.
     *
     * @return bool true
     */
    function instance_allow_config() {
    	
        return false;
    }
    
    
 
    
    /**
     * Only allow this block to be mounted to a course or the home page.
     *
     * @return array
     */
    function applicable_formats() {
        return array(
            'site-index'  => true,
            'course-view' => true,
        );
    }
    
    /**
     * Prevent the user from having more than one instance of the block on each
     * course.
     *
     * @return bool false
     */
    function instance_allow_multiple() {
        return false;
    }
    
    /*
     * Functions that we want to run directly after the block has been installed 
     *  
     */
	function after_install() {
		
		global $CFG;
		
		//call the install.php script (used for moodle 2) that has the operations that need to be carried 
		//out after installation 
		require_once($CFG->dirroot.'/blocks/ilp/db/install.php');
		
		//call the block_ilp_install function used by moodle 2.0
		xmldb_block_ilp_install();
		
	}
    
    
    
	function instance_config_save($data) {
	/* not needed now as we are not assigning reports to courses
		global $CFG;
		
		require_once($CFG->dirroot."/blocks/ilp/db/ilp_db.php");
	
		$dbc	=	new ilp_db();
		
		// include ilp lib file
        require_once($CFG->dirroot.'/blocks/ilp/lib.php');

		// remove the config_ prefixes
        foreach($data as $key => $value) {
            $key = preg_replace('/config_/', '', $key);
            $data->$key = $value;
        }
        
	  	$course_id		=	$data->course_id;
	  	$sesskey		=	$data->sesskey;
	  	$bui_editid		=	$data->bui_editid;
	  	
	  	$instanceid		=	$data->instanceid;
	  	$blockaction	=	$data->blockaction;

	  	
	  	
        if (!empty($data->addall) || !empty($data->addsel) || !empty($data->removeall) || !empty($data->removesel)) {
		  	
        	if (!empty($data->addall)) {

					//the add all button was pressed
			
					//since the add all button was pressed it reasonable to assume that all enabled reports 
					//will be added to this course so we get all enabled reports in the same manner the form 
					//would have and pass them to the add_coursereport function		
			
					//get all reports that are enabled in this course
			        $assignedreports	=	$dbc->get_coursereports($course_id,null,ILP_ENABLED);
			        
			        $coursereports		=	array();

			        var_dump($assignedreports);
			        
			        //populate the areport var with key and values from the objects returned in $assignedreports
			        $areport	=	array();
			        if (!empty($assignedreports)) {
				        foreach ($assignedreports as $r) {
				        	$areport[$r->id]	=	$r->name;	
				        }
			        }
			        
			        if (!empty($assignedreports))	{
			        	foreach ($assignedreports as $a) {
							$coursereports[]	=	$a->report_id;	
			        	}
			        }
			        
			        //get all ilp reports that enabled except the ones already enabled in this course 
			        $unassignedreports	=	$dbc->get_enabledreports($coursereports);		
			
					//loop through the options in the report field and add them to this course 	
					//the add_coursereport will create the record for the report in this course
					//if one doesn't exist if it does and it is disabled it will reenable it
					
					foreach ($unassignedreports	as $report) {
						add_coursereport($course_id,$report->id);
					}		
		    } else if (!empty($data->addsel)) {
		    	if (!empty($data->reports)) {
					//add the individual report selection
					//loop through the options in the report field and add them to this course 	
					//the add_coursereport will create the record for the report in this course
					//if one doesn't exist if it does and it is disabled it will reenable it
			
					foreach ($data->reports	as $report_id) {
						if ($report_id == -1) continue; 
						add_coursereport($course_id,$report_id);
					}	
		    	}
		    } else if (!empty($data->removeall)) {
		
					//since the remove all button it is resonable to assume that all reports assigned to the 
					//course are being removed so we will get all reports enabled in this course them we will 
					//pass the records to remove_coursereport which will disable the reports. 
					//NOTE these records are only disbaled as we dont want to orphan records 
			
					//get all reports that are enabled in this course
			        $assignedreports	=	$dbc->get_coursereports($course_id,null,ILP_ENABLED);
					
					foreach ($assignedreports	as $report) {
						remove_coursereport($course_id,$report->report_id);
					}
		    } else if (!empty($data->removesel)) {
		    	if (!empty($data->coursereports)) {	
					//remove the selected report
			
					//loop through the selected options and remove them from the course
					//in practice the record for the course is not being removed as this may 
					//orphan report entry records instead we will disable the report in the 
					//course to stop any new reports being created (the report will no longer show)
					//as a creatable report
			
					
					foreach ($data->coursereports	as $report_id) {
						if ($report_id == -1) continue; 
						remove_coursereport($course_id,$report_id);
					}	
		
		    	}
		    }
		    
		    $returnurl	=	$CFG->wwwroot."/course/view.php?id={$course_id}&sesskey={$sesskey}";
		    
		    //use the bui_editid which is not present in mooodle 1.9 to decide which version we are in and send the user back to the 
		    //the config page with the appropriate url
		    $returnurl .= (!empty($bui_editid)) ?  "&bui_editid={$bui_editid}" : "&blockaction={$blockaction}&instanceid={$instanceid}";	
		    //redirect the user back to the edit page
        	 redirect($returnurl, '', REDIRECT_DELAY);
        }
        
        // and now actually save it in the parent class
    	return parent::instance_config_save($data);
    	*/
	}
	
	
    
}
?>