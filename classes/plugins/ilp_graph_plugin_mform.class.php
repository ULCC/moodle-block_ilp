<?php

require_once($CFG->dirroot."/blocks/ilp/classes/ilp_formslib.class.php");

abstract class ilp_graph_plugin_mform extends ilp_moodleform {

    public		$report_id;
    public 		$plugin_id;
    public 		$creator_id;
    public 		$dbc;


    function __construct($report_id,$plugin_id,$creator_id,$reportgraph_id=null,$reportgraph=null) {
        global $CFG;

        $this->report_id		=	$report_id;
        $this->plugin_id		=	$plugin_id;
        $this->creator_id		=	$creator_id;
        $this->reportgraph_id	        =	$reportgraph_id;
        $this->dbc				=	new ilp_db();

        //defines which form element plugins can be used with this graph
        $this->form_elements();

        parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_graph.php?plugin_id={$plugin_id}&report_id={$report_id}");

    }

    function definition() {
        global $USER, $CFG;

        //get the plugin type by getting the plugin name
        $currentplugin	=	$this->dbc->get_graph_plugin_by_id($this->plugin_id);

        $mform =& $this->_form;
        $fieldsettitle	=	get_string("creategraph",'block_ilp');

        //define the elements that should be present on all plugin element forms

        //create a fieldset to hold the form
        $mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
        $mform->addElement('html', '<legend class="ftoggler">'.$fieldsettitle.'</legend>');

        //the id of the report that the element will be in
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('static', 'plugintypestatic',get_string('plugintype','block_ilp'),get_string($currentplugin->name.'_type','block_ilp'));

        //the id of the report that the element will be in
        $mform->addElement('hidden', 'report_id');
        $mform->setType('report_id', PARAM_INT);
        $mform->setDefault('report_id', $this->report_id);

        //the id of the plugin in use
        $mform->addElement('hidden', 'plugin_id');
        $mform->setType('plugin_id', PARAM_INT);
        $mform->setDefault('plugin_id', $this->plugin_id);

        //the id of the form element creator
        $mform->addElement('hidden', 'creator_id');
        $mform->setType('creator_id', PARAM_INT);
        $mform->setDefault('creator_id', $this->creator_id);

        //the id of the graph this is only used in edit instances
        $mform->addElement('hidden', 'reportgraph_id');
        $mform->setType('reportgraph_id', PARAM_INT);
        $mform->setDefault('reportgraph_id', $this->reportgraph_id);

        //text field for element label
        $mform->addElement(
            'text',
            'name',
            get_string('graphname', 'block_ilp'),
            array('class' => 'form_input')
        );

        $mform->addRule('name', null, 'maxlength', 255, 'client',array('size'=>'10'));
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setType('name', PARAM_RAW);


        //text field for element description
        $mform->addElement(
            'htmleditor',
            'description',
            get_string('description', 'block_ilp'),
            array('class' => 'form_input','rows'=> '10', 'cols'=>'65')
        );

        $mform->addRule('description', null, 'maxlength', 1000, 'client');
        $mform->setType('description', PARAM_RAW);

        $optionlist     =   array();

        $optionlist[ILP_GRAPH_ALLDATA]          =   get_string('alldata','block_ilp');
        $optionlist[ILP_GRAPH_ONEMONTHDATA]     =   get_string('onemonthdata','block_ilp');
        $optionlist[ILP_GRAPH_THREEMONTHDATA]   =   get_string('threemonthdata','block_ilp');
        $optionlist[ILP_GRAPH_SIXMONTHDATA]     =   get_string('sixmonthdata','block_ilp');
        $optionlist[ILP_GRAPH_YEARDATA]         =   get_string('yeardata','block_ilp');


        $mform->addElement(
            'select',
            'datacollected',
            get_string( 'datacollection' , 'block_ilp' ),
            $optionlist
        );

        $this->specific_definition($mform);


        //add the submit and cancel buttons
        $this->add_action_buttons(true, get_string('submit'));
    }

    /**
     * Force extending class to add its own form fields
     */
    abstract protected function specific_definition($mform);

    /**
     * Performs server-side validation of the unique constraints.
     *
     * @param object $data The data to be saved
     */
    function validation($data, $files) {
        $this->errors = array();

        //check that the field label does not already exist in this report
        if ($this->dbc->label_exists($data['name'],$data['report_id'],$data['id']))	{
            $this->errors['name']	=	get_string('graphexistserror','block_ilp',$data);
        }

        // now add fields specific to this type of evidence
        $this->specific_validation($data);

        return $this->errors;
    }

    /**
     * Force extending class to add its own server-side validation
     */
    abstract protected function specific_validation($data);

    /**
     * Saves the posted data to the database.
     *
     * @param object $data The data to be saved
     */
    function process_data($data) {

        $data->name	=	htmlentities($data->name);

        if (empty($data->id)) {
            //create the ilp_report_field record
            $data->id	=	$this->dbc->create_report_graph($data);
        } else {
            //update the report

            $reportgraph	=	$this->dbc->update_report_graph($data);
        }

        if(!empty($data->id)) {
            $data->reportgraph_id = $data->id;

            $this->specific_process_data($data);
        }
        return $data->id;
    }

    /**
     * Force extending class to add its own processing method
     */
    abstract protected function specific_process_data($data);


    /**
     * Force extending class to implement a form_elements function
     * The fields function specifies all form elements that can be used
     * by this graph. The form element class names should be passed into
     * the local $allowed_form_elements array.
     */
    abstract function form_elements();

    /**
     * Function used to check if the report field given is in the graph allowed fields list.
     * @param the id of the reportfield that will be checked to see if it can
     *        be used with the current plugin
     * @return bool
     */
    public function check_elements($reportfield_id)   {

        $plugin     =   $this->dbc->get_reportfield_plugin($reportfield_id);

        return (!empty($plugin)) ? in_array($plugin->name,$this->allowed_form_elements)  : false;
    }

    /**
     * checks if the fields in the given report are compatible with the current form
     *
     * @param null $report_id
     * @return array|bool
     */
    function check_fields($report_id=null) {

        $report_id  =   (empty($report_id)) ? $this->report_id  : $report_id;

        $reportfields   =   $this->dbc->get_report_fields_by_position($report_id);

        $optionlist =   array();

        foreach ($reportfields as $rf)  {
            //check if the report field element can be added to this type of graph
            if ($this->check_elements($rf->id))  {
                $optionlist[$rf->id]     =       $rf->label;
            }
        }

        return (!empty($optionlist))    ?   $optionlist :   false;
    }

}


?>
