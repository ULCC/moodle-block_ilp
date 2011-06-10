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

        // instantiate the Assessment manager database
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
            // skip the flexible_table
            if(!is_a($data, 'flexible_table')) {
                foreach($data as $index => &$datum) {
                    $datum = ilp_db::encode($datum);
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
                $datum = ilp_db::decode($datum);
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
                $datum = ilp_db::decode_htmlchars($datum);
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
        // check for the presence of a table to determine which query to run
        $tableexists = $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_plugin}'");

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
        // check for the presence of a table to determine which query to run
        $tableexists = $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_dash_plugin}'");

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_plugin', array()) : false;
	}
	
    /**
     * Gets the full list of dashboard plugins already installed
     *
     * @return array Result objects
     */
    function get_dashboard_tabs() {
        // check for the presence of a table to determine which query to run
        $tableexists = $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_dash_tab}'");

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_ilp_dash_tab', array()) : false;
	}

    /**
     * Gets the full list of dashboard templates already installed
     *
     * @return array Result objects
     */
    function get_dashboard_templates() {
        // check for the presence of a table to determine which query to run
        $tableexists = $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_ilp_dash_temp}'");

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

    /*
    * get plugin data from the plugin name
    * @param string $plugin_name
     * @return mixed object containing the plugin record or false
    */
    function get_plugin_by_name($plugin_name) {
    	return $this->dbc->get_record('block_ilp_plugin',array('name'=>$plugin_name));
    }
    
   	/**
     * Sets the new position of a field 
     *
     * @param int $plugin_id the id of the plugin that will be retrieved
     * @return mixed object containing the plugin record or false
     */
    function set_new_position($reportfield_id,$newposition) {
    	return $this->dbc->set_field('block_ilp_report_field',"position",$newposition,array('id'=>$reportfield_id));
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
			$positionsql 	=  "AND position = {$position} ||  position = {$otherfield}";
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
	function delete_form_element_by_reportfield($tablename,$id) {
		return $this->delete_records($tablename, array('reportfield_id' => $id));
	}
	
	/**
     * Generic delete function used to delete items from the items table
     *
     * @param string $tablename the table that you want to delete the record from
     * @param int $parent_id the parent_id that all fields to be deleted should have 
     * 
     * @return bool true or false
     */
	function delete_items($tablename,$parent_id) {
		return $this->delete_records($tablename, array('parent_id' => $id));
	}
	
	
	/**
     * Delete a report field record
     *
     * @param int $id the id of the record that you want to delete
     * 
     * @return bool true or false
     */
	function delete_report_field($id) {
		return $this->delete_records('block_ilp_report_field', array('id' => $id));
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
     * returns data that can be used with a pagable flexible_table
     *
     * @param object $flextable an object of type flextable
     * @return mixed object containing report records or false
     */ 
    function get_reports_table($flextable)	{
    	global $CFG;
    	
    	$select	=	"SELECT		*";
    	
    	$from	=	"FROM 		{$CFG->prefix}block_ilp_report";
    	
    	$where	=	"";

    	// get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        //$flextable->totalrows($count);
    	
    	$data = $this->dbc->get_records_sql(
            $select.$from.$where,
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
    	return $this->dbc->get_records("course");
    }
	 
	 /**
     * Returns all roles
     *
     * @return mixed object containing all course records or false
     */
    function get_roles()	{
    	return $this->dbc->get_records("role");
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

    	
    	$sql	=	"SELECT 	* 
					 FROM 		{$CFG->prefix}block_ilp_reportpermissions AS rp, 
					 			{$CFG->prefix}role AS r,
								{$CFG->prefix}capabilities AS c
					 WHERE		rp.capability_id	=	c.id
					 AND		rp.role_id		=	r.id
					 AND		rp.report_id	=	{$report_id}	
					 AND		r.id IN (".implode(',',$role_id).")
					 AND		c.id = {$capability_id}";
								
    	return 	$this->dbc->get_records_sql($sql);
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
		//if permissions where returned from then the role (or one of the roles given) has the permission in the course    
    	$permissions	=	$this->get_reportpermissions_by_criteria($report_id,$role_id,$capability_id);
    	return 	(!empty($permissions)) ? true	:	false;
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
		
		$sql	=	"SELECT		*
					 FROM 		{$parenttable} as p,
					 			{$entrytable} as e
					 WHERE 		e.parent_id	=	p.id
					 AND		e.entry_id	=	{$entry_id}
					 AND		p.reportfield_id	=	{$reportfield_id}";
		
		return (empty($multiple)) ? $this->dbc->get_record_sql($sql) : $this->dbc->get_records_sql($sql);
	}
   
    
    /*
    * check if any user data has been uploaded to a particular list-type reportfield
    * if it has then manager should not be allowed to delete any existing
    * options
    * @param string tablename
    * @param int reportfield_id
    * @return mixed array of objects or false
    */
    function plugin_data_item_exists( $tablename, $reportfield_id ){
		global $CFG;
		
		$tablename 		= $CFG->prefix . $tablename;
		$item_table 	= $tablename . "_items";
		$entry_table 	= $tablename . "_ent";
		
		$sql = "SELECT *
				FROM {$tablename} ele
				JOIN {$item_table} item ON item.parent_id = ele.id
				JOIN {$entry_table} entry ON entry.value = item.value
				WHERE ele.reportfield_id = {$reportfield_id}
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
    function delete_element_listitems( $tablename, $reportfield_id ){
		global $CFG;
		$real_tablename = $CFG->prefix . $tablename;
		$element_table = $tablename;
		$item_table = $tablename . "_items";
		$entry_table = $tablename . "_ent";
		//get parent_id
		$parent_id = $this->get_element_id_from_reportfield_id( $tablename, $reportfield_id );

    	return $this->dbc->delete_records( $item_table, array( 'parent_id' => $parent_id ) );
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

    /*
    * supply a reportfield id for a dropdown type element
    * dropdown options are returned
    * @param int
    * @param string
    * @return array of objects
    */
    function get_optionlist( $reportfield_id, $tablename, $field=false ){
		global $CFG;

        $fieldlist = array( 'value', 'name' );
        if( $field ){
            $fieldlist[] = $field;
        }

		$tablename = $CFG->prefix . $tablename;
		$item_table = $tablename . "_items";
		$plugin_table = $tablename;

        $whereandlist = array(
            "$plugin_table.reportfield_id = $reportfield_id"
        );

		$sql = "SELECT " . implode( ',' , $fieldlist ) . "
				FROM  {$CFG->prefix}block_ilp_report_field rptf
				JOIN $plugin_table ON $plugin_table.reportfield_id = rptf.id
				JOIN $item_table ON $item_table.parent_id = $plugin_table.id
				WHERE $plugin_table.reportfield_id = $reportfield_id
		";
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
    function delete_element_record_by_id ($tablename,$id) {
    	return $this->delete_records($tablename, array('id'=>$id));
    }

    
     /**
     * Returns all user entries for the given report
     * 
     * @param  int $report_id the id of the report that we are looking for
     * @param  int $user_id	the id of user who will be retrieving report entries for  
     * 
     * @return mixed array of objects containing databases recordsets or false
     */
    function get_user_report_entries($report_id,$user_id,$status_id=null)	{
    	global		$CFG;
    	$tables		=	"";
    	$where		=	"";
    	
    	//if the the id of a status has been given then we need to add mroe contitions to
    	//find the reports in this status
    	if (!empty($status_id)) {
    		$tables		=	", {$CFG->prefix}block_ilp_report as r,
    						   {$CFG->prefix}block_ilp_report_field as rf,
    						   {$CFG->prefix}block_ilp_plugin as p,
    						   {$CFG->prefix}block_ilp_plu_ste as s,
    						   {$CFG->prefix}block_ilp_plu_ste_items as si,
    						   ";
    						   
    		$where		=	" AND	r.id				=	e.report_id
    						  AND	r.id				=	rf.report_id
    						  AND 	rf.plugin_id		=	p.id
    						  AND	rf.id				=	s.reportfield_id
    						  AND	si.parent_id		=	s.id
    						  AND	si.id				=	{$status_id}";
    						   		
    	}
    	
    	$sql	=	"SELECT		e.*
    				 FROM		{$CFG->prefix}block_ilp_entry as e
    				 			{$tables}
    				 WHERE		e.id		=	{$report_id}
    				 AND 		e.user_id	=	{$user_id}
    				 {$where}";
    	
    	
    	return $this->dbc->get_records_sql($sql);
    }
    
    
     /**
     * Returns true or false depending on whether the report given has a state field
     * 
     * @param  int $report_id the id of the report that we are looking for
     * 
     * @return bool true or false
     */
    function has_state_fields($report_id) {
    	global	$CFG;
    	
    	$sql	=	"SELECT rf.id 	
    	 			 FROM 	{$CFG->prefix}block_ilp_report_field as rf,
    	 					{$CFG->prefix}block_ilp_plugin as p
    	 			 WHERE	report_id	=	{$report_id}
    	 			 AND 	rf.plugin_id	=	p.id
   		 			 AND	p.name	=	'ilp_element_plugin_state'";
	
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
    	 			AND		rf.id			=	s.reportfield_id
    	 			AND		s.id			=	si.parent_id
    	 			AND		rf.report_id	=	{$report_id}
    	 			AND		p.name			=	'ilp_element_plugin_state'";
    	 			
    	 return		$this->dbc->get_records_sql($sql);			
    }
    
     /**
     * Returns a report field table record for the report given if one exists that is of the course selector
     * type. The primary purpose of this function is to enable the user to test whether a report
     * has a course selector or not 
     * 
     * @param  int $report_id the id of the report that is being checked to see if it has a 
     *  course selector ()
     * 
     * @return mixed array recordset of objects or false
     */    
    function has_course_relation($report_id)	{
    	global	$CFG;
    	
    	$sql	=	"SELECT rf.id 	
    	 			 FROM 	{$CFG->prefix}block_ilp_report_field as rf,
    	 					{$CFG->prefix}block_ilp_plugin as p
    	 			 WHERE	report_id	=	{$report_id}
    	 			 AND 	rf.plugin_id	=	p.id
   		 			 AND	p.name	=	'ilp_element_plugin_course'";
    	 					
    	 return		$this->dbc->get_records_sql($sql);					
    	 					
    } 
    
      /**
     * Returns a report field table record for the report given if one exists that is of the datedeadline type.
     * The primary purpose of this function is to enable the user to test whether a report
     * has a course selector or not 
     * 
     * @param  int $report_id the id of the report that is being checked to see if it has a 
     *  course selector ()
     * 
     * @return mixed array recordset of objects or false
     */    
    function has_datedeadline($report_id)	{
    	global 	$CFG;
    	
    	$sql	=	"SELECT rf.id 	
    	 			 FROM 	{$CFG->prefix}block_ilp_report_field as rf,
    	 					{$CFG->prefix}block_ilp_plugin as p
    	 			 WHERE	report_id	=	{$report_id}
    	 			 AND 	rf.plugin_id	=	p.id
   		 			 AND	p.name	=	'ilp_element_plugin_date_deadline'";
    	 					
    	 return		$this->dbc->get_records_sql($sql);					
    } 
    
    /**
     * Wrapper for the has_datedeadline function 
     * 
     * @param  int $report_id the id of the report that is being checked to see if it has a 
     *  course selector ()
     * 
     * @return mixed array recordset of objects or false
     */
    function get_datedeadline_field($report_id)	{
    	return $this->has_datedeadline($report_id);
    }
    
    
    
    /**
     * Wrapper for the has_course_relation function 
     * 
     * @param  int $report_id the id of the report that is being checked to see if it has a 
     *  course selector ()
     * 
     * @return mixed array recordset of objects or false
     */
    function get_courserelated_field($report_id) {
    	return $this->has_course_relation($report_id);
    }
    
    

    /*
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
    
    
    /*
    * gets max position for a report and returns 1 more
    * if no entries yet, returns 1
    * @param int $report_id
    * @return int
    */
    public function get_next_position( $report_id, $tablename ){
    	global $CFG;
        $tablename = $CFG->prefix . $tablename;
        $sql = "SELECT MAX( position ) n FROM  $tablename WHERE report_id = $report_id";
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
     * @return bool true or false
     */    
    function record_exists($table, $field, $value ) {
    	return $this->dbc->record_exists( $table, array( $field => $value ) );
    }
    
    
}



?>
