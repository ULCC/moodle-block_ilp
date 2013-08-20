<?php
/**
 * An abstract class that holds methods and attributes common to all element dashboard tab
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



//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_plugin.class.php');

abstract class ilp_dashboard_template extends ilp_plugin {

	public 		$templatefile;
	public 		$regions;

    /**
     * Constructor
     */
    function __construct() {
    	global	$CFG;

		//set the directory where plugin files of type ilp_dashboard_tab are stored
    	$this->plugin_class_directory	=	$CFG->dirroot."/blocks/ilp/plugins/dash_templates";

    	//set the table that the details of these plugins are stored in
    	$this->plugintable	=	"block_ilp_dash_temp";



    	//initialise the regions array
    	$this->regions	=	array();

    	//call the parent constructor
    	parent::__construct();

    	//set the name of the template file should be a html file with the same name as the class
    	$this->templatefile		=	$this->plugin_class_directory.'/'.$this->name.'.html';
    }

    /**
     * Installs any new plugins
     */
    public static function install_new_plugins($dbplugins=array(),$plugin_class_directory="") {
    	global $CFG;

        // include the ilp db
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the ilp db class needed as this function will be called
        //when not in object context
        $dbc = new ilp_db();

    	//call the install new plugins function from the parent class
    	//pass the list of plugins currently installed to it
        parent::install_new_plugins($dbc->get_dashboard_templates(),$CFG->dirroot."/blocks/ilp/plugins/dash_templates");

    }

    /**
     * Install function can be used to install any additional tables or files, records etc
     */
    public function install($template_id)	{

    	//install the regions
    	foreach($this->get_regions() as $r_name) {
    		$r					=	new	stdClass();
    		$r->name			=	$r_name;
    		$r->template_id		=	$template_id;

    		//create the region record in the template_region table
    		$this->dbc->create_region($r);
    	}
    }


    /**
     * returns template contents
     */
    public final function get_template()	{
    	//if the template has not been specified in the code (in template function ) then call
    	//the get_template_from _file function to retrieve the classes template file contents
    	return (!$this->template())	? $this->get_template_from_file() : $this->template();
    }

    /**
     * Override this function if you do not want to create a template file and are only specifying
     * a simply template with simply html. The template html should be returned with all regions
     * specified in the same manner as you would in an external file
     */
    public	function template()	{
    	return false;
    }

    /**
     * This function returns the contents of the classes template file if found
     */
 	public	function get_template_from_file()	{

 		//make a connection to the template file
		$handle		=	 fopen($this->templatefile, "r");

		$templatecontents	= false;

		if (!empty($handle)) {
			//read in the contents of the file
			$templatecontents	=	fread($handle,filesize($this->templatefile));

			//close the connection to the file
			fclose($handle);
		}

		return $templatecontents;
 	}

    /**
     * This fucntion echo or returns the template file with all plugins in the specified regions
     */
    public function display_template($student_id=NULL,$course_id=NULL,$return=false)	{
    	global	$CFG;

		$templatecontents	=	$this->get_template();

    	if (!empty($templatecontents))	{

	    	//get all of the dashboard_plugins that are enabled for this template
    		$plugins	=	$this->dbc->get_template_plugins(get_class($this));

    		if (!empty($plugins))	{
		    	//loop through recordset
	    		foreach($plugins as $p) {

	    			$classname	=	$p->plugin_name;

	    			//include the dashboard_plugin class file
	    	        include_once("{$CFG->dirroot}/blocks/ilp/plugins/dashboard/{$classname}.php");

			        if(!class_exists($classname)) {
			            print_error('pluginclassnotfound', 'block_ilp', '', $classname);
			        }

	    			//instantiate dashboard_plugin class
	    			$dashplugin		=	new $classname($student_id,$course_id);

	    			//replace the region in the template file with the plugin code
	    			$templatecontents	=	$this->region_plugin($templatecontents,$p->region_name,$dashplugin->display());

	    			//end loop
	    		}
    		} else {
				$templatecontents	=	get_string('notemplateplugins','block_ilp');
    		}

    		if (empty($return)) {
				//echo out the template file
	    		echo $templatecontents;
    		} else	{
    			return $templatecontents;
    		}

    	} else {
    		print_error('templatenotfound','block_ilp');
    	}

    }


    /**
     *
     */
    public final function region_plugin($content,$region,$plugin)	{
    	return 	str_replace($region,$plugin,$content);
    }


    public function get_regions()	{
    	return $this->regions;
    }

}
?>
