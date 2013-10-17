<?php

//require the ilp_plugin.php class 
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');

class ilp_dashboard_vault_tab extends ilp_dashboard_tab {
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

        //$this->dbc	=	new ilp_archive_db();

    }

    /**
     * Return the text to be displayed on the tab
     */
    function display_name()	{
        return	get_string('ilp_dashboard_vault_tab_name','block_ilp');
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
     * this all code was copied from entry tab
     */
    public  function reportsoverview()  {

        global 	$CFG,$PAGE,$USER;

        // load custom javascript
        $module = array(
            'name'      => 'ilp_dashboard_vault_tab',
            'fullpath'  => '/blocks/ilp/plugins/tabs/ilp_dashboard_vault_tab.js',
            'requires'  => array('event','dom','node','io-form','anim-base','anim-xy','anim-easing','anim')
        );

        // js arguments
        $jsarguments = array();

        // initialise the js for the page
        $PAGE->requires->js_init_call('M.ilp_dashboard_vault_tab.init', $jsarguments, true, $module);

        //we will use this to find out if the reports tab is installed if it is the reportname will be a link
        $reporttab		=	$this->dbc->get_plugin_record_by_classname('block_ilp_dash_tab','ilp_dashboard_reports_tab');

        //get all enabled reports in this ilp
        $reports		=	ilp_report::get_enabledreports();
        $reportslist	=	array();
        if (!empty($reports)) {

            //cycle through all reports and save the relevant details
            foreach ($reports	as $r) {

                if ($r->vault != 1 || !$r->has_cap($USER->id,$PAGE->context,'block/ilp:viewreport')) {
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

                    $detail->name           =   "<a href='{$CFG->wwwroot}/blocks/ilp/actions/view_studentreports.php?course_id={$this->course_id}&tutor=0&report_id={$r->id}&group_id=0'>".$r->name."</a>";

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
        //require_once($CFG->dirroot.'/blocks/ilp/plugins/tabs/ilp_dashboard_vault_tab.html');

        $i	=	0;
        //var_dump($reportslist, $this->student_id);
        foreach ($reportslist as $rep) {
            //var_dump($rep);

            $floatclass	=	'';
            //add the extra class in to make the
            if ($i == 0) $floatclass	=	'entry_floatleft';

            if ($i == 0) {
                echo '<div align="right">
                        <a href="#" onclick="M.ilp_standard_functions.printfunction()">
                            <img src="'. $CFG->wwwroot . '/blocks/ilp/pix/icons/print_icon_med.png" alt="' . get_string("print","block_ilp") . ' width="32px" height="32px" /></a>
                      </div>';
            }
            if($rep->entries != null){
                $i++;
                echo   '<div id="vault_entries-container">
                        <div id="vault_left-entries">'
                        . $rep->icon
                        . '</div>';

                echo '<div id="middle-entries">';
                echo '<div class= "vault_report_name"><h2>'. $rep->name .'</h2></div>';
                echo '<div id = "' . $rep->report_id . '" class="vault_report_entry_count"><p>' . get_string('total_entry_found','block_ilp').': <span>'. $rep->entries.'</span></p></div>';
                //show entries here........
                //echo '<p>' . get_string('ilp_dashboard_entries_tab_lastupdate','block_ilp') . ': <span>'. $rep->lastmod .'</span></p>';
                //echo '</div>';
                $state_id	= (!empty($seltab[2])) ? $seltab[2] : false;
                $reportentries	=	$this->dbc->get_user_report_entries($rep->report_id,$this->student_id,$state_id);

                //var_dump($reportentries);

                global $CFG;
                $report_entry    =  "";

                if (!empty($reportentries)) {
                    $report_entry    .=   '<div id="vault_show_entries_'. $rep->report_id . '" class="vault_report_entries">';
                    foreach ($reportentries as $entry)	{

                        $entry_data	=	new stdClass();

                        //get the creator of the entry
                        $creator				=	$this->dbc->get_user_by_id($entry->creator_id);

                        //get comments for this entry
                        $comments				=	$this->dbc->get_entry_comments($entry->id);

                        //
                        $entry_data->creator		=	(!empty($creator)) ? fullname($creator)	: get_string('notfound','block_ilp');
                        $entry_data->created		=	userdate($entry->timecreated);
                        $entry_data->modified		=	userdate($entry->timemodified);
                        $entry_data->user_id		=	$entry->user_id;
                        $entry_data->entry_id		=	$entry->id;


                        $reportfields		=	$this->dbc->get_report_fields_by_position($rep->report_id);

                        foreach ($reportfields as $field) {

                            //get the plugin record that for the plugin
                            $pluginrecord	=	$this->dbc->get_plugin_by_id($field->plugin_id);

                            //take the name field from the plugin as it will be used to call the instantiate the plugin class
                            $classname = $pluginrecord->name;

                            // include the class for the plugin
                            include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

                            if(!class_exists($classname)) {
                                print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
                            }

                            //instantiate the plugin class
                            $pluginclass	=	new $classname();

                            if ($pluginclass->is_viewable() != false)	{
                                $pluginclass->load($field->id);

                                //call the plugin class entry data method
                                $pluginclass->view_data($field->id,$entry->id,$entry_data,false);
                            } else	{
                                $dontdisplay[]	=	$field->id;
                            }

                        }

                        if (!empty($reportfields)) {
                            $report_entry    .= html_writer::start_tag('div', array( 'class'=>'report-entry'));
                            $report_entry    .= html_writer::start_tag('table');
                            foreach ($reportfields as $field) 	{
                                $field_name	= $field->id."_field";
                                $report_entry .= html_writer::start_tag('tr');
                                $report_entry .= html_writer::start_tag('th');
                                $report_entry .= $field->label.':';
                                $report_entry .= html_writer::end_tag('th');
                                $report_entry .= html_writer::start_tag('td');
                                $report_entry .= (!empty($entry_data->$field_name)) ? $entry_data->$field_name : '&nbsp;';
                                $report_entry .= html_writer::end_tag('td');
                                $report_entry .= html_writer::end_tag('tr');
                            }


                            if (!empty($has_courserelated)) {
                                $report_entry .= "<p><strong>".get_string('course','block_ilp')."</strong> : ".$entry_data->coursename." </p>";
                            }
                            $report_entry .= "<div class='added_by'><strong>".get_string('addedby','block_ilp')."</strong>: {$entry_data->creator}</div>";
                            $report_entry .= "<div class='entry_date'><strong>".get_string('date')."</strong>: {$entry_data->modified}</div>";
                            $report_entry .= html_writer::end_tag('table');
                            $comments      = $this->dbc->get_entry_comments($entry->id);
                            if($comments){
                                $report_entry .= '<div id="'. $entry->id .'" class="comment_counter"> '. count($comments) .' comments</div>';
                                $report_entry .= '<div id="comment_for_'. $entry->id .'">' . $this->generate_comments($comments) . '</div>';
                            }
                            $report_entry .= html_writer::end_tag('div');

                        }
                    }
                    $report_entry    .=  "</div>";
                    echo $report_entry;
                }
                echo '</div></div>';
                //echo '<div class="clearfix"> </div>';
            }
            if ($i == 0){
                //we make a new div, so we can styling on dimmand
                echo '<div id="vault_entry_not_found">';
                echo '<p>' . get_string('vault_entry_entry_not_found','block_ilp') . '</p>';
                echo '</div>';
            }
        }
    }

    public function generate_comments($comments) {
        // this function copied from reports tab
        global $OUTPUT, $USER, $CFG;
        $o  = '';
        if ($comments) {
            foreach ($comments as $c) {
                $comment_creator = $this->dbc->get_user_by_id($c->creator_id);
                $commentval	= html_entity_decode($c->value, ENT_QUOTES, 'UTF-8');
                $o .= html_writer::start_tag('div', array('class'=>'vault_comment', 'id'=>'comment-id-' . $c->id));
                $o .= html_writer::tag('p', $commentval);
                $o .= html_writer::tag('div','', array('class'=>'editarea editarea-' . $c->id));
                $o .= html_writer::start_tag('div', array('class'=>'info'));
                $o .= get_string('creator','block_ilp') . ": " . fullname($comment_creator) . ' | ';
                $o .= get_string('date') . ": " . userdate($c->timemodified, get_string('strftimedate')) . ' | ';
                $o .= html_writer::end_tag('div');
                $o .= html_writer::end_tag('div');
            }
        }

        return $o;
    }

}



class ilp_vault_db extends ilp_db	{

    function __construct() {
        global $CFG;

        // include the static constants
        require_once($CFG->dirroot.'/blocks/ilp/lib.php');

        // instantiate the Assessment admin database
        $this->dbc = new ilp_vault_db_functions();
    }

}


class ilp_vault_db_functions extends ilp_db_functions	{

    function __construct() {
        parent::__construct();
    }

}