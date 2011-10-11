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

defined('MOODLE_INTERNAL') || die();

function xmldb_block_ilp_upgrade($oldversion) {
    global $CFG, $DB;

    if (empty($DB))	{ 
  		include($CFG->dirroot."/blocks/ilp/db/moodle2_emulator.php");
    }
 
    
    
    $result = TRUE;
    $dbman = $DB->get_manager();

    $xmldb_table = class_exists('xmldb_table') ? 'xmldb_table' : 'XMLDBTable';
    $xmldb_field = class_exists('xmldb_field') ? 'xmldb_field' : 'XMLDBField';
    $xmldb_key   = class_exists('xmldb_key')   ? 'xmldb_key'   : 'XMLDBKey';
    $set_attributes = method_exists($xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';
    
    
    if ($oldversion < 2011090711 ) {
        $table = new $xmldb_table('block_ilp_log');
        
        $field_exists	= (method_exists($xmldb_table,'field_exists'))	?	'field_exists' : 'findFieldInArray';

        $xmlfield	=	new $xmldb_field( 'oldvalue' );
        $xmlfield->$set_attributes(XMLDB_TYPE_BINARY,'big',null);
		$dbman->change_field_type($table,$xmlfield);
        
        $xmlfield	=	new $xmldb_field( 'newvalue' );
        $xmlfield->$set_attributes(XMLDB_TYPE_BINARY,'big',null);
		$dbman->change_field_type($table,$xmlfield);
        
        
        $table = new $xmldb_table( 'block_ilp_plu_sts_items' );
        $xmlfield	=	new $xmldb_field('hexcolour');
        if (!$dbman->field_exists($table,$xmlfield)) {
       		
       		$xmlfield->$set_attributes(XMLDB_TYPE_CHAR,'255',null);
       		$dbman->add_field($table,$xmlfield);
        }
        
        $table = new $xmldb_table( 'block_ilp_report' );
        $xmlfield	=	new $xmldb_field('deleted');
        if (!$dbman->field_exists($table,$xmlfield)) {
        	
       		$xmlfield->$set_attributes(XMLDB_TYPE_INTEGER,1,null,null,null,0,null,0);
       		$dbman->add_field($table,$xmlfield);
        }
        
        $xmlfield	=	new $xmldb_field('position');
        if (!$dbman->field_exists($table,$xmlfield)) {
			$xmlfield->$set_attributes(XMLDB_TYPE_INTEGER,'10',null,null,null,0,null,0);
        	$dbman->add_field($table,$xmlfield);
        }
    }
    
    
    
    return true;
}
