<?php 

//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');

class ilp_dashboard_graph_tab  extends ilp_dashboard_tab {
	
	public		$student_id;
	public		$course_id;
	public 		$filepath;
	public		$linkurl;
	public 		$selectedtab;

		
	
	function __construct($student_id=null,$course_id=NULL)	{
		global 	$CFG;
		
		$this->linkurl				=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id=".$student_id."&course_id={$course_id}";		
		
		$this->student_id	=	$student_id;
		$this->course_id	=	$course_id;
		$this->filepath		=	$CFG->dirroot."/blocks/ilp/plugins/tabs/entries/overview.php";

		
		//set the id of the tab that will be displayed first as default
		$this->default_tab_id	=	$this->plugin_id.'-1';
		
		//call the parent constructor
		parent::__construct();
	}
	
	/**
	 * Return the text to be displayed on the tab
	 */
	function display_name()	{
		return	get_string('ilp_dashboard_graph_tab_name','block_ilp');
	}
	
    function define_second_row()	{
    	global $CFG,$PARSER,$PAGE,$USER;
    	
    	
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table 
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION 
		if (!empty($this->plugin_id)) {


            /****
             * This code is in place as moodle insists on calling the settings functions on normal pages
             *
             */
            //check if the set_context method exists
            if (!isset($PAGE->context) === false) {

                $course_id = (is_object($PARSER)) ? $PARSER->optional_param('course_id', SITEID, PARAM_INT)  : SITEID;
                $user_id = (is_object($PARSER)) ? $PARSER->optional_param('user_id', $USER->id, PARAM_INT)  : $USER->id;

                if ($course_id != SITEID && !empty($course_id))	{
                    if (method_exists($PAGE,'set_context')) {
                        //check if the siteid has been set if not
                        $PAGE->set_context(context_course::instance(CONTEXT_COURSE,$course_id));
                    }	else {
                        $PAGE->context = context_course::instance(CONTEXT_COURSE,$course_id);
                    }
                } else {
                    if (method_exists($PAGE,'set_context')) {
                        //check if the siteid has been set if not
                        $PAGE->set_context(context_course::instance(CONTEXT_USER,$user_id));
                    }	else {
                        $PAGE->context = context_course::instance(CONTEXT_USER,$user_id);
                    }
                }
            }


            //get all of the users roles in the current context and save the id of the roles into
            //an array
            $role_ids	=	 array();

            $authuserrole	=	$this->dbc->get_role_by_id($CFG->defaultuserroleid);
            if (!empty($authuserrole)) $role_ids[]	=	$authuserrole->id;



            //TODO: strange but isset does not seem to work correctly in moodle 2.0
            //it doesn't return true when testing for $PAGE->context even when it is set
            //so I will do different tests depending on moodle version

            $contextset = false;

            $contextset	=	(!is_null($PAGE->context)) ? true : false;

            if (!empty($contextset))	{

                if ($roles = get_user_roles($PAGE->context, $USER->id)) {
                    foreach ($roles as $role) {
                        $role_ids[]	= $role->roleid;
                    }
                }

                $capability	=	$this->dbc->get_capability_by_name('block/ilp:viewreport');

                $this->secondrow	=	array();

                //get all reports
                $reports	=	$this->dbc->get_reports_by_position(null,null,false);



                if (!empty($reports)) {
                    //create a tab for each enabled report
                    foreach($reports as $r)	{
                        if ($this->dbc->has_report_permission($r->id,$role_ids,$capability->id)) {
                            //get all graphs attached to this report
                            $reportgraph    =   $this->dbc->get_report_graphs($r->id);

                            if (!empty($reportgraph)) {
                                //the tabitem and selectedtab query string params are added to the linkurl in the
                                //second_row() function
                                foreach ($reportgraph   as  $rg)  {
                                    $this->secondrow[]	=	array('id'=>$r->id.":".$rg->id,'link'=>$this->linkurl,'name'=>$rg->name);
                                }
                            }
                        }

                    }
                }
            }

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
		global 	$CFG,$PARSER,$PAGE, $USER;



		$pluginoutput	=	"";
		
		//get the selecttab param if has been set
		$this->selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

		//get the tabitem param if has been set
		$this->tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_INT);
		
		//split the selected tab id on up 3 ':'
		$seltab	=	explode(':',$selectedtab);
					
		//if the seltab is empty then the highest level tab has been selected
		if (empty($seltab))	$seltab	=	array($selectedtab);

        $report_id	= (!empty($seltab[1])) ? $seltab[1] : $this->default_tab_id ;
        $reportgraph_id	= (!empty($seltab[2])) ? $seltab[2] : 0 ;



        //find out whether this user has the correct permissions to view the current graph

        /****
         * This code is in place as moodle insists on calling the settings functions on normal pages
         *
         */
        //check if the set_context method exists
        if (!isset($PAGE->context) === false) {

            $course_id = (is_object($PARSER)) ? $PARSER->optional_param('course_id', SITEID, PARAM_INT)  : SITEID;
            $user_id = (is_object($PARSER)) ? $PARSER->optional_param('user_id', $USER->id, PARAM_INT)  : $USER->id;

            if ($course_id != SITEID && !empty($course_id))	{
                //check if the siteid has been set if not
                $PAGE->set_context(context_course::instance($course_id));
            } else {
                $PAGE->set_context(context_user::instance($user_id));
            }
        }

        //get all of the users roles in the current context and save the id of the roles into
        //an array
        $role_ids	=	 array();

        $authuserrole	=	$this->dbc->get_role_by_id($CFG->defaultuserroleid);
        if (!empty($authuserrole)) $role_ids[]	=	$authuserrole->id;



        //TODO: strange but isset does not seem to work correctly in moodle 2.0
        //it doesn't return true when testing for $PAGE->context even when it is set
        //so I will do different tests depending on moodle version

        $contextset = false;

        $contextset	=	(!is_null($PAGE->context)) ? true : false;


        if (!empty($contextset))	{

            if ($roles = get_user_roles($PAGE->context, $USER->id)) {
                foreach ($roles as $role) {
                    $role_ids[]	= $role->roleid;
                }
            }

            $capability	=	$this->dbc->get_capability_by_name('block/ilp:viewreport');

            //check if the user has permission to view the report that the graph is derived from
            if ($this->dbc->has_report_permission($report_id,$role_ids,$capability->id)) {

                if ($this->dbc->get_user_by_id($this->student_id) && !empty($reportgraph_id)) {

                    $user	=	$this->dbc->get_user_by_id($this->student_id);

                    $rg     =   $this->dbc->get_report_graph_data($reportgraph_id);

                    $graphplugin    =   $this->dbc->get_graph_plugin_by_id($rg->plugin_id);

                    $classname      =   $graphplugin->name;

                    //start buffering output
                    ob_start();

                    require_once($CFG->dirroot."/blocks/ilp/plugins/graph/{$classname}.php");

                    $graph  =   new $classname();

                    $graph->display($this->student_id,$report_id,$reportgraph_id);

                    //pass the output instead to the output var
                    $pluginoutput = ob_get_contents();

                    ob_end_clean();

                } else if (!empty($reportgraph_id)) {
                    $pluginoutput	=	get_string('studentnotfound','block_ilp');
                } else {
                    $pluginoutput	=	get_string('selectagraph','block_ilp');
                }


            } else {
                $pluginoutput	=	get_string('nopermissiongraph','block_ilp');
            }



        }













					
		
		return $pluginoutput;
	}

	/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {
        $string['ilp_dashboard_graph_tab'] 					= 'Graph plugins test';
        $string['ilp_dashboard_graph_tab_name'] 				= 'Graph Plugin';
        
        return $string;
    }
	
	
	
	
	
}

?>