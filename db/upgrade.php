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
    
    
    if ($oldversion < 2011101103)	{
    	$table = new $xmldb_table('block_ilp_cal_events');
	
		$table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);
        
        $table_entry = new $xmldb_field('entry_id');
        $table_entry->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_entry);
        
        $table_rf = new $xmldb_field('reportfield_id');
        $table_rf->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_rf);
        
        $table_event = new $xmldb_field('event_id');
        $table_event->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_event);
        
         $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);
        
        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);
        
        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $xmldb_key('date_unique_reportfield');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('reportfield_id'),'block_ilp_report_field','id');
        $table->addKey($table_key);
        
        $table_key = new $xmldb_key('unique_event');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('event_id'),'event','id');
        $table->addKey($table_key);
        
        $table_key = new $xmldb_key('entry');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('entry_id'),'block_ilp_entry','id');
        $table->addKey($table_key);
        

       	if (!$dbman->table_exists($table)) $dbman->create_table($table);

    } 


    if ($oldversion < 2012022405)	{

        //include ilp db class
        require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');

        require_once($CFG->dirroot.'/blocks/ilp/lib.php');
        $dbc                =   new ilp_db();
        $takenPositions     =   array();
        $unpositionedReports    = array();

        $reports    =   $dbc->get_reports_by_position();

        //first compile a list of all taken positions
        if (!empty($reports)) {
            foreach ($reports as $r)  {
                if (!empty($r->position))   {
                    $takenPositions[]           =   $r->position;
                }   else {
                    $unpositionedReports[]      =   $r;
                }
            }

            //
            if (!empty($unpositionedReports)) {
                foreach($unpositionedReports as $ur)   {
                    $ur->position = returnNextPosition($takenPositions);
                    $dbc->update_report($ur);
                    $takenPositions[]           =   $ur->position;
                    echo "Positon {$ur->position} allocated to {$ur->name}<br />";
                }
            }
        }
    }

    if ($oldversion < 2012030104) {

        // Define index report_entry (not unique) to be added to block_ilp_entry
        $table = new xmldb_table('block_ilp_entry');
        $index = new xmldb_index('report_entry', XMLDB_INDEX_NOTUNIQUE, array('report_id', 'user_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index report_entry (not unique) to be added to block_ilp_entry
        $table = new xmldb_table('block_ilp_plu_ste_items');
        $index = new xmldb_index('passfail', XMLDB_INDEX_NOTUNIQUE, array('passfail'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // ilp savepoint reached
        upgrade_block_savepoint(true, 2012030104, 'ilp');
    }


    return true;
}


