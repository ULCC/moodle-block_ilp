<?php
require_once('../configpath.php');
global $USER, $CFG, $SESSION, $PARSER;
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_mform.php');
$course_id = $PARSER->required_param('course_id', PARAM_INT);
$dbc = new ilp_db();

//$course	=	$dbc->get_course($course_id);

disp( main() );

/*
* This is an unpleasant hack to get round the fact that from moodle2.0 we are no longer allowed
* to use execute_sql. Please don't use this class except for utility scripts
*/
class quickdb{
	public static function get_connection(){
		global $CFG;
		$host = $CFG->dbhost;
		$username = $CFG->dbuser;
		$password = $CFG->dbpass;
		$dbname = $CFG->dbname;
		return new mysqli( $host, $username, $password, $dbname ) ;
	}

	public static function execute_sql( $conn, $sql ){
		if( $res = $conn->query( $sql ) ){
			if( $conn->insert_id ){
				return $conn->insert_id;
			}
			return true;
		}
		return false;
	}
	
}

/*
* scan blocks/ilp/predefined reports for files with names like report_[anything]
* these files should contain php code to add entries to $reportlist
* @return array of arrays
*/
function get_report_list(){
	global $CFG;
	$reports_dir = realpath( $CFG->dirroot.'/blocks/ilp/predefined_reports' );
	$dir = dir( $reports_dir );
	$reportlist = array();
	while( ( $file = $dir->read() ) !==false ){
		if( 'report_' == substr( $file, 0, 7 ) ){
			include( $reports_dir . DIRECTORY_SEPARATOR . $file );
		}
	}
	return $reportlist;
}

/*
* truncate the ILP plugin tables to reset the system
* only useful for testing - should not be called on production system
* @param mysqli connection $conn
*/
function trunc_ilp_tables( $conn ){
	global $CFG;
	$tablelist = array(
		'block_ilp_report',
		'block_ilp_report_field',
		'block_ilp_plu_user_status',
		'block_ilp_plu_tex',
		'block_ilp_plu_sts',
		'block_ilp_plu_sts_items',
		'block_ilp_plu_ste',
		'block_ilp_plu_ste_items',
		'block_ilp_plu_rdo',
		'block_ilp_plu_rdo_items',
		'block_ilp_plu_hte',
		'block_ilp_plu_dd',
		'block_ilp_plu_dat',
		'block_ilp_plu_ddl',
		'block_ilp_plu_crs',
		'block_ilp_plu_cat',
		'block_ilp_plu_are',
		'block_ilp_plu_cat_items',
		'block_ilp_reportpermissions'
	);
	$count = 0;
	foreach( $tablelist as $table ){
		$tablename = $CFG->prefix . $table;
		$sql = "truncate $tablename";
		if( quickdb::execute_sql( $conn, $sql ) ){
			$count++;
		}
		else{
			disp( "truncate $tablename FAILED" );
		}
	}
	return $count;
}

/*
* the main function of this action page
* scan the predefined reports directory and insert its contents into the ilp report tables
* @return array of ints
*/
function main(){
	global $USER, $CFG, $SESSION, $PARSER, $course_id, $dbc;
	$conn = quickdb::get_connection();
	if(1){
		//turn off this block on production systems !
		$trunccount = trunc_ilp_tables( $conn );
		$s = ( 1 == $trunccount ) ? '' : 's' ;
		disp( "$trunccount table$s truncated" );
	}
	$info = array();
	foreach( get_report_list() as $report ){
		$report_title = $report[ "title" ];
		$report_description = $report[ "description" ];
		$report_id = create_report( $course_id, $report_title, $report_description );
		foreach( $report[ "fieldlist" ] as $element ){
			$plugin_id = get_element_type_id_from_control_type( $element[ "type" ] );
			if( $plugin_id ){
				$label = $element[ "label" ];
				$description = $element[ "description" ];
				$req = $element[ "req" ];
				//everything ok - add the element to the report
				$info[] = apply_to_report( $conn, $course_id, $report_id, $plugin_id, $label, $description, $req, $element );
			}
		}
	}
	return $info;
}

