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

    if ($oldversion < 2011041301 ) {
        $table = new xmldb_table('block_ilp_log');
        $changefieldlist = array(
            'oldvalue' => array( 'type' => XMLDB_TYPE_BINARY, 'size' => 'medium' ),
            'newvalue' => array( 'type' => XMLDB_TYPE_BINARY, 'size' => 'medium' )
        );
        foreach( $changefieldlist as $field=>$newtype ){
            $type = $newtype[ 'type' ];
            $size = $newtype[ 'size' ];
            $xmlfield = new xmldb_field( $field );
            $xmlfield->set_attributes( $type, $size, null );
	        try {
	            $dbman->change_field_type($table, $xmlfield);
	        } catch (Exception $e) {
                var_dump( $e );
	            exit();
	        }
        }
    }
}
