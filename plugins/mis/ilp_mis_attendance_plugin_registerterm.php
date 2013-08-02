<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');


class ilp_mis_attendance_plugin_registerterm extends ilp_mis_attendance_plugin
{

    public $fields;
    public $normdata;
    public $courselist;
    public $weekoffset;
    public $numterms;

    public $terms;

    public $termonestart;
    public $termtwostart;
    public $termthreestart;
    public $termfourstart;
    public $termfivestart;
    public $termsixstart;

    public $termoneend;
    public $termtwoend;
    public $termthreeend;
    public $termfourend;
    public $termfiveend;
    public $termsixend;

    public $latecodes;
    public $absentcodes;
    public $presentcodes;
    public $noclasscodes;


    public function __construct($params = array())
    {
        parent::__construct($params);

        $this->normdata = false;
        $this->courselist = false;
        $this->tabletype = get_config('block_ilp', 'mis_plugin_registerterm_tabletype');


        //get the offset of weeks
        $this->weekoffset = 53 - date('W', get_config('block_ilp', 'mis_plugin_registerterm_termonestart'));

        //number of terms 
        $this->numterms = get_config('block_ilp', 'mis_plugin_registerterm_terms');

        
        $this->terms[] = array();

        if (!empty($this->numterms)) {
            for ($i = 0; $i < $this->numterms; $i++) {
                $s = $i + 1;
                $this->terms[$i] = array();
                $this->terms[$i]['start'] = date('W', get_config('block_ilp', "mis_plugin_registerterm_term{$s}start"));
                $this->terms[$i]['startts'] = get_config('block_ilp', "mis_plugin_registerterm_term{$s}start");

                $this->terms[$i]['end'] = date('W', get_config('block_ilp', "mis_plugin_registerterm_term{$s}end"));
                $this->terms[$i]['endts'] = get_config('block_ilp', "mis_plugin_registerterm_term{$s}end");
            }
        }

        $latecodes = get_config('block_ilp', 'mis_plugin_registerterm_late');
        $absentcodes = get_config('block_ilp', 'mis_plugin_registerterm_absent');
        $noclasscodes = get_config('block_ilp', 'mis_plugin_registerterm_noclass');
        $presentcodes = get_config('block_ilp', 'mis_plugin_registerterm_present');


        $this->latecodes = (!empty($latecodes)) ? explode(',', $latecodes) : array('');
        $this->absentcodes = (!empty($absentcodes)) ? explode(',', $absentcodes) : array('');
        $this->noclasscodes = (!empty($noclasscodes)) ? explode(',', $noclasscodes) : array('');
        $this->presentcodes = (!empty($presentcodes)) ? explode(',', $presentcodes) : array('');
    }

    /**
     * takes a real week number and returns the week in the academic year
     *
     * @param int $week
     * @param int $offset this should always be the week number of the school year start week
     */

    function academic_week($week, $offset)
    {
        return ($week >= $offset) ? ($week - $offset) + 1 : $week + $offset;
    }


    function weekno($date)
    {
        global $USER;

        $realweek = date("W", strtotime($date));

        return ($realweek >= $this->terms[0]['start']) ? ($realweek - $this->terms[0]['start']) + 1
                : ($this->weekoffset + $realweek);
    }


    function coursetime($timefield)
    {
        return date('G:i', strtotime($timefield));
    }

    function courseday($date)
    {
        return date("D", strtotime($date));
    }

