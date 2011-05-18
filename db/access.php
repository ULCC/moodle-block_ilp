<?php
/**
 * Capability definitions for the ILP block.
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

// TODO moodle 2.0 complains that this should be $capabilities
$block_ilp_capabilities = array(


	//admin report definition capabilities

	//defines whether the user is able to create,edit or delete a report
	'block/ilp:creeddelreport' => array(
		'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'admin' => CAP_ALLOW
        )
	),
	
	//the capaability needed in order to add a report instance 
	//to the ilp
	
	'block/ilp:addreport' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
	
	),
	
	//the capaability needed in order to edit a report instance 
	//to the ilp	
	'block/ilp:editreport' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
	
	),
	
	//the capaability needed in order to delete a report instance 
	//to the ilp
	'block/ilp:deletereport' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'admin' => CAP_ALLOW
        )
	
	),
	
	//the capaability needed in order veiw a report in the ilp 
	'block/ilp:viewreport' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
	
	),
    
);
?>