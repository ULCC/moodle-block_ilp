<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');
class ilp_mis_attendance_overview_plugin_monthlycoursebreakdown extends ilp_mis_attendance_plugin{

    protected $monthlist = array();

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        $this->monthlist = array(
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'
        );
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
        if( is_string( $this->data ) ){
            echo $this->data;
        }
        elseif( is_array( $this->data ) ){
            echo $this->flexentable( $this->data );
        }
    }

    protected function flexentable( $data ){
        // set up the flexible table for displaying the portfolios
        $flextable = new ilp_ajax_table( 'attendance_plugin_simple' );
        $headers = array_shift( $data );
        $columns = $headers;

        //convert headers to monthnames
        foreach( $headers as &$monthid ){
            if( is_numeric( $monthid ) ){
                //@todo internationalise the month name
                $monthid = $this->monthlist[ $monthid ];
            }
        }       

        $flextable->define_columns($columns);
        $flextable->define_headers($headers);
        $flextable->initialbars(false);
        $flextable->setup();
        foreach( $data as $row ){
            $tablerow = array();
            $i = 0;
            foreach( $columns as $col ){
                $tablerow[ $col ] = $row[ $i++ ];
            }
            $flextable->add_data_keyed( $tablerow );
        }
		ob_start();
        $flextable->print_html();
		$pluginoutput = ob_get_contents();
        ob_end_clean();
        
        return $pluginoutput;
    }

    protected function get_month_headerlist( $data ){
        $monthid_field = 'monthid';
        $monthlist = array();
        foreach( $data as $row ){
            $monthid = $row[ $monthid_field ];
            if( !in_array( $monthid, $monthlist ) ){
                $monthlist[] = $monthid;
            }
        }
        return $monthlist;
    }

    public function set_data( $student_id, $display_style ){
	        $this->data = $this->get_monthly_course_breakdown( $student_id );
    }
    public function plugin_type(){
        return 'overview';
    }
	function language_strings(&$string) {
        $string['ilp_mis_attendance_plugin_mcb_pluginname']		  = 'Monthly Course Breakdown Overview';
        $string['ilp_mis_attendance_plugin_mcb_table']		  = 'Month-course table';
        $string['ilp_mis_attendance_plugin_mcb_tabledesc']		  = 'table containing overview of student attendence by course by month';
        $string[ 'ilp_mis_attendance_plugin_mcb_studentidfield' ]   = 'Student id field';
        $string[ 'ilp_mis_attendance_plugin_mcb_course_idfield' ]   = 'Course id field';
        $string[ 'ilp_mis_attendance_plugin_mcb_course_namefield' ]   = 'Course title field';
        $string[ 'ilp_mis_attendance_plugin_mcb_monthidfield' ]   = 'Numerical month field';
        $string[ 'ilp_mis_attendance_plugin_mcb_markstotal' ]   = 'marks total field';
        $string[ 'ilp_mis_attendance_plugin_mcb_markspresent' ]   = 'marks present field';
        $string[ 'ilp_mis_attendance_plugin_mcb_marksabsent' ]   = 'marks absent field';
        $string[ 'ilp_mis_attendance_plugin_mcb_marksauthabsent' ]   = 'marks authabsent field';
        $string[ 'ilp_mis_attendance_plugin_mcb_markslate' ]   = 'marks late field';
    }
    public function config_settings(&$settings)	{
    	$settingsheader 	= new admin_setting_heading('block_ilp/mis_attendance_plugin_mcb', get_string('ilp_mis_attendance_plugin_mcb_pluginname', 'block_ilp'), '');
    	$settings->add($settingsheader);
    	
    	$mcbtable		=	new admin_setting_configtext('block_ilp/mis_attendance_plugin_mcb_table',get_string( 'ilp_mis_attendance_plugin_mcb_table', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_tabledesc', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($mcbtable);
    	
    	$mcbtable		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_studentidfield',get_string( 'ilp_mis_attendance_plugin_mcb_studentidfield', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_studentidfield', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($mcbtable);
    	
    	$mcbstudentid		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_studentidfield',get_string( 'ilp_mis_attendance_plugin_mcb_studentidfield', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_studentidfield', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($mcbstudentid);
    	
    	$mcbcourseid		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_courseidfield',get_string( 'ilp_mis_attendance_plugin_mcb_course_idfield', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_course_idfield', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($mcbcourseid);
    	
    	$mcbcoursename		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_coursenamefield',get_string( 'ilp_mis_attendance_plugin_mcb_course_namefield', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_course_namefield', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($mcbcoursename);
    	
    	$mcbmonthid		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_monthidfield',get_string( 'ilp_mis_attendance_plugin_mcb_monthidfield', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_monthidfield', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($mcbmonthid);
    	
    	$markstotal		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_markstotalfield',get_string( 'ilp_mis_attendance_plugin_mcb_markstotal', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_markstotal', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($markstotal);
    	
    	$markspresent		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_markspresentfield',get_string( 'ilp_mis_attendance_plugin_mcb_markspresent', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_markspresent', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($markspresent);
    	
    	$marksabsent		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_marksabsentfield',get_string( 'ilp_mis_attendance_plugin_mcb_marksabsent', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_marksabsent', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($marksabsent);
    	
    	$marksauthabsent		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_marksauthabsentfield',get_string( 'ilp_mis_attendance_plugin_mcb_marksauthabsent', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_marksauthabsent', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($marksauthabsent);
    	
    	$markslate		=	new admin_setting_configtext('block_ilp/mis_plugin_mcb_markslatefield',get_string( 'ilp_mis_attendance_plugin_mcb_markslate', 'block_ilp' ),get_string( 'ilp_mis_attendance_plugin_mcb_markslate', 'block_ilp' ),'',PARAM_RAW);
		$settings->add($markslate);
    }
    protected function set_params( $params ){
        parent::set_params( $params );
		$this->params[ 'coursestudentmonth_table' ] = get_config( 'block_ilp', 'mis_attendance_plugin_mcb_table'  );
		$this->params[ 'coursestudentmonth_table_student_id_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_studentidfield'  );
		$this->params[ 'coursestudentmonth_table_course_id_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_courseidfield'  );
		$this->params[ 'coursestudentmonth_table_month_coursename_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_coursenamefield'  ); 
		$this->params[ 'coursestudentmonth_table_month_id_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_monthidfield'  );
		$this->params[ 'coursestudentmonth_table_month_marksTotal_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_markstotalfield'  );
		$this->params[ 'coursestudentmonth_table_month_marksPresent_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_markspresentfield'  );
		$this->params[ 'coursestudentmonth_table_month_marksAbsent_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_marksabsentfield'  );
		$this->params[ 'coursestudentmonth_table_month_marksAuthAbsent_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_marksauthabsentfield'  );
		$this->params[ 'coursestudentmonth_table_month_marksLate_field' ] = get_config( 'block_ilp', 'mis_plugin_mcb_markslatefield'  );
    }

    public function get_monthly_course_breakdown( $student_id ){
        $table = $this->params[ 'coursestudentmonth_table' ];
        $studentid_field = $this->params[ 'coursestudentmonth_table_student_id_field' ];
        $courseid_field = $this->params[ 'coursestudentmonth_table_course_id_field' ];
        $coursename_field = $this->params[ 'coursestudentmonth_table_month_coursename_field' ]; 
        $monthid_field = $this->params[ 'coursestudentmonth_table_month_id_field' ];
        $markstotal_field = $this->params[ 'coursestudentmonth_table_month_marksTotal_field' ];
        $markspresent_field = $this->params[ 'coursestudentmonth_table_month_marksPresent_field' ];
        $marksabsent_field = $this->params[ 'coursestudentmonth_table_month_marksAbsent_field' ];
        $marksauthabsent_field = $this->params[ 'coursestudentmonth_table_month_marksAuthAbsent_field' ];
        $markslate_field = $this->params[ 'coursestudentmonth_table_month_marksLate_field' ];
        $where = array( $studentid_field => array( '=' => $student_id ) );
        $fieldstr = "$studentid_field studentid, $courseid_field courseid, $coursename_field coursename, $monthid_field monthid, $markstotal_field marksTotal, $markspresent_field marksPresent, $marksabsent_field marksAbsent, $markslate_field marksLate, $marksauthabsent_field marksAuthAbsent";
        $data = $this->dbquery( $table, $where, $fieldstr, array( 'sort' => "{$courseid_field}, {$monthid_field} " ) );


        $headerrow = array( 'Subject', 'Attendance' );
        $headerkeylist = array( 'Attendance' );
        $courseidlist = array();
        $tablerowlist = array();
        $prevcourseid = false;
        $prevsubject = false;
        foreach( $data as $row ){
            $subject = "{$row[ 'courseid' ]} {$row[ 'coursename' ]}";
            if( $prevcourseid === $row[ 'courseid' ] ){
                //just another month
                if( $index = array_search( $row[ 'monthid' ] , $headerrow ) ){
                }
                else{
                    //new month column
                    $headerrow[] = $row[ 'monthid' ];
                    $headerkeylist[] = $row[ 'monthid' ];
                    //$index=( count( $headerrow ) - 1 );
                }
                //$tablerow[ $index ] = $this->calcScore( $row, 'attendance' );
            }
            elseif( $prevcourseid ){
                //new tablerow
                $tablerow = array( $prevsubject );
                $courseidlist[] = $prevcourseid;
                $tablerowlist[] = $tablerow;
                $tablerow = array( $subject );
                $courseidlist[] = $row[ 'courseid' ];
            }
            $prevcourseid = $row[ 'courseid' ];
            $prevsubject = $subject;
        }
        //insert calculated values into the row
        $tablerowlist[] = $tablerow;
        foreach( $tablerowlist as &$tablerow ){
            $courseid = array_shift( $courseidlist );
            foreach( $headerkeylist as $column ){
                $tablerow[] = $this->calculate_attendance( $courseid, $column, $data );
            }
        }

        
        //prepend the header row
        array_unshift( $tablerowlist, $headerrow );
        return $tablerowlist;
/*
        echo $this->test_entable( $tablerowlist );
        var_crap($data);
        exit;
*/
    }
    protected function calculate_attendance( $courseid, $col, $data ){
        $field_total_list = array(
            'marksTotal' => 0,
            'marksPresent' => 0,
            'marksAbsent' => 0,
            'marksAuthAbsent' => 0,
            'marksLate' => 0
        );
        foreach( $data as $row ){
            if( $row[ 'courseid' ] == $courseid ){
                if( 'Attendance' == $col || $row[ 'monthid' ] == $col ){
                    foreach( array_keys( $field_total_list ) as $key ){
                        $field_total_list[ $key ] += $row[ $key ];
                    }
                }
            }
        }
        return $this->calcScore( $field_total_list, 'attendance' );
    }
}
