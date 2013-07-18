<?php
$handlers = array (
    'user_unenrolled' => array (
        'handlerfile'      => '/block/ilp/classes/ilp_report.class.php',
        'handlerfunction'  => 'ilp_report::roles_changed',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
    'user_unenrol_modified' => array (
        'handlerfile'      => '/block/ilp/classes/ilp_report.class.php',
        'handlerfunction'  => 'ilp_report::roles_changed',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
    'role_unassigned' => array (
        'handlerfile'      => '/block/ilp/classes/ilp_report.class.php',
        'handlerfunction'  => 'ilp_report::roles_changed',
        'schedule'         => 'instant',
        'internal'         => 1,
    )
);