<?php

//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');

require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_report_rules.class.php');

class ilp_dashboard_entries_tab extends ilp_dashboard_tab {

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
		return	get_string('ilp_dashboard_entries_tab_name','block_ilp');
	}

    /**
     * Override this to define the second tab row should be defined in this function
     */
    function define_second_row()	{
    	//if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table
		//as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION
		if (!empty($this->plugin_id)) {
			$this->secondrow	=	array();

			//NOTE names of tabs can not be get_string as this causes a nesting error
			$this->secondrow[]	=	array('id'=>'1','link'=>$this->linkurl,'name'=>'Overview');
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
		global 	$CFG,$PAGE,$USER, $PARSER;


		$pluginoutput	=	"";

        //get the selecttab param if has been set
        $this->selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

        //get the tabitem param if has been set
        $this->tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_INT);

        //split the selected tab id on up 3 ':'
        $seltab	=	explode(':',$selectedtab);

        //if the seltab is empty then the highest level tab has been selected
        if (empty($seltab))	$seltab	=	array($selectedtab);

        $taboption	= (!empty($seltab[1])) ? $seltab[1] : 1;


		if ($this->dbc->get_user_by_id($this->student_id)) {

				//start buffering output
				ob_start();

                switch ($taboption) {
                    case    1:
                        $this->reportsoverview();
                        break;

                    default:
                        $this->reportsoverview();
                }





				//pass the output instead to the output var
				$pluginoutput = ob_get_contents();

				ob_end_clean();

			} else {
				$pluginoutput	=	get_string('studentnotfound','block_ilp');
			}


			return $pluginoutput;
	}

    /**
     * Displays a overview of report entry data for this user
     */
    public  function reportsoverview()  {

        global 	$CFG,$PAGE,$USER;

        //we will use this to find out if the reports tab is installed if it is the reportname will be a link
        $reporttab		=	$this->dbc->get_plugin_record_by_classname('block_ilp_dash_tab','ilp_dashboard_reports_tab');

        //get all enabled reports in this ilp
        $reports		=	ilp_report::get_enabledreports();
        $reportslist	=	array();
        if (!empty($reports)) {

            //cycle through all reports and save the relevant details
            foreach ($reports	as $r) {

                if ($r->vault == 1 || !$r->has_cap($USER->id,$PAGE->context,'block/ilp:viewreport')) {
                    continue;
                }

                $canviewreport		=	true;

                $caneditreport		=	$r->has_cap($USER->id,$PAGE->context,'block/ilp:editreport');

                $canaddreport		=	$r->has_cap($USER->id,$PAGE->context,'block/ilp:addreport');

                $canviewothersreports		=	$r->has_cap($USER->id,$PAGE->context,'block/ilp:viewotherilp');

                $canaddviewextreport		=	$r->has_cap($USER->id,$PAGE->context,'block/ilp:viewextension');

                if (!empty($caneditreport) || !empty($canaddreport) || !empty($canviewreport) ) {

                    $detail					=	new stdClass();
                    $detail->report_id		=	$r->id;

                    $detail->name			=	(empty($reporttab)) ? $r->name : "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$this->student_id}&course_id={$this->course_id}&tabitem={$reporttab->id}:{$r->id}&selectedtab={$reporttab->id}'>{$r->name}</a>";

                    $binary_icon				=	(!empty($r->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report_id=".$r->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";

                    $detail->icon 	=	 "<img id='reporticon' class='icon_small' alt='$r->name ".get_string('reports','block_ilp')."' src='$binary_icon' />";

                    //does this report have a state field

                    //get all entries for this student in report
                    $detail->entries		=	($this->dbc->count_report_entries($r->id,$this->student_id)) ? $this->dbc->count_report_entries($r->id,$this->student_id) : 0;
                    $detail->state_report	=	false;

                    if ($this->dbc->has_plugin_field($r->id,'ilp_element_plugin_state')) {
                        //get the number of entries achieved
                        $detail->achieved	=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_STATE_PASS);

                        //get number of entries with notcounted state and minus this from the number of entries
                        $detail->notcounted	=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_STATE_NOTCOUNTED);
                        $detail->entries    = $detail->entries - $detail->notcounted;
                        $detail->state_report	=	true;
                    }

                    $res = $this->dbc->has_plugin_field($r->id,'ilp_element_plugin_date_deadline');
                    if ($res) {

                        $inprogressentries	=	$this->dbc->count_report_entries_with_state($r->id,$this->student_id,ILP_STATE_UNSET,false);
                        $inprogentries 		=	array();

                        if (!empty($inprogressentries)) {
                            foreach ($inprogressentries as $e) {
                                $inprogentries[]	=	$e->id;
                            }
                        }

                        //get the number of entries that are overdue
                        $detail->overdue			=	$this->dbc->count_overdue_report($r->id,$this->student_id,$inprogentries,time());
                        $detail->deadline_report	=	true;
                    }

                    $reportrules    =   new ilp_report_rules($r->id,$this->student_id);

                    //get the last updated report entry
                    $lastentry				=	$this->dbc->get_lastupdatedentry($r->id,$this->student_id);
                    $lastentry              =   reset($lastentry);
                    $lastupdate				=	$this->dbc->get_lastupdatetime($r->id,$this->student_id,false);

                    $detail->frequency		=	$r->frequency;

                    //if the report does not allow mutiple entries (frequency is empty)
                    //then we need to find a report entry instance this will be editable
                    $detail->editentry	=	(empty($detail->frequency) && !empty($lastentry)) ?  $lastentry->id : false;

                    //when was the last entry created for this report
                    $detail->lastmod	=	(!empty($lastupdate->timemodified)) ?  userdate($lastupdate->timemodified , get_string('strftimedate', 'langconfig')) : get_string('notapplicable','block_ilp');

                    //does the user have the capabiltilty to add a report
                    $detail->canadd	    = (!empty($canaddreport)) ? true : false;

                    //is the report available to the user
                    $detail->reportavailable    =   $reportrules->report_availabilty();

                   if( !empty($canaddviewextreport)){
                    $detail->addextension = $reportrules->can_add_extensions();
                   }

                    $detail->canedit	= ($caneditreport) ? true : false;

                    $reportgraphs   =   $this->dbc->get_report_graphs($r->id);

                    $reportgraphstatus  =   get_config('block_ilp','ilp_dashboard_entries_tab_graphstatus');

                    //we will use this to find out if the graph tab is installed if it is the reportname will be a link
                    $graphtab		=	$this->dbc->get_plugin_record_by_classname('block_ilp_dash_tab','ilp_dashboard_graph_tab');


                    $detail->reportgraphs   =   array();

                    if (!empty($reportgraphstatus))   {
                        if (!empty($reportgraphs)) {
                            foreach ($reportgraphs as $rg)   {

                                $reportgraph     =   $this->dbc->get_report_graph_data($rg->id);

                                $graphplugin    =   $this->dbc->get_graph_plugin_by_id($reportgraph->plugin_id);

                                $classname      =   $graphplugin->name;

                                $additionalcontent  =   "";

                                //start buffering output
                                ob_start();

                                require_once($CFG->dirroot."/blocks/ilp/plugins/graph/{$classname}.php");

                                $graph  =   new $classname();

                                switch($reportgraphstatus)    {

                                    case  ILP_DISPLAYGRAPHTHUMBS:
                                        $element =  $graph->display($this->student_id,$r->id,$rg->id,'small',true);
                                        break;

                                    case  ILP_DISPLAYGRAPHLINKS:
                                        $element            =   $reportgraph->name;
                                        $additionalcontent  =   "<br />";
                                         break;

                                    case  ILP_DISPLAYGRAPHICON:
                                        $element   =   $graph->icon();
                                        break;
                                }

                                $detail->reportgraphs[]   = (!empty($graphtab)) ? "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$this->student_id}&course_id={$this->course_id}&tabitem={$graphtab->id}:{$r->id}:{$rg->id}&selectedtab={$graphtab->id}'>{$element}</a>".$additionalcontent  :  $element.$additionalcontent  ;
                            }
                        }
                    }
                    $reportslist[]			=	$detail;
                }
            }
        }

        //we need to buffer output to prevent it being sent straight to screen
        require_once($CFG->dirroot.'/blocks/ilp/plugins/tabs/ilp_dashboard_entries_tab.html');
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

        $options = array();

        $options = array(
            ILP_DISABLED => get_string('disabled','block_ilp'),
            ILP_DISPLAYGRAPHTHUMBS   =>     get_string('ilp_dashboard_entries_tab_displaythumbs','block_ilp'),
            ILP_DISPLAYGRAPHLINKS    =>     get_string('ilp_dashboard_entries_tab_displaylinks','block_ilp'),
            ILP_DISPLAYGRAPHICON     =>     get_string('ilp_dashboard_entries_tab_displayicons','block_ilp'),
        );

        $this->config_select_element($mform,'ilp_dashboard_entries_tab_graphstatus',$options,get_string('ilp_dashboard_reports_tab_graphstatus', 'block_ilp'),get_string('ilp_dashboard_reports_tab_graphstatusdesc', 'block_ilp'),0);

        $options = array(
            ILP_ENABLED => get_string('enabled','block_ilp'),
            ILP_DISABLED => get_string('disabled','block_ilp')
        );

        $this->config_select_element($mform,$classname.'_pluginstatus',$options,get_string($classname.'_name', 'block_ilp'),get_string('tabstatusdesc', 'block_ilp'),0);

    }
	
		/**
	 * Adds the string values from the tab to the language file
	 *
	 * @param	array &$string the language strings array passed by reference so we  
	 * just need to simply add the plugins entries on to it
	 */
	 static function language_strings(&$string) {
        $string['ilp_dashboard_reports_tab_graphstatus'] 					= 'Entries Display Type';
        $string['ilp_dashboard_reports_tab_graphstatusdesc'] 				= 'How do you want to display the entries on the tab.';
        
        return $string;
    }
	

}
