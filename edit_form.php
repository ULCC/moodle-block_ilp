<?php
/**
 * Form for editing ILP block instances.
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
class block_ilp_edit_form extends block_edit_form {

    /**
     * Adds definitions to the form object which are specific to this sub class
     *
     * @param object $mform
     * @return void
     */
    protected function specific_definition($mform) {
        global $CFG;

        // get the course id
        $course_id = required_param('id', PARAM_INT);
        
        //the two params below are needed to send the user back to the config page if there is no javascript
        //and we have to perform submits to add and remove reports from the course        
        // get the current sesskey
        $sesskey = required_param('sesskey', PARAM_RAW);
        
        //get the bui_editid if we are in moodle 2.0 it is not needed in 1.9
        $bui_editid = optional_param('bui_editid','', PARAM_RAW);
        
        //get the instanceid if we are in 1.9
        $instanceid = optional_param('instanceid','', PARAM_INT);
        
        //get the blockaction if we are in 1.9
        $blockaction = optional_param('blockaction','', PARAM_RAW);
        

        // get the global config, which we'll use to set the defaults
        $globalconfig = get_config('block_ilp');

        // include ilp db class
        require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

        // instantiate the db class
        $dbc = new ilp_db();

        $fieldsettitle = get_string('coursereports', 'block_ilp');
        	
        //create a new fieldset
       	$mform->addElement('html', '<fieldset id="reportfieldset" class="clearfix ilpfieldset">');
        $mform->addElement('html', '<legend class="ftoggler">'.$fieldsettitle.'</legend>');
        
        //get all reports that are enabled in this course
        $assignedreports	=	$dbc->get_coursereports($course_id,null,ILP_ENABLED);
        
        $coursereports		=	array();
        
        //populate the areport var with key and values from the objects returned in $assignedreports
        $areport	=	array();
        if (!empty($assignedreports)) {
	        foreach ($assignedreports as $r) {
	        	
	        	$areport[$r->report_id]	=	$r->name;	
	        }
        }
        
        if (!empty($assignedreports))	{
        	foreach ($assignedreports as $a) {
				$coursereports[]	=	$a->report_id;	
        	}
        }
        
        //get all ilp reports that enabled except the ones already enabled in this course 
        $unassignedreports	=	$dbc->get_enabledreports($coursereports);
        
	    //populate the areport var with key and values from the objects returned
        $unreport	=	array();
        if (!empty($unassignedreports)) {
	        foreach ($unassignedreports as $r) {
	        	$unreport[$r->id]	=	$r->name;	
	        }
        }

        $mform->addElement('hidden', 'config_bui_editid', $bui_editid);
        $mform->addElement('hidden', 'config_sesskey', $sesskey);
        $mform->addElement('hidden', 'config_course_id', $course_id);
        
        //this hidden params are only needed in moodle 1.9 

        $mform->addElement('hidden', 'config_blockaction', $blockaction);
        $mform->addElement('hidden', 'config_instanceid', $instanceid);
        
        
        //create the select elements to hold the data
        $objs = array();
        $objs[0] =& $mform->createElement('select', 'config_coursereports', get_string('selectedreports', 'block_ilp'), $areport, 'size="15"');
        $objs[0]->setMultiple(true);
        $objs[1] =& $mform->createElement('select', 'config_reports', get_string('availablereports', 'block_ilp'), $unreport, 'size="15"');
        $objs[1]->setMultiple(true);
        
        $grp =& $mform->addElement('group', 'reportsgrp', get_string('reports','block_ilp'), $objs, ' ', false);
                $objs = array();
        $objs[] =& $mform->createElement('submit', 'config_addsel', get_string('addsel', 'block_ilp'));
        $objs[] =& $mform->createElement('submit', 'config_removesel', get_string('removesel', 'block_ilp'));
        $objs[] =& $mform->createElement('submit', 'config_addall', get_string('addall', 'block_ilp'));
        $objs[] =& $mform->createElement('submit', 'config_removeall', get_string('removeall', 'block_ilp'));
        $grp =& $mform->addElement('group', 'buttonsgrp', get_string('selectedreportlist', 'block_ilp'), $objs, array(' ', '<br />'), false);
       	//close the fieldset
	    $mform->addElement('html', '</fieldset>');
        
        
   }
}