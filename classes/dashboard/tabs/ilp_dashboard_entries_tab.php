<?php

//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/ilp_dashboard_tab.php');

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
		$this->filepath		=	$CFG->dirroot."/blocks/ilp/classes/dashboard/tabs/entries/overview.php";


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
		global 	$CFG,$PAGE,$USER;


		$pluginoutput	=	"";

		if ($this->dbc->get_user_by_id($this->student_id)) {

				//start buffering output
				ob_start();
					//we will use this to find out if the reports tab is installed if it is the reportname will be a link
					$reporttab		=	$this->dbc->get_plugin_record_by_classname('block_ilp_dash_tab','ilp_dashboard_reports_tab');

					//get all enabled reports in this ilp
					$reports		=	$this->dbc->get_reports_by_position(null,null,false);
					$reportslist	=	array();
					if (!empty($reports)) {

						$role_ids	=	ilp_get_user_role_ids($PAGE->context,$USER->id);
						$authuserrole	=	$this->dbc->get_role_by_name(ILP_AUTH_USER_ROLE);
						if (!empty($authuserrole)) $role_ids[]	=	$authuserrole->id;

						//cycle through all reports and save the relevant details
						foreach ($reports	as $r) {

							$addcapability		=	$this->dbc->get_capability_by_name('block/ilp:addreport');

							$editcapability		=	$this->dbc->get_capability_by_name('block/ilp:editreport');

							$viewcapability		=	$this->dbc->get_capability_by_name('block/ilp:viewreport');

							$caneditreport		=	$this->dbc->has_report_permission($r->id,$role_ids,$editcapability->id);

							$canaddreport		=	$this->dbc->has_report_permission($r->id,$role_ids,$addcapability->id);

							$canviewreport		=	$this->dbc->has_report_permission($r->id,$role_ids,$viewcapability->id);

							if (!empty($caneditreport) || !empty($canaddreport) || !empty($canviewreport)) {

								$detail					=	new stdClass();
								$detail->report_id		=	$r->id;


								$detail->name			=	(empty($reporttab)) ? $r->name : "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_main.php?user_id={$this->student_id}&course_id={$this->course_id}&tabitem={$reporttab->id}:{$r->id}&selectedtab={$reporttab->id}'>{$r->name}</a>";

								$binary_icon				=	(!empty($r->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report_id=".$r->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";

								$detail->icon 	=	 "<img id='reporticon' class='icon_small' alt='$r->name ".get_string('reports','block_ilp')."' src='$binary_icon' />";

								//does this report have a state field

								//get all entries for this student in report
                                $detail->entries		=	($this->dbc->count_report_entries($r->id,$this->student_id)) ? $this->dbc->count_report_entries($r->id,$this->student_id) : 0;
                                $detail->state_report	=	false;

								$res = $this->dbc->has_plugin_field($r->id,'ilp_element_plugin_state');
								if ($res) {
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

								//get the last updated report entry
                                $lastentry				=	$this->dbc->get_lastupdatedentry($r->id,$this->student_id);
                                $lastupdate				=	$this->dbc->get_lastupdatetime($r->id,$this->student_id);

								$detail->frequency		=	$r->frequency;

								//if the report does not allow mutiple entries (frequency is empty)
								//then we need to find a report entry instance this will be editable
								$detail->editentry	=	(empty($detail->frequency) && !empty($lastentry)) ?  $lastentry->id : false;

								$detail->lastmod	=	(!empty($lastupdate->timemodified)) ?  userdate($lastupdate->timemodified , get_string('strftimedate', 'langconfig')) : get_string('notapplicable','block_ilp');

								$detail->canadd	    = ($canaddreport) ? true : false;

								$detail->canedit	= ($caneditreport) ? true : false;

								$reportslist[]			=	$detail;
							}
						}
					}




					//we need to buffer output to prevent it being sent straight to screen

					require_once($CFG->dirroot.'/blocks/ilp/classes/dashboard/tabs/ilp_dashboard_entries_tab.html');


				//pass the output instead to the output var
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
        $string['ilp_dashboard_entries_tab'] 					= 'entries tab';
        $string['ilp_dashboard_entries_tab_name'] 				= 'Entries';
        $string['ilp_dashboard_entries_tab_overview'] 			= 'Overview';
        $string['ilp_dashboard_entries_tab_lastupdate'] 		= 'Last Update';

        return $string;
    }





}
