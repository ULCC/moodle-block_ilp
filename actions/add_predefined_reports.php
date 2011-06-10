<?php
require_once('../configpath.php');
//global $USER, $CFG, $SESSION, $PARSER;
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/forms/edit_report_mform.php');

$P = new ilp_predefined_reports();
$P->disp( $P->main() );

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
class ilp_predefined_reports{
	public function __construct(){
		global $CFG, $USER, $SESSION, $PARSER;
		$this->dbc = new ilp_db();
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
		//while( ( $file = $dir->read() ) !==false ){
		foreach( scandir( $reports_dir ) as $file ){
			if( 'report_' == substr( $file, 0, 7 ) ){
				$fullpath = $reports_dir . DIRECTORY_SEPARATOR . $file;
				$info = pathinfo( $file );
				if( 'php' == $info[ 'extension' ] ){
					include( $fullpath );
				}
				elseif( 'xml' == $info[ 'extension' ] ){
					$reportlist[] = $this->simple_xml_2_array( new SimpleXMLElement( file_get_contents( $fullpath ) ) );
				}
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
				$this->disp( "truncate $tablename FAILED" );
			}
		}
		return $count;
	}
	
	/*
	* the main function of this class - invoke it to install all the predefined reports
	* scan the predefined reports directory and insert its contents into the ilp report tables
	* @return array of ints
	*/
	public function main(){
		global $USER, $CFG, $SESSION, $PARSER;
		$conn = quickdb::get_connection();
		if(0){
			//turn off this block on production systems !
			$trunccount = $this->trunc_ilp_tables( $conn );
			$s = ( 1 == $trunccount ) ? '' : 's' ;
			$this->disp( "$trunccount table$s truncated" );
		}
		$info = array( 'reportlist' => array(), 
				'warninglist' => array(), 
				'elementlist' => array()
		);
		foreach( $this->get_report_list() as $report ){
			$report_title = $report[ "title" ];
			$report_description = $report[ "description" ];
			$report_id = $this->create_report( $report_title, $report_description );
			if( $report_id ){
				$info[ 'reportlist' ][] = $report_title;
				foreach( $report[ "fieldlist" ] as $element ){
					$plugin_id = $this->get_element_type_id_from_control_type( $element[ "type" ] );
					if( $plugin_id ){
						$label = $element[ "label" ];
						$description = $element[ "description" ];
						$req = $element[ "req" ];
						//everything ok - add the element to the report
						if( $element_id = $this->apply_to_report( $conn, $report_id, $plugin_id, $label, $description, $req, $element ) ){
							$info[ 'elementlist' ][] = "added $label to $report_title";
						}
					}
				}
			}
			else{
				$info[ 'warninglist' ][] = "Report already exists: $report_title";
			}
		}
		return $info;
	}
	
	/*
	* take in an array and generate xml
	* @param array $report
	* @return string of xml
	*/
	public function generate_xml( $report ){
		$xml = new SimpleXMLElement( "<?xml version='1.0'?><xml/>" );
		foreach( $report as $key=>$value ){
			$this->recursive_add( $xml, $key, $value );
		}
		return $xml->asXML();
	}
	
	/*
	* receive a reference to a SimpleXMLElement and add new values as children
	* @param SimpleXMLElement $simple_xml
	* @param string $childname
	* @param mixed $childvalue (could be int, string or array)
	*/
	function recursive_add( &$simple_xml, $childname, $childvalue ){
		if( is_numeric( $childvalue ) ){
			//coerce to string
			$childvalue = '' . $childvalue;
		}
		if( is_string( $childvalue ) ){
			$child = $simple_xml->addChild( $childname, $childvalue );
			return $child;
		}
		if( is_array( $childvalue ) ){
			$element = $simple_xml->addChild( $childname );
			foreach( $childvalue as $key=>$value ){
				$attribs = array();
				if( is_numeric( $key ) ){
					$key = 'listitem' . $key;
					$attribs[ 'value' ] = $key;
				}
				$this->recursive_add( $element, $key, $value );
			}
		}
	}
	
