<?php
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

class ilp_mis_learner_profile_employer_contact extends ilp_mis_plugin
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

        $this->tabletype = get_config('block_ilp', 'mis_learner_employer_contact_tabletype');
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
            require_once($CFG->dirroot . '/blocks/ilp/plugins/mis/ilp_mis_learner_profile_employer_contact.html');

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

        $table = get_config('block_ilp', 'mis_learner_employer_contact_table');

        if (!empty($table)) {

            $sidfield = get_config('block_ilp', 'mis_learner_employer_contact_studentid');

            //is the id a string or a int
            $idtype = get_config('block_ilp', 'mis_learner_employer_contact_idtype');
            $mis_user_id = (empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;

            $keyfields = array($sidfield => array('=' => $mis_user_id));

            $this->fields = array();

            if (get_config('block_ilp', 'mis_learner_employer_contact_studentid')) $this->fields['studentid'] = get_config('block_ilp', 'mis_learner_employer_contact_studentid');
            if (get_config('block_ilp', 'mis_learner_employer_contact_companyNameEmp')) $this->fields['companyNameEmp'] = get_config('block_ilp', 'mis_learner_employer_contact_companyNameEmp');
            if (get_config('block_ilp', 'mis_learner_employer_contact_addrEmp')) $this->fields['addrEmp'] = get_config('block_ilp', 'mis_learner_employer_contact_addrEmp');
            if (get_config('block_ilp', 'mis_learner_employer_contact_contactNameEmp')) $this->fields['contactNameEmp'] = get_config('block_ilp', 'mis_learner_employer_contact_contactNameEmp');
            if (get_config('block_ilp', 'mis_learner_employer_contact_emailEmp')) $this->fields['emailEmp'] = get_config('block_ilp', 'mis_learner_employer_contact_emailEmp');
            if (get_config('block_ilp', 'mis_learner_employer_contact_phoneEmp')) $this->fields['phoneEmp'] = get_config('block_ilp', 'mis_learner_employer_contact_phoneEmp');


            $prelimdbcalls = get_config('block_ilp', 'mis_learner_employer_contact_prelimcalls');

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

        $link = '<a href="' . $CFG->wwwroot . '/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_profile_employer_contact&plugintype=mis">' . get_string('ilp_mis_learner_employer_contact_pluginnamesettings', 'block_ilp') . '</a>';
        $settings->add(new admin_setting_heading('block_ilp_mis_learner_employer_contact', '', $link));
    }

    /**
     * Adds config settings for the plugin to the given mform
     * @see ilp_plugin::config_form()
     */
    function config_form(&$mform)
    {

        $this->config_text_element($mform, 'mis_learner_employer_contact_table', get_string('ilp_mis_learner_employer_contact_table', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_tabledesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_employer_contact_prelimcalls', get_string('ilp_mis_learner_employer_contact_prelimcalls', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_prelimcallsdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_employer_contact_studentid', get_string('ilp_mis_learner_employer_contact_studentid', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_studentiddesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_employer_contact_companyNameEmp', get_string('ilp_mis_learner_employer_contact_companyNameEmp', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_companyNameEmpdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_employer_contact_addrEmp', get_string('ilp_mis_learner_employer_contact_addrEmp', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_addrEmpdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_employer_contact_contactNameEmp', get_string('ilp_mis_learner_employer_contact_contactNameEmp', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_contactNameEmpdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_employer_contact_emailEmp', get_string('ilp_mis_learner_employer_contact_emailEmp', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_emailEmpdesc', 'block_ilp'), '');

        $this->config_text_element($mform, 'mis_learner_employer_contact_phoneEmp', get_string('ilp_mis_learner_employer_contact_phoneEmp', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_phoneEmpdesc', 'block_ilp'), '');


        $options = array(
            ILP_IDTYPE_STRING => get_string('stringid', 'block_ilp'),
            ILP_IDTYPE_INT => get_string('intid', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_employer_contact_idtype', $options, get_string('idtype', 'block_ilp'), get_string('idtypedesc', 'block_ilp'), 1);


        $options = array(
            ILP_MIS_TABLE => get_string('table', 'block_ilp'),
            ILP_MIS_STOREDPROCEDURE => get_string('storedprocedure', 'block_ilp')
        );

        $this->config_select_element($mform, 'mis_learner_employer_contact_tabletype', $options, get_string('ilp_mis_learner_employer_contact_tabletype', 'block_ilp'), get_string('ilp_mis_learner_employer_contact_tabletypedesc', 'block_ilp'), 1);

        $options = array(
            ILP_ENABLED => get_string('enabled', 'block_ilp'),
            ILP_DISABLED => get_string('disabled', 'block_ilp')
        );

        $this->config_select_element($mform, 'ilp_mis_learner_profile_employer_contact_pluginstatus', $options, get_string('ilp_mis_learner_profile_employer_contact_pluginstatus', 'block_ilp'), get_string('ilp_mis_learner_profile_employer_contact_pluginstatusdesc', 'block_ilp'), 0);

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

        $string['ilp_mis_learner_employer_contact_pluginname'] = ' Employer Contact';

        $string['ilp_mis_learner_employer_contact_pluginnamesettings'] = 'Employer Contact Configuration';

        $string['ilp_mis_learner_employer_contact_table'] = 'MIS table';
        $string['ilp_mis_learner_employer_contact_tabledesc'] = 'The table in the MIS where the data for this plugin will be retrieved from';

        $string['ilp_mis_learner_employer_contact_studentid'] = 'Student ID field';
        $string['ilp_mis_learner_employer_contact_studentiddesc'] = 'The field that will be used to find the student';

        $string['ilp_mis_learner_employer_contact_companyNameEmp'] = 'Company Name';
        $string['ilp_mis_learner_employer_contact_companyNameEmpdesc'] = 'The field that holds employer company name data';

        $string['ilp_mis_learner_employer_contact_addrEmp'] = 'Address';
        $string['ilp_mis_learner_employer_contact_addrEmpdesc'] = 'The field that holds employer address data';

        $string['ilp_mis_learner_employer_contact_contactNameEmp'] = 'Contact Name';
        $string['ilp_mis_learner_employer_contact_contactNameEmpdesc'] = 'The field that holds employer contact name';

        $string['ilp_mis_learner_employer_contact_emailEmp'] = 'Email';
        $string['ilp_mis_learner_employer_contact_emailEmpdesc'] = 'The field that holds employer email id';

        $string['ilp_mis_learner_employer_contact_phoneEmp'] = 'Phone';
        $string['ilp_mis_learner_employer_contact_phoneEmpdesc'] = 'The field that holds employer phone number';

        $string['ilp_mis_learner_employer_contact_tabletype'] = 'Table type';
        $string['ilp_mis_learner_employer_contact_tabletypedesc'] = 'Does this plugin connect to a table or stored procedure';

        $string['ilp_mis_learner_profile_employer_contact_pluginstatus'] = 'Status';
        $string['ilp_mis_learner_profile_employer_contact_pluginstatusdesc'] = 'Is the block enabled or disabled';


        //$string['ilp_mis_learner_profile_contact_disp_personal']            = 'Personal';
        //$string['ilp_mis_learner_profile_contact_disp_contact']          = 'Contact';
        //$string['ilp_mis_learner_profile_contact_disp_address']          = 'Address';
        $string['ilp_mis_learner_profile_employer_contact_disp_studentid'] = 'Student ID';
        $string['ilp_mis_learner_profile_employer_contact_disp_companyNameEmp'] = 'Company Name ';
        $string['ilp_mis_learner_profile_employer_contact_disp_addrEmp'] = 'Address ';
        $string['ilp_mis_learner_profile_employer_contact_disp_contactNameEmp'] = 'Contact Name ';
        $string['ilp_mis_learner_profile_employer_contact_disp_emailEmp'] = 'Email';
        $string['ilp_mis_learner_profile_employer_contact_disp_phoneEmp'] = 'Phone';

        $string['ilp_mis_learner_employer_contact_prelimcalls'] = 'Preliminary db calls';
        $string['ilp_mis_learner_employer_contact_prelimcallsdesc'] = 'preliminary calls that need to be made to the db before the sql is executed';
        $string['ilp_mis_learner_profile_employer_contact_tab_name'] = 'Employer Contact';


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
        return get_string('ilp_mis_learner_profile_employer_contact_tab_name', 'block_ilp');
    }

}

?>