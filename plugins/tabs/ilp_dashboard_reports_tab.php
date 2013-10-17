<?php

//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');
require_once($CFG->dirroot.'/blocks/ilp/classes/ilp_report.class.php');

class ilp_dashboard_reports_tab extends ilp_dashboard_tab {

   public	$student_id;
   public 	$filepath;
   public	$linkurl;
   public 	$selectedtab;
   public	$role_ids;
   public 	$capability;
   static       $access_report_editcomment;
   static       $access_report_deletecomment;
   static       $access_report_addreports;
   static       $multiple_entries;
   static       $reportavailable;
   static       $access_report_editreports;
   static       $access_report_deletereport;
   static       $access_report_addcomment;
   static       $access_report_viewcomment;
   static       $reportentries;
   static       $reportfields;
   static       $access_report_viewotherilp;


    function __construct($student_id=null,$course_id=null)	{
      global 	$CFG,$USER,$PAGE;

      //$this->linkurl				=	$CFG->wwwroot.$_SERVER["SCRIPT_NAME"]."?user_id=".$student_id."&course_id={$course_id}";

      $this->linkurl					=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id=".$student_id."&course_id={$course_id}";

      $this->student_id	=	$student_id;

      $this->course_id	=	$course_id;

      $this->selectedtab	=	false;

      $defaulttab			=	get_config('block_ilp','ilp_dashboard_reports_tab_default');

      //set the id of the tab that will be displayed first as default
      $this->default_tab_id	=	(empty($defaulttab)) ? '1' : get_config('block_ilp','ilp_dashboard_reports_tab_default');

      //call the parent constructor
      parent::__construct();

   }

    /**
     * Generates the add-new-entry element.
     * Gets the capabilites from static or as loaded in the display and shows the element accordingly.
     * @param string url of script to be requested via AJAX
     * @param bool $access_report_addreports
     * @param bool $multiple_entries
     * @param bool $reportavailable
     * @param int $studentid
     * @param int $ajax (0 or 1)
     * @param int $num_entries the number of existing entries
     * @param int $displaysummary displays only fields with summary flag
     * @return string
     */
    public function generate_addnewentry($addnewentry_url, $access_report_addreports = null, $multiple_entries = null, $reportavailable = null, $studentid = null, $ajax = null, $num_entries = false, $displaysummary = null) {
        global $CFG;
        $access_report_addreports = static::$access_report_addreports;
        if ($ajax) {
            // If not being called from 'display' of this class
            $multiple_entries = static::$multiple_entries;
            $reportavailable = static::$reportavailable;
        }

        if ($num_entries === false) {
            $num_entries = count(static::$reportentries);
        }

        $multiple_entries_switch = !empty($multiple_entries) ? '1' : '0';
        $multiple_entries_check = (!empty($multiple_entries) || !$num_entries);
        $classes = '_addnewentry';
        if (!$multiple_entries_check) {
            $classes .= " hiddenelement";
        }

        $addnew_attrs = array('data-link'=>$addnewentry_url, 'class'=>$classes, 'data-multiple_entries'=>$multiple_entries_switch);

        if ($displaysummary !== null) {
            $addnew_attrs['data-displaysummary'] = (int) $displaysummary;
        }
        if ($studentid) {
            $addnew_attrs['data-studentid'] = $studentid;
        }
        $addnew_span = html_writer::tag('span', get_string('addnew','block_ilp'), $addnew_attrs);
        $addnew_area = html_writer::tag('div','', array('class'=>'_addnewentryarea'));
        $loader_icon = $this->get_loader_icon('addnewentry-loader', 'span');

        if (!empty($access_report_addreports) && !empty($reportavailable['result'])) {
            return    "<div class='add' style='float :left'>
                                     $loader_icon $addnew_span
                                        </div> $addnew_area";
        }
        return '';
    }

    /**
     * Generates the AJAX loader icon.
     * @param string $container_classes used in Javascript selectors
     * @param string $eltype
     * @return string
     */
    public function get_loader_icon($container_classes, $eltype = 'div') {
        global $CFG;
        $src = $CFG->wwwroot . '/blocks/ilp/pix/loading.gif';
        $loadericon = html_writer::tag('img', '', array('class'=>'ajaxloadicon hiddenelement', 'src'=>$src));
        return html_writer::tag($eltype, $loadericon, array('class'=>$container_classes));
    }

