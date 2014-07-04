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
 * @package ILP
 * @version 2.0
 */
//abstract class assmgr_resource {
class ilp_element_plugin {

	/**
	* table to store the properties of the element
	*/
	public $tablename;

	/**
	* table to store user data submitted from an element of this type
	* (dd types will also have an intermediate table listing their options
	* user input will be stored as a key to the items table)
	*/
	public $data_entry_tablename;

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
    /**
     * @var string
     */
    var $xmldb_table;

    /**
     * @var string
     */
    var $xmldb_field;

    /**
     * @var string
     */
    var $xmldb_key;

    /**
     * @var string
     */
    var $xmldb_index;

    /**
     * @var database_manager
     */
    var $dbman;

    /**
     * @var
     */
    var $set_attributes;

    /**
     * @var
     */
    var $req;

    /**
     * @var
     */
    var $course_id;
	/*
	* local file for pre-populating particular types
	* filename is classname . '_pre_items.config'
	* eg ilp_element_plugin_category_pre_items.conf
	* in the local plugins directory
	*/
    /**
     * @var
     */
    public $local_config_file;

    /**
     * Constructor
     */
    function __construct() {
        global $CFG,$DB;

        
        // include the assmgr db
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the assmgr db
        $this->dbc = new ilp_db();
        
        $this->name = get_class($this);

        // include the xmldb classes
        require_once($CFG->libdir.'/ddllib.php');

        $this->dbman = $DB->get_manager();

        $this->xmldb_table = class_exists('xmldb_table') ? 'xmldb_table' : 'XMLDBTable';
        $this->xmldb_field = class_exists('xmldb_field') ? 'xmldb_field' : 'XMLDBField';
        $this->xmldb_key   = class_exists('xmldb_key')   ? 'xmldb_key'   : 'XMLDBKey';
        $this->xmldb_index = class_exists('xmldb_index') ? 'xmldb_index' : 'XMLDBIndex';

		
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
     * @param $course_id
     */
    public function set_course_id($course_id) {
        $this->course_id = $course_id;
    }

    /**
     * Edit the plugin instance
     *
     * @param $report_id
     * @param $plugin_id
     * @param $reportfield_id
     * @internal param object $plugin
     */
    public final function edit($report_id,$plugin_id,$reportfield_id) {
        global $CFG, $USER;

        //get the report field record
        $reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
       
        
        // include the moodle form library
        require_once($CFG->libdir.'/formslib.php');
       
        //include ilp_formslib
        require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_formslib.class.php');

        // get the name of the evidence class being edited
        $classname = get_class($this).'_mform';

        // include the moodle form for this table
        include_once("{$CFG->dirroot}/blocks/ilp/classes/forms/element_plugins/{$classname}.php");

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
	        $this->return_data( $reportfield );
        }   else    {
            //new element - check for config file
            if(file_exists($this->local_config_file)) {
                $parsed_ini_file = parse_ini_file( $this->local_config_file );
                $flattened = self::itemlist_flatten( $parsed_ini_file );
                if(!is_object($reportfield)) $reportfield = new stdClass();
                $reportfield->optionlist = $flattened;
            }
	}

    if (!$this->is_editable() && !empty($reportfield->id))   {
        $return_url = $CFG->wwwroot."/blocks/ilp/actions/edit_prompt.php?report_id={$report_id}";
        redirect($return_url, get_string("fieldnoteditable", 'block_ilp'));
    }

    // instantiate the form and load the data
    $this->mform = new $classname($report_id,$plugin_id,$USER->id);

        if ($this->is_configurable())   {

            $this->mform->set_data($reportfield);


            //enter a back u
            $backurl = $CFG->wwwroot."/blocks/ilp/actions/edit_prompt.php?report_id={$report_id}";


            //was the form cancelled?
            if ($this->mform->is_cancelled()) {

                //send the user back
                redirect($backurl, get_string('returnreportprompt', 'block_ilp'), ILP_REDIRECT_DELAY);
            }


            //was the form submitted?
            // has the form been submitted?
            if($this->mform->is_submitted()) {
                // check the validation rules
                if($this->mform->is_validated()) {

                    //get the form data submitted
                    $formdata = $this->mform->get_data();
                    $formdata->audit_type = $this->audit_type();

                    // process the data
                    $success = $this->mform->process_data($formdata);

                    //if saving the data was not successful
                    if(!$success) {
                        //print an error message
                        print_error(get_string("fieldcreationerror", 'block_ilp'), 'block_ilp');
                    }


                     if ($this->mform->is_submitted()) {
                        //return the user to the
                        $return_url = $CFG->wwwroot."/blocks/ilp/actions/edit_prompt.php?report_id={$report_id}";
                        redirect($return_url, get_string("fieldcreationsuc", 'block_ilp'), ILP_REDIRECT_DELAY);
                    }
                }
            }
        }   else {

            //this is a plugin type that is not configurable
            $data       =   new stdClass();
            $data->plugin_id    =   $plugin_id;

            $this->mform->unprocessed_data($data);

            $success    =   $this->mform->process_data($data);

            if(!$success) {
                //print an error message
                print_error(get_string("fieldcreationerror", 'block_ilp'), 'block_ilp');

            } else {
                //return the user to the
                $return_url = $CFG->wwwroot."/blocks/ilp/actions/edit_prompt.php?report_id={$report_id}";
                redirect($return_url, get_string("fieldcreationsuc", 'block_ilp'), ILP_REDIRECT_DELAY);
            }


        }

    }

