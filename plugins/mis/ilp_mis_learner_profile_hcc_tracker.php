<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_attendance_plugin.class.php');


class ilp_mis_learner_profile_hcc_tracker extends ilp_mis_attendance_plugin
{

    public $fields;

    public function __construct($params = array())
    {
        parent::__construct($params);
    }


    function tooltip_span($text,$tooltip,$id)	{
    	$u	=	time();
    	$id	=	$id."$u";
    	
    	return "<span id='{$id}' class='tooltip' title='{$tooltip}'>{$text}</span>";
    }
    
    /*
    * display the current state of $this->data
    */
    public function display()
    {
        global $CFG, $PARSER, $PAGE;

        if (!empty($this->data)) {

            //set up the flexible table for displaying

            //instantiate the ilp_ajax_table class
            $flextable = new ilp_mis_ajax_table('hcc_attendance', true, '');

            //setup the headers and columns with the fields that have been requested

            $headers = array();
            $columns = array();
            $headers[] = 'Course';
            $headers[] = 'Code';
            $headers[] = 'Target';
            $headers[] = 'Aug';
            $headers[] = 'Sept';
            
            $headers[] = 'Oct';
            $headers[] = 'Nov';
            $headers[] = 'Dec';
            $headers[] = 'Jan';
            $headers[] = 'Feb';
            $headers[] = 'Mar';
            $headers[] = 'Apr';
            $headers[] = 'May';
            
            $headers[] = 'June';
            $headers[] = 'July';
            
            

            $columns[] = 'course';
            $columns[] = 'code';
            $columns[] = 'target';
            $columns[] = 'aug';
            $columns[] = 'sep';
            $columns[] = 'oct';
            $columns[] = 'nov';
            $columns[] = 'dec';
            $columns[] = 'jan';
            $columns[] = 'feb';
            $columns[] = 'mar';
            $columns[] = 'apr';
            $columns[] = 'may';
            $columns[] = 'jun';
            $columns[] = 'jul';

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
				$data['course'] 		= $d[$this->fields['mname']];
	           	$data['code'] 			= $d[$this->fields['mreference']];
	            	
	           	$data['target'] 		= $d[$this->fields['target']];
	           	
	           	$data['aug'] 		= $this->tooltip_span($d[$this->fields['auggrade']],$d[$this->fields['augnote']],'augtooltip');
	           	$data['sep'] 		= $this->tooltip_span($d[$this->fields['sepgrade']],$d[$this->fields['sepnote']],'septooltip');
	           	$data['oct'] 		= $this->tooltip_span($d[$this->fields['octgrade']],$d[$this->fields['octnote']],'octtooltip');
	           	
	           	$data['nov'] 		= $this->tooltip_span($d[$this->fields['novgrade']],$d[$this->fields['novnote']],'novtooltip');
	           	$data['dec'] 		= $this->tooltip_span($d[$this->fields['decgrade']],$d[$this->fields['decnote']],'dectooltip');
	           	$data['jan'] 		= $this->tooltip_span($d[$this->fields['jangrade']],$d[$this->fields['jannote']],'jantooltip');
	           	
	           	$data['feb'] 		= $this->tooltip_span($d[$this->fields['febgrade']],$d[$this->fields['febnote']],'febtooltip');
	           	$data['mar'] 		= $this->tooltip_span($d[$this->fields['margrade']],$d[$this->fields['marnote']],'martooltip');
	           	$data['apr'] 		= $this->tooltip_span($d[$this->fields['aprgrade']],$d[$this->fields['aprnote']],'aprtooltip');
	           	
	           	$data['may'] 		= $this->tooltip_span($d[$this->fields['maygrade']],$d[$this->fields['maynote']],'maytooltip');
	           	$data['jun'] 		= $this->tooltip_span($d[$this->fields['jungrade']],$d[$this->fields['junnote']],'juntooltip');
	           	$data['jul'] 		= $this->tooltip_span($d[$this->fields['julgrade']],$d[$this->fields['julnote']],'jultooltip');
	
	           	$flextable->add_data_keyed($data);	
            }			

            ob_start();
            $flextable->print_html();
            $output = ob_get_contents();
            
            $jsmodule = array(
    				'name'     	=> 'ilp_mis_learner_profile_hcc_tracker',
    				'fullpath' 	=> '/blocks/ilp/plugins/mis/ilp_mis_learner_profile_hcc_tracker.js',
					'requires'  	=> array('yahoo','event','dom','element','connection','array')
			);

			$PAGE->requires->js_init_call('M.ilp_mis_learner_profile_hcc_tracker.init', null, true, $jsmodule);
            
            
            
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
    function set_data($mis_user_id,$user_id=null)
    {

        $table = 'VLE1112_Tracker';


        $this->mis_user_id = $mis_user_id;

        if (!empty($table)) {
            $sidfield = 'StudentRef';

            $mis_user_id = $mis_user_id;

            $keyfields = array();

            $keyfields[$sidfield] = array('=' => $mis_user_id);

			$this->fields = array();

            //get all of the fields that will be returned
            $this->fields['moduleid'] 		= 'ModuleID';
            $this->fields['mreference']  	= 'M_Reference';
            $this->fields['mname'] 			=  'M_Name';
            $this->fields['target'] 		=  'TargetGrade';
            $this->fields['auggrade']		= 'August_Grade';
            $this->fields['augbehav'] 		= 'August_Behaviour';
            $this->fields['augnote'] 		=  'August_Notes';
            $this->fields['sepgrade']		= 'September_Grade';
            $this->fields['sepbehav'] 		= 'September_Behaviour';
            $this->fields['sepnote'] 		=  'September_Notes';
            $this->fields['octgrade']		= 'October_Grade';
            $this->fields['octbehav'] 		= 'October_Behaviour';
            $this->fields['octnote'] 		=  'October_Notes';
            $this->fields['novgrade']		= 'November_Grade';
            $this->fields['novbehav'] 		= 'November_Behaviour';
            $this->fields['novnote'] 		=  'November_Notes';
            $this->fields['decgrade']		= 'December_Grade';
            $this->fields['decbehav'] 		= 'December_Behaviour';
            $this->fields['decnote'] 		=  'December_Notes';
            $this->fields['jangrade']		= 'January_Grade';
            $this->fields['janbehav'] 		= '	January_Behaviour';
            $this->fields['jannote']		= 'January_Notes';

            $this->fields['febgrade'] 		= 'February_Grade';
            $this->fields['febbehav'] 		=  'February_Behaviour';
            $this->fields['febnote']		= 'February_Notes';
            $this->fields['margrade'] 		= 'March_Grade';
            $this->fields['marbehav'] 		=  'March_Behaviour';
            $this->fields['marnote']		= 'March_Notes';
            
            $this->fields['aprgrade'] 		= 'April_Grade';
            $this->fields['aprbehav'] 	=  'April_Behaviour';
            $this->fields['aprnote']		= 'April_Notes';
            $this->fields['maygrade'] 		= 'May_Grade';
            $this->fields['maybehav'] 	=  'May_Behaviour';
            $this->fields['maynote']		= 'May_Notes';
            
            $this->fields['jungrade'] 		= 'June_Grade';
            $this->fields['junbehav'] 	=  'June_Behaviour';
            $this->fields['junnote']		= 'June_Notes';
            $this->fields['julgrade'] 		= '	July_Grade';
            $this->fields['julbehav'] 	=  'July_Behaviour';
            $this->fields['julnote']		= 'July_Notes';

            $keyfields = array();
            $keyfields['StudentRef'] = array('=' => $mis_user_id);
            $this->data	=	$this->dbquery('VLE1112_Tracker', $keyfields);
            
            
        }
    }

    


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_profile_hcc_tracker&plugintype=mis">' . get_string('ilp_mis_learner_profile_hcc_tracker_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_learner_profile_hcc_tracker', '', $link));
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

        $this->config_select_element($mform, 'ilp_mis_learner_profile_hcc_tracker_pluginstatus', $options, get_string('ilp_mis_learner_profile_hcc_tracker_pluginstatus', 'block_ilp'), get_string('ilp_mis_learner_profile_hcc_tracker_pluginstatusdesc', 'block_ilp'), 0);

    }


    static function plugin_type()	{
    	return 'learnerprofile';
    }

    static function language_strings(&$string)
    {
		$string['ilp_mis_learner_profile_hcc_tracker_pluginnamesettings'] = 'HCC tracker plugin';
        $string['ilp_mis_learner_profile_hcc_tracker_pluginstatus'] 		= 'Plugin status';
        $string['ilp_mis_learner_profile_hcc_tracker_pluginstatusdesc'] = 'Is the plugin enabled or disabled';
    }


    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return 'Tracker';
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
