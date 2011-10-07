<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');


class ilp_mis_attendance_plugin_register extends ilp_mis_attendance_plugin
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
        $this->tabletype = get_config('block_ilp', 'mis_plugin_register_tabletype');


        //get the offset of weeks
        $this->weekoffset = 53 - date('W', get_config('block_ilp', 'mis_plugin_register_termonestart'));

        //number of terms 
        $this->numterms = get_config('block_ilp', 'mis_plugin_register_terms');

		
        $this->terms[] = array();

        if (!empty($this->numterms)) {
            for ($i = 0; $i < $this->numterms; $i++) {
                $s = $i + 1;
                $this->terms[$i] = array();
                $this->terms[$i]['start'] = date('W', get_config('block_ilp', "mis_plugin_register_term{$s}start"));
                $this->terms[$i]['startts'] = get_config('block_ilp', "mis_plugin_register_term{$s}start");

                $this->terms[$i]['end'] = date('W', get_config('block_ilp', "mis_plugin_register_term{$s}end"));
                $this->terms[$i]['endts'] = get_config('block_ilp', "mis_plugin_register_term{$s}end");
            }
        }

        $latecodes = get_config('block_ilp', 'mis_plugin_register_late');
        $absentcodes = get_config('block_ilp', 'mis_plugin_register_absent');
        $noclasscodes = get_config('block_ilp', 'mis_plugin_register_noclass');
        $presentcodes = get_config('block_ilp', 'mis_plugin_register_present');


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

    function academic_week($week, $offset)	{
		global $USER;
		
		if ($week >= $offset)  {
			return ($week - $offset) + 1;
		} else {
			$yearrollover = 52 - $offset;
			return $yearrollover + $week+1;
		}
    }


    function weekno($date)	{
        global $USER;

		$date	=	str_replace('/','-',$date);
        $realweek = date("W", strtotime($date));

        return ($realweek >= $this->terms[0]['start']) ? ($realweek - $this->terms[0]['start']) + 1
                : ($this->weekoffset + $realweek);
    }


    function coursetime($timefield)	{
        return date('G:i', strtotime($timefield));
    }

    function courseday($date)	{
		global $USER;
		
		$date	=	str_replace('/','-',$date);
	
        return date("D", strtotime($date));
    }

    /*
    * display the current state of $this->data
    */
    public function display()	{
        global $CFG, $PARSER;

        //if set get the id of the report to be edited
        $term = $PARSER->optional_param('mis_term_id', 0, PARAM_INT);

        if (!empty($this->data)) {

            $summarydata = $this->summary_data($this->data, $term);

            if (!empty($term)) {
                $displayterms = array($term);
            } else {
                for ($i = 0; $i < $this->numterms; $i++) {
                    $displayterms[] = $i + 1;
                }
            }

            ob_start();
            $this->term_attendance($this->data, $term);
            $grid = ob_get_contents();
            ob_end_clean();

            ob_start();
            require_once($CFG->dirroot . '/blocks/ilp/classes/dashboard/mis/ilp_mis_attendance_plugin_register.html');
            $output = ob_get_contents();
            ob_end_clean();
        } else {
            if( $msg = get_string('nodataornoconfig', 'block_ilp') ){
                $output = '<div id="plugin_nodata">' . $msg . '</div>';
            }
        }

        return $output;

    }


    function normalise_date($date)
    {
        $date = str_replace('/', '-', $date);
        return $date;
    }


    function summary_data($data, $term = 0)
    {

        global $CFG;

        $cidfield = get_config('block_ilp', 'mis_plugin_register_courseid');
        $cdatefield = get_config('block_ilp', 'mis_plugin_register_datetime');
        $markfield = get_config('block_ilp', 'mis_plugin_register_mark');
        $timefield = get_config('block_ilp', 'mis_plugin_register_datetime');
        $cnamefield = get_config('block_ilp', 'mis_plugin_register_coursename');

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
        	

	        
        	//we need to make sure that the mar is within the academic year
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

        return array('total' => $total, 'present' => $present, 'late' => $late, 'absent' => $absent, 'att_prec' => $att_perc, 'pun_perc' => $pun_perc, 'att_class' => $att_class, 'pun_class' => $pun_class);
    }


	function getMonday($datets)	{
		while (date('l', $datets) != 'Monday') {
			$datets = strtotime('-1 day', $datets);
		}
		return date('d/m', $datets);
	}
	
	
    /**
     *
     * This function creates the register grid that is displayed
     * @param recordset $data recordset containing student register data
     * @param int          $term the term that will be displayed if not supplied all terms are shown
     * @param unknown_type $course
     */
    function term_attendance($data, $term = 0, $course = false)
    {

        global $CFG, $USER;

        $cidfield = get_config('block_ilp', 'mis_plugin_register_courseid');
        $cdatefield = get_config('block_ilp', 'mis_plugin_register_datetime');
        $markfield = get_config('block_ilp', 'mis_plugin_register_mark');
        $timefield = get_config('block_ilp', 'mis_plugin_register_starttime');
        $timefield = (!empty($timefield)) ? $timefield : 0;
        $cnamefield = get_config('block_ilp', 'mis_plugin_register_coursename');

        $startdate = $this->terms[0]['startts'];

        if (!empty($term)) {
            $yearstart = $this->terms[0]['start'];
            $termstart = $this->terms[$term - 1]['start'];
            $termend = $this->terms[$term - 1]['end'];
        } else {
            $yearstart 	= $this->terms[0]['start'];
            $termstart 	= $this->terms[0]['start'];
            $termend 	= $this->terms[$this->numterms - 1]['end'];
        }

        //these variables define the academic weeks of $termstart and $termend
        $academicstart = $this->academic_week($termstart, $yearstart);
        $academicend = $this->academic_week($termend, $yearstart);


        $weekofseconds = 604800;

        //assign the tables column names
        $tablecolumns = array('class', 'late', 'att', 'date', 'time');

        //set the displayed headers
        $tableheaders = array(get_string('ilp_mis_attendance_plugin_register_disp_class', 'block_ilp'),
                              get_string('ilp_mis_attendance_plugin_register_disp_late', 'block_ilp'),
                              get_string('ilp_mis_attendance_plugin_register_disp_att', 'block_ilp'),
                              get_string('ilp_mis_attendance_plugin_register_disp_day', 'block_ilp'),
                              get_string('ilp_mis_attendance_plugin_register_disp_time', 'block_ilp')
        );

        
        //assign the week column names and set the week display header
        for ($z = $academicstart; $z < $academicend + 1; $z++) {
            $tablecolumns[] = 'week' . $z;
            
            $tableheaders[] = $z;
        }

        require_once($CFG->dirroot . '/blocks/ilp/classes/tables/ilp_tablelib.class.php');


        $table = new ilp_flexible_table('user-attendence', true, 'ilp_mis_attendance_plugin_register');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot . '/blocks/ilp/attendence.php?&id=1');

        $table->sortable(false);
        $table->collapsible(false);
        $table->initialbars(false);

        $table->set_attribute('cellpadding', '6');
        $table->set_attribute('id', 'ilp-attendance-grid');
        $table->set_attribute('class', 'generalbox');
        $table->set_attribute('width', '100%');
        $table->set_attribute('align', 'left');
        $table->set_attribute('font-size', '0.85em');
        $table->setup();

        //set the row displaying the week dates
        $dates = array('', '', '', '', '<div class="termweeks">Week:</div>');

        for ($z = $academicstart; $z < $academicend + 1; $z++) {
			$weekindays		=	  ($z - 1) * 7;
			$timestampweek 	= strtotime("+{$weekindays} days",$startdate);
			
			//$timestampweek = $weekofseconds * ($z - 1);
            //$dates[] = '<div class="termweeks">' . $this->getMonday($startdate + $timestampweek) . '</div>';
			$dates[] = '<div class="termweeks">' . $this->getMonday($timestampweek) . '</div>';
        }

        $table->add_data($dates);

        $registers = array();

        //we will now take the attendance data and construct an array separating class on day and time basis
        foreach ($data as $att) {
            $tempday = $this->courseday($att[$cdatefield]);
            $temptime = (isset($att[$timefield])) ? $this->coursetime($att[$timefield]) : 'N/A';

            $registers[$att[$cidfield]][$tempday][$temptime][] = $att;
        }

        $total = array();


        foreach (array_keys($registers) as $groupKey) {
		
            $class = array('', '', '', '', '');
            $termsweeks = array();
            for ($z = $academicstart; $z < $academicend + 1; $z++) {
                $base_class[] = '';
                $base_termsweeks[] = $z;
            }

            $class = $base_class;
            $termsweeks = $base_termsweeks;

            $total[$groupKey] = array();
            foreach ($registers[$groupKey] as $cday => $timeslot) {

                foreach ($timeslot as $ctime => $classtime) {
				
                    $total[$groupKey][$cday][$ctime] = array(array(0, 0, 0, 0), array(0, 0, 0, 0), array(0, 0, 0, 0), array(0, 0, 0, 0));

                    foreach ($classtime as $item) {

                       //$item['Week_No'] = $this->weekno($item[$cdatefield]);
					   $marktimestamp = strtotime($this->normalise_date($item[$cdatefield]));

					   $item['Week_No'] = $this->academic_week(date('W', $marktimestamp), $yearstart);
						
                        if (in_array($item['Week_No'], $termsweeks)) {

                            if (!in_array($item[$markfield], $this->noclasscodes) && strlen($mark[$markfield]) > 0) {
                                $total[$groupKey][$cday][$ctime][0][0]++;
                            }
                            if (in_array($item[$markfield], $this->presentcodes)) {
                                $total[$groupKey][$cday][$ctime][1][0]++;
                            }
                            if (in_array($item[$markfield], $this->absentcodes)) {
                                $total[$groupKey][$cday][$ctime][2][0]++;
                            }

                            if (in_array($item[$markfield], $this->latecodes)) {
                                $total[$groupKey][$cday][$ctime][3][0]++;
                            }

                            $class['class'] = $item[$cidfield] . ': ' . $item[$cnamefield];

                            $startdate = explode('-', $item[$cdatefield]);
                            $class['date'] = $this->courseday($item[$cdatefield]);
                            $class['time'] = $ctime;
                            $att_class = 'amber';


                            if (in_array($item[$markfield], $this->presentcodes)) {
                                $att_class = get_config('block_ilp', 'mis_plugin_register_presentcolour');
                            }

                            if (in_array($item[$markfield], $this->absentcodes)) {
                                $att_class = get_config('block_ilp', 'mis_plugin_register_absentcolour');
                            }

                            if (in_array($item[$markfield], $this->latecodes)) {
                                $att_class = get_config('block_ilp', 'mis_plugin_register_latecolour');
                            }

                            $class['week' . $item['Week_No']] = '<span style="display:block; text-align:center; background-color:' . $att_class . '" title="">' . $item[$markfield] . '</span>';
                        }
                    }

                    //calculate the attendance and late averages
                    if ($total[$groupKey][$cday][$ctime][0][$term] != 0) {
                        $class['late'] = round(($total[$groupKey][$cday][$ctime][3][$term] / $total[$groupKey][$cday][$ctime][0][$term]) * 100, 0) . '%';
                        $class['att'] = round(($total[$groupKey][$cday][$ctime][1][$term] / $total[$groupKey][$cday][$ctime][0][$term]) * 100, 0) . '%';
                    }

                    $classdata[] = $class;

                    //reset $class and $termsweeks to their original states
                    $class = $base_class;
                    $termsweeks = $base_termsweeks;
                }
            }
			
        }

        foreach ($classdata as $class) {
            $table->add_data_keyed($class);
        }

        $table->print_html();
    }


    /**
     * Retrieves user data from the mis database
     *
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data($mis_user_id)
    {

        $table = get_config('block_ilp', 'mis_plugin_register_table');

        $this->mis_user_id = $mis_user_id;

        if (!empty($table)) {
            $sidfield = get_config('block_ilp', 'mis_plugin_register_studentidfield');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_plugin_register_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            $keyfields = array();

            $useyearfilter = get_config('block_ilp', 'mis_plugin_register_yearfilter');
            if (!empty($useyearfilter)) {

                $yearfilterfield = get_config('block_ilp', 'mis_plugin_register_yearfilter_field');
                $yearfilteryear = get_config('block_ilp', 'mis_plugin_register_yearfilter_year');

                $keyfields[$yearfilterfield] = array('=' => $yearfilteryear);
            }

            //create the key that will be used in sql query
            $keyfields[$sidfield] = array('=' => $mis_user_id);

            $this->fields = array();

            //get all of the fields that will be returned
            if (get_config('block_ilp', 'mis_plugin_register_courseid')) $this->fields['courseid'] = get_config('block_ilp', 'mis_plugin_register_courseid');
            if (get_config('block_ilp', 'mis_plugin_register_coursename')) $this->fields['coursename'] = get_config('block_ilp', 'mis_plugin_register_coursename');
            if (get_config('block_ilp', 'mis_plugin_register_registerid')) $this->fields['registerid'] = get_config('block_ilp', 'mis_plugin_register_registerid');
            if (get_config('block_ilp', 'mis_plugin_register_registername')) $this->fields['registername'] = get_config('block_ilp', 'mis_plugin_register_registername');
            if (get_config('block_ilp', 'mis_plugin_register_datetime')) $this->fields['datetime'] = get_config('block_ilp', 'mis_plugin_register_datetime');
            if (get_config('block_ilp', 'mis_plugin_register_starttime')) $this->fields['starttime'] = get_config('block_ilp', 'mis_plugin_register_starttime');
            if (get_config('block_ilp', 'mis_plugin_register_endtime')) $this->fields['endtime'] = get_config('block_ilp', 'mis_plugin_register_endtime');
            if (get_config('block_ilp', 'mis_plugin_register_mark')) $this->fields['mark'] = get_config('block_ilp', 'mis_plugin_register_mark');

            //get the users monthly attendance data
            $this->data = $this->dbquery($table, $keyfields, $this->fields);

        }

    }


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_register&plugintype=mis">' . get_string('ilp_mis_attendance_plugin_register_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_attendance_plugin_register', '', $link));
    }


    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_plugin_register_table', get_string('ilp_mis_attendance_plugin_register_table', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_register_studentidfield', get_string('ilp_mis_attendance_plugin_register_studentidfield', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_studentidfielddesc', 'block_ilp'), 'studentID');

        $this->config_text_element($mform, 'mis_plugin_register_courseid', get_string('ilp_mis_attendance_plugin_register_courseid', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_courseiddesc', 'block_ilp'), 'courseID');

        $this->config_text_element($mform, 'mis_plugin_register_registerid', get_string('ilp_mis_attendance_plugin_register_registerid', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_registeriddesc', 'block_ilp'), 'registerID');

        $this->config_text_element($mform, 'mis_plugin_register_registerName', get_string('ilp_mis_attendance_plugin_register_registername', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_registernamedesc', 'block_ilp'), 'registerName');

        $this->config_text_element($mform, 'mis_plugin_register_datetime', get_string('ilp_mis_attendance_plugin_register_datetime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_datetimedesc', 'block_ilp'), 'datetime');

        $this->config_text_element($mform, 'mis_plugin_register_starttime', get_string('ilp_mis_attendance_plugin_register_starttime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_starttimedesc', 'block_ilp'), 'starttime');

        $this->config_text_element($mform, 'mis_plugin_register_endtime', get_string('ilp_mis_attendance_plugin_register_endtime', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_endtimedesc', 'block_ilp'), 'endtime');

        $this->config_text_element($mform, 'mis_plugin_register_coursename', get_string('ilp_mis_attendance_plugin_register_coursename', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_coursenamedesc', 'block_ilp'), 'coursename');

        $this->config_text_element($mform, 'mis_plugin_register_mark', get_string('ilp_mis_attendance_plugin_register_mark', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_markdesc', 'block_ilp'), 'mark');

        $this->config_text_element($mform, 'mis_plugin_register_present', get_string('ilp_mis_attendance_plugin_register_present', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_presentdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_register_presentcolour', get_string('ilp_mis_attendance_plugin_register_presentcolour', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_presentcolourdesc', 'block_ilp'), 'green');

        $this->config_text_element($mform, 'mis_plugin_register_absent', get_string('ilp_mis_attendance_plugin_register_absent', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_absentdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_register_absentcolour', get_string('ilp_mis_attendance_plugin_register_absentcolour', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_absentcolourdesc', 'block_ilp'), 'red');

        $this->config_text_element($mform, 'mis_plugin_register_late', get_string('ilp_mis_attendance_plugin_register_late', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_latedesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_register_latecolour', get_string('ilp_mis_attendance_plugin_register_latecolour', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_latecolourdesc', 'block_ilp'), 'amber');

        $this->config_text_element($mform, 'mis_plugin_register_noclass', get_string('ilp_mis_attendance_plugin_register_noclass', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_noclassdesc', 'block_ilp'), '');

        $options = array(
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
            5 => 5,
            6 => 6,
        );

        $this->config_select_element($mform, 'mis_plugin_register_terms', $options, get_string('ilp_mis_attendance_plugin_register_terms', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termsdesc', 'block_ilp'), 3);

        $this->config_date_element($mform, 'mis_plugin_register_term1start', get_string('ilp_mis_attendance_plugin_register_termonestart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term1end', get_string('ilp_mis_attendance_plugin_register_termoneend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term2start', get_string('ilp_mis_attendance_plugin_register_termtwostart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term2end', get_string('ilp_mis_attendance_plugin_register_termtwoend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term3start', get_string('ilp_mis_attendance_plugin_register_termthreestart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term3end', get_string('ilp_mis_attendance_plugin_register_termthreeend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term4start', get_string('ilp_mis_attendance_plugin_register_termfourstart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term4end', get_string('ilp_mis_attendance_plugin_register_termfourend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term5start', get_string('ilp_mis_attendance_plugin_register_termfivestart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term5end', get_string('ilp_mis_attendance_plugin_register_termfiveend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termenddesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term6start', get_string('ilp_mis_attendance_plugin_register_termsixstart', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termstartdesc', 'block_ilp'), '');

        $this->config_date_element($mform, 'mis_plugin_register_term6end', get_string('ilp_mis_attendance_plugin_register_termsixend', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termenddesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_plugin_register_term1header', get_string('ilp_mis_attendance_plugin_register_term1header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termone', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_register_term2header', get_string('ilp_mis_attendance_plugin_register_term2header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termtwo', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_register_term3header', get_string('ilp_mis_attendance_plugin_register_term3header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termthree', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_register_term4header', get_string('ilp_mis_attendance_plugin_register_term4header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termfour', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_register_term5header', get_string('ilp_mis_attendance_plugin_register_term5header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termfive', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_register_term6header', get_string('ilp_mis_attendance_plugin_register_term6header', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termsix', 'block_ilp'));

        $this->config_text_element($mform, 'mis_plugin_register_overallheader', get_string('ilp_mis_attendance_plugin_register_overallheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_termheader', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_overall', 'block_ilp'));


        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_register_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_register_tabletype', $options, get_string('ilp_mis_attendance_plugin_register_tabletype', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_tabletypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_DISABLED => get_string('disabled', 'block_ilp'),
            ILP_ENABLED => get_string('enabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_plugin_register_yearfilter', $options, get_string('ilp_mis_attendance_plugin_register_yearfilter', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_yearfilterdesc', 'block_ilp'), 0);

        $this->config_text_element($mform, 'mis_plugin_register_yearfilter_field', get_string('ilp_mis_attendance_plugin_register_yearfilter_field', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_yearfilter_fielddesc', 'block_ilp'), 'year');

        $this->config_text_element($mform, 'mis_plugin_register_yearfilter_year', get_string('ilp_mis_attendance_plugin_register_yearfilter_year', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_yearfilter_yeardesc', 'block_ilp'), date('Y'));


        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_plugin_register_pluginstatus', $options, get_string('ilp_mis_attendance_plugin_register_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_plugin_register_pluginstatusdesc', 'block_ilp'), 0);

    }


    public function plugin_type()
    {
        return 'attendance';
    }

    function language_strings(&$string)
    {

        $string['ilp_mis_attendance_plugin_register_pluginname'] = 'Register Overview';
        $string['ilp_mis_attendance_plugin_register_pluginnamesettings'] = 'Register Attendance Configuration';


        $string['ilp_mis_attendance_plugin_register_table'] = 'Register attendance table';
        $string['ilp_mis_attendance_plugin_register_tabledesc'] = 'table containing register data';

        $string['ilp_mis_attendance_plugin_register_studentidfield'] = 'Student id field';
        $string['ilp_mis_attendance_plugin_register_studentidfielddesc'] = 'The field containing the mis user id';

        $string['ilp_mis_attendance_plugin_register_courseid'] = 'Course id field';
        $string['ilp_mis_attendance_plugin_register_courseiddesc'] = 'The field containing course id data';

        $string['ilp_mis_attendance_plugin_register_registerid'] = 'Register ID field';
        $string['ilp_mis_attendance_plugin_register_registeriddesc'] = 'The field containing register id data';

        $string['ilp_mis_attendance_plugin_register_registername'] = 'Register Name field';
        $string['ilp_mis_attendance_plugin_register_registernamedesc'] = 'The field containing register name data';

        $string['ilp_mis_attendance_plugin_register_datetime'] = 'Date time field';
        $string['ilp_mis_attendance_plugin_register_datetimedesc'] = 'The field containing date time data';

        $string['ilp_mis_attendance_plugin_register_starttime'] = 'Course start time field';
        $string['ilp_mis_attendance_plugin_register_starttimedesc'] = 'The field containing course start time data';

        $string['ilp_mis_attendance_plugin_register_endtime'] = 'Course end time field';
        $string['ilp_mis_attendance_plugin_register_endtimedesc'] = 'The field containing course end time data';

        $string['ilp_mis_attendance_plugin_register_coursename'] = 'Course Name field';
        $string['ilp_mis_attendance_plugin_register_coursenamedesc'] = 'The field containing course name data';

        $string['ilp_mis_attendance_plugin_register_mark'] = 'Mark field';
        $string['ilp_mis_attendance_plugin_register_markdesc'] = 'The field containing mark data';

        $string['ilp_mis_attendance_plugin_register_present'] = 'Present codes';
        $string['ilp_mis_attendance_plugin_register_presentdesc'] = 'enter a comma separated list of present codes';

        $string['ilp_mis_attendance_plugin_register_presentcolour'] = 'Present code colour';
        $string['ilp_mis_attendance_plugin_register_presentcolourdesc'] = 'The colour that present marks will be displayed in on the grid';

        $string['ilp_mis_attendance_plugin_register_absent'] = 'Absent codes';
        $string['ilp_mis_attendance_plugin_register_absentdesc'] = 'enter a comma separated list of absent codes';

        $string['ilp_mis_attendance_plugin_register_absentcolour'] = 'Absent code colour';
        $string['ilp_mis_attendance_plugin_register_absentcolourdesc'] = 'The colour that absent marks will be displayed in on the grid';

        $string['ilp_mis_attendance_plugin_register_late'] = 'Late codes';
        $string['ilp_mis_attendance_plugin_register_latedesc'] = 'enter a comma separated list of late codes';

        $string['ilp_mis_attendance_plugin_register_latecolour'] = 'Late code colour';
        $string['ilp_mis_attendance_plugin_register_latecolourdesc'] = 'The colour that late marks will be displayed in on the grid';

        $string['ilp_mis_attendance_plugin_register_noclass'] = 'No class codes';
        $string['ilp_mis_attendance_plugin_register_noclassdesc'] = 'enter a comma separated list of no class codes';

        $string['ilp_mis_attendance_plugin_register_terms'] = 'Numbner of terms';
        $string['ilp_mis_attendance_plugin_register_termsdesc'] = 'How many terms does a year have';

        $string['ilp_mis_attendance_plugin_register_termonestart'] = 'Term 1 start';
        $string['ilp_mis_attendance_plugin_register_termoneend'] = 'Term 1 end';

        $string['ilp_mis_attendance_plugin_register_termtwostart'] = 'Term 2 start';
        $string['ilp_mis_attendance_plugin_register_termtwoend'] = 'Term 2 end';

        $string['ilp_mis_attendance_plugin_register_termthreestart'] = 'Term 3 start';
        $string['ilp_mis_attendance_plugin_register_termthreeend'] = 'Term 3 end';

        $string['ilp_mis_attendance_plugin_register_termfourstart'] = 'Term 4 start';
        $string['ilp_mis_attendance_plugin_register_termfourend'] = 'Term 4 end';

        $string['ilp_mis_attendance_plugin_register_termfivestart'] = 'Term 5 start';
        $string['ilp_mis_attendance_plugin_register_termfiveend'] = 'Term 5 end';

        $string['ilp_mis_attendance_plugin_register_termsixstart'] = 'Term 6 start';
        $string['ilp_mis_attendance_plugin_register_termsixend'] = 'Term 6 end';

        $string['ilp_mis_attendance_plugin_register_termstartdesc'] = 'Enter the terms start date';
        $string['ilp_mis_attendance_plugin_register_termenddesc'] = 'Enter the terms end date';

        $string['ilp_mis_attendance_plugin_register_termstartdesc'] = 'Enter the terms start date';
        $string['ilp_mis_attendance_plugin_register_termenddesc'] = 'Enter the terms end date';

        $string['ilp_mis_attendance_plugin_register_tabletype'] = 'Table type';
        $string['ilp_mis_attendance_plugin_register_tabletypedesc'] = 'what is the table type';

        $string['ilp_mis_attendance_plugin_register_yearfilter'] = 'Year filter';
        $string['ilp_mis_attendance_plugin_register_yearfilterdesc'] = 'Is a year filter used when selecting data from the MIS';

        $string['ilp_mis_attendance_plugin_register_yearfilter_field'] = 'Year filter field';
        $string['ilp_mis_attendance_plugin_register_yearfilter_fielddesc'] = 'If a MIS year filter is being used enter the field that will be filter on. (if stored procedure and field not needed leave field as year)';

        $string['ilp_mis_attendance_plugin_register_yearfilter_year'] = 'Year filter date';
        $string['ilp_mis_attendance_plugin_register_yearfilter_yeardesc'] = 'The date that will be filtered on';

        $string['ilp_mis_attendance_plugin_register_ignore'] = 'Ignore';
        $string['ilp_mis_attendance_plugin_register_positive'] = 'Positive';
        $string['ilp_mis_attendance_plugin_register_negative'] = 'Negative';

        $string['ilp_mis_attendance_plugin_register_months'] = 'Months';
        $string['ilp_mis_attendance_plugin_register_terms'] = 'Terms';

        $string['ilp_mis_attendance_plugin_register_pluginstatus'] = 'Status';
        $string['ilp_mis_attendance_plugin_register_pluginstatusdesc'] = 'is the plugin enabled or disabled';

        $string['ilp_mis_attendance_plugin_register_disp_day'] = 'Day';
        $string['ilp_mis_attendance_plugin_register_disp_date'] = 'Date';


        $string['ilp_mis_attendance_plugin_register_disp_att'] = 'Att';
        $string['ilp_mis_attendance_plugin_register_disp_late'] = 'Late';
        $string['ilp_mis_attendance_plugin_register_disp_time'] = 'Time';
        $string['ilp_mis_attendance_plugin_register_disp_class'] = 'Class';
        $string['ilp_mis_attendance_plugin_register_disp_week'] = 'Week';
        $string['ilp_mis_attendance_plugin_register_disp_possible'] = 'Possible';
        $string['ilp_mis_attendance_plugin_register_disp_attendance'] = 'Attendance';
        $string['ilp_mis_attendance_plugin_register_disp_absent'] = 'Absent';
        $string['ilp_mis_attendance_plugin_register_disp_present'] = 'Present';
        $string['ilp_mis_attendance_plugin_register_disp_punctuality'] = 'Punctuality';

        $string['ilp_mis_attendance_plugin_register_overall'] = 'All';
        $string['ilp_mis_attendance_plugin_register_termone'] = 'Autumn';
        $string['ilp_mis_attendance_plugin_register_termtwo'] = 'Spring';
        $string['ilp_mis_attendance_plugin_register_termthree'] = 'Summer';
        $string['ilp_mis_attendance_plugin_register_termfour'] = 'Term 4';
        $string['ilp_mis_attendance_plugin_register_termfive'] = 'Term 5';
        $string['ilp_mis_attendance_plugin_register_termsix'] = 'Term 6';


        $string['ilp_mis_attendance_plugin_register_overallheader'] = 'Overall header';
        $string['ilp_mis_attendance_plugin_register_term1header'] = 'Term 1 header';
        $string['ilp_mis_attendance_plugin_register_term2header'] = 'Term 2 header';
        $string['ilp_mis_attendance_plugin_register_term3header'] = 'Term 3 header';
        $string['ilp_mis_attendance_plugin_register_term4header'] = 'Term 4 header';
        $string['ilp_mis_attendance_plugin_register_term5header'] = 'Term 5 header';
        $string['ilp_mis_attendance_plugin_register_term6header'] = 'Term 6 header';
        $string['ilp_mis_attendance_plugin_register_termheader'] = 'The header that will be used to when displaying data from this term';

    }


    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return 'Register';
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
