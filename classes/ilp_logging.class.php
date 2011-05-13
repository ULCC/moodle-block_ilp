<?php

/**
 * Class to enable the logging of user actions in the assessment manager
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */

class ilp_logging {

    public $dbc;

    /**
     * Protected member function to update a record and
     * add a entry into the assmgr_log table
     *
     * @param string $table The name of the table being updated
     * @param object $paramsobject the object that contains the data that will be used in the update
     * @return mixed The success of the action
     */
    protected function update_record($table,$paramsobj) {
        // set the timestamp

        $paramsobj->timemodified = time();


        $currobject = (!empty($paramsobj->id)) ? $this->dbc->get_record($table, array('id' => $paramsobj->id)) : false ;


         $success = $this->dbc->update_record($table, $paramsobj);
         
         /**** REINSTATE CODE WHEN add_to_audit has been modified to work with 
          *    ilp
          */
         //$this->add_to_audit($table,LOG_UPDATE,$paramsobj,$currobject);
         return $success;
     }

    /**
     * Protected member function to create a record and add an entry into the
     * assmgr_log table
     *
     * @param string $table The name of the table where the record will be created
     * @param object $paramsobject the object that contains the data that will be used to create the record
     * @return mixed The id of the insert or false if unsuccessful
     */
    protected function insert_record($table,$paramsobj) {
        // set the timestamp
        $paramsobj->timecreated = $paramsobj->timemodified = time();
        
        $paramsobj->id = $this->dbc->insert_record($table, $paramsobj);

        if ($paramsobj->id) {
        	
        	/**** REINSTATE CODE WHEN add_to_audit has been modified to work with 
          *    ilp
          */
            //$this->add_to_audit($table,LOG_ADD,$paramsobj);
        }

        return $paramsobj->id;
     }

    /**
     * Protected member function to delete a record and add a entry into the
     * assmgr_log table
     *
     * @param string $table The name of the table where the record will be created
     * @param mixed $params the object (or array) that contains the data that will be used to create the record
     * @return mixed The success of the action
     */
    protected function delete_records($table,$params) {
         //deletes are often carried out with an array
         //audit expects a object so a quick conversion
         //is needed
         //$log_class = new assmgr_logging();
         if (is_array($params)) $auditobj = (array) $params;
         $deleteobject = $this->dbc->get_records($table, $params );
         $success = $this->dbc->delete_records($table, $params);

         if (!empty($deleteobject)) {

             foreach ($deleteobject as $delobj) {
             	
             	/**** REINSTATE CODE WHEN add_to_audit has been modified to work with 
          		*    ilp
          		*/
                //$this->add_to_audit($table,LOG_DELETE,$delobj);
             }

         }

         return $success;
     }

    /**
     * Private member function to delete a record and add a entry into the
     * assmgr_log table
     *
     * @param string $table The name of the table where the record will be created
     * @param object $select the object that contains the data that will be used to create the record
     * @return mixed The success of the action
     */
    protected function delete_records_select($table,$select) {
        //$log_class = new assmgr_logging();
         $deleteobject = $this->dbc->get_records_select($table, $select );
         $success = $this->dbc->delete_records_select($table, $select);

         if (!empty($deleteobject)) {

             foreach ($deleteobject as $delobj) {
                
             	/**** REINSTATE CODE WHEN add_to_audit has been modified to work with 
          		*    ilp
          		*/
             	//$this->add_to_audit($table,LOG_DELETE,$delobj);
             }

         }

         return $success;

     }

