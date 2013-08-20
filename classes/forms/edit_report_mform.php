<?php 

/**
 * This class makes the form that is used to create reports 
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


class edit_report_mform extends ilp_moodleform {

		public		$report_id;
		public		$dbc;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($report_id=null) {

			global $CFG;

			$this->report_id	=	$report_id;
			$this->dbc			=	new ilp_db();
			
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_report.php?report_id={$this->report_id}");
		}
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG;

        	$dbc = new ilp_db;

        	$mform =& $this->_form;

        	$fieldsettitle = (!empty($this->report_id)) ? get_string('editreport', 'block_ilp') : get_string('createreport', 'block_ilp');
        	
        	//create a new fieldset
        	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset"><div>');
           $mform->addElement('html', '<legend >'.$fieldsettitle.'</legend>');

        	$mform->addElement('hidden', 'id');
        	$mform->setType('id', PARAM_INT);
        	
        	$mform->addElement('hidden', 'creator_id', $USER->id);
        	$mform->setType('creator_id', PARAM_INT);

            //the id of the form element creator
            $mform->addElement('hidden', 'position');
            $mform->setType('position', PARAM_INT);
            //set the field position of the field
            $mform->setDefault('position', $this->dbc->get_new_report_position());

        	// NAME element
	        $mform->addElement(
	            'text',
	            'name',
	            get_string('name', 'block_ilp'),
	            array('class' => 'form_input')
	        );
	        $mform->addRule('name', null, 'maxlength', 255, 'client');
	        $mform->addRule('name', null, 'required', null, 'client');
	        $mform->setType('name', PARAM_RAW);

            // DESCRIPTION element
            $mform->addElement(
                'htmleditor',
                'description',
                get_string('description', 'block_ilp'),
                array('class' => 'form_input', 'rows'=> '10', 'cols'=>'65')
            );

            $mform->addRule('description', null, 'maxlength', 65535, 'client');

            // commented out as causing problems with double submitting
            // $mform->addRule('description', null, 'required', null, 'client');

            $mform->setType('description', PARAM_RAW);

            //TODO add the elements to implement the frequency functionlaity

            $mform->addElement('filepicker', 'binary_icon',get_string('binary_icon', 'block_ilp'), null, array('maxbytes' => ILP_MAXFILE_SIZE, 'accepted_types' => ILP_ICON_TYPES));

	        $mform->addElement('checkbox', 'maxedit',get_String('maxedit','block_ilp'),null);
	        
	        $mform->addElement('checkbox', 'comments',get_String('allowcomments','block_ilp'),null);

            $mform->addElement('html', '<noscript>');
            $mform->addElement('html', get_string('reportnojs','block_ilp'));
            $mform->addElement('html', '</noscript>');

            // maximum entries element
            $mform->addElement(
                'text',
                'reportmaxentries',
                get_string('maxentries', 'block_ilp'),
                array('class' => 'form_input')
            );

            $radioarray[]   =&  $mform->createElement( 'radio', 'reptype', '', get_string('openend','block_ilp'), 1);
            $radioarray[]   =&  $mform->createElement( 'radio', 'reptype', '',get_string('finaldate','block_ilp') , 2);

            $mform->addGroup(
                $radioarray,
                'reptype',
                get_string('reporttype','block_ilp'),
                '',
                '',
                array('class' => 'form_input'),
                false
            );

            $mform->addRule('reptype', null, 'required', null, 'client');

            $mform->setType('reportmaxentries', PARAM_INT);

            //specific date selector
            $mform->addElement(
                'date_time_selector',
                'reportlockdate',
                get_string('reportlockdate','block_ilp'),
                array('optional' => false ),
                array('class' => 'lockdate')
            );


            $mform->addElement('checkbox', 'frequency', get_String('multipleentries','block_ilp'),null);

            $mform->addElement('advcheckbox', 'recurrent', get_string('reportrecurrence','block_ilp'),null,null,array(0,1));

            $mform->addElement('html', '<fieldset id="recurringfieldset" class="ilpfieldset">');
            $mform->addElement('html', '<legend >'.get_string('recurringrules','block_ilp').'</legend>');
            $mform->addElement('html', '<br />');
            $options    =   array();
            $options[ILP_RECURRING_DAY]  =   get_string('day','block_ilp');
            $options[ILP_RECURRING_WEEK]  =   get_string('weekly','block_ilp');
            $options[ILP_RECURRING_2WEEK]  =   "2".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_3WEEK]  =   "3".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_4WEEK]  =   "4".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_5WEEK]  =   "5".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_6WEEK]  =   "6".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_7WEEK]  =   "7".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_8WEEK]  =   "8".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_9WEEK]   =   "9".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_10WEEK]  =   "10".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_11WEEK]  =   "11".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_12WEEK]  =   "12".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_13WEEK]  =   "13".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_14WEEK]  =   "14".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_15WEEK]  =   "15".get_string('weeks','block_ilp');
            $options[ILP_RECURRING_16WEEK]   =  "16".get_string('weeks','block_ilp');

            $mform->addElement('select', 'recurfrequency', get_string('howoften', 'block_ilp'), $options, array('class' => 'recurring'));
            $mform->setType('recurfrequency', PARAM_INT);

            // maximum entries element
            $mform->addElement(
                'text',
                'recurmax',
                get_string('recurringmax', 'block_ilp'),
                array('class' => 'recurring')
            );
            $mform->setType('recurmax', PARAM_INT);
            $radioarray         =   array();
            $radioarray[]       =&  $mform->createElement( 'radio', 'recurstart', '', get_string('reportcreation','block_ilp'), ILP_RECURRING_REPORTCREATION ,array('class'=>'recurring')); ;
            $radioarray[]       =&  $mform->createElement( 'radio', 'recurstart', '', get_string('firstentry','block_ilp') , ILP_RECURRING_FIRSTENTRY ,array('class'=>'recurring'));
            $radioarray[]       =&  $mform->createElement( 'radio', 'recurstart', '',get_string('specificdate','block_ilp') , ILP_RECURRING_SPECIFICDATE ,array('class'=>'recurring'));

            $mform->addGroup(
                $radioarray,
                'recurstart',
                get_string('recurringstart','block_ilp'),
                '',
                '',
                array('class' => 'recurring'),
                false
            );

            //specific date selector
            $mform->addElement(
                'date_time_selector',
                'recurdate',
                get_string('specificstart','block_ilp'),
                array('optional'=>false),
                array('class'=>'recurring')
            );

            //DISABLE RULES
            //disable Report Recurrence if 'Allow Multiple Entries' is not selected
            $mform->disabledIf('recurrent', 'frequency', 'notchecked');

            //disable reportlockdate if open end selected
            $mform->disabledIf('reportlockdate', 'reptype', 'eq', 1);

            //disable rule reset frequency
            $mform->disabledIf('recurfrequency', 'recurrent', 'notchecked');
            //disable rule recurring maximum
            $mform->disabledIf('recurmax', 'recurrent', 'notchecked');
            //disable rule reset start of recurring rule
            $mform->disabledIf('recurstart', 'recurrent', 'notchecked');
            //disable rule reset specific start date
            $mform->disabledIf('recurdate', 'recurrent', 'notchecked');




            //close the fieldset
            $mform->addElement('html', '</fieldset>');


	        $buttonarray[] = $mform->createElement('submit', 'saveanddisplaybutton', get_string('submit'));
	        $buttonarray[] = &$mform->createElement('cancel');

	        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

	        //close the fieldset
	        $mform->addElement('html', '</div></fieldset>');
		}



        function validation( $data, $files ){

            $data   =   (object)    $data;

            $this->errors = array();

         /*  if (!isset($data->frequency))   {
                if ($data->reporttype == 2) {
                    $this->errors['reporttype']	=	get_string('setallowmultipleerror','block_ilp',$data);
                }

                //we could check all other fields but the only one that is relevant is the report type field as
                //everything else is disregarded based on the value of this field
            }
*/

            if ($data->reptype   ==  2)  {


                if (isset($data->recurdate) && $data->recurdate > $data->reportlockdate && $data->recurstart == 3) {
                    $this->errors['recurdate']	=   get_string('recurstartafterlockerror','block_ilp');
                }

                if (isset($data->recurmax) && $data->recurmax > $data->reportmaxentries) {
                    $this->errors['recurmax']	=   get_string('recurmaxgreaterthanmaxentrieserror','block_ilp');
                }




          }


        }

		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			global $CFG;

            //open end, no recurrent
            if ($data->reptype==1 && empty($data->recurrent)){
                $data->reporttype=ILP_RT_OPENEND;
            }
            //open end, recurrent
            if ($data->reptype==1 && !empty($data->recurrent)){
                $data->reporttype=ILP_RT_RECURRING;
            }
            //final date, recurrent
            if ($data->reptype==2 && !empty($data->recurrent)){
                $data->reporttype=ILP_RT_RECURRING_FINALDATE;
            }
            //final date, no recurrent
            if ($data->reptype==2 && empty($data->recurrent)){
                $data->reporttype=ILP_RT_FINALDATE;
            }


            if (empty($data->id)) {

            	$data->id = $this->dbc->create_report($data);
            	
            	//setup report default permissions. They will match the permissions
            	//that the block has for each role
            	
            	$report_id	=	$data->id;
            	
            	//get all roles in moodle 
            	$roles		=	$this->dbc->get_roles();
            	
            	//get all capabilities for the ilp block
				$blockcapabilities	=	$this->dbc->get_block_capabilities();

				//loop through roles
            	foreach ($roles as $r) {
            		//secondary loop through capabilities
            		foreach($blockcapabilities as $cap) {
           			
            			//if the capability is not in the array
            			if (!in_array($cap->name,array('block/ilp:creeddelreport'))) {
            				
            				//initialise capable as an array
            				$capable	=	array();
            				//get all roles with the capability
            				$capabilityroles	=	get_roles_with_capability($cap->name,CAP_ALLOW);
							
            				//put the ids of roles with the current capability into the capable array
            				foreach($capabilityroles as $cr) {
								$capable[]	=	$cr->id;
							}
							
							//if the current role is one who has the capability
							if (in_array($r->id,$capable)) {
								
								//create a permission for the report with this role
								$permission					=	new stdClass();
								$permission->role_id		=	$r->id;
								$permission->capability_id	=	$cap->id;
								$permission->report_id		=	$report_id;
								$this->dbc->create_permisssion($permission);
							}
            			}	
            		}
            	}
        	} else {
			
				//check to stop report icons from being overwritten
				//if the binary_icon param is empty unset it that will stop 
				//any data that is currently present from being overwritten
				if (empty($data->binary_icon)) unset($data->binary_icon); 

				
            	$this->dbc->update_report($data);
        	}
	
    	    return $data->id;
		}
		
		/**
     	 * TODO comment this
     	 */
    	function definition_after_data() {
    		
    	}
	
}

	
?>
