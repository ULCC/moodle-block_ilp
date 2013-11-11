<?php
/**
 * This class provides a mform that previews the entry form
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */




class report_entry_mform extends ilp_moodleform {


		public 		$course_id;
		public		$report_id;
		public 		$user_id;
		public		$dbc;
        public      $formdata;
        public   $dontfreeze;

		/**
     	 * TODO comment this
     	 */
		function __construct($report_id,$user_id,$entry_id=null,$course_id=null,$currentpage=null) {

			global $CFG;

			$this->course_id	=	$course_id;
			$this->report_id	=	$report_id;
			$this->user_id		=	$user_id;
			$this->entry_id		=	$entry_id;
            $this->currentpage  =   $currentpage;
            $this->canfreeze   =   array();

			$this->dbc			=	new ilp_db();

			$query_string	=	"?report_id={$report_id}&amp;user_id={$user_id}";

			if (!empty($entry_id)) $query_string	.= "&amp;entry_id={$entry_id}";
			if (!empty($course_id)) $query_string	.= "&amp;course_id={$course_id}";


			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_reportentry.php");
            /*$this->_form->_elements[9]->_attributes = array(
                'class'=>'123', 'name'=>'345', 'type'=>'hidden'
            );
            $this->_form->_elementIndex['345'] = 9;*/
		}


		/**
     	 * TODO comment this
     	 */
		function definition() {
			 global $USER, $CFG;

	         // include the assmgr db
        	require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        	$dbc = new ilp_db;

        	$mform =& $this->_form;

			//get all of the fields in the current report, they will be returned in order as
			//no position has been specified
			$reportfields		=	$this->dbc->get_report_fields_by_position($this->report_id);

			$report				=	$this->dbc->get_report_by_id($this->report_id);
			$user				=	$this->dbc->get_user_by_id($this->user_id);


			$title	=	"{$report->name} ".get_string('for','block_ilp')." {$user->firstname} {$user->lastname}";
			//create a new fieldset
        	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset"><div>');
            $mform->addElement('html', '<legend class="ftoggler">'.$title.'</legend>');

            $desc	=	html_entity_decode($report->description, ENT_QUOTES, 'UTF-8');

			$mform->addElement('html', '<div class="descritivetext">'.$desc.'</div>');

			$mform->addElement('hidden', 'entry_id',$this->entry_id);
        	$mform->setType('entry_id', PARAM_INT);

        	$mform->addElement('hidden', 'report_id',$this->report_id);
        	$mform->setType('report_id', PARAM_INT);

        	$mform->addElement('hidden', 'user_id',$this->user_id);
        	$mform->setType('user_id', PARAM_INT);

            $mform->addElement('hidden', 'course_id',$this->course_id);
            $mform->setType('course_id', PARAM_INT);

            $mform->addElement('hidden', 'current_page',$this->currentpage);
            $mform->setType('current_page', PARAM_INT);

            $mform->addElement('hidden', 'page_data',1);
            $mform->setType('page_data', PARAM_INT);

            if ($count  =   $this->dbc->element_type_exists($this->report_id,'block_ilp_plu_pb')) {
                $pagebreakcount =   $count;
            }

            $breaksfound    =   0;

        	if (!empty($reportfields)) {

                $breakcounter = 0;
                $fieldcounter = 0;
                foreach ($reportfields as $field) {

                    $fieldcounter ++;
                    //get the plugin record that for the plugin
                    $pluginrecord	=	$dbc->get_plugin_by_id($field->plugin_id);

                    //take the name field from the plugin as it will be used to call the instantiate the plugin class
                    $classname = $pluginrecord->name;

                    if ($pluginrecord->tablename == 'block_ilp_plu_pb') {
                        if ($breakcounter) {
                            $mform->addElement('html', '</div>');
                        }
                        $breakcounter ++;
                        $marker = '<div class="hiddenelement pagebreak-marker pagebreak-marker-' . $breakcounter . '">';

                        $mform->addElement('html', $marker);
                        continue;
                    }
                    include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

                    if(!class_exists($classname)) {
                        print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
                    }

                    //instantiate the plugin class
                    $pluginclass	=	new $classname();

                    if (method_exists($pluginclass, 'set_user_id')) {
                        $pluginclass->set_user_id($this->user_id);
                    }

                    $pluginclass->load($field->id);

                    //call the plugins entry_form function which will add an instance of the plugin
                    //to the form
                    $pluginclass->entry_form($mform);
                    if (count($reportfields) == $fieldcounter && $breakcounter) {
                        $mform->addElement('html', '</div>');
                    }

                    if($classname!='ilp_element_plugin_state')
                    {
                       $this->canfreeze[]=$pluginclass->reportfield_id.'_field';
                    }
                }

        	}

//	        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
//	        $buttonarray[] = &$mform->createElement('cancel');

            $prev_attrs = array('class'=>'hiddenelement');
            //only show previous if this is not the first page
            if (!empty($pagebreakcount))    $buttonarray[] = &$mform->createElement('submit', 'previousbutton', get_string('previous','block_ilp'), $prev_attrs);
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
            $buttonarray[] = &$mform->createElement('cancel');

            //only show next if this is not the last page
            if (!empty($pagebreakcount))    $buttonarray[] = &$mform->createElement('submit', 'nextbutton', get_string('next','block_ilp'));

            $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
            //close the fieldset
	        $mform->addElement('html', '</div></fieldset>');
		}

//The form represents a report which has passed its editing deadline.
//Freeze all the visible forms except "state" drop downs.
        function expired()
        {
           $mform =& $this->_form;
           foreach($this->canfreeze as $f)
           {
              $mform->freeze($f);
           }
        }


