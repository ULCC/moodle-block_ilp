<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');

require_once($CFG->dirroot . '/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');

class ilp_mis_attendance_plugin_term extends ilp_mis_attendance_plugin
{

    public $fields;
    public $termdata;
    public $courselist;


    protected $monthlist = array();

    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->termdata = false;
        $this->courselist = false;
        $this->tabletype = get_config('block_ilp', 'mis_plugin_term_tabletype');

    }

    /*
    * display the current state of $this->data
    */
    public function display()
    {

        if (!empty($this->termdata)) {

            $sixtermformat = get_config('block_ilp', 'mis_plugin_term_termformat');

            //set up the flexible table for displaying
            ob_start();
            //instantiate the ilp_ajax_table class
            $flextable = new ilp_mis_ajax_table('monthly_breakdown', true, 'ilp_mis_attendance_plugin_term');

            //setup the headers and columns with the fields that have been requested

            $headers = array();
            $columns = array();

            $headers[] = '';
            $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_term_overallheader'), array('mis_term_id' => 0));
            $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_term_term1header'), array('mis_term_id' => 1));
            $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_term_term2header'), array('mis_term_id' => 2));
            $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_term_term3header'), array('mis_term_id' => 3));

            if (!empty($sixtermformat)) {
                $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_term_term4header'), array('mis_term_id' => 4));
                $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_term_term5header'), array('mis_term_id' => 5));
                $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_term_term6header'), array('mis_term_id' => 6));
            }

            $columns[] = 'metric';
            $columns[] = 'overall';
            $columns[] = 'one';
            $columns[] = 'two';
            $columns[] = 'three';

            if (!empty($sixtermformat)) {
                $columns[] = 'four';
                $columns[] = 'five';
                $columns[] = 'six';
            }

            //define the columns in the tables
            $flextable->define_columns($columns);

            //define the headers in the tables
            $flextable->define_headers($headers);

            //we do not need the intialbars
            $flextable->initialbars(false);

            $flextable->set_attribute('class', 'flexible generaltable');

            //setup the flextable
            $flextable->setup();


            $terms = (empty($sixtermformat)) ? 4 : 7;

              
            foreach ($this->termdata as $metric) {

                $data['metric'] = $metric['name'];
                $data['overall'] = $this->percent_format( $metric['overall'] , true );//. '%';
                $data['one'] = isset($metric[1]) ? $this->percent_format( $metric[1] , true ): 0 ;//. '%';
                $data['two'] = isset($metric[2]) ? $this->percent_format( $metric[2] , true ): 0 ;//. '%';
                $data['three'] = isset($metric[3]) ? $this->percent_format( $metric[3] , true ): 0 ;//. '%';

                if (!empty($sixtermformat)) {
                    $data['four'] = isset($metric[4]) ? $this->percent_format( $metric[4] , true ): 0 ;//. '%';
                    $data['five'] = isset($metric[5]) ? $this->percent_format( $metric[5] , true ): 0 ;//. '%';
                    $data['six'] = isset($metric[6]) ? $this->percent_format( $metric[6] , true ): 0 ;//. '%';
                }
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
     * This function determines whether links should be added to the content if yes then it adds the link
     * pointing to any mis plugin that can link to this plugin
     *
     * @param string $content the content that will be displayed
     * @param array  $param    any additional paramaters that should be added
     */
    public function addlinks($content, $params = false)
    {
        global $CFG;

        $plugin_id = get_config('block_ilp', 'mis_plugin_term_linkedplugin');

        if (!empty($plugin_id)) {

            //get the
            $plugin_id = get_config('block_ilp', 'mis_plugin_term_linkedplugin');

            if (!empty($plugin_id)) {
                $plugin = $this->dbc->get_mis_plugin_by_id($plugin_id);

                //links will only be made if the plugin being linked to is enabled
                if ($plugin->status == ILP_ENABLED) {
                    $urlparams = explode('&', $_SERVER['QUERY_STRING']);
                    $newurlparams = array();
                    if (!empty($urlparams)) {
                        foreach ($urlparams as $v) {
                            if (strpos($v, 'mis_term_id') === FALSE
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


        //check if plugin is enabled

        return $content;


    }

    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_term&plugintype=mis">' . get_string('ilp_mis_attendance_plugin_term_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_attendance_plugin_term', '', $link));
    }

    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {
        global $CFG;

        $this->config_text_element($mform, 'mis_plugin_term_table', get_string('ilp_mis_attendance_plugin_term_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform,'mis_plugin_term_prelimcalls',get_string('ilp_mis_attendance_plugin_term_prelimcalls', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_prelimcallsdesc', 'block_ilp'),'');

        $this->config_text_element($mform, 'mis_plugin_term_studentidfield', get_string('ilp_mis_attendance_plugin_term_studentidfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_studentidfielddesc', 'block_ilp'), 'studentID');

        $this->config_text_element($mform, 'mis_plugin_term_term', get_string('ilp_mis_attendance_plugin_term_term', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termdesc', 'block_ilp'), 'term');

        $this->config_text_element($mform, 'mis_plugin_term_markstotalfield', get_string('ilp_mis_attendance_plugin_term_markstotal', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_markstotaldesc', 'block_ilp'), 'marksTotal');

        $this->config_text_element($mform, 'mis_plugin_term_markspresentfield', get_string('ilp_mis_attendance_plugin_term_markspresent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_markspresentdesc', 'block_ilp'), 'marksPresent');

        $this->config_text_element($mform, 'mis_plugin_term_marksabsentfield', get_string('ilp_mis_attendance_plugin_term_marksabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_marksabsentdesc', 'block_ilp'), 'marksAbsent');

        $this->config_text_element($mform, 'mis_plugin_term_marksauthabsentfield', get_string('ilp_mis_attendance_plugin_term_marksauthabsent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_marksauthabsentdesc', 'block_ilp'), 'marksAuthAbsent');

        $this->config_text_element($mform, 'mis_plugin_term_markslatefield', get_string('ilp_mis_attendance_plugin_term_markslate', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_markslatedesc', 'block_ilp'), 'marksLate');

        $this->config_text_element($mform, 'mis_plugin_term_term1header', get_string('ilp_mis_attendance_plugin_term_term1header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termone', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_term_term2header', get_string('ilp_mis_attendance_plugin_term_term2header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termtwo', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_term_term3header', get_string('ilp_mis_attendance_plugin_term_term3header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termthree', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_term_term4header', get_string('ilp_mis_attendance_plugin_term_term4header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termfour', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_term_term5header', get_string('ilp_mis_attendance_plugin_term_term5header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termfive', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_term_term6header', get_string('ilp_mis_attendance_plugin_term_term6header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termsix', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_term_overallheader', get_string('ilp_mis_attendance_plugin_term_overallheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_overall', 'block_ilp'));


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
                       $class = basename($plugin_file, ".php");
                       $pluginobj = new $class();

                       $mismisc = $this->dbc->get_mis_plugin_by_name($plugin_file);
                       $options[$mismisc->id] = $pluginobj->tab_name();
	            }
        	}
        }

        $this->config_select_element($mform, 'mis_plugin_term_linkedplugin', $options, get_string('linkedplugin', 'block_ilp'), get_string('linkedplugindesc', 'block_ilp'), '');


        $options = array(
            0 => get_string('ilp_mis_attendance_plugin_term_threeterms', 'block_ilp'),
            1 => get_string('ilp_mis_attendance_plugin_term_sixterms', 'block_ilp'),
        );

        $this->config_select_element($mform, 'mis_plugin_term_termformat', $options, get_string('ilp_mis_attendance_plugin_term_termformat', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_termformatdesc', 'block_ilp'), 9);

        $options = array(
            0 => get_string('ilp_mis_attendance_plugin_term_ignore', 'block_ilp'),
            1 => get_string('ilp_mis_attendance_plugin_term_positive', 'block_ilp'),
            2 => get_string('ilp_mis_attendance_plugin_term_negative', 'block_ilp'),
        );

        $this->config_select_element($mform, 'mis_plugin_term_authorised', $options, get_string('ilp_mis_attendance_plugin_term_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_tabledesc', 'block_ilp'), 1);

        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_term_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_term_tabletype', $options, get_string('ilp_mis_attendance_plugin_term_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_tabledesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_plugin_term_pluginstatus', $options, get_string('ilp_mis_attendance_plugin_term_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_plugin_term_pluginstatusdesc', 'block_ilp'), 0);

    }


    public static function plugin_type()
    {
        return 'overview';
    }

    static function language_strings(&$string)
    {
        $string['ilp_mis_attendance_plugin_term_pluginname'] = 'Term attendance overview';
        $string['ilp_mis_attendance_plugin_term_pluginnamesettings'] = 'Term attendance configuration';


        $string['ilp_mis_attendance_plugin_term_table'] = 'Term table';
        $string['ilp_mis_attendance_plugin_term_tabledesc'] = 'table containing overview of student attendence by course by term';

        $string['ilp_mis_attendance_plugin_term_studentidfield'] = 'Student id field';
        $string['ilp_mis_attendance_plugin_term_studentidfielddesc'] = 'The field containing the mis user id';

        $string['ilp_mis_attendance_plugin_term_term'] = 'Term field';
        $string['ilp_mis_attendance_plugin_term_termdesc'] = 'The field containing the the term the data pertain too';

        $string['ilp_mis_attendance_plugin_term_monthorderfield'] = 'Month order field';
        $string['ilp_mis_attendance_plugin_term_monthorderfielddesc'] = 'The field containing the month order';

        $string['ilp_mis_attendance_plugin_term_markstotal'] = 'Marks total field';
        $string['ilp_mis_attendance_plugin_term_markstotaldesc'] = 'The field containing marks total data';


        $string['ilp_mis_attendance_plugin_term_markspresent'] = 'marks present field';
        $string['ilp_mis_attendance_plugin_term_markspresentdesc'] = 'The field containing the marks present data';

        $string['ilp_mis_attendance_plugin_term_marksabsent'] = 'marks absent field';
        $string['ilp_mis_attendance_plugin_term_marksabsentdesc'] = 'The field containing the absents data';

        $string['ilp_mis_attendance_plugin_term_marksauthabsent'] = 'marks authabsent field';
        $string['ilp_mis_attendance_plugin_term_marksauthabsentdesc'] = 'the field containing the authorised absents data';

        $string['ilp_mis_attendance_plugin_term_markslate'] = 'marks late field';
        $string['ilp_mis_attendance_plugin_term_markslatedesc'] = 'the field containing the marks late data';

        $string['ilp_mis_attendance_plugin_term_authorised'] = 'Authorised Absents';
        $string['ilp_mis_attendance_plugin_term_authoriseddesc'] = 'What should be done with authorised absents? Positive - to add to present marks, Negative - to add to absents and ignore to not count';

        $string['ilp_mis_attendance_plugin_term_ignore'] = 'Ignore';
        $string['ilp_mis_attendance_plugin_term_positive'] = 'Positive';
        $string['ilp_mis_attendance_plugin_term_negative'] = 'Negative';

        $string['ilp_mis_attendance_plugin_term_pluginstatus'] = 'Status';
        $string['ilp_mis_attendance_plugin_term_pluginstatusdesc'] = 'is the plugin enabled or disabled';

        $string['ilp_mis_attendance_plugin_term_overall'] = 'Overall';
        $string['ilp_mis_attendance_plugin_term_termone'] = 'Term 1';
        $string['ilp_mis_attendance_plugin_term_termtwo'] = 'Term 2';
        $string['ilp_mis_attendance_plugin_term_termthree'] = 'Term 3';
        $string['ilp_mis_attendance_plugin_term_termfour'] = 'Term 4';
        $string['ilp_mis_attendance_plugin_term_termfive'] = 'Term 5';
        $string['ilp_mis_attendance_plugin_term_termsix'] = 'Term 6';

        $string['ilp_mis_attendance_plugin_term_threeterms'] = '3 Terms';
        $string['ilp_mis_attendance_plugin_term_sixterms'] = '6 Terms';

        $string['ilp_mis_attendance_plugin_term_termformat'] = 'Term Format';
        $string['ilp_mis_attendance_plugin_term_termformatdesc'] = 'How many terms are there';


        $string['ilp_mis_attendance_plugin_term_overallheader'] = 'Overall header';
        $string['ilp_mis_attendance_plugin_term_term1header'] = 'Term 1 header';
        $string['ilp_mis_attendance_plugin_term_term2header'] = 'Term 2 header';
        $string['ilp_mis_attendance_plugin_term_term3header'] = 'Term 3 header';
        $string['ilp_mis_attendance_plugin_term_term4header'] = 'Term 4 header';
        $string['ilp_mis_attendance_plugin_term_term5header'] = 'Term 5 header';
        $string['ilp_mis_attendance_plugin_term_term6header'] = 'Term 6 header';
        $string['ilp_mis_attendance_plugin_term_termheader'] = 'The header that will be used to when displaying data from this term';

        $string['ilp_mis_attendance_plugin_term_prelimcalls']						= 'Preliminary db calls';
        $string['ilp_mis_attendance_plugin_term_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';



    }


    /**
     * Retrieves user data from the mis database
     *
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data($mis_user_id, $user_id=null)
    {
        $table = get_config('block_ilp', 'mis_plugin_term_table');

        $this->mis_user_id = $mis_user_id;


        if (!empty($table)) {

            $sidfield = get_config('block_ilp', 'mis_plugin_term_studentidfield');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_plugin_term_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            //create the key that will be used in sql query
            $keyfields = array($sidfield => array('=' => $mis_user_id));

            $this->fields = array();

            //get all of the fields that will be returned
            if (get_config('block_ilp', 'mis_plugin_term_term')) $this->fields['term'] = get_config('block_ilp', 'mis_plugin_term_term');
            if (get_config('block_ilp', 'mis_plugin_term_markstotalfield')) $this->fields['markstotal'] = get_config('block_ilp', 'mis_plugin_term_markstotalfield');
            if (get_config('block_ilp', 'mis_plugin_term_markspresentfield')) $this->fields['markspresent'] = get_config('block_ilp', 'mis_plugin_term_markspresentfield');
            if (get_config('block_ilp', 'mis_plugin_term_marksabsentfield')) $this->fields['marksabsent'] = get_config('block_ilp', 'mis_plugin_term_marksabsentfield');
            if (get_config('block_ilp', 'mis_plugin_term_marksauthabsentfield')) $this->fields['marksauthabsent'] = get_config('block_ilp', 'mis_plugin_term_marksauthabsentfield');
            if (get_config('block_ilp', 'mis_plugin_term_markslatefield')) $this->fields['markslate'] = get_config('block_ilp', 'mis_plugin_term_markslatefield');

            $prelimdbcalls   =    get_config('block_ilp','mis_plugin_term_prelimcalls');

            //get the users monthly attendance data
            $this->data = $this->dbquery($table, $keyfields, $this->fields, null, $prelimdbcalls);

            $this->normalise_data($this->data);
        }
    }

    function normalise_data($data)
    {

        $termdata = array();
        if (!empty($data)) {
            foreach ($data as $d) {
                if( !$d[  $this->fields[ 'markstotal' ] ] ) continue;   //if markstotal is zero, there is no data for this term

                //get the id of the current course
                $termid = $d[$this->fields['term']];


                //check if an array position for the course exists
                if (!isset($termdata[$termid])) {
                    $termdata[$termid] = array();
                }

                //auth absent data is not required so we make must set it to 0 if not field given
                $authab = (isset($this->fields['marksauthabsent'])) ? $d[$this->fields['marksauthabsent']] : 0;


                //should authabsent not be counted as absent? and does this vary from site to site in which case a config option is needed
                $present = $this->presents_cal($d[$this->fields['markspresent']], $authab);

                //caculate the months attendance percentage
                //$attendpercent = ($present / $d[$this->fields['markstotal']]) * 100;
                $attendpercent = 100 * (1 - ( $d[ $this->fields[ 'marksabsent' ] ] / $d[$this->fields['markstotal']] ));

                //caculate the months attendance percentage
                $punctpercent = ($d[$this->fields['markslate']] / $present) * 100;
                $punctpercent = 100 - $punctpercent;
                //fill the couse month array position with percentage for the month
                if( $d[ $this->fields[ 'markstotal' ] ] ){
		                $termdata[$termid] = array(
		                    'attendance' => $attendpercent,
		                    'punctuality' => $punctpercent,
		                    'markstotal' => $d[$this->fields['markstotal']],
		                    'markspresent' => $d[$this->fields['markspresent']],
		                    'marksabsent' => $d[$this->fields['marksabsent']],
		                    'marksauthabsent' => $authab,
		                    'markslate' => $d[$this->fields['markslate']]);
                }
            }

            $presents = 0;
            $absents = 0;
            $authabsents = 0;
            $lates = 0;
            $totals=0; //Should really be singular but that breaks the pattern

            //now we have all course data nicely in an array we can work the overall totals
            foreach ($termdata as &$term) {
               foreach(array('present','absent','authabsent','late','total') as $basename)
               {
                  $idx='marks'.$basename;
                  $name=$basename.'s';
                  $$name+=isset($term[$idx]) ? $term[$idx] : 0;
               }
            }

            $present = $this->presents_cal($presents, $authabsents);
            $presentpercent = ($absents / $totals) * 100;
            $presentpercent = 100 - $presentpercent;

            //overall late percentage is calculated by geting the percentage of lates and taking
            //it away from 100
            $latepercent = 100 - (($lates / $present) * 100);
            $latepercent = $latepercent;

            $termdata['overall']['attendance'] = $presentpercent;
            $termdata['overall']['punctuality'] = $latepercent;
            $termdata['overall']['marksabsent'] = $absents;
            $termdata['overall']['marksauthabsent'] = $authabsents;
            $termdata['overall']['markspresent'] = $present;


            //in this piece of code the data is made ready to bne placed in the term table
            $keynames = array('attendance', 'punctuality');
            $newtermdata = array();
            foreach ($keynames as $key) {
                $newdata = array();
                $newdata['name'] = $key;
                foreach ($termdata as $k => $v) {
                    $newdata[$k] = number_format($v[$key], 0);
                }
                $newtermdata[] = $newdata;
            }

            $this->termdata = $newtermdata;
        } else {
            $this->termdata = false;
        }


    }

    private function presents_cal($markspresent, $authabesent)
    {

        switch (get_config('block_ilp', 'mis_plugin_term_authorised')) {

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

    function getAttendance()	{
    	//this returns the attendace data for the current record
    	return (!empty($this->data)) ? $this->termdata[0]['overall'] : 0;	
    }

    function getPunctuality()	{
    	//this returns the attendace data for the current record    	
        return (!empty($this->data)) ? $this->termdata[1]['overall'] : 0;
    }


    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return 'Term';
    }

}
