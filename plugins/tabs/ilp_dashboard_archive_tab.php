<?php

global $CFG;

//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');

/**
 * Class ilp_dashboard_archive_tab
 */
class ilp_dashboard_archive_tab extends ilp_dashboard_tab {

    /**
     * @var null
     */
    public		$student_id;
    /**
     * @var
     */
    public 		$filepath;
    /**
     * @var string
     */
    public		$linkurl;
    /**
     * @var bool
     */
    public 		$selectedtab;
    /**
     * @var
     */
    public		$role_ids;
    /**
     * @var
     */
    public 		$capability;

    /**
     * @param null $student_id
     * @param null $course_id
     */
    function __construct($student_id=null,$course_id=null)	{
		global 	$CFG;
		
		$this->linkurl	=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id=".$student_id."&course_id={$course_id}";
		
		$this->student_id	=	$student_id;
		
		$this->course_id	=	$course_id;
		
		$this->selectedtab	=	false;
		
		
		
		//call the parent constructor
		parent::__construct();
		
		$this->dbc	=	new ilp_archive_db();
		
	}
	
	/**
	 * Return the text to be displayed on the tab
	 */
	function display_name()	{
		return	get_string('ilp_dashboard_archive_tab_name','block_ilp');
	}
	
    /**
     * Override this to define the second tab row should be defined in this function  
     */
    function define_second_row()	{

    	$reportone		=	get_config('block_ilp','mis_archive_tab_reportone');
    	$reporttwo		=	get_config('block_ilp','mis_archive_tab_reporttwo');
    	$reportthree	=	get_config('block_ilp','mis_archive_tab_reportthree');
    	$reportfour		=	get_config('block_ilp','mis_archive_tab_reportfour');
    	$reportfive		=	get_config('block_ilp','mis_archive_tab_reportfive');
    	$reporttarget	=	get_config('block_ilp','mis_archive_tab_target');
    	$reportstudent	=	get_config('block_ilp','mis_archive_tab_studentinfo');
    	$reportteacher	=	get_config('block_ilp','mis_archive_tab_teacherinfo');
    	$reporttutor	=	get_config('block_ilp','mis_archive_tab_tutorinfo');
    	
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
		if (!empty($this->plugin_id)) {	
			if (!empty($reportstudent)) $this->secondrow[]	=	array('id'=>1,'link'=>$this->linkurl,'name'=>$reportstudent);
			if (!empty($reportstudent)) $this->secondrow[]	=	array('id'=>2,'link'=>$this->linkurl,'name'=>$reportteacher);
			if (!empty($reportstudent)) $this->secondrow[]	=	array('id'=>3,'link'=>$this->linkurl,'name'=>$reporttutor);
			if (!empty($reporttarget))	$this->secondrow[]	=	array('id'=>4,'link'=>$this->linkurl,'name'=>$reporttarget);
			if (!empty($reportone))		$this->secondrow[]	=	array('id'=>5,'link'=>$this->linkurl,'name'=>$reportone);
			if (!empty($reporttwo))		$this->secondrow[]	=	array('id'=>6,'link'=>$this->linkurl,'name'=>$reporttwo);
			if (!empty($reportthree))	$this->secondrow[]	=	array('id'=>7,'link'=>$this->linkurl,'name'=>$reportthree);
			if (!empty($reportfour))	$this->secondrow[]	=	array('id'=>8,'link'=>$this->linkurl,'name'=>$reportfour);
			if (!empty($reportfive))	$this->secondrow[]	=	array('id'=>9,'link'=>$this->linkurl,'name'=>$reportfive);
		}
    }
    
    
    /**
     * Override this to define the third tab row should be defined in this function  
     */
    function define_third_row()	{
    	
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
    	if (!empty($this->plugin_id) && !empty($this->selectedtab)) {	
    	
    		
    	}
    	    	
    }

	
	
	/**
	 * Returns the content to be displayed 
	 *
	 * @param	string $selectedtab the tab that has been selected this variable
	 * this variable should be used to determined what to display
	 * 
	 * @return string
	  */
	function display($selectedtab=null)	{
		global 	$CFG, $PAGE, $PARSER;
		
		//get the selecttab param if has been set
		$this->selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

		//get the tabitem param if has been set
		$this->tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_CLEAN);
		
