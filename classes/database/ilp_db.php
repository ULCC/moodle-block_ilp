<?php

/**
 * Databse classes for the ILP block module.
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_logging.class.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_report.class.php');

/**
 * Main database class, with functions to encode and decode stuff to and from the DB
 *
 * Acts as a wrapper for {@link ilp_db_functions} with a magic method to intercept
 * function calls.
 *
 */
class ilp_db {

    /**
     * Constructor to instantiate the db connection.
     *
     * @return \ilp_db
     */
    function __construct() {
        global $CFG;

        // include the static constants
        require_once($CFG->dirroot.'/blocks/ilp/lib.php');

        // instantiate the Assessment admin database
        $this->dbc = new ilp_db_functions();
    }

    /**
     * A PHP magic method that intercepts all calls to the database class and
     * encodes all the data being input.
     *
     * @param string $method The name of the method being called.
     * @param array $params The array of parameters passed to the method.
     * @return mixed The result of the query.
     */
    function __call($method, $params) {
        // Sanitise everything coming into the database here
        $params = $this->encode($params);

        // hand control to the ilp_db_functions()
        return call_user_func_array(array($this->dbc, $method), $params);
    }

    /**
     * Encodes mixed params before they are sent to the database.
     *
     * @param mixed $data The unencoded object/array/string/etc
     * @return mixed The encoded version
     */
    static function encode(&$data) {
        if(is_object($data) || is_array($data)) {
            // skip the ilp_flexible_table
            if(!is_a($data, 'ilp_flexible_table')) {
                foreach($data as $index => &$datum) {

                    //we will skip any index with the prefix binary
                    if (substr($index, 0,7) != 'binary_') {
                        $datum = ilp_db::encode($datum);
                    }
                }

            }
            return $data;
        } else {

            // decode any special characters prevent malicious code slipping through
            $data = ilp_db::decode_htmlchars($data, ENT_QUOTES);

            // purify all data (e.g. validate html, remove js and other bad stuff)
            $data = purify_html($data);

            // encode the purified string
            $data = trim(preg_replace('/\\\/', '&#92;', htmlentities($data, ENT_QUOTES, 'utf-8', false)));

            // convert the empty string into null as such values break nullable FK fields
            return ($data == '') ? null : $data;
        }
    }

