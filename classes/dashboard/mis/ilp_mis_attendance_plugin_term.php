<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_mis_attendance_plugin.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_mis_ajax_table.class.php');

class ilp_mis_attendance_plugin_term extends ilp_mis_attendance_plugin{

	public 	$fields;
	public	$termdata;
	public	$courselist;
	
	
    protected $monthlist = array();

    public function __construct( $params=array() ) {
        parent::__construct( $params );
        
        $this->termdata		=	false;
        $this->courselist	= 	false;
        $this->tabletype	=	get_config('block_ilp','mis_plugin_term_tabletype');
       
    }

    /*
    * display the current state of $this->data
    */
    public function display(){
       
    	if (!empty($this->termdata)) {
    		
    		$sixtermformat	=	get_config('block_ilp','mis_plugin_term_termformat');
    		
    		//set up the flexible table for displaying

	        //instantiate the ilp_ajax_table class
	        $flextable = new ilp_mis_ajax_table( 'monthly_breakdown',true ,'ilp_mis_attendance_plugin_term');
	
	        //setup the headers and columns with the fields that have been requested 

	        $headers		=	array();
	        $columns		=	array();

	        $headers[]		=	'';
			$headers[]		=	get_string('ilp_mis_attendance_plugin_term_overall','block_ilp');
			$headers[]		=	get_string('ilp_mis_attendance_plugin_term_termone','block_ilp');
			$headers[]		=	get_string('ilp_mis_attendance_plugin_term_termtwo','block_ilp');
			$headers[]		=	get_string('ilp_mis_attendance_plugin_term_termthree','block_ilp');
			
    		if (!empty($sixtermformat)) {
				$headers[]		=	get_string('ilp_mis_attendance_plugin_term_termfour','block_ilp');
				$headers[]		=	get_string('ilp_mis_attendance_plugin_term_termfive','block_ilp');
				$headers[]		=	get_string('ilp_mis_attendance_plugin_term_termsix','block_ilp');
			}
					
	        $columns[]		=	'metric';
	        $columns[]		=	'overall';
	        $columns[]		=	'one';
	        $columns[]		=	'two';
	        $columns[]		=	'three';
	        
    		if (!empty($sixtermformat)) {
				$columns[]	=	'four';
	        	$columns[]	=	'five';
	        	$columns[]	=	'six';
			}
	        
	        //define the columns in the tables
	        $flextable->define_columns($columns);
	        
	        //define the headers in the tables
	        $flextable->define_headers($headers);
	        
	        //we do not need the intialbars
	        $flextable->initialbars(false);
	        
	        $flextable->set_attribute('class', 'flexible generaltable');
	        
	        //setup the flextable
	        $flextable->setup();
	        
	        
    		$terms	=	(empty($sixtermformat)) ? 4 : 7;
	        
	        foreach( $this->termdata as $metric )	{

	        	$data['metric']		=	$metric['name'];
	        	$data['overall']	=	$metric['overall'].'%';
	        	$data['one']		=	$metric[1].'%';
	        	$data['two']		=	$metric[2].'%';
	        	$data['three']		=	$metric[3].'%';

	            if (!empty($sixtermformat)) {
					$data['four']	=	$metric[4].'%';
	        		$data['five']	=	$metric[5].'%';
	        		$data['six']	=	$metric[6].'%';
				}
	        	$flextable->add_data_keyed( $data );
	        }
	        
			ob_start();
	        $flextable->print_html();
			$pluginoutput = ob_get_contents();
	        ob_end_clean();
	        
	        return $pluginoutput;
    	} else {
    		echo '<div id="plugin_nodata">'.get_string('nodataornoconfig','block_ilp').'</div>';
    	}
    }

    
	/**
     * Adds settings for this plugin to the admin settings
     * @see ilp_mis_plugin::config_settings()
     */
    public function config_settings(&$settings)	{
    	global $CFG;
    	
    	$link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_attendance_plugin_term&plugintype=mis">'.get_string('ilp_mis_attendance_plugin_term_pluginnamesettings', 'block_ilp').'</a>';
		$settings->add(new admin_setting_heading('block_ilp_mis_attendance_plugin_term', '', $link));
 	 }
    