    /**
     * Generates the comments below each entry.
     * When a new comment is added via AJAX, the full comment list for that entry is re-generated for display.
     * @param array $comments
     * @param int $ajax
     * @param string $url_params
     * @param int $entry_id
     * @param array $access array of access requirements
     * @return string
     */
    public function generate_comments($comments, $ajax, $url_params, $entry_id = null, $access = array(), $readonly = false) {
        global $OUTPUT, $USER, $CFG;
        $o  = '';

        if ($ajax) {
            $comments =	$this->dbc->get_entry_comments($entry_id);
            $access = array('access_report_editcomment' => static::$access_report_editcomment,'access_report_deletecomment'=>static::$access_report_deletecomment);
        }

        if ($readonly) {
            $access = array();
        }
        if ($comments) {
            foreach ($comments as $c) {
                $comment_creator = $this->dbc->get_user_by_id($c->creator_id);
                $commentval	= html_entity_decode($c->value, ENT_QUOTES, 'UTF-8');
                $o .= html_writer::start_tag('div', array('class'=>'comment', 'id'=>'comment-id-' . $c->id));
                $o .= html_writer::tag('p', $commentval);
                $o .= html_writer::tag('div','', array('class'=>'editarea editarea-' . $c->id));
                $o .= html_writer::start_tag('div', array('class'=>'info'));
                $o .= get_string('creator','block_ilp') . ": " . fullname($comment_creator) . ' | ';
                $o .= get_string('date') . ": " . userdate($c->timemodified, get_string('strftimedate')) . ' | ';
                if ($c->creator_id == $USER->id && !empty($access['access_report_editcomment'])) {
                    $edit_link = $CFG->wwwroot . '/blocks/ilp/actions/edit_entrycomment.ajax.php?' . $url_params . '&comment_id=' . $c->id;
                    $edit_link_content = get_string('edit') . html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url("/i/edit"), 'alt'=>get_string('edit')));
                    //$o .= html_writer::link($edit_link, $edit_link_content, array('class'=>'edit-comment-ajax', 'id'=>'edit-comment-ajax-' . $c->id));
                    $o .= html_writer::tag('span', $edit_link_content,
                        array('class'=>'edit-comment-ajax', 'id'=>'edit-comment-ajax-' . $c->id, 'data-link'=>$edit_link, 'data-entry'=>$entry_id));
                    $o .= $this->get_loader_icon('editcomment-loader-icon-' . $c->id, 'span');
                }
                if (!empty($access['access_report_deletecomment'])) {
                    $delete_link = $CFG->wwwroot . '/blocks/ilp/actions/delete_reportcomment.ajax.php?' . $url_params . '&comment_id=' . $c->id;
                    $delete_link_content = get_string('delete') . html_writer::tag('img', '', array('src'=>$OUTPUT->pix_url("/t/delete"), 'alt'=>get_string('delete')));
                    $o .= html_writer::tag('span', $delete_link_content,
                        array('class'=>'delete-comment-ajax', 'id'=>'delete-comment-ajax-' . $c->id, 'data-link'=>$delete_link, 'data-entry'=>$entry_id));
                    $o .= $this->get_loader_icon('deletecomment-loader-icon-' . $c->id, 'span');
                }
                $o .= html_writer::end_tag('div');
                $o .= html_writer::end_tag('div');
            }
        }

        return $o;
    }

    /**
     * Generates the left-hand part of the content of a report entry.
     * This is designed for use by the standard display and for newly added/edited reports via ajax.
     * @param array $reportfields
     * @param array $dontdisplay array of fields not to show
     * @param int $displaysummary displays only fields with summary flag
     * @param object $entry_data
     * @return string
     */
    public function generate_left_reports($reportfields = null, $dontdisplay, $displaysummary, $entry_data) {
        if (!$reportfields) {
            $reportfields = static::$reportfields;
        }

        $displaysummary = (isset($displaysummary)) ? $displaysummary : 0;
        $table = '<table><tbody>';
        foreach ($reportfields as $field) {
           $fieldname	=	$field->id."_field";

            $summary_condition = $field->summary || !$displaysummary;
            if (!isset($entry_data->$fieldname)) {
            }
           if (!in_array($field->id,$dontdisplay) &&
               isset($entry_data->$fieldname) &&
               $summary_condition)
           {
              $fieldcontent = html_writer::tag('th', '<strong>' . $field->label . '</strong>: ');
              $fieldvalue = (!empty($entry_data->$fieldname)) ? $entry_data->$fieldname : '';
              $attrs = array('class'=>$field->pluginname);
              $fieldcontent .= html_writer::tag('td', $fieldvalue, $attrs);
              $table .= html_writer::tag('tr', $fieldcontent);
           }
        }
        $table .= '</tbody></table>';
        return html_writer::tag('div', $table, array('class'=>'left-reports'));
    }

    /**
     * Generates the right-hand part of the content of a report entry.
     * This is used by the standard display and for newly added/edited reports via ajax in the reports tab.
     * @param bool $has_courserelated
     * @param bool $has_deadline
     * @param object $entry_data
     * @return string
     */
    public function generate_right_reports($has_courserelated, $has_deadline, $entry_data) {
        $content = '';
        $fieldcontent = array(
            get_string('addedby','block_ilp')." : ".$entry_data->creator,
        );
        if (!empty($has_courserelated)) {
            $fieldcontent[] = get_string('course','block_ilp')." : ".$entry_data->coursename;
        }
        if (!empty($has_deadline)) {
            $fieldcontent[] = get_string('deadline','block_ilp') . ': date';
        }
        $fieldcontent[] = get_string('date')." : ".$entry_data->modified;
        foreach ($fieldcontent as $fieldcontent_item) {
            $content .= html_writer::tag('p', $fieldcontent_item);
        }

        return html_writer::tag('div', $content, array('class'=>'right-reports'));
    }

    /*
     * Generates a form so that page has JS to handle AJAX forms that are used
     */
    public function generate_unused_form() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/ilp/classes/forms/js_loader_mform.php');
        $js_loader = new js_loader_mform();
        echo '<div class="hiddenelement">';
        $loader_form = $js_loader->display();
        echo '</div>';
    }

   /**
    * Return the text to be displayed on the tab
    */
   function display_name()	{
      return	get_string('ilp_dashboard_reports_tab_name','block_ilp');
   }

   /**
    * Override this to define the second tab row should be defined in this function
    */
   function define_second_row()	{
      global 	$CFG,$USER,$PAGE,$OUTPUT,$PARSER;

      //if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table
      //as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION
      if (!empty($this->plugin_id)) {


         /****
          * This code is in place as moodle insists on calling the settings functions on normal pages
          *
          */
         //check if the set_context method exists
         if (!empty($PAGE->context->id)) {

            $course_id = (is_object($PARSER)) ? $PARSER->optional_param('course_id', SITEID, PARAM_INT)  : SITEID;
            $user_id = (is_object($PARSER)) ? $PARSER->optional_param('user_id', $USER->id, PARAM_INT)  : $USER->id;

            $this->secondrow	=	array();

            //create a tab for each enabled report
            foreach(ilp_report::get_enabledreports() as $r)	{
               if ($r->has_cap($USER->id,$PAGE->context,'block/ilp:viewreport'))
                  if($r->vault == 0){ //if any report has been sent to vault, they should not be displayed here.
                      //the tabitem and selectedtab query string params are added to the linkurl in the
                      //second_row() function
                      $this->secondrow[]	=	array('id'=>$r->id,'link'=>$this->linkurl,'name'=>$r->name);
                  }
            }
         }
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
    *
    * Simple function to return the header for this tab
    * @param unknown_type $headertext
    */
   function get_header($headertext,$icon)	{
      //setup the icon
      $icon 	=	 "<img id='reporticon' class='icon_med' alt='$headertext ".get_string('reports','block_ilp')."' src='$icon' />";

      return "<h2>{$icon}{$headertext}</h2>";
   }

    /**
     * Sets the capabilities in static variables; used by scripts called in the AJAX add/edit features which are outside this class.
     *
     * @return none
     */
    function get_capabilites($selectedtab=null, $report_id = null)	{
        global 	$CFG, $PAGE, $USER, $OUTPUT, $PARSER;

        if ($this->dbc->get_user_by_id($this->student_id)) {
           require_once("$CFG->dirroot/blocks/ilp/classes/ilp_report_rules.class.php");
            //get the selecttab param if has been set
            $this->selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

            //get the tabitem param if has been set
            $this->tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_CLEAN);

            $displaysummary     =	$PARSER->optional_param('summary', 0, PARAM_INT);

            //start buffering output
            ob_start();

            //split the selected tab id on up 3 ':'
            $seltab	=	explode(':',$selectedtab);

            //if the seltab is empty then the highest level tab has been selected
            if (empty($seltab))	$seltab	=	array($selectedtab);

            if (!$report_id) {
                $report_id	= (!empty($seltab[1])) ? $seltab[1] : $this->default_tab_id;
            }

            $state_id	= (!empty($seltab[2])) ? $seltab[2] : false;

            if ($report	=$this->dbc->get_report_by_id($report_id)) {
                $context = $PAGE->context;

               if($report->status==ILP_ENABLED && $report->has_cap($USER->id,$context,'block/ilp:viewreport'))
               {

                  $reportname	=	$report->name;
                  //get all of the fields in the current report, they will be returned in order as
                  //no position has been specified
                  $reportfields		=	$this->dbc->get_report_fields_by_position($report_id, null, null, true);

                  static::$reportfields = $reportfields;
                  $reporticon	= (!empty($report->iconfile)) ? '' : '';

                  //does this report give user the ability to add comments
                  $has_comments	=	!empty($report->comments);

                  //this will hold the ids of fields that we dont want to display
                  $dontdisplay	=	 array();

                  //does this report allow users to say it is related to a particular course
                  $has_courserelated	=	($this->dbc->has_plugin_field($report_id,'ilp_element_plugin_course')) ? true : false;

                  if (!empty($has_courserelated))	{
                     $courserelated	=	$this->dbc->has_plugin_field($report_id,'ilp_element_plugin_course');
                     //the should not be anymore than one of these fields in a report
                     foreach ($courserelated as $cr) {
                        $dontdisplay[] 	=	$cr->id;
                        $courserelatedfield_id	=	$cr->id;
                     }
                  }

                  //find if the current user can add reports
                  static::$access_report_addreports= $report->has_cap($USER->id,$PAGE->context,'block/ilp:addreport');

                  //find out if the current user has the edit report capability for the report
                  static::$access_report_editreports = $report->has_cap($USER->id,$PAGE->context,'block/ilp:editreport');

                  //find out if the current user has the delete report capability for the report
                  static::$access_report_deletereport=$report->has_cap($USER->id,$PAGE->context,'block/ilp:deletereport');

                  //find out if the current user has the add comment capability for the report
                  static::$access_report_addcomment=$report->has_cap($USER->id,$PAGE->context,'block/ilp:addcomment');

                  //find out if the current user has the edit comment capability for the report
                  static::$access_report_editcomment=$report->has_cap($USER->id,$PAGE->context,'block/ilp:editcomment');

                  //find out if the current user has the delete comment capability for the report
                  static::$access_report_deletecomment=$report->has_cap($USER->id,$PAGE->context,'block/ilp:deletecomment');

                  // view comment
                  static::$access_report_deletecomment=$report->has_cap($USER->id,$PAGE->context,'block/ilp:viewcomment');

                  static::$access_report_deletecomment=$report->has_cap($USER->id,$PAGE->context,'block/ilp:viewotherilp');

                  static::$access_report_deletecomment=$report->has_cap($USER->id,$PAGE->context,'block/ilp:viewextension');

                  static::$access_report_viewotherilp=$report->has_cap($USER->id,$PAGE->context,'block/ilp:viewotherilp');

                   if (empty(static::$access_report_viewotherilp) && $USER->id != $this->student_id) {
                       //the user doesnt have the capability to create this type of report entry
                       print_error('accessnotallowed','block_ilp');

                   }

                  //get all of the entries for this report

                  static::$reportentries = $report->get_user_report_entries($this->student_id,$state_id);

                  //does the current report allow multiple entries
                  static::$multiple_entries = !empty($report->frequency);

                  //instantiate the report rules class
                  $reportrules    =   new ilp_report_rules($report_id,$this->student_id);

                  $stateselector	=	(isset($report_id)) ?	$this->stateselector($report_id) :	"";

                  static::$reportavailable = $reportrules->report_availabilty();
               }
            }
        }
    }

   /**
    * Returns the content to be displayed
    *
    * @param	string $selectedtab the tab that has been selected this variable
    * @param	array $ajax_settings Some settings used to dynamically update after performing ajax edits.
    * this variable should be used to determined what to display
    *
    * @return none
    */
    public function display($selectedtab=null, $ajax_settings = array(),$readonly=false,$showcomments=true, $tabitem = null, $report_id = null)	{
      global 	$CFG, $PAGE, $USER, $OUTPUT, $PARSER;

       $this->get_capabilites($selectedtab, $report_id);

       $access_report_viewcomment = static::$access_report_viewcomment;
       $access_report_addcomment = static::$access_report_addcomment;
       $access_report_deletecomment = static::$access_report_deletecomment;
       $access_report_editcomment = static::$access_report_editcomment;
       $access_report_addreports = static::$access_report_addreports;
       $access_report_editreports = static::$access_report_editreports;
       $access_report_deletereport = static::$access_report_deletereport;
       $candelete = $access_report_deletereport;

       $jsarguments = array(
           'root' => $CFG->wwwroot,
           'pagename' => 'view_main/reports_tab'
       );

       $jsmodule = array(
           'name'     	=> 'ilp_ajax_addnew',
           'fullpath' 	=> '/blocks/ilp/views/js/ajax_addnew.js',
           'requires'  	=> array('io','io-form', 'json-parse', 'json-stringify', 'json', 'base', 'node')
       );

       $return_only_newest = !empty($ajax_settings['return_only_newest_entry']);

       $return_refreshed_list = !empty($ajax_settings['return_refreshed_entry_list']);

       $return_left_report_only = !empty($ajax_settings['return_left_reports_for_single_entry']) ?
          $ajax_settings['return_left_reports_for_single_entry'] : false;

       $return_right_report_only = !empty($ajax_settings['return_right_reports_for_single_entry']) ?
          $ajax_settings['return_right_reports_for_single_entry'] : false;

       $PAGE->requires->js_init_call('M.ilp_ajax_addnew.init', $jsarguments, true, $jsmodule);

        $jsarguments = array();

        $jsmodule = array(
            'name'     	=> 'ilp_edit_reportentry',
            'fullpath' 	=> '/blocks/ilp/views/js/edit_reportentry.js',
            'requires'  	=> array('event','dom','node','io-form','anim-base','anim-xy','anim-easing','anim', 'node-event-simulate')
        );

        $PAGE->requires->js_init_call('M.ilp_edit_reportentry.init', $jsarguments, true, $jsmodule);

      $pluginoutput	    =	"";

      if ($this->dbc->get_user_by_id($this->student_id)) {

         //get the selecttab param if has been set
         $this->selectedtab = $PARSER->optional_param('selectedtab', $selectedtab, PARAM_INT);

         //get the tabitem param if has been set
         $this->tabitem = $PARSER->optional_param('tabitem', $tabitem, PARAM_CLEAN);

         $displaysummary     =	$PARSER->optional_param('summary', 0, PARAM_INT);

         //start buffering output
         ob_start();

         //split the selected tab id on up 3 ':'
         $seltab	=	explode(':',$selectedtab);

         //if the seltab is empty then the highest level tab has been selected
         if (empty($seltab))	$seltab	=	array($selectedtab);

         if (!$report_id) {
             $report_id	= (!empty($seltab[1])) ? $seltab[1] : $this->default_tab_id ;
         }

         $state_id	= (!empty($seltab[2])) ? $seltab[2] : false;

         if ($report=ilp_report::from_id($report_id)) {
             $PAGE->navbar->add($report->name, '', 'title');
            if ($report->status == ILP_ENABLED and $report->has_cap($USER->id,$PAGE->context,'block/ilp:viewreport')) {
               $reportname	=	$report->name;
               //get all of the fields in the current report, they will be returned in order as
               //no position has been specified
               $reportfields=static::$reportfields;

               $reporticon = (!empty($report->iconfile)) ? '' : '';

               //does this report give user the ability to add comments
               $has_comments	=	!empty($report->comments);

               //this will hold the ids of fields that we dont want to display
               $dontdisplay	=	 array();

               //does this report allow users to say it is related to a particular course
               $has_courserelated	=	($report->has_plugin_field('ilp_element_plugin_course'));

               if (!empty($has_courserelated))	{
                  $dontdisplay[] =	$has_courserelated->id;
                  $courserelatedfield_id  =$has_courserelated->id;
               }

               foreach(array('addreports','editreport','deletereport','addcomment','editcomment',
                             'deletecomment','viewcomment','viewotherilp','addviewextension') as $capname)
               {
                  $varname='access_report_'.$capname;
                  $$varname=$report->has_cap($USER->id,$PAGE->context,"block/ilp:$capname");
               }

               foreach(array('addreports','editreport','deletereport','addcomment','editcomment',
                             'deletecomment','addviewextension') as $capname)
               {
                  $varname='access_report_'.$capname;
                  $$varname=($$varname and !$readonly);
               }

               $access_report_viewcomment=($access_report_viewcomment && $showcomments);
               $access_report_addcomment=($access_report_addcomment && !$readonly);

               //get all of the entries for this report
               $reportentries	=	$report->get_user_report_entries($this->student_id,$state_id);

               static::$reportentries = $reportentries;
                    //does the current report allow multiple entries
               $multiple_entries   =   !empty($report->frequency);

               //instantiate the report rules class
               $reportrules    =   new ilp_report_rules($report_id,$this->student_id);

               //output html elements to screen

               $icon = (!empty($report->binary_icon)) ? $CFG->wwwroot."/blocks/ilp/iconfile.php?report_id=".$report->id : $CFG->wwwroot."/blocks/ilp/pix/icons/defaultreport.gif";

               echo $this->get_header($report->name,$icon);

               $stateselector	=	(isset($report_id) and !$readonly) ?	$this->stateselector($report_id, $displaysummary) :	"";

               //find out if the rules set on this report allow a new entry to be created
               $reportavailable =   $reportrules->report_availabilty();

               echo "<div id='report-entries'>";
               $addnewentry_url = "{$CFG->wwwroot}/blocks/ilp/actions/edit_reportentry.ajax.php?user_id={$this->student_id}&selectedtab={$this->selectedtab}&tabitem={$this->tabitem}&report_id={$report_id}&course_id={$this->course_id}";

               echo $this->generate_addnewentry($addnewentry_url, $access_report_addreports, $multiple_entries, $reportavailable, null, null, false, $displaysummary);

               if (!empty($access_report_viewothers)) {

                  if (!empty($access_report_addviewextension) && $reportrules->can_add_extensions()) {
                     echo "<div class='add' style='float :left'>
                                        <a href='{$CFG->wwwroot}/blocks/ilp/actions/edit_report_preference.php?user_id={$this->student_id}&report_id={$report_id}&course_id={$this->course_id}' >".get_string('addextension','block_ilp')."</a>&nbsp;
                                      </div>

                                    <div class='add' style='float :left'>
                                        <a href='{$CFG->wwwroot}/blocks/ilp/actions/view_extensionlist.php?user_id={$this->student_id}&report_id={$report_id}&course_id={$this->course_id}' >".get_string('viewextension','block_ilp')."</a>
                                    </div>";
                  }

               }
               echo "</div>
                            <br />";

               if(!$readonly)
               {
                  //This is dubious - readonly is being assumed to mean "batch mode"
                  //But the whole class needs seriously re-written anyway.
                  //output the print icon
                  echo "{$stateselector}";

                   $allow_page_print = get_config('block_ilp', 'allow_page_print');

                   if ($allow_page_print !== '0') {
                       echo "<div class='entry_floatright'><a href='#' onclick='M.ilp_standard_functions.printfunction()' ><img src='{$CFG->wwwroot}/blocks/ilp/pix/icons/print_icon_med.png' alt='".get_string("print","block_ilp")."' class='ilp_print_icon' width='32px' height='32px' ></a></div>
                                     ";
                   }
               }

               //create the entries list var that will hold the entry information
               $entrieslist	=	array();

               $container_dom_id = 'reports-container-container';
               if (!empty($reportentries)) {

//Mini caches for items that are looked at repeatedly in the loops below
                  $creators=$pluginRecords=$pluginInstances=array();

                  echo html_writer::start_tag('div', array('class'=>$container_dom_id));
                  if ($return_refreshed_list) {
                      ob_end_clean();
                      ob_start();
                  }
                  foreach ($reportentries as $entry)	{

                      if ($return_left_report_only && $return_left_report_only != $entry->id) {
                          continue;
                      }
                      if ($return_right_report_only && $return_right_report_only != $entry->id) {
                          continue;
                      }

                     //TODO: is there a better way of doing this?
                     //I am currently looping through each of the fields in the report and get the data for it
                     //by using the plugin class. I do this for two reasons it may lock the database for less time then
                     //making a large sql query and 2 it will also allow for plugins which return multiple values. However
                     //I am not naive enough to think there is not a better way!

                     $entry_data	=	new stdClass();

                     //get the creator of the entry with caching
                     if(!isset($creators[$entry->creator_id]))
                     {
                        $creators[$entry->creator_id]          =       $this->dbc->get_user_by_id($entry->creator_id);
                     }

                     $creator=$creators[$entry->creator_id];

                     //get comments for this entry
                     $comments				=	$this->dbc->get_entry_comments($entry->id);

                     //
                     $entry_data->creator		=	(!empty($creator)) ? fullname($creator)	: get_string('notfound','block_ilp');
                     $entry_data->created		=	userdate($entry->timecreated);
                     $entry_data->modified		=	userdate($entry->timemodified);
                     $entry_data->user_id		=	$entry->user_id;
                     $entry_data->entry_id		=	$entry->id;

                     if ($has_courserelated) {
                        $coursename	=	false;
                        $crfield	=	$this->dbc->get_report_coursefield($entry->id,$courserelatedfield_id);
                        if (empty($crfield) || empty($crfield->value)) {
                           $coursename	=	get_string('allcourses','block_ilp');
                        } else if ($crfield->value == '-1') {
                           $coursename	=	get_string('personal','block_ilp');
                        } else {
                           $crc	=	$this->dbc->get_course_by_id($crfield->value);
                           if (!empty($crc)) $coursename	=	$crc->shortname;
                        }
                        $entry_data->coursename = (!empty($coursename)) ? $coursename : '';
                     }

                     foreach ($reportfields as $field) {

                        //get the plugin record that for the plugin, with cacheing
                        if(!isset($pluginRecords[$field->plugin_id]))
                        {
                           $pluginRecords[$field->plugin_id]=$this->dbc->get_plugin_by_id($field->plugin_id);
                        }

                        $pluginrecord=$pluginRecords[$field->plugin_id];

                        //take the name field from the plugin as it will be used to call the instantiate the plugin class
                        $classname = $pluginrecord->name;

                        if(!isset($pluginInstances[$classname]))
                        {
                           // include the class for the plugin
                           include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

                           if(!class_exists($classname)) {
                              print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
                           }

                           //instantiate the plugin class

                           $pluginInstances[$classname]=new $classname;
                           $pluginFieldsLoaded[$classname]=array();
                        }

                        $pluginclass	=   $pluginInstances[$classname];

                        if ($pluginclass->is_viewable() != false)	{
                           if(!isset($pluginFieldsLoaded[$classname][$field->id]))
                           {
                              $pluginclass->load($field->id);
                              $pluginFieldsLoaded[$classname][$field->id]=true;
                           }

                           //call the plugin class entry data method
                           $pluginclass->view_data($field->id,$entry->id,$entry_data,false);
                        } else	{
                           $dontdisplay[]	=	$field->id;

                        }

                     }

                      if ($return_only_newest) {
                          ob_end_clean();
                          ob_start();
                      }
                      if ($return_left_report_only || $return_right_report_only) {
                          ob_end_clean();
                          ob_start();
                          if ($return_left_report_only) {
                              echo $this->generate_left_reports(null, $dontdisplay, $displaysummary, $entry_data);
                          } else if ($return_right_report_only) {
                              $has_deadline = (isset($has_deadline)) ? $has_deadline : null;
                              echo $this->generate_right_reports($has_courserelated, $has_deadline, $entry_data);
                          }
                          $pluginoutput = ob_get_contents();
                          ob_end_clean();
                          return $pluginoutput;
                      }
                      include($CFG->dirroot.'/blocks/ilp/plugins/tabs/ilp_dashboard_reports_tab.html');
                      if ($return_only_newest) {
                          $pluginoutput = ob_get_contents();
                          ob_end_clean();
                          return $pluginoutput;
                      }

                  }
                   if ($return_refreshed_list) {
                       $pluginoutput = ob_get_contents();
                       ob_end_clean();
                       return $pluginoutput;
                   }
                   echo html_writer::end_tag('div');
               } else {
                  echo html_writer::tag('div', '', array('class'=>$container_dom_id));
                  if(!$readonly)
                  {
                     echo html_writer::tag('p', get_string('nothingtodisplay'), array('class'=>'nothingtodisplay'));
                  }
                  else
                  {
                     ob_clean();
                  }

               }

            }

         }

         // load custom javascript
         $module = array(
            'name'      => 'ilp_dashboard_reports_tab',
            'fullpath'  => '/blocks/ilp/plugins/tabs/ilp_dashboard_reports_tab.js',
            'requires'  => array('event','dom','node','io-form','anim-base','anim-xy','anim-easing','anim')
            );

         // js arguments
         $jsarguments = array(
            'open_image'   => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_minus.gif",
            'closed_image' => $CFG->wwwroot."/blocks/ilp/pix/icons/switch_plus.gif",
            );

         //Only activate JS hide/show code if not in read-only/show comment modes
         if(!($readonly and $showcomments))
         {
            // initialise the js for the page
            $PAGE->requires->js_init_call('M.ilp_dashboard_reports_tab.init', $jsarguments, true, $module);
            $this->generate_unused_form();
         }

         $pluginoutput = ob_get_contents();

         ob_end_clean();

      } else {
         $pluginoutput	=	get_string('studentnotfound','block_ilp');
      }


      return $pluginoutput;
   }

   function stateselector($report_id, $displaysummary = null)	{
      $stateselector = "<div class='report_state'><form action='{$this->linkurl}&selectedtab={$this->plugin_id}' method='get' >
			                                <input type='hidden' name='course_id' value='{$this->course_id}' />
											<input type='hidden' name='user_id' value='{$this->student_id}' />
											<input type='hidden' name='selectedtab' value='{$this->plugin_id}' />
                                            <input type='hidden' name='tabitem' value='{$this->plugin_id}:{$report_id}' />";

      //find out if the report has state fields
      if ($this->dbc->has_plugin_field($report_id,'ilp_element_plugin_state'))	{
         $states		=	$this->dbc->get_report_state_items($report_id,'ilp_element_plugin_state');
         $stateselector	.=	"<label>Report State</label>

											<select name='tabitem' id='reportstateselect'>
											<option value='{$this->plugin_id}:{$report_id}' >Any State</option>";
         if (!empty($states)) {
            foreach($states as $s)	{
               $stateselector .= "<option value='{$this->plugin_id}:{$report_id}:{$s->id}'>{$s->name}</option>";
            }
         }
         $stateselector	.=	"</select>";


      }

      $summarychecked =    (!empty($displaysummary)) ? "checked='checked'" : "";

      $stateselector	.=   "<br />
                                      <label for='summary'>".get_string('displaysummary','block_ilp')."</label>
                                      <input id='summary' type='checkbox' name='summary' value='1' {$summarychecked} >
                                      <p>
					                  <input type='submit' value='Apply Filter' id='stateselectorsubmit' />
					                  </p></form></div>";
			return $stateselector;
	}


   /**
    * Adds config settings for the plugin to the given mform
    * by default this allows config option allows a tab to be enabled or dispabled
    * override the function if you want more config options REMEMBER TO PUT
    *
    */
   function config_form(&$mform)	{

      $reports	=	$this->dbc->get_reports(ILP_ENABLED);

      $options = array();

      if (!empty($reports)) {
         foreach ($reports as $r) {
            $options[$r->id]	=	$r->name;
         }
      }

      $this->config_select_element($mform,'ilp_dashboard_reports_tab_default',$options,get_string('ilp_dashboard_reports_tab_default_tab', 'block_ilp'),'',0);


      //get the name of the current class
      $classname	=	get_class($this);

      $options = array(
         ILP_ENABLED => get_string('enabled','block_ilp'),
         ILP_DISABLED => get_string('disabled','block_ilp')
         );

      $this->config_select_element($mform,$classname.'_pluginstatus',$options,get_string($classname.'_name', 'block_ilp'),get_string('tabstatusdesc', 'block_ilp'),0);

   }
   	 static function language_strings(&$string) {
        $string['ilp_dashboard_reports_tab_default_tab'] 					= 'Default form to display';
        $string['ilp_dashboard_reports_tab_default_tabdesc'] 				= 'Default form to display';
        
        return $string;
    }
}
