<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');


class ilp_mis_attendance_plugin_byclass extends ilp_mis_attendance_plugin
{

    public $fields;
    public $normdata;
    public $courselist;


    protected $monthlist = array();

    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->normdata = false;
        $this->courselist = false;
        $this->tabletype = get_config('block_ilp', 'mis_plugin_course_byclass_tabletype');

    }

    /*
    * display the current state of $this->data
    */
    public function display()
    {
        global $CFG, $PARSER, $PAGE;

        $misperiod_id = $PARSER->optional_param('mis_period_id', date('m',time()), PARAM_ALPHANUM);
        $miscourse_id = $PARSER->optional_param('mis_course_id', NULL, PARAM_ALPHANUM);

        $params = explode('&', $_SERVER['QUERY_STRING']);
        $hiddenparams = "";

        foreach ($params as $v) {
            if (strpos($v, 'mis_course_id') === FALSE && strpos($v, 'mis_period_id') === FALSE) {
                $p = explode('=', $v);
                $hiddenparams .= "<input type='hidden' name='{$p[0]}' value='{$p[1]}' />";
            }
        }


        if (!empty($this->normdata)) {
            $this->init_bgcolours();

            //set up the flexible table for displaying
            ob_start();
            //instantiate the ilp_ajax_table class
            $flextable = new ilp_mis_ajax_table('attendance_byclass', true, 'ilp_mis_attendance_plugin_byclass_container');

            //setup the headers and columns with the fields that have been requested


            $headers = array();
            $columns = array();

            if (get_config('block_ilp', 'mis_plugin_course_byclass_datetime')) $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_day', 'block_ilp');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_room')) $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_room', 'block_ilp');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_starttime')) $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_start', 'block_ilp');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_endtime')) $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_end', 'block_ilp');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_tutor')) $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_tutor', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_overall', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_punct', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_attend', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_unauth', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_late', 'block_ilp');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_latexfield'))     $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_latex', 'block_ilp');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_notifiedfield'))  $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_notified', 'block_ilp');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_placementfield')) $headers[] = get_string('ilp_mis_attendance_plugin_byclass_disp_placement', 'block_ilp');

            if (get_config('block_ilp', 'mis_plugin_course_byclass_datetime')) $columns[] = 'day';
            if (get_config('block_ilp', 'mis_plugin_course_byclass_room')) $columns[] = 'room';
            if (get_config('block_ilp', 'mis_plugin_course_byclass_starttime')) $columns[] = 'start';
            if (get_config('block_ilp', 'mis_plugin_course_byclass_endtime')) $columns[] = 'end';
            if (get_config('block_ilp', 'mis_plugin_course_byclass_tutor')) $columns[] = 'tutor';
            $columns[] = 'overall';
            $columns[] = 'present';
            $columns[] = 'absent';
            $columns[] = 'unauth';
            $columns[] = 'late';
            if (get_config('block_ilp', 'mis_plugin_course_byclass_latexfield'))        $columns[] = 'latex';
            if (get_config('block_ilp', 'mis_plugin_course_byclass_notifiedfield'))     $columns[] = 'notified';
            if (get_config('block_ilp', 'mis_plugin_course_byclass_placementfield'))    $columns[] = 'placement';



            //define the columns in the tables
            $flextable->define_columns($columns);

            //define the headers in the tables
            $flextable->define_headers($headers);

            if (get_config('block_ilp','mis_plugin_course_byclass_markspresentcss'))
                $flextable->column_style('present','background-color',get_config('block_ilp','mis_plugin_course_byclass_markspresentcss'));


            if (get_config('block_ilp','mis_plugin_course_byclass_marksabsentcss'))
                $flextable->column_style('absent','background-color',get_config('block_ilp','mis_plugin_course_byclass_marksabsentcss'));


            if (get_config('block_ilp','mis_plugin_course_byclass_marksauthabsentcss'))
                $flextable->column_style('unauth','background-color',get_config('block_ilp','mis_plugin_course_byclass_marksauthabsentcss'));


            if (get_config('block_ilp','mis_plugin_course_byclass_markslatecss'))
                $flextable->column_style('late','background-color',get_config('block_ilp','mis_plugin_course_byclass_markslatecss'));


            if (get_config('block_ilp','mis_plugin_course_byclass_latexcss') && get_config('block_ilp', 'mis_plugin_course_byclass_latexfield'))
                $flextable->column_style('latex','background-color',get_config('block_ilp','mis_plugin_course_byclass_latexcss'));


            if (get_config('block_ilp','mis_plugin_course_byclass_placementcss') && get_config('block_ilp', 'mis_plugin_course_byclass_placementfield'))
                $flextable->column_style('placement','background-color',get_config('block_ilp','mis_plugin_course_byclass_placementcss'));


            if (get_config('block_ilp','mis_plugin_course_byclass_notifiedcss') && get_config('block_ilp', 'mis_plugin_course_byclass_notifiedfield'))
                $flextable->column_style('notified','background-color',get_config('block_ilp','mis_plugin_course_byclass_notifiedcss'));



            //we do not need the intialbars
            $flextable->initialbars(false);

            $flextable->set_attribute('class', 'flexible generaltable');

            //setup the flextable
            $flextable->setup();

            foreach ($this->normdata as $dayid => $data) {
                foreach ($data as $d) {
                    if (get_config('block_ilp', 'mis_plugin_course_byclass_datetime')) $data['day'] = $d['day'];
                    if (get_config('block_ilp', 'mis_plugin_course_byclass_room')) $data['room'] = $d['room'];
                    if (get_config('block_ilp', 'mis_plugin_course_byclass_starttime')) $data['start'] = $d['starttime'];
                    if (get_config('block_ilp', 'mis_plugin_course_byclass_endtime')) $data['end'] = $d['endtime'];
                    if (get_config('block_ilp', 'mis_plugin_course_byclass_tutor')) $data['tutor'] = $d['tutor'];
                    $data['overall'] = $this->format_background_by_value( $d['attendance'] . '%' );
                    $data['present']    = $d['markspresent'];
                    $data['absent']     = $d['marksabsent'];
                    $data['unauth']     = $d['marksauthabsent'];
                    $data['late']       = $d['markslate'];
                    if (get_config('block_ilp', 'mis_plugin_course_byclass_latexfield'))     $data['latex']      = $d['latex'];
                    if (get_config('block_ilp', 'mis_plugin_course_byclass_notifiedfield'))  $data['notified']   = $d['notified'];
                    if (get_config('block_ilp', 'mis_plugin_course_byclass_placementfield')) $data['placement']  = $d['placement'];
                    $flextable->add_data_keyed($data);
                }
            }

            $flextable->finish_html();
            $pluginoutput = ob_get_contents();
            ob_end_clean();

            ob_start();
            require_once $CFG->dirroot . '/blocks/ilp/plugins/mis/ilp_mis_attendance_plugin_byclass.html';
            $output = ob_get_contents();
            ob_end_clean();


        } else {

            if( $msg = get_string('nodataornoconfig', 'block_ilp') ){
                $pluginoutput = '<div id="plugin_nodata">' . $msg . '</div>';
            }

            ob_start();
            require_once $CFG->dirroot . '/blocks/ilp/plugins/mis/ilp_mis_attendance_plugin_byclass.html';
            $output = ob_get_contents();
            ob_end_clean();
        }

        return $output;

    }


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_byclass&plugintype=mis">' . get_string('ilp_mis_attendance_plugin_byclass_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_attendance_plugin_byclass', '', $link));
    }

    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_plugin_course_byclass_table', get_string('ilp_mis_attendance_plugin_byclass_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform,'mis_plugin_course_byclass_prelimcalls',get_string('ilp_mis_attendance_plugin_byclass_prelimcalls', 'block_ilp'),get_string('ilp_mis_attendance_plugin_byclass_prelimcallsdesc', 'block_ilp'),'');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_studentidfield', get_string('ilp_mis_attendance_plugin_byclass_studentidfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_studentidfielddesc', 'block_ilp'), 'studentID');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_courseid', get_string('ilp_mis_attendance_plugin_byclass_courseid', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_courseiddesc', 'block_ilp'), 'courseID');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_coursename', get_string('ilp_mis_attendance_plugin_byclass_coursename', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_coursenamedesc', 'block_ilp'), 'courseName');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_registerid', get_string('ilp_mis_attendance_plugin_byclass_registerid', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_registeriddesc', 'block_ilp'), 'registerID');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_period', get_string('ilp_mis_attendance_plugin_byclass_period', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_perioddesc', 'block_ilp'), 'period');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_registerName', get_string('ilp_mis_attendance_plugin_byclass_registername', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_registernamedesc', 'block_ilp'), 'registerName');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_datetime', get_string('ilp_mis_attendance_plugin_byclass_datetime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_datetimedesc', 'block_ilp'), 'datetime');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_room', get_string('ilp_mis_attendance_plugin_byclass_room', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_roomdesc', 'block_ilp'), 'room');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_starttime', get_string('ilp_mis_attendance_plugin_byclass_starttime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_starttimedesc', 'block_ilp'), 'starttime');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_endtime', get_string('ilp_mis_attendance_plugin_byclass_endtime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_endtimedesc', 'block_ilp'), 'endtime');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_tutor', get_string('ilp_mis_attendance_plugin_byclass_tutor', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_tutordesc', 'block_ilp'), 'tutor');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_markstotalfield', get_string('ilp_mis_attendance_plugin_byclass_markstotal', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_markstotaldesc', 'block_ilp'), 'marksTotal');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_markspresentfield', get_string('ilp_mis_attendance_plugin_byclass_markspresent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_markspresentdesc', 'block_ilp'), 'marksPresent');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_markspresentcss', get_string('ilp_mis_attendance_plugin_byclass_markspresentcss', 'block_ilp'), '', '');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_marksabsentfield', get_string('ilp_mis_attendance_plugin_byclass_marksabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_marksabsentdesc', 'block_ilp'), 'marksAbsent');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_marksabsentcss', get_string('ilp_mis_attendance_plugin_byclass_marksabsentcss', 'block_ilp'), '', '');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_marksauthabsentfield', get_string('ilp_mis_attendance_plugin_byclass_marksauthabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_marksauthabsentdesc', 'block_ilp'), 'marksAuthAbsent');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_marksauthabsentcss', get_string('ilp_mis_attendance_plugin_byclass_marksauthabsentcss', 'block_ilp'), '', '');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_markslatefield', get_string('ilp_mis_attendance_plugin_byclass_markslate', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_markslatedesc', 'block_ilp'), 'marksLate');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_markslatecss', get_string('ilp_mis_attendance_plugin_byclass_markslatecss', 'block_ilp'), '', '');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_latexfield', get_string('ilp_mis_attendance_plugin_byclass_latex', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_latexdesc', 'block_ilp'), 'latex');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_latexcss', get_string('ilp_mis_attendance_plugin_byclass_latexcss', 'block_ilp'), '', '');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_notifiedfield', get_string('ilp_mis_attendance_plugin_byclass_notified', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_notifieddesc', 'block_ilp'), 'notified');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_notifiedcss', get_string('ilp_mis_attendance_plugin_byclass_notifiedcss', 'block_ilp'), '', '');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_placementfield', get_string('ilp_mis_attendance_plugin_byclass_placement', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_placementdesc', 'block_ilp'), 'placement');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_placementcss',get_string('ilp_mis_attendance_plugin_byclass_placementcss', 'block_ilp'), '', '');

        $this->config_text_element($mform, 'mis_plugin_course_byclass_sort', get_string('ilp_mis_attendance_plugin_byclass_sort', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_sortdesc', 'block_ilp'), '');

        $options    =   array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_course_byclass_key', $options, get_string('ilp_mis_attendance_plugin_byclass_key', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_keydesc', 'block_ilp'), 1);

        $options = array(
            0 => get_string('ilp_mis_attendance_plugin_byclass_ignore', 'block_ilp'),
            1 => get_string('ilp_mis_attendance_plugin_byclass_positive', 'block_ilp'),
            2 => get_string('ilp_mis_attendance_plugin_byclass_negative', 'block_ilp'),
        );

        $this->config_select_element($mform, 'mis_plugin_course_byclass_authorised', $options, get_string('ilp_mis_attendance_plugin_byclass_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_tabledesc', 'block_ilp'), 1);


        $options = array(
            0 => get_string('ilp_mis_attendance_plugin_byclass_months', 'block_ilp'),
            1 => get_string('ilp_mis_attendance_plugin_byclass_terms', 'block_ilp'),
        );

        $this->config_select_element($mform, 'mis_plugin_course_byclass_timeperiod', $options, get_string('ilp_mis_attendance_plugin_byclass_timeperiod', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_timeperioddesc', 'block_ilp'), 1);

        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_course_byclass_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_course_byclass_tabletype', $options, get_string('ilp_mis_attendance_plugin_byclass_tabletype', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_tabletypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_plugin_byclass_pluginstatus', $options, get_string('ilp_mis_attendance_plugin_byclass_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_plugin_byclass_pluginstatusdesc', 'block_ilp'), 0);

    }


    public static function plugin_type()
    {
        return 'attendance';
    }

    static function language_strings(&$string)
    {
        $string['ilp_mis_attendance_plugin_byclass_pluginname'] = 'Register By Class Attendance Overview';
        $string['ilp_mis_attendance_plugin_byclass_pluginnamesettings'] = 'Register By Class Attendance Configuration';


        $string['ilp_mis_attendance_plugin_byclass_table'] = 'Register attendance table';
        $string['ilp_mis_attendance_plugin_byclass_tabledesc'] = 'table containing overview of student attendence by course by month';

        $string['ilp_mis_attendance_plugin_byclass_studentidfield'] = 'Student id field';
        $string['ilp_mis_attendance_plugin_byclass_studentidfielddesc'] = 'The field containing the mis user id';


        $string['ilp_mis_attendance_plugin_byclass_courseid'] = 'Course id field';
        $string['ilp_mis_attendance_plugin_byclass_courseiddesc'] = 'The field containing course id data';

        $string['ilp_mis_attendance_plugin_byclass_coursename'] = 'Course Name field';
        $string['ilp_mis_attendance_plugin_byclass_coursenamedesc'] = 'The field containing course name data';

        $string['ilp_mis_attendance_plugin_byclass_registerid'] = 'Register ID field';
        $string['ilp_mis_attendance_plugin_byclass_registeriddesc'] = 'The field containing register id data';

        $string['ilp_mis_attendance_plugin_byclass_prelimcalls']						= 'Preliminary db calls';
        $string['ilp_mis_attendance_plugin_byclass_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';


        $string['ilp_mis_attendance_plugin_byclass_registername'] = 'Register Name field';
        $string['ilp_mis_attendance_plugin_byclass_registernamedesc'] = 'The field containing register name data';

        $string['ilp_mis_attendance_plugin_byclass_period'] = 'Period field';
        $string['ilp_mis_attendance_plugin_byclass_perioddesc'] = 'The field containing period data';

        $string['ilp_mis_attendance_plugin_byclass_datetime'] = 'Date time field';
        $string['ilp_mis_attendance_plugin_byclass_datetimedesc'] = 'The field containing date time data';

        $string['ilp_mis_attendance_plugin_byclass_room'] = 'Room field';
        $string['ilp_mis_attendance_plugin_byclass_roomdesc'] = 'The field containing room data';

        $string['ilp_mis_attendance_plugin_byclass_starttime'] = 'Start time field';
        $string['ilp_mis_attendance_plugin_byclass_starttimedesc'] = 'The field containing course start time data';

        $string['ilp_mis_attendance_plugin_byclass_endtime'] = 'End time field';
        $string['ilp_mis_attendance_plugin_byclass_endtimedesc'] = 'The field containing end time data';

        $string['ilp_mis_attendance_plugin_byclass_tutor'] = 'Tutor field';
        $string['ilp_mis_attendance_plugin_byclass_tutordesc'] = 'The field containing tutor name data';

        $string['ilp_mis_attendance_plugin_byclass_markstotal'] = 'Marks total field';
        $string['ilp_mis_attendance_plugin_byclass_markstotaldesc'] = 'The field containing marks total data';

        $string['ilp_mis_attendance_plugin_byclass_markspresent'] = 'marks present field';
        $string['ilp_mis_attendance_plugin_byclass_markspresentdesc'] = 'The field containing the marks present data';

        $string['ilp_mis_attendance_plugin_byclass_marksabsent'] = 'marks absent field';
        $string['ilp_mis_attendance_plugin_byclass_marksabsentdesc'] = 'The field containing the absents data';

        $string['ilp_mis_attendance_plugin_byclass_marksauthabsent'] = 'marks authabsent field';
        $string['ilp_mis_attendance_plugin_byclass_marksauthabsentdesc'] = 'the field containing the authorised absents data';

        $string['ilp_mis_attendance_plugin_byclass_markslate'] = 'marks late field';
        $string['ilp_mis_attendance_plugin_byclass_markslatedesc'] = 'the field containing the marks late data';

        $string['ilp_mis_attendance_plugin_byclass_latex'] = 'late x field';
        $string['ilp_mis_attendance_plugin_byclass_latexdesc'] = 'the field containing the late x data';

        $string['ilp_mis_attendance_plugin_byclass_notified'] = 'notified field';
        $string['ilp_mis_attendance_plugin_byclass_notifieddesc'] = 'the field containing the notified data';

        $string['ilp_mis_attendance_plugin_byclass_placement'] = 'placement marks field';
        $string['ilp_mis_attendance_plugin_byclass_placementdesc'] = 'the field containing the placement marks data';

        $string['ilp_mis_attendance_plugin_byclass_authorised'] = 'Authorised Absents';
        $string['ilp_mis_attendance_plugin_byclass_authoriseddesc'] = 'What should be done with authorised absents? Positive - to add to present marks, Negative - to add to absents and ignore to not count';

        $string['ilp_mis_attendance_plugin_byclass_ignore'] = 'Ignore';
        $string['ilp_mis_attendance_plugin_byclass_positive'] = 'Positive';
        $string['ilp_mis_attendance_plugin_byclass_negative'] = 'Negative';

        $string['ilp_mis_attendance_plugin_byclass_timeperiod'] = 'Time Period';
        $string['ilp_mis_attendance_plugin_byclass_timeperioddesc'] = 'What time period does the data deal with a month or a term';

        $string['ilp_mis_attendance_plugin_byclass_months'] = 'Months';
        $string['ilp_mis_attendance_plugin_byclass_terms'] = 'Terms';


        $string['ilp_mis_attendance_plugin_byclass_pluginstatus'] = 'Status';
        $string['ilp_mis_attendance_plugin_byclass_pluginstatusdesc'] = 'is the plugin enabled or disabled';

        $string['ilp_mis_attendance_plugin_byclass_tabletype'] = 'Table type';
        $string['ilp_mis_attendance_plugin_byclass_tabletypedesc'] = 'Is a table or a stored procedure being used';

        $string['ilp_mis_attendance_plugin_byclass_key'] = 'Enable key table';
        $string['ilp_mis_attendance_plugin_byclass_keydesc'] = 'Do you want a key to display with the class register';
        $string['ilp_mis_attendance_plugin_byclass_sort'] = 'Sort field';
        $string['ilp_mis_attendance_plugin_byclass_sortdesc'] = 'the field you want to sort by';


        $string['ilp_mis_attendance_plugin_byclass_markspresentcss'] = 'marks present hex colour';
        $string['ilp_mis_attendance_plugin_byclass_marksabsentcss'] = 'marks absent hex colour';
        $string['ilp_mis_attendance_plugin_byclass_marksauthabsentcss'] = 'marks authabsent hex colour';
        $string['ilp_mis_attendance_plugin_byclass_markslatecss'] = 'marks late hex colour';
        $string['ilp_mis_attendance_plugin_byclass_latexcss'] = 'late x hex colour';
        $string['ilp_mis_attendance_plugin_byclass_notifiedcss'] = 'notified hex colour';
        $string['ilp_mis_attendance_plugin_byclass_placementcss'] = 'placement hex colour';
        $string['ilp_mis_attendance_plugin_byclass_authorisedcss'] = 'Authorised Absents hex colour';





        $string['ilp_mis_attendance_plugin_byclass_disp_day'] = 'Day';
        $string['ilp_mis_attendance_plugin_byclass_disp_room'] = 'Room';
        $string['ilp_mis_attendance_plugin_byclass_disp_start'] = 'Start';
        $string['ilp_mis_attendance_plugin_byclass_disp_end'] = 'End';
        $string['ilp_mis_attendance_plugin_byclass_disp_tutor'] = 'Tutor';
        $string['ilp_mis_attendance_plugin_byclass_disp_overall'] = 'Att.';
        $string['ilp_mis_attendance_plugin_byclass_disp_punct'] = 'P';
        $string['ilp_mis_attendance_plugin_byclass_disp_attend'] = 'A';
        $string['ilp_mis_attendance_plugin_byclass_disp_unauth'] = 'U';
        $string['ilp_mis_attendance_plugin_byclass_disp_late'] = 'L';
        $string['ilp_mis_attendance_plugin_byclass_disp_latex'] = 'L';
        $string['ilp_mis_attendance_plugin_byclass_disp_notified'] = 'NA';
        $string['ilp_mis_attendance_plugin_byclass_disp_placement'] = 'WP';
        $string['ilp_mis_attendance_plugin_byclass_disp_punct_desc'] = 'P description';
        $string['ilp_mis_attendance_plugin_byclass_disp_attend_desc'] = 'A description';
        $string['ilp_mis_attendance_plugin_byclass_disp_unauth_desc'] = 'U description';
        $string['ilp_mis_attendance_plugin_byclass_disp_late_desc'] = 'L description';
        $string['ilp_mis_attendance_plugin_byclass_disp_latex_desc'] = 'L description';
        $string['ilp_mis_attendance_plugin_byclass_disp_notified_desc'] = 'NA description';
        $string['ilp_mis_attendance_plugin_byclass_disp_placement_desc'] = 'WP description';
        $string['ilp_mis_attendance_plugin_byclass_disp_key'] = 'KEY';






    }


    /**
     * Retrieves user data from the mis database
     *
     * @param $misuser_id the mis id of the user whose data will be retireved.
     */
    function set_data($misuser_id,$user_id=null)
    {
        global $PARSER;

        $table = get_config('block_ilp', 'mis_plugin_course_byclass_table');

        $this->mis_user_id = $misuser_id;

        if (!empty($table)) {

            //we need to perform a query to get all courses that the user is in
            $sidfield = get_config('block_ilp', 'mis_plugin_course_byclass_studentidfield');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_plugin_course_byclass_idtype');
            $misuser_id = (empty($idtype)) ? "'{$misuser_id}'" : $misuser_id;

            $prelimdbcalls   =    get_config('block_ilp','mis_plugin_course_byclass_prelimcalls');

            //create the key that will be used in sql query
            $keyfields = array($sidfield => array('=' => $misuser_id));

            $this->coursedata = $this->dbquery($table, $keyfields, array('courseid'=> get_config('block_ilp', 'mis_plugin_course_byclass_courseid'), 'coursename'=>get_config('block_ilp', 'mis_plugin_course_byclass_coursename')),null,$prelimdbcalls);

            //set the default course & month that will be selected
            $defaultcourseid        = NULL;
            $defaultperiodid        = date('m',time());
            $i  =   0;

            //get the db fieldnames for the courseid and course name fields
            if (get_config('block_ilp', 'mis_plugin_course_byclass_courseid')) $this->fields['courseid'] = get_config('block_ilp', 'mis_plugin_course_byclass_courseid');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_coursename')) $this->fields['coursename'] = get_config('block_ilp', 'mis_plugin_course_byclass_coursename');


            foreach($this->coursedata   as $d)  {
                if (!isset($this->courselist[$d[$this->fields['courseid']]]) && isset($d[$this->fields['coursename']])) {
                    $this->courselist[$d[$this->fields['courseid']]] = $d[$this->fields['coursename']];
                    if (empty($i)) {
                        $defaultcourseid    =   $d[$this->fields['courseid']];
                        $i++;
                    }
                }
            }


            $misperiod_id = $PARSER->optional_param('mis_period_id', $defaultperiodid, PARAM_ALPHANUM);
            $miscourse_id = $PARSER->optional_param('mis_course_id', $defaultcourseid, PARAM_ALPHANUM);

            if (!empty($misperiod_id)) {
                $pidfield = get_config('block_ilp', 'mis_plugin_course_byclass_period');
                $keyfields[$pidfield] = array('=' => $misperiod_id);
            }

            if (!empty($miscourse_id)) {
                $cidfield = get_config('block_ilp', 'mis_plugin_course_byclass_courseid');
                $miscourse_id   =   (is_string($miscourse_id))  ?   "'{$miscourse_id}'" : $miscourse_id;

                $keyfields[$cidfield] = array('=' => $miscourse_id);
            }


            $this->fields = array();

            //get all of the fields that will be returned

            if (get_config('block_ilp', 'mis_plugin_course_byclass_registerid')) $this->fields['registerid'] = get_config('block_ilp', 'mis_plugin_course_byclass_registerid');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_registername')) $this->fields['registername'] = get_config('block_ilp', 'mis_plugin_course_byclass_registername');

            if (get_config('block_ilp', 'mis_plugin_course_byclass_period')) $this->fields['period'] = get_config('block_ilp', 'mis_plugin_course_byclass_period');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_datetime')) $this->fields['datetime'] = get_config('block_ilp', 'mis_plugin_course_byclass_datetime');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_room')) $this->fields['room'] = get_config('block_ilp', 'mis_plugin_course_byclass_room');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_starttime')) $this->fields['starttime'] = get_config('block_ilp', 'mis_plugin_course_byclass_starttime');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_endtime')) $this->fields['endtime'] = get_config('block_ilp', 'mis_plugin_course_byclass_endtime');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_tutor')) $this->fields['tutor'] = get_config('block_ilp', 'mis_plugin_course_byclass_tutor');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_markstotalfield')) $this->fields['markstotal'] = get_config('block_ilp', 'mis_plugin_course_byclass_markstotalfield');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_markspresentfield')) $this->fields['markspresent'] = get_config('block_ilp', 'mis_plugin_course_byclass_markspresentfield');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_marksabsentfield')) $this->fields['marksabsent'] = get_config('block_ilp', 'mis_plugin_course_byclass_marksabsentfield');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_marksauthabsentfield')) $this->fields['marksauthabsent'] = get_config('block_ilp', 'mis_plugin_course_byclass_marksauthabsentfield');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_markslatefield')) $this->fields['markslate'] = get_config('block_ilp', 'mis_plugin_course_byclass_markslatefield');

            if (get_config('block_ilp', 'mis_plugin_course_byclass_latexfield')) $this->fields['latex'] = get_config('block_ilp', 'mis_plugin_course_byclass_latexfield');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_notifiedfield')) $this->fields['notified'] = get_config('block_ilp', 'mis_plugin_course_byclass_notifiedfield');
            if (get_config('block_ilp', 'mis_plugin_course_byclass_placementfield')) $this->fields['placement'] = get_config('block_ilp', 'mis_plugin_course_byclass_placementfield');

            $addionalargs = array();

            $addionalargs['sort'] = (get_config('block_ilp', 'mis_plugin_course_byclass_sort')) ? get_config('block_ilp', 'mis_plugin_course_byclass_sort') :  "";


            //get the users monthly attendance data
            $this->data = $this->dbquery($table, $keyfields, $this->fields,$addionalargs,$prelimdbcalls);

            $this->normalise_data($this->data);



        }
    }

    function normalise_data($data)
    {

        $normdata = array();
        $daylist = array();


        if (!empty($data)) {
            foreach ($data as $d) {

                if (isset($this->fields['datetime'])) {
                    //convert the given date to a timestamp
                    $datetime = $d[$this->fields['datetime']];

                    $datetime = strtotime($datetime);

                    //convert thge timestamp to a 3 letter day representation
                    $day = date('D', $datetime);

                    //convert the day to a number 1-7 1- monday 7-sunday
                    //the id will be used to sort the results
                    $dayid = date('N', $datetime);


                    //check if an array position for the course exists
                    if (!isset($normdata[$dayid])) {
                        $normdata[$dayid] = array();
                    }
                }




                //should authabsent not be counted as absent? and does this vary from site to site in which case a config option is needed
                $present = $this->presents_cal($d[$this->fields['markspresent']], $d[$this->fields['marksauthabsent']]);

                //calculate the months attendance percentage
                $attendpercent = ($present / $d[$this->fields['markstotal']]) * 100;

                //remove any decimal places
                $attendpercent = number_format($attendpercent, 0);
                if (isset($this->fields['starttime'])) {
                    $timestamp = strtotime($d[$this->fields['starttime']]);
                    $start = date('G:i', $timestamp);
                }

                if (isset($this->fields['endtime'])) {
                    $timestamp = strtotime($d[$this->fields['endtime']]);
                    $end = date('G:i', $timestamp);
                }


                $tempdata = array();
                if (isset($day)) $tempdata['day'] = $day;
                if (isset($this->fields['room'])) $tempdata['room'] = $d[$this->fields['room']];
                if (isset($attendpercent)) $tempdata['attendance'] = $attendpercent;
                if (isset($start)) $tempdata['starttime'] = $start;
                if (isset($end)) $tempdata['endtime'] = $end;
                if (isset($this->fields['tutor'])) $tempdata['tutor'] = $d[$this->fields['tutor']];
                if (isset($this->fields['markstotal'])) $tempdata['markstotal'] = $d[$this->fields['markstotal']];
                if (isset($this->fields['markspresent'])) $tempdata['markspresent'] = $d[$this->fields['markspresent']];
                if (isset($this->fields['marksabsent'])) $tempdata['marksabsent'] = $d[$this->fields['marksabsent']];
                if (isset($this->fields['marksauthabsent'])) $tempdata['marksauthabsent'] = $d[$this->fields['marksauthabsent']];
                if (isset($this->fields['markslate'])) $tempdata['markslate'] = $d[$this->fields['markslate']];

                if (isset($this->fields['latex'])) $tempdata['latex'] = $d[$this->fields['latex']];
                if (isset($this->fields['notified'])) $tempdata['notified'] = $d[$this->fields['notified']];
                if (isset($this->fields['placement'])) $tempdata['placement'] = $d[$this->fields['placement']];

                //fill the course month array position with percentage for the month
                $normdata[$dayid][] = $tempdata;

            }
            // Sort by array key
            // ksort($normdata);


            $this->normdata = $normdata;
        }

    }

    private function format_background($value,$config)    {

        $configval  =   get_config('block_ilp',$config);

        if (!empty($configval)) {
            return  "<span style='color: #{$configval}'>{$value}</span>";
        } else {
            return  $value;
        }
    }

    private function presents_cal($markspresent, $authabesent)
    {

        switch (get_config('block_ilp', 'mis_plugin_course_byclass_authorised')) {

            case 1 :
                //positive
                $present = $markspresent + $authabesent;
                break;

            case 2:
                $present = $markspresent - $authabesent;
                break;

            default:
                $present = $markspresent;
        }

        return $present;
    }

    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *@return string
     */
    function tab_name()
    {
        return 'Register By Class';
    }

}





