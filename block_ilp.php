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

        // include assessment manager db class
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

        //check if the user has the relevant permission before allowing them to see the create report link
        //if () {
		 $label = get_string('reportconfiguration', 'block_ilp');
         $url  = "{$CFG->wwwroot}/blocks/ilp/actions/edit_report_configuration.php?course_id={$course_id}";
         $this->content->items[] = "<a href='{$url}'>{$label}</a>";
         $this->content->icons[] = "";
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
    
}
?>