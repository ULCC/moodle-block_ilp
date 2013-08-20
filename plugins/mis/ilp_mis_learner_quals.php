<?php


require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

class ilp_mis_learner_quals extends ilp_mis_plugin
{

    protected $fields;
    protected $mis_user_id;

    public function    __construct($params = array())
    {
        parent::__construct($params);

        $this->tabletype = get_config('block_ilp', 'mis_learner_quals_tabletype');
        $this->fields = array();
    }


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_quals&plugintype=mis">' . get_string('ilp_mis_learner_quals_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_learner_quals', '', $link));
    }


    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_learner_quals_table', get_string('ilp_mis_learner_quals_table', 'block_ilp'), get_string('ilp_mis_learner_quals_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_prelimcalls', get_string('ilp_mis_learner_quals_prelimcalls', 'block_ilp'), get_string('ilp_mis_learner_quals_prelimcallsdesc', 'block_ilp'), '');

        $this->config_text_element($mform,'mis_learner_quals_studentid',get_string('ilp_mis_learner_quals_studentid', 'block_ilp'),get_string('ilp_mis_learner_quals_studentiddesc', 'block_ilp'),'');


        $this->config_text_element($mform, 'mis_learner_quals_course_title', get_string('ilp_mis_learner_quals_course_title', 'block_ilp'), get_string('ilp_mis_learner_quals_course_titledesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_course_code', get_string('ilp_mis_learner_quals_course_code', 'block_ilp'), get_string('ilp_mis_learner_quals_course_codedesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_occurence', get_string('ilp_mis_learner_quals_occurence', 'block_ilp'), get_string('ilp_mis_learner_quals_occurencedesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_learning_aim_code', get_string('ilp_mis_learner_quals_learning_aim_code', 'block_ilp'), get_string('ilp_mis_learner_quals_learning_aim_codedesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_learning_aim_title', get_string('ilp_mis_learner_quals_learning_aim_title', 'block_ilp'), get_string('ilp_mis_learner_quals_course_learning_aim_titledesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_awarding_body_name', get_string('ilp_mis_learner_quals_awarding_body_name', 'block_ilp'), get_string('ilp_mis_learner_quals_awarding_body_namedesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_learning_aim_start_date', get_string('ilp_mis_learner_quals_learning_aim_start_date', 'block_ilp'), get_string('ilp_mis_learner_quals_learning_aim_start_datedesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_learning_aim_end_date', get_string('ilp_mis_learner_quals_learning_aim_end_date', 'block_ilp'), get_string('ilp_mis_learner_quals_learning_aim_end_datedesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_completion_description', get_string('ilp_mis_learner_quals_completion_description', 'block_ilp'), get_string('ilp_mis_learner_quals_completion_descriptiondesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_planned_hours', get_string('ilp_mis_learner_quals_planned_hours', 'block_ilp'), get_string('ilp_mis_learner_quals_planned_hoursdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_qualification_level', get_string('ilp_mis_learner_quals_qualification_level', 'block_ilp'), get_string('ilp_mis_learner_quals_qualification_leveldesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_quals_delivered_by', get_string('ilp_mis_learner_quals_delivered_by', 'block_ilp'), get_string('ilp_mis_learner_quals_delivered_bydesc', 'block_ilp'), '');


        $this->config_htmleditor_element($mform, 'mis_learner_quals_course_title_help', get_string('ilp_mis_learner_quals_course_title_help', 'block_ilp'), get_string('ilp_mis_learner_quals_course_title_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_course_code_help', get_string('ilp_mis_learner_quals_course_code_help', 'block_ilp'), get_string('ilp_mis_learner_quals_course_code_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_occurence_help', get_string('ilp_mis_learner_quals_occurence_help', 'block_ilp'), get_string('ilp_mis_learner_quals_occurence_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_learning_aim_code_help', get_string('ilp_mis_learner_quals_learning_aim_code_help', 'block_ilp'), get_string('ilp_mis_learner_quals_learning_aim_code_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_learning_aim_title_help', get_string('ilp_mis_learner_quals_learning_aim_title_help', 'block_ilp'), get_string('ilp_mis_learner_quals_learning_aim_title_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_learning_awarding_body_name_help', get_string('ilp_mis_learner_quals_learning_awarding_body_name_help', 'block_ilp'), get_string('ilp_mis_learner_quals_learning_aim_title_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_learning_aim_start_date_help', get_string('ilp_mis_learner_quals_learning_aim_start_date_help', 'block_ilp'), get_string('ilp_mis_learner_quals_learning_aim_start_date_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_learning_aim_end_date_help', get_string('ilp_mis_learner_quals_learning_aim_end_date_help', 'block_ilp'), get_string('ilp_mis_learner_quals_learning_aim_end_date_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_completion_description_help', get_string('ilp_mis_learner_quals_completion_description_help', 'block_ilp'), get_string('ilp_mis_learner_quals_completion_description_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_planned_hours_help', get_string('ilp_mis_learner_quals_planned_hours_help', 'block_ilp'), get_string('ilp_mis_learner_quals_planned_hours_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_qualification_level_help', get_string('ilp_mis_learner_quals_qualification_level_help', 'block_ilp'), get_string('ilp_mis_learner_quals_qualification_level_helpdesc', 'block_ilp'), '');

        $this->config_htmleditor_element($mform, 'mis_learner_quals_delivered_by_help', get_string('ilp_mis_learner_quals_delivered_by_help', 'block_ilp'), get_string('ilp_mis_learner_quals_delivered_by_helpdesc', 'block_ilp'), '');




        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_quals_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_quals_tabletype', $options, get_string('ilp_mis_learner_quals_tabletype', 'block_ilp'), get_string('ilp_mis_learner_quals_tabletypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_learner_quals_pluginstatus', $options, get_string('ilp_mis_learner_quals_pluginstatus', 'block_ilp'), get_string('ilp_mis_learner_quals_pluginstatusdesc', 'block_ilp'), 0);

        $this->config_htmleditor_element($mform, 'ilp_mis_learner_quals_introduction', get_string('ilp_mis_learner_quals_introduction', 'block_ilp'), get_string('ilp_mis_learner_quals_introductiondesc', 'block_ilp'));
    }


    /**
     * Retrieves data from the mis
     *
     * @param    $mis_user_id    the id of the user in the mis used to retrieve the data of the user
     *
     * @return    null
     */
    public function set_data($mis_user_id, $userid = null)
    {

        $this->mis_user_id = $mis_user_id;

        $table = get_config('block_ilp', 'mis_learner_quals_table');

        if (!empty($table)) {

            $sidfield = get_config('block_ilp', 'mis_learner_quals_studentid');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_learner_quals_idtype');
            $mis_user_id = (empty($idtype)) ? "'$mis_user_id'" : $mis_user_id;

            $keyfields = array();

            $useyearfilter = get_config('block_ilp', 'mis_learner_quals_yearfilter');

            //create the key that will be used in sql query
            $keyfields[$sidfield] = array('=' => $mis_user_id);

            //check if the course_title config has been set and pass the value
            if (get_config('block_ilp', 'mis_learner_quals_course_title')) $this->fields['mis_learner_quals_course_title'] = get_config('block_ilp', 'mis_learner_quals_course_title');

            if (get_config('block_ilp', 'mis_learner_quals_course_code')) $this->fields['mis_learner_quals_course_code'] = get_config('block_ilp', 'mis_learner_quals_course_code');

            if (get_config('block_ilp', 'mis_learner_quals_occurence')) $this->fields['mis_learner_quals_occurence'] = get_config('block_ilp', 'mis_learner_quals_occurence');

            if (get_config('block_ilp', 'mis_learner_quals_learning_aim_code')) $this->fields['mis_learner_quals_learning_aim_code'] = get_config('block_ilp', 'mis_learner_quals_learning_aim_code');

            if (get_config('block_ilp', 'mis_learner_quals_learning_aim_title')) $this->fields['mis_learner_quals_learning_aim_title'] = get_config('block_ilp', 'mis_learner_quals_learning_aim_title');

            if (get_config('block_ilp', 'mis_learner_quals_awarding_body_name')) $this->fields['mis_learner_quals_awarding_body_name'] = get_config('block_ilp', 'mis_learner_quals_awarding_body_name');

            if (get_config('block_ilp', 'mis_learner_quals_learning_aim_start_date')) $this->fields['mis_learner_quals_learning_aim_start_date'] = get_config('block_ilp', 'mis_learner_quals_learning_aim_start_date');

            if (get_config('block_ilp', 'mis_learner_quals_learning_aim_end_date')) $this->fields['mis_learner_quals_learning_aim_end_date'] = get_config('block_ilp', 'mis_learner_quals_learning_aim_end_date');

            if (get_config('block_ilp', 'mis_learner_quals_completion_description')) $this->fields['mis_learner_quals_completion_description'] = get_config('block_ilp', 'mis_learner_quals_completion_description');

            if (get_config('block_ilp', 'mis_learner_quals_planned_hours')) $this->fields['mis_learner_quals_planned_hours'] = get_config('block_ilp', 'mis_learner_quals_planned_hours');

            if (get_config('block_ilp', 'mis_learner_quals_qualification_level')) $this->fields['mis_learner_quals_qualification_level'] = get_config('block_ilp', 'mis_learner_quals_qualification_level');

            if (get_config('block_ilp', 'mis_learner_quals_delivered_by')) $this->fields['mis_learner_quals_delivered_by'] = get_config('block_ilp', 'mis_learner_quals_delivered_by');

            $prelimdbcalls = get_config('block_ilp', 'mis_learner_quals_prelimcalls');

            $this->data = $this->dbquery($table, $keyfields, $this->fields, null, $prelimdbcalls);
            //we only need the first record so pass it back
        }
    }


    /**
     *
     * @see ilp_mis_plugin::display()
     */
    function display()
    {

        global $CFG;

        if (!empty($this->data)) {

            $tables = $this->generate_tables();

            $pluginoutput = '';
            //buffer output
            ob_start();


            $misdata = $this->data;

            //call the html file for the plugin
            require($CFG->dirroot . '/blocks/ilp/plugins/mis/ilp_mis_learner_quals.html');
            $pluginoutput .= ob_get_contents();
            ob_end_clean();

            return $pluginoutput;
        } else {
            echo '<div id="plugin_nodata">' . get_string('nodataornoconfig', 'block_ilp') . '</div>';
        }
    }

    public function generate_tables() {
        $tables = array();
        foreach ($this->data as $coursedata) {
            $table = '<table class="quals-course"><tbody>';
            foreach($this->fields as $fieldname => $data_offset) {
                $entry_label = get_string('ilp_' . $fieldname . 'display', 'block_ilp');
                $entry_help = get_config('block_ilp', $fieldname . '_help');
                $table_entry = html_writer::tag('th', $entry_label);
                $table_entry .= html_writer::tag('td', $entry_help);
                $table_entry .= html_writer::tag('td', $coursedata[$data_offset]);
                $table .= html_writer::tag('tr', $table_entry);
            }
            $table .= '</tbody></table>';
            $tables[] = $table;
        }

        $cleared_div = '<div class="clearfix"></div>';
        $tables_html = implode($cleared_div, $tables);
        return $tables_html;
    }


    static function language_strings(&$string)
    {

        $string['ilp_mis_learner_quals_table'] = 'Database table';
        $string['ilp_mis_learner_quals_tabledesc'] = 'The name of the database table where the data for this plugin is held';

        $string['ilp_mis_learner_quals_tabletype'] = 'Table type';
        $string['ilp_mis_learner_quals_tabletypedesc'] = 'Does this plugin connect to a table or stored procedure';

        $string['ilp_mis_learner_quals_pluginstatus'] = 'Status';
        $string['ilp_mis_learner_quals_pluginstatusdesc'] = 'Is the block enabled or disabled';

        $string['ilp_mis_learner_quals_prelimcalls'] = 'Preliminary db calls';
        $string['ilp_mis_learner_quals_prelimcallsdesc'] = 'preliminary calls that need to be made to the db before the sql is executed';

        $string['ilp_mis_learner_quals_studentid'] = 'Student Id field';
        $string['ilp_mis_learner_quals_studentiddesc'] = 'The id field used to find the student data in the database table';

        $string['ilp_mis_learner_quals_pluginname'] = 'My Qualifications';
        $string['ilp_mis_learner_quals_pluginnamesettings'] = 'Qualifications configuration';

        $string['ilp_mis_learner_quals_course_title'] = 'course_title data field';
        $string['ilp_mis_learner_quals_course_titledesc'] = 'The field that holds course_title data';

        $string['ilp_mis_learner_quals_course_code'] = 'course_code data field';
        $string['ilp_mis_learner_quals_course_codedesc'] = 'The field that holds course_code data';

        $string['ilp_mis_learner_quals_occurence'] = 'occurence data field';
        $string['ilp_mis_learner_quals_occurencedesc'] = 'The field that holds occurence data';

        $string['ilp_mis_learner_quals_learning_aim_code'] = 'learning_aim_code data field';
        $string['ilp_mis_learner_quals_learning_aim_codedesc'] = 'The field that holds learning_aim_code data';

        $string['ilp_mis_learner_quals_learning_aim_title'] = 'learning_aim_title data field';
        $string['ilp_mis_learner_quals_course_learning_aim_titledesc'] = 'The field that holds learning_aim_title data';

        $string['ilp_mis_learner_quals_awarding_body_name'] = 'awarding_body_name data field';
        $string['ilp_mis_learner_quals_awarding_body_namedesc'] = 'The field that holds awarding_body_name data';

        $string['ilp_mis_learner_quals_learning_aim_start_date'] = 'learning_aim_start_date field';
        $string['ilp_mis_learner_quals_learning_aim_start_datedesc'] = 'The field that holds learning_aim_start_date data';

        $string['ilp_mis_learner_quals_learning_aim_end_date'] = 'learning_aim_end_date field';
        $string['ilp_mis_learner_quals_learning_aim_end_datedesc'] = 'The field that holds learning_aim_end_date data';

        $string['ilp_mis_learner_quals_completion_description'] = 'completion_description data field';
        $string['ilp_mis_learner_quals_completion_descriptiondesc'] = 'The field that holds completion_description data';

        $string['ilp_mis_learner_quals_planned_hours'] = 'planned_hours data field';
        $string['ilp_mis_learner_quals_planned_hoursdesc'] = 'The field that holds planned_hours data';

        $string['ilp_mis_learner_quals_qualification_level'] = 'qualification_level data field';
        $string['ilp_mis_learner_quals_qualification_leveldesc'] = 'The field that holds qualification_level data';

        $string['ilp_mis_learner_quals_delivered_by'] = 'delivered_by data field';
        $string['ilp_mis_learner_quals_delivered_bydesc'] = 'The field that holds delivered_by data';

        $string['ilp_mis_learner_quals_course_titledisplay'] = 'Course Title';
        $string['ilp_mis_learner_quals_course_codedisplay'] = 'Course Code';
        $string['ilp_mis_learner_quals_occurencedisplay'] = 'Occurence';
        $string['ilp_mis_learner_quals_learning_aim_codedisplay'] = 'Learning Aim Code';
        $string['ilp_mis_learner_quals_learning_aim_titledisplay'] = 'Learning Aim Title';
        $string['ilp_mis_learner_quals_awarding_body_namedisplay'] = 'Awarding Body Name';
        $string['ilp_mis_learner_quals_learning_aim_start_datedisplay'] = 'Learning Aim Start Date';
        $string['ilp_mis_learner_quals_learning_aim_end_datedisplay'] = 'Learning Aim End Date';
        $string['ilp_mis_learner_quals_completion_descriptiondisplay'] = 'Completion Description';
        $string['ilp_mis_learner_quals_planned_hoursdisplay'] = 'Planned Hours';
        $string['ilp_mis_learner_quals_qualification_leveldisplay'] = 'Qualification Level';
        $string['ilp_mis_learner_quals_delivered_bydisplay'] = 'Delivered By';

        $string['ilp_mis_learner_quals_disp_tabname'] = 'My Qualifications';
        $string['ilp_mis_learner_quals_disp_course_title'] = 'Course Title';
        $string['ilp_mis_learner_quals_disp_award_in_employability'] = 'Award in Employability';

        $string['ilp_mis_learner_quals_introduction'] = 'Introduction';
        $string['ilp_mis_learner_quals_introductiondesc'] = 'Introduction to display';
        $string['ilp_mis_learner_quals_introductiondisplay'] = 'Introduction';

        $string['ilp_mis_learner_quals_course_title_help'] = 'Course Title Help';
        $string['ilp_mis_learner_quals_course_code_help'] = 'Course Code Help';
        $string['ilp_mis_learner_quals_occurence_help'] = 'Occurence Help';
        $string['ilp_mis_learner_quals_learning_aim_code_help'] = 'Learning Aim Code Help';
        $string['ilp_mis_learner_quals_learning_aim_title_help'] = 'Learning Aim Title Help';
        $string['ilp_mis_learner_quals_learning_awarding_body_name_help'] = 'Awarding Body Name Help';
        $string['ilp_mis_learner_quals_learning_aim_start_date_help'] = 'Learning Aim Start Date Help';
        $string['ilp_mis_learner_quals_learning_aim_end_date_help'] = 'Learning Aim End Date Help';
        $string['ilp_mis_learner_quals_completion_description_help'] = 'Completion Help';
        $string['ilp_mis_learner_quals_planned_hours_help'] = 'Planned Hours Help';
        $string['ilp_mis_learner_quals_qualification_level_help'] = 'Qualification Level Help';
        $string['ilp_mis_learner_quals_delivered_by_help'] = 'Delivered By Help';

        $string['ilp_mis_learner_quals_course_title_helpdesc'] = 'Course Title Help';
        $string['ilp_mis_learner_quals_course_code_helpdesc'] = 'Course Code Help';
        $string['ilp_mis_learner_quals_occurence_helpdesc'] = 'Occurence Help';
        $string['ilp_mis_learner_quals_learning_aim_code_helpdesc'] = 'Learning Aim Code Help';
        $string['ilp_mis_learner_quals_learning_aim_title_helpdesc'] = 'Learning Aim Title Help';
        $string['ilp_mis_learner_quals_awarding_body_name_helpdesc'] = 'Awarding Body Name Help';
        $string['ilp_mis_learner_quals_learning_aim_start_date_helpdesc'] = 'Learning Aim Start Date Help';
        $string['ilp_mis_learner_quals_learning_aim_end_date_helpdesc'] = 'Learning Aim End Date Help';
        $string['ilp_mis_learner_quals_completion_description_helpdesc'] = 'Completion Help';
        $string['ilp_mis_learner_quals_planned_hours_helpdesc'] = 'Planned Hours Help';
        $string['ilp_mis_learner_quals_qualification_level_helpdesc'] = 'Qualification Level Help';
        $string['ilp_mis_learner_quals_delivered_by_helpdesc'] = 'Delivered By Help';
    }

    static function plugin_type()
    {
        return 'quals';
    }


    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return 'My Qualifications';
    }

}

?>