		//split the selected tab id on up 3 ':'
		$seltab	=	explode(':',$selectedtab);
					
		//if the seltab is empty then the highest level tab has been selected
		if (empty($seltab))	$seltab	=	array($selectedtab); 
		
		$pluginoutput	=	"";
		
		if ($this->dbc->get_user_by_id($this->student_id)) {
			
			//start buffering output
			ob_start();
			if (!empty($seltab[1]))	{ 
				switch ($seltab[1]) {
					
					case 1:
						$this->ilp_display_student_info($this->student_id);
						break;
						
					case 2:
						$this->ilp_display_teacher_info($this->student_id);
						break;

					case 3:
						$this->ilp_display_tutor_info($this->student_id);
						break;
					
					case 4:
						$this->ilp_display_targets($this->student_id);
						break;
	
					case 5:
						$this->ilp_display_concerns($this->student_id,0);
						break;
	
					case 6:
						$this->ilp_display_concerns($this->student_id,1);
						break;
	
					case 7:
						$this->ilp_display_concerns($this->student_id,2);
						break;
	
					case 8:
						$this->ilp_display_concerns($this->student_id,3);
						break;
	
					case 9:
						$this->ilp_display_concerns($this->student_id,4);
						break;					
					
					default:
						$this->ilp_display_concerns($this->student_id,5);
					break;
				}
			}			
			// load custom javascript
			$module = array(
			    'name'      => 'ilp_dashboard_archive_tab',
			    'fullpath'  => '/blocks/ilp/plugins/tabs/ilp_dashboard_archive_tab.js',
			    'requires'  => array('yui2-dom', 'yui2-event', 'yui2-connection', 'yui2-container', 'yui2-animation')
			);

			// js arguments
			$jsarguments = array(
			    'open_image'   => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_minus.gif",
			    'closed_image' => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_plus.gif",
			);
			
			// initialise the js for the page
			$PAGE->requires->js_init_call('M.ilp_dashboard_archive_tab.init', $jsarguments, true, $module);
			
			$pluginoutput = ob_get_contents();
			
			ob_end_clean();

			
		} else {
			$pluginoutput	=	get_string('studentnotfound','block_ilp');
		}
			
