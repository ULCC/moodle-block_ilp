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
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW
        )
	),

	//the capaability needed in order to add a report instance
	//to the ilp

	'block/ilp:addreport' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
			'user' => CAP_ALLOW
        )

	),

	//the capaability needed in order to edit a report instance
	//to the ilp
	'block/ilp:editreport' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
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
            'admin' => CAP_ALLOW
        )

	),

	//the capaability needed in order veiw a report in the ilp
	'block/ilp:viewreport' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
			'user' => CAP_ALLOW
        )

	),


	//the capaability needed in order to view an ilp
	'block/ilp:viewilp' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
			'user' => CAP_ALLOW
        )

	),

	//the capaability needed in order to view an ilp belong to someone else
	'block/ilp:viewotherilp' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW
        )
	),

	//the capaability needed in order to add a comment
	'block/ilp:addcomment' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
			'user' => CAP_ALLOW
        )

	),

	//the capaability needed in order to edit a comment
	'block/ilp:editcomment' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
			'user' => CAP_ALLOW
        )

	),

	//the capaability needed in order to delete a comment
	'block/ilp:deletecomment' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
			'user' => CAP_ALLOW
        )
	),

	//the capaability needed in order to view a comment
	'block/ilp:viewcomment' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
			'user' => CAP_ALLOW
        )
	),

	//the capaability needed in order to view anything in the -
	'block/ilp:ilpviewall' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'student' => CAP_PREVENT,
            'editingteacher' => CAP_PREVENT,
            'admin' => CAP_ALLOW,
			'user' => CAP_PREVENT
        )
	),






);
global $CFG;
//test the version number if we are in 2.0 we need to pass the $block_ilp_capabilities to $capabilities

if (stripos($CFG->release,"2.") !== false) {

	//pass the $block_ilp_capabilities to $capabilities
	$capabilities	=	$block_ilp_capabilities;

	//move all values in admin key to manager key
	$capabilities['block/ilp:creeddelreport']['legacy']['manager'] = $capabilities['block/ilp:creeddelreport']['legacy']['admin'];
	//unset the admin key
	unset($capabilities['block/ilp:creeddelreport']['legacy']['admin']);

	$capabilities['block/ilp:addreport']['legacy']['manager'] = $capabilities['block/ilp:addreport']['legacy']['admin'];
	unset($capabilities['block/ilp:addreport']['legacy']['admin']);

	$capabilities['block/ilp:editreport']['legacy']['manager'] = $capabilities['block/ilp:editreport']['legacy']['admin'];
	unset($capabilities['block/ilp:editreport']['legacy']['admin']);

	$capabilities['block/ilp:deletereport']['legacy']['manager'] = $capabilities['block/ilp:deletereport']['legacy']['admin'];
	unset($capabilities['block/ilp:deletereport']['legacy']['admin']);

	$capabilities['block/ilp:viewreport']['legacy']['manager'] = $capabilities['block/ilp:viewreport']['legacy']['admin'];
	unset($capabilities['block/ilp:viewreport']['legacy']['admin']);

	$capabilities['block/ilp:viewotherilp']['legacy']['manager'] = $capabilities['block/ilp:viewotherilp']['legacy']['admin'];
	unset($capabilities['block/ilp:viewotherilp']['legacy']['admin']);

	$capabilities['block/ilp:viewilp']['legacy']['manager'] = $capabilities['block/ilp:viewilp']['legacy']['admin'];
	unset($capabilities['block/ilp:viewilp']['legacy']['admin']);

	unset($block_ilp_capabilities);
}
