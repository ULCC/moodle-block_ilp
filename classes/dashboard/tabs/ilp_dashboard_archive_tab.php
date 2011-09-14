<?php

//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_tab.php');

class ilp_dashboard_archive_tab extends ilp_dashboard_tab {
	
	public		$student_id;
	public 		$filepath;	
	public		$linkurl;
	public 		$selectedtab;
	public		$role_ids;
	public 		$capability;
	
	
	function __construct($student_id=null,$course_id=null)	{
		global 	$CFG,$USER,$PAGE;
		
		$this->linkurl					=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id=".$student_id."&course_id={$course_id}";
		
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
    	global 	$CFG,$USER,$PAGE,$OUTPUT,$PARSER;
    	
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
		if (!empty($this->plugin_id)) {	
			$this->secondrow[]	=	array('id'=>2,'link'=>$this->linkurl,'name'=>'Target Reports');
			$this->secondrow[]	=	array('id'=>3,'link'=>$this->linkurl,'name'=>'Report 1');
			$this->secondrow[]	=	array('id'=>4,'link'=>$this->linkurl,'name'=>'Report 2');
			$this->secondrow[]	=	array('id'=>5,'link'=>$this->linkurl,'name'=>'Report 3');
			$this->secondrow[]	=	array('id'=>6,'link'=>$this->linkurl,'name'=>'Report 4');
			$this->secondrow[]	=	array('id'=>7,'link'=>$this->linkurl,'name'=>'Report 5');
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
	 * @return none
	  */
	function display($selectedtab=null)	{
		global 	$CFG, $PAGE, $USER, $OUTPUT, $PARSER;
		
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
			
			switch ($seltab[1]) {
				case 2:
					$this->ilp_display_targets($this->student_id);
					break;

				case 3:
					$this->ilp_display_concerns($this->student_id,0);
					break;

				case 4:
					$this->ilp_display_concerns($this->student_id,1);
					break;

				case 5:
					$this->ilp_display_concerns($this->student_id,2);
					break;

				case 6:
					$this->ilp_display_concerns($this->student_id,3);
					break;

				case 7:
					$this->ilp_display_concerns($this->student_id,4);
					break;					
				
				default:
					$this->ilp_display_concerns($this->student_id,5);
				break;
			}
			
			// load custom javascript
			$module = array(
			    'name'      => 'ilp_dashboard_archive_tab',
			    'fullpath'  => '/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_archive_tab.js',
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
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 function language_strings(&$string) {
        $string['ilp_dashboard_archive_tab'] 					= 'Archive';
        $string['ilp_dashboard_archive_tab_name'] 				= 'Archives';
        
        $string['ilp_dashboard_archive_tab_targetname'] 				= 'Name';
        $string['ilp_dashboard_archive_tab_targetagreed'] 				= 'Target';
        $string['ilp_dashboard_archive_tab_targetcategory'] 			= 'Category';
        $string['ilp_dashboard_archive_tab_addedby'] 					= 'Set By';
        $string['ilp_dashboard_archive_tab_targetset'] 					= 'Set';
        $string['ilp_dashboard_archive_tab_targetdeadline'] 			= 'Deadline';
        $string['ilp_dashboard_archive_tab_concername'] 				= 'Concern';
        $string['ilp_dashboard_archive_tab_report1']	 				= 'Report1';
        $string['ilp_dashboard_archive_tab_report2']	 				= 'Report2';
        $string['ilp_dashboard_archive_tab_report3']	 				= 'Report3';
        $string['ilp_dashboard_archive_tab_report4']	 				= 'Report4';
        $string['ilp_dashboard_archive_tab_report5']	 				= 'Report5';
        return $string;
    }
	
	
	/**
 	  * Adds config settings for the plugin to the given mform
 	  * by default this allows config option allows a tab to be enabled or dispabled
 	  * override the function if you want more config options REMEMBER TO PUT 
 	  * 
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	//get the name of the current class
 	 	$classname	=	get_class($this);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,$classname.'_pluginstatus',$options,get_string($classname.'_name', 'block_ilp'),get_string('tabstatusdesc', 'block_ilp'),0);
 	 	
 	 }
 	 
 	 /**
 	  *	Returns the archived data from ilp 1.0 student targets 
 	  * 
 	  * 
 	  */
 	 function ilp_display_targets($student_id)	{
 	 		global	$CFG;
 	 	
 	 		$archivetargetposts		=	$this->dbc->get_student_target_posts($student_id);	
 	 		
 	 		if (!empty($archivetargetposts)) {
 	 		
	 	 		$this->get_archive_student_ilptargets_lists($archivetargetposts);
 	 		
 	 		}
 	 	
 	 	
 	 }
 	 
 	 
 	 function get_archive_student_ilptargets_lists($archivetargetposts)	{
 	 	global 	$CFG, $PAGE, $USER, $OUTPUT, $PARSER;
 	 	
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
	 		
	 		include($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_archive_tab/ilp_dashboard_archive_tab_target.html');
	 	}
	 }
	 
	 function ilp_display_concerns($student_id,$reporttype_id=false)	{
	 	global	$CFG;
	 	
	 	$archiveconcerns	=	$this->dbc->get_student_concern_posts($student_id,$reporttype_id);
	 	
	 	if (!empty($archiveconcerns)) {
	 		$this->get_archive_student_concern_lists($archiveconcerns);
	 	}
	 }
	 
	 
	 function get_archive_student_concern_lists($archiveconcerns)	{
	 	global 	$CFG, $PAGE, $USER, $OUTPUT, $PARSER;
 	 	
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

	 		include($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_archive_tab/ilp_dashboard_archive_tab_target.html');
 	 		
 	 	}	 	
	 }
 	 
 	 
 	 
 	 
 	 
 	 
	
}




class ilp_archive_db extends ilp_db	{
	
	function __construct() {
        global $CFG;

        // include the static constants
        require_once($CFG->dirroot.'/blocks/ilp/lib.php');

        // instantiate the Assessment admin database
        $this->dbc = new ilp_archive_db_functions();
    }
	
	
	
	
	
}


class ilp_archive_db_functions extends ilp_db_functions	{
	
	function __construct() {
		parent::__construct();
	}
	
	
	function get_student_target_posts($student_id,$status=-1,$sortorder='ASC')	{
		global $CFG;
		
		$sql	=	"SELECT 	{$CFG->prefix}ilptarget_posts.*, up.username
					 FROM		{$CFG->prefix}ilptarget_posts, {$CFG->prefix}user up
					 WHERE		up.id = setbyuserid AND setforuserid = {$student_id}
					 ORDER BY 	deadline $sortorder ";

		/* re-implement for status
		if($status != -1) {
			$where .= "AND status = $status ";
		}elseif($config->ilp_show_achieved_targets == 1){
	    	$where .= "AND status != 3 ";
		}else{
	    	$where .= "AND status = 0 ";
		}
		*/
		
		return 	$this->dbc->get_records_sql($sql);
	}
	
	function get_student_concern_posts($student_id,$reporttype_id=false)	{
		global 	$CFG;
		
		$statussql	=	(!empty($reporttype_id))	? "AND 		status = {$reporttype_id}"	: "";		
		
		$sql	=	"SELECT		{$CFG->prefix}ilpconcern_posts.*, up.username
					 FROM 		{$CFG->prefix}ilpconcern_posts, {$CFG->prefix}user up
					 WHERE		up.id = setbyuserid 
					 {$statussql} 
					 AND 		setforuserid = {$student_id}";

		return $this->dbc->get_records_sql($sql);
		
	}
	
	function get_archive_category($category_id)	{
		return $this->dbc->get_record('ilp_post_category',array('id'=>$category_id));
	}
	
	function get_target_comments($target_id)	{
		return $this->dbc->get_records('ilptarget_comments',array('targetpost'=>$target_id));
	}
	
	function get_concern_comments($concern_id)	{
		return $this->dbc->get_records('ilpconcern_comments',array('concernspost'=>$concern_id));
	}
	
	
	
}
