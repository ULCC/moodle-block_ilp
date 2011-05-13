<?php

global $CFG;

require_once("$CFG->libdir/formslib.php");


/**
 * This is the class that forms should now extend
 */
class ilp_moodleform extends moodleform {

    /**
     * Return submitted data if properly submitted or returns NULL if validation fails or
     * if there is no submitted data.
     *
     * Edited to override the default get data method, such that data is not slashed unless
     * specifically requested to be.
     *
     * @param bool $slashed true means return data with addslashes applied
     * @return object submitted data; NULL if not valid or not submitted
     */
    function get_data($slashed = false) {

        return parent::get_data($slashed);
    }

    /**
     * This is identical to the overridden function except that it calls ilp_MoodleQuickForm instead
     * of MoodleQuickForm
     * @param <type> $action
     * @param <type> $customdata
     * @param <type> $method
     * @param <type> $target
     * @param <type> $attributes
     * @param <type> $editable
     */
    function ilp_moodleform($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true) {
        if (empty($action)){
            $action = strip_querystring(qualified_me());
        }

        $this->_formname = get_class($this); // '_form' suffix kept in order to prevent collisions of form id and other element
        $this->_customdata = $customdata;
        $this->_form =& new ilp_MoodleQuickForm($this->_formname, $method, $action, $target, $attributes);
        if (!$editable){
            $this->_form->hardFreeze();
        }
        
        
        //TODO find a way to emulate moodle 2 functionality in 1.9 and check if file manager 
        //$this->set_upload_manager(new upload_manager());

        $this->definition();

        $this->_form->addElement('hidden', 'sesskey', null); // automatic sesskey protection
        $this->_form->setType('sesskey', PARAM_RAW);
        $this->_form->setDefault('sesskey', sesskey());
        $this->_form->addElement('hidden', '_qf__'.$this->_formname, null);   // form submission marker
        $this->_form->setType('_qf__'.$this->_formname, PARAM_RAW);
        $this->_form->setDefault('_qf__'.$this->_formname, 1);
        $this->_form->_setDefaultRuleMessages();

        // we have to know all input types before processing submission ;-)
        $this->_process_submission($method);
    }
    
    
    function definition() {
    	
    }
}








/*
 * This class wraps the main Moodle form class so that stuff from HTMLarea fields can be encoded
 * and decoded properly. It is called when a new form is created by the ilp_moodleform class
 */
class ilp_MoodleQuickForm extends MoodleQuickForm {

    /**
     * Class constructor - same parameters as HTML_QuickForm_DHTMLRulesTableless
     * @param    string      $formName          Form's name.
     * @param    string      $method            (optional)Form's method defaults to 'POST'
     * @param    mixed      $action             (optional)Form's action - string or moodle_url
     * @param    string      $target            (optional)Form's target defaults to none
     * @param    mixed       $attributes        (optional)Extra attributes for <form> tag
     * @param    bool        $trackSubmit       (optional)Whether to track if the form was submitted by adding a special hidden field
     * @access   public
     */
    function ilp_MoodleQuickForm($formName, $method, $action, $target='', $attributes=array()) {

        global $CFG,$OUTPUT;

        static $formcounter = 1;

        HTML_Common::HTML_Common($attributes);
        $target = empty($target) ? array() : array('target' => $target);
        $this->_formName = $formName;

        if (is_a($action, 'moodle_url')) {
            $this->_pageparams = $action->hidden_params_out();
            $action = $action->out(true);
        } else {
            $this->_pageparams = '';
        }
        //no 'name' atttribute for form in xhtml strict :

        $attributes['action'] = $action;
        $attributes['method'] = $method;
        $attributes['accept-charset'] = 'utf-8';
        $attributes['id'] = (empty($attributes['id'])) ? 'mform'.$formcounter : $attributes['id'];
        $attributes += $target;

//
//            array('action'=>$action, 'method'=>$method,
//                'accept-charset'=>'utf-8', 'id'=>'mform'.$formcounter) + $target;

        $formcounter++;
        $this->updateAttributes($attributes);

        //this is custom stuff for Moodle :
        $oldclass = $this->getAttribute('class');

        if (!empty($oldclass)) {
            $this->updateAttributes(array('class'=>$oldclass.' mform'));
        } else {
            $this->updateAttributes(array('class'=>'mform'));
        }
        $this->_reqHTML = '<img class="req" title="'.get_string('requiredelement', 'form').'" alt="'.get_string('requiredelement', 'form').'" src="'.$OUTPUT->pix_url('req').'" />';
        $this->_advancedHTML = '<img class="adv" title="'.get_string('advancedelement', 'form').'" alt="'.get_string('advancedelement', 'form').'" src="'.$OUTPUT->pix_url('adv').'" />';
        $this->setRequiredNote(get_string('somefieldsrequired', 'form', '<img alt="'.get_string('requiredelement', 'form').'" src="'.$OUTPUT->pix_url('req').'" />'));
        //(Help file doesn't add anything) helpbutton('requiredelement', get_string('requiredelement', 'form'), 'moodle', true, false, '', true));
    }


    /**
     * Initializes default form values.
     *
     * Edited to decode the html in htmleditor elements.
     *
     * @param     array    $defaultValues       values used to fill the form
     * @param     mixed    $filter              (optional) filter(s) to apply to all default values
     * @since     1.0
     * @access    public
     * @return    void
     */
    function setDefaults($defaultValues = null, $filter = null) {

        parent::setDefaults($defaultValues, $filter);

        // decode the values for htmleditor elements
        foreach ($this->_defaultValues as $name => &$value) {

            if ($this->getElementType($name) == 'htmleditor') {
                
            	
            	/********NEEDS ATTENTION*******/
            	
            	//need to replace assmgr_db::decode with a function for ilp
            	$this->_defaultValues[$name] = ilp_db::decode($value);
            }
        }

        // reinitialise each of the elements
        foreach (array_keys($this->_elements) as $key) {
            $this->_elements[$key]->onQuickFormEvent('updateValue', null, $this);
        }
    }
    
}



?>