    /**
     * Adds a entry to the audit table
     *
     * @param string $table The name of the table where the record will be created
     * @param string $action the action that took place
     * @param object $newobject the object that contains the data that will be used to create the record
     * @param object $currobject the object that represents the state the record used to be in
     * @return mixed The success of the action
     */
    function add_to_audit($table, $action, $newobject, $currobject=NULL)  {
        global $USER;
        $attributes    =   array();

        switch($table) {

            case 'block_assmgr_evidence':
                $attributes =    array('id', 'description');
                break;

            case 'block_assmgr_resource':
                $attributes =    array('record_id');
                break;

            case 'block_assmgr_confirmation':
                $attributes =    array('status', 'feedback');
                break;

            case 'block_assmgr_sub_evid_type':
                $attributes =    array('evidence_type_id');
                break;

            case 'block_assmgr':
                $attributes =    array('evidence_id', 'hidden');
                break;

            case 'block_assmgr_claim':
                $attributes =    array('outcome_id');
                break;

            case 'block_assmgr_portfolio':
                $attributes =    array('course_id', 'needassess', 'verified');
                break;

            case 'portfolio_grade':
                $attributes =    array('str_grade', 'comments');
                break;
            case 'block_assmgr_grade':
                $attributes =    array('grade','feedback','outcome_id');
                break;


            case 'portfolio_outcome_grade':
                $attributes =    array('str_grade');
                break;

            case 'submission_comment':
                $attributes =    array('feedback');
                break;

            case 'assessment_date':
                $attributes =    array('date', 'comment');
                break;

            case 'block_assmgr_verification':
                $attributes = array('category_id','course_id','assessor_id','complete');
                break;

            case 'block_assmgr_verify_form':
                $attributes = array('accurate','accurate_comment','constructive','constructive_comment','needs_amending','amendment_comment','actions');
                break;

            default:
                $attributes = array();
        }

        $now = time();

        foreach ($newobject as $key => $val) {
            if ((in_array($key,$attributes) &&
                ($this->diff_object($table,$newobject,$currobject,$key,$action) || $action == LOG_DELETE))) {

                $log = new object();
                $log->creator_id = $USER->id;

                $log->candidate_id = $this->log_candidate($table,$newobject,$action);
                $log->type = $this->action_type($table,$action,$log->candidate_id,$log->creator_id);

                //record id pertain to the actual submission or
                $log->record_id = $this->log_record_id($table,$newobject);

                $log->entity = $this->entity_type($table,$newobject);
                $log->attribute = $this->attribute_type($table,$newobject,$key);

                $log->course_id = $this->log_course($table,$newobject);
                if ($action != LOG_ADD) {
                    $log->old_value = ($action != LOG_DELETE ) ? $this->interpret_value($table,$currobject,$key,$this->get_old_value($table,$newobject,$currobject,$key,$action)) : $this->interpret_value($table,$newobject,$key,$val);
                }
                $log->new_value = ($action != LOG_DELETE ) ? $this->interpret_value($table,$newobject,$key,$val) : NULL;
                $log->timecreated = $now;
                $log->timemodified = $now;

                $this->dbc->insert_record('block_assmgr_log',$log);


            }
        }
    }

    /**
     * returns the candidate for the given record
     *
     * @param string $table The name of the table that will hold the object
     * @param object $obj the object that contains the data that will be logged
     * @return mixed The success of the action
     */
    private function log_candidate($table,$obj) {
        global $USER;

         switch ($table) {
             case 'submission_outcome_grade':

             case 'block_assmgr_sub_evid_type':
                $submission = $this->get_submission_by_id($obj->submission_id);
                if (!empty($submission)) $evidence = $this->get_evidence($submission->evidence_id);
                return (!empty($evidence)) ? $evidence->candidate_id : $USER->id;

            case 'block_assmgr_resource':
                $evidence = $this->get_evidence($obj->evidence_id);
                return (!empty($evidence)) ? $evidence->candidate_id : $USER->id;

            case 'block_assmgr_portfolio':

            case 'portfolio_outcome_grade':

            case 'submission_comment':

            case 'portfolio_grade':

            case 'assessment_date':
                return $obj->candidate_id;
                break;

            case 'block_assmgr_verification':

            case 'block_assmgr_verify_form':
                return NULL;

             default:
                 return $USER->id;
         }
     }

