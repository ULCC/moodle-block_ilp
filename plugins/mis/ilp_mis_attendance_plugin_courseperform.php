<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');


class ilp_mis_attendance_plugin_courseperform extends ilp_mis_attendance_plugin
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
        $this->tabletype = get_config('block_ilp', 'mis_plugin_courseperform_tabletype');

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
            $flextable = new ilp_mis_ajax_table('monthly_breakdown', true, 'ilp_mis_attendance_overview_plugin_mcb');

            //setup the headers and columns with the fields that have been requested

            $headers = array();
            $columns = array();

            $headers[] = get_string('ilp_mis_attendance_plugin_courseperform_course', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_courseperform_attendance', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_courseperform_punctuality', 'block_ilp');
			// additional two columns 
			// if there is no data in effortInClass and effortAtHome or fields in plugins config are empty than this two  columns are not being displayed
			if (get_config('block_ilp', 'mis_plugin_courseperform_effortinclass')!=='') {
            	$headers[] = get_string('ilp_mis_attendance_plugin_courseperform_effortinclass', 'block_ilp');
            	$headers[] = get_string('ilp_mis_attendance_plugin_courseperform_effortathome', 'block_ilp');
			}
            $headers[] = get_string('ilp_mis_attendance_plugin_courseperform_grade', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_courseperform_performance', 'block_ilp');
            
            
            $columns[] = 'course';
            $columns[] = 'attendance';
            $columns[] = 'punctuality';

            if (get_config('block_ilp', 'mis_plugin_courseperform_effortinclass')!=='') {
            	$columns[] = 'effortinclass';
            	$columns[] = 'effortathome';
            }	
            $columns[] = 'grade';
            $columns[] = 'performance';
            


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
                $month = $startmonth;
                $data['course'] = $cname;
                $data['attendance'] = $this->percent_format( $this->mcbdata[$cid]['attendance'] , true );//. '%';
                $data['punctuality'] = $this->percent_format( $this->mcbdata[$cid]['punctuality'] , true );//. '%';
                $data['effortinclass'] = $this->percent_format( $this->mcbdata[$cid]['effortinclass'] );
                $data['effortathome'] = $this->percent_format( $this->mcbdata[$cid]['effortathome'] );
                $data['grade'] = $this->mcbdata[$cid]['grade'];
                $data['performance'] = $this->mcbdata[$cid]['performance'];
                $flextable->add_data_keyed($data);
            }

            $flextable->finish_html();
            $pluginoutput = ob_get_contents();
            ob_end_clean();

            return $pluginoutput;


        } else {
            if( $msg = get_string( 'nodataornoconfig' , 'block_ilp' ) ){
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

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_courseperform&plugintype=mis">' . get_string('ilp_mis_attendance_overview_plugin_courseperform_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_plugin_courseperform', '', $link));
    }

    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_plugin_courseperform_table', get_string('ilp_mis_attendance_plugin_courseperform_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform,'mis_plugin_courseperform_prelimcalls',get_string('ilp_mis_attendance_plugin_courseperform_prelimcalls', 'block_ilp'),get_string('ilp_mis_attendance_plugin_courseperform_prelimcallsdesc', 'block_ilp'),'');

        $this->config_text_element($mform, 'mis_plugin_courseperform_studentidfield', get_string('ilp_mis_attendance_plugin_courseperform_studentidfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_studentidfielddesc', 'block_ilp'), 'studentID');

        $this->config_text_element($mform, 'mis_plugin_courseperform_courseidfield', get_string('ilp_mis_attendance_plugin_courseperform_course_idfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_course_idfielddesc', 'block_ilp'), 'courseID');

        $this->config_text_element($mform, 'mis_plugin_courseperform_coursenamefield', get_string('ilp_mis_attendance_plugin_courseperform_course_namefield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_course_namefielddesc', 'block_ilp'), 'courseName');

        $this->config_text_element($mform, 'mis_plugin_courseperform_markstotalfield', get_string('ilp_mis_attendance_plugin_courseperform_markstotal', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_markstotaldesc', 'block_ilp'), 'marksTotal');

        $this->config_text_element($mform, 'mis_plugin_courseperform_markspresentfield', get_string('ilp_mis_attendance_plugin_courseperform_markspresent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_markspresentdesc', 'block_ilp'), 'marksPresent');

        $this->config_text_element($mform, 'mis_plugin_courseperform_marksabsentfield', get_string('ilp_mis_attendance_plugin_courseperform_marksabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_marksabsentdesc', 'block_ilp'), 'marksAbsent');

        $this->config_text_element($mform, 'mis_plugin_courseperform_marksauthabsentfield', get_string('ilp_mis_attendance_plugin_courseperform_marksauthabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_marksauthabsentdesc', 'block_ilp'), 'marksAuthAbsent');

        $this->config_text_element($mform, 'mis_plugin_courseperform_markslatefield', get_string('ilp_mis_attendance_plugin_courseperform_markslate', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_markslatedesc', 'block_ilp'), 'marksLate');

        $this->config_text_element($mform, 'mis_plugin_courseperform_grade', get_string('ilp_mis_attendance_plugin_courseperform_grade', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_gradedesc', 'block_ilp'), 'Target Grade');

        $this->config_text_element($mform, 'mis_plugin_courseperform_performance', get_string('ilp_mis_attendance_plugin_courseperform_performance', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_performancedesc', 'block_ilp'), 'performance');
        
        $this->config_text_element($mform, 'mis_plugin_courseperform_effortinclass', get_string('ilp_mis_attendance_plugin_courseperform_effortinclass', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_effortinclass', 'block_ilp'), 'EffortInClass');
        
        $this->config_text_element($mform, 'mis_plugin_courseperform_effortathome', get_string('ilp_mis_attendance_plugin_courseperform_effortathome', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_effortathome', 'block_ilp'), 'EffortAtHome');

        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_courseperform_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


        $options = array(
            0 => get_string('ilp_mis_attendance_plugin_courseperform_ignore', 'block_ilp'),
            1 => get_string('ilp_mis_attendance_plugin_courseperform_positive', 'block_ilp'),
            2 => get_string('ilp_mis_attendance_plugin_courseperform_negative', 'block_ilp'),
        );

        $this->config_select_element($mform, 'mis_plugin_courseperform_authorised', $options, get_string('ilp_mis_attendance_plugin_courseperform_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_tabledesc', 'block_ilp'), 1);

        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_courseperform_tabletype', $options, get_string('ilp_mis_attendance_plugin_courseperform_authorised', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_authoriseddesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_plugin_courseperform_pluginstatus', $options, get_string('ilp_mis_attendance_plugin_courseperform_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_plugin_courseperform_pluginstatusdesc', 'block_ilp'), 0);

    }


    public static function plugin_type()
    {
        return 'overview';
    }

    static function language_strings(&$string)
    {
        $string['ilp_mis_attendance_plugin_courseperform_pluginname'] = 'Course Based Attendance with Performance Overview';
        $string['ilp_mis_attendance_overview_plugin_courseperform_pluginnamesettings'] = 'Course Based Attendance with Performance Configuration';


        $string['ilp_mis_attendance_plugin_courseperform_table'] = 'Course attendance table';
        $string['ilp_mis_attendance_plugin_courseperform_tabledesc'] = 'table containing overview of student attendence by course by month';

        $string['ilp_mis_attendance_plugin_courseperform_studentidfield'] = 'Student id field';
        $string['ilp_mis_attendance_plugin_courseperform_studentidfielddesc'] = 'The field containing the mis user id';

        $string['ilp_mis_attendance_plugin_courseperform_course_idfield'] = 'Course id field';
        $string['ilp_mis_attendance_plugin_courseperform_course_idfielddesc'] = 'The field containing course id data';

        $string['ilp_mis_attendance_plugin_courseperform_course_namefield'] = 'Course title field';
        $string['ilp_mis_attendance_plugin_courseperform_course_namefielddesc'] = 'The field containing course name data';

        $string['ilp_mis_attendance_plugin_courseperform_gradeidfield'] = 'Grade field';
        $string['ilp_mis_attendance_plugin_courseperform_gradedesc'] = 'The field containing the grade data';

        $string['ilp_mis_attendance_plugin_courseperform_performance'] = 'Performance field';
        $string['ilp_mis_attendance_plugin_courseperform_performancedesc'] = 'The field containing the performance data';


        $string['ilp_mis_attendance_plugin_courseperform_markstotal'] = 'Marks total field';
        $string['ilp_mis_attendance_plugin_courseperform_markstotaldesc'] = 'The field containing marks total data';


        $string['ilp_mis_attendance_plugin_courseperform_markspresent'] = 'marks present field';
        $string['ilp_mis_attendance_plugin_courseperform_markspresentdesc'] = 'The field containing the marks present data';

        $string['ilp_mis_attendance_plugin_courseperform_marksabsent'] = 'marks absent field';
        $string['ilp_mis_attendance_plugin_courseperform_marksabsentdesc'] = 'The field containing the absents data';

        $string['ilp_mis_attendance_plugin_courseperform_marksauthabsent'] = 'marks authabsent field';
        $string['ilp_mis_attendance_plugin_courseperform_marksauthabsentdesc'] = 'the field containing the authorised absents data';

        $string['ilp_mis_attendance_plugin_courseperform_markslate'] = 'marks late field';
        $string['ilp_mis_attendance_plugin_courseperform_markslatedesc'] = 'the field containing the marks late data';

        $string['ilp_mis_attendance_plugin_courseperform_authorised'] = 'Authorised Absents';
        $string['ilp_mis_attendance_plugin_courseperform_authoriseddesc'] = 'What should be done with authorised absents? Positive - to add to present marks, Negative - to add to absents and ignore to not count';

        $string['ilp_mis_attendance_plugin_courseperform_ignore'] = 'Ignore';
        $string['ilp_mis_attendance_plugin_courseperform_positive'] = 'Positive';
        $string['ilp_mis_attendance_plugin_courseperform_negative'] = 'Negative';

        $string['ilp_mis_attendance_plugin_courseperform_pluginstatus'] = 'Status';
        $string['ilp_mis_attendance_plugin_courseperform_pluginstatusdesc'] = 'is the plugin enabled or disabled';

        $string['ilp_mis_attendance_plugin_courseperform_course'] = 'Course';
        $string['ilp_mis_attendance_plugin_courseperform_attendance'] = 'Attendance';
        $string['ilp_mis_attendance_plugin_courseperform_punctuality'] = 'Punctuality';
        $string['ilp_mis_attendance_plugin_courseperform_grade'] = 'Target Grade';
        $string['ilp_mis_attendance_plugin_courseperform_performance'] = 'Performance';

		// for additional columns        
        $string['ilp_mis_attendance_plugin_courseperform_effortinclass'] = 'Effort in class';
        $string['ilp_mis_attendance_plugin_courseperform_effortinclassdesc'] = 'the field containing the effort in class (Default: empty)';
        
        $string['ilp_mis_attendance_plugin_courseperform_effortathome'] = 'Effort at home';
        $string['ilp_mis_attendance_plugin_courseperform_effortathomedesc'] = 'the field containing the effort at home (Default: empty)';

        $string['ilp_mis_attendance_plugin_courseperform_prelimcalls']						= 'Preliminary db calls';
        $string['ilp_mis_attendance_plugin_courseperform_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';


    }


    /**
     * Retrieves user data from the mis database
     *
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data($mis_user_id,$user_id=null)
    {
        $table = get_config('block_ilp', 'mis_plugin_courseperform_table');

        $this->mis_user_id = $mis_user_id;


        if (!empty($table)) {

            $sidfield = get_config('block_ilp', 'mis_plugin_courseperform_studentidfield');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_plugin_courseperform_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            //create the key that will be used in sql query
            $keyfields = array($sidfield => array('=' => $mis_user_id));

            $this->fields = array();

            //get all of the fields that will be returned
            if (get_config('block_ilp', 'mis_plugin_courseperform_courseidfield')) $this->fields['courseid'] = get_config('block_ilp', 'mis_plugin_courseperform_courseidfield');
            if (get_config('block_ilp', 'mis_plugin_courseperform_coursenamefield')) $this->fields['coursename'] = get_config('block_ilp', 'mis_plugin_courseperform_coursenamefield');
            if (get_config('block_ilp', 'mis_plugin_courseperform_grade')) $this->fields['grade'] = get_config('block_ilp', 'mis_plugin_courseperform_grade');
            if (get_config('block_ilp', 'mis_plugin_courseperform_performance')) $this->fields['performance'] = get_config('block_ilp', 'mis_plugin_courseperform_performance');

            if (get_config('block_ilp', 'mis_plugin_courseperform_markstotalfield')) $this->fields['markstotal'] = get_config('block_ilp', 'mis_plugin_courseperform_markstotalfield');
            if (get_config('block_ilp', 'mis_plugin_courseperform_markspresentfield')) $this->fields['markspresent'] = get_config('block_ilp', 'mis_plugin_courseperform_markspresentfield');
            if (get_config('block_ilp', 'mis_plugin_courseperform_marksabsentfield')) $this->fields['marksabsent'] = get_config('block_ilp', 'mis_plugin_courseperform_marksabsentfield');
            if (get_config('block_ilp', 'mis_plugin_courseperform_marksauthabsentfield')) $this->fields['marksauthabsent'] = get_config('block_ilp', 'mis_plugin_courseperform_marksauthabsentfield');
            if (get_config('block_ilp', 'mis_plugin_courseperform_markslatefield')) $this->fields['markslate'] = get_config('block_ilp', 'mis_plugin_courseperform_markslatefield');
            
            if (get_config('block_ilp', 'mis_plugin_courseperform_effortinclass')) $this->fields['effortinclass'] = get_config('block_ilp', 'mis_plugin_courseperform_effortinclass');
            if (get_config('block_ilp', 'mis_plugin_courseperform_effortathome')) $this->fields['effortathome'] = get_config('block_ilp', 'mis_plugin_courseperform_effortathome');

            $prelimdbcalls   =    get_config('block_ilp','mis_plugin_courseperform_prelimcalls');

            //get the users monthly attendance data
            $this->data = $this->dbquery($table, $keyfields, $this->fields,null,$prelimdbcalls);

            $this->normalise_data($this->data);
        }
    }

    function normalise_data($data)
    {

        $mcbdata = array();
        $courselist = array();

        foreach ($data as $d) {

            //get the id of the current course
            $courseid = $d[$this->fields['courseid']];


            //check if an array position for the course exists
            if (!isset($mcbdata[$courseid])) {
                $mcbdata[$courseid] = array();
            }

            //check if an array position for the month exists in the course
            if (!isset($mcbdata[$courseid][$month])) {
                $mcbdata[$courseid][$month] = array();
            }

            //should authabsent not be counted as absent? and does this vary from site to site in which case a config option is needed
            $present = $this->presents_cal($d[$this->fields['markspresent']], $d[$this->fields['marksauthabsent']]);

            //calculate the months attendance percentage
            $monthpercent = ($present / $d[$this->fields['markstotal']]) * 100;

            //remove any decimal places
            $monthpercent = number_format($monthpercent, 0);

            $latepercent = (1 - $d[$this->fields['markslate']] / $present) * 100;

            $latepercent = number_format($latepercent, 0);

            if($latepercent == 0) {
                $latepercent = 100;
            }

            //fill the couse month array position with percentage for the month
            $mcbdata[$courseid] = array(
                'attendance' => $monthpercent,
                'latepercent' => $latepercent,
                'grade' => $d[$this->fields['grade']],
                'performance' => $d[$this->fields['performance']],
                'punctuality' => $latepercent,
                'markstotal' => $d[$this->fields['markstotal']],
                'markspresent' => $d[$this->fields['markspresent']],
                'marksabsent' => $d[$this->fields['marksabsent']],
                'marksauthabsent' => $d[$this->fields['marksauthabsent']],
                'markslate' => $d[$this->fields['markslate']],
            
				//if fielsd not defined, than they will not be displayed
            	'effortinclass' => $d[$this->fields['effortinclass']] ? $d[$this->fields['effortinclass']] : '',
            	'effortathome' => $d[$this->fields['effortathome']] ? $d[$this->fields['effortathome']] : '');
            

            //check if the course has been added to the courselist array
            if (!isset($courselist[$courseid])) {
                $courselist[$courseid] = $d[$this->fields['coursename']];
            }
        }

        $this->mcbdata = $mcbdata;

        asort($courselist);

        $this->courselist = $courselist;

    }


    private function presents_cal($markspresent, $authabesent)
    {

        switch (get_config('block_ilp', 'mis_plugin_courseperform_authorised')) {

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
     *
     */
    function tab_name()
    {
        return 'Course Based Attendance';
    }


}





