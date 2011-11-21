<?php

class ilp_cron	{
	
	public 		$dbc;
	
	
	function __construct()	{
        // include the assmgr db
        require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

        // instantiate the assmgr db
        $this->dbc = new ilp_db();	
	}
	
	
	function get_list($lowertimestamp,$uppertimestamp)	{
		return	$this->dbc->get_reports_in_period($lowertimestamp,$uppertimestamp);
	}
	
	
	function run_cron()	{	
		
		$notificationdays	=	get_config('block_ilp','deadlinenotification');
		
		if (!empty($notificationdays)) {
			$lowertimestamp		=	mktime(0,0,0,date('n'),date('j')+$notificationdays);
			$uppertimestamp		=	mktime(23,59,59,date('n'),date('j')+$notificationdays);
			
			$reports	=	$this->get_list($lowertimestamp,$uppertimestamp);
			
			foreach ($reports as $r) {
				
				$user	=	$this->dbc->get_user($r->user_id);
				
				$email	=	new stdClass();
				$email->reportname		=	$r->name;
				$email->firsttname		=	$user->firstname;
				$email->lasttname		=	$user->lastname;
				$email->deadline		=	userdate($r->deadline);
				
				$subject		=	get_string('cronemailsubject','block_ilp',$email);
				$messagetext	=	get_string('cronemailhtml','block_ilp',$email);
				email_to_user($user, get_string('cronemailsender','block_ilp'), $subject, $messagetext);
			}
		}
	}
}


?>