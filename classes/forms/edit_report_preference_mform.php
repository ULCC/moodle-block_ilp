<?php

/**
 * This class makes the form that is used to create reports
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


class edit_report_preference_mform extends ilp_moodleform {

    public		$report_id;
    public      $preference_id;
    public      $course_id;
    public      $user_id;
    public      $report;
    public		$dbc;

    /**
     * TODO comment this
     */
    function __construct($report_id,$course_id=null,$user_id=null,$preference_id=null) {

        global $CFG;

        $this->dbc			=	new ilp_db();

        $this->report_id	=	$report_id;
        $this->preference   =   $preference_id;
        $this->course_id    =   $course_id;
        $this->user_id      =   $user_id;


        // call the parent constructor
        parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_report_preference.php?report_id={$this->report_id}&course_id={$course_id}&user_id={$user_id}");
    }

    /**
     * TODO comment this
     */
    function definition() {
        global $USER, $CFG;

        include_once ($CFG->dirroot."/blocks/ilp/classes/ilp_report_rules.class.php");

        $dbc = new ilp_db;

        $mform =& $this->_form;

        $student        =   $this->dbc->get_user_by_id($this->user_id);

        $report         =   $this->dbc->get_report_by_id($this->report_id);

        $fieldsettitle = (!empty($this->report_id)) ? get_string('editreport', 'block_ilp') : get_string('createreport', 'block_ilp');

        //create a new fieldset
        $mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset"><div>');
        $mform->addElement('html', '<legend >'.$fieldsettitle.'</legend>');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'creator_id', $USER->id);
        $mform->setType('creator_id', PARAM_INT);

        $mform->addElement(
            'html',
            "<div class='fitem'><div class='fitemtitle'> <label>".get_string('reportname','block_ilp')." :</label></div><div class='felement'> ".$report->name." </div></div>"
        );

        $mform->addElement(
            'html',
            "<div class='fitem'><div class='fitemtitle'> <label>".get_string('name').":</label> </div><div class='felement'> ".fullname($student)." </div></div>"
        );

        $reportrules    =   new ilp_report_rules($this->report_id,$this->user_id);
if ($reportrules->can_add_extensions($this->report_id)) {

        if ($report->reporttype ==  ILP_RT_RECURRING_FINALDATE || $report->reporttype == ILP_RT_FINALDATE) {

            $mform->addElement('checkbox', 'usereportlockdate',get_string('usereportlock','block_ilp'),null);

            //specific date selector
            $mform->addElement(
                'date_time_selector',
                'reportlockdate',
                get_string('reportlockdate','block_ilp'),
                array('optional' => false ),
                array('class' => 'lockdate')
            );
        }
            //if maximum number of entries is not specified- any number of entries allowed, therefore cannot be extended
            if ($report->reportmaxentries!=null ){
        $mform->addElement('checkbox', 'usemaxentries',get_String('maxedit','block_ilp'),null);

        // maximum entries element
        $mform->addElement(
             'text',
             'maxentries',
             get_string('maxentries', 'block_ilp'),
             array('class' => 'form_input')
        );
                $mform->setType('maxentries', PARAM_INT);
            }

        if ($report->reporttype ==  ILP_RT_RECURRING_FINALDATE || $report->reporttype == ILP_RT_RECURRING) {

            $mform->addElement('checkbox', 'userecurmax',get_String('maxedit','block_ilp'),null);

            // maximum entries element
            $mform->addElement(
                'text',
                'recurmax',
                get_string('recurringmax', 'block_ilp'),
                array('class' => 'recurring')
            );
        }

        $mform->addElement('hidden', 'user_id', $this->user_id);
        $mform->setType('user_id', PARAM_INT);

        $mform->addElement('hidden', 'report_id', $this->report_id);
        $mform->setType('report_id', PARAM_INT);

        $mform->addElement('hidden', 'course_id', $this->course_id);
        $mform->setType('course_id', PARAM_INT);

        $buttonarray[] = $mform->createElement('submit', 'saveanddisplaybutton', get_string('submit'));
        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        //close the fieldset
        $mform->addElement('html', '</div></fieldset>');
    }
   }



    function specific_validation( $data ){

        $data   =   (object)    $data;

        $this->errors = array();


        if (isset($data->usemaxentries)) {
            if (empty($data->maxentries))   $this->error['maxentries']  = get_string('entermaxentries','block_ilp');
            if (!is_number($data->maxentries))   $this->error['maxentries']  = get_string('enteranumber','block_ilp');
        }

        if (isset($data->userecurmax)) {
            if (empty($data->recurmax))   $this->error['recurmax']  = get_string('recurentermaxentries','block_ilp');
            if (!is_number($data->recurmax))   $this->error['recurmax']  = get_string('enteranumber','block_ilp');
        }



    }


    /**
     * TODO comment this
     */
    function process_data($data) {
        global $CFG;
        $preference             =       new stdClass();
        $preference->report_id  =       $data->report_id;
        $preference->user_id    =       $this->user_id;

        if (isset($data->userecurmax)) {
            $preference->action     =    'report_extension';
            $preference->param     =     'recurmax';
            $preference->value      =    $data->recurmax;
            $data->id   =   $this->dbc->create_preference($preference);
        }

        if (isset($data->usemaxentries)) {
            $preference->action     =    'report_extension';
            $preference->param     =     'reportmaxentries';
            $preference->value      =    $data->maxentries;
            $data->id   =   $this->dbc->create_preference($preference);
        }

        if (isset($data->usereportlockdate)) {
            $preference->action     =    'report_extension';
            $preference->param     =     'reportlockdate';
            $preference->value      =    $data->reportlockdate;
            $data->id   =   $this->dbc->create_preference($preference);
        }


        return $data->id;
    }

    /**
     * TODO comment this
     */
    function definition_after_data() {

    }

}


?>