    /*
    * display the current state of $this->data
    */
    public function display()
    {
        global $CFG, $PARSER;

        if (!empty($this->data)) {

            $summarydata = $this->summary_data($this->data);

            $sixtermformat = get_config('block_ilp', 'mis_plugin_term_termformat');

            //set up the flexible table for displaying
            ob_start();
            //instantiate the ilp_ajax_table class
            $flextable = new ilp_mis_ajax_table('monthly_breakdown', true, 'ilp_mis_attendance_plugin_term');

            //setup the headers and columns with the fields that have been requested

            $headers = array();
            $columns = array();

            $headers[] = '';

            $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_registerterm_overallheader'), array('mis_term_id' => 0));
            $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_registerterm_term1header'), array('mis_term_id' => 1));
            $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_registerterm_term2header'), array('mis_term_id' => 2));
            $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_registerterm_term3header'), array('mis_term_id' => 3));

            if (!empty($sixtermformat)) {
                $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_registerterm_term4header'), array('mis_term_id' => 4));
                $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_registerterm_term5header'), array('mis_term_id' => 5));
                $headers[] = $this->addlinks(get_config('block_ilp', 'mis_plugin_registerterm_term6header'), array('mis_term_id' => 6));
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

            $data['metric'] = get_string('ilp_mis_attendance_plugin_registerterm_disp_attendance', 'block_ilp');
            $data['overall'] = isset( $summarydata['att_prec'][0] ) ? $this->percent_format( $summarydata['att_prec'][0], true ): 0 ;
            $data['one'] = isset( $summarydata['att_prec'][1] ) ? $this->percent_format( $summarydata['att_prec'][1], true ): 0 ;
            $data['two'] = isset( $summarydata['att_prec'][2] ) ? $this->percent_format( $summarydata['att_prec'][2], true ): 0 ;
            $data['three'] = isset( $summarydata['att_prec'][3] ) ? $this->percent_format( $summarydata['att_prec'][3], true ): 0 ;

            if (!empty($sixtermformat)) {
                $data['four'] = isset( $summarydata['att_prec'][4] ) ? $this->percent_format( $summarydata['att_prec'][4], true ): 0 ;
                $data['five'] = isset( $summarydata['att_prec'][5] ) ? $this->percent_format( $summarydata['att_prec'][5], true ): 0 ;
                $data['six'] = isset( $summarydata['att_prec'][6] ) ? $this->percent_format( $summarydata['att_prec'][6], true ): 0 ;
            }

            $flextable->add_data_keyed($data);

            $data['metric'] = get_string('ilp_mis_attendance_plugin_registerterm_disp_punctuality', 'block_ilp');
            $data['overall'] = isset( $summarydata['pun_perc'][0] ) ? $this->percent_format( $summarydata['pun_perc'][0], true ): 0 ;
            $data['one'] = isset( $summarydata['pun_perc'][1] ) ? $this->percent_format( $summarydata['pun_perc'][1], true ): 0 ;
            $data['two'] = isset( $summarydata['pun_perc'][2] ) ? $this->percent_format( $summarydata['pun_perc'][2], true ): 0 ;
            $data['three'] = isset( $summarydata['pun_perc'][3] ) ? $this->percent_format( $summarydata['pun_perc'][3], true ): 0 ;

            if (!empty($sixtermformat)) {
                $data['four'] = isset( $summarydata['pun_perc'][4] ) ? $this->percent_format( $summarydata['pun_perc'][4], true ): 0 ;
                $data['five'] = isset( $summarydata['pun_perc'][5] ) ? $this->percent_format( $summarydata['pun_perc'][5], true ): 0 ;
                $data['six'] = isset( $summarydata['pun_perc'][6] ) ? $this->percent_format( $summarydata['pun_perc'][6], true ): 0 ;
            }

            $flextable->add_data_keyed($data);

            $flextable->finish_html();
            $output = ob_get_contents();
            ob_end_clean();

        } else {
            if( $msg = get_string('nodataornoconfig', 'block_ilp') ){
                $output = '<div id="plugin_nodata">*******************' . $msg . '</div>';
            }
        }

        return $output;

    }

    function percentage($number, $total)
    {
        return (!empty($total)) ? round($number / $total * 100, 0) : 0;
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

        $plugin_id = get_config('block_ilp', 'mis_plugin_registerterm_linkedplugin');

        if (!empty($plugin_id)) {

            //get the
            $plugin_id = get_config('block_ilp', 'mis_plugin_registerterm_linkedplugin');

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


    function normalise_date($date)
    {
        $date = str_replace('/', '-', $date);
        return $date;
    }


function summary_data($data, $term = 0)
    {

        global $CFG;

        $cidfield = get_config('block_ilp', 'mis_plugin_registerterm_courseid');
        $cdatefield = get_config('block_ilp', 'mis_plugin_registerterm_datetime');
        $markfield = get_config('block_ilp', 'mis_plugin_registerterm_mark');
        $timefield = get_config('block_ilp', 'mis_plugin_registerterm_datetime');
        $cnamefield = get_config('block_ilp', 'mis_plugin_registerterm_coursename');

        if (!empty($term)) {
            $yearstart = $this->terms[0]['start'];
            $termstart = $this->terms[$term - 1]['start'];
            $termend = $this->terms[$term - 1]['end'];
        } else {
            $yearstart = $this->terms[0]['start'];
            $termstart = $this->terms[0]['start'];
            $termend = $this->terms[$this->numterms - 1]['end'];
        }


        $total = array(0, 0, 0, 0, 0, 0);
        $absent = array(0, 0, 0, 0, 0, 0);
        $present = array(0, 0, 0, 0, 0, 0);
        $late = array(0, 0, 0, 0, 0, 0);
        
        $academicstart = $this->academic_week($this->terms[0]['start'], $yearstart);
	    $academicend = $this->academic_week($this->terms[$this->numterms - 1]['end'], $yearstart);

        foreach ($data as $mark) {
        	
        	$marktimestamp = strtotime($this->normalise_date($mark[$cdatefield]));
				
	        $mark['Week_No'] = $this->academic_week(date('W', $marktimestamp), $yearstart);
        	

	        
        	//we need to make sure that the mark is within the academic year
        	if ($mark['Week_No'] >= $academicstart && $mark['Week_No'] <= $academicend) {
	            if (!in_array($mark[$markfield], $this->noclasscodes) && strlen($mark[$markfield]) > 0) {
	                $total[0]++;
	            }
	
	            if (in_array($mark[$markfield], $this->presentcodes)) {
	                $present[0]++;
	            }
	
	            if (in_array($mark[$markfield], $this->absentcodes)) {
	                $absent[0]++;
	            }
	
	            if (in_array($mark[$markfield], $this->latecodes)) {
	                $late[0]++;
	            }
	

	
	            for ($i = 1; $i <= $this->numterms; $i++) {
	
	                //these variables define the academic weeks of $termstart and $termend
	                $termstart = $this->academic_week($this->terms[$i - 1]['start'], $yearstart);
	                $termend = $this->academic_week($this->terms[$i - 1]['end'], $yearstart);
	
	                if ($mark['Week_No'] >= $termstart && $mark['Week_No'] <= $termend) {
	
	                    if (!in_array($mark[$markfield], $this->noclasscodes) && strlen($mark[$markfield]) > 0) {
	                        $total[$i]++;
	                    }
	
	                    if (in_array($mark[$markfield], $this->presentcodes)) {
	                        $present[$i]++;
	                    }
	
	                    if (in_array($mark[$markfield], $this->absentcodes)) {
	                        $absent[$i]++;
	                    }
	
	                    if (in_array($mark[$markfield], $this->latecodes)) {
	                        $late[$i]++;
	                    }
	                }
	            }
        	}
        }

        for ($i = 0; $i <= $this->numterms; $i++) {
        	if (isset($total[$i])) {
	            if ($total[$i] > 0) {
	                @$att_perc[$i] = round(($present[$i] / $total[$i]) * 100, 0) . '%';
	                @$pun_perc[$i] = round((($total[$i] - $late[$i]) / $total[$i]) * 100, 0) . '%';
	            } else {
	                @$att_perc[$i] = '';
	                @$pun_perc[$i] = '';
	            }
	
	            if ($total[$i] > 0) {
	                if ($att_perc[$i] > 85) {
	                    $att_class[$i] = 'green';
	                } elseif ($att_perc[$i] >= 75 && $att_perc[$i] <= 85) {
	                    $att_class[$i] = 'amber';
	                } elseif ($att_perc[$i] < 75) {
	                    $att_class[$i] = 'red';
	                }
	
	                if ($pun_perc[$i] > 85) {
	                    $pun_class[$i] = 'green';
	                } elseif ($pun_perc[$i] >= 75 && $pun_perc[$i] <= 85) {
	                    $pun_class[$i] = 'amber';
	                } elseif ($pun_perc[$i] < 75) {
	                    $pun_class[$i] = 'red';
	                }
	            } else {
	                $att_class[$i] = 'none';
	                $pun_class[$i] = 'none';
	            }
        	}
        }
 
        return array('total' => $total, 'present' => $present, 'late' => $late, 'absent' => $absent, 'att_prec' => $att_perc, 'pun_perc' => $pun_perc, 'att_class' => $att_class, 'pun_class' => $pun_class);
    }


    /**
     * Retrieves user data from the mis database
     *
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data($mis_user_id,$user_id=null)
    {

        $table = get_config('block_ilp', 'mis_plugin_registerterm_table');


        $this->mis_user_id = $mis_user_id;

        if (!empty($table)) {
            $sidfield = get_config('block_ilp', 'mis_plugin_registerterm_studentidfield');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_plugin_registerterm_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            $keyfields = array();

            $useyearfilter = get_config('block_ilp', 'mis_plugin_registerterm_yearfilter');
            if (!empty($useyearfilter)) {

                $yearfilterfield = get_config('block_ilp', 'mis_plugin_registerterm_yearfilter_field');
                $yearfilteryear = get_config('block_ilp', 'mis_plugin_registerterm_yearfilter_year');

                $keyfields[$yearfilterfield] = array('=' => $yearfilteryear);
            }

            //create the key that will be used in sql query
            $keyfields[$sidfield] = array('=' => $mis_user_id);

            $this->fields = array();

            //get all of the fields that will be returned
            if (get_config('block_ilp', 'mis_plugin_registerterm_courseid')) $this->fields['courseid'] = get_config('block_ilp', 'mis_plugin_registerterm_courseid');
            if (get_config('block_ilp', 'mis_plugin_registerterm_coursename')) $this->fields['coursename'] = get_config('block_ilp', 'mis_plugin_registerterm_coursename');
            if (get_config('block_ilp', 'mis_plugin_registerterm_registerid')) $this->fields['registerid'] = get_config('block_ilp', 'mis_plugin_registerterm_registerid');
            if (get_config('block_ilp', 'mis_plugin_registerterm_registername')) $this->fields['registername'] = get_config('block_ilp', 'mis_plugin_registerterm_registername');
            if (get_config('block_ilp', 'mis_plugin_registerterm_datetime')) $this->fields['datetime'] = get_config('block_ilp', 'mis_plugin_registerterm_datetime');
            if (get_config('block_ilp', 'mis_plugin_registerterm_starttime')) $this->fields['starttime'] = get_config('block_ilp', 'mis_plugin_registerterm_starttime');
            if (get_config('block_ilp', 'mis_plugin_registerterm_endtime')) $this->fields['endtime'] = get_config('block_ilp', 'mis_plugin_registerterm_endtime');
            if (get_config('block_ilp', 'mis_plugin_registerterm_mark')) $this->fields['mark'] = get_config('block_ilp', 'mis_plugin_registerterm_mark');

            $prelimdbcalls   =    get_config('block_ilp','mis_learner_causeforconcern_prelimcalls');

            //get the users monthly attendance data
            $this->data = $this->dbquery($table, $keyfields, $this->fields,null,$prelimdbcalls);
        }

    }


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_registerterm&plugintype=mis">' . get_string('ilp_mis_attendance_plugin_registerterm_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_attendance_plugin_registerterm', '', $link));
    }


    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {
        global $CFG;

        $this->config_text_element($mform, 'mis_plugin_registerterm_table', get_string('ilp_mis_attendance_plugin_registerterm_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform,'mis_plugin_registerterm_prelimcalls',get_string('ilp_mis_attendance_plugin_registerterm_prelimcalls', 'block_ilp'),get_string('ilp_mis_attendance_plugin_registerterm_prelimcallsdesc', 'block_ilp'),'');

        $this->config_text_element($mform, 'mis_plugin_registerterm_studentidfield', get_string('ilp_mis_attendance_plugin_registerterm_studentidfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_studentidfielddesc', 'block_ilp'), 'studentID');

        $this->config_text_element($mform, 'mis_plugin_registerterm_courseid', get_string('ilp_mis_attendance_plugin_registerterm_courseid', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_courseiddesc', 'block_ilp'), 'courseID');

        $this->config_text_element($mform, 'mis_plugin_registerterm_registerid', get_string('ilp_mis_attendance_plugin_registerterm_registerid', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_registeriddesc', 'block_ilp'), 'registerID');

        $this->config_text_element($mform, 'mis_plugin_registerterm_registerName', get_string('ilp_mis_attendance_plugin_registerterm_registername', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_registernamedesc', 'block_ilp'), 'registerName');

        $this->config_text_element($mform, 'mis_plugin_registerterm_datetime', get_string('ilp_mis_attendance_plugin_registerterm_datetime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_datetimedesc', 'block_ilp'), 'datetime');

        $this->config_text_element($mform, 'mis_plugin_registerterm_starttime', get_string('ilp_mis_attendance_plugin_registerterm_starttime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_starttimedesc', 'block_ilp'), 'starttime');

        $this->config_text_element($mform, 'mis_plugin_registerterm_endtime', get_string('ilp_mis_attendance_plugin_registerterm_endtime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_endtimedesc', 'block_ilp'), 'endtime');

        $this->config_text_element($mform, 'mis_plugin_registerterm_coursename', get_string('ilp_mis_attendance_plugin_registerterm_coursename', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_coursenamedesc', 'block_ilp'), 'coursename');

        $this->config_text_element($mform, 'mis_plugin_registerterm_mark', get_string('ilp_mis_attendance_plugin_registerterm_mark', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_markdesc', 'block_ilp'), 'mark');

        $this->config_text_element($mform, 'mis_plugin_registerterm_present', get_string('ilp_mis_attendance_plugin_registerterm_present', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_presentdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_registerterm_presentcolour', get_string('ilp_mis_attendance_plugin_registerterm_presentcolour', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_presentcolourdesc', 'block_ilp'), 'green');

        $this->config_text_element($mform, 'mis_plugin_registerterm_absent', get_string('ilp_mis_attendance_plugin_registerterm_absent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_absentdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_registerterm_absentcolour', get_string('ilp_mis_attendance_plugin_registerterm_absentcolour', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_absentcolourdesc', 'block_ilp'), 'red');

        $this->config_text_element($mform, 'mis_plugin_registerterm_late', get_string('ilp_mis_attendance_plugin_registerterm_late', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_latedesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_registerterm_latecolour', get_string('ilp_mis_attendance_plugin_registerterm_latecolour', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_latecolourdesc', 'block_ilp'), 'amber');

        $this->config_text_element($mform, 'mis_plugin_registerterm_noclass', get_string('ilp_mis_attendance_plugin_registerterm_noclass', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_noclassdesc', 'block_ilp'), '');

        $options = array(
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
        );

        $this->config_select_element($mform, 'mis_plugin_registerterm_terms', $options, get_string('ilp_mis_attendance_plugin_registerterm_terms', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termsdesc', 'block_ilp'), 3);

        $this->config_date_element($mform, 'mis_plugin_registerterm_term1start', get_string('ilp_mis_attendance_plugin_registerterm_termonestart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term1end', get_string('ilp_mis_attendance_plugin_registerterm_termoneend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term2start', get_string('ilp_mis_attendance_plugin_registerterm_termtwostart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term2end', get_string('ilp_mis_attendance_plugin_registerterm_termtwoend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term3start', get_string('ilp_mis_attendance_plugin_registerterm_termthreestart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term3end', get_string('ilp_mis_attendance_plugin_registerterm_termthreeend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term4start', get_string('ilp_mis_attendance_plugin_registerterm_termfourstart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term4end', get_string('ilp_mis_attendance_plugin_registerterm_termfourend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term5start', get_string('ilp_mis_attendance_plugin_registerterm_termfivestart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term5end', get_string('ilp_mis_attendance_plugin_registerterm_termfiveend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term6start', get_string('ilp_mis_attendance_plugin_registerterm_termsixstart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_registerterm_term6end', get_string('ilp_mis_attendance_plugin_registerterm_termsixend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termenddesc', 'block_ilp'), '');


        $this->config_text_element($mform, 'mis_plugin_registerterm_term1header', get_string('ilp_mis_attendance_plugin_registerterm_term1header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termone', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_registerterm_term2header', get_string('ilp_mis_attendance_plugin_registerterm_term2header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termtwo', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_registerterm_term3header', get_string('ilp_mis_attendance_plugin_registerterm_term3header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termthree', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_registerterm_term4header', get_string('ilp_mis_attendance_plugin_registerterm_term4header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termfour', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_registerterm_term5header', get_string('ilp_mis_attendance_plugin_registerterm_term5header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termfive', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_registerterm_term6header', get_string('ilp_mis_attendance_plugin_registerterm_term6header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termsix', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_registerterm_overallheader', get_string('ilp_mis_attendance_plugin_registerterm_overallheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_overall', 'block_ilp'));

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

        $this->config_select_element($mform, 'mis_plugin_registerterm_linkedplugin', $options, get_string('linkedplugin', 'block_ilp'), get_string('linkedplugindesc', 'block_ilp'), '');


        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_registerterm_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_registerterm_tabletype', $options, get_string('ilp_mis_attendance_plugin_registerterm_tabletype', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_tabletypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_DISABLED => get_string('disabled', 'block_ilp'),
            ILP_ENABLED => get_string('enabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_registerterm_yearfilter', $options, get_string('ilp_mis_attendance_plugin_registerterm_yearfilter', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_yearfilterdesc', 'block_ilp'), 0);

        $this->config_text_element($mform, 'mis_plugin_registerterm_yearfilter_field', get_string('ilp_mis_attendance_plugin_registerterm_yearfilter_field', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_yearfilter_fielddesc', 'block_ilp'), 'year');

        $this->config_text_element($mform, 'mis_plugin_registerterm_yearfilter_year', get_string('ilp_mis_attendance_plugin_registerterm_yearfilter_year', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_yearfilter_yeardesc', 'block_ilp'), date('Y'));


        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_plugin_registerterm_pluginstatus', $options, get_string('ilp_mis_attendance_plugin_registerterm_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_plugin_registerterm_pluginstatusdesc', 'block_ilp'), 0);

    }


    public static function plugin_type()
    {
        return 'overview';
    }

    static function language_strings(&$string)
    {

        $string['ilp_mis_attendance_plugin_registerterm_pluginname'] = 'Register Term Overview';
        $string['ilp_mis_attendance_plugin_registerterm_pluginnamesettings'] = 'Register Term Attendance Overview Configuration';


        $string['ilp_mis_attendance_plugin_registerterm_table'] = 'Register attendance table';
        $string['ilp_mis_attendance_plugin_registerterm_tabledesc'] = 'table containing register data';

        $string['ilp_mis_attendance_plugin_registerterm_studentidfield'] = 'Student id field';
        $string['ilp_mis_attendance_plugin_registerterm_studentidfielddesc'] = 'The field containing the mis user id';

        $string['ilp_mis_attendance_plugin_registerterm_courseid'] = 'Course id field';
        $string['ilp_mis_attendance_plugin_registerterm_courseiddesc'] = 'The field containing course id data';

        $string['ilp_mis_attendance_plugin_registerterm_registerid'] = 'Register ID field';
        $string['ilp_mis_attendance_plugin_registerterm_registeriddesc'] = 'The field containing register id data';

        $string['ilp_mis_attendance_plugin_registerterm_registername'] = 'Register Name field';
        $string['ilp_mis_attendance_plugin_registerterm_registernamedesc'] = 'The field containing register name data';

        $string['ilp_mis_attendance_plugin_registerterm_datetime'] = 'Date time field';
        $string['ilp_mis_attendance_plugin_registerterm_datetimedesc'] = 'The field containing date time data';

        $string['ilp_mis_attendance_plugin_registerterm_starttime'] = 'Course start time field';
        $string['ilp_mis_attendance_plugin_registerterm_starttimedesc'] = 'The field containing course start time data';

        $string['ilp_mis_attendance_plugin_registerterm_endtime'] = 'Course end time field';
        $string['ilp_mis_attendance_plugin_registerterm_endtimedesc'] = 'The field containing course end time data';

        $string['ilp_mis_attendance_plugin_registerterm_coursename'] = 'Course Name field';
        $string['ilp_mis_attendance_plugin_registerterm_coursenamedesc'] = 'The field containing course name data';

        $string['ilp_mis_attendance_plugin_registerterm_mark'] = 'Mark field';
        $string['ilp_mis_attendance_plugin_registerterm_markdesc'] = 'The field containing mark data';

        $string['ilp_mis_attendance_plugin_registerterm_present'] = 'Present codes';
        $string['ilp_mis_attendance_plugin_registerterm_presentdesc'] = 'enter a comma separated list of present codes';

        $string['ilp_mis_attendance_plugin_registerterm_presentcolour'] = 'Present code colour';
        $string['ilp_mis_attendance_plugin_registerterm_presentcolourdesc'] = 'The colour that present marks will be displayed in on the grid';

        $string['ilp_mis_attendance_plugin_registerterm_absent'] = 'Absent codes';
        $string['ilp_mis_attendance_plugin_registerterm_absentdesc'] = 'enter a comma separated list of absent codes';

        $string['ilp_mis_attendance_plugin_registerterm_absentcolour'] = 'Absent code colour';
        $string['ilp_mis_attendance_plugin_registerterm_absentcolourdesc'] = 'The colour that absent marks will be displayed in on the grid';

        $string['ilp_mis_attendance_plugin_registerterm_late'] = 'Late codes';
        $string['ilp_mis_attendance_plugin_registerterm_latedesc'] = 'enter a comma separated list of late codes';

        $string['ilp_mis_attendance_plugin_registerterm_latecolour'] = 'Late code colour';
        $string['ilp_mis_attendance_plugin_registerterm_latecolourdesc'] = 'The colour that late marks will be displayed in on the grid';

        $string['ilp_mis_attendance_plugin_registerterm_noclass'] = 'No class codes';
        $string['ilp_mis_attendance_plugin_registerterm_noclassdesc'] = 'enter a comma separated list of no class codes';

        $string['ilp_mis_attendance_plugin_registerterm_terms'] = 'Numbner of terms';
        $string['ilp_mis_attendance_plugin_registerterm_termsdesc'] = 'How many terms does a year have';

        $string['ilp_mis_attendance_plugin_registerterm_termonestart'] = 'Term 1 start';
        $string['ilp_mis_attendance_plugin_registerterm_termoneend'] = 'Term 1 end';

        $string['ilp_mis_attendance_plugin_registerterm_termtwostart'] = 'Term 2 start';
        $string['ilp_mis_attendance_plugin_registerterm_termtwoend'] = 'Term 2 end';

        $string['ilp_mis_attendance_plugin_registerterm_termthreestart'] = 'Term 3 start';
        $string['ilp_mis_attendance_plugin_registerterm_termthreeend'] = 'Term 3 end';

        $string['ilp_mis_attendance_plugin_registerterm_termfourstart'] = 'Term 4 start';
        $string['ilp_mis_attendance_plugin_registerterm_termfourend'] = 'Term 4 end';

        $string['ilp_mis_attendance_plugin_registerterm_termfivestart'] = 'Term 5 start';
        $string['ilp_mis_attendance_plugin_registerterm_termfiveend'] = 'Term 5 end';

        $string['ilp_mis_attendance_plugin_registerterm_termsixstart'] = 'Term 6 start';
        $string['ilp_mis_attendance_plugin_registerterm_termsixend'] = 'Term 6 end';

        $string['ilp_mis_attendance_plugin_registerterm_termstartdesc'] = 'Enter the terms start date';
        $string['ilp_mis_attendance_plugin_registerterm_termenddesc'] = 'Enter the terms end date';

        $string['ilp_mis_attendance_plugin_registerterm_termstartdesc'] = 'Enter the terms start date';
        $string['ilp_mis_attendance_plugin_registerterm_termenddesc'] = 'Enter the terms end date';

        $string['ilp_mis_attendance_plugin_registerterm_tabletype'] = 'Table type';
        $string['ilp_mis_attendance_plugin_registerterm_tabletypedesc'] = 'what is the table type';

        $string['ilp_mis_attendance_plugin_registerterm_yearfilter'] = 'Year filter';
        $string['ilp_mis_attendance_plugin_registerterm_yearfilterdesc'] = 'Is a year filter used when selecting data from the MIS';

        $string['ilp_mis_attendance_plugin_registerterm_yearfilter_field'] = 'Year filter field';
        $string['ilp_mis_attendance_plugin_registerterm_yearfilter_fielddesc'] = 'If a MIS year filter is being used enter the field that will be filter on. (if stored procedure and field not needed leave field as year)';

        $string['ilp_mis_attendance_plugin_registerterm_yearfilter_year'] = 'Year filter date';
        $string['ilp_mis_attendance_plugin_registerterm_yearfilter_yeardesc'] = 'The date that will be filtered on';

        $string['ilp_mis_attendance_plugin_registerterm_ignore'] = 'Ignore';
        $string['ilp_mis_attendance_plugin_registerterm_positive'] = 'Positive';
        $string['ilp_mis_attendance_plugin_registerterm_negative'] = 'Negative';

        $string['ilp_mis_attendance_plugin_registerterm_months'] = 'Months';
        $string['ilp_mis_attendance_plugin_registerterm_terms'] = 'Terms';

        $string['ilp_mis_attendance_plugin_registerterm_pluginstatus'] = 'Status';
        $string['ilp_mis_attendance_plugin_registerterm_pluginstatusdesc'] = 'is the plugin enabled or disabled';

        $string['ilp_mis_attendance_plugin_registerterm_disp_day'] = 'Day';
        $string['ilp_mis_attendance_plugin_registerterm_disp_date'] = 'Date';


        $string['ilp_mis_attendance_plugin_registerterm_disp_att'] = 'Att';
        $string['ilp_mis_attendance_plugin_registerterm_disp_late'] = 'Late';
        $string['ilp_mis_attendance_plugin_registerterm_disp_time'] = 'Time';
        $string['ilp_mis_attendance_plugin_registerterm_disp_class'] = 'Class';
        $string['ilp_mis_attendance_plugin_registerterm_disp_week'] = 'Week';
        $string['ilp_mis_attendance_plugin_registerterm_disp_possible'] = 'Possible';
        $string['ilp_mis_attendance_plugin_registerterm_disp_attendance'] = 'Attendance';
        $string['ilp_mis_attendance_plugin_registerterm_disp_absent'] = 'Absent';
        $string['ilp_mis_attendance_plugin_registerterm_disp_present'] = 'Present';
        $string['ilp_mis_attendance_plugin_registerterm_disp_punctuality'] = 'Punctuality';


        $string['ilp_mis_attendance_plugin_registerterm_overall'] = 'All';
        $string['ilp_mis_attendance_plugin_registerterm_termone'] = 'Autumn';
        $string['ilp_mis_attendance_plugin_registerterm_termtwo'] = 'Spring';
        $string['ilp_mis_attendance_plugin_registerterm_termthree'] = 'Summer';
        $string['ilp_mis_attendance_plugin_registerterm_termfour'] = 'Term 4';
        $string['ilp_mis_attendance_plugin_registerterm_termfive'] = 'Term 5';
        $string['ilp_mis_attendance_plugin_registerterm_termsix'] = 'Term 6';


        $string['ilp_mis_attendance_plugin_registerterm_overallheader'] = 'Overall header';
        $string['ilp_mis_attendance_plugin_registerterm_term1header'] = 'Term 1 header';
        $string['ilp_mis_attendance_plugin_registerterm_term2header'] = 'Term 2 header';
        $string['ilp_mis_attendance_plugin_registerterm_term3header'] = 'Term 3 header';
        $string['ilp_mis_attendance_plugin_registerterm_term4header'] = 'Term 4 header';
        $string['ilp_mis_attendance_plugin_registerterm_term5header'] = 'Term 5 header';
        $string['ilp_mis_attendance_plugin_registerterm_term6header'] = 'Term 6 header';
        $string['ilp_mis_attendance_plugin_registerterm_termheader'] = 'The header that will be used to when displaying data from this term';

        $string['ilp_mis_attendance_plugin_registerterm_prelimcalls']						= 'Preliminary db calls';
        $string['ilp_mis_attendance_plugin_registerterm_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';
    }


    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return 'Register Term';
    }

    function getAttendance()
    {
    	$attendance 	=	0;

    	if (!empty($this->data)) {
            $summarydata = $this->summary_data($this->data);
            $attendance	= $summarydata['att_prec'][0];
    	} 
    	
        return $attendance;
    }

    function getPunctuality()
    {
    	$punctuality	=	0;
    	
    	if (!empty($this->data)) {
            $summarydata 	= $this->summary_data($this->data);
            $punctuality	= $summarydata['pun_perc'][0];
    	} 
    	
 		return $punctuality;   	
    }


}
