<?php

class ilp_cron	{
	
	public 		$dbc;
	
	
	function __construct()	{
		global	$CFG;
		
        // include the assmgr db
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the assmgr db
        $this->dbc = new ilp_db();	
	}
	
	
	function get_list($lowertimestamp,$uppertimestamp)	{
		return	$this->dbc->get_reports_in_period($lowertimestamp,$uppertimestamp);
	}
	
	
	function run()	{	
		
		$notificationdays	=	get_config('block_ilp','deadlinenotification');
		
		if (!empty($notificationdays)) {
			$lowertimestamp		=	mktime(0,0,0,date('n'),date('j')+$notificationdays);
			$uppertimestamp		=	mktime(23,59,59,date('n'),date('j')+$notificationdays);
			
			$reportentries	=	$this->get_list($lowertimestamp,$uppertimestamp);
			mtrace( "running cron" );
			
			
			
			
			foreach ($reportentries as $r) {
				mtrace( $r->name);
				
				$user	=	$this->dbc->get_user_by_id($r->user_id);
				mtrace( $user->firstname);
				$email	=	new stdClass();
				$email->reportname		=	$r->name;
				$email->firsttname		=	$user->firstname;
				$email->lasttname		=	$user->lastname;
				$email->deadline		=	userdate($r->deadline);
				
				$subject		=	get_string('cronemailsubject','block_ilp',$email);
				$messagetext	=	get_string('cronemailhtml','block_ilp',$email);
				$sent = email_to_user($user, get_string('cronemailsender','block_ilp'), $subject, $messagetext);

                if($sent==true){
                $updateemail =	new stdClass();
                $updateemail->id =  $r->id;
                $updateemail->emailsent = 1;
                $this->dbc->update_emailsent_status($updateemail);
                }
			}
		}
	}
}


?>