    /**
     * returns the record id that the given obj pertains to
     *
     * @param string $table The name of the table that will hold the object
     * @param object $obj the object that contains the data that will be logged
     * @return mixed The success of the action (object), false if it failed or null as default
     */
    private function log_record_id($table, $obj) {

        switch($table) {
            case 'block_assmgr_resource':

            case 'block_assmgr_confirmation':
                $record_id = (!empty($obj->evidence_id)) ? $obj->evidence_id : NULL;
                break;

            case 'block_assmgr_sub_evid_type':

            case 'block_assmgr_claim':

            case 'block_assmgr_grade':

            case 'submission_comment':
                $record_id = (!empty($obj->submission_id)) ? $obj->submission_id : NULL;
                break;

            case 'block_assmgr':

            case 'block_assmgr_evidence':

            case 'block_assmgr_portfolio':
                $record_id = (!empty($obj->id)) ? $obj->id : NULL;
                break;

            case 'assessment_date':
                if (!empty($obj->portfolio_id) || !empty($obj->group_id)) {
                    $record_id  = (!empty($obj->group_id)) ? $obj->group_id : $obj->portfolio_id;
                } else {
                    $record_id = (!empty($obj->course_id)) ? $obj->course_id : NULL;
                }
                break;

            case 'portfolio_grade':

            case 'portfolio_outcome_grade':
                $portfolio = $this->get_portfolio($obj->candidate_id, $obj->course_id);
                $record_id = $portfolio->id;
                break;
//TODO I m not sure whether block_assmgr_verification and block_assmgr_verify_form cases should
//use the verification id or the portfolio id/submission id
            case 'block_assmgr_verification':
                $record_id = $obj->id;
                break;

           case 'block_assmgr_verify_form':
                $record_id = $obj->verification_id;
                break;

            default:
                $record_id = NULL;
        }
        return $record_id;
     }


     /**
     * returns the id of the course that the given obj was made in
     *
     * @param string $table The name of the table that will hold the object
     * @param object $obj the object that contains the data that will be logged
     * @return mixed The success of the action
     */
     private function log_course($table,$obj) {

        $logtables = array('block_assmgr','block_assmgr_claim','block_assmgr_portfolio','block_assmgr_sub_evid_type'
                            ,'submission_comment','submission_outcome_grade','portfolio_outcome_grade','portfolio_grade');

          if (in_array($table,$logtables)) {

              switch ($table) {
                  case 'submission_outcome_grade':

                  case 'portfolio_outcome_grade':

                  case 'portfolio_grade':

                  case 'block_assmgr_portfolio':

                  case 'submission_comment':

                  case 'assessment_date':
                      return $obj->course_id;
                      break;

                  case 'block_assmgr_sub_evid_type':

                  case 'block_assmgr_claim':
                      $subobj = $this->get_submission_by_id($obj->submission_id);
                      if (!empty($subobj)) $portfolio_id = $subobj->portfolio_id;

                      //note no break as the course id will be retrived below
                  case 'block_assmgr':
                      if (empty($portfolio_id)) $portfolio_id = $obj->portfolio_id;
                      $portobj = $this->get_portfolio_by_id($portfolio_id);
                      if (!empty($portobj)) return $portobj->course_id;
                      break;

                  case 'block_assmgr_verify_form':
                      if (!empty($obj->portfolio_id)) {
                          $portobj = $this->get_portfolio_by_id($obj->portfolio_id);
                          if (!empty($portobj)) return $portobj->course_id;
                      }
                      if (!empty($obj->submission_id)) {
                          $subobj = $this->get_submission_by_id($obj->submission_id);

                          $portobj = (!empty($subobj)) ? $this->get_portfolio_by_id($subobj->portfolio_id) : NULL;
                          if (!empty($portobj)) return $portobj->course_id;
                      }


                      break;




                }
          }

          return NULL;
     }