	/*
	* adapted from http://us2.php.net/manual/en/ref.simplexml.php:XMLToArray($xml)
	* @param SimpleXMLElement $simple_xml
	* @return array
	*/
	function simple_xml_2_array( $simple_xml ){
		if( is_string( $simple_xml ) ){
			$simple_xml = new SimpleXMLElement( $simple_xml );
		}
		foreach ( (array) $simple_xml as $index => $node ){
		    if( 'listitem' == substr( $index, 0, 8 ) ){
			$outkey = substr( $index, 8 );
		    }
		    else{
			$outkey = $index;
		    }
	            $out[$outkey] = ( is_object ( $node ) ) ? $this->simple_xml_2_array( $node ) : $node;
		}
	        return $out;
	}
	
	/*
	* add a control to a report
	* @param mysqli connection $conn
	* @param int $report_id
	* @param int $plugin_id
	* @param string $label
	* @param string $description
	* @param int $req
	* @param array $element
	* @return int
	*/
	protected function apply_to_report( $conn, $report_id, $plugin_id, $label, $description, $req, $element ){
		global $CFG;
		$pluginrecord	=	$this->dbc->get_plugin_by_id($plugin_id);
		$plugin_table_name = $pluginrecord->tablename;
		$reportfield_id = $this->insert_report_field( $conn, $report_id, $label, $description, $plugin_id, $req );
		$specific_sql = "INSERT INTO {$CFG->prefix}{$pluginrecord->tablename} ( reportfield_id, timecreated, timemodified ) VALUES ($reportfield_id, NOW(), NOW() )";
		$specific_parent_id = quickdb::execute_sql( $conn, $specific_sql );
		if( in_array( 'opts' , array_keys( $element ) ) ){
			//it's a list type element (radio, dropdown, select) with some option items
			$itemtable = $plugin_table_name . '_items';
			foreach( $element[ "opts" ] as $value=>$name ){
				$sql = "INSERT INTO {$CFG->prefix}$itemtable ( parent_id, value, name, timemodified, timecreated ) VALUES ( $specific_parent_id, '$value', '$name', NOW(), NOW() )";
				if( !quickdb::execute_sql( $conn, $sql ) ){
					$this->disp( "FAILED: $sql" );
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
	protected function insert_report_field( $conn, $report_id, $label, $description, $plugin_id, $req ){
		global $USER, $CFG;
		$tablename = 'block_ilp_report_field';
		$tablename = $CFG->prefix . $tablename;
		$position = $this->get_next_position( $report_id );
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
	protected function get_next_position( $report_id ){
		$tablename = 'block_ilp_report_field';
		return $this->dbc->get_next_position( $report_id , $tablename );
	}
	
	/*
	* take an everyday element type name (eg 'radio') and return the correct type_id
	* @param string $element_type (eg 'dropdown', 'text', 'textarea' - anything which is a key in $plugin_name_list)
	* @return int or false
	*/
	protected function get_element_type_id_from_control_type( $element_type ){
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
			$plugin = $this->dbc->get_plugin_by_name( $plugin_name );
			return $plugin->id;
		}
		return $plugin_name;
	}
	
	/*
	* insert a new report with name and description, and return the id
	* @param string $name
	* @param string $description
	* @return int
	*/
	protected function create_report( $name, $description ){
		//does this report exist already ?
		//@todo return 0 if report already exists
		if( $this->report_already_exists( $name ) ){
			return 0;
		}
		global $USER;
		//$name .= "-" . date( 'Hms' );
		$formdata = new stdClass();
		$formdata->id = 0;
		$formdata->creator_id = $USER->id;
		$formdata->name = $name;
		$formdata->description = $description;
		$formdata->status = 1;
		$course_id = 0;	//not necessary for report creation, so just a dummy value
		$mform	= new edit_report_mform( $course_id, null );
	    	$report_id = $mform->process_data($formdata);
		return $report_id;
	}

	protected function report_already_exists( $name ){
		global $CFG;
		return $this->dbc->record_exists( 'block_ilp_report', 'name', $name );
	}
	
	/*
	* utility function for testing
	* @param string s
	*/
	public function disp( $s ){
		var_crap( $s );
	}
}
