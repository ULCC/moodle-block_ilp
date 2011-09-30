<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');


class ilp_mis_attendance_plugin_hcc_attendance extends ilp_mis_attendance_plugin
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
    }


    /*
    * display the current state of $this->data
    */
    public function display()
    {
        global $CFG, $PARSER;

        if (!empty($this->data)) {

            //set up the flexible table for displaying

            //instantiate the ilp_ajax_table class
            $flextable = new ilp_mis_ajax_table('hcc_attendance', true, '');

            //setup the headers and columns with the fields that have been requested

            $headers = array();
            $columns = array();
            $headers[] = 'Course';
            $headers[] = 'Code';
            $headers[] = 'Performance';
            $headers[] = 'Attendance';
            $headers[] = 'Punchuality';
            

            $columns[] = 'course';
            $columns[] = 'code';
            $columns[] = 'performance';
            $columns[] = 'attendance';
            $columns[] = 'punchuality';

            //define the columns in the tables
            $flextable->define_columns($columns);

            //define the headers in the tables
            $flextable->define_headers($headers);

            //we do not need the intialbars
            $flextable->initialbars(false);

            $flextable->set_attribute('class', 'flexible generaltable');

            //setup the flextable
            $flextable->setup();
            
			foreach ($this->data as $d) {
				$data['course'] 		= $d[$this->fields['coursetitle']];
            	$data['code'] 			= $d[$this->fields['coursecode']];
            	
            	$attendance				=	$d[$this->fields['positivemarks']] / ( $d[$this->fields['totalmarks']] - $d[$this->fields['missedmarks']] ) * 100;
            	$punchuality			=	( $d[$this->fields['totalmarks']] - $d[$this->fields['missedmarks']] - $d[$this->fields['latemarks']]) / ($d[$this->fields['totalmarks']] - $d[$this->fields['missedmarks']] ) * 100;
            	
            	$colour					=	$this->performane_css($d['performance']);
            	
            	$data['performance'] 	= "<span style='background-color: {$colour}'>{$d['performance']}</span>";
            	$data['attendance']		= round($attendance,0);
            	$data['punchuality'] 	= round($punchuality,0);
            	$flextable->add_data_keyed($data);	
			}

            ob_start();
            $flextable->print_html();
            $output = ob_get_contents();
            ob_end_clean();

        } else {
            $output = '<div id="plugin_nodata">' . get_string('nodataornoconfig', 'block_ilp') . '</div>';
        }

        return $output;

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
    /**
     * Retrieves user data from the mis database
     *
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data($mis_user_id)
    {

        $table = 'VLE1112_StudentAttendance';


        $this->mis_user_id = $mis_user_id;

        if (!empty($table)) {
            $sidfield = 'Lnr_Reference';

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_plugin_registerterm_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            $keyfields = array();

            $keyfields[$sidfield] = array('=' => $mis_user_id);

			$this->fields = array();

            //get all of the fields that will be returned
            $this->fields['coursetitle'] = 'Course_Title';
            $this->fields['coursecode']  = 'Course_Code';
            $this->fields['positivemarks'] 	=  'TotalPositiveMarks';
            $this->fields['missedmarks'] 	=  'TotalMissedMarks';
            $this->fields['totalmarks']		= 'TotalExpMarks';
            $this->fields['latemarks'] 		= 'TotalLateMarks';
            

            //get the users monthly attendance data
            $this->data = $this->dbquery($table, $keyfields, $this->fields);
            
            //we now need to get behaviour data 
            if (!empty($this->data))	{
            	
            	foreach ($this->data as &$d) {
            		$keyfields = array();
            		$keyfields['StudentRef'] = array('=' => $mis_user_id);
            		$keyfields['M_Reference'] = array('=' => "'".$d[$this->fields['coursecode']]."'");
            		$behaviour	=	$this->dbquery('VLE1112_Tracker', $keyfields);
            		
            		if (!empty($behaviour))	{
            			$behaviour	=	array_shift($behaviour);
            			$d['performance']	=	$this->determine_behaviour($behaviour, date('n'), 12);
            		}
            	}
            } 
        }
    }
    
	function performane_css($behaviour)	{
	
		switch ($behaviour) {
			case 'Excellent':
				return '#00ff00';
				break;
			case 'Good':
				return '#99cc00';
				break;			
			case 'Satisfactory':
				return '#ff9900';
				break;
			case 'Unsatisfactory':
				return '#ff0000';
				break;
			default:
				return'white';
		}
	}
    
    
	function determine_behaviour($dbcall,$current_month,$index) {
		$monthname = date("F", mktime(0, 0, 0, $current_month, 1, 2000));
		$behaviourmonth = $monthname."_Behaviour";
	
		$behaviour = $dbcall[$behaviourmonth];	
		
		$current_month--;
		$index--;
	
		if ($index == 1 || empty($index))  return 'n/a';//this stops any infinite loops
	
		if ($current_month == 0) $current_month = 12;
			
		if (empty($behaviour)) {
				return $this->determine_behaviour($dbcall,$current_month,$index);
		} else {
				return $behaviour;
		}
	}
    


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_hcc_attendance&plugintype=mis">' . get_string('ilp_mis_attendance_plugin_hcc_attendance_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_attendance_plugin_hcc_attendance', '', $link));
    }


    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {
        global $CFG;

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_attendance_plugin_hcc_attendance_pluginstatus', $options, get_string('ilp_mis_attendance_plugin_hcc_attendance_pluginstatus', 'block_ilp'), get_string('ilp_mis_attendance_plugin_hcc_attendance_pluginstatusdesc', 'block_ilp'), 0);

    }


    public function plugin_type()
    {
        return 'overview';
    }

    function language_strings(&$string)
    {
		$string['ilp_mis_attendance_plugin_hcc_attendance_pluginnamesettings'] = 'HCC attendance plugin';
        $string['ilp_mis_attendance_plugin_hcc_attendance_pluginstatus'] = 'Plugin status';
        $string['ilp_mis_attendance_plugin_hcc_attendance_pluginstatusdesc'] = 'Is the plugin enabled or disabled';
    }


    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return '';
    }

    function getAttendance()
    {
        // TODO: Implement getAttendance() method.
    }

    function getPunctuality()
    {
        // TODO: Implement getPunctuality() method.
    }


}