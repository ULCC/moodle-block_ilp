<?php
/**
 * An abstract class that holds methods and attributes common to all mis plugin
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
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_plugin.class.php');


abstract class ilp_graph_plugin extends ilp_plugin  {


    /*
      * This var should hold the connection to the mis database
      */
    public $db;

    /*
      * This var should hold the tabletype used by the plugin in queries
      */
    public $tabletype;

    /*
      * This var should hold the data retrieved from the dbquery function
      */
    public $data;

    protected $params; //initialisation params set at invocation time

    protected $allowed_form_elements;


    /**
     * Constructor
     */
    function __construct()   {
        global $CFG;

        //set the directory where plugin files of type ilp_graph are stored
        $this->plugin_class_directory = $CFG->dirroot . "/blocks/ilp/plugins/graph";

        //set the table that the details of these plugins are stored in
        $this->plugintable = "block_ilp_graph_plugin";

        $this->allowed_form_elements       =   array();

        //call the parent constructor
        parent::__construct();

        //set the name of the template file should be a html file with the same name as the class
        $this->templatefile = $this->plugin_class_directory . '/' . $this->name . '.html';
    }


    /**
     * Edit the plugin instance
     *
     * @param object $plugin
     */
    public final function edit($report_id,$plugin_id,$reportgraph_id) {

        global $CFG, $PARSER,$USER;

        //get the report graph record
        $reportgraph		=	$this->dbc->get_report_graph_data($reportgraph_id);


        // include the moodle form library
        require_once($CFG->libdir.'/formslib.php');

        //include ilp_formslib
        require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_formslib.class.php');

        // get the name of the evidence class being edited
        $classname = get_class($this).'_mform';

        // include the moodle form for this table
        include_once("{$CFG->dirroot}/blocks/ilp/plugins/graph/{$classname}.php");

        if(!class_exists($classname)) {
            print_error('noeditilpform', 'block_ilp', '', get_class($this));
        }

        if (!empty($reportgraph->id)) {
            $this->specific_edit($reportgraph);
        }

        // instantiate the form and load the data
        $this->mform = new $classname($report_id,$plugin_id,$USER->id,$reportgraph_id,$reportgraph);

        $this->mform->set_data($reportgraph);

        //enter a back u
        $backurl = $CFG->wwwroot."/blocks/ilp/actions/edit_report_graphs.php?report_id={$report_id}";

        //was the form cancelled?
        if ($this->mform->is_cancelled()) {
            //send the user back
            redirect($backurl, get_string('returnreportgraph', 'block_ilp'), ILP_REDIRECT_DELAY);
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
                    print_error(get_string("graphcreationerror", 'block_ilp'), 'block_ilp');
                }


                if ($this->mform->is_submitted()) {
                    //return the user to the
                    $return_url = $CFG->wwwroot."/blocks/ilp/actions/edit_report_graphs.php?report_id={$report_id}";
                    redirect($return_url, get_string("graphcreationsuc", 'block_ilp'), ILP_REDIRECT_DELAY);
                }
            }
        }


    }


    /**
     * Installs any new plugins
     */
    public static function install_new_plugins($dbplugins=array(),$plugin_class_directory="")   {
        global $CFG;

        // include the ilp db
        require_once($CFG->dirroot . '/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the ilp db class needed as this function will be called 
        //when not in object context
        $dbc = new ilp_db();

        //call the install new plugins function from the parent class
        //pass the list of plugins currently installed to it
        parent::install_new_plugins($dbc->get_graph_plugins(), $CFG->dirroot . "/blocks/ilp/plugins/graph");

    }


    /**
     * This fucntion updates the install plugin record it sets the plugin type (overview or detail)
     */
    function install($plugin_id)    {
        $graphplugin = $this->dbc->get_graph_plugin_by_id($plugin_id);

        $graphplugin->type = static::plugin_type();

        $this->dbc->update_graph_plugin($graphplugin);
    }

    /**
     * Force extending class to implement a display function
     * The display function is called to display the graph
     *
     * @param $user_id  int the user_id
     */
    abstract function display($user_id,$report_id,$reportgraph_id,$size='large',$return=false);

    public function set_data($reportgraph)  {

    }

    function config_settings(&$settings)    {
        return $settings;
    }

    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name() {
        return 'Graph Plugin';
    }

    /**
     * This function is called by the ILPs cron it should be used to generate data needed
     * by a graph plugin it is intended to be overriden by child functions. Be careful when
     * overriding this function as a none well though out function could have implications
     * outside of the ILP.
     * @return bool
     */
    function cron_data()    {
        return false;
    }

    /**
     * returns a icon that can be used to represents a graph
     *
     * @return string
     */
    function icon() {
        global $CFG;

        return  "<img src='$CFG->wwwroot/blocks/ilp/pix/graphicon.jpg' height='24' width='24' />";
    }


    function audit_type()   {
        return '';
    }

    /**
     * Delete a form element
     */
    public function delete_graph( $reportgraph_id, $extraparams=array() ) {
      return $this->dbc->delete_record( 'block_ilp_report_graph', array('id'=>$reportgraph_id), $extraparams );
    }


    /**
     * This function adds plugin specific data to the reportgraph var (which is then displayed on the edit form). The
     * function can cope with plugins that store single or multiple data items in a table, however if your plugin does
     * something different then simply oveerride the function and add your data to the $reportgraph object
     *
     * @param   object  $reportgraph    the reportgraph object that plugin data will be added to.
     * @param   bool    $multipleitem   will multiple items be returned and stored in each attribute as an array,
     *                                  true if yes
     * @param   array   $non_attrib     array containing attributes that should not be added to reportgraph object if not
     *                                  set the following are not added to reportgraph object: id,reportgraph_id,
     *                                  timemodified, timecreated
     */
    public function specific_edit(&$reportgraph,$multipleitems = false,$non_attrib=NULL)  {
        $plugin	=	$this->dbc->get_graph_plugin_by_id($reportgraph->plugin_id);

        //get the graph chart data from the plugin table
        $rgrecords		=	$this->dbc->get_graph_by_report($plugin->tablename,$reportgraph->id);

        $non_attrib = (empty($non_attrib)) ? array('id','reportgraph_id', 'timemodified', 'timecreated') : $non_attrib;

        if (!empty($rgrecords)) {
            foreach($rgrecords as $rg)   {
                foreach ($rg as $attrib => $value) {
                  if (!in_array($attrib, $non_attrib)) {
                        if (!isset($reportgraph->$attrib) && !empty($multipleitems) )   $reportgraph->$attrib   =   array();
                        array_push($reportgraph->$attrib ,$value);
                    }
                }
            }
        }
    }


}
