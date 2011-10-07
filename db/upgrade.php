<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Keeps track of upgrades to the global search block
 *
 * @package    blocks
 * @subpackage search
 * @copyright  2010 Aparup Banerjee <aparup@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_ilp_upgrade($oldversion) {
    global $CFG, $DB;

    $result = TRUE;
    $dbman = $DB->get_manager();

    $xmldb_key   = class_exists('xmldb_key')   ? 'xmldb_key'   : 'XMLDBKey';
    if( class_exists( 'xmldb_table' ) ){
        $tableclass = 'xmldb_table';
        $fieldclass = 'xmldb_field';
        $setAttributesMethod = 'set_attributes';
        $fieldExistsMethod = 'field_exists';
        $use_dbman = true;
    }
    elseif( class_exists( 'XMLDBTable' ) ){
        $tableclass = 'XMLDBTable';
        $fieldclass = 'XMLDBField';
        $setAttributesMethod = 'setAttributes';
        $fieldExistsMethod = 'findFieldInArray';
        $use_dbman = false;
    }
    if ($oldversion < 2011090711 ) {
        $table = new $tableclass('block_ilp_log');
        $changefieldlist = array(
            'oldvalue' => array( 'type' => XMLDB_TYPE_BINARY, 'size' => 'medium' ),
            'newvalue' => array( 'type' => XMLDB_TYPE_BINARY, 'size' => 'medium' )
        );
        $changefieldlist = array();
        foreach( $changefieldlist as $field=>$newtype ){
            $type = $newtype[ 'type' ];
            $size = $newtype[ 'size' ];
            $xmlfield = new $fieldclass( $field );
            $xmlfield->$setAttributesMethod( $type, $size, null );
	        try {
                if( $use_dbman ){
	                $dbman->change_field_type($table, $xmlfield);
                }
                else{
                    //not sure how to modify a field if dbman not available
                    $xmlfield = &$table->getField( $field );
                    $xmlfield->$setAttributesMethod( $type, $size, null );
                }
	        } catch (Exception $e) {
                var_dump( $e );
	            exit();
	        }
        }
    }

    //add hexcolour field to block_ilp_plu_sts_items
    if ($oldversion < 2011090711 ) {
        $tablename =  'block_ilp_plu_sts_items';
        $table = new $tableclass( $tablename );
        $fieldname = 'hexcolour';
        $field = new $fieldclass( $fieldname );
        $field->$setAttributesMethod( XMLDB_TYPE_CHAR, 255 );
        if( $use_dbman ){
            if( !$dbman->field_exists( $table, $field ) ){
                $dbman->add_field( $table, $field );
            }
        }
        else{
            if( !$table->$fieldExistsMethod( $fieldname ) ){
                $table->addField( $field );
            }
        }
    }
    return true;
}
