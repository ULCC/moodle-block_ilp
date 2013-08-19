<pre>
<?php
require_once('../lib.php');
require_once( $CFG->dirroot . '/lib/xmldb/xmldb_object.php' );
require_once( $CFG->dirroot . '/lib/xmldb/xmldb_table.php' );

require_login(0, false);
require_capability('block/ilp:creeddelreport', context_system::instance());

$dbman = $DB->get_manager();

$problematic_table_list = array(
    "block_ilp_plu_hte_ent" => 'value',
    "block_ilp_plu_are_ent" => 'value'
);

foreach( $problematic_table_list as $table=>$field ){
	$table = new xmldb_table( $table );
	$field = new xmldb_field( $field );
	$field->setType( XMLDB_TYPE_TEXT );
	$dbman->change_field_type( $table, $field );
	echo "$table.$field type is now 'text'\n";
}
/*
$table = new xmldb_table( 'block_ilp_plu_gradebooktracker_ent' );

$dropfield = new xmldb_field( 'review' );

if($dbman->field_exists( $table, $dropfield ) ){
    echo "Found 'review': dropping 'review' from block_ilp_plu_gradebooktracker_ent\n";
    $dbman->drop_field( $table, $dropfield );
}
else{
    echo "Didn't find 'review' in block_ilp_plu_gradebooktracker_ent\n";
}

$addfield = new xmldb_field( 'entry_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED ) ;
if( !$dbman->field_exists( $table, $addfield ) ){
    echo "adding 'entry_id' to block_ilp_plu_gradebooktracker_ent\n";
    $dbman->add_field( $table, $addfield );
}
else{
    echo "block_ilp_plu_gradebooktracker_ent.entry_id already exists.\n";
}
*/
echo "Finished\n";
?>
</pre>