    /**
     * Interprets the value of the attribute given
     * so that it can be read in human form
     *
     * @param string $table The name of the table that will hold the object
     * @param object $obj the object that contains the data that will be logged
     * @param string $attrib the name of the attribute
     * @param int $value the value of the attribute
     * @return mixed The success of the action
     */
    private function interpret_value($table,$obj,$attrib,$value) {
        //TODO change name of secondobj

        $new_value = NULL;

        switch($table) {
            case 'block_assmgr':
                 $new_value = $this->interpret_submission_value($obj,$attrib,$value);
                 break;

            case 'portfolio_grade':

            case 'portfolio_outcome_grade':

            case 'block_assmgr_grade':

            case 'block_assmgr_claim':
                $new_value = $this->interpret_grade_value($obj,$attrib,$value);
                break;

            case 'block_assmgr_resource':
                $new_value = $this->interpret_resource_value($obj,$attrib,$value);
                break;

            case 'block_assmgr_confirmation':
                $new_value = $this->interpret_confirmation_value($attrib,$value);
                break;

            case 'block_assmgr_evidence':
                $new_value = $this->interpret_evidence_value($attrib,$value);
                break;

            case 'block_assmgr_sub_evid_type':
                $new_value = $this->interpret_evidence_type_value($attrib,$value);
                break;

            case 'block_assmgr_portfolio':
                $new_value = $this->interpret_portfolio_value($attrib,$value);
                break;

           case 'block_assmgr_verify_form':
                $new_value = $this->interpret_verification_value($attrib,$value);
                break;

            default:
                $new_value = $value;
        }
        return trim($new_value);

     }

    /**
     * Private member function to intrepret the value of a given portfolio
     * attribute
     *
     * @param string $attrib the name of the attribute that will be interpreted
     * @param int $value the actual value that will be interpreted
     * @return mixed The attribute's interpreted value
     */
     private function interpret_portfolio_value($attrib,$value) {
           if ($attrib == 'course_id' && !empty($value)) {
               $course = $this->get_course($value);
               if (!empty($course)) return $course->shortname;
           }
           return (!empty($value)) ? $value : NULL;
     }


     /**
     * Private member function to interpret the value
     * of a given submission attribute
     *
     * @param object $obj the object that contains the value that will be interpreted
     * @param string $attrib the name of the attribute that will be interpreted
     * @param int $value the actual value that will be interpreted
     * @return mixed The attribute's interpreted value
     */
     private function interpret_submission_value($obj,$attrib,$value) {
         if (empty($obj)) return NULL;
          switch ($attrib) {
              case 'evidence_id':
                    $interpvalue = (!empty($value)) ? $this->get_evidence($value) : NULL;
                    return (!empty($interpvalue)) ? $interpvalue->name : $value;
                    break;
              case 'hidden':
                    $new_value = NULL;
                    if ($value === 0) $new_value = 'visible';
                    if ($value == 1) $new_value = 'hidden' ;
                    return $new_value;
                    break;
          }
          return $value;
     }

     /**
     * Private member function to interpret the value
     * of a given evidence value
     *
     * @param string $attrib the name of the attribute that will be interpreted
     * @param int $value the actual value that will be interpreted
     * @return mixed The attribute's interpreted value
     */

     function interpret_verification_value($attrib,$value) {
         if ($attrib == 'accurate' || $attrib == 'constructive' || $attrib == 'needs_amending') {
               if ($value == 1) {
                   return "Yes";
               } else if ($value == 0) {
                   return "No";
               }
         }
         return (!empty($value)) ? $value : NULL;


     }


     /**
     * Private member function to interpret the value
     * of a given evidence value
     *
     * @param string $attrib the name of the attribute that will be interpreted
     * @param int $value the actual value that will be interpreted
     * @return mixed The attribute's interpreted value
     */
     private function interpret_evidence_value($attrib,$value) {
         if ($attrib == 'id') {
               $evidence = $this->get_evidence($value);
               return (!empty($evidence)) ? $evidence->name : $value;
         }
         return (!empty($value)) ? $value : NULL;
     }