 	  	 /**
 	  * Adds config settings for the plugin to the given mform
 	  * @see ilp_plugin::config_form()
 	  */
 	 function config_form(&$mform)	{
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_term_table',get_string('ilp_mis_attendance_plugin_term_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_tabledesc', 'block_ilp'),'');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_term_studentidfield',get_string('ilp_mis_attendance_plugin_term_studentidfield', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_studentidfielddesc', 'block_ilp'),'studentID');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_term_term',get_string('ilp_mis_attendance_plugin_term_term', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_termdesc', 'block_ilp'),'term');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_term_markstotalfield',get_string('ilp_mis_attendance_plugin_term_markstotal', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_markstotaldesc', 'block_ilp'),'marksTotal');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_term_markspresentfield',get_string('ilp_mis_attendance_plugin_term_markspresent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_markspresentdesc', 'block_ilp'),'marksPresent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_term_marksabsentfield',get_string('ilp_mis_attendance_plugin_term_marksabsent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_marksabsentdesc', 'block_ilp'),'marksAbsent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_term_marksauthabsentfield',get_string('ilp_mis_attendance_plugin_term_marksauthabsent', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_marksauthabsentdesc', 'block_ilp'),'marksAuthAbsent');
 	 	
 	 	$this->config_text_element($mform,'mis_plugin_term_markslatefield',get_string('ilp_mis_attendance_plugin_term_markslate', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_markslatedesc', 'block_ilp'),'marksLate');
 	 	
 	 	
 	 	$options = array(
    		 0 => get_string('ilp_mis_attendance_plugin_term_threeterms','block_ilp'),
    		 1 => get_string('ilp_mis_attendance_plugin_term_sixterms','block_ilp'),
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_plugin_term_termformat',$options,get_string('ilp_mis_attendance_plugin_term_termformat', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_termformatdesc', 'block_ilp'),9);
 	 	
    	$options = array(
    		 0 => get_string('ilp_mis_attendance_plugin_term_ignore','block_ilp'),
    		 1 => get_string('ilp_mis_attendance_plugin_term_positive','block_ilp'),
    		 2 => get_string('ilp_mis_attendance_plugin_term_negative','block_ilp'), 
    	);
    	
 	 	$this->config_select_element($mform,'mis_plugin_term_authorised',$options,get_string('ilp_mis_attendance_plugin_term_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_tabledesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
    		 ILP_IDTYPE_INT		=> get_string('intid','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_plugin_term_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);
 	 	 	 	
 	 	
 	 	$options = array(
    		 ILP_MIS_TABLE => get_string('table','block_ilp'),
    		 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp') 
    	);
 	 	
 	 	$this->config_select_element($mform,'mis_plugin_term_tabletype',$options,get_string('ilp_mis_attendance_plugin_term_table', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_tabledesc', 'block_ilp'),1);
 	 	
 	 	$options = array(
    		ILP_ENABLED => get_string('enabled','block_ilp'),
    		ILP_DISABLED => get_string('disabled','block_ilp')
    	);
 	
 	 	$this->config_select_element($mform,'ilp_mis_attendance_plugin_term_pluginstatus',$options,get_string('ilp_mis_attendance_plugin_term_pluginstatus', 'block_ilp'),get_string('ilp_mis_attendance_plugin_term_pluginstatusdesc', 'block_ilp'),0);
 	 	
 	 }
    

        
    public function plugin_type(){
        return 'overview';
    }
    
	function language_strings(&$string) {
        $string['ilp_mis_attendance_plugin_term_pluginname']		  			= 'Term attendance overview';
        $string['ilp_mis_attendance_plugin_term_pluginnamesettings']		  	= 'Term attendance configuration';
        
        
        $string['ilp_mis_attendance_plugin_term_table']		  			= 'Term table';
        $string['ilp_mis_attendance_plugin_term_tabledesc']		  		= 'table containing overview of student attendence by course by term';
        
        $string[ 'ilp_mis_attendance_plugin_term_studentidfield']   		= 'Student id field';
        $string[ 'ilp_mis_attendance_plugin_term_studentidfielddesc']  	= 'The field containing the mis user id';
        
        $string[ 'ilp_mis_attendance_plugin_term_term' ]		   		= 'Term field';
        $string[ 'ilp_mis_attendance_plugin_term_termdesc' ]   			= 'The field containing the the term the data pertain too';
        
        $string[ 'ilp_mis_attendance_plugin_term_monthorderfield' ]  	= 'Month order field';
        $string[ 'ilp_mis_attendance_plugin_term_monthorderfielddesc' ]  = 'The field containing the month order';
        
        $string[ 'ilp_mis_attendance_plugin_term_markstotal' ]   		= 'Marks total field';
        $string[ 'ilp_mis_attendance_plugin_term_markstotaldesc' ]   	= 'The field containing marks total data';
        
        
        $string[ 'ilp_mis_attendance_plugin_term_markspresent' ]   		= 'marks present field';
        $string[ 'ilp_mis_attendance_plugin_term_markspresentdesc' ]   	= 'The field containing the marks present data';
        
        $string[ 'ilp_mis_attendance_plugin_term_marksabsent' ]   		= 'marks absent field';
        $string[ 'ilp_mis_attendance_plugin_term_marksabsentdesc' ]   	= 'The field containing the absents data';
        
        $string[ 'ilp_mis_attendance_plugin_term_marksauthabsent' ] 	 	= 'marks authabsent field';
        $string[ 'ilp_mis_attendance_plugin_term_marksauthabsentdesc' ]  = 'the field containing the authorised absents data';
        
        $string[ 'ilp_mis_attendance_plugin_term_markslate' ]   			= 'marks late field';
        $string[ 'ilp_mis_attendance_plugin_term_markslatedesc' ]   		= 'the field containing the marks late data';
        
        $string[ 'ilp_mis_attendance_plugin_term_authorised' ]   			= 'Authorised Absents';
        $string[ 'ilp_mis_attendance_plugin_term_authoriseddesc' ]   		= 'What should be done with authorised absents? Positive - to add to present marks, Negative - to add to absents and ignore to not count';
        
        $string[ 'ilp_mis_attendance_plugin_term_ignore' ]  				= 'Ignore';
        $string[ 'ilp_mis_attendance_plugin_term_positive' ]   			= 'Positive';
        $string[ 'ilp_mis_attendance_plugin_term_negative' ]   			= 'Negative';
        
        $string[ 'ilp_mis_attendance_plugin_term_pluginstatus' ]   			= 'Status';
        $string[ 'ilp_mis_attendance_plugin_term_pluginstatusdesc' ]   		= 'is the plugin enabled or disabled';
        
        $string[ 'ilp_mis_attendance_plugin_term_overall' ] 		  		= 'Overall';
        $string[ 'ilp_mis_attendance_plugin_term_termone' ]   				= 'Term 1';
        $string[ 'ilp_mis_attendance_plugin_term_termtwo' ]   				= 'Term 2';
        $string[ 'ilp_mis_attendance_plugin_term_termthree' ]  				= 'Term 3';
        $string[ 'ilp_mis_attendance_plugin_term_termfour' ]   				= 'Term 4';
        $string[ 'ilp_mis_attendance_plugin_term_termfive' ]   				= 'Term 5';
        $string[ 'ilp_mis_attendance_plugin_term_termsix' ]   				= 'Term 6';
        
        $string[ 'ilp_mis_attendance_plugin_term_threeterms' ]   				= '3 Terms';
        $string[ 'ilp_mis_attendance_plugin_term_sixterms' ]   					= '6 Terms';
        
        $string[ 'ilp_mis_attendance_plugin_term_termformat' ]   					= 'Term Format';
        $string[ 'ilp_mis_attendance_plugin_term_termformatdesc' ]   				= 'How many terms are there';
        
        
        
        
    }

    
    /**
     * Retrieves user data from the mis database
     * 
     * @param $mis_user_id the mis id of the user whose data will be retireved.
     */
    function set_data( $mis_user_id ) {
    	$table 		=		get_config( 'block_ilp', 'mis_plugin_term_table'  );
    	
    	$this->mis_user_id	=	$mis_user_id;

    	
    	if (!empty($table)) {
    		
    		$sidfield	=	get_config('block_ilp','mis_plugin_term_studentidfield');
    		
    		//is the id a string or a int
    		$idtype	=	get_config('block_ilp','mis_plugin_term_idtype');
    		$mis_user_id	=	(empty($idtype)) ? "'{$mis_user_id}'" : $mis_user_id;
    		
    		//create the key that will be used in sql query
    		$keyfields	=	array($sidfield	=> array('=' => $mis_user_id));
    		
    		$this->fields		=	array();
    		
    		//get all of the fields that will be returned
    		if 	(get_config('block_ilp','mis_plugin_term_term')) 				$this->fields['term']		=	get_config('block_ilp','mis_plugin_term_term');
    		if 	(get_config('block_ilp','mis_plugin_term_markstotalfield')) 	$this->fields['markstotal']	=	get_config('block_ilp','mis_plugin_term_markstotalfield');
    		if 	(get_config('block_ilp','mis_plugin_term_markspresentfield')) 	$this->fields['markspresent']	=	get_config('block_ilp','mis_plugin_term_markspresentfield');
    		if 	(get_config('block_ilp','mis_plugin_term_marksabsentfield')) 	$this->fields['marksabsent']	=	get_config('block_ilp','mis_plugin_term_marksabsentfield');
    		if 	(get_config('block_ilp','mis_plugin_term_marksauthabsentfield')) 	$this->fields['marksauthabsent']	=	get_config('block_ilp','mis_plugin_term_marksauthabsentfield');
    		if 	(get_config('block_ilp','mis_plugin_term_markslatefield')) 	$this->fields['markslate']	=	get_config('block_ilp','mis_plugin_term_markslatefield');
    		
    		//get the users monthly attendance data
    		$this->data	=	$this->dbquery( $table, $keyfields, $this->fields);
    		
    		$this->normalise_data($this->data);
    	}
    }
    
    function normalise_data($data)	{
    	
    	$termdata		=	array();
    	if (!empty($data)) {
	    	foreach ($data as $d) {
	    		
	    		//get the id of the current course
	    		$termid	=	$d[$this->fields['term']];
	    		
	    		
	    		//check if an array position for the course exists 
	    		if (!isset($termdata[$termid])) {
	    			$termdata[$termid]	=	array();
	    		}
	    		
	    		
	    		//should authabsent not be counted as absent? and does this vary from site to site in which case a config option is needed
	    		$present	=	$this->presents_cal($d[$this->fields['markspresent']],$d[$this->fields['marksauthabsent']]);
	    		
	    		//caculate the months attendance percentage 
	    		$attendpercent 	=	($present / $d[$this->fields['markstotal']]) * 100;
	    		
	    		//caculate the months attendance percentage 
	    		$punctpercent 	=	($d[$this->fields['markslate']] / $present) * 100;
	    		$punctpercent	=	 100 - $punctpercent;
	    		
	    		//fill the couse month array position with percentage for the month
	    		$termdata[$termid]			=	array(
	    											  'attendance'			=>  $attendpercent,
	    											  'punctuality'			=>  $punctpercent, 
	    											  'markstotal'		=>	$d[$this->fields['markstotal']],
	    											  'markspresent'	=>	$d[$this->fields['markspresent']],
	    											  'marksabsent'		=>	$d[$this->fields['marksabsent']],
	    											  'marksauthabsent'	=>	$d[$this->fields['marksauthabsent']],
	    											  'markslate'		=>	$d[$this->fields['markslate']]);
	    	}
	
	    	$presents			=	0;
	    	$absents			=	0;
	    	$authabsents		=	0;
	    	$lates				=	0;
	    	
	    	//now we have all course data nicely in an array we can work the overall totals
	    	foreach ($termdata as &$term)	{
	    		$presents		+=	$term['markspresent'];
	    		$absents		+=	$term['marksabsent'];
	    		$authabsents	+=	$term['marksauthabsent'];
	    		$lates			+=	$term['markslate'];
	    	}
	
	    	
	    	$present		=	$this->presents_cal($presents,$authabsents);
	    	$presentpercent =	($absents	/	$present) * 100;
	    	$presentpercent		=	100 - $presentpercent;
	    	
	    	//overall late percentage is calculated by geting the percentage of lates and taking 
	    	//it away from 100 
	    	$latepercent	=	($lates	/	$present) * 100;
	    	$latepercent	=	100 - $latepercent;
	    	
	    	$termdata['overall']['attendance']			=	$presentpercent;
	    	$termdata['overall']['punctuality']			=	$latepercent;
	    	$termdata['overall']['marksabsent']			=	$absents;
	    	$termdata['overall']['marksauthabsent']		=	$authabsents;
	    	$termdata['overall']['markspresent']			=	$present;
	    	
	
			//in this piece of code the data is made ready to bne placed in the term table   	
	    	$keynames		=	array('attendance','punctuality');
	    	$newtermdata	=	array();
	    	foreach ($keynames as $key) {
		   		$newdata = array();
		   		$newdata['name']	=	$key;
		    	foreach ($termdata as $k => $v)	{ 
		    		$newdata[$k]	=	number_format($v[$key],0);	
		    	}
		    	$newtermdata[] = $newdata;
	    	}
	    	
	    	$this->termdata		=	$newtermdata;
    	} else {
    		$this->termdata		=	false;
    	} 
    	
   	
    	
    } 
    
    private function presents_cal($markspresent,$authabesent) {
  		
 	 		switch (get_config('block_ilp','mis_plugin_term_authorised')) {
    			
    			case 1 : 
    				//positive
					$present	=    $markspresent + $authabesent;				
    				break;
    				
    			case 2:
    				$present	=    $markspresent - $authabesent;
    				break;
    				
    			default:
    				$present	=	$markspresent;
    		}

    		
    	return $present;
  	} 
    
    
    
    
    
}
