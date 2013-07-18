<?php
@require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');

require_once($CFG->dirroot . '/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');

class ilp_mis_attendance_percentbar_plugin extends ilp_mis_attendance_plugin
{

   public function __construct($params = array())
    {
        parent::__construct($params);

        //find out whether a table or stored procedure is used in queries 
        $this->tabletype = get_config('block_ilp', 'mis_plugin_attendance_percentbartabletype');
        $this->data = false;
    }


    /**
     *
     * return the punctuality
     * @return int
     */
    public function get_student_punctuality()
    {
        return $this->return_percent_data('punctuality');
    }

    /**
     *
     * return the attendance
     * @return int
     */
    public function get_student_attendance()
    {
        return $this->return_percent_data('attendance');
    }


    /**
     * This function returns the percentage data for the given student for either
     * attendance or punctuality
     * @param $data_type expected to be attendance or punctuality
     * @return bool
     */
    public function return_percent_data($datatype)
    {
        if (!empty($this->data)) {
            if (!empty($this->data[$datatype])) return $this->data[$datatype];
        }
        return false;
    }

    /*
    * display the current state of $this->data
    */
    public function display()
    {
        global $CFG;

        if (!empty($this->data)) {

        }
    }

    public function set_data($student_id, $user_id=null)
    {
        //get the plugins configuration and pass to variables
        $tablename = get_config('block_ilp', 'mis_plugin_attendance_percentbarstudenttable'); //$this->params[ 'student_table' ];

        if (!empty($tablename)) {
            $keyfield = get_config('block_ilp', 'mis_plugin_attendance_percentbarstudentid');
            $attendance_field = get_config('block_ilp', 'mis_plugin_attendance_percentbarpunctuality');
            $punctuality_field = get_config('block_ilp', 'mis_plugin_attendance_percentbarattendance');

            $querydata = $this->cached_dbquery($tablename, array($keyfield => array('=' => $student_id)), array($attendance_field, $punctuality_field));

            $data = (is_array($querydata)) ? array_shift($querydata) : $querydata;

            if (!empty($data)) {
                $this->data = array('attendance' => $data[$attendance_field], 'punctuality' => $data[$punctuality_field]);
            }
        }
    }


    public static function plugin_type()
    {
        return 'misc';
    }

    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_percentbar_plugin&plugintype=mis">' . get_string('ilp_mis_attendance_percentbar_plugin_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_plugin_attendance', '', $link));
    }

    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_plugin_attendance_percentbarstudenttable', get_string('ilp_mis_attendance_percentbar_plugin_studenttable', 'block_ilp'), get_string('ilp_mis_attendance_percentbar_plugin_studenttabledesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_attendance_percentbarstudentid', get_string('ilp_mis_attendance_percentbar_plugin_studentid', 'block_ilp'), get_string('ilp_mis_attendance_percentbar_plugin_studentiddesc', 'block_ilp'), 'studentID');

        $this->config_text_element($mform, 'mis_plugin_attendance_percentbarpunctuality', get_string('ilp_mis_attendance_percentbar_plugin_punctuality', 'block_ilp'), get_string('ilp_mis_attendance_percentbar_plugin_punctualitydesc', 'block_ilp'), 'punctuality');

        $this->config_text_element($mform, 'mis_plugin_attendance_percentbarattendance', get_string('ilp_mis_attendance_percentbar_plugin_attendance', 'block_ilp'), get_string('ilp_mis_attendance_percentbar_plugin_attendancedesc', 'block_ilp'), 'attendance');

        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_attendance_percentbar_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_attendance_percentbartabletype', $options, get_string('ilp_mis_attendance_percentbar_plugin_tabletype', 'block_ilp'), get_string('ilp_mis_attendance_percentbar_plugin_tabletypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_percentbar_plugin_pluginstatus', $options, get_string('ilp_mis_attendance_percentbar_plugin_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_percentbar_plugin_pluginstatusdesc', 'block_ilp'), 0);

    }


    /**
     * Adds the string values from the tab to the language file
     *
     * @param    array &$string the language strings array passed by reference so we
     * just need to simply add the plugins entries on to it
     */
    static function language_strings(&$string)
    {

        $string['ilp_mis_attendance_percentbar_plugin_attendance'] = 'attendance';
        $string['ilp_mis_attendance_percentbar_plugin_punctuality'] = 'punctuality';
        $string['ilp_mis_attendance_percentbar_plugin_pluginname'] = 'Attendance & Punctuality';
        $string['ilp_mis_attendance_percentbar_plugin_pluginnamesettings'] = 'Attendance & Punctuality Overview Configuration';

        $string['ilp_mis_attendance_percentbar_plugin_studenttable'] = 'MIS table';
        $string['ilp_mis_attendance_percentbar_plugin_studenttabledesc'] = 'The table in the MIS where the data for this plugin will be retrieved from';

        $string['ilp_mis_attendance_percentbar_plugin_studentid'] = 'Student ID field';
        $string['ilp_mis_attendance_percentbar_plugin_studentiddesc'] = 'The field that will be used to find the student';

        $string['ilp_mis_attendance_percentbar_plugin_punctuality'] = 'Punctuality';
        $string['ilp_mis_attendance_percentbar_plugin_punctualitydesc'] = 'The field that holds punctuality data';

        $string['ilp_mis_attendance_percentbar_plugin_attendance'] = 'Attendance';
        $string['ilp_mis_attendance_percentbar_plugin_attendancedesc'] = 'The field that holds attendance data';

        $string['ilp_mis_attendance_percentbar_plugin_tabletype'] = 'Table type';
        $string['ilp_mis_attendance_percentbar_plugin_tabletypedesc'] = 'Does this plugin connect to a table or stored procedure';

        $string['ilp_mis_attendance_percentbar_plugin_pluginstatus'] = 'Status';
        $string['ilp_mis_attendance_percentbar_plugin_pluginstatusdesc'] = 'Is the block enabled or disabled';

        return $string;
    }

    function getAttendance()
    {
        return $this->return_percent_data('attendance');
    }

    function getPunctuality()
    {
        return $this->return_percent_data('punctuality');
    }


}
