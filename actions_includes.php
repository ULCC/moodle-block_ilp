<?php

//include the access checks file
require_once($CFG->dirroot.'/blocks/ilp/db/accesscheck.php');

if ($USER->id != $user_id ) {
	
	//we only require the viewotherilp capabilty id the user is not a ilp admin
	if (empty($access_ilp_admin)) require_capability('block/ilp:viewotherilp', $context);
	
	if (!empty($course_id))	{

		$currentcoursecontext	=	context_course::instance($course_id);
		
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