    /**
     * Decodes mixed params.
     *
     * @param mixed The encoded object/array/string/etc
     * @return mixed The decoded version
     */
    static function decode(&$data) {
        if(is_object($data) || is_array($data)) {
            foreach($data as $index => &$datum) {
                //skip any fields with prefix binary_
                if (substr($index, 0,7) != 'binary_') {
                    $datum = ilp_db::decode($datum);
                }
            }
            return $data;
        } else {
            return html_entity_decode($data, ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Decodes mixed params.
     *
     * @param mixed $data The encoded object/array/string/etc
     * @return mixed The decoded version
     */
    static function decode_htmlchars(&$data) {
        if(is_object($data) || is_array($data)) {
            foreach($data as $index => &$datum) {
                //skip any fields with prefix binary_
                if (substr($index, 0,7) != 'binary_') {
                    $datum = ilp_db::decode_htmlchars($datum);
                }
            }
            return $data;
        } else {
            return str_replace(array('&quot;', '&#039;', '&lt;', '&gt;'), array('"', "'", '<', '>'), $data);
        }
    }
}



/**
 * Database class holding functions to actually perform the queries.
 *
 * This extends the logging class which intercepts all insert, update and delete
 * actions that are executed on the database and makes a record of what data was
 * changed. Instantiated as $dbc in the {@link ilp_db} class.
 */

class ilp_db_functions	extends ilp_logging {

    /**
     * The Moodle 2 database, or the emulator.
     *
     * @var moodle_database
     */
    var $dbc;

    /**
     * Constructor for the ilp_db_functions class
     *
     * @return \ilp_db_functions
     */
    function __construct() {
        global $CFG, $DB;

        $this->dbc = $DB;

        // include the static constants
        require_once($CFG->dirroot.'/blocks/ilp/constants.php');
    }

    /**
     * Returns the ilp_report_field record with the id given
     *
     * @param int    $reportfield_id the id of the report field that you want to get the data on
     * @return object containing data from the report field record that matches criteria
     */
    function get_report_field_data($reportfield_id) {
        return $this->dbc->get_record("block_ilp_report_field",array("id"=>$reportfield_id));
    }

    /**
     * @return array
     */
    public function null_position_reports() {
        $query = 'SELECT * FROM {block_ilp_report} WHERE position is null AND deleted = 0';
        return $this->dbc->get_records_sql($query);
    }

    /**
     * @param $expected_min_position
     * @return bool
     */
    public function report_position_sequence_is_continuous($expected_min_position) {
        $query = 'SELECT * FROM {block_ilp_report} WHERE position is not null AND deleted = 0 ORDER BY position';
        $reports =  $this->dbc->get_records_sql($query);
        foreach ($reports as $report) {
            if ($report->position != $expected_min_position) {
                return false;
            }
            $expected_min_position ++;
        }
        return true;
    }

    /**
     * @param $report_id
     * @param $expected_min_position
     * @return bool
     */
    public function report_field_position_sequence_is_continuous($report_id, $expected_min_position) {
        $query = 'SELECT label, position FROM {block_ilp_report_field} r WHERE report_id = ' . $report_id . ' ORDER BY r.position';
        $fields =  $this->dbc->get_records_sql($query);
        foreach ($fields as $field) {
            if ($field->position != $expected_min_position) {
                return false;
            }
            $expected_min_position ++;
        }
        return true;
    }

    /**
     * @param $position
     */
    public function report_position_resequence($position) {
        $query = 'SELECT * FROM {block_ilp_report} WHERE position is not null AND deleted = 0 ORDER BY position';
        $reports =  $this->dbc->get_records_sql($query);
        foreach ($reports as $report) {
            $report->position = $position;
            $this->dbc->update_record('block_ilp_report', $report);
            $position ++;
        }
    }

    /**
     * @param $report_id
     * @param $position
     */
    public function report_field_position_resequence($report_id, $position) {
        $query = 'SELECT * FROM {block_ilp_report_field} r WHERE report_id = :report_id ORDER BY r.position';
        $fields =  $this->dbc->get_records_sql($query, array('report_id' => $report_id));
        foreach ($fields as $field) {
            $field->position = $position;
            $this->dbc->update_record('block_ilp_report_field', $field);
            $position ++;
        }
    }

    /**
     * @param string $minmax
     * @return mixed
     */
    public function upperlower_report_position($minmax = 'MIN') {
        $query = 'SELECT '.$minmax.'(position) FROM mdl_block_ilp_report r WHERE position is not null AND deleted = 0';
        return $this->dbc->get_field_sql($query);
    }

    /**
     * @param $report_id
     * @param string $minmax
     * @return mixed
     */
    public function upperlower_report_field_position($report_id, $minmax = 'MIN') {
        // Prevent SQL injection.
        if ($minmax != 'MIN') {
            $minmax = 'MAX';
        }
        $query = 'SELECT ' . $minmax . '(r.position) FROM {block_ilp_report_field} r WHERE r.report_id = :report_id';
        return $this->dbc->get_field_sql($query, array('report_id' => $report_id));
    }

    /**
     * @param $reports
     * @param $current_min
     */
    public function create_report_positions_where_null($reports, $current_min) {
        $reports = array_reverse($reports);
        foreach ($reports as $report) {
            $current_min --;
            $report->position = $current_min;
            $this->dbc->update_record('block_ilp_report', $report);
        }
    }
    /**
     * Returns the record from the given ilp form element plugin table with the id given
     *
     * @param int    $form_element_id the id of the element in the given table
     * @param string $tablename the name of the plugin table that holds the data that will be retrieved
     * @return object containing plugin record that matches criteria
     */
    function get_form_element_data($tablename,$form_element_id) {
        return $this->dbc->get_record($tablename,array("id"=>$form_element_id));
    }

    /**
     * Returns all report field records for the report with the given id
     *
     * @param int $report_id the id of the report whose fields will be retrieved
     * @param bool $orderbyposition
     * @return array containing report field objects that match criteria
     */
    function get_report_fields($report_id,$orderbyposition=false) {

        $order = (!empty($orderbyposition)) ? "ORDER BY position DESC": "";

        $sql	=	"SELECT		*
    				 FROM 		{block_ilp_report_field}
    				 WHERE		report_id	= :report_id
    				{$order}";

        return $this->dbc->get_records_sql($sql, array('report_id'=>$report_id));
    }

    /**
     * Gets the reocrd with id matching the given plugin_id
     *
     * @param $plugin_id
     * @internal param string $name the name of the new form element plugin
     * @return mixed the id of the inserted record or false
     */
    function get_form_element_plugin($plugin_id){
        return	$this->dbc->get_record("block_ilp_plugin",array('id'=>$plugin_id));
    }


    /**
     * Gets the full list of form element plugins currently installed.
     *
     * @return array Result objects
     */
    function get_form_element_plugins() {
        global $CFG;

        // check for the presence of a table to determine which query to run
        $tableexists =  in_array('block_ilp_plugin',$this->dbc->get_tables());

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_plugin', array()) : false;

    }

    /**
     * Creates a new form element plugin record.
     *
     * @param string $name the name of the new form element plugin
     * @param string $tablename
     * @return mixed the id of the inserted record or false
     */
    function create_form_element_plugin($name,$tablename) {
        $type = new stdClass();
        $type->name 		= $name;
        $type->tablename 	= $tablename;

        //TODO: should form element be enabled by default?
        $type->status 		= 1;

        return $this->insert_record('block_ilp_plugin', $type);
    }

    /**
     * Gets the full list of dashboard plugins already installed
     *
     * @return array Result objects
     */
    function get_dashboard_plugins() {
        global $CFG;

        // check for the presence of a table to determine which query to run
        $tableexists =  in_array('block_ilp_dash_plugin',$this->dbc->get_tables());

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_plugin', array()) : false;
    }

    /**
     * Gets the full list of dashboard plugins already installed
     *
     * @return array Result objects
     */
    function get_dashboard_tabs() {

        $tableexists = in_array('block_ilp_dash_tab',$this->dbc->get_tables());

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_tab', array('status'=>1)) : false;
    }

    /**
     * Gets the full list of dashboard templates already installed
     *
     * @return array Result objects
     */
    function get_dashboard_templates() {

        $tableexists = in_array('block_ilp_dash_temp',$this->dbc->get_tables());

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_temp', array()) : false;
    }

    /**
     * Creates a new  plugin record.
     *
     * @param $table
     * @param string $name the name of the new form element plugin
     * @param string $tablename    the name of the table the record will be saved to
     * @return mixed the id of the inserted record or false
     */
    function create_plugin($table,$name,$tablename=NULL) {
        $type = new stdClass();
        $type->name 		    = $name;
        $type->tablename 		= $tablename;

        //TODO: should the dashboard plugin be enabled by default?
        $type->status 		= 1;

        return $this->insert_record($table, $type);
    }

    /**
     * Creates a new region record.
     *
     * @param object $region the region object to be inserted into the db
     * @return mixed the id of the inserted record or false
     */
    function create_region($region) {
        return $this->insert_record('block_ilp_dash_temp_region',$region);
    }

    /**
     * Retrieve the information for the course
     *
     * @param int $course_id The id of the course
     * @return array containing a course object that matches the outcomes
     */
    function get_course($course_id) {
        return $this->dbc->get_record('course', array('id' => $course_id));
    }

    /**
     * Wrapper for get_course
     *
     * @param int $course_id The id of the course
     * @return array containing a course object that matches the outcomes
     */
    function get_course_by_id($course_id) {
        return $this->get_course($course_id);
    }


    /**
     * Creates a new report record
     *
     * @param object $report an object containing data on
     * about a new report
     * @return mixed the id of the inserted record or false
     */
    function create_report($report) {
        return $this->insert_record("block_ilp_report",$report);
    }

    /**
     * @param string $tablename
     * @param $object
     * @return mixed
     */
    public function special_insert($tablename, $object) {
        return $this->insert_record($tablename, $object);
    }
    /**
     * Returns the position number a new report field should take
     *
     * @param int $report_id the id of the report that the new field will be in
     * @return int the new fields position number
     */

    function get_new_report_field_position($report_id) {

        $position =  $this->dbc->count_records("block_ilp_report_field",array("report_id"=>$report_id));

        return (empty($position)) ? 1 : $position+1;
    }

    /**
     * Returns the position number a new report field should take
     * @internal param int $report_id the id of the report that the new field will be in
     * @return int the new fields position number
     */

    function get_new_report_position() {

        $position =  $this->dbc->count_records_select("block_ilp_report", "position !='0'");
        return (empty($position)) ? 1 : $position+1;
    }


    /**
     * Creates a new record in the given plugin table
     *
     * @param string $tablename the name of the table that will be updated
     * @param object $pluginrecord an object containing the data on the record
     * @return mixed the id of the inserted record or false
     */
    function create_plugin_record($tablename,$pluginrecord) {
        return $this->insert_record($tablename,$pluginrecord);
    }

    /**
     * Updates the given record in the given table
     *
     * @param string $tablename the name of the table that will be updated
     * @param object $pluginrecord an object containing the data on the record
     * @return bool true or false depending on result of query
     */
    function update_plugin_record($tablename,$pluginrecord) {
        return $this->update_record($tablename,$pluginrecord);
    }

    /**
     * Creates a new report field record
     *
     * @param object $reportfield an object containing the data to be saved
     * @return mixed the id of the inserted record or false
     */
    function create_report_field($reportfield) {
        return $this->insert_record("block_ilp_report_field",$reportfield);
    }

    /**
     * Updates the record in the report field table with a id matching the one
     * in the given object
     *
     * @param object $reportfield an object containing the data on the record
     * @return bool true or false depending on result of query
     */
    function update_report_field($reportfield) {
        return $this->update_record('block_ilp_report_field',$reportfield);
    }

    /**
     * Get the plugin instance record that has the reportfield_id given
     *
     * @param string $tablename the name of the table that will be updated
     * @param int $reportfield_id the reportfield_id that the record must have
     * @return mixed object containing the plugin instance record or false
     */
    function get_plugin_record($tablename,$reportfield_id) {
        return $this->dbc->get_record($tablename, array('reportfield_id' => $reportfield_id));
    }

    /**
     * Get the warning status item for a user
     *
     * @param string $tablename the name of the table that will be updated
     * @param int $user_id
     * @return mixed object containing the plugin instance record or false
     */
    public function get_secondstatus_userrecord($tablename = 'block_ilp_plu_wsts_ent',$user_id) {
        return $this->dbc->get_record($tablename, array('user_id' => $user_id));
    }

    /**
     * This is the same as get_form_element_plugin() above
     * @todo refactor calls to this function
     * Returns the plugin record that has the matching id
     *
     * @param int $plugin_id the id of the plugin that will be retrieved
     * @return mixed object containing the plugin record or false
     */
    function get_plugin_by_id($plugin_id) {
        return $this->dbc->get_record('block_ilp_plugin',array('id'=>$plugin_id));
    }

    /**
     * Sets the new position of a field
     *
     * @param int $reportfield_id the id of the reportfield whose position will be changed
     * @param $newposition
     * @return mixed object containing the plugin record or false
     */
    function set_new_position($reportfield_id,$newposition) {
        return $this->dbc->set_field('block_ilp_report_field',"position",$newposition,array('id'=>$reportfield_id));
    }

    /**
     * Sets the new position of a report
     *
     * @param int $report_id the id of the report whose position will be changed
     * @param $newposition
     * @return mixed object containing the plugin record or false
     */
    function set_new_report_position($report_id,$newposition) {
        return $this->dbc->set_field('block_ilp_report',"position",$newposition,array('id'=>$report_id));
    }

    /**
     * Returns all reports with a position less than or greater than
     * depending on type given. the results will include the position as well.
     * if position and type are not specified all reports are returned ordered by
     * position
     *
     * @param int $position the position of fields that will be returned
     *      greater than or less than depending on $type
     * @param  int $type determines whether fields returned will be greater than
     *         or less than position. move up = 1 move down 0
     * @param bool $disabled should disabled reports be returned
     * @param bool $deleted
     * @param bool $include_vaulted
     * @return mixed object containing the plugin record or false
     */
    function get_reports_by_position($position=null,$type=null,$disabled=true,$deleted=true, $include_vaulted = false) {

        $positionsql	=	"";
        //the operand that will be used

        $params = array();
        $and = "";
        $deletedrec = '';
        if(!empty($deleted)) {
            $deletedrec = "deleted = 0";
            $and = "AND";
        }

        if (!empty($position)) {
            $otherfield		=	(!empty($type)) ? $position-1 : $position+1;
            $params['position'] =  $position;
            $params['otherfield'] = $otherfield;
            $positionsql 	=  "{$and} (position = :position ||  position = :otherfield) ";
            $and = "AND";
        }

        $disabledsql    =   '';
        if (empty($disabled)) {
            $disabledsql    =   "{$and} status = 1 ";
        }

        $vaultsql = '';
        if (!$include_vaulted) {
            $vaultsql = $and . ' vault = 0 ';
        }

        $where = (!empty($position) ||empty($disabled) || !empty($deleted) || !empty($vaultsql) ) ? " WHERE" : "";

        $sql	=	"SELECT		*
					 FROM		{block_ilp_report}
					 {$where}      {$deletedrec}
                     {$disabledsql}
					 {$positionsql}
					 {$vaultsql}
					 ORDER BY 	position";

        return		$this->dbc->get_records_sql($sql, $params);
    }

    /**
     * Returns all reports which marked as vault with a position less than or greater than
     * depending on type given. the results will include the position as well.
     * if position and type are not specified all reports are returned ordered by
     * position
     *
     * @param int $position the position of fields that will be returned
     *      greater than or less than depending on $type
     * @param  int $type determines whether fields returned will be greater than
     *         or less than position. move up = 1 move down 0
     * @param bool $disabled should disabled reports be returned
     * @param bool $deleted
     * @return mixed object containing the plugin record or false
     */
    function get_reports_vaulted($position=null,$type=null,$disabled=true,$deleted=true) {

        $positionsql	=	"";
        //the operand that will be used

        $params = array();
        $and = "";

        if(!empty($deleted)) {
            $deletedrec = "deleted = 0";
            $and = "AND";
        }

        if (!empty($position)) {
            $otherfield		=	(!empty($type)) ? $position-1 : $position+1;
            $params['position'] =  $position;
            $params['otherfield'] = $otherfield;
            $positionsql 	=  "{$and} (position = :position ||  position = :otherfield) ";
            $and = "AND";
        }

        $disabledsql    =   '';
        if (empty($disabled)) {
            $disabledsql    =   "{$and} status = 1 ";
        }

        $where = (!empty($position) ||empty($disabled) || !empty($deleted) ) ? " WHERE" : "";

        //just overwriting the sql....
        $sql	=	"SELECT		*
					 FROM		{block_ilp_report}
					 {$where}      {$deletedrec}
                     {$disabledsql}
					 {$positionsql} and vault = 1
					 ORDER BY 	position";

        return		$this->dbc->get_records_sql($sql, $params);
    }

    /**
     * Returns all fields in a report with a position less than or greater than
     * depending on type given. the results will include the position as well.
     * if position and type are not specified all fields are returned ordered by
     * position
     *
     * @param int $report_id the id of the report whose fields will be returned
     * @param int $position the position of fields that will be returned
     *    greater than or less than depending on $type
     * @param  int $type determines whether fields returned will be greater than
     *        or less than position. move up = 1 move down 0
     * @param null $plugin_details
     * @return mixed object containing the plugin record or false
     */
    function get_report_fields_by_position($report_id,$position=null,$type=null, $plugin_details = null) {

        $positionsql	=	"";
        //the operand that will be used

        $params = array('report_id'=>$report_id);
        if (!empty($position)) {
            $otherfield		=	(!empty($type)) ? $position-1 : $position+1;
            $params['position'] = $position;
            $params['otherfield'] = $otherfield;
            $positionsql 	=  "AND (position = :position ||  position = :otherfield)";
        }

        if ($plugin_details) {
            $sql	=	"SELECT		reportfield.*, plugin.name as pluginname
				    	 FROM		{block_ilp_report_field} reportfield
				    	 INNER JOIN {block_ilp_plugin} plugin ON (reportfield.plugin_id = plugin.id)
					     WHERE		report_id	= :report_id
					    {$positionsql}
					     ORDER BY 	position";
        } else {
            $sql	=	"SELECT		*
				    	 FROM		{block_ilp_report_field}
					     WHERE		report_id	= :report_id
					    {$positionsql}
					     ORDER BY 	position";
        }


        return		$this->dbc->get_records_sql($sql, $params);
    }

    /**
     * Delete the record from the given table with the reportfield_id matching the given id
     *
     * @param string $tablename the table that you want to delete the record from
     * @param int $id the id of the record that you want to delete
     *
     * @param array $extraparams
     * @return bool true or false
     */
    function delete_form_element_by_reportfield( $tablename,$id, $extraparams=array() ) {
        return $this->delete_records($tablename, array('reportfield_id' => $id), $extraparams );
    }

    /**
     * Generic delete function used to delete items from the items table
     *
     * @param string $tablename the table that you want to delete the record from
     * @param int $parent_id the parent_id that all fields to be deleted should have
     *
     * @param array $extraparams
     * @return bool true or false
     */
    function delete_items($tablename,$parent_id, $extraparams=array() ) {
        return $this->delete_records( $tablename, array('parent_id' => $parent_id), $extraparams );
    }

    /**
     * Delete a report field record
     *
     * @param int $id the id of the record that you want to delete
     *
     * @param array $extraparams
     * @return bool true or false
     */
    function delete_report_field( $id, $extraparams=array() ) {
        return $this->delete_records('block_ilp_report_field', array( 'id' => $id ), $extraparams );
    }


    /**
     * get a report record using the id given
     *
     * @param int $id the id of the record that you want to retrieve
     *
     * @return mixed object or false if no record found
     */
    function get_report_by_id($id) {
       return ilp_report::from_id($id);
    }

    /**
     * get a report record using the id given
     *
     * @param $field
     * @param $value
     * @internal param int $id the id of the record that you want to retrieve
     *
     * @return mixed object or false if no record found
     */
    function get_report_by_other($field, $value) {
        return $this->dbc->get_record('block_ilp_report', array($field=>$value));
    }

    /**
     * Updates the report record with the data in the given object
     * the object must contain a id param with the id of the record
     * to be updated
     *
     * @param $report
     * @internal param object $reportfield an object containing the data on the record
     * @return bool true or false depending on result of query
     */
    function update_report($report) {
        return $this->update_record('block_ilp_report',$report);
    }

    /**
     * returns an array containing all of the capabilities for the ilp block
     *
     * @return mixed array of objects or false depending on result of query
     */
    function get_block_capabilities() {

        $sql	=	"SELECT	*
    				 FROM	{capabilities}
    				 WHERE	name	LIKE	'block/ilp:%'";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * returns whether any permission exists for the given report
     *
     * @param $report_id
     * @return bool true or false
     */
    function permissions_exist($report_id) {
        return $this->dbc->record_exists("block_ilp_reportpermissions",array("report_id"=>$report_id));
    }

    /**
     * Deletes all report permission
     *
     * @param $report_id
     * @internal param int $id the id of the record that you want to delete
     *
     * @return bool true or false
     */
    function delete_permissions_by_report_id($report_id) {
        return $this->dbc->delete_records('block_ilp_reportpermissions', array('report_id' => $report_id));
    }



    /**
     * Creates a new report permission
     *
     * @param object $permission an object containing the data to be saved
     * @return mixed the id of the inserted record or false
     */
    function create_permisssion($permission) {
        return $this->insert_record("block_ilp_reportpermissions", $permission);
    }

    /**
     * Returns permissions for the report with the given id
     *
     * @param $report_id
     * @return mixed object containing all permissions records or false
     */
    function get_report_permissions($report_id)	{
        return $this->dbc->get_records("block_ilp_reportpermissions",array('report_id'=>$report_id));
    }


    /**
     * returns data that can be used with a pagable ilp_flexible_table
     *
     * @param object $flextable an object of type flextable
     * @param boolean $deleted should deleted reports be returned
     * 				  defaults to false
     * @return mixed object containing report records or false
     */
    function get_reports_table($flextable,$deleted=false)	{

        $select	=	"SELECT		* ";

        $from	=	"FROM 		{block_ilp_report}";

        $where	=	(empty($deleted)) ? " WHERE deleted != 1 " : "";

        $order  =   " order by position";

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        //$flextable->totalrows($count);

        $data = $this->dbc->get_records_sql(
            $select.$from.$where. $order,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );

        return $data;
    }

    /**
     * Returns all courses in the current moodle
     *
     * @return mixed object containing all course records or false
     */
    function get_courses()	{
        return $this->dbc->get_records("course", array(), 'fullname ASC');
    }

    /**
     * Returns all roles
     *
     * @return mixed object containing all role records or false
     */
    function get_roles()	{
        return $this->dbc->get_records("role");
    }

    /**
     * Returns the role that matches the name given
     *
     * @param string $rolename the name of the role that will be retrieved
     *
     * @return mixed object containing role record or false
     */
    function get_role_by_name($rolename)	{
        return $this->dbc->get_record("role",array('name'=>$rolename));
    }

    /**
     * Returns the role that matches the id given
     *
     * @param int $roleid the id of the role that will be retrieved
     *
     * @return mixed object containing role record or false
     */
    function get_role_by_id($roleid)	{
        return $this->dbc->get_record("role",array('id'=>$roleid));
    }

    /**
     * Create a plugin entry in the table given
     *
     * @param $tablename
     * @param $pluginentry
     * @return mixed int id of new reocrd or false
     */
    function create_plugin_entry($tablename,$pluginentry)	{
        return $this->insert_record($tablename, $pluginentry);
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public function get_current_warning_status($user_id) {
        return $this->dbc->get_record("block_ilp_plu_wsts_ent",array('user_id'=>$user_id));
    }

    /**
     * @param $value
     * @return mixed
     */
    public function get_warning_status_name($value) {
        return $this->dbc->get_field("block_ilp_plu_wsts_items", 'name', array('value'=>$value));
    }

    /**
     * Update a plugin entry record in the table given
     *
     * @param string $tablename
     * @param stdClass $pluginentry
     * @return bool true or false
     */
    function update_plugin_entry($tablename,$pluginentry)	{
        return $this->update_record($tablename, $pluginentry);
    }

    /**
     * Returns a list of all reports currently enabled in a course
     *
     * @param int $course_id the id of the course who we want to
     * get report for
     * @param int $report_id the id of the report
     * @param null $status
     * @return mixed array containing recordset objects or false
     */
    function get_coursereports($course_id,$report_id=null,$status=null) {

        $report_sql = "";
        $status_sql = "";

        $params = array('course_id'=>$course_id);

        if(!empty($report_id)){
            $params['report_id'] = $report_id;
            $report_sql = " AND report_id = :report_id ";
        }
        if(!empty($status)){
            $params['status'] = $status;
            $status_sql = " AND cr.status = :status ";
        }

        $sql	=	"SELECT		*, cr.id as cr_id
    				 FROM 		{block_ilp_coursereports} as cr,
    				 			{block_ilp_report} as r
    				 WHERE		cr.report_id	=	r.id
    				 AND		course_id 	=  :course_id
    				{$report_sql}
    				{$status_sql}";

        return	$this->dbc->get_records_sql($sql, $params);
    }

    /**
     * Returns a list of all ilp reports with an enabled status
     *
     * @param null $report_ids
     * @internal param array $reports a array contain the ids of reports that
     * you do not want included in the return values
     * @return mixed array containing recordset objects or false
     */
    function get_enabledreports($report_ids=null)	{

        $unwantedcourses	=	(!empty($report_ids)) ? " AND id NOT IN (".implode(',',$report_ids).")": "";

        $sql	=	"SELECT		*
    				 FROM		{block_ilp_report}
    				 WHERE		status	=	".ILP_ENABLED.
            $unwantedcourses;

        return	$this->dbc->get_records_sql($sql);
    }

    /**
     * Returns a list of all ilp reports with an enabled status
     *
     * @param null $userid
     * @param null $pluginid
     * @internal param array $reports a array contain the ids of reports that
     * you do not want included in the return values
     * @return mixed array containing recordset objects or false
     */
    function get_enabledreports_with_entry($userid=null, $pluginid = null)	{

        $sql	=	"SELECT	DISTINCT	e.id as entryid, re.*
    				 FROM		{block_ilp_report} re
    				 INNER JOIN {block_ilp_entry} e ON (re.id = e.report_id" . (($userid) ? " AND e.user_id = " . $userid : "") . ")
    				 INNER JOIN {block_ilp_report_field} rf ON (rf.report_id = re.id AND rf.plugin_id = " . $pluginid .")
    				 WHERE		status	=	".ILP_ENABLED;

        return	$this->dbc->get_records_sql($sql);
    }

    /**
     * Creates a record in the block_ilp_coursereports table that allows
     * a report to be shown in a course
     *
     * @param object $record the record that will be created
     *
     * @return mixed int the id of the record or false
     */
    function create_coursereport($record) {
        return $this->insert_record("block_ilp_coursereports",$record);
    }

    /**
     * Updates the given coursereport record
     *
     * @param object $record the record that will be updated
     *
     * @return bool true or false
     */
    function update_coursereport($record) {
        return $this->update_record("block_ilp_coursereports",$record);
    }


    /**
     * returns any reportpermission records that match the given
     * criteria of report_id, role_id and capability (name)
     *
     * @param int $report_id the id of the report whose permission
     * is being checked
     * @param mixed $role_id int a single role id or array filled with
     * a series of role_ids
     * @param the id of the capability we are checking if the user has for the report
     *
     * @return mixed array with recordset objects or false
     */
    function get_reportpermissions_by_criteria($report_id,$role_id,$capability_id) {

        if (!is_array($role_id)) {
            $role_id	=	array($role_id);
        }

        $params = array('report_id'=>$report_id, 'capability_id'=>$capability_id);

        $sql	=	"SELECT 	c.id, r.*, rp.*
  					 FROM 		{block_ilp_reportpermissions} AS rp,
					 			{role} AS r,
								{capabilities} AS c
					 WHERE		rp.capability_id	=	c.id
					 AND		rp.role_id		=	r.id
					 AND		rp.report_id	=	:report_id
					 AND		r.id IN (".implode(',',$role_id).")
					 AND		c.id = :capability_id";

        return 	(!empty($role_id)) ? $this->dbc->get_records_sql($sql, $params) : false;
    }


    /**
     * returns true or false depending on whether role (or one of the roles given)
     * has a cappability in a report
     *
     * @param int $report_id the id of the report whose permission
     * is being checked
     * @param mixed $role_id int a single role id or array filled with
     * a series of role_ids
     * @param the id of the capability we are checking if the user has for the report
     *
     * @return mixed array with recordset objects or false
     */
    function has_report_permission($report_id,$role_id,$capability_id)	{
        global $USER, $CFG;

        if($this->ilp_admin($USER)){
            return true;
        }

        require_once($CFG->dirroot."/blocks/ilp/lib.php");
        //adding addtional lines that return true if the user is either a site admin or has the ilpviewall capabilty at site level

        //if permissions where returned from then the role (or one of the roles given) has the permission in the course
        $permissions	=	$this->get_reportpermissions_by_criteria($report_id,$role_id,$capability_id);
        return 	(!empty($permissions) || !empty($is_admin)) ? true	:	false;
    }

    /**
     * @param int $user
     * @return bool
     */
    function ilp_admin($user=0)
    {
       if(empty($user))
       {
          global $USER;
          $user=$USER;
       }

       if(is_siteadmin($user->id))
          return true;

       //check for the ilpviewall capability at site level this gives the user rights to view all
       return has_capability('block/ilp:ilpviewall',context_system::instance());
    }

    /**
     * Get the a users record based on the id given
     *
     * @param int $user_id the id of the user
     *
     * @return mixed objects containing user record or false
     */
    function get_user_by_id($user_id) {
        return	$this->dbc->get_record("user",array("id"=>$user_id));
    }

    /**
     * return the capability record for the capability with the
     * given name
     *
     * @param string capability name
     *
     * @return mixed objects containing capability record or false
     */
    function get_capability_by_name($capability) {
        return	$this->dbc->get_record("capabilities",array("name"=>$capability));
    }

    /**
     * Create a entry record
     *
     * @param object $entry the entry that you want to insert
     *
     * @return mixed int the id of the entry or false
     */
    function create_entry($entry) {
        return	$this->insert_record("block_ilp_entry", $entry);
    }

    /**
     * Updates an entry record
     *
     * @param object entry the object that we want to update
     *
     * @return bool true or false
     */
    function update_entry($entry) {
        return	$this->update_record("block_ilp_entry", $entry);
    }

    /**
     * Updates a second status record
     *
     * @param object $secondstatus_userrecord the object that we want to update
     *
     * @return bool true or false
     */
    function update_secondstatus($secondstatus_userrecord) {
        return	$this->update_record("block_ilp_plu_wsts_ent", $secondstatus_userrecord);
    }

    /**
     * Updates a second status item record
     *
     * @param object $secondstatus_item the object that we want to update
     *
     * @return bool true or false
     */
    function update_secondstatus_item($secondstatus_item) {
        return	$this->update_record("block_ilp_plu_wsts_items", $secondstatus_item);
    }

    /**
     * get the data entry record with the id given
     *
     * @param string tablename the name of the table that will be interrogated
     * @param int 	$entry_id the entry id of the records that will be returned
     * @param int 	$reportfield_id the id of the report field
     * @param bool 	$multiple is there a chance multiple records will be return
     * if yes set mutliple to true
     * @return mixed object the entry record or false
     */
    function get_pluginentry($tablename,$entry_id,$reportfield_id,$multiple=false) {
        global	$CFG;

        $entrytable		=	"{$CFG->prefix}{$tablename}_ent";
        $parenttable	=	"{$CFG->prefix}{$tablename}";

        $itemtable		=      $multiple ? "{$CFG->prefix}{$tablename}_items as i," : '';
        $where			=      $multiple ? "e.parent_id	=	i.id AND i.parent_id	=	p.id" : "e.parent_id	=	p.id";

        $params = array('entry_id'=>$entry_id, 'reportfield_id'=>$reportfield_id);

        $sql	=	"SELECT		*
					 FROM 		{$parenttable} as p,
					 			{$itemtable}
					 			{$entrytable} as e
					 WHERE 		{$where}
					 AND		e.entry_id	=	:entry_id
					 AND		p.reportfield_id	=	:reportfield_id
					 ";

        return !$multiple ? $this->dbc->get_record_sql($sql, $params) : $this->dbc->get_records_sql($sql, $params);
    }

    /**
     * @return array
     */
    public function get_secondstatus_items() {
        return $this->dbc->get_records('block_ilp_plu_wsts_items');
    }
    /**
     * get the status of the
     *
     * @param int 	$entry_id the entry id of the records that will be returned
     * @param int 	$reportfield_id the id of the report field
     *
     * @return mixed object the entry record or false
     */
    function get_entrystatus($entry_id,$reportfield_id)	{
        global 	$CFG;

        $sql	=	"SELECT			 si.*
					 FROM			{block_ilp_user_status} as us,
					 				{block_ilp_plu_sts_items} as si,
					 				{block_ilp_entry} as e
					 WHERE			e.id                =   :entry_id
					 AND            e.user_id           =   us.user_id
					 AND            us.parent_id		=	si.id";

        return 		$this->dbc->get_record_sql($sql, array('entry_id'=>$entry_id));
    }

    /**
     * get the status of the
     *
     * @param int 	$entry_id the entry id of the records that will be returned
     * @param int 	$reportfield_id the id of the report field
     *
     * @return mixed object the entry record or false
     */
    function get_entry_warning_status($entry_id,$reportfield_id)	{
        global 	$CFG;

        $sql	=	"SELECT			 si.*
					 FROM			{block_ilp_user_w_status} as us,
					 				{block_ilp_plu_wsts_items} as si,
					 				{block_ilp_entry} as e
					 WHERE			e.id                =   :entry_id
					 AND            e.user_id           =   us.user_id
					 AND            us.parent_id		=	si.id";

        return 		$this->dbc->get_record_sql($sql, array('entry_id'=>$entry_id));
    }

    /**
     * get the status of the
     *
     * @param int 	$entry_id the entry id of the records that will be returned
     * @param int 	$reportfield_id the id of the report field
     *
     * @return mixed object the entry record or false
     */
    function get_entrywarningstatus($entry_id,$reportfield_id)	{
        global 	$CFG;

        $sql	=	"SELECT			 si.*
					 FROM			{block_ilp_plu_wsts_ent} as us,
					 				{block_ilp_plu_wsts_items} as si,
					 				{block_ilp_entry} as e
					 WHERE			e.id                =   :entry_id
					 AND            e.id           =   us.entry_id
					 AND            us.parent_id		=	si.id";

        return 		$this->dbc->get_record_sql($sql, array('entry_id'=>$entry_id));
    }

    /**
     * check if any user data has been uploaded to a particular list-type reportfield
     * if it has then admin should not be allowed to delete any existing
     * options
     * @param $tablename
     * @param $reportfield_id
     * @param bool $item_table
     * @param bool $item_key
     * @param bool $item_value_field
     * @internal param \tablename $string
     * @internal param \reportfield_id $int
     * @internal param \item_table $string - use this item_table if item_table name is not simply $tablename . "_items"
     * @internal param \item_key $string - use this foreign key if specific item_table has been sent as arg. Send empty string to simply get all rows from the item table
     * @internal param \item_value_field $string - field from the item table to use as the value submitted to the user entry table
     * @return mixed array of objects or false
     */
    function plugin_data_item_exists( $tablename, $reportfield_id, $item_table=false, $item_key=false, $item_value_field=false ){
        global $CFG;

        $tablename 		= $CFG->prefix . $tablename;
        if( !$item_table ){
            $item_table 	= $tablename . "_items";
        }
        if( false === $item_key ){
            $item_key 	= 'parent_id';
        }
        $entry_table 	= $tablename . "_ent";

        $item_on_clause = '';
        if( $item_key ){
            $item_on_clause = "ON item.$item_key = ele.id";
        }

        if( !$item_value_field ){
            $item_value_field = 'value';
        }

        $sql = "SELECT *
				FROM {$tablename} ele
				JOIN {$item_table} item  $item_on_clause
				JOIN {$entry_table} entry ON entry.value = item.$item_value_field
				WHERE ele.reportfield_id = :reportfield_id";

        return	$this->dbc->get_records_sql($sql, array('reportfield_id'=>$reportfield_id),0,10);
    }

    /*
    * special version of plugin_data_item_exists for gradebook tracker
    * @param string $tablename
    * @param int $reportfield_id
    */
    /**
     * @param $tablename
     * @param $reportfield_id
     * @return array
     */
    function plugin_data_item_exists_gradebooktracker( $tablename, $reportfield_id ){
        global $CFG;
        $tablename 		= $CFG->prefix . $tablename;

        $sql = "
		SELECT *
		FROM  {$tablename} ele
		JOIN {$tablename}_ent entry ON entry.parent_id = ele.reportfield_id
		JOIN {$tablename}_items item ON item.parent_id = entry.id
		WHERE ele.reportfield_id = :reportfield_id";

        return	$this->dbc->get_records_sql($sql, array('reportfield_id'=>$reportfield_id));
    }

    /**
     * delete option items for a plugin list-type element
     * $tablename is the element table eg block_ilp_plu_category
     * @param $tablename
     * @param $reportfield_id
     * @param array $extraparams
     * @internal param \tablename $string
     * @internal param \reportfield_id $int
     *
     * @return boolean true or false
     */
    function delete_element_listitems( $tablename, $reportfield_id , $extraparams=array() ){



        $real_tablename =  $tablename;
        $element_table = $tablename;
        $item_table = $tablename . "_items";
        $entry_table = $tablename . "_ent";
        //get parent_id
        $parent_id = $this->get_element_id_from_reportfield_id( $tablename, $reportfield_id );

        return $this->dbc->delete_records( $item_table, array( 'parent_id' => $parent_id ) , $extraparams );
    }

    /**
     * delete option items for a plugin list-type element referenced by element_id (parent_id) instead of reportfield_id
     * $tablename is the element table eg block_ilp_plu_category
     * @param $tablename
     * @param $parent_id
     * @param array $extraparams
     * @internal param \tablename $string
     * @internal param \reportfield_id $int
     *
     * @return boolean true or false
     */
    function delete_element_listitems_by_parent_id( $tablename, $parent_id , $extraparams=array() ){



        $real_tablename = $tablename;
        $element_table = $tablename;
        $item_table = $tablename . "_items";
        $entry_table = $tablename . "_ent";
        //get parent_id

        //return $this->dbc->delete_records( $item_table, array( 'parent_id' => $parent_id ) , $extraparams );
        return $this->delete_records( $item_table, array( 'parent_id' => $parent_id ) , $extraparams );
    }

    /**
     * @param string tablename
     * @param int reportfieldid
     * @return int or false
     */
    public function get_element_id_from_reportfield_id( $tablename, $reportfield_id ){
        $records    =   $this->dbc->get_records( $tablename , array( 'reportfield_id' => $reportfield_id ));

        $element_record = array_shift( $records  );
        if( !empty( $element_record ) ){
            return $element_record->id;
        }
        return false;
    }


    /**
     * the full data from listelement_item_exists is used by ilp_element_plugin_status::get_option_list(),
     * so please do not change the return type
     * @param string tablename
     * @param array for where clause
     * @return array of objects
     */
    public function listelement_item_exists( $item_tablename, $conditionlist ){
        return $this->dbc->get_records( $item_tablename, $conditionlist );
    }

    /**
     * find user input in a particular data entry table
     * @param string - element table
     * @param string - record id
     * @param string - entry_id (use for finding multiple records from a multi-select submit)
     * @return array of objects or false
     */
    //public function get_data_entry_record( $tablename, $pluginrecord_id, $entry_id ){
    public function get_data_entry_record( $tablename, $entry_id ){


        $entry_tablename = $tablename . '_ent';
        $entry = array_shift( $this->dbc->get_records( $entry_tablename , array( 'id' => $entry_id ) ) );

        //but are there other entries with the same parent_id and entry_id ?
        if( !empty( $entry ) ){
            $parent_id = $entry->parent_id;
            $entry_id = $entry->entry_id;
            $wider_condition = array( 'parent_id' => $parent_id, 'entry_id' => $entry_id );
            return $this->dbc->get_records( $entry_tablename, $wider_condition );
        }
        //no records - return false
        return false;
    }

    /**
     * supply a reportfield id for a dropdown type element
     * dropdown options are returned
     * @param $reportfield_id
     * @param $tablename
     * @param bool $field
     * @internal param $int
     * @internal param $string
     * @return array of objects
     */
    function get_optionlist( $reportfield_id, $tablename, $field=false ){
        global $CFG;
        $tablename = $CFG->prefix . $tablename;
        $item_table = $tablename . "_items";
        $plugin_table = $tablename;

        $fieldlist = array( "$item_table.id", 'value', 'name' );
        if( $field ){
            $fieldlist[] = $field;
        }

        $whereandlist = array(
            "$plugin_table.reportfield_id = $reportfield_id"
        );

        $sql = "SELECT " . implode( ',' , $fieldlist ) . "
				FROM  	{block_ilp_report_field} rptf
				JOIN 	{$plugin_table} ON $plugin_table.reportfield_id = rptf.id
				JOIN 	{$item_table} ON $item_table.parent_id = $plugin_table.id
				WHERE 	$plugin_table.reportfield_id = :reportfield_id
		";
        return $this->dbc->get_records_sql($sql, array('reportfield_id'=>$reportfield_id));
    }



    /**
     * Return the entry record that matches the id given
     *
     * @param	int $entry_id the id of the entry that will be returned
     *
     * @return mixed object the entry record or false
     */
    function get_entry_by_id($entry_id) {
        return $this->dbc->get_record("block_ilp_entry",array('id'=>$entry_id));
    }

    /**
     * Deletes a record in the given table matching its id field
     *
     * @param   string $tablename the name of the table that the record
     * will be deleted form
     * @param    int $id the id of the record you will be deleting
     *
     * @param array $extraparams
     * @param string $id_field
     * @return mixed true or false
     */
    function delete_element_record_by_id ( $tablename, $id, $extraparams=array(), $id_field = 'id' ) {
        return $this->delete_records( $tablename, array($id_field=>$id), $extraparams );
    }

    /**
     * Deletes a record in the given table matching its id field
     *
     * @param $tablename
     * @param    int $entry_id the id of the record you will be deleting
     *
     * @param array $extraparams
     * @return mixed true or false
     */
    function delete_entryfield($tablename,$entry_id, $extraparams=array())	{
        return $this->delete_records( $tablename, array('id'=>$entry_id), $extraparams );
    }

    /**
     * Deletes a record in the given table matching its id field
     *
     * @param	int $entry_id the id of the record you will be deleting
     *
     * @return mixed true or false
     */
    function delete_entry_by_id($entry_id)	{
        return $this->delete_records( 'block_ilp_entry', array('id'=>$entry_id),array());
    }

    /**
     * Deletes a entrys comment
     *
     * @param $entry_id
     * @internal param int $comment_id the id of the record you will be deleting
     *
     * @return mixed true or false
     */
    function delete_comment_by_id($entry_id)	{
        return $this->delete_records( 'block_ilp_entry_comment', array('id'=>$entry_id),array());
    }

    /**
     * Returns all user entries for the given report
     *
     * @param  int $report_id the id of the report that we are looking for
     * @param  int $user_id	the id of user who will be retrieving report entries for
     * @param  int $state_id the id of the state that the returned entries should be in
     * @param  int $createdby who should the entries be created by null for anyone
     * ILP_CREATED_BY_USER for only user created and ILP_NOTCREATED_BY_USER for entries
     * created by others
     *
     * @return mixed array of objects containing databases recordsets or false
     */
    function get_user_report_entries($report_id,$user_id,$state_id=null,$createdby=null)	{

        $tables		=	"";
        $where		=	"";

        //if the the id of a status has been given then we need to add mroe contitions to
        //find the reports in this state


        $params = array('report_id'=>$report_id, 'user_id'=>$user_id);

        if (!empty($state_id)) {
            $tables		=	", {block_ilp_plu_ste_ent} as se";
            $params['state_id'] = $state_id;
            $where		=	" 	AND e.id	=	se.entry_id
    							AND	se.parent_id	=	:state_id";
        }


        if (!empty($createdby)) {
            if ($createdby  ==  ILP_CREATED_BY_USER)    {
                $params['user_id1'] = $user_id;
                $where .= "AND creator_id = :user_id1";
            } else if ($createdby  ==  ILP_NOTCREATED_BY_USER)    {
                $params['user_id1'] = $user_id;
                $where .= "AND creator_id != :user_id1";
            }
        }


        $sql	=	"SELECT		e.*
    				 FROM		{block_ilp_entry} as e
    				 			{$tables}
    				 WHERE		e.report_id		=	:report_id
    				 AND 		e.user_id	    =	:user_id
    				             {$where}
    				 ORDER BY   e.timemodified DESC";


        return $this->dbc->get_records_sql($sql, $params);
    }

    /**
     * Returns all user entries for the given report
     *
     * @param  int $report_id the id of the report that we are looking for
     * @param  int $user_id    the id of user who will be retrieving report entries for
     * @param null $start
     * @param null $end
     * @internal param int $state_id if set only entries that have a specified state are returned
     * @return mixed array of objects containing databases recordsets or false
     */
    function get_user_report_entries_between_time($report_id,$user_id,$start=null,$end=null)	{

        $tables		=	"";
        $where		=	"";

        $params = array('report_id'=>$report_id, 'user_id'=>$user_id);

        //if the the id of a status has been given then we need to add mroe contitions to
        //find the reports in this state

        if (!empty($start)){
            $params['start'] = $start;
            $where		.=	" 	AND e.timemodified	>   :start";
        }

        if (!empty($end)){
            $params['end'] = $end;
            $where		.=	" 	AND e.timemodified	<   :end";
        }


        $sql	=	"SELECT		e.*
    				 FROM		{block_ilp_entry} as e
    				 			{$tables}
    				 WHERE		e.report_id		=	:report_id
    				 AND 		e.user_id	    =	:user_id
    				 {$where}
    				 ORDER BY   e.timemodified DESC";

        return $this->dbc->get_records_sql($sql, $params);
    }

    /**
     * Returns all entries by the specidified user for the specified report that with a datedeadline date less than the given date.
     * This function is intended for use on reports that have a state field and and a date dealine field. The function will also
     * take a array or
     *
     * @param int $report_id  the id of the report that the entries will be for
     * @param int $user_id    the user id of the user whose entries will be returned
     * @param int $time       a unix timestamp of the date at which the entry datedeadline should have be less than if not set it
     *                          the time is set to the current timestamp
     * @param int $state expected to be one of ILP_STATE_UNSET, ILP_STATE_FAIL, ILP_STATE_PASS,ILP_STATE_NOTCOUNTED
     * @param array|bool $entries the user may provide an array containing the ids of report entries that will then be checked
     *                          to see if they fit the given critera if a entry does not it will not be returned
     * @return array
     */

    public  function    get_deadline_entries($report_id,$user_id,$time=null,$state=ILP_STATE_UNSET,$entries=false)   {

        $params = array('report_id'=>$report_id, 'user_id'=>$user_id, 'time'=>$time, 'state'=>$state);

        $entriessql	= 	(!empty($entries))	? "AND e.id IN (".implode($entries,',').")" : "";

        if(empty($time)){
            $params['time'] = time();
        }


        $sql	=	"SELECT		e.*
                     FROM 		{block_ilp_entry}  as e,
                                {block_ilp_plu_datf_ent} as	datfent,
                                {block_ilp_plu_ste_ent} as se,
                                {block_ilp_plu_ste_items} as si

                     WHERE		e.id			    =	datfent.entry_id
                     AND		e.report_id			=	:report_id
                     AND		e.user_id			=	:user_id
                     AND		datfent.value		<	:time

                     AND		e.id		        =	se.entry_id
                     AND		se.parent_id	    =	si.id
                     AND		si.passfail         =   :state
                     {$entriessql}";


        return $this->dbc->get_records_sql($sql, $params);

    }

    /**
     * Returns the all elements of the state field for the given report
     *
     * @param  int $report_id the id of the report that we want to get the state items for
     *
     * @return mixed array recordset of objects or false
     */
    function get_report_stateitems($report_id)	{


        $sql	=	"SELECT		*
    	 			 FROM 		{block_ilp_report_field} as rf,
    	 						{block_ilp_plugin} as p,
    	 						{block_ilp_plu_ste} as s,
    	 						{block_ilp_plu_ste_items} as si
    	 			WHERE		rf.plugin_id	=	p.id
    	 			AND			rf.id			=	s.reportfield_id
    	 			AND			s.id			=	si.parent_id
    	 			AND			rf.report_id	=	:report_id
    	 			AND			p.name			=	'ilp_element_plugin_state'";

        return		$this->dbc->get_records_sql($sql, array('report_id'=>$report_id));
    }

    /**
     * see if an element of a particular type already exists in a report
     * @param int $report_id
     * @param string $tablename
     * @return int
     */
    public function element_type_exists( $report_id , $tablename ){

        $sql = "
            SELECT COUNT( rpt.id ) n
            FROM {block_ilp_report} rpt
            JOIN {block_ilp_report_field} rptf ON rptf.report_id = rpt.id
            JOIN {block_ilp_plugin} pln ON pln.id = rptf.plugin_id
            WHERE rpt.id = :report_id AND pln.tablename = :tablename
        ";
        $res = $this->dbc->get_record_sql( $sql,  array('report_id'=>$report_id, 'tablename'=>$tablename));
        return $res->n;
    }

    /**
     * @return mixed
     */
    public	function get_enabled_template()	{
        return $this->dbc->get_record('block_ilp_dash_temp',array('status'=>'1'));
    }


    /**
     * Returns the records of plugins enabled for the given template
     *
     *  @param string $templatename the name of the template
     *
     *  @return mixed array of recordset objects or false
     */
    public	function get_template_plugins($templatename)	{

        $sql	=	"SELECT 	tr.name as region_name,
    							p.name as plugin_name,
    							tr.id as region_id,
    							p.id as plugin_id
    				 FROM 		{block_ilp_dash_temp}	as t,
    				 			{block_ilp_dash_temp_region} as tr,
    				 			{block_ilp_dash_region_plugin} as rp,
    				 			{block_ilp_dash_plugin} as p
    				 WHERE 		t.id 	=	tr.template_id
    				 AND		tr.id	=	rp.region_id
    				 AND		rp.plugin_id	=	p.id
    				 AND 		t.name	=   :templatename
    				 ";

        return	$this->dbc->get_records_sql($sql, array('templatename'=>$templatename));
    }

    /**
     * Returns the record from the given ilp form element plugin table with the reportfield_id given
     *
     * @param int    $reportfield_id the id of the element in the given table
     * @param string $tablename the name of the plugin table that holds the data that will be retrieved
     * @return object containing plugin record that matches criteria
     */
    function get_form_element_by_reportfield($tablename,$reportfield_id) {
        return $this->dbc->get_record($tablename,array("reportfield_id"=>$reportfield_id));
    }

    /**
     * gets max position for a report and returns 1 more
     * if no entries yet, returns 1
     * @param int $report_id
     * @param $tablename
     * @return int
     */
    public function get_next_position( $report_id, $tablename ){
        global $CFG;

        $tablename = $CFG->prefix . $tablename;
        $sql = "SELECT MAX( position ) n FROM  {$tablename} WHERE report_id = :report_id";  //gregp - records should be counted not the position #
        $res = array_values( $this->dbc->get_records_sql($sql, array('report_id'=>$report_id)));
        $n = $res[0]->n;
        if( $n ){
            return $n + 1;
        }
        return 1;
    }

    /**
     * returns whether any record already exists given a table, field and value
     *
     * @param string $table
     * @param string $field
     * @param mixed $value
     * @return bool true or false
     */
    function record_exists($table, $field, $value ) {
        return $this->dbc->record_exists( $table, array( $field => $value ) );
    }

    /**
     * Returns all records in the reports table with the given status
     *
     * @param int $status    the status that the returned report records shoulds
     * have
     * @param null $orderby
     * @return    mixed array of recordsets or false
     */
    public function	get_reports($status, $orderby = null)	{
        return $this->dbc->get_records('block_ilp_report',array('status'=>$status), $orderby);
    }

    /**
     * Count the number of entries for given user in the given report
     *
     * @param    int $report_id    the id of the report whose entries will be counted
     * @param    int $user_id the id of the  user whose entries will be counted
     *
     *
     * @param null $timestart
     * @param null $timeend
     * @return    mixed int the number of entries or false
     */

    public function count_report_entries($report_id,$user_id,$timestart=null,$timeend=null)	{

        $params = array('user_id'=>$user_id, 'report_id'=>$report_id);

        $timestartsql = "";
        $timeendsql   = "";

        if (!empty($timestart)){
            $params['timestart'] = $timestart;
            $timestartsql = " AND timecreated > :timestart";
        }

        if (!empty($timeend)){
            $params['timeend'] = $timeend;
            $timeendsql= " AND timecreated < :timeend ";
        }


        $sql	=	"SELECT		COUNT(*)
    				 FROM		{block_ilp_entry}	as 	e
    				 WHERE		user_id			=	:user_id
    				 AND		report_id		=	:report_id
    				           {$timestartsql}
    				           {$timeendsql} ";

        return	$this->dbc->count_records_sql($sql, $params);
    }

    /**
     *
     * @param array students optional array of students to limit result to
     * @param int $timestart optional start timestamp
     * @param int $timeend option end timestamp
     *
     * @return array $array[$reportid][$studentid]
     */
    public function count_all_report_entries($students=array(),$timestart=null,$timeend=null)
    {

        $timestartsql = "";
        $timeendsql   = "";

        if (!empty($timestart)){
            $params['timestart'] = $timestart;
            $timestartsql = " AND timecreated > :timestart";
        }

        if (!empty($timeend)){
            $params['timeend'] = $timeend;
            $timeendsql= " AND timecreated < :timeend ";
        }

        if(!empty($students))
        {
            $studentpart='and user_id in ('.implode(',',$students).')';
        }

        $sql = "SELECT  report_id, user_id, COUNT(*) number
                      FROM  {block_ilp_entry}
                                 where  1 {$studentpart} {$timestartsql}
                                 {$timeendsql}
                      GROUP BY report_id, user_id";

        $r=array();
        foreach($this->dbc->get_recordset_sql($sql, $params) as $item)
        {
            $r[$item->report_id][$item->user_id]=$item->number;
        }

        return $r;
    }

    /**
     *
     * Returns whether the given report has a plugins field
     * @param int $report_id the id of the report that we will
     * check if it has a the plugins field
     *
     * @param $pluginname
     * @return    bool true or false
     */
    public function	has_plugin_field($report_id,$pluginname)	{

        $sql	=	"SELECT			*
    				 FROM			{block_ilp_plugin} as p,
    				 				{block_ilp_report_field} as rf
    				 WHERE			rf.plugin_id	=	p.id
    				 AND			rf.report_id	=	:report_id
    				 AND			p.name			=   :pluginname";

        return 		$this->dbc->get_records_sql($sql, array('report_id'=>$report_id, 'pluginname'=>$pluginname));
    }


    /**
     * Fetch the id, report_id, user_id, and pass/fail state of all reports for the
     * given users
     *
     * @param	array of int $users users to examine
     *
     * @return	array of objects (id, report_id, user_id, state)
     */
    public  function fetch_all_report_entries_with_state($users)	{

        $r=array();
        if(empty($users))
        {
            return $r;
        }

        $where=' where e.user_id in ('.implode($users,',').')';

        $sql = "SELECT e.id, e.report_id, e.user_id, pi.passfail as state
                 FROM {block_ilp_entry}  as e
                   LEFT JOIN  ({block_ilp_plu_ste_ent} as pe
                   JOIN   {block_ilp_plu_ste_items} as pi on pi.id=pe.parent_id)  on e.id=pe.entry_id $where";

        foreach ($this->dbc->get_recordset_sql($sql) as $item)
        {
            $r[$item->report_id][$item->user_id][]=$item;
        }

        return $r;

    }

    /**
     * Count the number of entries in the given report with a pass state
     *
     * @param    int $report_id    the id of the report whose entries will be counted
     * @param    int $user_id the id of the  user whose entries will be counted
     * @param    int $state the state that the report entry should have
     * @param    bool $count true if the function should return a count or false to return the
     * entry records (defaults true)
     *
     * @param bool $entry_id
     * @return    mixed int the number of entries or false
     */
    public  function count_report_entries_with_state($report_id,$user_id,$state,$count=true,$entry_id=false)	{

        $select	=	(!empty($count))	? "count(e.id)" : " e.* ";
        $params = array('report_id'=>$report_id, 'user_id'=>$user_id, 'state'=>$state);
        $entrysql = "";

        if(!empty($entry_id)){
            $params['entry_id'] = $entry_id;
            $entrysql =  " AND e.id = $entry_id ";
        }

        $sql	=	"SELECT		$select
    					 FROM 		{block_ilp_entry}  as e,
    					 			{block_ilp_plu_ste_ent} as pe,
    					 			{block_ilp_plu_ste_items} as pi
    					 WHERE		e.id			=	pe.entry_id
    					 AND		pe.parent_id	=	pi.id
    					 AND		e.report_id		=	:report_id
    					 AND		e.user_id		=	:user_id
    					 AND		pi.passfail		=	:state
    					            $entrysql";

        return 		(!empty($count)) ? $this->dbc->count_records_sql($sql, $params) : $this->dbc->get_records_sql($sql, $params);
    }


    /**
     * Count the number of entries in the given report with a deadline that has passed
     * the given time
     *
     * @param	int $report_id	the id of the report whose entries will be counted
     * @param	int $user_id the id of the  user whose entries will be counted
     * @param	array $entries a list of entry ids that should be checked to see
     * if they are overdue
     * @param	int	$time a unix timestamp
     *
     * @return	mixed int the number of entries or false
     */
    public	function count_overdue_report($report_id,$user_id,$entries,$time)	{

        $params = array('report_id'=>$report_id,
            'user_id'=>$user_id,
            'time'=>$time,
            'state'=>ILP_STATE_UNSET,
            'deadline'=>ILP_DATEFIELD_DEADLINE);

        $entriessql	= 	(!empty($entries))	? "AND e.id IN (".implode($entries,',').")" : "";


        //$sql	=	"SELECT		count(e.id)
        $sql	=	"SELECT		e.id
    					 FROM 		{block_ilp_entry}  as e,
    					 			{block_ilp_plu_datf_ent} as	datfent,
    					 			{block_ilp_plu_datf}    as	datf,
    					 			{block_ilp_plu_ste_ent} as pe,
    					 			{block_ilp_plu_ste_items} as pi
    					 WHERE		e.id			    =	datfent.entry_id
    					 AND        datf.id             =   datfent.parent_id
    					 AND		e.report_id			=	:report_id
    					 AND		e.user_id			=	:user_id
    					 AND		datfent.value		<	:time
                         AND        datf.datetype       =   :deadline
    					 AND		e.id		        =	pe.entry_id
    					 AND		pe.parent_id	    =	pi.id
    					 AND		pi.passfail         =   :state
    					            {$entriessql}";

        $rst = $this->dbc->get_records_sql($sql, $params);
        $c = count( $rst );
        return	$c;

    }


    /**
     * Returns a list of reports that have deadline dates that fall between the given timestamps
     * and are in
     *
     * @param	$ltimestamp	lower time stamp for the records that will be retrieved
     * @param	$utimestamp	upper time stamp for the records that will be retrieved
     */
    public	function get_reports_in_period($ltimestamp,$utimestamp)	{

        $sql	=	"SELECT 		e.*, r.*,datfent.value as deadline, datfent.id AS id
					   FROM			{block_ilp_entry} 			AS e,
									{block_ilp_plu_datf_ent}	AS datfent,
									{block_ilp_plu_datf}    	AS datf,
									{block_ilp_plu_ste_items}	AS stitems,
									{block_ilp_report}			AS r,
									{block_ilp_plu_ste_ent}		AS stent

						WHERE		stitems.passfail = 0
						  AND		datfent.value >= :ltimestamp
						  AND		datfent.value <  :utimestamp
						  AND		stent.parent_id = stitems.id
						  AND		e.id 	=	datfent.entry_id
						  AND		e.id 	=	stent.entry_id
						  AND       datf.id = datfent.parent_id
						  AND		e.report_id	= r.id
						  AND       datfent.emailsent = 0
						  AND       datf.reminder = 1
						  AND       r.deleted     = 0";

        return 		$this->dbc->get_records_sql($sql, array('ltimestamp'=>$ltimestamp, 'utimestamp'=>$utimestamp));
    }


    /**
     * Update email status to 'sent' (1)
     * @param	object $updateemail
     *
     * @return  true if update successful or false
     */
    public function update_emailsent_status($updateemail){

        return $this->update_record('block_ilp_plu_datf_ent', $updateemail);
    }



    /**
     * Returns the last updated entry for the given student in the given report
     *
     * @param	int $report_id	the id of the report whose entry will be returned
     * @param	int $user_id the id of the user whose entry will be returned
     *
     * @return	mixed object the  of entries or false
     */
    public function	get_lastupdatedentry($report_id,$user_id)	{
        $sql='SELECT id, timemodified tm FROM {block_ilp_entry} e
              WHERE report_id=:report_id AND user_id=:user_id ORDER BY timemodified desc';

        return $this->dbc->get_records_sql($sql, array('report_id'=>$report_id, 'user_id'=>$user_id), 0, 1);
    }

    /**
     * Returns the timestamp for the last update to entries for the given report and user_id
     * Differs from get_lastupdatedentry in that this only returns the timestamp and checks
     * for updates to comments and user status
     *
     * @param	int $report_id	the id of the report whose entry will be returned
     * @param	int $user_id the id of the user whose entry will be returned
     * @param	bool $statuscheck should the status field be checked to see if it has been updated
     *
     * @return	mixed object the  of entries or false
     */
    public  function get_lastupdatetime($report_id,$user_id,$statuscheck=true)    {

        $statussql  = "";

        $params = array('report_id1'=>$report_id, 'report_id2'=>$report_id,'user_id1'=>$user_id, 'user_id2'=>$user_id);

        if (!empty($statuscheck)) {
            $params['user_id3'] = $user_id;
            $statussql  =    "UNION
                             (
                                SELECT	timemodified
                                 FROM	{block_ilp_user_status}
                                 WHERE	user_id		= :user_id3'
                              )";
        }


        $sql    =   "SELECT MAX(timemodified) AS timemodified
                     FROM (
                            (
                                SELECT MAX(timemodified) AS timemodified
                                FROM	{block_ilp_entry}
                                WHERE	report_id	=	:report_id1
                                AND 	user_id		=	:user_id1
                            )
                            UNION
                            (
                                SELECT 	MAX(ec.timemodified) AS timemodified
                                FROM 	{block_ilp_entry_comment} AS ec,
                                        {block_ilp_entry} AS e
                                WHERE 	entry_id = e.id
                                AND	report_id	=	:report_id2
                                AND	user_id		=	:user_id2
                            )
                            {$statussql}
			            ) AS lastreportupdate
                     ";

        return $this->dbc->get_record_sql($sql, $params);

    }




    /**
     * Returns the plugin record that has the matching name in the given table
     *
     * @param string $tablename the name of the plugin table that willbe queried
     * @param string $pluginname the name of the plugin we are looking for
     * @return mixed object containing the plugin record or false
     */
    function get_plugin_by_name($tablename,$pluginname) {
        return $this->dbc->get_record($tablename,array('name'=>$pluginname));
    }

    /**
     * Get all state items for the given report
     *
     * @param    int $report_id    the id of the report whose entries will be counted
     * @param $pluginname
     * @internal param int $user_id the id of the  user whose entries will be counted
     * @internal param int $state the state that the report entry should have
     *
     * @return    mixed int the number of entries or false
     */
    public	function get_report_state_items($report_id,$pluginname)	{


        $sql	=	"SELECT		i.name as name,
    								i.value as value,
    								i.id as id
    					 FROM 		{block_ilp_report_field} as rf,
    					 			{block_ilp_plugin} as p,
    					 			{block_ilp_plu_ste} as s,
    					 			{block_ilp_plu_ste_items} as i

    					 WHERE		rf.id			=	s.reportfield_id
    					 AND		p.id			=	rf.plugin_id
    					 AND		s.id			=	i.parent_id
    					 AND		rf.report_id	=	:report_id
    					 AND		p.name			=	:pluginname
    					 ";

        return 		$this->dbc->get_records_sql($sql, array('report_id'=>$report_id, 'pluginname'=>$pluginname));
    }

    /**
     * Returns the id of the item with the given value
     *
     * @param $tablename
     * @param    int $parent_id    the id of the state item record that is the parent of the item
     * @param    int $itemvalue the actual value of the field
     * @param    string $keyfield field from $itemtable to use as key
     * @param bool|string $itemtable name of item table to use if this element type does not follow the '_items' naming convention
     *
     * @return    mixed object or false
     */
    public function get_state_item_id($tablename,$parent_id,$itemvalue, $keyfield='id', $itemtable=false )	{

        $tablename              =	( !empty($itemtable) ) ? $itemtable : $tablename."_items";
        $params[$keyfield]      =   $itemvalue;

        if( !$itemtable )	{
            $params['parent_id']  =   $parent_id;
        }

        return 		$this->dbc->get_record($tablename, $params);
    }

    /**
     * Creates a entry comment record in the database
     *
     * @param	object	$comment an object contain the information to be saved to the database
     *
     * @return	mixed int the id of the created record or false
     */
    function  create_entry_comment($comment)	{
        return $this->insert_record('block_ilp_entry_comment', $comment);
    }

    /**
     * Updates a entry comment record in the database
     *
     * @param	object	$comment an object contain the information to be saved to the database
     *
     * @return	bool true or false
     */
    function  update_entry_comment($comment)	{
        return $this->update_record('block_ilp_entry_comment', $comment);
    }


    /**
     * Returns all comments attached to a entry
     *
     * @param	int	$entry_id	the id of the entry whose comments we are retrieving
     *
     * @return	mixed  array of recordset objects or bool false if nothing found
     */
    function  get_entry_comments($entry_id)	{
        return	$this->dbc->get_records('block_ilp_entry_comment',array('entry_id'=>$entry_id));
    }

    /**
     * Returns the entry comment with the given id
     *
     * @param $comment_id
     * @internal param int $commnet_id the id of the comment being retrieved
     *
     * @return    mixed  object containing the record or bool false
     */
    function get_comment_by_id($comment_id)	{
        return 	$this->dbc->get_record('block_ilp_entry_comment',array('id'=>$comment_id));
    }

    /**
     * Returns the items that can be used for user status, note this should always be the first status field
     * created so item parent ids should have a value of 1
     *
     * @param int $parent_id
     * @return    mixed  object containing the record or bool false
     */
    function get_user_status_items($parent_id=1)	{
        return $this->dbc->get_records('block_ilp_plu_sts_items',array('parent_id'=>$parent_id));
    }

    /**
     * This function sets the status of a report sending to vault or bringing back from valut
     *
     * @param $report_id
     * @param $status
     * @return    mixed  object containing the record or bool false
     */
    function set_report_vault_status ($report_id,$status)	{
        return $this->dbc->set_field('block_ilp_report','vault', $status, array('id'=>$report_id));
    }

    /**
     * This function sets the status of a report enabled or disabled
     *
     * @param $report_id
     * @param $status
     * @return    mixed  object containing the record or bool false
     */
    function set_report_status ($report_id,$status)	{
        //return $this->dbc->set_field('block_ilp_report',array('status'=>$status),array('id'=>$report_id));
        return $this->dbc->set_field('block_ilp_report','status', $status, array('id'=>$report_id));
    }

    /**
     * This function sets the delete field of a reportd
     *
     * @param $report_id
     * @param $deleted
     * @return    mixed  object containing the record or bool false
     */
    function delete_report($report_id,$deleted)	{
        return $this->dbc->set_field('block_ilp_report','deleted', $deleted, array('id'=>$report_id));
    }

    /**
     * @param $statusfield
     * @param string $tablename
     */
    function create_statusfield($statusfield, $tablename = 'block_ilp_plu_sts')	{
        $this->insert_record($tablename, $statusfield);
    }



    /**
     * Creates a user status record
     *
     * @param $userstatus object containing data to saved
     *
     * @return	mixed  int the new record id or bool false
     */
    function create_userstatus($userstatus)	{
        $this->insert_record('block_ilp_user_status',$userstatus);
    }


    /**
     * updates a user status record
     *
     * @param $userstatus object containing data to saved
     *
     * @return	bool true if update successful false if not
     */
    function update_userstatus($userstatus)	{
        return $this->update_record('block_ilp_user_status',$userstatus);
    }

    /**
     * Returns the status of the user in the ilp of the user with the given user id
     *
     * @param int $user_id the users whose status you want to retrieve
     *
     * @return mixed object contain the status of the user or false
     */
    function get_user_status($user_id)	{
        return $this->dbc->get_record('block_ilp_user_status',array('user_id'=>$user_id));
    }

    /**
     * Returns the regions for a template
     *
     * @param int $template_id the id of the template whose regions you want to retrieve
     *
     * @return mixed array of objects containing the regions or false
     */
    function get_template_regions($template_id) {
        return $this->dbc->get_records('block_ilp_dash_temp_region',array('template_id'=>$template_id));
    }

    /**
     * Gets the dashboard plugin record for the
     *
     * @param $name
     * @internal param string $plugin_name the name of the template
     *
     * @return mixed array of objects containing the regions or false
     */
    function get_dashboard_plugin_by_name($name)	{
        return $this->dbc->get_record('block_ilp_dash_plugin',array('name'=>$name));
    }

    /**
     * Create a record in the block_ilp_dash_region_plugin table
     *
     * @param object $regionplugin the record to be saved to the
     * block_ilp_dash_region_plugin table
     *
     * @return mixed int the id of the new record or false
     */
    function create_region_plugin($regionplugin) {
        return $this->insert_record('block_ilp_dash_region_plugin', $regionplugin);
    }

    /**
     * Returns all items with a parent id matching the one given
     *
     * @param int $id the parent id the items returned should have
     *
     * @return mixed array of recordset objects or false
     */
    function get_status_items($id)	{
        return $this->dbc->get_records('block_ilp_plu_sts_items',array('parent_id'=>$id));
    }

    /**
     * Returns the status item with the id given
     *
     * @param int $id the id of the status item we want to return
     *
     * @param string $tablename
     * @return mixed object containing recordset with matching id or false
     */
    function get_status_item_by_id($id, $tablename = 'block_ilp_plu_sts_items')	{
        return $this->dbc->get_record($tablename,array('id'=>$id));
    }

    /**
     * Returns the tutor of the user with the given id
     *
     * @param int $user_id the id of the user whose tutor we want to fid
     *
     * @return mixed array of objects containing recordsets of user who
     * tutor the given user or bool false
     */
    function get_student_tutors($user_id)	{

        $sql	=	"SELECT 	tu.*
                       FROM 	{role_assignments} AS ra,
                       			{context} AS c,
                       			{user} AS u,
                       			{user} AS tu
                      WHERE 	ra.contextid = c.id
                        AND 	c.instanceid = u.id
                        AND 	u.id =	:user_id
                        AND		ra.userid = tu.id
                        AND 	c.contextlevel = ".CONTEXT_USER;

        return $this->dbc->get_records_sql($sql, array('user_id'=>$user_id));
    }

    /**
     * Returns the all tutees for the given user
     *
     * @param int $user_id the id of the user whose tutee we want to find
     *
     * @return mixed array of object containing all users who are tutored
     * by the given user or bool false
     */
    function get_user_tutees($user_id) {

        $sql	=	"SELECT 	u.id
                       FROM 	{role_assignments} ra, {context} c, {user} u
                      WHERE 	ra.userid = :user_id
                        AND 	ra.contextid = c.id
                        AND 	c.instanceid = u.id
                        AND 	c.contextlevel = ".CONTEXT_USER
            ." GROUP BY u.id";

        return	$this->dbc->get_records_sql($sql, array('user_id'=>$user_id));
    }

    /**
     * Returns the user ids of all users enrolled into the given course
     *
     * @param int $course_id the id of the course whose enrolled users
     * we want to retrieve
     *
     * @param null $group_id
     * @param bool $fullrecords
     * @return mixed array of object containing all users enrolled in the course
     * or bool false
     */
        function get_course_users($course_id,$group_id=null,$fullrecords=false) {

	if(empty($course_id))
	{
	    return $this->dbc->get_records('user',array('deleted'=>0),'lastname','id');
	}

        $grouptable		=	(!empty($group_id)) ? " INNER JOIN {groups_members} as gm on u.id = gm.userid " : "";
        $groupwhere = "";

        $context = context_course::instance($course_id);

        /// Get all users that should appear in this list
        list($esql, $params) = get_enrolled_sql($context, 'block/ilp:reviewee', $group_id);

        if(!empty($group_id)){
            $params['group_id'] = $group_id;
            $groupwhere = "AND gm.groupid = :group_id ";
        }

        $fields= $fullrecords ? 'distinct u.*' : ' distinct u.id' ;

        $sql	=	"SELECT		$fields
	 					  FROM		{user} u
	 					            LEFT JOIN ($esql) eu ON eu.id=u.id
	 					  			{$grouptable}
	 					  WHERE		u.deleted = 0
	 					  AND       eu.id=u.id
	 					  			{$groupwhere}";

        if($fullrecords)
           return $this->dbc->get_recordset_sql($sql, $params);

        return $this->dbc->get_records_sql($sql, $params);
    }


    /**
     * Returns a paginated list of all students
     *
     * @param object $flextable the table where the matrix will be displayed
     * @param array  $student_ids an array contain the ids of studdents to be displayed
     * @param int    $status_id the status id that should be matched
     * @param bool   $includenull allows those without a status to be displayed
     *
     * @return mixed array of object containing all students in a course or false
     */
    function get_students_matrix($flextable,$student_ids,$status_id, $includenull=false)
    {
        $data=array();
        $count=0;
        ($start=$flextable->get_page_start() or $start=0);
        ($end=$flextable->get_page_size()+$start or $end=1e9);

        foreach($this->get_studentlist_details($student_ids,$status_id,
            $flextable->get_sql_where(), $flextable->get_sql_sort(),
            $includenull)
                as $item)
        {
            if($count>=$start and $count<$end)
            {
                $data[$item->id]=$item;
            }
            $count++;
        }

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $data;
    }

    /**
     * @param $student_ids
     * @param $status_id
     * @param string $sql_where
     * @param string $sql_sort
     * @param bool $includenull
     * @return array
     */
    function get_studentlist_details($student_ids,$status_id, $sql_where='',$sql_sort='',$includenull=false)
    {
        global $CFG, $DB;

        if(empty($student_ids))
        {
            return array();
        }

        $secondstatus_leftjoin = '';

        if (strpos($sql_sort, 'warningstatus_title') !== false) {
            $secondstatus_leftjoin = ' LEFT JOIN {block_ilp_plu_wsts_ent} secondsts ';
            $secondstatus_leftjoin .= ' ON (secondsts.user_id = u.id) ';
            $sql_sort = str_replace('warningstatus_title', 'secondsts.value', $sql_sort);
        }

        $select = "SELECT 		u.id as id,
        				u.idnumber as idnumber,
        				u.firstname as firstname,
        				u.lastname as lastname,
                                        u.username as username,
                        u.firstnamephonetic as firstnamephonetic,
                        u.lastnamephonetic  as lastnamephonetic,
                        u.middlename as middlename,
                        u.alternatename as alternatename,
        				si.id as u_status_id,
        				si.name	as u_status,
        				si.icon	as u_status_icon,
        				si.display_option as u_display_option,
        				si.bg_colour as bg_colour,
        				si.description	as u_status_description,
        				u.picture as picture,
        				u.imagealt as imagealt,
        				u.email as email ";

        $from = " FROM 			{user} as u LEFT JOIN {block_ilp_user_status} as us on (u.id = us.user_id) LEFT JOIN
        						{block_ilp_plu_sts_items} as si on (us.parent_id = si.id)" . $secondstatus_leftjoin;

        $where = "";

        if (!empty($student_ids) || !empty($status_id)) {
            $where = " WHERE ";
            $and = '';

            $studentssql	=	 (!empty($student_ids)) ? " u.id IN (".implode(",",$student_ids).")" : "" ;
            $statussql		=	 (!empty($status_id)) ? " si.id = {$status_id} " : '';

            //if the
            $statussql		=	 (!empty($includenull)) ? " ( si.id = {$status_id} OR si.id IS NULL)"  : $statussql;

            if (!empty($student_ids)) {
                $where .= " {$studentssql} ";
                $and = 'AND';
            }

            if (!empty($status_id)) {
                $where .= " {$and} {$statussql} ";
            }
        }


        $sort = "";

        // fetch any additional filters provided by the caller
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any extra sort keys provided by the caller
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        return $this->dbc->get_records_sql($select.$from.$where.$sort,null);
    }

    /**
     *
     * Returns the latest entry reocrd for the given student
     *
     * @param $user_id
     * @internal param int $student_id the id of the student whose
     * last entry will be retrieved
     *
     * @return mixed
     */
    function get_lastupdate($user_id) {

        $params = array('user_id1'=>$user_id, 'user_id2'=>$user_id, 'user_id3'=>$user_id);

        $sql	=	"SELECT MAX(timemodified) AS timemodified
                     FROM (
                            (
                                SELECT MAX(timemodified) AS timemodified
                                FROM	{block_ilp_entry}
                                WHERE	user_id		=   :user_id1
                            )
                            UNION
                            (
                                SELECT 	MAX(ec.timemodified) AS timemodified
                                FROM 	{block_ilp_entry_comment} AS ec,
                                        {block_ilp_entry} AS e
                                WHERE 	entry_id = e.id
                                AND	    user_id		=	:user_id2
                            )
							UNION
                             (
                                SELECT	timemodified
                                 FROM	{block_ilp_user_status}
                                 WHERE	user_id		=	:user_id3
                              )

			            ) AS lastreportupdate";


        return	$this->dbc->get_record_sql($sql, $params);
    }

    /**
     *
     * Returns all courses that the user with the given id is enrolled
     * in
     *
     * @param int $user_id	the id of the user whose course we will retrieve
     *
     * @return  array of recordset objects or bool false
     */
    function get_user_courses($user_id)	{
        global $CFG;

        $courses	=	enrol_get_users_courses($user_id, false,NULL,'fullname DESC');

        return $courses;
    }



    /**
     * Returns all currently installed mis plugins
     *
     * @return array of recordset objects or bool false
     */
    function get_mis_plugins() 	{
        global $CFG;

        $tableexists =  in_array('block_ilp_mis_plugin',$this->dbc->get_tables());

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_mis_plugin', array()) : false;
    }


    /**
     * Returns the mis plugin with the id given
     *
     * @param int $plugin_id
     *
     * @return mixed object containing the plugin record selected
     */
    function get_mis_plugin_by_id($plugin_id)	{
        return $this->dbc->get_record('block_ilp_mis_plugin',array('id'=>$plugin_id));
    }


    /**
     * Returns the mis plugin with the name given
     *
     * @param int $pluginname
     *
     * @return mixed object containing the plugin record selected
     */
    function get_mis_plugin_by_name($pluginname)	{
        return $this->dbc->get_record('block_ilp_mis_plugin',array('name'=>$pluginname));
    }

    /**
     * Returns all currently installed stats plugins
     *
     * @return array of recordset objects or bool false
     */
    function get_graph_plugins() 	{
        global $DB;
        $dbman = $DB->get_manager();
        if ($dbman->table_exists('block_ilp_graph_plugin')) {
            return $DB->get_records('block_ilp_graph_plugin');
        }
        return array();
    }

    /*
     * Adds all field items from a previous parent field to a new parent field.
     *  (E.g. checkbox options associated with the previous field)
     * @param $old_parent_id
     * @param $new_parent_id
     * @param $tablename
     * @return array of ids of items created
     */
    /**
     * @param $old_parent_id
     * @param $new_parent_id
     * @param $tablename
     * @return array
     */
    function add_old_items_to_new_field($old_parent_id, $new_parent_id, $tablename) {
        global $DB;
        $dbman = $DB->get_manager();
        $newitem_ids = array();
        $items_table = $tablename . '_items';
        if ($dbman->table_exists($items_table)) {
            $old_items = $this->dbc->get_records($items_table, array('parent_id'=>$old_parent_id));
            if ($old_items) {
                foreach ($old_items as $old_item) {
                    $new_item = clone $old_item;
                    $new_item->parent_id = $new_parent_id;
                    unset($new_item->id);
                    $newitem_ids[] = $this->dbc->insert_record($items_table, $new_item);
                }
            }
        }
        return $newitem_ids;
    }

    /**
     * Returns the graph plugin with the id given
     *
     * @param int $plugin_id
     *
     * @return mixed object containing the plugin record selected
     */
    function get_graph_plugin_by_id($plugin_id)	{
        return $this->dbc->get_record('block_ilp_graph_plugin',array('id'=>$plugin_id));
    }


    /**
     * Returns the grap plugin with the name given
     *
     * @param int $pluginname
     *
     * @return mixed object containing the plugin record selected
     */
    function get_graph_plugin_by_name($pluginname)	{
        return $this->dbc->get_record('block_ilp_graph_plugin',array('name'=>$pluginname));
    }

    /**
     * Returns all currently installed tab plugins
     *
     * @return array of recordset objects or bool false
     */
    function get_tab_plugins() 	{
        global $CFG;

        $tableexists =  in_array('block_ilp_dash_tab',$this->dbc->get_tables());



        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_tab', array()) : false;
    }


    /**
     * Returns the tab plugin with the id given
     *
     * @param int $plugin_id
     *
     * @return mixed object containing the plugin record selected
     */
    function get_tab_plugin_by_id($plugin_id)	{
        return $this->dbc->get_record('block_ilp_dash_tab',array('id'=>$plugin_id));
    }


    /**
     * Returns the tab plugin with the name given
     *
     * @param int $pluginname
     *
     * @return mixed object containing the plugin record selected
     */
    function get_tab_plugin_by_name($pluginname)	{
        return $this->dbc->get_record('block_ilp_dash_tab',array('name'=>$pluginname));
    }


    /**
     * Updates the given mis plugin record
     *
     * @param object $misrecord
     *
     * @return bool true or false
     */
    function update_mis_plugin($misrecord) {
        return $this->update_record('block_ilp_mis_plugin',$misrecord);
    }

    /**
     * Used to check if a report field with the given label already exists in the report
     * with the given report_id
     *
     * @param    string $label    the label that is being test to see if it exists
     * @param    int $report_id the id of the report that will be checked
     *
     * @param $field_id
     * @return    mixed array of recordsets or bool false
     */
    function label_exists($label,$report_id,$field_id)	{

        //thsi code is needed due to a substr_count in the
        //moodle_database.php file (line 666 :-( ) it causes
        //an error whenever a label has an ? in it
        $label = str_replace('?', '.', $label);


        $params = array('label'=>$label,'report_id'=>$report_id);

        $currentfieldsql = '';

        if(!empty($field_id)){
            $params['field_id'] = $field_id;
            $currentfieldsql = "AND id != :field_id";

        }

        $sql	=	'SELECT		*
  					 FROM		{block_ilp_report_field}
  					 WHERE		label		=	:label
  					 AND		report_id	=	:report_id
  					  '.$currentfieldsql;

        return $this->dbc->get_records_sql($sql, $params);
    }



    /**
     * Returns the record with a tablename matching the one given
     *
     * @param	string $tablename	the tablename that will be used to match
     *
     * @return	mixed object recordset or bool false
     */
    function get_plugin_by_tablename($tablename)	{
        return $this->dbc->get_record('block_ilp_plugin',array('tablename'=>$tablename));
    }

    /**
     * Returns the record with a class name matching the one given in the given table
     *
     * @param    string $tablename the name of the table that will be queried
     *
     * @param $classname
     * @internal param string $name the name of the plugin that we want to match
     * @return    mixed object recordset or bool false
     */
    function get_plugin_record_by_classname($tablename,$classname) {
        return $this->dbc->get_record($tablename,array('name'=>$classname));
    }

    /**
     * @param $name
     * @return mixed
     */
    function setting_exists($name)	{
        return $this->dbc->get_record('config_plugins',array('name'=>$name,'plugin'=>'block_ilp'));
    }

    /**
     * @param $name
     * @param $value
     * @return bool|int
     */
    function insert_config_setting($name,$value) {
        $setting	=	new stdClass();
        $setting->plugin	=	'block_ilp';
        $setting->name		=	$name;
        $setting->value		=	$value;

        return $this->dbc->insert_record('config_plugins',$setting);
    }

    /**
     * @param $setting
     * @return bool
     */
    function update_config_setting($setting) {
        return $this->dbc->update_record('config_plugins',$setting);
    }

    /**
     * @param $entry_id
     * @param $reportfield_id
     * @return mixed
     */
    function get_report_coursefield($entry_id,$reportfield_id)	{

        $sql	=	"SELECT 	*
    				 FROM		{block_ilp_plu_crs} as c,
    				 			{block_ilp_plu_crs_ent} as ce
    				 WHERE		c.id = ce.parent_id
    				 AND		c.reportfield_id 	= 	:reportfield_id
    				 AND		entry_id			=	:entry_id ";

        return 	$this->dbc->get_record_sql($sql, array('reportfield_id'=>$reportfield_id, 'entry_id'=>$entry_id));
    }

    /**
     *
     * Adds an event to the calendar of a user
     * @param object $event a object containing details of an event tot be saved into a users calendar
     */
    function save_event($event)	{

        //we can not user add_event in moodle 2.0 as it requires the user to have persmissions to add events to the
        //calendar however this capability check can be bypassed if we use the calendar event class
        global $CFG, $USER;


        require_once($CFG->dirroot.'/calendar/lib.php');
        $calevent = new calendar_event($event);
        $calevent->update($event,false);

        if ($calevent !== false) {
            return $calevent->id;
        }
    }

    /*
     * The Moodle update event function is converting the link from html entities to text;
     * This ensures that they remain as HTML entities.
     */
    /**
     * @param $event
     * @param $ilp_profile_link
     */
    public function update_event_description($event, $ilp_profile_link) {
        $ilp_profile_link = html_entity_decode($ilp_profile_link);
        $event_descupdate = new stdClass();
        $event_descupdate->id = $event->id;
        $event_descupdate->description = $ilp_profile_link;
        $this->dbc->update_record('event', $event_descupdate);
    }

    /**
     *
     * Updates a calendar event with new details
     * @param object $event a object containing details of an event tot be saved into a users calendar
     */
    function update_event($event)	{

        global $CFG, $USER;

        require_once($CFG->dirroot.'/calendar/lib.php');
        $calevent = calendar_event::load($event->id);
        return $calevent->update($event,false);
    }

    /**
     *
     * Returns a record from the block_cal_event table
     * @param int $entry_id the id of the entry that the record was creared for
     * @param int $reportfield_id the id of the reportfield that the report was created for
     */
    function get_calendar_event($entry_id,$reportfield_id)	{

        $sql	=	"SELECT		e.*
    				 FROM 		{block_ilp_cal_events} as ce,
    				 			{event} as e
    				 WHERE		e.id = ce.event_id
    				 AND		ce.reportfield_id	=	:reportfield_id
    				 AND		ce.entry_id			=	:entry_id ";

        return $this->dbc->get_record_sql($sql, array('reportfield_id'=>$reportfield_id, 'entry_id'=>$entry_id));
    }

    /**
     * @param $reportfield_id
     * @return mixed
     */
    function get_reportfield_by_id($reportfield_id)	{
        return $this->dbc->get_record('block_ilp_report_field',array('id'=>$reportfield_id));
    }

    /**
     * @param $record
     * @return mixed
     */
    function create_event_cross_reference($record) {
        return $this->insert_record('block_ilp_cal_events',$record);
    }

    /**
     * Returns the plugin record of the report field with the given id
     *
     * @param   int reportfield_id the id of the rpeort field whose plugin will be returned
     *
     * @return  mixed object the plugin record or false
     */
    function get_reportfield_plugin($reportfield_id)    {
        $sql    =   "SELECT     p.*
                      FROM      {block_ilp_plugin} as p,
                                {block_ilp_report_field} as rf
                      WHERE     p.id  = rf.plugin_id
                      AND       rf.id    = :id";

        return $this->dbc->get_record_sql($sql,array('id'=>$reportfield_id));
    }


    /**
     * Creates a new report graph record
     *
     * @param object $reportgraph an object containing the data to be saved
     * @return mixed the id of the inserted record or false
     */
    function create_report_graph($reportgraph) {
        return $this->insert_record("block_ilp_report_graph",$reportgraph);
    }

    /**
     * Updates the record in the report graph table with a id matching the one
     * in the given object
     *
     * @param object $reportgraph an object containing the data on the record
     * @return bool true or false depending on result of query
     */
    function update_report_graph($reportgraph) {
        return $this->update_record('block_ilp_report_graph',$reportgraph);
    }

    /*******************************************
     * Gets all graph records attached to the given report
     *
     * @param int $report_id the id of the report whose graphs you want to retrieve
     * return mixed bool false or
     */
    function get_report_graphs($report_id)    {
        return $this->dbc->get_records('block_ilp_report_graph',array('report_id'=>$report_id));
    }

    /**
     * Returns the ilp_report_graph record with the id given
     *
     * @param $reportgraph_id
     * @internal param int $graph_id the id of the report graph that you want to get the data on
     * @return object containing data from the report graph record that matches criteria
     */
    function get_report_graph_data($reportgraph_id) {
        return $this->dbc->get_record("block_ilp_report_graph",array("id"=>$reportgraph_id));
    }


    /**
     * Returns the record from the given ilp form element plugin table with the reportgraph_id given
     *
     * @param int    $reportgraph_id the id of the element in the given table
     * @param string $tablename the name of the plugin table that holds the data that will be retrieved
     * @return object containing plugin record that matches criteria
     */
    function get_graph_by_report($tablename,$reportgraph_id) {
        return $this->dbc->get_records($tablename,array("reportgraph_id"=>$reportgraph_id));
    }

    /**
     * Deletes a record from the specified table with the matching criteria
     *
     * @param   string $tablename the name of the table that the record
     * will be deleted form
     * @param $params
     * @param array $extraparams
     * @internal param int $param criteria that should be matched to delete the field. Criteria must be specified
     * no blanket deletes are allowed
     *
     * @return mixed true or false
     */
    function delete_record ( $tablename, $params, $extraparams=array() ) {
        return (!empty($params)) ? $this->delete_records( $tablename, $params, $extraparams ) : false;
    }


    /**
     * returns a preference set for a particular user or users of the ilp. Please note that although all
     * of the functions params can be set to null this will result in false being returned to the user.
     *
     * @param int $report_id    the id of a report
     * @param int $entry_id     the id of a entry
     * @param string $action
     * @param int $user_id
     * @param int $course_id
     */
    function get_preferences($report_id=null,$entry_id=null,$action=null,$user_id=null,$course_id=null)  {

        if (empty($report_id) && empty($entry_id) && empty($action) && empty($user_id) && empty($course_id))
            return false;


        $params     =   "WHERE ";
        $and        =   "";

        $parameters = array();

        if (!empty($report_id))     {
            $parameters['report_id'] = $report_id;
            $params .=  " report_id =   :report_id";
            $and    =   " AND ";
        }

        if (!empty($entry_id))  {
            $parameters['entry_id'] = $entry_id;
            $params .=  "{$and} entry_id    =   :entry_id";
            $and    =   " AND ";
        }

        if (!empty($action))  {
            $parameters['action'] = $action;
            $params .=  "{$and} action    =   :action";
            $and    =   " AND ";
        }

        if (!empty($user_id))  {
            $parameters['user_id'] = $user_id;
            $params .=  "{$and} user_id    =   :user_id";
            $and    =   " AND ";
        }

        if (!empty($course_id))  {
            $parameters['course_id'] = $course_id;
            $params .=  "{$and} course_id    =   :course_id";
            $and    =   " AND ";
        }

        $sql    =   "SELECT     *
                     FROM       {block_ilp_preferences}
                     {$params}
                     ORDER BY id  DESC";

        return $this->dbc->get_records_sql($sql, $parameters);
    }



    /**
     * Creates a preference record in the database
     *
     * @param object object containning preference data
     *
     * @return mixed int id of new record or false
     *
     */
    function create_preference($preference)    {
        return $this->insert_record("block_ilp_preferences",$preference);
    }





    /**
     * The function returns reportfield_id for the matched calendar event
     *
     * @param int $entry_id     -the id of an entry
     *
     * @return object containing $reportfield_id or false
     *
     */
    function get_calevent_reportfield_id($entry_id){

        $sql    =   "SELECT     reportfield_id
                      FROM      {block_ilp_cal_events}
                      WHERE     entry_id  = :entry_id";

        return $this->dbc->get_record_sql($sql,array('entry_id'=>$entry_id));
    }


    /**
     * The function returns records from the calendar events
     *
     * @param int $entry_id         -the id of an entry
     * @param int $reportfield_id   -the id of the reportfield
     *
     * @return object containing data for matched events or false
     *
     */
    function get_calendar_events($entry_id,$reportfield_id)	{

        $sql	=	"SELECT		e.*
    				 FROM 		{block_ilp_cal_events} as ce,
    				 			{event} as e
    				 WHERE		e.id = ce.event_id
    				 AND		ce.reportfield_id	=	:reportfield_id
    				 AND		ce.entry_id			=	:entry_id ";

        return $this->dbc->get_records_sql($sql, array('reportfield_id'=>$reportfield_id, 'entry_id'=>$entry_id));
    }


    /**
     * The function returns ids of entries retrieved by $report_id
     *
     * @param int $report_id         -the id of a report
     *
     * @return object containing ids of entries or false
     *
     */
    function get_entries_by_report_id($report_id){
        $sql    =   "SELECT     id
                     FROM      {block_ilp_entry}
                     WHERE     report_id  = :report_id";

        return $this->dbc->get_records_sql($sql,array('report_id'=>$report_id));
    }


    /**
     * The function deletes event entries by given entry id
     *
     * @param int $entry         -the id of an entry
     *
     * @return true if records successfully deleted or false
     *
     */
    function delete_event_entry($entry) {
        return $this->delete_records( 'event', array('id'=>$entry),array());
    }





    /**
     * Saves temp data into the block_ilp_temp table data stored using this function is serialised. It should be
     * noted that only temp data should be stored using this function as the block_ilp_temp table can be purged
     *
     * @param $data the data to be serialized and saved into the temp table
     *
     * @return mixed int the id of the data thats been saved or bool false
     */
    function save_temp_data($data)    {
        $serialiseddata     =   serialize($data);

        $tempdata           =   new stdClass();
        $tempdata->data     =   $serialiseddata;

        return $this->insert_record('block_ilp_temp',$tempdata);
    }

    /**
     * Updates data stored in the block_ilp_temp table
     *
     * @param int $tempid the id of the record to be updated
     * @param mixed $data the data that will be saved
     *
     * return bool true if successful false is not
     */
    function update_temp_data($tempid,$data) {

        $serialiseddata     =   serialize($data);

        $tempdata           =   new stdClass();
        $tempdata->id       =   $tempid;
        $tempdata->data     =   $serialiseddata;

        return $this->update_record('block_ilp_temp',$tempdata);
    }

    /**
     * Returns the temp data with the given id
     *
     * @param int $id the id of the data that is being retrieved
     * @return mixed the data that was saved
     */
    function get_temp_data($id)    {
        $tempdata   =     $this->dbc->get_record('block_ilp_temp',array('id'=>$id));
        return (!empty($tempdata))  ?   unserialize($tempdata->data) :   false;
    }

    /**
     * returns data from the given table using the given field and value
     *
     * @param string $tablename the name of the table that will be queried
     * @param string $field the name of the field that will be used in the query
     * @param mixed $value a value that will be used in the query
     * @return array the result of the query
     */
    function get_entry_data($tablename,$field,$value)   {
        global $DB;

        return  $DB->get_records($tablename,array($field=> $value));
    }

    /**
     * Returns the next review date for a student on a given report if the report has
     * a next review date type datefield
     *
     * @param int   $report_id  the id of the report
     * @param int   $student_id the id of the student
     */
    function get_next_review($report_id,$student_id)  {

        $params =   array('report_id'=>$report_id,
            'student_id'=>$student_id,
            'reviewtype'=>ILP_DATEFIELD_REVIEWDATE,
            'time'=>time());

        $sql	=	    "SELECT		max(datfent.value) as review
    					 FROM 		{block_ilp_entry}  as e,
    					 			{block_ilp_plu_datf_ent} as	datfent,
    					 			{block_ilp_plu_datf}    as	datf
    					 WHERE		e.id			    =	datfent.entry_id
    					 AND        datf.id             =   datfent.parent_id
    					 AND		e.report_id			=	:report_id
    					 AND		e.user_id			=	:student_id
    					 AND		datfent.value		>	:time
                         AND        datf.datetype       =   :reviewtype";

        return  $this->dbc->get_record_sql($sql,$params);
    }

}

/**
 * Return checks the position which exist in $possarr array
 * @param $pos
 * @param $possarr
 * @return bool
 */
function checkpositions($pos, $possarr) {
    if (in_array($pos, $possarr)) {
        return true;
    } else {
        return false;
    }
}
