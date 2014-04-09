<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

class ilp_mis_learner_profile_care_support extends ilp_mis_plugin
{

    protected $fields;
    protected $mis_user_id;
    protected $user_id;

    /**
     *
     * Constructor for the class
     * @param array $params should hold any vars that are needed by plugin. can also hold the
     *             the connection string vars if they are different from those specified
     *             in the mis connection
     */

    function  __construct($params = array())
    {
        parent::__construct($params);

        $this->tabletype = get_config('block_ilp', 'mis_learner_care_support_tabletype');
        $this->fields = array();
    }

    /**
     *
     * @see ilp_mis_plugin::display()
     */
    function display()
    {
        global $CFG;

        if (!empty($this->data)) {

            //get the moodle user record of the user
            $user = $this->dbc->get_user_by_id($this->user_id);

            //buffer output
            ob_start();

            //call the html file
            require_once($CFG->dirroot . '/blocks/ilp/plugins/mis/ilp_mis_learner_profile_care_support.html');

            $pluginoutput = ob_get_contents();

            ob_end_clean();

            return $pluginoutput;


        } else {
            if ($msg = get_string('nodataornoconfig', 'block_ilp')) {
                echo '<div id="plugin_nodata">' . $msg . '</div>';
            }
        }

    }

    /**
     * Retrieves data from the mis
     *
     * @param  $mis_user_id  the id of the user in the mis used to retrieve the data of the user
     * @param  $user_id    the id of the user in moodle
     *
     * @return  null
     */


    public function set_data($mis_user_id, $user_id = NULL)
    {

        //this check is in place as we have to make sure the userid is populated
        if (empty($user_id)) return false;

        $this->mis_user_id = $mis_user_id;
        $this->user_id = $user_id;

        $table = get_config('block_ilp', 'mis_learner_care_support_table');

        if (!empty($table)) {

            $sidfield = get_config('block_ilp', 'mis_learner_care_support_studentid');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_learner_care_support_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            $keyfields = array($sidfield => array('=' => $mis_user_id));

            $this->fields = array();

            if (get_config('block_ilp', 'mis_learner_care_support_studentid')) $this->fields['studentid'] = get_config('block_ilp', 'mis_learner_care_support_studentid');
            if (get_config('block_ilp', 'mis_learner_care_support_care')) $this->fields['care'] = get_config('block_ilp', 'mis_learner_care_support_care');
            if (get_config('block_ilp', 'mis_learner_care_support_careContact')) $this->fields['careContact'] = get_config('block_ilp', 'mis_learner_care_support_careContact');
            if (get_config('block_ilp', 'mis_learner_care_support_als')) $this->fields['als'] = get_config('block_ilp', 'mis_learner_care_support_als');
            if (get_config('block_ilp', 'mis_learner_care_support_lsf')) $this->fields['LSF'] = get_config('block_ilp', 'mis_learner_care_support_lsf');
            if (get_config('block_ilp', 'mis_learner_care_support_medical')) $this->fields['Medical'] = get_config('block_ilp', 'mis_learner_care_support_medical');


            $prelimdbcalls = get_config('block_ilp', 'mis_learner_care_support_prelimcalls');

            $data = $this->dbquery($table, $keyfields, $this->fields, null, $prelimdbcalls);
            // $data   =   $this->populate_from_usertable( array_shift( $data ) , $user_id );

            $data = (!empty($data)) ? array_shift($data) : false;
            $this->data = (!empty($data)) ? $data : false;

        }
    }


