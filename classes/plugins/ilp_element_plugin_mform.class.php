<?php

/**
 * Class ilp_element_plugin_mform
 */
abstract class ilp_element_plugin_mform extends ilp_moodleform {

    /**
     * @var
     */
    public		$report_id;
    /**
     * @var
     */
    public 		$plugin_id;
    /**
     * @var
     */
    public 		$creator_id;
    /**
     * @var
     */
    public 		$course_id;
    /**
     * @var ilp_db
     */
    public 		$dbc;

    /**
     * @param $report_id
     * @param $plugin_id
     * @param $creator_id
     * @param null $reportfield_id
     */
    function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {
		global $CFG;
		
		$this->report_id		=	$report_id;
		$this->plugin_id		=	$plugin_id;
		$this->creator_id		=	$creator_id;
		$this->reportfield_id	=	$reportfield_id;
		$this->dbc				=	new ilp_db();
		
		parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_field.php?plugin_id={$plugin_id}&report_id={$report_id}");

	}
	
	function definition() {

        //get the plugin type by getting the plugin name
        $currentplugin	=	$this->dbc->get_form_element_plugin($this->plugin_id);
        
        $mform =& $this->_form;
        $fieldsettitle	=	get_string("addfield",'block_ilp');
        
        //define the elements that should be present on all plugin element forms

		//create a fieldset to hold the form        
        $mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
        $mform->addElement('html', '<legend class="ftoggler">'.$fieldsettitle.'</legend>');       	
        
        //the id of the report that the element will be in
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
         $mform->addElement('static', 'plugintypestatic',get_string('plugintype','block_ilp'),get_string($currentplugin->name.'_type','block_ilp'));

        
        //button to state whether the element is required
        $mform->addElement('checkbox', 
        				   'req', 
        					get_string('req', 'block_ilp')
        );
        
        
        //the id of the report that the element will be in
        $mform->addElement('hidden', 'report_id');
        $mform->setType('report_id', PARAM_INT);
        $mform->setDefault('report_id', $this->report_id);
        
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
        
        //the id of the course that the element is being created in
        $mform->addElement('hidden', 'course_id');
        $mform->setType('course_id', PARAM_INT);
        $mform->setDefault('course_id', $this->course_id);
        
        
        //the id of the reportfield this is only used in edit instances
        $mform->addElement('hidden', 'reportfield_id');
        $mform->setType('reportfield_id', PARAM_INT);
        $mform->setDefault('reportfield_id', $this->reportfield_id);
        
        //the id of the form element creator
        $mform->addElement('hidden', 'position');
        $mform->setType('position', PARAM_INT);
        //set the field position of the field
        $mform->setDefault('position', $this->dbc->get_new_report_field_position($this->report_id));

       
        
       	//text field for element label
        $mform->addElement(
            'text',
            'label',
            get_string('label', 'block_ilp'),
            array('class' => 'form_input')
        );
        
        $mform->addRule('label', null, 'maxlength', 255, 'client',array('size'=>'10'));
        $mform->addRule('label', null, 'required', null, 'client');
        $mform->setType('label', PARAM_RAW);
        
        
       	//text field for element description
        $mform->addElement(
            'htmleditor',
            'description',
            get_string('description', 'block_ilp'),
            array('class' => 'form_input','rows'=> '10', 'cols'=>'65')
        );
        
        $mform->addRule('description', null, 'maxlength', 10000, 'client');
        $mform->setType('description', PARAM_RAW);

        //button to state whether the element is required
        $mform->addElement('checkbox',
            'summary',
            get_string('addtosummary', 'block_ilp')
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
     * @param array $files
     * @return array
     */
    function validation($data, $files) {
        $this->errors = array();
        
        //check that the field label does not already exist in this report
        if ($this->dbc->label_exists($data['label'],$data['report_id'],$data['id']))	{
        	$this->errors['label']	=	get_string('labelexistserror','block_ilp',$data);
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
    	
    	$data->label	=	htmlentities($data->label);

        $data->summary  =   (isset($data->summary)) ? 1 : 0;

        if (empty($data->id)) {
            $data->position = $this->dbc->get_new_report_field_position($this->report_id);
            //create the ilp_report_field record
        	$data->id	=	$this->dbc->create_report_field($data);
        } else {
        	//update the report

        	$reportfield	=	$this->dbc->update_report_field($data);
	    }

        if(!empty($data->id)) {
        	$data->reportfield_id = $data->id;

            $this->specific_process_data($data);
        }
        return $data->id;
    }

    /**
     * Force extending class to add its own processing method
     */
    abstract protected function specific_process_data($data);
    

    
}

