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

$capabilities = array(


	//manager report definition capabilities

	//defines whether the user is able to create,edit or delete a report
	'block/ilp:creeddelreport' => array(
		'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'manager' => CAP_ALLOW
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
            'manager' => CAP_ALLOW,
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
            'manager' => CAP_ALLOW
        )
	),

	//the capaability needed in order to delete a report instance
	//to the ilp
	'block/ilp:deletereport' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'manager' => CAP_ALLOW
        )
	),

	//the capaability needed in order veiw a report in the ilp
	'block/ilp:viewreport' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
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
            'manager' => CAP_ALLOW,
			'user' => CAP_ALLOW
        )
	),

    //the capability needed in order to appear in student list for a course
    'block/ilp:reviewee' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'student' => CAP_ALLOW,
        )
 	),

	//the capaability needed in order to view an ilp belong to someone else
	'block/ilp:viewotherilp' => array(
		'captype' => 'read',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
	),

	//the capaability needed in order to add a comment
	'block/ilp:addcomment' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
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
            'manager' => CAP_ALLOW,
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
            'manager' => CAP_ALLOW,
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
            'manager' => CAP_ALLOW,
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
            'manager' => CAP_ALLOW,
			'user' => CAP_PREVENT
        )
	),

    //the capability needed in order to add/view an extension
    'block/ilp:addviewextension' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'legacy' => array(
            'student' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'user' => CAP_PREVENT
        )
    ),
    'block/ilp:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
    'block/ilp:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);