    /**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)
    {
        global $CFG;

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_profile_care_support&plugintype=mis">' . get_string('ilp_mis_learner_care_support_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_learner_care_support', '', $link));
    }

    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_learner_care_support_table', get_string('ilp_mis_learner_care_support_table', 'block_ilp'), get_string('ilp_mis_learner_care_support_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_care_support_prelimcalls', get_string('ilp_mis_learner_care_support_prelimcalls', 'block_ilp'), get_string('ilp_mis_learner_care_support_prelimcallsdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_care_support_studentid', get_string('ilp_mis_learner_care_support_studentid', 'block_ilp'), get_string('ilp_mis_learner_care_support_studentiddesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_care_support_care', get_string('ilp_mis_learner_care_support_care', 'block_ilp'), get_string('ilp_mis_learner_care_support_caredesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_care_support_careContact', get_string('ilp_mis_learner_care_support_careContact', 'block_ilp'), get_string('ilp_mis_learner_care_support_careContactdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_care_support_als', get_string('ilp_mis_learner_care_support_als', 'block_ilp'), get_string('ilp_mis_learner_care_support_alsdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_care_support_lsf', get_string('ilp_mis_learner_care_support_lsf', 'block_ilp'), get_string('ilp_mis_learner_care_support_lsfdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_care_support_medical', get_string('ilp_mis_learner_care_support_medical', 'block_ilp'), get_string('ilp_mis_learner_care_support_medicaldesc', 'block_ilp'), '');


        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_care_support_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_care_support_tabletype', $options, get_string('ilp_mis_learner_care_support_tabletype', 'block_ilp'), get_string('ilp_mis_learner_care_support_tabletypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_learner_profile_care_support_pluginstatus', $options, get_string('ilp_mis_learner_profile_care_support_pluginstatus', 'block_ilp'), get_string('ilp_mis_learner_profile_care_support_pluginstatusdesc', 'block_ilp'), 0);

    }

    /**
     * Adds the string values from the tab to the language file
     *
     * @param  array &$string the language strings array passed by reference so we
     * just need to simply add the plugins entries on to it
     * @return array
     */
    static function language_strings(&$string)
    {

        $string['ilp_mis_learner_care_support_pluginname'] = ' Care & Support';

        $string['ilp_mis_learner_care_support_pluginnamesettings'] = 'Care & Support Configuration';

        $string['ilp_mis_learner_care_support_table'] = 'MIS table';
        $string['ilp_mis_learner_care_support_tabledesc'] = 'The table in the MIS where the data for this plugin will be retrieved from';

        $string['ilp_mis_learner_care_support_studentid'] = 'Student ID field';
        $string['ilp_mis_learner_care_support_studentiddesc'] = 'The field that will be used to find the student';

        $string['ilp_mis_learner_care_support_care'] = 'Care';
        $string['ilp_mis_learner_care_support_caredesc'] = 'The field that holds care data';

        $string['ilp_mis_learner_care_support_careContact'] = 'Care Contact';
        $string['ilp_mis_learner_care_support_careContactdesc'] = 'The field that holds care contact data';

        $string['ilp_mis_learner_care_support_als'] = 'ALS';
        $string['ilp_mis_learner_care_support_alsdesc'] = 'The field that holds employer contact name';

        $string['ilp_mis_learner_care_support_lsf'] = 'Bursary/LSF';
        $string['ilp_mis_learner_care_support_lsfdesc'] = 'The field that holds bursary/LSF data';

        $string['ilp_mis_learner_care_support_medical'] = 'Medical';
        $string['ilp_mis_learner_care_support_medicaldesc'] = 'The field that holds Medical data';

        $string['ilp_mis_learner_care_support_tabletype'] = 'Table type';
        $string['ilp_mis_learner_care_support_tabletypedesc'] = 'Does this plugin connect to a table or stored procedure';

        $string['ilp_mis_learner_profile_care_support_pluginstatus'] = 'Status';
        $string['ilp_mis_learner_profile_care_support_pluginstatusdesc'] = 'Is the block enabled or disabled';


        //$string['ilp_mis_learner_profile_contact_disp_personal']            = 'Personal';
        //$string['ilp_mis_learner_profile_contact_disp_contact']          = 'Contact';
        //$string['ilp_mis_learner_profile_contact_disp_address']          = 'Address';
        $string['ilp_mis_learner_profile_care_support_disp_studentid'] = 'Student ID  ';
        $string['ilp_mis_learner_profile_care_support_disp_care'] = 'Care  ';
        $string['ilp_mis_learner_profile_care_support_disp_careContact'] = 'Care Contact  ';
        $string['ilp_mis_learner_profile_care_support_disp_als'] = 'ALS  ';
        $string['ilp_mis_learner_profile_care_support_disp_lsf'] = 'Bursary/LSF  ';
        $string['ilp_mis_learner_profile_care_support_disp_medical'] = 'Medical  ';

        $string['ilp_mis_learner_care_support_prelimcalls'] = 'Preliminary db calls';
        $string['ilp_mis_learner_care_support_prelimcallsdesc'] = 'preliminary calls that need to be made to the db before the sql is executed';
        $string['ilp_mis_learner_profile_care_support_tab_name'] = 'Care & Support';


        return $string;
    }

    public static function plugin_type()
    {
        return 'learnerprofile';
    }

    /**
     * This function is used if the plugin is displayed in the tab menu.
     * Do not use a menu string in this function as it will cause errors
     *
     */
    function tab_name()
    {
        return get_string('ilp_mis_learner_profile_care_support_tab_name', 'block_ilp');
    }

}

?>