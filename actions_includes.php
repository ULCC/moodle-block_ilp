<?php

//include the moodle library
require_once($CFG->dirroot.'/lib/moodlelib.php');

//include the ilp parser class
require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_parser.class.php');

//include ilp db class
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

require_once($CFG->dirroot."/blocks/ilp/classes/ilp_formslib.class.php");

//include the library file
require_once($CFG->dirroot.'/blocks/ilp/lib.php');

//include the static constants
require_once($CFG->dirroot.'/blocks/ilp/constants.php');

//include the access checks file
require_once($CFG->dirroot.'/blocks/ilp/db/accesscheck.php');

if ($USER->id != $user_id ) {
	
	//we only require the viewotherilp capabilty id the user is not a ilp admin
	if (empty($access_ilp_admin)) require_capability('block/ilp:viewotherilp', $context);
	
	if (!empty($course_id))	{

		$currentcoursecontext	=	get_context_instance(CONTEXT_COURSE, $course_id);
		
		if ($context ==	$currentcoursecontext)	{
			$dbc			=	new ilp_db();
			$userenrolled	=	$dbc->get_user_by_id($user_id);
			//check that the user is enrolled on the current course if not then print error			
			$viewilp = true;
                        if (!is_enrolled($context,$userenrolled)) $viewilp = false;	

			if (empty($viewilp)) print_error('usernotenrolled','block_ilp');
		} 
	}
	
}

?>