<?php


require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

class ilp_mis_learner_skillsbuilder extends ilp_mis_plugin
{

    protected $fields;
    protected $mis_user_id;
    static $skillsbuilder_data;
    static $skillsbuilder_fields;

    public function    __construct($params = array())
    {
        parent::__construct($params);

        $this->tabletype = get_config('block_ilp', 'mis_learner_skillsbuilder_tabletype');
        $this->fields = array();
    }


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_skillsbuilder&plugintype=mis">' . get_string('ilp_mis_learner_skillsbuilder_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_learner_skillsbuilder', '', $link));
    }


    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_learner_skillsbuilder_table', get_string('ilp_mis_learner_skillsbuilder_table', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_tabledesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_studentid', get_string('ilp_mis_learner_skillsbuilder_studentid', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_studentiddesc', 'block_ilp'), '');

       // $this->config_text_element($mform, 'mis_learner_skillsbuilder_forskills_id', get_string('ilp_mis_learner_skillsbuilder_forskills_id', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_forskills_iddesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_first_name', get_string('ilp_mis_learner_skillsbuilder_first_name', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_first_namedesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_last_name', get_string('ilp_mis_learner_skillsbuilder_last_name', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_last_namedesc', 'block_ilp'), '');
        /*
         * studentid does this.
         * $this->config_text_element($mform, 'mis_learner_skillsbuilder_learner_ref', get_string('ilp_mis_learner_skillsbuilder_learner_ref', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_learner_refdesc', 'block_ilp'), '');
         */
      /*  $this->config_text_element($mform, 'mis_learner_skillsbuilder_date_enrolled_engish', get_string('ilp_mis_learner_skillsbuilder_date_enrolled_engish', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_date_enrolled_engishdesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_date_completed_eng_ia', get_string('ilp_mis_learner_skillsbuilder_date_completed_eng_ia', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_date_completed_eng_iadesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_eng_ia_level', get_string('ilp_mis_learner_skillsbuilder_eng_ia_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_eng_ia_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_eng_ia_reading_level', get_string('ilp_mis_learner_skillsbuilder_eng_ia_reading_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_eng_ia_reading_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_eng_ia_punctuation_level', get_string('ilp_mis_learner_skillsbuilder_eng_ia_punctuation_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_eng_ia_punctuation_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_eng_ia_spelling_level', get_string('ilp_mis_learner_skillsbuilder_eng_ia_spelling_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_eng_ia_spelling_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_eng_ia_grammar_level', get_string('ilp_mis_learner_skillsbuilder_eng_ia_grammar_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_eng_ia_grammar_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_date_enrolled_maths', get_string('ilp_mis_learner_skillsbuilder_date_enrolled_maths', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_date_enrolled_mathsdesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_date_completed_mat_ia', get_string('ilp_mis_learner_skillsbuilder_date_completed_mat_ia', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_date_completed_mat_iadesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_mat_ia_level', get_string('ilp_mis_learner_skillsbuilder_mat_ia_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_mat_ia_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_mat_ia_numer_level', get_string('ilp_mis_learner_skillsbuilder_mat_ia_numer_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_mat_ia_numer_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_mat_ia_hd_level', get_string('ilp_mis_learner_skillsbuilder_mat_ia_hd_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_mat_ia_hd_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_mat_ia_mss_level', get_string('ilp_mis_learner_skillsbuilder_mat_ia_mss_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_mat_ia_mss_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_date_completed_eng_diag', get_string('ilp_mis_learner_skillsbuilder_date_completed_eng_diag', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_date_completed_eng_diagdesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_eng_diag_level', get_string('ilp_mis_learner_skillsbuilder_eng_diag_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_eng_diag_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_date_completed_mat_diag', get_string('ilp_mis_learner_skillsbuilder_date_completed_mat_diag', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_date_completed_mat_diagdesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_mat_diag_level', get_string('ilp_mis_learner_skillsbuilder_mat_diag_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_mat_diag_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_date_enrolled_ict', get_string('ilp_mis_learner_skillsbuilder_date_enrolled_ict', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_date_enrolled_ictdesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_date_completed_ict', get_string('ilp_mis_learner_skillsbuilder_date_completed_ict', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_date_completed_ictdesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_ict_diag_level', get_string('ilp_mis_learner_skillsbuilder_ict_diag_level', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_ict_diag_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_enrolled_to_site', get_string('ilp_mis_learner_skillsbuilder_enrolled_to_site', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_enrolled_to_sitedesc', 'block_ilp'), '');
*/


        $this->config_text_element($mform, 'mis_learner_skillsbuilder_assessment_stage',get_string('ilp_mis_learner_skillsbuilder_assessment_stage', 'block_ilp'),get_string('ilp_mis_learner_skillsbuilder_assessment_stagedesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_assessment_title',get_string('ilp_mis_learner_skillsbuilder_assessment_title', 'block_ilp'),get_string('ilp_mis_learner_skillsbuilder_assessment_titledesc', 'block_ilp'),'');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_overall_recommended_level', get_string('ilp_mis_learner_skillsbuilder_overall_recommended_level', 'block_ilp'),get_string('ilp_mis_learner_skillsbuilder_overall_recommended_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_assessment_section', get_string('ilp_mis_learner_skillsbuilder_assessment_section', 'block_ilp'),get_string('ilp_mis_learner_skillsbuilder_assessment_sectiondesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_section_recommended_level', get_string('ilp_mis_learner_skillsbuilder_section_recommended_level', 'block_ilp'),get_string('ilp_mis_learner_skillsbuilder_section_recommended_leveldesc', 'block_ilp'), '');
        $this->config_text_element($mform, 'mis_learner_skillsbuilder_status', get_string('ilp_mis_learner_skillsbuilder_assessment_status', 'block_ilp'),get_string('ilp_mis_learner_skillsbuilder_assessment_statusdesc', 'block_ilp'), '');



        $this->config_text_element($mform, 'mis_learner_skillsbuilder_prelimcalls', get_string('ilp_mis_learner_skillsbuilder_prelimcalls', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_prelimcallsdesc', 'block_ilp'), '');

        $options = array(
            ILP_DISABLED => get_string('disabled', 'block_ilp'),
            ILP_ENABLED => get_string('enabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_skillsbuilder_yearfilter', $options, get_string('ilp_mis_learner_skillsbuilder_yearfilter', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_yearfilterdesc', 'block_ilp'), 0);

        $this->config_text_element($mform, 'mis_learner_skillsbuilder_yearfilter_field', get_string('ilp_mis_learner_skillsbuilder_yearfilter_field', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_yearfilter_fielddesc', 'block_ilp'), 'year');

        $this->config_text_element($mform, 'mis_learner_skillsbuilder_yearfilter_year', get_string('ilp_mis_learner_skillsbuilder_yearfilter_year', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_yearfilter_yeardesc', 'block_ilp'), date('Y'));


        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_skillsbuilder_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_skillsbuilder_tabletype', $options, get_string('ilp_mis_learner_skillsbuilder_tabletype', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_tabletypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_learner_skillsbuilder_pluginstatus', $options, get_string('ilp_mis_learner_skillsbuilder_pluginstatus', 'block_ilp'), get_string('ilp_mis_learner_skillsbuilder_pluginstatusdesc', 'block_ilp'), 0);
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
        if (!empty(static::$skillsbuilder_data) || static::$skillsbuilder_data === false) {
            // Reload from static if set_data has been previously called.
            $this->data = static::$skillsbuilder_data;
            $this->fields = static::$skillsbuilder_fields;
        } else {
            $this->mis_user_id = $mis_user_id;

            $table = get_config('block_ilp', 'mis_learner_skillsbuilder_table');

            if (!empty($table)) {

                $sidfield = get_config('block_ilp', 'mis_learner_skillsbuilder_studentid');

                //is the id a string or a int
                $idtype = get_config('block_ilp', 'mis_learner_skillsbuilder_idtype');
                $mis_user_id = (empty($idtype)) ? "'$mis_user_id'" : $mis_user_id;

                $keyfields = array();

                $useyearfilter = get_config('block_ilp', 'mis_learner_skillsbuilder_yearfilter');

                if (!empty($useyearfilter)) {

                    $yearfilterfield = get_config('block_ilp', 'mis_learner_skillsbuilder_yearfilter_field');
                    $yearfilteryear = get_config('block_ilp', 'mis_learner_skillsbuilder_yearfilter_year');

                    $keyfields[$yearfilterfield] = array('=' => $yearfilteryear);
                }

                //create the key that will be used in sql query
                $keyfields[$sidfield] = array('=' => $mis_user_id);


                //check if the forskills id config has been set and pass the value
            //    if (get_config('block_ilp', 'mis_learner_skillsbuilder_forskills_id')) $this->fields['forskills id'] = get_config('block_ilp', 'mis_learner_skillsbuilder_forskills_id');

                //check if the first name config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_first_name')) $this->fields['first name'] = get_config('block_ilp', 'mis_learner_skillsbuilder_first_name');

                //check if the last name config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_last_name')) $this->fields['last name'] = get_config('block_ilp', 'mis_learner_skillsbuilder_last_name');

                //check if the learner ref config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_learner_ref')) $this->fields['learner ref'] = get_config('block_ilp', 'mis_learner_skillsbuilder_learner_ref');

                //check if the date enrolled engish config has been set and pass the value
              /*  if (get_config('block_ilp', 'mis_learner_skillsbuilder_date_enrolled_engish')) $this->fields['date enrolled engish'] = get_config('block_ilp', 'mis_learner_skillsbuilder_date_enrolled_engish');

                //check if the date completed eng ia config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_eng_ia')) $this->fields['date completed eng ia'] = get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_eng_ia');

                //check if the eng ia level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_level')) $this->fields['eng ia level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_level');

                //check if the eng ia reading level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_reading_level')) $this->fields['eng ia reading level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_reading_level');

                //check if the eng ia punctuation level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_punctuation_level')) $this->fields['eng ia punctuation level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_punctuation_level');

                //check if the eng ia spelling level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_spelling_level')) $this->fields['eng ia spelling level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_spelling_level');

                //check if the eng ia grammar level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_grammar_level')) $this->fields['eng ia grammar level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_eng_ia_grammar_level');

                //check if the date enrolled maths config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_date_enrolled_maths')) $this->fields['date enrolled maths'] = get_config('block_ilp', 'mis_learner_skillsbuilder_date_enrolled_maths');

                //check if the date completed mat ia config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_mat_ia')) $this->fields['date completed mat ia'] = get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_mat_ia');

                //check if the mat ia level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_mat_ia_level')) $this->fields['mat ia level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_mat_ia_level');

                //check if the mat ia numer level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_mat_ia_numer_level')) $this->fields['mat ia numer level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_mat_ia_numer_level');

                //check if the mat ia hd level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_mat_ia_hd_level')) $this->fields['mat ia hd level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_mat_ia_hd_level');

                //check if the mat ia mss level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_mat_ia_mss_level')) $this->fields['mat ia mss level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_mat_ia_mss_level');

                //check if the date completed eng diag config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_eng_diag')) $this->fields['date completed eng diag'] = get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_eng_diag');

                //check if the eng diag level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_eng_diag_level')) $this->fields['eng diag level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_eng_diag_level');

                //check if the date completed mat diag config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_mat_diag')) $this->fields['date completed mat diag'] = get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_mat_diag');

                //check if the mat diag level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_mat_diag_level')) $this->fields['mat diag level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_mat_diag_level');

                //check if the date enrolled ict config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_date_enrolled_ict')) $this->fields['date enrolled ict'] = get_config('block_ilp', 'mis_learner_skillsbuilder_date_enrolled_ict');

                //check if the date completed ict config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_ict')) $this->fields['date completed ict'] = get_config('block_ilp', 'mis_learner_skillsbuilder_date_completed_ict');

                //check if the ict diag level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_ict_diag_level')) $this->fields['ict diag level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_ict_diag_level');

                //check if the enrolled to site config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_enrolled_to_site')) $this->fields['enrolled to site'] = get_config('block_ilp', 'mis_learner_skillsbuilder_enrolled_to_site');

               */

                //check if the eng diag level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_assessment_stage')) $this->fields['assessment stage'] = get_config('block_ilp', 'mis_learner_skillsbuilder_assessment_stage');

                //check if the date completed mat diag config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_assessment_title')) $this->fields['assessment title'] = get_config('block_ilp', 'mis_learner_skillsbuilder_assessment_title');

                //check if the mat diag level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_overall_recommended_level')) $this->fields['overall recommended level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_overall_recommended_level');

                //check if the date enrolled ict config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_assessment_section')) $this->fields['assessment section'] = get_config('block_ilp', 'mis_learner_skillsbuilder_assessment_section');

                //check if the date completed ict config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_section_recommended_level')) $this->fields['section recommended level'] = get_config('block_ilp', 'mis_learner_skillsbuilder_section_recommended_level');

                //check if the ict diag level config has been set and pass the value
                if (get_config('block_ilp', 'mis_learner_skillsbuilder_status')) $this->fields['status'] = get_config('block_ilp', 'mis_learner_skillsbuilder_status');


                $prelimdbcalls = get_config('block_ilp', 'mis_learner_skillsbuilder_prelimcalls');

                $this->data = $this->dbquery($table, $keyfields, $this->fields, null, $prelimdbcalls);

                //we only need the first record so pass it back
                //$this->data = (!empty($this->data)) ? array_shift($this->data) : $this->data;
                static::$skillsbuilder_data = $this->data;
                static::$skillsbuilder_fields = $this->fields;
            }
        }
    }

    public function get_data() {
        return $this->data;
    }


    /**
     *
     * @see ilp_mis_plugin::display()
     */
    function display()
    {

        global $CFG;

        if (!empty($this->data)) {


            $pluginoutput = '';
            $subjects_html = $this->generate_subjects_html();
            //buffer output
            ob_start();


            $misdata = $this->data;

            //call the html file for the plugin
            require($CFG->dirroot . '/blocks/ilp/plugins/mis/ilp_mis_learner_skillsbuilder.html');
            $pluginoutput .= ob_get_contents();
            ob_end_clean();

            return $pluginoutput;
        } else {
            echo '<div id="plugin_nodata">' . get_string('nodataornoconfig', 'block_ilp') . '</div>';
        }
    }

    public function generate_subjects_html() {
        $field_ids = $this->fields;

        $out = '';

        $english_assessments = array();
        $maths_assessments = array();
        $ict_assessments = array();

        $records = $this->data;

        // separate assessments
        foreach ($records as $record){

            if ($record[$field_ids['assessment title']] == 'English Assessment'){
                $english_assessments[] = $record;

            } else if ($record[$field_ids['assessment title']] == 'Maths Assessment'){
                 $maths_assessments[] = $record;

            } else if ($record[$field_ids['assessment title']] == 'ICT Assessment'){
                 $ict_assessments[] = $record;

            }
        }

        // retrieve tables
        $englishtable = $this->generate_assessment_table($english_assessments);
        $mathstable = $this->generate_assessment_table($maths_assessments);
        $icttable = $this->generate_assessment_table($ict_assessments);


        if ($englishtable ) {
            $out .= html_writer::tag('h2',get_string('ilp_mis_learner_skillsbuilder_english_assessment', 'block_ilp'));

            $resulthtml = html_writer::tag('span', get_string('ilp_mis_learner_skillsbuilder_result', 'block_ilp'),
                array('class'=>'skillsbuilder_result'));
            $overall_result = $english_assessments[0][$field_ids['overall recommended level']];
            $overall_result = html_writer::tag('span', $overall_result, array('class'=>'overall_result'));
            $resulthtml .= html_writer::tag('span', get_string('ilp_mis_learner_skillsbuilder_youareoverall', 'block_ilp') . ' ' . $overall_result,
                array('class'=>'skills_builder_youareoverall'));
            if ($this->data[0][$field_ids['overall recommended level']]) {
            $englishtable .= html_writer::tag('div', $resulthtml, array('class'=>'result_container'));
            }

            $out .= html_writer::tag('div', $englishtable, array('class'=>'skillsbuilder_subjects_table_holder'));
        }

        if ($mathstable ) {
            $out .= html_writer::tag('h2',get_string('ilp_mis_learner_skillsbuilder_maths_assessment', 'block_ilp'));

            $resulthtml = html_writer::tag('span', get_string('ilp_mis_learner_skillsbuilder_result', 'block_ilp'),
                array('class'=>'skillsbuilder_result'));
            $overall_result = $maths_assessments[0][$field_ids['overall recommended level']];
            $overall_result = html_writer::tag('span', $overall_result, array('class'=>'overall_result'));
            $resulthtml .= html_writer::tag('span', get_string('ilp_mis_learner_skillsbuilder_youareoverall', 'block_ilp') . ' ' . $overall_result,
                array('class'=>'skills_builder_youareoverall'));
            if ($this->data[0][$field_ids['overall recommended level']]) {
                $mathstable .= html_writer::tag('div', $resulthtml, array('class'=>'result_container'));
            }

            $out .= html_writer::tag('div', $mathstable, array('class'=>'skillsbuilder_subjects_table_holder'));
        }

        if ($icttable ) {
            $out .= html_writer::tag('h2',get_string('ilp_mis_learner_skillsbuilder_ict_assessment', 'block_ilp'));

            $resulthtml = html_writer::tag('span', get_string('ilp_mis_learner_skillsbuilder_result', 'block_ilp'),
                array('class'=>'skillsbuilder_result'));
            $overall_result = $ict_assessments[0][$field_ids['overall recommended level']];
            $overall_result = html_writer::tag('span', $overall_result, array('class'=>'overall_result'));
            $resulthtml .= html_writer::tag('span', get_string('ilp_mis_learner_skillsbuilder_youareoverall', 'block_ilp') . ' ' . $overall_result,
                array('class'=>'skills_builder_youareoverall'));
            if ($this->data[0][$field_ids['overall recommended level']]) {
                $icttable .= html_writer::tag('div', $resulthtml, array('class'=>'result_container'));
            }

            $out .= html_writer::tag('div', $icttable, array('class'=>'skillsbuilder_subjects_table_holder'));
        }

        return $out;
    }


    public function generate_assessment_table($assessments) {

        $table = '';
        $field_ids = $this->fields;
        $datarows = '';

        $table .= '<table class="subject"><tbody>';
        $heading = html_writer::tag('th',get_string('ilp_mis_learner_skillsbuilder_assessment_section', 'block_ilp'));
        $heading .= html_writer::tag('th',get_string('ilp_mis_learner_skillsbuilder_section_recommended_level', 'block_ilp'));
        $heading .= html_writer::tag('th',get_string('ilp_mis_learner_skillsbuilder_assessment_status', 'block_ilp'));
        $table .= html_writer::tag('tr', $heading);

        foreach ($assessments as $assessment){
             $datarowcell = '';
             $datarowcell .= html_writer::tag('td', $assessment[$field_ids['assessment section']]);
             $datarowcell .= html_writer::tag('td', $assessment[$field_ids['section recommended level']]);
             $datarowcell .= html_writer::tag('td', $assessment[$field_ids['status']]);

             $datarows .= html_writer::tag('tr', $datarowcell);
        }

        $table .= $datarows;
        $table .= '</tbody></table>';

        return $table;
    }




    static function language_strings(&$string)
    {

        $string['ilp_mis_learner_skillsbuilder_english_assessment'] = 'English Assessment';
        $string['ilp_mis_learner_skillsbuilder_maths_assessment'] = 'Maths Assessment';
        $string['ilp_mis_learner_skillsbuilder_ict_assessment'] = 'ICT Assessment';

       // $string['ilp_mis_learner_skillsbuilder_section'] = 'Section';
       // $string['ilp_mis_learner_skillsbuilder_workingtowards'] = 'Working Towards';

      /*  $string['ilp_mis_learner_skillsbuilder_first_subject_skill_1'] = 'Reading';
        $string['ilp_mis_learner_skillsbuilder_first_subject_skill_2'] = 'Punctuation';
        $string['ilp_mis_learner_skillsbuilder_first_subject_skill_3'] = 'Grammar';
        $string['ilp_mis_learner_skillsbuilder_first_subject_skill_4'] = 'Spelling';

        $string['ilp_mis_learner_skillsbuilder_second_subject_skill_1'] = 'Number';
        $string['ilp_mis_learner_skillsbuilder_second_subject_skill_2'] = 'MSS';
        $string['ilp_mis_learner_skillsbuilder_second_subject_skill_3'] = 'Handling Data';*/

        $string['ilp_mis_learner_skillsbuilder_result'] = 'Result';
        $string['ilp_mis_learner_skillsbuilder_youareoverall'] = 'You are <span class="skills_builder_overall">overall</span> working towards';

        $string['ilp_mis_learner_skillsbuilder_table'] = 'Database table';
        $string['ilp_mis_learner_skillsbuilder_tabledesc'] = 'The name of the database table where the data for this plugin is held';

        $string['ilp_mis_learner_skillsbuilder_studentid'] = 'Student Id field';
        $string['ilp_mis_learner_skillsbuilder_studentiddesc'] = 'The id field used to find the student data in the database table';

        $string['ilp_mis_learner_skillsbuilder_tabletype'] = 'Table type';
        $string['ilp_mis_learner_skillsbuilder_tabletypedesc'] = 'Does this plugin connect to a table or stored procedure';

        $string['ilp_mis_learner_skillsbuilder_pluginstatus'] = 'Status';
        $string['ilp_mis_learner_skillsbuilder_pluginstatusdesc'] = 'Is the block enabled or disabled';

        $string['ilp_mis_learner_skillsbuilder_prelimcalls'] = 'Preliminary db calls';
        $string['ilp_mis_learner_skillsbuilder_prelimcallsdesc'] = 'preliminary calls that need to be made to the db before the sql is executed';

        $string['ilp_mis_learner_skillsbuilder_yearfilter'] = 'Year filter';
        $string['ilp_mis_learner_skillsbuilder_yearfilterdesc'] = 'Is a year filter used when selecting data from the MIS';

        $string['ilp_mis_learner_skillsbuilder_yearfilter_field'] = 'Year filter field';
        $string['ilp_mis_learner_skillsbuilder_yearfilter_fielddesc'] = 'If a MIS year filter is being used enter the field that will be filter on. (if stored procedure and field not needed leave field as year)';

        $string['ilp_mis_learner_skillsbuilder_yearfilter_year'] = 'Year filter date';
        $string['ilp_mis_learner_skillsbuilder_yearfilter_yeardesc'] = 'The date that will be filtered on';

        $string['ilp_mis_learner_skillsbuilder_pluginname'] = 'Skills Builder';
        $string['ilp_mis_learner_skillsbuilder_pluginnamesettings'] = 'Skills Builder configuration';


       // $string['ilp_mis_learner_skillsbuilder_forskills_id'] = 'ForSkills ID data field';
        //$string['ilp_mis_learner_skillsbuilder_forskills_iddesc'] = 'The field that holds ForSkills ID data';

        $string['ilp_mis_learner_skillsbuilder_first_name'] = 'First Name data field';
        $string['ilp_mis_learner_skillsbuilder_first_namedesc'] = 'The field that holds First Name data';

        $string['ilp_mis_learner_skillsbuilder_last_name'] = 'Last Name data field';
        $string['ilp_mis_learner_skillsbuilder_last_namedesc'] = 'The field that holds Last Name data';

        $string['ilp_mis_learner_skillsbuilder_assessment_stage'] = 'Assessment Stage';
        $string['ilp_mis_learner_skillsbuilder_assessment_stagedesc'] = 'The field that holds Assessment Stage data';

        $string['ilp_mis_learner_skillsbuilder_assessment_title'] = 'Assessment Title';
        $string['ilp_mis_learner_skillsbuilder_assessment_titledesc'] = 'The field that holds Assessment Title data';

        $string['ilp_mis_learner_skillsbuilder_overall_recommended_level'] = 'Overall Recommended Level';
        $string['ilp_mis_learner_skillsbuilder_overall_recommended_leveldesc'] = 'The field that holds Overall Recommended Level data';

        $string['ilp_mis_learner_skillsbuilder_assessment_section'] = 'Assessment Section';
        $string['ilp_mis_learner_skillsbuilder_assessment_sectiondesc'] = 'The field that holds Assessment Section data';

        $string['ilp_mis_learner_skillsbuilder_section_recommended_level'] = 'Section Recommended Level';
        $string['ilp_mis_learner_skillsbuilder_section_recommended_leveldesc'] = 'The field that holds Section Recommended Level data';

        $string['ilp_mis_learner_skillsbuilder_assessment_status'] = 'Assessment Status';
        $string['ilp_mis_learner_skillsbuilder_assessment_statusdesc'] = 'The field that holds Assessment Status data';









        // $string['ilp_mis_learner_skillsbuilder_learner_ref'] = 'Learner Ref data field';
        //$string['ilp_mis_learner_skillsbuilder_learner_refdesc'] = 'The field that holds Learner Ref data';

       /* $string['ilp_mis_learner_skillsbuilder_date_enrolled_engish'] = 'Date Enrolled Engish data field';
        $string['ilp_mis_learner_skillsbuilder_date_enrolled_engishdesc'] = 'The field that holds Date Enrolled Engish data';

        $string['ilp_mis_learner_skillsbuilder_date_completed_eng_ia'] = 'Date Completed Eng IA data field';
        $string['ilp_mis_learner_skillsbuilder_date_completed_eng_iadesc'] = 'The field that holds Date Completed Eng IA data';

        $string['ilp_mis_learner_skillsbuilder_eng_ia_level'] = 'Eng IA Level data field';
        $string['ilp_mis_learner_skillsbuilder_eng_ia_leveldesc'] = 'The field that holds Eng IA Level data';

        $string['ilp_mis_learner_skillsbuilder_eng_ia_reading_level'] = 'Eng IA Reading Level data field';
        $string['ilp_mis_learner_skillsbuilder_eng_ia_reading_leveldesc'] = 'The field that holds Eng IA Reading Level data';

        $string['ilp_mis_learner_skillsbuilder_eng_ia_punctuation_level'] = 'Eng IA Punctuation Level data field';
        $string['ilp_mis_learner_skillsbuilder_eng_ia_punctuation_leveldesc'] = 'The field that holds Eng IA Punctuation Level data';

        $string['ilp_mis_learner_skillsbuilder_eng_ia_spelling_level'] = 'Eng IA Spelling Level data field';
        $string['ilp_mis_learner_skillsbuilder_eng_ia_spelling_leveldesc'] = 'The field that holds Eng IA Spelling Level data';

        $string['ilp_mis_learner_skillsbuilder_eng_ia_grammar_level'] = 'Eng IA Grammar Level data field';
        $string['ilp_mis_learner_skillsbuilder_eng_ia_grammar_leveldesc'] = 'The field that holds Eng IA Grammar Level data';

        $string['ilp_mis_learner_skillsbuilder_date_enrolled_maths'] = 'Date Enrolled Maths data field';
        $string['ilp_mis_learner_skillsbuilder_date_enrolled_mathsdesc'] = 'The field that holds Date Enrolled Maths data';

        $string['ilp_mis_learner_skillsbuilder_date_completed_mat_ia'] = 'Date Completed Mat IA data field';
        $string['ilp_mis_learner_skillsbuilder_date_completed_mat_iadesc'] = 'The field that holds Date Completed Mat IA data';

        $string['ilp_mis_learner_skillsbuilder_mat_ia_level'] = 'Mat IA Level data field';
        $string['ilp_mis_learner_skillsbuilder_mat_ia_leveldesc'] = 'The field that holds Mat IA Level data';

        $string['ilp_mis_learner_skillsbuilder_mat_ia_numer_level'] = 'Mat IA Numer Level data field';
        $string['ilp_mis_learner_skillsbuilder_mat_ia_numer_leveldesc'] = 'The field that holds Mat IA Numer Level data';

        $string['ilp_mis_learner_skillsbuilder_mat_ia_hd_level'] = 'Mat IA HD Level data field';
        $string['ilp_mis_learner_skillsbuilder_mat_ia_hd_leveldesc'] = 'The field that holds Mat IA HD Level data';

        $string['ilp_mis_learner_skillsbuilder_mat_ia_mss_level'] = 'Mat IA MSS Level data field';
        $string['ilp_mis_learner_skillsbuilder_mat_ia_mss_leveldesc'] = 'The field that holds Mat IA MSS Level data';

        $string['ilp_mis_learner_skillsbuilder_date_completed_eng_diag'] = 'Date Completed Eng Diag data field';
        $string['ilp_mis_learner_skillsbuilder_date_completed_eng_diagdesc'] = 'The field that holds Date Completed Eng Diag data';

        $string['ilp_mis_learner_skillsbuilder_eng_diag_level'] = 'Eng Diag Level data field';
        $string['ilp_mis_learner_skillsbuilder_eng_diag_leveldesc'] = 'The field that holds Eng Diag Level data';

        $string['ilp_mis_learner_skillsbuilder_date_completed_mat_diag'] = 'Date Completed Mat Diag data field';
        $string['ilp_mis_learner_skillsbuilder_date_completed_mat_diagdesc'] = 'The field that holds Date Completed Mat Diag data';

        $string['ilp_mis_learner_skillsbuilder_mat_diag_level'] = 'Mat Diag Level data field';
        $string['ilp_mis_learner_skillsbuilder_mat_diag_leveldesc'] = 'The field that holds Mat Diag Level data';

        $string['ilp_mis_learner_skillsbuilder_date_enrolled_ict'] = 'Date Enrolled ICT data field';
        $string['ilp_mis_learner_skillsbuilder_date_enrolled_ictdesc'] = 'The field that holds Date Enrolled ICT data';

        $string['ilp_mis_learner_skillsbuilder_date_completed_ict'] = 'Date Completed ICT data field';
        $string['ilp_mis_learner_skillsbuilder_date_completed_ictdesc'] = 'The field that holds Date Completed ICT data';

        $string['ilp_mis_learner_skillsbuilder_ict_diag_level'] = 'ICT Diag Level data field';
        $string['ilp_mis_learner_skillsbuilder_ict_diag_leveldesc'] = 'The field that holds ICT Diag Level data';

        $string['ilp_mis_learner_skillsbuilder_enrolled_to_site'] = 'Enrolled to site data field';
        $string['ilp_mis_learner_skillsbuilder_enrolled_to_sitedesc'] = 'The field that holds Enrolled to site data';
           $string['ilp_mis_learner_skillsbuilder_disp_tabname'] = 'Skills Builder';
        $string['ilp_mis_learner_skillsbuilder_disp_forskills_id'] = 'ForSkills ID';
        $string['ilp_mis_learner_skillsbuilder_disp_first_name'] = 'First Name';
        $string['ilp_mis_learner_skillsbuilder_disp_last_name'] = 'Last Name';
        $string['ilp_mis_learner_skillsbuilder_disp_learner_ref'] = 'Learner Ref';
        $string['ilp_mis_learner_skillsbuilder_disp_date_enrolled_engish'] = 'Date Enrolled Engish';
        $string['ilp_mis_learner_skillsbuilder_disp_date_completed_eng_ia'] = 'Date Completed Eng IA';
        $string['ilp_mis_learner_skillsbuilder_disp_eng_ia_level'] = 'Eng IA Level';
        $string['ilp_mis_learner_skillsbuilder_disp_eng_ia_reading_level'] = 'Eng IA Reading Level';
        $string['ilp_mis_learner_skillsbuilder_disp_eng_ia_punctuation_level'] = 'Eng IA Punctuation Level';
        $string['ilp_mis_learner_skillsbuilder_disp_eng_ia_spelling_level'] = 'Eng IA Spelling Level';
        $string['ilp_mis_learner_skillsbuilder_disp_eng_ia_grammar_level'] = 'Eng IA Grammar Level';
        $string['ilp_mis_learner_skillsbuilder_disp_date_enrolled_maths'] = 'Date Enrolled Maths';
        $string['ilp_mis_learner_skillsbuilder_disp_date_completed_mat_ia'] = 'Date Completed Mat IA';
        $string['ilp_mis_learner_skillsbuilder_disp_mat_ia_level'] = 'Mat IA Level';
        $string['ilp_mis_learner_skillsbuilder_disp_mat_ia_numer_level'] = 'Mat IA Numer Level';
        $string['ilp_mis_learner_skillsbuilder_disp_mat_ia_hd_level'] = 'Mat IA HD Level';
        $string['ilp_mis_learner_skillsbuilder_disp_mat_ia_mss_level'] = 'Mat IA MSS Level';
        $string['ilp_mis_learner_skillsbuilder_disp_date_completed_eng_diag'] = 'Date Completed Eng Diag';
        $string['ilp_mis_learner_skillsbuilder_disp_eng_diag_level'] = 'Eng Diag Level';
        $string['ilp_mis_learner_skillsbuilder_disp_date_completed_mat_diag'] = 'Date Completed Mat Diag';
        $string['ilp_mis_learner_skillsbuilder_disp_mat_diag_level'] = 'Mat Diag Level';
        $string['ilp_mis_learner_skillsbuilder_disp_date_enrolled_ict'] = 'Date Enrolled ICT';
        $string['ilp_mis_learner_skillsbuilder_disp_date_completed_ict'] = 'Date Completed ICT';
        $string['ilp_mis_learner_skillsbuilder_disp_ict_diag_level'] = 'ICT Diag Level';
        $string['ilp_mis_learner_skillsbuilder_disp_enrolled_to_site'] = 'Enrolled to site';*/
    }

    static function plugin_type()
    {
        return 'skillsbuilder';
    }


    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return 'Skills Builder';
    }
}

?>