		return $pluginoutput;
	}
 	 
	 /**
	  * 
	  * Retrieves student info data from ILP 1 tables to be displayed in the archive tab
	  * @param int $student_id
	  */ 	 
    function ilp_display_student_info($student_id)	{

 	 		$infotexts			=	array();
 	 		
 	 		$studentinfo		=	$this->dbc->get_per_student_info($student_id);

 	 		$this->return_texts($studentinfo,$infotexts);
 	 		
 	 		if (!empty($infotexts)) {
	 	 		$this->get_archive_student_info($infotexts);
 	 		}
 	 }
 	 
	 /**
	  * Retrieves teacher info data from ILP 1 tables to be displayed in the archive tab
	  * @param int $student_id
	  */ 	 
 	 
 	 function ilp_display_teacher_info($student_id)	{
 	 		global	$CFG;
 	 	
 	 		$teacherinfo		=	$this->dbc->get_per_teacher_info($student_id);
 	 		
 	 		$infotexts			=	array();
 	 		
 	 		foreach ($teacherinfo as $ti) {
 	 			$this->return_texts($ti,$infotexts);	
 	 		}
 	 		
 	 		if (!empty($infotexts)) {
	 	 		$this->get_archive_student_info($infotexts);
 	 		}
 	 }

    /**
     * @param $student_id
     */
    function ilp_display_tutor_info($student_id)	{

 	 		$tutorinfo		=	$this->dbc->get_per_tutor_info($student_id);
 	 		
 	 		$infotexts			=	array();
 	 		
 	 		foreach ($tutorinfo as $ti) {
 	 			$this->return_texts($ti,$infotexts);	
 	 		}
 	 		
 	 		if (!empty($infotexts)) {
	 	 		$this->get_archive_student_info($infotexts);
 	 		}
 	 }
 	 
 	 /**
 	  * 
 	  * Places the text record (teacher,shared & student) into the given array (if one exists)  
 	  * @param object $infoobj expected to be a record from containing the fields: teacher_textid,
 	  * shared_textid and student_textid
 	  * @param array $infotexts
 	  */
 	 
 	 function return_texts($infoobj,&$infotexts)	{
 	 		 	 		
 	  	 	if (!empty($infoobj) && !empty($infoobj->student_textid))	{
 	 			$text	=	$this->dbc->get_info_text($infoobj->student_textid);
 	 			if (!empty($text))  {
 	 				$text->type			=	'student';
 	 				$text->course_id	=	$infoobj->courseid;
 	 				$text->user_id		=	$infoobj->student_userid;
 	 				$infotexts[]		=	$text;	
 	 			}
 	 		} 
 	 	
 	 		if (!empty($infoobj) && !empty($infoobj->teacher_textid))	{
 	 			$text	=	$this->dbc->get_info_text($infoobj->teacher_textid);
 	 			if (!empty($text))  {	
 	 				$text->type		=	'teacher';
 	 				$text->course_id	=	$infoobj->courseid;
 	 				$text->user_id		=	$infoobj->teacher_userid;
 	 				$infotexts[]	=	$text;		
 	 			}
 	 		}
 	 		
 	 		if (!empty($infoobj) && !empty($infoobj->shared_textid))	{
 	 			$text	=	$this->dbc->get_info_text($infoobj->shared_textid);
 	 			if (!empty($text))  {
 	 				$text->type		=	'shared';
 	 				$text->course_id	=	$infoobj->courseid;
 	 				$infotexts[]	=	$text;		
 	 			}
 	 		}
 	 }

    /**
     * @param $studentinforeport
     */
    function get_archive_student_info($studentinforeport)	{
 	 		global 	$CFG;
 	 		
	 	 	foreach ($studentinforeport as $sir) {
		 				 	 			
		 		$setby				=	$this->dbc->get_user_by_id($sir->lastchanged_userid);
		 		$sir->setbyname		=	fullname($setby);
		 		$course				=	$this->dbc->get_course_by_id($sir->course_id);
		 		$sir->coursename	= 	(!empty($course)) ? $course->fullname : false; 
		 		$sir->creationtime	=	userdate($sir->lastchanged_datetime, get_string('strftimedate'));
		 			
		 		$post					=	$sir;
		 		$post->displayfields	=	array();
		 		$post->displayfields[]	=	array('label'=>get_string('ilp_dashboard_archive_tab_studentinfo_'.$sir->type, 'block_ilp'),'content'=>$post->text);
		 		
		 		include($CFG->dirroot.'/blocks/ilp/plugins/tabs/ilp_dashboard_archive_tab.html');
		 	}
 	 	
 	 }
 	 
 	 /**
 	  *	Returns the archived data from ilp 1.0 student targets 
 	  * 
 	  * 
 	  */
 	 function ilp_display_targets($student_id)	{

 	 		$archivetargetposts		=	$this->dbc->get_student_target_posts($student_id);	
 	 		
 	 		if (!empty($archivetargetposts)) {
 	 		
	 	 		$this->get_archive_student_ilptargets_lists($archivetargetposts);
 	 		
 	 		}
 	 	
 	 	
 	 }

    /**
     * @param $archivetargetposts
     */
    function get_archive_student_ilptargets_lists($archivetargetposts)	{
 	 	global 	$CFG;
 	 	
 	 	foreach ($archivetargetposts as $atp) {
	 		$category			=	$this->dbc->get_archive_category($atp->category);
	 		$atp->catgoryname	=	(!empty($category)) ? $category->name	: '';
	 	 			
	 		$setby				=	$this->dbc->get_user_by_id($atp->setbyuserid);
	 		$atp->setbyname		=	fullname($setby);
	 		$course				=	false;
	 	 			
	 		if ($atp->courserelated == 1) {
	 			$course		=	$this->dbc->get_course_by_id($atp->targetcourse);
	 		}	
	 	 			
	 		$atp->coursename	=	(!empty($course))	?	$course->shortname	: "";
	 	 			
	 		$atp->creationtime	=	userdate($atp->timecreated, get_string('strftimedate'));
	 		$atp->deadlinetime	=	userdate($atp->deadline, get_string('strftimedate'));
	 			
	 		$comments	=	$this->dbc->get_target_comments($atp->id);
			
	 		$post	=	$atp;
	 		$post->displayfields	=	array();
	 		$post->displayfields[]	=	array('label'=>get_string('ilp_dashboard_archive_tab_targetname', 'block_ilp'),'content'=>$post->name);
	 		$post->displayfields[]	=	array('label'=>get_string('ilp_dashboard_archive_tab_targetagreed', 'block_ilp'),'content'=>$post->targetset);
	 		
	 		include($CFG->dirroot.'/blocks/ilp/plugins/tabs/ilp_dashboard_archive_tab.html');
	 	}
	 }

    /**
     * @param $student_id
     * @param bool $reporttype_id
     */
    function ilp_display_concerns($student_id,$reporttype_id=false)	{
	 	global	$CFG;
	 	
	 	$archiveconcerns	=	$this->dbc->get_student_concern_posts($student_id,$reporttype_id);
	 	
	 	if (!empty($archiveconcerns)) {
	 		$this->get_archive_student_concern_lists($archiveconcerns);
	 	}
	 }

    /**
     * @param $archiveconcerns
     */
    function get_archive_student_concern_lists($archiveconcerns)	{
	 	global 	$CFG;
 	 	
 	 	foreach ($archiveconcerns as $ac) {
	 		$setby				=	$this->dbc->get_user_by_id($ac->setbyuserid);
	 		$ac->setbyname		=	fullname($setby);
	 		$course				=	false;
	 	 			
	 		if ($ac->courserelated == 1) {
	 			$course		=	$this->dbc->get_course_by_id($ac->targetcourse);
	 		}	
	 	 			
	 		$ac->coursename	=	(!empty($course))	?	$course->shortname	: "";
	 	 			
	 		$ac->creationtime	=	userdate($ac->timecreated, get_string('strftimedate'));
	 		$ac->deadlinetime	=	userdate($ac->deadline, get_string('strftimedate'));
	 			
	 		$comments	=	$this->dbc->get_concern_comments($ac->id);
 	 		
	 		$post	=	$ac;
	 		$post->displayfields	=	array();
	 		$post->displayfields[]	=	array('label'=>get_string('ilp_dashboard_archive_tab_concername', 'block_ilp'),'content'=>$post->concernset);

	 		include($CFG->dirroot.'/blocks/ilp/plugins/tabs/ilp_dashboard_archive_tab.html');
 	 		
 	 	}	 	
	 }
	 
	 	/**
 	  * Adds config settings for the plugin to the given mform
 	  * 
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	//get the name of the current class
 	 	$classname	=	get_class($this);
 	 	
 	 	$this->config_text_element($mform, 'mis_archive_tab_reportone', get_string('ilp_dashboard_archive_tab_reportoneheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_report1', 'block_ilp'));
 	 	
 	 	$this->config_text_element($mform, 'mis_archive_tab_reporttwo', get_string('ilp_dashboard_archive_tab_reporttwoheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_report2', 'block_ilp'));
 	 	
 	 	$this->config_text_element($mform, 'mis_archive_tab_reportthree', get_string('ilp_dashboard_archive_tab_reportthreeheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_report3', 'block_ilp'));
 	 	
 	 	$this->config_text_element($mform, 'mis_archive_tab_reportfour', get_string('ilp_dashboard_archive_tab_reportfourheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_report4', 'block_ilp'));
 	 	
 	 	$this->config_text_element($mform, 'mis_archive_tab_reportfive', get_string('ilp_dashboard_archive_tab_reportfiveheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_report5', 'block_ilp'));

 	 	$this->config_text_element($mform, 'mis_archive_tab_studentinfo', get_string('ilp_dashboard_archive_tab_reportstudentheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_student', 'block_ilp'));
 	 	
 	 	$this->config_text_element($mform, 'mis_archive_tab_teacherinfo', get_string('ilp_dashboard_archive_tab_reportteacherheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_teacher', 'block_ilp'));
 	 	
 	 	$this->config_text_element($mform, 'mis_archive_tab_tutorinfo', get_string('ilp_dashboard_archive_tab_reporttutorheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_tutor', 'block_ilp'));
 	 	
 	 	$this->config_text_element($mform, 'mis_archive_tab_target', get_string('ilp_dashboard_archive_tab_reporttargetheader', 'block_ilp'), get_string('ilp_dashboard_archive_tab_reportdesc', 'block_ilp'), get_string('ilp_dashboard_archive_tab_target', 'block_ilp'));
 
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,$classname.'_pluginstatus',$options,get_string($classname.'_name', 'block_ilp'),get_string('tabstatusdesc', 'block_ilp'),0);
 	 	
 	 }
	
 	 
 	 
 	 
 	 
 	 
 	 
	
}

/**
 * Class ilp_archive_db
 */
