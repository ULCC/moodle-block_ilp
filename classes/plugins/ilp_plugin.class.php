<?php
/**
 * An abstract class that holds methods and attributes common to all element form plugin
 * classes.
 *
 * @abstract
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
class ilp_plugin {

	/**
	* table to store the properties of the element
	*/
	public $tablename;

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
     * The  directory in which the plugin classes reside
     *
     * @var string
     */
     var	$plugin_class_directory;
     
     /**
     * The name of the table that the plugins details will be saved to
     *
     * @var string
     */
     var	$plugintable;
     
    
    /**
     * The plugins id
     *
     * @var int
     */
	var $plugin_id;

    /**
     * @var ilp_db|ilp_db_functions
     */
    var	$dbc;

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
     * @var database_manager
     */
    var $dbman;

    /**
     * @var
     */
    var $set_attributes;

    /**
     * Constructor
     */
    function __construct() {
        global $CFG,$DB;

        
        // include the ilp db
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the ilp db
        $this->dbc = new ilp_db();
        
        $this->name = get_class($this);

        // include the xmldb classes
        require_once($CFG->libdir.'/ddllib.php');

        $this->dbman = $DB->get_manager();

        $this->xmldb_table = class_exists('xmldb_table') ? 'xmldb_table' : 'XMLDBTable';
        $this->xmldb_field = class_exists('xmldb_field') ? 'xmldb_field' : 'XMLDBField';
        $this->xmldb_key   = class_exists('xmldb_key')   ? 'xmldb_key'   : 'XMLDBKey';

    }

	/**
     * Returns the name of the plugin
     */
    public function get_name() {
        return $this->name;
    }

	/**
     * Returns the directory in which the files for this plugin reside
     */
    public function get_directory() {
        return $this->directory;
    }
    
	/**
     * Returns the name of the plugin
     */
    public function get_plugin_class_directory() {
        return $this->plugin_class_directory;
    }
    
	/**
     * Returns the name of the plugin
     */
    public function get_plugin_table() {
        return $this->plugintable;
    }
    
	/**
     * Delete the plugin
     */
    public final function delete($plugin_id) {
        return false; 
    }
    
    
    /**
     * Install function can be used to install any additional tables or files, records etc
     */
    public function install($id)	{
    	return false;
    }

    /**
     * Installs any new plugins
     */
    public static function install_new_plugins($dbplugins,$plugin_class_directory) {
        global $CFG;

        // instantiate the assmgr db
        $dbc = new ilp_db();

        // get all the currently installed evidence resource types
        $plugins = ilp_records_to_menu($dbplugins, 'id', 'name');
        
        // get the folder contents of the resource plugin directory
        $files = scandir($plugin_class_directory);

        foreach($files as $file) {
            // look for plugins

	            if(preg_match('/^([a-z_]+)\.php$/i', $file, $matches)) {

                    if(!in_array($matches[1], $plugins) && substr($matches[1], -5)  != 'mform') {
                        if(!in_array($matches[1], $plugins)) {
                            // include the class

                            require_once($plugin_class_directory.'/'.$file);

                            // instantiate the object
                            $class = basename($file, ".php");

                            $pluginobj = new $class();

                            // update the resource_types table
                            $id	=	$dbc->create_plugin($pluginobj->get_plugin_table(),$pluginobj->get_name(),$pluginobj->get_tablename());

                            // any additional functions that must be carried that are specific to a child class can be carried out in the install function
                            $pluginobj->install($id);
                        }
                    }
	            }
        	
        }

    }


     /**
     * function used to return configuration settings for a plugin
     */
    function config_settings(&$settings) {
        return $settings;
    }

    /**
     * function used to return the language strings for the plugin
     */
    static function language_strings(&$string) {
        return $string;
    }

     /**
     * Creates a text element with a description on the config page for the plugin
     * 
     * @param ilp_moodleform $mform the form that the text element will be added to
     * @param string $elementname the name of the element this will be saved to the 
     * 							  block_config table with the value
     * @param string $label the label to be put on the text element
     * @param strnig $description a description of what should be in the config element
     * @param mixed $defaultvalue the default contents of the text element 
     */
	 function config_text_element(&$mform,$elementname,$label,$description,$defaultvalue='') {

	 	//check if the value is already in the config table
	 	$configsetting	=	get_config('block_ilp',$elementname);
	 	
	 	if (empty($configsetting)) {
	 		//we need to check if the value is empty because the user set it that way so 
	 		//we will perform a query to see if the setting exists if it does then we will go 
	 		//with the config setting, if not set $value to default
	 		$settingexists	= $this->dbc->setting_exists($elementname);
	 		$value	=	(!empty($settingexists)) ? $configsetting : $defaultvalue;
	 	}	else	{
	 		$value	=	$configsetting;
	 	}
	 	
	 	$mform->addElement('text',"s_{$elementname}",$label,array('class' => 'form_input'),$value);
 	 	$mform->addElement('static', "{$elementname}_desc", NULL, $description);
 	 	$mform->setDefault("s_{$elementname}",$value);
 	 }

    /**
     * @param $mform
     * @param $elementname
     * @param $label
     * @param $description
     * @param string $defaultvalue
     */
    function config_htmleditor_element(&$mform,$elementname,$label,$description,$defaultvalue='') {

        //check if the value is already in the config table
        $configsetting	=	get_config('block_ilp',$elementname);

        if (empty($configsetting)) {
            //we need to check if the value is empty because the user set it that way so
            //we will perform a query to see if the setting exists if it does then we will go
            //with the config setting, if not set $value to default
            $settingexists	= $this->dbc->setting_exists($elementname);
            $value	=	(!empty($settingexists)) ? $configsetting : $defaultvalue;
        }	else	{
            $value	=	$configsetting;
        }

        $mform->addElement('editor',"s_{$elementname}",$label,array('class' => 'form_input'),$value);
        $mform->addElement('static', "{$elementname}_desc", NULL, $description);
        $mform->setDefault("s_{$elementname}",array('text'=>$value));
    }
	 
 	 
 	  /**
     * Creates a select element with a description on the config page for the plugin
     * 
     * @param ilp_moodleform $mform the form that the select element will be added to
     * @param string $elementname the name of the element this will be saved to the s
     * 							  block_config table with the value
     * @param string $label the label to be put on the select element
     * @param array $options options to be placed in the select
     * @param strnig $description a description of what should be in the config element
     * @param mixed $defaultvalue the default contents of the text element 
     */
	 function config_select_element(&$mform,$elementname,$options,$label,$description,$defaultvalue='') {
	 	
	 	$configsetting	=	get_config('block_ilp',$elementname);
	 	
	 	if (empty($configsetting)) {
	 		//we need to check if the value is empty because the user set it that way so 
	 		//we will perform a query to see if the setting exists if it does then we will go 
	 		//with the config setting, if not set $value to default
	 		$settingexists	= $this->dbc->setting_exists($elementname);
	 		$value	=	(!empty($settingexists)) ? $configsetting : $defaultvalue;
	 	}	else	{
	 		$value	=	$configsetting;
	 	}
	 	
	 	$mform->addElement('select',"s_{$elementname}",$label,$options,array('class' => 'form_input'));
 	 	$mform->addElement('static', "{$elementname}_desc", NULL, $description);
 	 	$mform->setDefault("s_{$elementname}",$value);
	 }
	 
 	  /**
     * Creates a select element with a description on the config page for the plugin
     * 
     * @param ilp_moodleform $mform the form that the select element will be added to
     * @param string $elementname the name of the element this will be saved to the s
     * 							  block_config table with the value
     * @param string $label the label to be put on the select element
     * @param array $options options to be placed in the select
     * @param strnig $description a description of what should be in the config element
     * @param mixed $defaultvalue the default contents of the text element 
     */
	 function config_date_element(&$mform,$elementname,$label,$description,$defaultvalue='') {
	 	
	 	$configsetting	=	get_config('block_ilp',$elementname);
	 	
	 	$value	= (!empty($configsetting)) ? $configsetting : $defaultvalue;
	 	
	 	$mform->addElement('date_selector',"s_{$elementname}",$label,array('class' => 'form_input'));
 	 	$mform->addElement('static', "{$elementname}_desc", NULL, $description);
 	 	$mform->setDefault("s_{$elementname}",$value);
	 }

    /**
     * @param $mform
     */
    function config_form(&$mform)	{
	 	
	 }

    /**
     * @param $data
     * @return bool
     */
    function config_save($data)	{
	 	global $CFG;

	 	foreach ($data as $name => $value)	{
            if (is_array($value) && isset($value['text'])) {
                // HTML editor returns an array with text and format.
                $value = $value['text'];
            }
	 		if ($name != 'saveanddisplaybutton') {
	 			//removes the s_ from the front of the element name
				$name	=	substr_replace($name,'',0,2);	 		
			
				//use moodles set_config function to save the configuration setting
				set_config($name,$value,'block_ilp');
	 		}
	 	}
	 	
	 	return true;
	 }

    function get_tablename()    {
        return $this->tablename;
    }

}
?>
