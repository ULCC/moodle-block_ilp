<?php
/**
 * This class enables the rules in a report to be checked to see if the given student
 * can make a new entry
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ilp
 * @version 2.0
 */


class ilp_report_rules  {

    var $report_id;

    var $student_id;

    var $dbc;


    function __construct($report_id,$student_id)  {
        global  $CFG;

        $this->report_id    =   $report_id;
        $this->student_id   =   $student_id;

        // include the assmgr db
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        // instantiate the assmgr db
        $this->dbc = new ilp_db();
    }


    function report_availabilty()   {

        $report     =       $this->dbc->get_report_by_id($this->report_id);

        //if a maximum number of entries has been set lets see if the student has reached this number
        if (!empty($report->reportmaxentries))  {
            $studententries   =   $this->dbc->count_report_entries($this->report_id,$this->student_id);

              if ($studententries >= $report->reportmaxentries) {
                  $extension  =   $this->extension_check('reportmaxentries');

                  if (empty($extension) || $studententries >= $extension->value) {
                      $temp             =   new stdClass();
                      $temp->entries    =   $studententries;
                      $temp->maxentries    =  (!empty($extension))? $extension->value : $report->reportmaxentries;
                      return array('result'=>false,'text'=>get_string('exceededmaxentries','block_ilp',$temp));
                  }
              }

        }

        //if this report has a lock date check if the date has passed
        if ($report->reporttype ==  ILP_RT_RECURRING_FINALDATE  || $report->reporttype == ILP_RT_FINALDATE)   {

            if ( $report->reportlockdate < time() )   {
                //find out if this student has been given a report extension
                $extension  =   $this->extension_check('reportlockdate');
                if (empty($extension) || $extension->value < time()) {
                    $temp               =   new stdClass();
                    $temp->expiredate   =  (!empty($extension))? date('d-m-Y',$extension->value) : date('d-m-Y',$report->reportlockdate);
                    return array('result'=>false,'text'=>get_string('reportlocked','block_ilp',$temp));
                }
            }
        }

        //if the report is a recurring report
        if ($report->reporttype ==  ILP_RT_RECURRING || $report->reporttype ==  ILP_RT_RECURRING_FINALDATE)   {
            //

            $recurringstart  =   0;

            if ($report->recurstart == ILP_RECURRING_REPORTCREATION)   {
                //rules started at report creation
                $recurringstart  =   $report->timecreated;
            }   else if ($report->recurstart == ILP_RECURRING_SPECIFICDATE) {
                //rules started at specific date
                $recurringstart  =   $report->recurdate;
            }  else {
                //rules started at first entry
                $studententries =   $this->dbc->get_user_report_entries($this->report_id,$this->student_id);

                if (!empty($studententries))    {
                    //get the creation time of the first user entry
                   $recurringstart  = reset($studententries);
                   $recurringstart = $recurringstart->timecreated;
                }
            }


            if (!empty($recurringstart)) {

                $recurringperiod    =   $this->recurring_period($recurringstart,$report->recurfrequency);

                $entriescount       =   $this->dbc->count_report_entries($this->report_id,$this->student_id,$recurringperiod['start'],$recurringperiod['end']);

                if (!empty($entriescount) && $entriescount >= $report->recurmax) {
                    $extension  =   $this->extension_check('recurmax');
                    if (empty($extension) || $extension->value <= $entriescount) {
                        $temp             =   new stdClass();
                        $temp->entries    =   $entriescount;
                        $temp->maxentries   =  (!empty($extension))? $extension->value : $report->recurmax;
                        return array('result'=>false,'text'=>get_string('recurexceededmaxentries','block_ilp',$temp));
                    }
                }
            }



        }
        return array('result'=>true,'text'=>NULL);
    }


    /**
     * checks the ilp preference table to see if a user has been given a extension on the current report
     * if a preference is found that has the same value in the param field as the one given it is returned
     *
     * @param   $param  the exact param that will be returned
     * @param   $action the action that will be looked for
     * @return bool
     */
    function extension_check($param,$action="report_extension")  {

        $preferencerecords =   $this->dbc->get_preferences($this->report_id,null,$action,$this->student_id);
        $preference =   false;

        foreach($preferencerecords as $p)  {

            if ($p->param == $param)    {
                $preference =   $p;
                break;
            }
        }
        return (!empty($preference)) ? $preference  :   false;
    }


    /**
     * returns the start and end dates for the recurring period that the user is currently in
     *
     * @param $recurringstart        timestamp of the date when the recurring period started
     * @param $frequency    the frequency of the recurring period
     *
     */
     function recurring_period($recurringstart,$frequency)  {

            if ($frequency  ==  ILP_RECURRING_DAY) {
                //$start
                $strstart   =   date("d-m-Y",$recurringstart);
                $recurringend   =   strtotime("{$strstart} + 1 day");
                if ( time() > $recurringstart && time() < $recurringend )   {
                    return array('start'=>$recurringstart,'end'=>$recurringend);
                } else  {
                    return $this->recurring_period($recurringend,$frequency);
                }
            } else {
                $strstart   =   date("d-m-Y",$recurringstart);
                $recurringend   =   strtotime("{$strstart} + $frequency weeks");
                if ( time() > $recurringstart && time() < $recurringend )   {
                    return array('start'=>$recurringstart,'end'=>$recurringend);
                } else  {
                    return $this->recurring_period($recurringend,$frequency);
                }
            }
     }

    function can_add_extensions($report_id=NULL){

        $report_id  =   (empty($report_id)) ?   $this->report_id    :   $report_id;

        $report         =   $this->dbc->get_report_by_id($report_id);

        return(empty($report) || ($report->frequency ==1 && $report->reporttype==1 && $report->reportmaxentries==null))?false:true;
    }




}