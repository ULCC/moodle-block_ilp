<?php
/* test the ilp_mis_connection class */
require_once('../configpath.php');
require_once($CFG->dirroot.'/blocks/ilp/admin_actions_includes.php');
require_once($CFG->dirroot.'/blocks/ilp/db/ilp_mis_connection.php');

$db = new ilp_mis_connection( array( 'prefix' => 'mdl_', 'user_table' => 'users', 'attendance_table' => 'attendance', 'user_unique_key' => 'id' ) );
$rs = $db->Execute( "SELECT * FROM mdl_block_ilp_plu_dd_items" );
var_crap( $rs->GetRows() );