		/**
     	 * TODO comment this
     	 */
		function process_data($data) {
			global	$CFG,$USER;

            $data   =    (!is_object($data))    ?   (object) $data : $data;

			//get the id of the report
			$report_id	=	$data->report_id;

			//get the id of the entry  if known
			$entry_id	=	$data->entry_id;

			//get the id of the user
			$user_id	=	$data->user_id;

			//get the id of the course
			$course_id	=	$data->course_id;
			$result = true;

			if (empty($entry_id)) {
				//create the entry
				$entry					=	new stdClass();
				$entry->report_id		=	$report_id;
				$entry->creator_id		=	$USER->id;
				$entry->user_id			=	$user_id;
				//TODO: do we need to save course ?
				//$entry->course

				$entry_id	=	$this->dbc->create_entry($entry);

			} else {
				//update the entry
				//as there is nothing to update but we want the entries timemodifed
				//to be updated we will just re-add the report_id
				$entry					=	new stdClass();
				$entry->id				=	$entry_id;
				$entry->report_id		=	$report_id;
				if (!$this->dbc->update_entry($entry)) $result = false;

			}


			//get all of the fields in the current report, they will be returned in order as
			//no position has been specified
			$reportfields		=	$this->dbc->get_report_fields_by_position($report_id);

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

				$pluginclass->load($field->id);

				//call the plugins entry_form function which will add an instance of the plugin
				//to the form
                if (method_exists($pluginclass, 'set_course_id')) {
                    $pluginclass->set_course_id($course_id);
                }

				if ($pluginclass->is_processable())	{
					if (!$pluginclass->entry_process_data($field->id,$entry_id,$data)) $result = false;
				}
			}