class ilp_archive_db extends ilp_db	{

    /**
     *
     */
    function __construct() {
        global $CFG;

        // include the static constants
        require_once($CFG->dirroot.'/blocks/ilp/lib.php');

        // instantiate the Assessment admin database
        $this->dbc = new ilp_archive_db_functions();
    }
	
	
	
	
	
}

/**
 * Class ilp_archive_db_functions
 */
class ilp_archive_db_functions extends ilp_db_functions	{

    /**
     *
     */
    function __construct() {
		parent::__construct();
	}

    /**
     * @param $student_id
     * @param $status
     * @param string $sortorder
     * @return array
     */
    function get_student_target_posts($student_id,$status=-1,$sortorder='DESC')	{
		global $CFG;
		
		$sql	=	"SELECT 	{$CFG->prefix}ilptarget_posts.*, up.username
					 FROM		{$CFG->prefix}ilptarget_posts, {$CFG->prefix}user up
					 WHERE		up.id = setbyuserid AND setforuserid = {$student_id}
					 ORDER BY 	timemodified $sortorder ";
		
		return 	$this->dbc->get_records_sql($sql);
	}

    /**
     * @param $student_id
     * @param bool $reporttype_id
     * @param string $sortorder
     * @return array
     */
    function get_student_concern_posts($student_id,$reporttype_id=false,$sortorder='DESC')	{
		global 	$CFG;

        $statussql  =  ($reporttype_id !== false) ? "AND status = {$reporttype_id}"	: "";
		
		$sql	=	"SELECT		{$CFG->prefix}ilpconcern_posts.*, up.username
					 FROM 		{$CFG->prefix}ilpconcern_posts, {$CFG->prefix}user up
					 WHERE		up.id = setbyuserid 
					 {$statussql} 
					 AND 		setforuserid = {$student_id}
					 ORDER BY 	timemodified $sortorder ";

		return $this->dbc->get_records_sql($sql);
		
	}