     /**
     * Private member function to interpret the value
     * of a given grade attribute
     *
     * @param object $obj the object that contains the value that will be interpreted
     * @param string $attrib the name of the attribute that will be interpreted
     * @param int $value the actual value that will be interpreted
     * @return mixed The attribute's interpreted value
     */
     private function interpret_grade_value($obj, $attrib, $value) {

         if ((empty($obj) || empty($value)) && $attrib != 'rawgrade') return NULL;

         switch ($attrib) {
             case 'outcome_id':
                    $outcomeobj = $this->get_outcome($obj->outcome_id);
                    return (!empty($outcomeobj)) ? $outcomeobj->shortname : $value;
                    break;

             case 'submission_id':
                      $submission = $this->get_submission_by_id($value);
                      if (!empty($submission)) $evidence = $this->get_evidence($submission->evidence_id);
                      return (!empty($evidence)) ? $evidence->name : $value;
                      break;

             case 'grade':
                    $outcomeobj = $this->get_outcome($obj->outcome_id);
                    // TODO this should be given the optional gradepass param
                    $scaleobj = $this->get_scale($outcomeobj->scaleid);
                    // TODO this should be using $scale->render_scale_item()
                    $scale = explode(',',$scaleobj->scale);
                    return  ($value-1 >= 0) ? $scale[$value-1] : $scale[0];
             default:
                   return $value;
                   break;
          }
     }

     /**
     * Private member function to interpret the value
     * of a given resource attribute
     *
     * @param string $attrib the name of the attribute that will be interpreted
     * @param int $value the actual value that will be interpreted
     * @return mixed The attribute's interpreted value
     */
     private function interpret_confirmation_value($attrib, $value) {

         if (empty($obj) || empty($value)) return NULL;

         if ($attrib == 'status') {

             switch ($value) {
                case CONFIRMATION_CONFIRMED:
                    return get_string('confirmed', 'block_assmgr');
                    break;

                case CONFIRMATION_PENDING:
                    return get_string('pending', 'block_assmgr');
                    break;

                case CONFIRMATION_REJECTED:
                    return get_string('rejected', 'block_assmgr');
                    break;
             }
         }
         return $value;
     }

     /**
      * Private member function to interpret the value
      * of a given evidence type attribute. Currently only works for 'evidence_type_id'
      *
      * @param string $attrib the name of the attribute that will be interpreted
      * @param int $value the actual value that will be interpreted
      * @return mixed The attribute's interpreted value
      */
     private function interpret_evidence_type_value($attrib, $value) {

         $evidence_name = '';

         if (empty($value)) {
             return NULL;
         }

         if ($attrib == 'evidence_type_id') {

             // get the evidence type with this id
             $evidence_type = $this->get_evidence_types($value);
             foreach ($evidence_type as $et) {
                $evidence_name = get_string($et->name, 'block_assmgr');
             }

         }
         return (empty($evidence_name)) ? $value : $evidence_name;
     }

     /**
      * Private member function to interpret the value
      * of a given resource attribute. Turns constants into text.
      *
      * @param string $attrib the name of the attribute that will be interpreted
      * @param int $value the actual value that will be interpreted
      * @return mixed The attribute's interpreted value
      */
     private function interpret_resource_value($obj,$attrib,$value) {
         if (!empty($obj->resource_type_id)) {
            $resource_type = $this->get_resource_type($obj->resource_type_id);
            $resource_fields = new $resource_type->name;
            $resource_fields->load($obj->id);

            return str_replace("'","\'",$resource_fields->get_link());
         }
         return NULL;
     }

     /**
     * Private member function to check if the given object attrib differs
     * from the value of its record field in the database
     *
     * @param string $table The name of the table where the record will be created
     * @param object $newobj the object that contains the new data to be checked
     * @param object $currobj the object containing the data that is currently in the DB
     * @param string $attrib the name of the attribute of the objects to be compared
     * @param int $action the database operation about to be performed
     * @return bool The success of the action
     */
     private function diff_object($table,$newobj,$currobj,$attrib,$action) {
         if ($action == LOG_UPDATE || $action == LOG_ASSESSMENT) {
             if (empty($newobj->$attrib)) return false;
             if (empty($currobj->$attrib)) return true;
             return ( $newobj->$attrib != $currobj->$attrib ) ? true : false;
         }

         return true;
     }


