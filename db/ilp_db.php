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
			
			$operator		=	(!empty($type)) ? " <= " : " >= ";
			$positionsql 	= 	"AND		position	{$operator}  {$position}";
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
    function get_enabledreports($report_ids)	{
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
    
    
   	
    
   
    
    /*
    * check if any user data has been uploaded to a particular list-type reportfield
    * if it has then manager should not be allowed to delete any existing
    * options
    */
    function plugin_data_item_exists( $tablename, $reportfield_id ){
		global $CFG;
		
		$tablename = $CFG->prefix . $tablename;
		$element_table = $tablename;
		$item_table = $tablename . "_items";
		$entry_table = $tablename . "_ent";
		$sql = "SELECT *
				FROM $tablename ele
				JOIN $item_table item ON item.parent_id = ele.id
				JOIN $entry_table entry ON entry.item_value = item.item_value
				WHERE ele.reportfield_id = $reportfield_id
				";
		$data = $this->dbc->get_records_sql( $sql );
		return $data;
<<<<<<< .mine	var_crap($data);exit;
*/
	return $data;
=======>>>>>>> .theirs    }  

	/*
	* delete option items for a plugin list-type element
	* $tablename is the element table eg block_ilp_plu_category
	*/
    function delete_element_listitems( $tablename, $reportfield_id ){
	global $CFG;
	$real_tablename = $CFG->prefix . $tablename;
	$element_table = $tablename;
	$item_table = $tablename . "_items";
	$entry_table = $tablename . "_ent";
	//get parent_id
	$parent_id = $this->get_element_id_from_reportfield_id( $tablename, $reportfield_id );
	$sql = "
		DELETE FROM $item_table 
		WHERE parent_id = $parent_id
	";
    	return $this->dbc->delete_records( $item_table, array( 'parent_id' => $parent_id ) );
    }
    public function get_element_id_from_reportfield_id( $tablename, $reportfield_id ){
	$element_record = array_shift( $this->dbc->get_records( $tablename , array( 'reportfield_id' => $reportfield_id ) ) );
	if( !empty( $element_record ) ){
		return $element_record->id;
	}
	return false;
    }
    public function listelement_item_exists( $item_tablename, $conditionlist ){
	return $this->dbc->get_records( $item_tablename, $conditionlist );
    }

    /*
    * supply a reportfield id for a dropdown type element
    * dropdown options are returned
    */
    function get_optionlist( $reportfield_id, $tablename ){
		global $CFG;
		$tablename = $CFG->prefix . $tablename;
		$item_table = $tablename . "_items";
		$plugin_table = $tablename;
		$sql = "SELECT item_value, item_name
				FROM  {$CFG->prefix}block_ilp_report_field rptf
				JOIN $plugin_table ON $plugin_table.reportfield_id = rptf.id
				JOIN $item_table ON $item_table.parent_id = $plugin_table.id
				WHERE $plugin_table.reportfield_id = $reportfield_id
				";
    	return $this->dbc->get_records_sql( $sql );
    }
    
    
    
	
}



?>