/*
* add a control to a report
* @param mysqli connection $conn
* @param int $course_id
* @param int $report_id
* @param int $plugin_id
* @param string $label
* @param string $description
* @param int $req
* @param array $element
* @return int
*/
function apply_to_report( $conn, $course_id, $report_id, $plugin_id, $label, $description, $req, $element ){
	global $dbc, $CFG;
	$pluginrecord	=	$dbc->get_plugin_by_id($plugin_id);
	$plugin_table_name = $pluginrecord->tablename;
	$reportfield_id = insert_report_field( $conn, $report_id, $label, $description, $plugin_id, $req );
	$specific_sql = "INSERT INTO {$CFG->prefix}{$pluginrecord->tablename} ( reportfield_id, timecreated, timemodified ) VALUES ($reportfield_id, NOW(), NOW() )";
	$specific_parent_id = quickdb::execute_sql( $conn, $specific_sql );
	if( in_array( 'opts' , array_keys( $element ) ) ){
		//it's a list type element (radio, dropdown, select) with some option items
		$itemtable = $plugin_table_name . '_items';
		foreach( $element[ "opts" ] as $value=>$name ){
			$sql = "INSERT INTO {$CFG->prefix}$itemtable ( parent_id, value, name, timemodified, timecreated ) VALUES ( $specific_parent_id, '$value', '$name', NOW(), NOW() )";
			if( !quickdb::execute_sql( $conn, $sql ) ){
				disp( "FAILED: $sql" );
			}
		}
	}
	return $specific_parent_id;
}

/*
* add a control to a report (called by apply_to_report())
* @param mysqli connection $conn
* @param int $report_id
* @param string $label
* @param string $description
* @param int $plugin_id
* @param int $req
*/
function insert_report_field( $conn, $report_id, $label, $description, $plugin_id, $req ){
	global $USER, $CFG;
	$tablename = 'block_ilp_report_field';
	$tablename = $CFG->prefix . $tablename;
	$position = get_next_position( $report_id );
	$sql = "
		INSERT INTO $tablename ( label, description, report_id, plugin_id, position, req, creator_id, timecreated, timemodified )
		VALUES( '$label', '$description', $report_id, $plugin_id, $position, $req, $USER->id, NOW(), NOW() )
	";
	return quickdb::execute_sql( $conn, $sql );
	
}

/*
* find max position value for a report and return 1 higher
* @param int report_id
* @return int
*/
function get_next_position( $report_id ){
	global $dbc;
	$tablename = 'block_ilp_report_field';
	return $dbc->get_next_position( $report_id , $tablename );
}

/*
* take an everyday element type name (eg 'radio') and return the correct type_id
* @param string $element_type (eg 'dropdown', 'text', 'textarea' - anything which is a key in $plugin_name_list)
* @return int or false
*/
function get_element_type_id_from_control_type( $element_type ){
	global $dbc;
	$plugin_name_list = array(
		'textarea' => 'ilp_element_plugin_text_area'
		,'text' => 'ilp_element_plugin_text'
		,'status' => 'ilp_element_plugin_status'
		,'state' => 'ilp_element_plugin_state'
		,'radio' => 'ilp_element_plugin_rdo'
		,'html' => 'ilp_element_plugin_html_editor'
		,'dropdown' => 'ilp_element_plugin_dd'
		,'date_deadline' => 'ilp_element_plugin_date_deadline'
		,'date' => 'ilp_element_plugin_date'
		,'course' => 'ilp_element_plugin_course'
		,'cat' => 'ilp_element_plugin_category'
	);
	$plugin_name = false;
	if( in_array( $element_type, array_keys( $plugin_name_list ) ) ){
		$plugin_name = $plugin_name_list[ $element_type ];
	}
	if( $plugin_name ){
		$plugin = $dbc->get_plugin_by_name( $plugin_name );
		return $plugin->id;
	}
	return $plugin_name;
}

/*
* insert a new report with name and description, and return the id
* @param int $course_id
* @param string $name
* @param string $description
* @return int
*/
function create_report( $course_id, $name, $description ){
	global $USER;
	//$name .= "-" . date( 'Hms' );
	$formdata = new stdClass();
	$formdata->course_id = $course_id;
	$formdata->id = 0;
	$formdata->creator_id = $USER->id;
	$formdata->name = $name;
	$formdata->description = $description;
	$formdata->status = 1;
	$mform	= new edit_report_mform( $course_id, null );
    	$report_id = $mform->process_data($formdata);
	return $report_id;
}

/*
* utility function for testing
* @param string s
*/
function disp( $s ){
	var_crap( $s );
}