     /**
     * only necessary in listitem types
     * just here for completeness
     */
     public function return_data( &$reportfield ){
     	
     }
        
    /**
    * take an associative array returned from parsing an ini file
    * and return a string formatted for displaying in a text area on a management form
    */
    public static function itemlist_flatten( $configarray, $linesep="\n", $keysep=":" ){
		$outlist = array();
		foreach( $configarray as $key=>$value ){
			$outlist[] = "$key$keysep$value";
		}
		return implode( $linesep , $outlist );
    }

    /**
     * Delete the form entry
     */
    public final function delete($reportfield_id) {
        return false;
    }


    /**
     * Remove a form element from the a report - note this does not remove the data attached  to the element from the
     * database table
     *
     * @param int $reportfield_id the id of the report field
     * @param string $tablename the name of the table attached to the form element
     * @param array $extraparams extra params for the log table
     * @return bool
     */
    public function delete_form_element(  $reportfield_id,$tablename, $extraparams=array() ) {
        $reportfield	=	$this->dbc->get_plugin_record($tablename,$reportfield_id); 

        if ($this->dbc->delete_form_element_by_reportfield($tablename,$reportfield_id, $extraparams )) {
    	   	//TODO: should we delete all entry records linked to this field?
		    //yes we should, and it has been implemented in ilp_element_plugin_itemlist::delete_form_element
        	//now delete the reportfield
        	return $this->dbc->delete_report_field( $reportfield_id, $extraparams );
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
        
        
        $plugins_directory = $CFG->dirroot.'/blocks/ilp/plugins/form_elements';

        
        
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
                    $plugin_id  =   $dbc->create_form_element_plugin($formelementobj->get_name(),$formelementobj->get_tablename());

                    $formelementobj->after_install($plugin_id);
                }
            }
          }
    }

    /** Function used to specify what needs to be done after the new plugin is created
     * @param int $plugin_id  - the id of new plugin
     * @return true
     */
    public function after_install($plugin_id){
        return true;
    }

    /**
     * @param $resource_name
     * @param null $course
     * @return array
     */
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
    static function language_strings(&$string) {
        return $string;
    }

    /**
     * function used to update records in the resource
     */
    function update() {
        return true;
    }




    /**
    * make descendents of this function return false on occasions when
    * the element should not be added to a form
    * eg adding a category selector when there is already a
    * category selector in the same form
    */
    public function can_add( $report_id ){
        return true;
    }

	/**
    * This function saves the data entered on a entry form to the plugins _entry table
	* the function expects the data object to contain the id of the entry (it should have been
	* created before this function is called) in a param called id. 
    */
	 public	function entry_process_data($reportfield_id,$entry_id,$data) {
	 	
	 	//check to see if a entry record already exists for the reportfield in this plugin

		//create the fieldname
		$fieldname =	$reportfield_id."_field";

	 	//get the plugin table record that has the reportfield_id 
	 	$pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
	 	if (empty($pluginrecord)) {
	 		print_error('pluginrecordnotfound');
	 	}
	 
	 	//get the _entry table record that has the pluginrecord id
	 	$pluginentry 	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id);

	 	
	 	//if no record has been created create the entry record
	 	if (empty($pluginentry)) {
	 		$pluginentry	=	new stdClass();
            $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
			$pluginentry->entry_id = $entry_id;
	 		$pluginentry->value	=	$data->$fieldname;
	 		$pluginentry->parent_id	=	$pluginrecord->id;
	 		$result	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
	 	} else {
	 		//update the current record
            $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
	 		$pluginentry->value	=	$data->$fieldname;
	 		$result	= $this->dbc->update_plugin_entry($this->data_entry_tablename,$pluginentry);
	 	}

	 	return (!empty($result)) ? true: false;
	 }

	 /**
	  * Places entry data for the report field given into the entryobj given by the user
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
	  */
	 public function entry_data($reportfield_id, $entry_id, &$entryobj) {
	 	// This function will suffice for 90% of plugins who only have one value field (named value) i
	 	// in the _ent table of the plugin. However if your plugin has more fields you should override
	 	// the function
	 	
		//default entry_data 	
	 	$fieldname	=	$reportfield_id."_field";
	 	
	 	$entry	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id);
	 	if (!empty($entry)) {
	 		$entryobj->$fieldname	=	html_entity_decode($entry->value, ENT_QUOTES, 'UTF-8');
	 	}
	 }
	 
	  /**
	  * places entry data formatted for viewing for the report field given  into the
	  * entryobj given by the user. By default the entry_data function is called to provide
	  * the data. Any child class which needs to have its data formatted should override this
	  * function. 
	  * 
	  * @param int $reportfield_id the id of the reportfield that the entry is attached to 
	  * @param int $entry_id the id of the entry
	  * @param object $entryobj an object that will add parameters to
      * @param bool $returnvalue should a label or value be returned
	  */
	  public function view_data($reportfield_id, $entry_id, &$entryobj, $returnvalue = false ){
		$this->entry_data( $reportfield_id,$entry_id, $entryobj );
	 }
	 
	 /**
	  * Function that determiones whether the class in question should have its data process in most cases 
	  * this should be set to true (so the class willnot have to implement) however if the plugin class
	  * does not process data (e.g free_html class) then the function should be implemented and should return
	  * false 
	  * 
	  */
	public function is_processable()	{
    	return true;
    }
    
	 /**
	  * Function that determines whether the class in question should have its data displayed in any view page
	  * this should be set to true (so the class will not have to implement) however if the plugin class
	  * does not process data (e.g free_html class) then the function should be implemented and should return
	  * false  
	  */
	public function is_viewable()	{
    	return true;
    }



    /** Function used to delete an entry record
     * @param int $entry_id the if of a record entry
     */
    public function delete_entry_record($entry_id){

	    $this->dbc->delete_record ($this->data_entry_tablename, array('entry_id'=>$entry_id));
    }


    /** Function used to 'delete' a report form, in fact set it to invisible
     * @param int $report_id the id of the report
     * @return bool true
     */
    public function delete_report($report_id){
        return true;
        }



    /**
     * Function that determines whether the class in question is configurable this should be set to true
     * (so the class willnot have to implement) however if the form element class is not configurable (e.g page_break class)
     * then the function should be implemented and should return false
     */
    public function is_configurable()	{
        return true;
    }

    /**
     * Function that determines whether the class in question is editable this should be set to true
     * (so the class will not have to implement) however if the form element class is not editable (e.g page_break class)
     * then the function should be implemented and should return false
     */
    public function is_editable()   {
        return true;
    }

    /**
     * @return bool
     */
    public function is_exportable()
    {
       return true;
    }

    /**
     * @param $fieldid
     * @param $report_entry_id
     * @param $item
     */
    public function export_data($fieldid,$report_entry_id,&$item)
    {
       return $this->view_data($fieldid,$report_entry_id,$item);
    }

}
?>
