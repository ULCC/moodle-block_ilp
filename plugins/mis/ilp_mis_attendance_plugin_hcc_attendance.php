<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');


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
            ob_start();
            //instantiate the ilp_ajax_table class
            $flextable = new ilp_mis_ajax_table('hcc_attendance', true, '');

            //setup the headers and columns with the fields that have been requested

            $headers = array();
            $columns = array();
            $headers[] = 'Course';
            $headers[] = 'Code';
            //$headers[] = 'Performance';
            $headers[] = 'Attendance';
            $headers[] = 'Punctuality';
            

            $columns[] = 'course';
            $columns[] = 'code';
            //$columns[] = 'performance';
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
            
			foreach ($this->data as $d) {
				$data['course'] 		= $d[$this->fields['coursetitle']];
            	$data['code'] 			= $d[$this->fields['coursecode']];
            	
            	$attendance				=	$d[$this->fields['positivemarks']] / ( $d[$this->fields['totalmarks']] - $d[$this->fields['missedmarks']] ) * 100;
            	$punctuality			=	( $d[$this->fields['totalmarks']] - $d[$this->fields['missedmarks']] - $d[$this->fields['latemarks']]) / ($d[$this->fields['totalmarks']] - $d[$this->fields['missedmarks']] ) * 100;
            	
            	//$colour					=	$this->performane_css($d['performance']);
            	
            	//$data['performance'] 	= "<span style='background-color: {$colour}'>{$d['performance']}</span>";
            	$data['attendance']		= round($attendance,0);
            	$data['punctuality'] 	= round($punctuality,0);
            	$flextable->add_data_keyed($data);	
			}

            $flextable->finish_html();
            $output = ob_get_contents();
            ob_end_clean();

        } else {
            if( $msg = get_string('nodataornoconfig', 'block_ilp') ){
                $output = '<div id="plugin_nodata">' . $msg . '</div>';
            }
        }

        return $output;

    }




    /**
     * Retrieves user data from the mis database
     *
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data($mis_user_id, $user_id=null)
    {

        $table = 'VLE1112_NEWStudentAttendance';


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
            /*
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
            
            */
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


    public static function plugin_type()
    {
        return 'overview';
    }

    static function language_strings(&$string)
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
