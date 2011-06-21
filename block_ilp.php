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

        $coursecontext = get_context_instance(CONTEXT_COURSE, $course_id);

        //check user capabilities 
        /*
        // are we a student on the course?
        $access_iscandidate = has_capability('block/assmgr:creddelevidenceforself', $coursecontext, $USER->id, false);
		*/

        // cache the content of the block
        if($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';

		 $label = get_string('mypersonallearningplan', 'block_ilp');
         $url  = "{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$USER->id}";
         $this->content->items[] = "<a href='{$url}'>{$label}</a>";
         $this->content->icons[] = "";
         
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