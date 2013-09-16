<?php
/**
 * This class provides a mform that allows the user to assign permissions to 
 * a report
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */




class edit_report_permissions_mform extends ilp_moodleform {

		public		$report_id;
		public		$dbc;
	
		/**
     	 * TODO comment this
     	 */
		function __construct($report_id) {

			global $CFG;

			$this->report_id	=	$report_id;
			
			// include the ilb db
        	require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');
			
			$this->dbc			=	new ilp_db();
			
			// call the parent constructor
       	 	parent::__construct("{$CFG->wwwroot}/blocks/ilp/actions/edit_report_permissions.php?report_id={$this->report_id}");
		}
	
		
		/**
     	 * TODO comment this
     	 */		
		function definition() {
			 global $USER, $CFG;

        	$report		=	$this->dbc->get_report_by_id($this->report_id);
        	
        	$mform =& $this->_form;

        	$mform->addElement('html','<div><span>'.get_string('reportname','block_ilp').'</span><span>'.$report->name.'</span></div>');
        	
        	$mform->addElement('html','<div class="desciptivetext">'.get_string('reportpermissionsdescription','block_ilp', $report).'</div');
        	
			//create a new fieldset
        	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
           // $mform->addElement('html', '<legend class="ftoggler">'.get_string('reportpermissions','block_ilp').'</legend>');
			
			$mform->addElement('html','<div>');
			
			$mform->addElement('hidden', 'report_id', $this->report_id);
        	$mform->setType('report_id', PARAM_INT);
			
			$mform->addElement('html','<table id="ilppermissionstable" class="generaltable">');
			
			$blockcapabilities	=	$this->dbc->get_block_capabilities();
						
			//create header row for the table
			$mform->addElement('html','<tr>');

			//insert first balnk table cell
			$mform->addElement('html','<td class="first-child">');
			$mform->addElement('html','');
			$mform->addElement('html','</td>');
			
			//loop through capabilities and name columns based on the capability
			foreach ($blockcapabilities as $id => $cap) {
				$mform->addElement('html','<td class="slimcell">');
				$langstring	=	str_ireplace('block/', '', $cap->name);
                $cap_fullname = get_string($langstring,'block_ilp');
                $blockcapabilities[$id]->fullname = $cap_fullname;
                $rotate_span = html_writer::tag('span', $cap_fullname, array('class'=>'capabilityname')) ;
				$mform->addElement('html', $rotate_span);
				$mform->addElement('html','</td>');
			}
			$mform->addElement('html','</tr>');

			//create an array of contexts
			$roles	=	$this->dbc->get_roles();
			
			foreach($roles as $r) {
				//start new row
				$mform->addElement('html','<tr>');
				$displayname = (!empty($r->name)) ? $r->name : $r->shortname;
				//set the row title
				$mform->addElement('html','<td class="rowtitle first-child">');
				$mform->addElement('html',$displayname);
				$mform->addElement('html','</td>');
				
				//create the checkboxes using the current context and capability id as 
				foreach ($blockcapabilities as $cap) {
 
					$capable = array();				
					//once we have an ilp for just one version of moodle it will be wise to 
					//create a record_exists query to do the work that the code below is doing

					//get all roles with CAP_ALLOW for this capabilty
					$capabilityroles	=	get_roles_with_capability($cap->name,CAP_ALLOW);
					//put all the ids of the roles into an array which will be used to check if 
					//the current role has the ability
					foreach($capabilityroles as $cr) {
						
						$capable[]	=	$cr->id;
					}
					$checkboxname	=	$r->id."_".$cap->id;
					$mform->addElement('html','<td class="slimcell">');
					
					//if the role doesnot have the capability at the system level it can not be assigned
					//to the role at report level. we ensure this by checking if the id of the current role 
					//is in the $capabilityroles array. if it is then we do not disabled the checkbox if it isn't we 
					//disable the checkbox
					$attrs = (in_array($r->id,$capable)) ? null : array("disabled"=>"disabled");
                    $attrs['title'] = $cap->fullname;
					$mform->addElement('checkbox', $checkboxname,null,null,$attrs);
					$mform->addElement('html','</td>');
				}
				
				//end row
				$mform->addElement('html','</tr>');
			}
		
			$mform->addElement('html','</table>');
			$mform->addElement('html','</div>');
			
	        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('submit'));
	        $buttonarray[] = &$mform->createElement('cancel');
        		        
	        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        
	        //close the fieldset
	        $mform->addElement('html', '</fieldset>');
		}
	
		
		/**
     	 * TODO comment this
     	 */		
		function process_data($data) {
			//the following takes place irrespective of whether the permissions are being set for 
			//the first time or not
						
			//TODO: adding and deleting new permissions in this way (whilst effect)is a bit lazy
			//find a better way
			
			//check if any permissions for this report currently exist if yes delete them
			if ($this->dbc->permissions_exist($data->report_id)) {
				//delete the permissions for this form 	
				$this->dbc->delete_permissions_by_report_id($data->report_id);
			}
			
			$notsaving	=	array('report_id','submitbutton');
			$result		=	true;	
			
			
			
			//loop through all data posted and save as along as it is not in the not saving array
			foreach($data as $key => $value) {
				if (!in_array($key,$notsaving)) {
					//explode the key this should give us the context and capability ids 
					$params		=	explode("_",$key);
					
					//params[0] should now contain context_id
					//params[1] should now contain capability_id
					
					$permission					=	new stdClass();
					$permission->report_id		=	$data->report_id;
					$permission->role_id		=	$params[0];
					$permission->capability_id	=	$params[1];
					
					if (!$this->dbc->create_permisssion($permission)) 	{
						$result	=	false;
					}
				}
			}
			
			return $result;
		}
		
		/**
     	 * TODO comment this
     	 */
    	function definition_after_data() {
    		
    	}
	
	
	
}