     /**
     * Private member function to return the previous value of the specified attribute
     *
     * @param string $table The name of the table where the record will be created
     * @param object $obj the object that contains the data that will be used to create the record
     * @param object $currobj the object containing the data that is currently in the DB
     * @param string $attrib the name of the attribute of the objects to be compared
     * @param string $action the name of the database operation about to be performed
     * @return mixed The value or NULL
     */
     private function get_old_value($table,$obj,$currobj,$attrib,$action) {
          if ($table == 'block_assmgr_resource' && !empty($currobj)) {

            $resource_type = $this->get_resource_type($currobj->resource_type_id);
            $resource_fields = new $resource_type->name;
            $resource_fields->load($currobj->record_id);
            return $resource_fields->get_link();
          }
          return (!empty($currobj->$attrib)) ? $currobj->$attrib : NULL;
     }

     /**
     * function to return the action type of given action
     *
     * @param string $table the table the action is targetting
     * @param int $action the action whose type we want
     * @param int $candidate_id the user id of the candidate
     * @param int $candidate_id the user id of the creator
     * @return string the attribute type
     */
     private function action_type($table,$action,$candidate_id,$creator_id) {
            switch($action) {
                case LOG_ADD:
                    if ($table == 'assessment_date') return get_string('setassessmentdate', 'block_assmgr');
                    if ($table == 'block_assmgr_verify_form' || $table == 'block_assmgr_verification') return get_string('verification', 'block_assmgr');
                    if ($table == 'block_assmgr_grade') return get_string('assessment', 'block_assmgr');
                    return ($table == 'block_assmgr_sub_evid_type' && ($candidate_id != $creator_id)) ? get_string('assessment', 'block_assmgr') : get_string('create', 'block_assmgr');

                    break;
                case LOG_UPDATE:
                    if ($table == 'assessment_date') return get_string('updateassessmentdate', 'block_assmgr');
                    if ($table == 'block_assmgr_verify_form' || $table == 'block_assmgr_verification') return get_string('verification', 'block_assmgr');
                    return get_string('update', 'block_assmgr');
                    break;
                case LOG_DELETE:
                    if ($table == 'assessment_date') return get_string('cancelassessmentdate', 'block_assmgr');
                    return get_string('delete', 'block_assmgr');
                    break;
                case LOG_VIEW:
                    return get_string('view', 'block_assmgr');
                    break;
               case LOG_ASSESSMENT:
                    return get_string('assessment', 'block_assmgr');
                    break;
               case LOG_CLAIM:
                    return get_string('claim', 'block_assmgr');
                    break;
               case LOG_VERIFY:
                    return get_string('verification', 'block_assmgr');
                    break;

               default:
                   return get_string('unknown', 'block_assmgr');
            }
     }