			return $result;
		}

		/**
     	 * TODO comment this
     	 */
    	function definition_after_data() {

    	}


    /**
     * Returns data submitted from previous pages on the current form.
     * (this feature is not available using the normal get_data and get_submitted data functions)
     *
     * @param int $report_id the id of the mutlipage form that we want to get submitted data for
     *
     * @return mixed array or null if not data is found
     */
    function get_multipage_data($report_id)   {

        $normdata   =   $this->get_submitted_data();

        $reportfields =   $this->dbc->get_report_fields_by_position($report_id);

        if (!empty($reportfields))   {

            $elementnames   =   array();
            $data           =   array();

            foreach ($reportfields as $rf)    {
                $elementnames[]     =   $rf->id."_field";
            }

            $elementnames[] =   'previousbutton';
            $elementnames[] =   'nextbutton';

            $submiteddata  =   array_merge($_GET,$_POST);

            foreach($submiteddata as $key => $sd)   {
                foreach ($elementnames as $en)  {


                    //we will find anything with a name beginning with the code name of a field
                    //e.g 9_field 9_field_test will both be found and returned
                    if (preg_match("/\b{$en}/i",$key))  {


                       if (is_array($sd)){
                          if (count($sd)==3){
                             if ((array_key_exists('day', $sd))&&(array_key_exists('month', $sd))&&(array_key_exists('year', $sd))){
                                //convert time to timestamp
                              $sd =  make_timestamp($sd['year'],
                                     $sd['month'],
                                     $sd['day'],
                                     0, 0, 0,
                                     99,true);
                             }
                          }
                       }
                        $data[$key]    =   $sd;
                    }
                }
            }

            $normdata   =    (is_array($normdata))  ?   $normdata   :   (array) $normdata;

            return (object) array_merge($normdata,$data);
        }
        return null;
    }


    function next($report_id,$currentpage) {

        global  $SESSION;

        $this->formdata  =  (empty($this->formdata))    ?  $this->get_multipage_data($report_id)   :   $this->formdata ;

        //was the next button pressed
        if (isset($this->formdata->nextbutton))   {

            $cformdata      =   $this->formdata;

            //we do not want any of the following data to be saved as it stop the pagination features from working
            if (isset($cformdata->current_page)) unset($cformdata->current_page);
            if (isset($cformdata->previousbutton))  unset($cformdata->previousbutton);
            if (isset($cformdata->nextbutton))  unset($cformdata->nextbutton);

            //save all data submitted from last page

            //check if the page data array has been created in the session
            if (!isset($SESSION->pagedata)) $SESSION->pagedata  =   array();

            //create a array to hold the page temp_data
            if (!isset($SESSION->pagedata[$report_id]))   $SESSION->pagedata[$report_id] = array();

            if (!isset($SESSION->pagedata[$report_id][$currentpage-1]))   {
                //if no data has been saved for the current page save the data to the dd
                //and save the key
                $SESSION->pagedata[$report_id][$currentpage-1] = $this->dbc->save_temp_data($cformdata);
            } else {
                //if data for this page has already been saved get the key and update the record
                $tempid =   $SESSION->pagedata[$report_id][$currentpage-1];
                $this->dbc->update_temp_data($tempid,$cformdata);
            }

            //set the data in the page to what it equaled before
            if (isset($SESSION->pagedata[$report_id][$currentpage])) {
                $tempdata   =   $this->dbc->get_temp_data($SESSION->pagedata[$report_id][$currentpage]);
                $this->set_data($tempdata);
            }
        }
    }

    /**
     * Carrys out operations necessary if the form is a multipage form and the previous button has been pressed
     */
    function previous($report_id,$currentpage) {
        global $SESSION;

        $this->formdata  =  (empty($this->formdata))    ?  $this->get_multipage_data($report_id)   :   $this->formdata ;

        if (isset($this->formdata->previousbutton)) {

            $cformdata      =   $this->formdata;

            //we do not want any of the following data to be saved as it stop the pagination features from working
            if (isset($cformdata->current_page)) unset($cformdata->current_page);
            if (isset($cformdata->previousbutton))  unset($cformdata->previousbutton);
            if (isset($cformdata->nextbutton))  unset($cformdata->nextbutton);


            if (!isset($SESSION->pagedata[$report_id][$currentpage+1]))   {
                //if no data has been saved for the current page save the data to the dd
                //and save the key
                $SESSION->pagedata[$report_id][$currentpage+1] = $this->dbc->save_temp_data($cformdata);
            } else {
                //if data for this page has already been saved get the key and update the record
                $tempid =   $SESSION->pagedata[$report_id][$currentpage+1];
                $this->dbc->update_temp_data($tempid,$cformdata);
            }

            //set the data in the page to what it equaled before
            if (isset($SESSION->pagedata[$report_id][$currentpage])) {
                $tempdata   =   $this->dbc->get_temp_data($SESSION->pagedata[$report_id][$currentpage]);
                $this->set_data($tempdata);
            }
        }
    }

    function submit($report_id)   {

        global  $SESSION;

        //get all of the submitted data
        $this->formdata  = $this->get_multipage_data($report_id);
        $darray     =   array();

        if (!empty($SESSION->pagedata[$report_id]))   {
            foreach($SESSION->pagedata[$report_id] as $tempid) {
                $tempdata   =   $this->dbc->get_temp_data($tempid);
                $tempdata   =   (is_array($tempdata)) ? $tempdata   :  (array) $tempdata;
                $darray     =   array_merge($darray,$tempdata);
            }
        }

        $formdata   =   (is_array($this->formdata)) ? $this->formdata   :  (array) $this->formdata;

        $formdata =   array_merge($formdata, $darray);

        return $this->process_data($formdata);
    }

}
