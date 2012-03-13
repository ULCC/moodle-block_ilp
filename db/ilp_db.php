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

/**
 * Main database class, with functions to encode and decode stuff to and from the DB
 *
 * Acts as a wrapper for {@link ilp_db_functions} with a magic method to intercept
 * function calls.
 */
class ilp_db {

    /**
     * Constructor to instantiate the db connection.
     *
     * @return void
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
        // sanatise everything coming into the database here
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

            //I have had to remove the purify call as it was causing pages to timeout in 1.9
            //this should be put back in once the ilp is moodle 2.0 only
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
            return html_entity_decode($data, ENT_QUOTES, 'utf-8');
        }
    }

    /**
     * Decodes mixed params.
     *
     * @param mixed The encoded object/array/string/etc
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
 * Databse class holding functions to actually perform the queries.
 *
 * This extends the logging class which intercepts all insert, update and delete
 * actions that are executed on the database and makes a record of what data was
 * changed. Instantiated as $dbc in the {@link ilp_db} class.
 */

class ilp_db_functions	extends ilp_logging {

    /**
     * The Moodle 2 database, or the emulator.
     *
     * @var ADODB connection
     */
    var $dbc;

    /**
     * Constructor for the ilp_db_functions class
     *
     * @return void
     */
    function __construct() {
        global $CFG, $DB;

        // if this is empty then we're using Moodle 1.9.x, so we need the 2.0 emulator
        if(empty($DB)) {
            require_once($CFG->dirroot.'/blocks/ilp/db/moodle2_emulator.php');
            $this->dbc = new moodle2_db_emulator();
        } else {
            $this->dbc = $DB;
        }

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
     * @param int    $report_id the id of the report whose fields will be retrieved
     * @return array containing report field objects that match criteria
     */
    function get_report_fields($report_id,$orderbyposition=false) {

    	global $CFG;

    	$order = (!empty($orderbyposition)) ? "ORDER BY position DESC": "";
    	$sql	=	"SELECT		*
    				 FROM 		{$CFG->prefix}block_ilp_report_field
    				 WHERE		report_id	=	{$report_id}
    				{$order}";

    	return $this->dbc->get_records_sql($sql);
    }

    /**
     * Gets the reocrd with id matching the given plugin_id
     *
     * @param string $name the name of the new form element plugin
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
        $tableexists =  (stripos($CFG->release,"2.") !== false) ?
               in_array('block_ilp_plugin',$this->dbc->get_tables())
            :
                $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_plugin}'");

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_plugin', array()) : false;

    }

    /**
     * Creates a new form element plugin record.
     *
     * @param string $name the name of the new form element plugin
     * @return mixed the id of the inserted record or false
     */
    function create_form_element_plugin($name,$tablename) {
        $type = new object();
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
        $tableexists =  (stripos($CFG->release,"2.") !== false) ?
            in_array('block_ilp_dash_plugin',$this->dbc->get_tables())
            :
            $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_dash_plugin}'");

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_plugin', array()) : false;
	}

    /**
     * Gets the full list of dashboard plugins already installed
     *
     * @return array Result objects
     */
    function get_dashboard_tabs() {
        global $CFG;

        $tableexists =  (stripos($CFG->release,"2.") !== false) ?
            in_array('block_ilp_dash_tab',$this->dbc->get_tables())
            :
            $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_dash_tab}'");

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_tab', array()) : false;
	}

