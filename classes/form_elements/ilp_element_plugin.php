<?php
/**
 * An abstract class that holds methods and attributes common to all element form plugin
 * classes.
 *
 * @abstract
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */
//abstract class assmgr_resource {
class ilp_element_plugin {

    /**
     * The element data
     *
     * @var array
     */
    var $data;

    /**
     * The name of the plugin
     *
     * @var string
     */
    var $name;

    /**
     * The moodle form for editing the plugin data
     *
     * @var moodleform
     */
    var $mform;

    /**
     * The plugins id
     *
     * @var int
     */
	var $plugin_id;
	
	/**
     * The label used by the instance of the plugin
     *
     * @var string
     */
	var	$label;
	
	/**
     * The decription used by the instance of the plugin
     *
     * @var string
     */
	var	$description;
    var $xmldb_table;

    var $xmldb_field;

    var $xmldb_key;

    var $dbman;

    var $set_attributes;


    /**
     * Constructor
     */
    function __construct() {
        global $CFG,$DB;

        
        // include the assmgr db
        require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

        // instantiate the assmgr db
        $this->dbc = new ilp_db();
        
        $this->name = get_class($this);

        // include the xmldb classes
        require_once($CFG->libdir.'/ddllib.php');

        $this->dbman = $DB->get_manager();

        // if 2.0 classes are available then use them
        $this->xmldb_table = class_exists('xmldb_table') ? 'xmldb_table' : 'XMLDBTable';
        $this->xmldb_field = class_exists('xmldb_field') ? 'xmldb_field' : 'XMLDBField';
        $this->xmldb_key   = class_exists('xmldb_key')   ? 'xmldb_key'   : 'XMLDBKey';
    }

    /**
     *
     */
    public function get_name() {
        return $this->name;
    }

    /**
     *
     */
    public function get_tablename() {
        return $this->tablename;
    }
    
    

    /**
     * Edit the plugin instance
     *
     * @param object $plugin
     */
    public final function edit($course_id,$report_id,$plugin_id,$reportfield_id) {
        global $CFG, $PARSER,$USER;

        //get the report field record
        $reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
       
        
        // include the moodle form library
        require_once($CFG->libdir.'/formslib.php');
       
        //include ilp_formslib
        require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_formslib.class.php');

        // get the name of the evidence class being edited
        $classname = get_class($this).'_mform';

        // include the moodle form for this table
        include_once("{$CFG->dirroot}/blocks/ilp/classes/form_elements/plugins/{$classname}.php");

        if(!class_exists($classname)) {
            print_error('noeditilpform', 'block_ilp', '', get_class($this));
        }

        if (!empty($reportfield->id)) {
        
        	$plugin	=	$this->dbc->get_form_element_plugin($reportfield->plugin_id);
        	
        	//get the form element data from the plugin table
        	$form_element		=	$this->dbc->get_form_element_by_reportfield($plugin->tablename,$reportfield->id);

        	
            $non_attrib = array('id', 'timemodified', 'timecreated');

            if (!empty($form_element)) {
                foreach ($form_element as $attrib => $value) {
                    if (!in_array($attrib, $non_attrib)) {
                        $reportfield->$attrib = $value;
                    }
                }
            }
        }
        
        // instantiate the form and load the data
        
        $this->mform = new $classname($report_id,$plugin_id,$course_id,$USER->id);

        $this->mform->set_data($reportfield);

        
        //enter a back u
        $backurl = $CFG->dirroot."/blocks/ilp/actions/edit_prompt.php?course_id={$course_id}&report_id={$report_id}";
        
        
	    //was the form cancelled?
		if ($this->mform->is_cancelled()) {
			//send the user back
			redirect($backurl, get_string('changescancelled', 'block_ilp'), REDIRECT_DELAY);
		}


		//was the form submitted?
		// has the form been submitted?
		if($this->mform->is_submitted()) {
		    // check the validation rules
		    if($this->mform->is_validated()) {
		
		        //get the form data submitted
		    	$formdata = $this->mform->get_data();
		    	    	
		        // process the data
		    	$success = $this->mform->process_data($formdata);
		
		    	//if saving the data was not successful
		        if(!$success) {
					//print an error message	
		            print_error(get_string("fieldcreationerror", 'block_ilp'), 'block_ilp');
		        }
		
		                
		         if ($this->mform->is_submitted()) { 
		            //return the user to the 
		        	$return_url = $CFG->wwwroot."/blocks/ilp/actions/edit_prompt.php?course_id={$course_id}&report_id={$report_id}";
		        	redirect($return_url, get_string("fieldcreationsuc", 'block_ilp'), REDIRECT_DELAY);
		        }
		    }
		}

    }

    /**
     * Delete the form entry
     */
    public final function delete($reportfield_id) {
    	
    	
    	
        return false;
    }

    
	/**
     * Delete a form element
     */
    public function delete_form_element($tablename,$reportfield_id) {
        $reportfield	=	$this->dbc->get_plugin_record($tablename,$reportfield_id);
        
        if ($this->dbc->delete_form_element_by_reportfield($tablename,$reportfield_id)) {
    	   	//TODO: should we delete all entry records linked to this field?
        	//now delete the reportfield
        	return $this->dbc->delete_report_field($reportfield_id);
        } 
        return false;
    }

    /**
     * Installs any new plugins
     */
    public static function install_new_plugins() {
        global $CFG;

        // instantiate the assmgr db
        $dbc = new ilp_db();

        // get all the currently installed evidence resource types
        $plugins = ilp_records_to_menu($dbc->get_form_element_plugins(), 'id', 'name');
        
        
        $plugins_directory = $CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins';

        
        
        // get the folder contents of the resource plugin directory
        $files = scandir($plugins_directory);

        foreach($files as $file) {
            // look for plugins
            if(preg_match('/^([a-z_]+)\.php$/i', $file, $matches)) {

                if(!in_array($matches[1], $plugins) && substr($matches[1], -5)  != 'mform') {
                    // include the class

                	require_once($plugins_directory.'/'.$file);

                    // instantiate the object
                    $class = basename($file, ".php");
                    
                    $formelementobj = new $class();

                    // install the plugin
                    $formelementobj->install();

                    // update the resource_types table
                    $dbc->create_form_element_plugin($formelementobj->get_name(),$formelementobj->get_tablename());
                }
            }
        }

    }


    function get_resource_enabled_instances($resource_name,$course=null) {

        $enabled_courses = array();

        if (!empty($course)) {
             $course_instances = (is_array($course)) ? $course : array($course);
        } else {
            $course_instances = array();
            //get all courses that the block is attached to
            $block_course =  $this->dbc->get_block_course_ids($course);

            if (!empty($block_course)) {
                foreach ($block_course as $block_c) {
                    array_push($course_instances,$block_c->pageid);
                }
            }
        }

        if (!empty($course_instances)) {
            foreach ($course_instances as $course_id) {
                $instance_config  = (array) $this->dbc->get_instance_config($course_id);
                if (isset($instance_config[$resource_name])) {
                    if (!empty($instance_config[$resource_name])) {
                         array_push($enabled_courses,$course_id);
                    }
                }
            }
        }

        return $enabled_courses;
    }


    /**
     * function used to return configuration settings for a plugin
     */
    function config_settings(&$settings) {
        return $settings;
    }

    /**
     * function used to return the language strings for the resource
     */
    function language_strings(&$string) {
        return $string;
    }

    /**
     * function used to update records in the resource
     */
    function update() {

    }


    /**
     * function used to specify whether the current resource requires file storage
     */
    public function file_storage() {
        return false;
    }
}
?>
