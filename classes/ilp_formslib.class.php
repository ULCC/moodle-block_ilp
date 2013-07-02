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
        $this->_form = new ilp_MoodleQuickForm($this->_formname, $method, $action, $target, $attributes);
        if (!$editable){
            $this->_form->hardFreeze();
        }

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

    /**
     * Method to add a repeating group of elements to a form.
     *
     * This is the ilp implementation overridding original it adds a button to delete labels and also allows the user
     *to set the maximum number of repeated elements
     *
     * @param array $elementobjs Array of elements or groups of elements that are to be repeated
     * @param integer $repeats no of times to repeat elements initially
     * @param array $options Array of options to apply to elements. Array keys are element names.
     *                      This is an array of arrays. The second sets of keys are the option types
     *                      for the elements :
     *                          'default' - default value is value
     *                          'type' - PARAM_* constant is value
     *                          'helpbutton' - helpbutton params array is value
     *                          'disabledif' - last three moodleform::disabledIf()
     *                                           params are value as an array
     * @param string $repeathiddenname name for hidden element storing no of repeats in this form
     * @param string $addfieldsname name for button to add more fields
     * @param int $addfieldsno how many fields to add at a time
     * @param string $addstring name of button, {no} is replaced by no of blanks that will be added.
     * @param boolean $addbuttoninside if true, don't call closeHeaderBefore($addfieldsname). Default false.
     * @return int no of repeats of element in this page
     */
    function repeat_elements($elementobjs, $repeats, $options, $repeathiddenname,
                             $addfieldsname, $addfieldsno=5, $addstring=null, $addbuttoninside=false,$maxrepeats=NULL,$removefieldsname='removerepeatfield',$removestring=NULL){


        if ($addstring===null){
            $addstring = get_string('addfields', 'form', $addfieldsno);
        } else {
            $addstring = str_ireplace('{no}', $addfieldsno, $addstring);
        }

        $repeats = optional_param($repeathiddenname, $repeats, PARAM_INT);
        $addfields = optional_param($addfieldsname, '', PARAM_TEXT);
        $removefieldspressed = optional_param($removefieldsname, '', PARAM_TEXT);

        if (!empty($addfields)){
            $repeats += $addfieldsno;
        }

        if (!empty($removefieldspressed)) {
            $repeats -= 1;
        }

        $mform =& $this->_form;

        $mform->registerNoSubmitButton($addfieldsname);
        $mform->registerNoSubmitButton($removefieldsname);
        $mform->addElement('hidden', $repeathiddenname, $repeats);
        $mform->setType($repeathiddenname, PARAM_INT);
        //value not to be overridden by submitted value
        $mform->setConstants(array($repeathiddenname=>$repeats));
        $namecloned = array();

        //makes sure number of repeats is below the max number or repeats (if it has been set)
        if (!empty($maxrepeats) && $repeats > $maxrepeats) $repeats    =   $maxrepeats;

        if ($removestring===null){
            $removestring = get_string('removefield', 'block_ilp');
        } else {
            $removestring = str_ireplace('{no}', $repeats, $removestring);
        }

        for ($i = 0; $i < $repeats; $i++) {
            foreach ($elementobjs as $elementobj){
                $elementclone = fullclone($elementobj);
                $this->repeat_elements_fix_clone($i, $elementclone, $namecloned);

                if ($elementclone instanceof HTML_QuickForm_group && !$elementclone->_appendName) {
                    foreach ($elementclone->getElements() as $el) {
                        $this->repeat_elements_fix_clone($i, $el, $namecloned);
                    }
                }
                $elementclone->setLabel(str_replace('{no}', $i + 1, $elementclone->getLabel()));
                $mform->addElement($elementclone);
            }
        }
        for ($i=0; $i<$repeats; $i++) {
            foreach ($options as $elementname => $elementoptions){
                $pos=strpos($elementname, '[');
                if ($pos!==FALSE){
                    $realelementname = substr($elementname, 0, $pos+1)."[$i]";
                    $realelementname .= substr($elementname, $pos+1);
                }else {
                    $realelementname = $elementname."[$i]";
                }
                foreach ($elementoptions as  $option => $params){

                    switch ($option){
                        case 'default' :
                            $mform->setDefault($realelementname, $params);
                            break;
                        case 'helpbutton' :
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addHelpButton'), $params);
                            break;
                        case 'disabledif' :
                            foreach ($namecloned as $num => $name){
                                if ($params[0] == $name){
                                    $params[0] = $params[0]."[$i]";
                                    break;
                                }
                            }
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'disabledIf'), $params);
                            break;
                        case 'rule' :
                            if (is_string($params)){
                                $params = array(null, $params, null, 'client');
                            }
                            $params = array_merge(array($realelementname), $params);
                            call_user_func_array(array(&$mform, 'addRule'), $params);
                            break;
                        case 'type' :
                            //Type should be set only once
                            if (!isset($mform->_types[$elementname])) {
                                $mform->setType($elementname, $params);
                            }
                            break;
                    }
                }
            }
        }

        $buttonarray=array();

        if ($repeats < $maxrepeats)  $buttonarray[] = &$mform->createElement('submit', $addfieldsname, $addstring);;
        if ($repeats > 1)  $buttonarray[] = &$mform->createElement('submit', $removefieldsname, $removestring);
        if (!empty($buttonarray)) $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        if (!$addbuttoninside) {
            $mform->closeHeaderBefore($removefieldsname);
        }

        return $repeats;
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