     /**
     * function to return the attribute type of given key in the data object
     *
     *
     * @param string $table the table the attribute was taken from
     * @param object $obj The object holding the data
     * @param string $key the attribute name
     * @return string the attribute type
     */
     private function attribute_type($table,$obj,$key) {
         switch($table) {
           case 'block_assmgr_resource':
               $resource_type = $this->get_resource_type($obj->resource_type_id);
                $resource = new $resource_type->name;
                return $resource->audit_type();
               break;

            case 'block_assmgr_evidence':
                return  ($key == 'id') ? get_string('evidencename', 'block_assmgr') : get_string('evidence', 'block_assmgr').' '.$key;
                break;

            case 'block_assmgr_confirmation':
                return get_string('confirmationstatus', 'block_assmgr');
                break;

            case 'block_assmgr_sub_evid_type':
                return get_string('submissionevidencetype', 'block_assmgr');
                break;

            case 'block_assmgr':
                if ($key == 'evidence_id') return get_string('submittedevidence', 'block_assmgr');
                if ($key == 'assess_ready') return get_string('submissionhiddenstatus', 'block_assmgr');
                break;

            case 'block_assmgr_claim':
                if ($key == 'outcome_id') return get_string('claim', 'block_assmgr');
                break;

            case 'block_assmgr_portfolio':
                if ($key == 'course_id') return get_string('course', 'block_assmgr');
                break;

            case 'block_assmgr_grade':

            case 'portfolio_outcome_grade':
                if ($key == 'str_grade') {
                    if (!empty($obj->outcome_id)) {
                        $record = $this->get_outcome($obj->outcome_id);
                        if (!empty($record)) return get_string('outcome', 'block_assmgr').': '.$record->shortname.' '.get_string('grade', 'block_assmgr').':';
                    }
                }
                return $key;

            case 'portfolio_grade':
                if ($key == 'str_grade') return get_string('grade', 'block_assmgr');
                break;

            case 'assessment date':
                if ($key == 'date') return get_string('assessmentdate', 'block_assmgr');
                if ($key == 'comment') return get_string('assessmentdatecomment', 'block_assmgr');
                break;

            case 'block_assmgr_verification':
                if ($key == 'category_id') return get_string('verificationcategory', 'block_assmgr');
                if ($key == 'course_id') return get_string('verificationcourse', 'block_assmgr');
                if ($key == 'assessor_id') return get_string('verificationassessor', 'block_assmgr');
                if ($key == 'complete') return get_string('verificationcomplete', 'block_assmgr');
                break;

            case 'block_assmgr_verify_form':
                if ($key == 'accurate') return get_string('verificationaccurate', 'block_assmgr');
                if ($key == 'accurate_comment') return get_string('verificationaccuratecom', 'block_assmgr');
                if ($key == 'constructive') return get_string('verificationconstructive', 'block_assmgr');
                if ($key == 'constructive_comment') return get_string('verificationconstructivecom', 'block_assmgr');
                if ($key == 'needs_amending') return get_string('verificationamendment', 'block_assmgr');
                if ($key == 'amendment_comment') return get_string('verificationamendmentcom', 'block_assmgr');
               if ($key == 'actions') return get_string('verificationactions', 'block_assmgr');

               break;
        }
        return $key;


     }


     /**
     * function to return the entity type of records in the given table
     *
     * @param string $table The name of the table where the record will be created
     * @param object $obj the object with the new data (optional)
     * @return string the entity type 'evidence', 'submission' or 'portfolio'
     */
     private function entity_type($table, $obj=NULL) {

        switch($table) {
            case 'block_assmgr_evidence':

            case 'block_assmgr_resource':

            case 'block_assmgr_confirmation':
                return get_string('evidence', 'block_assmgr');
                break;

            case 'block_assmgr_sub_evid_type':

            case 'block_assmgr':

            case 'block_assmgr_claim':

            case 'block_assmgr_grade':

            case  'submission_comment':

                return get_string('submission', 'block_assmgr');
                break;

            case 'block_assmgr_portfolio':

            case 'portfolio_outcome_grade':

            case 'portfolio_grade':
                return get_string('portfolio', 'block_assmgr');

            case 'assessment_date':
                if (!empty($obj->group_id)){
                    return get_string('group', 'block_assmgr');
                } else if (!empty($obj->portfolio_id)) {
                    return get_string('portfolio', 'block_assmgr');
                } else {
                    return get_string('course', 'block_assmgr');
                }
                break;

            case 'block_assmgr_verification':
                return get_string('verificationsample','block_assmgr');

            case 'block_assmgr_verify_form':
                return (!empty($obj->submission_id)) ? get_string('submission','block_assmgr') : get_string('portfolio','block_assmgr') ;


            default:
                return get_string('unknown', 'block_assmgr');
        }

     }


}

?>