    /**
     * @param $category_id
     * @return bool|mixed
     */
    function get_archive_category($category_id)	{
		return (!empty($category_id)) ? $this->dbc->get_record('ilp_post_category',array('id'=>$category_id)) : false;
	}

    /**
     * @param $target_id
     * @return array
     */
    function get_target_comments($target_id)	{
		return $this->dbc->get_records('ilptarget_comments',array('targetpost'=>$target_id));
	}

    /**
     * @param $concern_id
     * @return array
     */
    function get_concern_comments($concern_id)	{
		return $this->dbc->get_records('ilpconcern_comments',array('concernspost'=>$concern_id));
	}

    /**
     * @param $student_id
     * @return mixed
     */
    function get_per_student_info($student_id)	{
		return $this->dbc->get_record('ilp_student_info_per_student',array('student_userid'=>$student_id));
	}

    /**
     * @param $student_id
     * @return array
     */
    function get_per_teacher_info($student_id)	{
		return $this->dbc->get_records('ilp_student_info_per_teacher',array('student_userid'=>$student_id));
	}

    /**
     * @param $student_id
     * @return array
     */
    function get_per_tutor_info($student_id)	{
		return $this->dbc->get_records('ilp_student_info_per_tutor',array('student_userid'=>$student_id));
	}

    /**
     * @param $text_id
     * @return mixed
     */
    function get_info_text($text_id)	{
	   	return  $this->dbc->get_record('ilp_student_info_text',array('id'=>$text_id)) ;
	}
	
	
	
}