    /**
     * Gets the full list of dashboard templates already installed
     *
     * @return array Result objects
     */
    function get_dashboard_templates() {
        global $CFG;

        $tableexists =  (stripos($CFG->release,"2.") !== false) ?
            in_array('block_ilp_dash_temp',$this->dbc->get_tables())
            :
            $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_dash_temp}'");

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_temp', array()) : false;
	}


    /**
     * Creates a new  plugin record.
     *
     * @param string $tablename	the name of the table the record will be saved to
     * @param string $name the name of the new form element plugin
     * @return mixed the id of the inserted record or false
     */
    function create_plugin($tablename,$name) {
        $type = new object();
        $type->name 		= $name;

        //TODO: should the dashboard plugin be enabled by default?
        $type->status 		= 1;

        return $this->insert_record($tablename, $type);
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
     *
     * @param int $report_id the id of the report that the new field will be in
     * @return int the new fields position number
     */

    function get_new_report_position() {

        $position =  $this->dbc->count_records("block_ilp_report");

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
     * @return mixed object containing the plugin record or false
     */
    function set_new_position($reportfield_id,$newposition) {
    	return $this->dbc->set_field('block_ilp_report_field',"position",$newposition,array('id'=>$reportfield_id));
    }

    /**
     * Sets the new position of a report
     *
     * @param int $report_id the id of the report whose position will be changed
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
     * @param bool $disabled should disabled reports be returned
     * @param int $position the position of fields that will be returned
     *  	greater than or less than depending on $type
     * @param  int $type determines whether fields returned will be greater than
     * 		or less than position. move up = 1 move down 0
     * @return mixed object containing the plugin record or false
     */
    function get_reports_by_position($position=null,$type=null,$disabled=true) {
        global	$CFG;

        $positionsql	=	"";
        //the operand that will be used
        if (!empty($position)) {
            $otherfield		=	(!empty($type)) ? $position-1 : $position+1;
            $positionsql 	=  "AND (position = {$position} ||  position = {$otherfield})";
        }

        $disabledsql    =   '';
        if (empty($disabled)) {
            $disabledsql    =   "AND status = 1 ";
        }

        $sql	=	"SELECT		*
					 FROM		{$CFG->prefix}block_ilp_report
					 WHERE      deleted = 0
                     {$disabledsql}
					 {$positionsql}
					 ORDER BY 	position";


        return		$this->dbc->get_records_sql($sql);
    }

    /**
     * Returns all fields in a report with a position less than or greater than
     * depending on type given. the results will include the position as well.
     * if position and type are not specified all fields are returned ordered by
     * position
     *
     * @param int $report_id the id of the report whose fields will be returned
     * @param int $position the position of fields that will be returned
     *  	greater than or less than depending on $type
     * @param  int $type determines whether fields returned will be greater than
     * 		or less than position. move up = 1 move down 0
     * @return mixed object containing the plugin record or false
     */
	function get_report_fields_by_position($report_id,$position=null,$type=null) {
		global	$CFG;

		$positionsql	=	"";
		//the operand that will be used
		if (!empty($position)) {
			$otherfield		=	(!empty($type)) ? $position-1 : $position+1;
			$positionsql 	=  "AND (position = {$position} ||  position = {$otherfield})";
		}

		$sql	=	"SELECT		*
					 FROM		{$CFG->prefix}block_ilp_report_field
					 WHERE		report_id	=	{$report_id}
					{$positionsql}
					 ORDER BY 	position";


		return		$this->dbc->get_records_sql($sql);
	}


/**
     * Delete the record from the given table with the reportfield_id matching the given id
     *
     * @param string $tablename the table that you want to delete the record from
     * @param int $id the id of the record that you want to delete
     *
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
		return $this->dbc->get_record('block_ilp_report', array('id' => $id));
	}

     /**
     * Updates the report record with the data in the given object
     * the object must contain a id param with the id of the record
     * to be updated
     *
     * @param object $reportfield an object containing the data on the record
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
    	global		$CFG;

    	$sql	=	"SELECT	*
    				 FROM	{$CFG->prefix}capabilities
    				 WHERE	name	LIKE	'block/ilp:%'";

    	return $this->dbc->get_records_sql($sql);
    }

     /**
     * returns whether any permission exists for the given report
     *
     * @return bool true or false
     */
    function permissions_exist($report_id) {
    	return $this->dbc->record_exists("block_ilp_reportpermissions",array("report_id"=>$report_id));
    }

    /**
     * Deletes all report permission
     *
     * @param int $id the id of the record that you want to delete
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
    	global $CFG;

    	$select	=	"SELECT		* ";

    	$from	=	"FROM 		{$CFG->prefix}block_ilp_report";

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
     * Create a plugin entry in the table given
     *
     * @return mixed int id of new reocrd or false
     */
    function create_plugin_entry($tablename,$pluginentry)	{
    	return $this->insert_record($tablename, $pluginentry);
    }


	/**
     * Update a plugin entry record in the table given
     *
     * @return bool true or false
     */
    function update_plugin_entry($tablename,$pluginentry)	{
    	return $this->update_record($tablename, $pluginentry);
    }

	/**
     * Returns a list of all reports currently enabled in a course
     *
     * @param int 	$course_id the id of the course who we want to
     * get report for
     *  @param int $report_id the id of the report
     * @return mixed array containing recordset objects or false
     */
    function get_coursereports($course_id,$report_id=null,$status=null) {
		global	$CFG;

		$report_sql		=	(!empty($report_id)) ? " AND report_id = {$report_id} " : "";
		$status_sql		=	(!empty($status)) ? " AND cr.status = {$status} " : "";

    	$sql	=	"SELECT		*, cr.id as cr_id
    				 FROM 		{$CFG->prefix}block_ilp_coursereports as cr,
    				 			{$CFG->prefix}block_ilp_report as r
    				 WHERE		cr.report_id	=	r.id
    				 AND		course_id 	=	{$course_id}
    				{$report_sql}
    				{$status_sql}";

    	return	$this->dbc->get_records_sql($sql);
    }

	/**
     * Returns a list of all ilp reports with an enabled status
     *
     * @param array $reports a array contain the ids of reports that
     * you do not want included in the return values
     * @return mixed array containing recordset objects or false
     */
    function get_enabledreports($report_ids=null)	{
    	global $CFG;
    	$unwantedcourses	=	(!empty($report_ids)) ? " AND id NOT IN (".implode(',',$report_ids).")": "";

    	$sql	=	"SELECT		*
    				 FROM		{$CFG->prefix}block_ilp_report
    				 WHERE		status	=	".ILP_ENABLED.
    				 $unwantedcourses;

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
 		global	$CFG;

    	if (!is_array($role_id)) {
    		$role_id	=	array($role_id);
    	}

    	$sql	=	"SELECT 	c.id, r.*, rp.*
  					 FROM 		{$CFG->prefix}block_ilp_reportpermissions AS rp,
					 			{$CFG->prefix}role AS r,
								{$CFG->prefix}capabilities AS c
					 WHERE		rp.capability_id	=	c.id
					 AND		rp.role_id		=	r.id
					 AND		rp.report_id	=	{$report_id}
					 AND		r.id IN (".implode(',',$role_id).")
					 AND		c.id = {$capability_id}";

    	return 	(!empty($role_id)) ? $this->dbc->get_records_sql($sql) : false;
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

        require_once($CFG->dirroot."/blocks/ilp/lib.php");
    	//adding addtional lines that return true if the user is either a site admin or has the ilpviewall capabilty at site level

    	//get sote context
    	$sitecontext	=	get_context_instance(CONTEXT_SYSTEM);

    	//check for the ilpviewall capability at site level this gives the user rights to view all
		$ilpadmin				=	has_capability('block/ilp:ilpviewall',$sitecontext);

		$is_admin				=	(ilp_is_siteadmin($USER->id) || $ilpadmin) ? true : false;


		//if permissions where returned from then the role (or one of the roles given) has the permission in the course
    	$permissions	=	$this->get_reportpermissions_by_criteria($report_id,$role_id,$capability_id);
    	return 	(!empty($permissions) || !empty($is_admin)) ? true	:	false;
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

		$itemtable		=	(!empty($multiple)) ? "{$CFG->prefix}{$tablename}_items as i," : '';
		$where			=	(!empty($multiple)) ? "e.parent_id	=	i.id AND i.parent_id	=	p.id" : "e.parent_id	=	p.id";

		$sql	=	"SELECT		*
					 FROM 		{$parenttable} as p,
					 			{$itemtable}
					 			{$entrytable} as e
					 WHERE 		{$where}
					 AND		e.entry_id	=	{$entry_id}
					 AND		p.reportfield_id	=	{$reportfield_id}";

		return (empty($multiple)) ? $this->dbc->get_record_sql($sql) : $this->dbc->get_records_sql($sql);
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

		$sql	=	"SELECT			*
					 FROM			{$CFG->prefix}block_ilp_plu_rf_sts as s,
					 				{$CFG->prefix}block_ilp_plu_sts_ent as se
					 WHERE			se.parent_id		=	s.id
					 AND			se.entry_id			=	{$entry_id}
					 AND			s.reportfield_id	=	{$reportfield_id}";

		return 		$this->dbc->get_record_sql($sql);
	}

    /**
    * check if any user data has been uploaded to a particular list-type reportfield
    * if it has then admin should not be allowed to delete any existing
    * options
    * @param string tablename
    * @param int reportfield_id
    * @param string item_table - use this item_table if item_table name is not simply $tablename . "_items"
    * @param string item_key - use this foreign key if specific item_table has been sent as arg. Send empty string to simply get all rows from the item table
    * @param string item_value_field - field from the item table to use as the value submitted to the user entry table
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
				WHERE ele.reportfield_id = {$reportfield_id}
				";

		return	$this->dbc->get_records_sql( $sql );
	}

	/*
	* special version of plugin_data_item_exists for gradebook tracker
	* @param string $tablename
	* @param int $reportfield_id
	*/
    function plugin_data_item_exists_gradebooktracker( $tablename, $reportfield_id ){
	$sql = "
		SELECT *
		FROM $tablename ele
		JOIN {$tablename}_ent entry ON entry.parent_id = ele.reportfield_id
		JOIN {$tablename}_items item ON item.parent_id = entry.id
		WHERE ele.reportfield_id = $reportfield_id
	";
	return	$this->dbc->get_records_sql( $sql );
    }




	/**
	* delete option items for a plugin list-type element
	* $tablename is the element table eg block_ilp_plu_category
	* @param string tablename
	* @param int reportfield_id
	*
	* @return boolean true or false
	*/
    function delete_element_listitems( $tablename, $reportfield_id , $extraparams=array() ){
		global $CFG;
		$real_tablename = $CFG->prefix . $tablename;
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
	* @param string tablename
	* @param int reportfield_id
	*
	* @return boolean true or false
	*/
    function delete_element_listitems_by_parent_id( $tablename, $parent_id , $extraparams=array() ){
		global $CFG;
		$real_tablename = $CFG->prefix . $tablename;
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
		$element_record = array_shift( $this->dbc->get_records( $tablename , array( 'reportfield_id' => $reportfield_id ) ) );
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
    * @param int
    * @param string
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
				FROM  	{$CFG->prefix}block_ilp_report_field rptf
				JOIN 	$plugin_table ON $plugin_table.reportfield_id = rptf.id
				JOIN 	$item_table ON $item_table.parent_id = $plugin_table.id
				WHERE 	$plugin_table.reportfield_id = $reportfield_id
		";
    	return $this->dbc->get_records_sql( $sql );
    }


    function get_status_options( $reportfield_id )	{

    	global	$CFG;

    		$sql	=	"SELECT i.id, i.name, i.value
    					 FROM 	{$CFG->prefix}block_ilp_plu_rf_sts as rs,
    					 		{$CFG->prefix}block_ilp_plu_sts as s,
    					 		{$CFG->prefix}block_ilp_plu_sts_items as i
    					 WHERE	rs.status_id	=	s.id
    					 AND	s.id			=	i.parent_id
    					 AND	rs.reportfield_id	=	{$reportfield_id}";

    	return $this->dbc->get_records_sql( $sql );
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
     * @param	int $id the id of the record you will be deleting
     *
     * @return mixed true or false
     */
    function delete_element_record_by_id ( $tablename,$id, $extraparams=array() ) {
    	return $this->delete_records( $tablename, array('id'=>$id), $extraparams );
    }

    /**
     * Deletes a record in the given table matching its id field
     *
     * @param	int $entry_id the id of the record you will be deleting
     *
     * @return mixed true or false
     */
    function delete_entryfield($tablename,$entry_id)	{
    	return $this->delete_records( $tablename, array('id'=>$entry_id));
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
     * @param	int $comment_id the id of the record you will be deleting
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
     *
     * @return mixed array of objects containing databases recordsets or false
     */
    function get_user_report_entries($report_id,$user_id,$state_id=null)	{
    	global		$CFG;
    	$tables		=	"";
    	$where		=	"";

    	//if the the id of a status has been given then we need to add mroe contitions to
    	//find the reports in this state
    	if (!empty($state_id)) {
    		$tables		=	", {$CFG->prefix}block_ilp_plu_ste_ent as se
    						   ";

    		$where		=	" 	AND e.id	=	se.entry_id
    							AND	se.parent_id	=	{$state_id}";

    	}

    	$sql	=	"SELECT		e.*
    				 FROM		{$CFG->prefix}block_ilp_entry as e
    				 			{$tables}
    				 WHERE		e.report_id		=	{$report_id}
    				 AND 		e.user_id	=	{$user_id}
    				 {$where}
    				 ORDER BY   e.timemodified DESC";

    	return $this->dbc->get_records_sql($sql);
    }

    /**
     * Returns the all elements of the state field for the given report
     *
     * @param  int $report_id the id of the report that we want to get the state items for
     *
     * @return mixed array recordset of objects or false
     */
    function get_report_stateitems($report_id)	{
    	global	$CFG;

    	$sql	=	"SELECT		*
    	 			 FROM 		{$CFG->prefix}block_ilp_report_field as rf,
    	 						{$CFG->prefix}block_ilp_plugin as p,
    	 						{$CFG->prefix}block_ilp_plu_ste as s,
    	 						{$CFG->prefix}block_ilp_plu_ste_items as si
    	 			WHERE		rf.plugin_id	=	p.id
    	 			AND			rf.id			=	s.reportfield_id
    	 			AND			s.id			=	si.parent_id
    	 			AND			rf.report_id	=	{$report_id}
    	 			AND			p.name			=	'ilp_element_plugin_state'";

    	 return		$this->dbc->get_records_sql($sql);
    }

    /**
    * see if an element of a particular type already exists in a report
    * @param int $report_id
    * @param string $tablename
    * @return int
    */
    public function element_type_exists( $report_id , $tablename ){
		global $CFG;
        $sql = "
            SELECT COUNT( rpt.id ) n
            FROM {$CFG->prefix}block_ilp_report rpt
            JOIN {$CFG->prefix}block_ilp_report_field rptf ON rptf.report_id = rpt.id
            JOIN {$CFG->prefix}block_ilp_plugin pln ON pln.id = rptf.plugin_id
            WHERE rpt.id = $report_id AND pln.tablename = '$tablename'
        ";
        $res = $this->dbc->get_record_sql( $sql );
        return $res->n;
    }


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

    	global	$CFG;

    	$sql	=	"SELECT 	tr.name as region_name,
    							p.name as plugin_name,
    							tr.id as region_id,
    							p.id as plugin_id
    				 FROM 		{$CFG->prefix}block_ilp_dash_temp	as t,
    				 			{$CFG->prefix}block_ilp_dash_temp_region as tr,
    				 			{$CFG->prefix}block_ilp_dash_region_plugin as rp,
    				 			{$CFG->prefix}block_ilp_dash_plugin as p
    				 WHERE 		t.id 	=	tr.template_id
    				 AND		tr.id	=	rp.region_id
    				 AND		rp.plugin_id	=	p.id
    				 AND 		t.name	=	'{$templatename}'";


    	return	$this->dbc->get_records_sql($sql);
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
    * @return int
    */
    public function get_next_position( $report_id, $tablename ){
    	global $CFG;
        $tablename = $CFG->prefix . $tablename;
        $sql = "SELECT MAX( position ) n FROM  $tablename WHERE report_id = $report_id";  //gregp - records should be counted not the position #
        $res = array_values( $this->dbc->get_records_sql( $sql ) );
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
     * @param int	$status	the status that the returned report records shoulds
     * have
     * @return	mixed array of recordsets or false
     */
    public function	get_reports($status)	{
    	return $this->dbc->get_records('block_ilp_report',array('status'=>$status));
    }


    /**
     * Count the number of entries for given user in the given report
     *
     * @param	int $report_id	the id of the report whose entries will be counted
     * @param	int $user_id the id of the  user whose entries will be counted
     *
     * @return	mixed int the number of entries or false
     */
    public function count_report_entries($report_id,$user_id)	{
    	global $CFG;

    	$sql	=	"SELECT		*
    				 FROM		{$CFG->prefix}block_ilp_entry		as 	e
    				 WHERE		user_id			=		{$user_id}
    				 AND		report_id		=		{$report_id}";

    	return	$this->dbc->count_records('block_ilp_entry',array('user_id'=>$user_id,'report_id'=>$report_id));
    }

    /**
     *
     * Returns whether the given report has a plugins field
     * @param int $report_id the id of the report that we will
     * check if it has a the plugins field
     *
     * @return	bool true or false
     */
    public function	has_plugin_field($report_id,$pluginname)	{
    	global 	$CFG;

    	$sql	=	"SELECT			*
    				 FROM			{$CFG->prefix}block_ilp_plugin as p,
    				 				{$CFG->prefix}block_ilp_report_field as rf
    				 WHERE			rf.plugin_id	=	p.id
    				 AND			rf.report_id	=	{$report_id}
    				 AND			p.name			=	'{$pluginname}'";

    	return 		$this->dbc->get_records_sql($sql);
    }


/**
     * Count the number of entries in the given report with a pass state
     *
     * @param	int $report_id	the id of the report whose entries will be counted
     * @param	int $user_id the id of the  user whose entries will be counted
     * @param	int	$state the state that the report entry should have
     * @param	bool $count true if the function should return a count or false to return the
     * entry records (defaults true)
     *
     * @return	mixed int the number of entries or false
     */
    public	function count_report_entries_with_state($report_id,$user_id,$state,$count=true,$entry_id=false)	{
			global 		$CFG;

			$select	=	(!empty($count))	? "count(e.id)" : " e.* ";

			$entrysql	=	(!empty($entry_id))	? " AND e.id = {$entry_id} "	: "";

    		$sql	=	"SELECT		{$select}
    					 FROM 		{$CFG->prefix}block_ilp_entry  as e,
    					 			{$CFG->prefix}block_ilp_plu_ste_ent as pe,
    					 			{$CFG->prefix}block_ilp_plu_ste_items as pi
    					 WHERE		e.id			=	pe.entry_id
    					 AND		pe.parent_id	=	pi.id
    					 AND		e.report_id		=	{$report_id}
    					 AND		e.user_id		=	{$user_id}
    					 AND		pi.passfail		=	{$state}
    					 $entrysql";

    		return 		(!empty($count)) ? $this->dbc->count_records_sql($sql) : $this->dbc->get_records_sql($sql);
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
			global 		$CFG;

			$entriessql	= 	(!empty($entries))	? "AND e.id IN (".implode($entries,',').")" : "";



    		//$sql	=	"SELECT		count(e.id)
    		$sql	=	"SELECT		e.id
    					 FROM 		{$CFG->prefix}block_ilp_entry  as e,
    					 			{$CFG->prefix}block_ilp_plu_ddl_ent as	ddlent,
    					 			{$CFG->prefix}block_ilp_plu_ste_ent as pe,
    					 			{$CFG->prefix}block_ilp_plu_ste_items as pi

    					 WHERE		e.id			=	ddlent.entry_id
    					 AND		e.report_id			=	{$report_id}
    					 AND		e.user_id			=	{$user_id}
    					 AND		ddlent.value		<	{$time}

    					 AND		e.id		        =	pe.entry_id
    					 AND		pe.parent_id	    =	pi.id
    					 AND		pi.passfail         =   " . ILP_STATE_UNSET . "

    					 {$entriessql}";
    					 
    		$rst = $this->dbc->get_records_sql($sql);
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
    	
    	$sql	=	"SELECT 		e.*, r.*,ddl.value as deadline
					   FROM			{block_ilp_entry} 			AS e,
									{block_ilp_plu_ddl_ent}		AS ddl,
									{block_ilp_plu_ste_items}	AS stitems,
									{block_ilp_report}			AS r,
									{block_ilp_plu_ste_ent}		AS stent
						WHERE		stitems.passfail = 0
						  AND		ddl.value >= {$ltimestamp}
						  AND		ddl.value < {$utimestamp}
						  AND		stent.parent_id = stitems.id
						  AND		e.id 	=	ddl.entry_id
						  AND		e.id 	=	stent.entry_id
						  AND		e.report_id	= r.id";
    	
    	return 		$this->dbc->get_records_sql($sql);
    	
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
    	global 	$CFG;

    	$sql	=	"SELECT			*
    				 FROM 			{$CFG->prefix}block_ilp_entry
    				 WHERE			timemodified	=(SELECT MAX(timemodified)
    				 								  FROM 			{$CFG->prefix}block_ilp_entry
    				 								  WHERE			report_id	=	{$report_id}
    				 								  AND			user_id		=	{$user_id}
    				 								 )";

    	return $this->dbc->get_record_sql($sql);
    }

    /**
     * Returns the timestamp for the last update to entries for the given report and user_id
     * Differs from get_lastupdatedentry in that this only returns the timestamp and checks
     * for updates to comments and user status
     *
     * @param	int $report_id	the id of the report whose entry will be returned
     * @param	int $user_id the id of the user whose entry will be returned
     *
     * @return	mixed object the  of entries or false
     */
    public  function get_lastupdatetime($report_id,$user_id)    {

        $sql    =   "SELECT MAX(timemodified) AS timemodified
                     FROM (
                            (
                                SELECT MAX(timemodified) AS timemodified
                                FROM	{block_ilp_entry}
                                WHERE	report_id	=	{$report_id}
                                AND 	user_id		=	{$user_id}
                            )
                            UNION
                            (
                                SELECT 	MAX(ec.timemodified) AS timemodified
                                FROM 	{block_ilp_entry_comment} AS ec,
                                        {block_ilp_entry} AS e
                                WHERE 	entry_id = e.id
                                AND	report_id	=	{$report_id}
                                AND	user_id		=	{$user_id}
                            )
                            UNION
                            (
                                SELECT	timemodified
                                FROM	{block_ilp_user_status}
                                WHERE	user_id		=	{$user_id}
                            )
			            ) AS lastreportupdate
                     ";

        return $this->dbc->get_record_sql($sql);

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
     * @param	int $report_id	the id of the report whose entries will be counted
     * @param	int $user_id the id of the  user whose entries will be counted
     * @param	int	$state the state that the report entry should have
     *
     * @return	mixed int the number of entries or false
     */
    public	function get_report_state_items($report_id,$pluginname)	{
			global 		$CFG;

			$sql	=	"SELECT		i.name as name,
    								i.value as value,
    								i.id as id
    					 FROM 		{$CFG->prefix}block_ilp_report_field as rf,
    					 			{$CFG->prefix}block_ilp_plugin as p,
    					 			{$CFG->prefix}block_ilp_plu_ste as s,
    					 			{$CFG->prefix}block_ilp_plu_ste_items as i

    					 WHERE		rf.id			=	s.reportfield_id
    					 AND		p.id			=	rf.plugin_id
    					 AND		s.id			=	i.parent_id
    					 AND		rf.report_id	=	{$report_id}
    					 AND		p.name			=	'{$pluginname}'";

    		return 		$this->dbc->get_records_sql($sql);
    }



   public function 	get_report_entries_with_state()	{
   		global $CFG;

   			$sql	=	"SELECT		r.*,
    					 FROM 		{$CFG->prefix}block_ilp_entry as e,
   									{$CFG->prefix}block_ilp_report_field as rf,
    					 			{$CFG->prefix}block_ilp_plugin as p,
    					 			{$CFG->prefix}block_ilp_pu_sts as s,
    					 			{$CFG->prefix}block_ilp_pu_sts_items as si,
    					 			{$CFG->prefix}block_ilp_pu_sts_ent as se
    					 WHERE		e.report_id		=	$rf.report_id
    					 AND		p.id			=	rf.plugin_id
    					 AND		s.reportfield_id	=	rf.id
				 		 AND		rf.id			=	{$report_id}
				 		 AND		s.id			=	si.parent_id
				 		 AND		si.id			=	se.parent_id
    					 AND		p.name			=	'{$pluginname}'
    					 AND		e.user_id		=	{$user_id}
";

    		return 		$this->dbc->get_records_sql($sql);
   }


   /**
    * Returns the id of the item with the given value
    *
    * @param	int $parent_id	the id of the state item record that is the parent of the item
    * @param	int $itemvalue the actual value of the field
    * @param	string $keyfield field from $itemtable to use as key
    * @param	string $itemtable name of item table to use if this element type does not follow the '_items' naming convention
    *
    * In my opinion this is a flakey function as it bases the
    *
    * @return	mixed object or false
    */
   public function get_state_item_id($tablename,$parent_id,$itemvalue, $keyfield='id', $itemtable=false )	{
   		global 	$CFG;

   		 $tablename =	( !empty($itemtable) ) ? $itemtable : $tablename."_items";

   		$sql	=	"SELECT		*
   					 FROM 		{$CFG->prefix}{$tablename} 	as si
        		 	 WHERE		$keyfield		=	{$itemvalue}";

        if( !$itemtable )	{
            //not an '_items' table - so comes from some other area eg course and has no parent id
   		    $sql    .=  " AND   	parent_id	=	{$parent_id}";
        }

   		return 		$this->dbc->get_record_sql($sql);
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
    * @param	int	$commnet_id	the id of the comment being retrieved
    *
    * @return	mixed  object containing the record or bool false
    */
  	function get_comment_by_id($comment_id)	{
  		return 	$this->dbc->get_record('block_ilp_entry_comment',array('id'=>$comment_id));
  	}

  	/**
    * Returns the items that can be used for user status, note this should always be the first status field
    * created so item parent ids should have a value of 1
    *
    * @return	mixed  object containing the record or bool false
    */
  	function get_user_status_items($parent_id=1)	{
  		return $this->dbc->get_records('block_ilp_plu_sts_items',array('parent_id'=>$parent_id));
  	}

  	/**
    * Returns whether a link between a given report and the given status field exists
    *
    * @return	mixed  object containing the record or bool false
    */
  	function has_statusfield($status_id,$report_id)	{
  		global	$CFG;

  		$sql	=	"SELECT			*
  					FROM 			{$CFG->prefix}block_ilp_report as r,
  									{$CFG->prefix}block_ilp_report_field as rf,
  									{$CFG->prefix}block_ilp_plu_rf_sts as s
  					WHERE			r.id	=	rf.report_id
  					AND				rf.id	=	s.reportfield_id
  					AND				s.id	=	{$status_id}
  					AND				r.id	=	{$report_id}";

  		return ($this->dbc->get_records_sql($sql)) ? true: false;
  	}


  	/**
    * This function sets the status of a report enabled or disabled
    *
    * @return	mixed  object containing the record or bool false
    */
  	function set_report_status ($report_id,$status)	{
  		//return $this->dbc->set_field('block_ilp_report',array('status'=>$status),array('id'=>$report_id));
  		return $this->dbc->set_field('block_ilp_report','status', $status, array('id'=>$report_id));
  	}


  	/**
    * This function sets the delete field of a reportd
    *
    * @return	mixed  object containing the record or bool false
    */
  	function delete_report($report_id,$deleted)	{
  		return $this->dbc->set_field('block_ilp_report','deleted', $deleted, array('id'=>$report_id));
  	}

  	function create_statusfield($statusfield)	{
  		$this->insert_record('block_ilp_plu_rf_sts', $statusfield);
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
     * @param string $plugin_name the name of the template
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
     * @return mixed object containing recordset with matching id or false
     */
  	function get_status_item_by_id($id)	{
  		return $this->dbc->get_record('block_ilp_plu_sts_items',array('id'=>$id));
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
                        AND 	u.id =	{$user_id}
                        AND		ra.userid = tu.id
                        AND 	c.contextlevel = ".CONTEXT_USER;

  		return $this->dbc->get_records_sql($sql);
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
                      WHERE 	ra.userid = {$user_id}
                        AND 	ra.contextid = c.id
                        AND 	c.instanceid = u.id
                        AND 	c.contextlevel = ".CONTEXT_USER
  						." GROUP BY u.id";

  		return	$this->dbc->get_records_sql($sql);
 	}

 	/**
     * Returns the user records of all users enrolled into the given course
     *
     * @param int $course_id the id of the course whose enrolled users
     * we want to retrieve
     *
     * @return mixed array of object containing all users enrolled in the course
     * or bool false
     */
 	function get_course_users($course_id,$group_id=null) {
 		global $CFG;

 			$coursecontext	=	get_context_instance(CONTEXT_COURSE, $course_id);

 			$grouptable		=	(!empty($group_id)) ? " INNER JOIN {groups_members} as gm on u.id = gm.userid " : "";
 			$groupwhere		=	(!empty($group_id)) ? "AND gm.groupid = {$group_id} " : "";


 			if ($usercontexts		=	get_parent_contexts($coursecontext))	{
 					$listofcontexts	=	'('.implode(',',$usercontexts).')';
 			} else {
 				$sitecontext = get_context_instance(CONTEXT_SYSTEM, SITEID);
        		$listofcontexts = '('.$sitecontext->id.')'; // must be site
 			}

 			$context = get_context_instance(CONTEXT_COURSE, $course_id);

	 		$sql	=	"SELECT		distinct(u.id)
	 					  FROM		{user} u INNER JOIN {role_assignments} ra on u.id = ra.userid
	 					  			{$grouptable}
	 					  			LEFT OUTER JOIN {user_lastaccess} ul on (ra.userid and ul.courseid = {$course_id})
	 					  			LEFT OUTER JOIN {role} r on ra.roleid = r.id

	 					  WHERE		(ra.contextid = {$context->id} OR ra.contextid in {$listofcontexts})
	 					    AND		u.deleted = 0
	 					    AND		(ul.courseid = {$course_id} OR ul.courseid IS NULL)
	 					    AND		u.username <> 'guest'
	 					    AND		r.id = 5
	 					  			{$groupwhere}";

		return $this->dbc->get_records_sql($sql);
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
    function get_students_matrix($flextable,$student_ids,$status_id, $includenull=false) {



        $select = "SELECT 		u.id as id,
        						u.idnumber as idnumber,
        						u.firstname as firstname,
        						u.lastname as lastname,
        						si.name	as u_status,
        						u.picture as picture,
        						u.imagealt as imagealt,
        						u.email as email ";

        $from = " FROM 			{user} as u LEFT JOIN {block_ilp_user_status} as us on (u.id = us.user_id) LEFT JOIN
        						{block_ilp_plu_sts_items} as si on (us.parent_id = si.id)";

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

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();

        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     *
     * Returns the latest entry reocrd for the given student
     *
     * @param int $student_id the id of the student whose
     * last entry will be retrieved
     *
     * @return
     */
    function get_lastupdate($user_id) {

    	$sql	=	"SELECT		MAX(timemodified) as timemodified
    				 FROM		{block_ilp_entry}
    				 WHERE		user_id	=	{$user_id}";


    	return	$this->dbc->get_record_sql($sql);
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

    	if (stripos($CFG->release,"2.") !== false) {
    		$courses	=	enrol_get_users_courses($user_id, false,NULL,'fullname DESC');
    	} else {
    		$courses	=	get_my_courses($user_id);
    	}

    	return $courses;
    }



    /**
     * Returns all currently installed mis plugins
     *
     * @return array of recordset objects or bool false
     */
    function get_mis_plugins() 	{
        global $CFG;

        $tableexists =  (stripos($CFG->release,"2.") !== false) ?
            in_array('block_ilp_mis_plugin',$this->dbc->get_tables())
            :
            $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_mis_plugin}'");

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
     * Returns all currently installed tab plugins
     *
     * @return array of recordset objects or bool false
     */
    function get_tab_plugins() 	{
        global $CFG;

        $tableexists =  (stripos($CFG->release,"2.") !== false) ?
            in_array('block_ilp_dash_tab',$this->dbc->get_tables())
            :
            $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_dash_tab}'");



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
  	 * @param	string $label	the label that is being test to see if it exists
  	 * @param	int $report_id the id of the report that will be checked
  	 *
  	 * @return	mixed array of recordsets or bool false
  	 */
  	function label_exists($label,$report_id,$field_id)	{

  		$label	=	mysql_real_escape_string($label);

  		//thsi code is needed due to a substr_count in the
  		//moodle_database.php file (line 666 :-( ) it causes
  		//an error whenever a label has an ? in it
		$label = str_replace('?', '.', $label);


  		$currentfieldsql	=	(!empty($field_id)) 	?	"AND id != {$field_id}" : "";

  		$sql	=	'SELECT		*
  					 FROM		{block_ilp_report_field}
  					 WHERE		label		=	"'.$label.'"
  					 AND		report_id	=	'.$report_id
  					 .' '.$currentfieldsql;

  		return $this->dbc->get_records_sql($sql);
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
  	 * @param	string 	$name the name of the plugin that we want to match
  	 * @param	string 	$tablename the name of the table that will be queried
  	 *
  	 * @return	mixed object recordset or bool false
  	 */
  	function get_plugin_record_by_classname($tablename,$classname) {
  		return $this->dbc->get_record($tablename,array('name'=>$classname));
  	}

    function setting_exists($name)	{
    	return $this->dbc->get_record('config_plugins',array('name'=>$name,'plugin'=>'block_ilp'));
    }

    function insert_config_setting($name,$value) {
    	$setting	=	new stdClass();
    	$setting->plugin	=	'block_ilp';
    	$setting->name		=	$name;
    	$setting->value		=	$value;

    	return $this->dbc->insert_record('config_plugins',$setting);
    }

    function update_config_setting($setting) {
		return $this->dbc->update_record('config_plugins',$setting);
    }


    function get_report_coursefield($entry_id,$reportfield_id)	{

    	$sql	=	"SELECT 	*
    				 FROM		{block_ilp_plu_crs} as c,
    				 			{block_ilp_plu_crs_ent} as ce
    				 WHERE		c.id = ce.parent_id
    				 AND		c.reportfield_id 	= 	{$reportfield_id}
    				 AND		entry_id			=	{$entry_id}";

    	return 	$this->dbc->get_record_sql($sql);
    }

    /**
     *
     * Adds an event to the calendar of a user
     * @param object $event a object containing details of an event tot be saved into a users calendar
     */
    function save_event($event)	{

    	//we can not user add_event in moodle 2.0 as it requires the user to have persmissions to add events to the
    	//calendar however this capability check can be bypassed if we use the calendar event class so we will use add_event in
    	//1.9 and calendar_event class in 2.0
    	global $CFG, $USER;


    	if (stripos($CFG->release,"2.") !== false) {
    	    require_once($CFG->dirroot.'/calendar/lib.php');
    		$calevent = new calendar_event($event);
	    	$calevent->update($event,false);

    		if ($calevent !== false) {
        		return $calevent->id;
	    	}

    	} else {
    		return add_event($event);
    	}
    }

    /**
     *
     * Updates a calendar event with new details
     * @param object $event a object containing details of an event tot be saved into a users calendar
     */
    function update_event($event)	{

		global $CFG, $USER;

    	if (stripos($CFG->release,"2.") !== false) {
    	    require_once($CFG->dirroot.'/calendar/lib.php');
    		$calevent = calendar_event::load($event->id);
	    	return $calevent->update($event,false);
    	} else {
    		return update_event($event);
    	}



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
    				 AND		ce.reportfield_id	=	{$reportfield_id}
    				 AND		ce.entry_id			=	{$entry_id}";

    	return $this->dbc->get_record_sql($sql);
    }


    function get_reportfield_by_id($reportfield_id)	{
    	return $this->dbc->get_record('block_ilp_report_field',array('id'=>$reportfield_id));
    }


    function create_event_cross_reference($record) {
    	return $this->dbc->insert_record('block_ilp_cal_events',$record);
    }

}



?>
