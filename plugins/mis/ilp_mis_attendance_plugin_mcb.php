<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');

require_once($CFG->dirroot . '/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');

class ilp_mis_attendance_plugin_mcb extends ilp_mis_attendance_plugin
{

    public $fields;
    public $mcbdata;
    public $courselist;
    public $attendance;


    protected $monthlist = array();

    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->mcbdata = false;
        $this->courselist = false;
        $this->tabletype = get_config('block_ilp', 'mis_plugin_mcb_tabletype');

    }

    /*
    * display the current state of $this->data
    */
    public function display()
    {
        $this->init_bgcolours();

        if (!empty($this->courselist) && !empty($this->mcbdata)) {

            //set up the flexible table for displaying
            ob_start();
            //instantiate the ilp_ajax_table class
            $flextable = new ilp_mis_ajax_table('monthly_breakdown', true, 'ilp_mis_attendance_plugin_mcb');

            //setup the headers and columns with the fields that have been requested

            $headers = array();
            $columns = array();

            $headers[] = get_string('ilp_mis_attendance_plugin_mcb_course', 'block_ilp');
            $headers[] = get_string('ilp_mis_attendance_plugin_mcb_attendance', 'block_ilp');

            $columns[] = 'course';
            $columns[] = 'overall';

            $startmonth = get_config('block_ilp', 'mis_plugin_mcb_startmonth');
            $endmonth = get_config('block_ilp', 'mis_plugin_mcb_endmonth');

            //we start the month counter from the first month
            $month = $startmonth;

            do {
                //get a string representation of the month
                $monthstr = strtolower(date('M', strtotime("1-$month-2011")));

                //pass the lang string for the month
                $headers[] = get_string($monthstr, 'block_ilp');

                //cast the month to a int
                $columns[] = "{$month}month";

                $month++;
                if ($month >= 13) $month = 1;
            } while ($month != $endmonth + 1);

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
                $cellcontents = $this->mcbdata[$cid]['overallpercent'] . '%';
                $data['overall'] = $this->format_background_by_value( $cellcontents );

                do {

                    $precentage =  (!empty($this->mcbdata[$cid][$month])) ? round($this->mcbdata[$cid][$month]['percent'],0).'%' : '';
                    $background = $this->format_background_by_value( $precentage );
                    $data["{$month}month"] = (!empty($this->mcbdata[$cid][$month]))
                       ? $this->addlinks( $background, array('mis_period_id' => $month, 'mis_course_id' => $cid))
                       : "";    //the value when the condition is not met will fill the blank cells in the table row

                    //$data["{$month}month"] = $this->format_background_by_value( $background );
                            //? $this->addlinks($this->mcbdata[$cid][$month] . "%", array('mis_period_id' => $month))

                    $month++;
                    if ($month >= 13) $month = 1;
                } while ($month != $endmonth + 1);

                $flextable->add_data_keyed($data);
            }

            $flextable->finish_html();
            $pluginoutput = ob_get_contents();
            ob_end_clean();

            return $pluginoutput;


        } else {
            if( $msg = get_string( 'nodataornoconfig' , 'block_ilp' ) ){
                return '<div id="plugin_nodata">' . $msg . '</div>';
            }
        }
    }

    /**
     * This function determines whether links should be added to the content if yes then it adds the link
     * pointing to any mis plugin that can link to this plugin
     *
     * @param string $content the content that will be displayed
     * @param array  $param    any additional paramaters that should be added
     */
    public function addlinks($content, $params = false)
    {
        global $CFG;

        $plugin_id = get_config('block_ilp', 'mis_plugin_mcb_linkedplugin');

        if (!empty($plugin_id)) {

            //get the
            $plugin_id = get_config('block_ilp', 'mis_plugin_mcb_linkedplugin');

            if (!empty($plugin_id)) {
                $plugin = $this->dbc->get_mis_plugin_by_id($plugin_id);

                //links will only be made if the plugin being linked to is enabled
                if ($plugin->status == ILP_ENABLED) {
                    $urlparams = explode('&', $_SERVER['QUERY_STRING']);
                    $newurlparams = array();
                    if (!empty($urlparams)) {
                        foreach ($urlparams as $v) {
                            if (strpos($v, 'mis_period_id') === FALSE && strpos($v, 'mis_course_id') === FALSE
                                && strpos($v, 'tabitem') === FALSE && strpos($v, 'selectedtab') === FALSE
                            ) {
                                array_push($newurlparams, $v);
                            }
                        }
                    }

                    //add the params given by the user to the newurlparams var
                    if (!empty($params)) {
                        foreach ($params as $k => $v) {
                            array_push($newurlparams, "{$k}={$v}");
                        }
                    }

                    //TODO work out a way to do this dynamically
                    //get the id of the attendance tab
                    $atttab = $this->dbc->get_plugin_by_name('block_ilp_dash_tab', 'ilp_dashboard_mis_attendance_tab');

                    if (!empty($atttab)) {
                        //set the selected tab url param
                        array_push($newurlparams, "selectedtab={$atttab->id}");

                        //set the tabitem url param
                        array_push($newurlparams, "tabitem={$atttab->id}:$plugin_id");

                        $querystring = implode('&', $newurlparams);
                        $url = $CFG->wwwroot . "/blocks/ilp/actions/view_main.php?{$querystring}";

                        $content = "<a href='$url' >{$content}</a>";
                    }
                }
            }
        }
        return $content;
    }


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;
        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_mcb&plugintype=mis">' . get_string('ilp_mis_attendance_plugin_mcb_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('mis_plugin_mcb', '', $link));
    }

    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {
        global $CFG;

        $this->config_text_element($mform, 'mis_plugin_mcb_table', get_string('ilp_mis_attendance_plugin_mcb_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform,'mis_plugin_mcb_prelimcalls',get_string('ilp_mis_attendance_plugin_mcb_prelimcalls', 'block_ilp'),get_string('ilp_mis_attendance_plugin_mcb_prelimcallsdesc', 'block_ilp'),'');

        $this->config_text_element($mform, 'mis_plugin_mcb_studentidfield', get_string('ilp_mis_attendance_plugin_mcb_studentidfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_studentidfielddesc', 'block_ilp'), 'studentID');

        $this->config_text_element($mform, 'mis_plugin_mcb_courseidfield', get_string('ilp_mis_attendance_plugin_mcb_course_idfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_course_idfielddesc', 'block_ilp'), 'courseID');

        $this->config_text_element($mform, 'mis_plugin_mcb_coursenamefield', get_string('ilp_mis_attendance_plugin_mcb_course_namefield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_course_namefielddesc', 'block_ilp'), 'courseName');

        $this->config_text_element($mform, 'mis_plugin_mcb_monthidfield', get_string('ilp_mis_attendance_plugin_mcb_monthidfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_monthidfielddesc', 'block_ilp'), 'month');

        $this->config_text_element($mform, 'mis_plugin_mcb_monthorderfield', get_string('ilp_mis_attendance_plugin_mcb_monthorderfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_monthorderfielddesc', 'block_ilp'), 'monthOrder');

        $this->config_text_element($mform, 'mis_plugin_mcb_markstotalfield', get_string('ilp_mis_attendance_plugin_mcb_markstotal', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_markstotaldesc', 'block_ilp'), 'marksTotal');

        $this->config_text_element($mform, 'mis_plugin_mcb_markspresentfield', get_string('ilp_mis_attendance_plugin_mcb_markspresent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_markspresentdesc', 'block_ilp'), 'marksPresent');

        $this->config_text_element($mform, 'mis_plugin_mcb_marksabsentfield', get_string('ilp_mis_attendance_plugin_mcb_marksabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_marksabsentdesc', 'block_ilp'), 'marksAbsent');

        $this->config_text_element($mform, 'mis_plugin_mcb_marksauthabsentfield', get_string('ilp_mis_attendance_plugin_mcb_marksauthabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_marksauthabsentdesc', 'block_ilp'), 'marksAuthAbsent');

        $this->config_text_element($mform, 'mis_plugin_mcb_markslatefield', get_string('ilp_mis_attendance_plugin_mcb_markslate', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_markslatedesc', 'block_ilp'), 'marksLate');


        $options = array(
            1 => get_string('jan', 'block_ilp'),
            2 => get_string('feb', 'block_ilp'),
            3 => get_string('mar', 'block_ilp'),
            4 => get_string('apr', 'block_ilp'),
            5 => get_string('may', 'block_ilp'),
            6 => get_string('jun', 'block_ilp'),
            7 => get_string('jul', 'block_ilp'),
            8 => get_string('aug', 'block_ilp'),
            9 => get_string('sep', 'block_ilp'),
            10 => get_string('oct', 'block_ilp'),
            11 => get_string('nov', 'block_ilp'),
            12 => get_string('dec', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_mcb_startmonth', $options, get_string('ilp_mis_attendance_plugin_mcb_startmonth', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_startmonthdesc', 'block_ilp'), 9);

        $options = array(
            1 => get_string('jan', 'block_ilp'),
            2 => get_string('feb', 'block_ilp'),
            3 => get_string('mar', 'block_ilp'),
            4 => get_string('apr', 'block_ilp'),
            5 => get_string('may', 'block_ilp'),
            6 => get_string('jun', 'block_ilp'),
            7 => get_string('jul', 'block_ilp'),
            8 => get_string('aug', 'block_ilp'),
            9 => get_string('sep', 'block_ilp'),
            10 => get_string('oct', 'block_ilp'),
            11 => get_string('nov', 'block_ilp'),
            12 => get_string('dec', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_mcb_endmonth', $options, get_string('ilp_mis_attendance_plugin_mcb_endmonth', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_endmonthdesc', 'block_ilp'), 6);

        $options = array(
            0 => get_string('ilp_mis_attendance_plugin_mcb_ignore', 'block_ilp'),
            1 => get_string('ilp_mis_attendance_plugin_mcb_positive', 'block_ilp'),
            2 => get_string('ilp_mis_attendance_plugin_mcb_negative', 'block_ilp'),
        );

        $this->config_select_element($mform, 'mis_plugin_mcb_authorised', $options, get_string('ilp_mis_attendance_plugin_mcb_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_tabledesc', 'block_ilp'), 1);

        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_mcb_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);

        //set the plugin that term will link to if any
        $options = array(
            0 => get_string('notapplicable', 'block_ilp'),
        );


        //get all mis_plugins
        $mis_plugins = ilp_records_to_menu($this->dbc->get_mis_plugins(), 'id', 'name');
        $plugins = $CFG->dirroot . '/blocks/ilp/plugins/mis';

        foreach ($mis_plugins as $plugin_file) {
        	if (file_exists($plugins.'/'.$plugin_file.".php")) {
	            require_once($plugins . '/' . $plugin_file . ".php");

                    if ($plugin_file::plugin_type() == 'attendance') {
                       // instantiate the object
                       $pluginobj = new $plugin_file();
                       $mismisc = $this->dbc->get_mis_plugin_by_name($plugin_file);
                       $options[$mismisc->id] = $pluginobj->tab_name();
	            }
        	}
        }

        $this->config_select_element($mform, 'mis_plugin_mcb_linkedplugin', $options, get_string('linkedplugin', 'block_ilp'), get_string('linkedplugindesc', 'block_ilp'), '');


        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_mcb_tabletype', $options, get_string('ilp_mis_attendance_plugin_mcb_tabletype', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_tabledesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_plugin_mcb_pluginstatus', $options, get_string('ilp_mis_attendance_plugin_mcb_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_plugin_mcb_pluginstatusdesc', 'block_ilp'), 0);

    }


    public static function plugin_type()
    {
        return 'overview';
    }

    static function language_strings(&$string)
    {
        $string['ilp_mis_attendance_plugin_mcb_pluginname'] = 'Monthly Course Breakdown Overview';
        $string['ilp_mis_attendance_plugin_mcb_pluginnamesettings'] = 'Monthly Course Breakdown Configuration';


        $string['ilp_mis_attendance_plugin_mcb_table'] = 'Month-course table';
        $string['ilp_mis_attendance_plugin_mcb_tabledesc'] = 'table containing overview of student attendence by course by month';

        $string['ilp_mis_attendance_plugin_mcb_studentidfield'] = 'Student id field';
        $string['ilp_mis_attendance_plugin_mcb_studentidfielddesc'] = 'The field containing the mis user id';

        $string['ilp_mis_attendance_plugin_mcb_course_idfield'] = 'Course id field';
        $string['ilp_mis_attendance_plugin_mcb_course_idfielddesc'] = 'The field containing course id data';

        $string['ilp_mis_attendance_plugin_mcb_course_namefield'] = 'Course title field';
        $string['ilp_mis_attendance_plugin_mcb_course_namefielddesc'] = 'The field containing course name data';

        $string['ilp_mis_attendance_plugin_mcb_monthidfield'] = 'Month field';
        $string['ilp_mis_attendance_plugin_mcb_monthidfielddesc'] = 'The field containing the month';

        $string['ilp_mis_attendance_plugin_mcb_monthorderfield'] = 'Month order field';
        $string['ilp_mis_attendance_plugin_mcb_monthorderfielddesc'] = 'The field containing the month order';

        $string['ilp_mis_attendance_plugin_mcb_markstotal'] = 'Marks total field';
        $string['ilp_mis_attendance_plugin_mcb_markstotaldesc'] = 'The field containing marks total data';


        $string['ilp_mis_attendance_plugin_mcb_markspresent'] = 'marks present field';
        $string['ilp_mis_attendance_plugin_mcb_markspresentdesc'] = 'The field containing the marks present data';

        $string['ilp_mis_attendance_plugin_mcb_marksabsent'] = 'marks absent field';
        $string['ilp_mis_attendance_plugin_mcb_marksabsentdesc'] = 'The field containing the absents data';

        $string['ilp_mis_attendance_plugin_mcb_marksauthabsent'] = 'marks authabsent field';
        $string['ilp_mis_attendance_plugin_mcb_marksauthabsentdesc'] = 'the field containing the authorised absents data';

        $string['ilp_mis_attendance_plugin_mcb_markslate'] = 'marks late field';
        $string['ilp_mis_attendance_plugin_mcb_markslatedesc'] = 'the field containing the marks late data';

        $string['ilp_mis_attendance_plugin_mcb_authorised'] = 'Authorised Absents';
        $string['ilp_mis_attendance_plugin_mcb_authoriseddesc'] = 'What should be done with authorised absents? Positive - to add to present marks, Negative - to add to absents and ignore to not count';

        $string['ilp_mis_attendance_plugin_mcb_ignore'] = 'Ignore';
        $string['ilp_mis_attendance_plugin_mcb_positive'] = 'Positive';
        $string['ilp_mis_attendance_plugin_mcb_negative'] = 'Negative';

        $string['ilp_mis_attendance_plugin_mcb_endmonth'] = 'End month';
        $string['ilp_mis_attendance_plugin_mcb_endmonthdesc'] = 'The last month to be displayed on the monthly course breakdown table';

        $string['ilp_mis_attendance_plugin_mcb_startmonth'] = 'Start Month';
        $string['ilp_mis_attendance_plugin_mcb_startmonthdesc'] = 'The first month to be displayed on the monthly course breakdown table';


        $string['ilp_mis_attendance_plugin_mcb_pluginstatus'] = 'Status';
        $string['ilp_mis_attendance_plugin_mcb_pluginstatusdesc'] = 'is the plugin enabled or disabled';

        $string['ilp_mis_attendance_plugin_mcb_course'] = 'Course';
        $string['ilp_mis_attendance_plugin_mcb_attendance'] = 'Attendance';

        $string['ilp_mis_attendance_plugin_mcb_course'] = 'Course';
        $string['ilp_mis_attendance_plugin_mcb_attendance'] = 'Attendance';

        $string['ilp_mis_attendance_plugin_mcb_course'] = 'Course';
        $string['ilp_mis_attendance_plugin_mcb_attendance'] = 'Attendance';

        $string['ilp_mis_attendance_plugin_mcb_tabletype'] = 'Table type';
        $string['ilp_mis_attendance_plugin_mcb_tabletypedesc'] = 'what is the table type';

        $string['ilp_mis_attendance_plugin_mcb_prelimcalls']						= 'Preliminary db calls';
        $string['ilp_mis_attendance_plugin_mcb_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';

    }


    /**
     * Retrieves user data from the mis database
     *
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data($mis_user_id, $user_id=null)
    {
        $table = get_config('block_ilp', 'mis_plugin_mcb_table');

        $this->mis_user_id = $mis_user_id;

        if (!empty($table)) {

            $sidfield = get_config('block_ilp', 'mis_plugin_mcb_studentidfield');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_plugin_mcb_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            //create the key that will be used in sql query
            $keyfields = array($sidfield => array('=' => $mis_user_id));

            $this->fields = array();

            if (get_config('block_ilp', 'mis_plugin_mcb_courseidfield')) $this->fields['courseid'] = get_config('block_ilp', 'mis_plugin_mcb_courseidfield');
            if (get_config('block_ilp', 'mis_plugin_mcb_coursenamefield')) $this->fields['coursename'] = get_config('block_ilp', 'mis_plugin_mcb_coursenamefield');
            if (get_config('block_ilp', 'mis_plugin_mcb_monthidfield')) $this->fields['month'] = get_config('block_ilp', 'mis_plugin_mcb_monthidfield');
            if (get_config('block_ilp', 'mis_plugin_mcb_monthorderfield')) $this->fields['monthorder'] = get_config('block_ilp', 'mis_plugin_mcb_monthorderfield');

            if (get_config('block_ilp', 'mis_plugin_mcb_markstotalfield')) $this->fields['markstotal'] = get_config('block_ilp', 'mis_plugin_mcb_markstotalfield');
            if (get_config('block_ilp', 'mis_plugin_mcb_markspresentfield')) $this->fields['markspresent'] = get_config('block_ilp', 'mis_plugin_mcb_markspresentfield');
            if (get_config('block_ilp', 'mis_plugin_mcb_marksabsentfield')) $this->fields['marksabsent'] = get_config('block_ilp', 'mis_plugin_mcb_marksabsentfield');
            if (get_config('block_ilp', 'mis_plugin_mcb_marksauthabsentfield')) $this->fields['marksauthabsent'] = get_config('block_ilp', 'mis_plugin_mcb_marksauthabsentfield');
            if (get_config('block_ilp', 'mis_plugin_mcb_markslatefield')) $this->fields['markslate'] = get_config('block_ilp', 'mis_plugin_mcb_markslatefield');

            $prelimdbcalls   =    get_config('block_ilp','mis_plugin_mcb_prelimcalls');

//get the users monthly attendance data
            $this->data = $this->cached_dbquery($table, $keyfields, $this->fields,null,$prelimdbcalls);

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


                //check if an array position for the course exists
                if (!isset($mcbdata[$courseid])) {
                    $mcbdata[$courseid] = array();
                }

                //get the current month
                $month = $d[$this->fields['month']];

                //addtional check that month value is a number not a string
                $month = (is_string($month)) ? date('n',strtotime("1-$month-2011")) : $month;


                //check if an array position for the month exists in the course
                if (!isset($mcbdata[$courseid][$month])) {
                    $mcbdata[$courseid][$month] = array();
                }

                //should authabsent not be counted as absent? and does this vary from site to site in which case a config option is needed
                @$present = $this->presents_cal($d[$this->fields['markspresent']], $d[$this->fields['marksauthabsent']]);

                //calculate the months attendance percentage, guarding against division by zero
                if(isset($this->fields['markstotal']) and !empty($d[$this->fields['markstotal']]))
                {
                   $monthpercent = ($present / $d[$this->fields['markstotal']]) * 100;
                }
                else
                {
                   $monthpercent = 0;
                }

                //fill the couse month array position with percentage for the month
                $this->setAttendance($monthpercent);

                $mcbdata[$courseid][$month]['percent'] = $monthpercent;

                foreach(array('markstotal','markspresent',
                              'marksabsent','marksauthabsent','markslate') as $fieldname)
                {
                   if(isset($this->fields[$fieldname]))
                   {
                      $mcbdata[$courseid][$month][$fieldname]=$d[$this->fields[$fieldname]];
                   }
                }

                //check if the course has been added to the courselist array
                if (!isset($courselist[$courseid])) {
                    $courselist[$courseid] = $d[$this->fields['coursename']];
                }

                //check if the month has been added
                if (!isset($monthlist[$month])) {
                    $monthlist[$month] = $d[$this->fields['monthorder']];
                }
            }

            //now we have all course data nicely in an array we can work the overall totals
            foreach ($mcbdata as &$course) {
                $presents = 0;
                $absents = 0;
                $authabsents = 0;
                $total	=	0;

                foreach ($course as $monthdata) {
                   (isset($monthdata['markstotal']) and $total += $monthdata['markstotal']);
                   (isset($monthdata['markspresent']) and $presents += $monthdata['markspresent']);
                   (isset($monthdata['marksabsent']) and $absents += $monthdata['marksabsent']);
                   (isset($monthdata['marksauthabsent']) and $authabsents += $monthdata['marksauthabsent']);
                }

                $present = $this->presents_cal($presents, $authabsents);
                if( $total > 0 ){
                    $percent = ($present / $total) * 100;
                }
                else{
                    $percent = '--';
                }

                $course['overallpercent'] = number_format($percent, 0);
                $course['overallabsents'] = $absents;
                $course['overallauthabsents'] = $authabsents;
                $course['overallpresents'] = $present;
            }

            asort($courselist);
        }

        $this->mcbdata = $mcbdata;

        $this->courselist = $courselist;
    }

    private function presents_cal($markspresent, $authabesent)
    {

        switch (get_config('block_ilp', 'mis_plugin_mcb_authorised')) {

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
        return 'Monthly Course Breakdown';
    }

    public function setAttendance($value) {
        $this->attendance = $value;
    }

    public function getAttendance() {
        return $this->attendance;
    }


}
