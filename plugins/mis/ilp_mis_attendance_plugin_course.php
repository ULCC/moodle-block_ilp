<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');


class ilp_mis_attendance_plugin_course extends ilp_mis_attendance_plugin
{

    public $fields;
    public $mcbdata;
    public $courselist;


    protected $monthlist = array();

    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->mcbdata = false;
        $this->courselist = false;
        $this->tabletype = get_config('block_ilp', 'mis_plugin_course_tabletype');

    }

    /*
    * display the current state of $this->data
    */
    public function display()
    {

        if (!empty($this->courselist) && !empty($this->mcbdata)) {

            //set up the flexible table for displaying
            ob_start();
            //instantiate the ilp_ajax_table class
            $flextable = new ilp_mis_ajax_table('monthly_breakdown', true, 'ilp_mis_attendance_plugin_mcb');

            //setup the headers and columns with the fields that have been requested

            $headers = array();
            $columns = array();

            $headers[] = get_string('ilp_mis_attendance_plugin_course_course', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_course_attendance', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_course_punctuality', 'block_ilp');

            $columns[] = 'course';
            $columns[] = 'attendance';
            $columns[] = 'punctuality';


            //define the columns in the tables
            $flextable->define_columns($columns);

            //define the headers in the tables
            $flextable->define_headers($headers);

            //we do not need the intialbars
            $flextable->initialbars(false);

            $flextable->set_attribute('class', 'flexible generaltable');

            //setup the flextable
            $flextable->setup();

            foreach ($this->courselist as $cid => $cname) {
                //we start the month counter from the first month
                $data['course'] = $cname;
                $data['attendance'] = $this->percent_format( $this->mcbdata[$cid]['attendance'] , true );//. '%';
                $data['punctuality'] = $this->percent_format( $this->mcbdata[$cid]['punctuality'] , true );//. '%';
                $flextable->add_data_keyed($data);
            }

            $flextable->finish_html();
            $pluginoutput = ob_get_contents();
            ob_end_clean();

            return $pluginoutput;


        } else {
            if( $msg = get_string('nodataornoconfig', 'block_ilp') ){
                echo '<div id="plugin_nodata">' . $msg . '</div>';
            }
        }


    }


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_course&plugintype=mis">' . get_string('ilp_mis_attendance_overview_plugin_course_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_plugin_course', '', $link));
    }

    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_plugin_course_table', get_string('ilp_mis_attendance_plugin_course_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform,'mis_plugin_course_prelimcalls',get_string('ilp_mis_attendance_plugin_course_prelimcalls', 'block_ilp'),get_string('ilp_mis_attendance_plugin_course_prelimcallsdesc', 'block_ilp'),'');

        $this->config_text_element($mform, 'mis_plugin_course_studentidfield', get_string('ilp_mis_attendance_plugin_course_studentidfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_studentidfielddesc', 'block_ilp'), 'studentID');

        $this->config_text_element($mform, 'mis_plugin_course_courseidfield', get_string('ilp_mis_attendance_plugin_course_course_idfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_course_idfielddesc', 'block_ilp'), 'courseID');

        $this->config_text_element($mform, 'mis_plugin_course_coursenamefield', get_string('ilp_mis_attendance_plugin_course_course_namefield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_course_namefielddesc', 'block_ilp'), 'courseName');

        $this->config_text_element($mform, 'mis_plugin_course_markstotalfield', get_string('ilp_mis_attendance_plugin_course_markstotal', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_markstotaldesc', 'block_ilp'), 'marksTotal');

        $this->config_text_element($mform, 'mis_plugin_course_markspresentfield', get_string('ilp_mis_attendance_plugin_course_markspresent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_markspresentdesc', 'block_ilp'), 'marksPresent');

        $this->config_text_element($mform, 'mis_plugin_course_marksabsentfield', get_string('ilp_mis_attendance_plugin_course_marksabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_marksabsentdesc', 'block_ilp'), 'marksAbsent');

        $this->config_text_element($mform, 'mis_plugin_course_marksauthabsentfield', get_string('ilp_mis_attendance_plugin_course_marksauthabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_marksauthabsentdesc', 'block_ilp'), 'marksAuthAbsent');

        $this->config_text_element($mform, 'mis_plugin_course_markslatefield', get_string('ilp_mis_attendance_plugin_course_markslate', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_markslatedesc', 'block_ilp'), 'marksLate');

        $options = array(
            0 => get_string('ilp_mis_attendance_plugin_course_ignore', 'block_ilp'),
            1 => get_string('ilp_mis_attendance_plugin_course_positive', 'block_ilp'),
            2 => get_string('ilp_mis_attendance_plugin_course_negative', 'block_ilp'),
        );

        $this->config_select_element($mform, 'mis_plugin_course_authorised', $options, get_string('ilp_mis_attendance_plugin_course_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_tabledesc', 'block_ilp'), 1);

        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_course_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_course_tabletype', $options, get_string('ilp_mis_attendance_plugin_course_authorised', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_authoriseddesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_plugin_course_pluginstatus', $options, get_string('ilp_mis_attendance_plugin_course_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_plugin_course_pluginstatusdesc', 'block_ilp'), 0);

    }


    public static function plugin_type()
    {
        return 'overview';
    }

    static function language_strings(&$string)
    {
        $string['ilp_mis_attendance_plugin_course_pluginname'] = 'Course Based Attendance Overview';
        $string['ilp_mis_attendance_overview_plugin_course_pluginnamesettings'] = 'Course Based Attendance Configuration';


        $string['ilp_mis_attendance_plugin_course_table'] = 'Course attendance table';
        $string['ilp_mis_attendance_plugin_course_tabledesc'] = 'table containing overview of student attendence by course by month';

        $string['ilp_mis_attendance_plugin_course_studentidfield'] = 'Student id field';
        $string['ilp_mis_attendance_plugin_course_studentidfielddesc'] = 'The field containing the mis user id';

        $string['ilp_mis_attendance_plugin_course_course_idfield'] = 'Course id field';
        $string['ilp_mis_attendance_plugin_course_course_idfielddesc'] = 'The field containing course id data';

        $string['ilp_mis_attendance_plugin_course_course_namefield'] = 'Course title field';
        $string['ilp_mis_attendance_plugin_course_course_namefielddesc'] = 'The field containing course name data';

        $string['ilp_mis_attendance_plugin_course_markstotal'] = 'Marks total field';
        $string['ilp_mis_attendance_plugin_course_markstotaldesc'] = 'The field containing marks total data';


        $string['ilp_mis_attendance_plugin_course_markspresent'] = 'marks present field';
        $string['ilp_mis_attendance_plugin_course_markspresentdesc'] = 'The field containing the marks present data';

        $string['ilp_mis_attendance_plugin_course_marksabsent'] = 'marks absent field';
        $string['ilp_mis_attendance_plugin_course_marksabsentdesc'] = 'The field containing the absents data';

        $string['ilp_mis_attendance_plugin_course_marksauthabsent'] = 'marks authabsent field';
        $string['ilp_mis_attendance_plugin_course_marksauthabsentdesc'] = 'the field containing the authorised absents data';

        $string['ilp_mis_attendance_plugin_course_markslate'] = 'marks late field';
        $string['ilp_mis_attendance_plugin_course_markslatedesc'] = 'the field containing the marks late data';

        $string['ilp_mis_attendance_plugin_course_authorised'] = 'Authorised Absents';
        $string['ilp_mis_attendance_plugin_course_authoriseddesc'] = 'What should be done with authorised absents? Positive - to add to present marks, Negative - to add to absents and ignore to not count';

        $string['ilp_mis_attendance_plugin_course_prelimcalls']						= 'Preliminary db calls';
        $string['ilp_mis_attendance_plugin_course_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';

        $string['ilp_mis_attendance_plugin_course_ignore'] = 'Ignore';
        $string['ilp_mis_attendance_plugin_course_positive'] = 'Positive';
        $string['ilp_mis_attendance_plugin_course_negative'] = 'Negative';

        $string['ilp_mis_attendance_plugin_course_pluginstatus'] = 'Status';
        $string['ilp_mis_attendance_plugin_course_pluginstatusdesc'] = 'is the plugin enabled or disabled';

        $string['ilp_mis_attendance_plugin_course_course'] = 'Course';
        $string['ilp_mis_attendance_plugin_course_attendance'] = 'Attendance';
        $string['ilp_mis_attendance_plugin_course_punctuality'] = 'Punctuality';
        $string['ilp_mis_attendance_plugin_course_grade'] = 'Grade';
        $string['ilp_mis_attendance_plugin_course_performance'] = 'Performance';

    }


    /**
     * Retrieves user data from the mis database
     *
     * @param $mis_user_id the mis id of the user whose data will be retrieved.
     */
    function set_data($mis_user_id, $user_id=null)
    {
        $table = get_config('block_ilp', 'mis_plugin_course_table');

        $this->mis_user_id = $mis_user_id;


        if (!empty($table)) {

            $sidfield = get_config('block_ilp', 'mis_plugin_course_studentidfield');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_plugin_course_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            //create the key that will be used in sql query
            $keyfields = array($sidfield => array('=' => $mis_user_id));

            $this->fields = array();

            //get all of the fields that will be returned
            if (get_config('block_ilp', 'mis_plugin_course_courseidfield')) $this->fields['courseid'] = get_config('block_ilp', 'mis_plugin_course_courseidfield');
            if (get_config('block_ilp', 'mis_plugin_course_coursenamefield')) $this->fields['coursename'] = get_config('block_ilp', 'mis_plugin_course_coursenamefield');
            if (get_config('block_ilp', 'mis_plugin_course_markstotalfield')) $this->fields['markstotal'] = get_config('block_ilp', 'mis_plugin_course_markstotalfield');
            if (get_config('block_ilp', 'mis_plugin_course_markspresentfield')) $this->fields['markspresent'] = get_config('block_ilp', 'mis_plugin_course_markspresentfield');
            if (get_config('block_ilp', 'mis_plugin_course_marksabsentfield')) $this->fields['marksabsent'] = get_config('block_ilp', 'mis_plugin_course_marksabsentfield');
            if (get_config('block_ilp', 'mis_plugin_course_marksauthabsentfield')) $this->fields['marksauthabsent'] = get_config('block_ilp', 'mis_plugin_course_marksauthabsentfield');
            if (get_config('block_ilp', 'mis_plugin_course_markslatefield')) $this->fields['markslate'] = get_config('block_ilp', 'mis_plugin_course_markslatefield');

            $prelimdbcalls   =    get_config('block_ilp','mis_plugin_course_prelimcalls');

            //get the users monthly attendance data
            $this->data = $this->dbquery($table, $keyfields, $this->fields,null,$prelimdbcalls);

            $this->normalise_data($this->data);
        }
    }

    function normalise_data($data)
    {

        $mcbdata = array();
        $courselist = array();

        if (!empty($data)) {

            foreach ($data as $d) {

                //get the id of the current course
                $courseid = $d[$this->fields['courseid']];

                //should authabsent not be counted as absent? and does this vary from site to site in which case a config option is needed
                $present = $this->presents_cal($d[$this->fields['markspresent']], $d[$this->fields['marksauthabsent']]);

                //calculate the months attendance percentage
                $monthpercent = ($present / $d[$this->fields['markstotal']]) * 100;
                //$monthpercent = 100 * (1 - ( $d[ $this->fields[ 'marksabsent' ] ] / $d[$this->fields['markstotal']] ));

                //remove any decimal places
                $monthpercent = number_format($monthpercent, 0);

                $latepercent = (1 - $d[$this->fields['markslate']] / $present) * 100;

                $latepercent = number_format($latepercent, 0);

                //fill the couse month array position with percentage for the month
                $mcbdata[$courseid] = array(
                    'attendance' => $monthpercent,
                    'latepercent' => $latepercent,
                    'punctuality' => $latepercent,
                    'markstotal' => $d[$this->fields['markstotal']],
                    'markspresent' => $d[$this->fields['markspresent']],
                    'marksabsent' => $d[$this->fields['marksabsent']],
                    'marksauthabsent' => $d[$this->fields['marksauthabsent']],
                    'markslate' => $d[$this->fields['markslate']]);

                //check if the course has been added to the courselist array
                if (!isset($courselist[$courseid])) {
                    $courselist[$courseid] = $d[$this->fields['coursename']];
                }
            }

            $this->mcbdata = $mcbdata;

            asort($courselist);

            $this->courselist = $courselist;
        }
    }


    private function presents_cal($markspresent, $authabesent)
    {

        switch (get_config('block_ilp', 'mis_plugin_course_authorised')) {

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


}





