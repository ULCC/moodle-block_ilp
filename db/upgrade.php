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
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

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
	
	if ($oldversion < 2012030107) {

        // Define index report_entry (not unique) to be added to block_ilp_entry
        $table = new xmldb_table('block_ilp_plu_ddl_ent');
        $index = new xmldb_index('entry_id', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // ilp savepoint reached
        upgrade_block_savepoint(true, 2012030107, 'ilp');
    }


    if ($oldversion < 2012030110) {

        //

        $table = new $xmldb_table('block_ilp_graph_plugin');

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_name = new $xmldb_field('name');
        $table_name->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_name);

        $table_name = new $xmldb_field('tablename');
        $table_name->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_name);

        $table_status = new $xmldb_field('status');
        $table_status->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_status);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        if (!$dbman->table_exists($table)) $dbman->create_table($table);

        $table = new $xmldb_table('block_ilp_report_graph');

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_name = new $xmldb_field('name');
        $table_name->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_name);

        $table_name = new $xmldb_field('description');
        $table_name->$set_attributes(XMLDB_TYPE_TEXT);
        $table->addField($table_name);

        $table_report_id = new $xmldb_field('report_id');
        $table_report_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report_id);

        $table_plugin_id = new $xmldb_field('plugin_id');
        $table_plugin_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_plugin_id);

        $table_status = new $xmldb_field('status');
        $table_status->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_status);

        $table_datacol = new $xmldb_field('datacollected');
        $table_datacol->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_datacol);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        if (!$dbman->table_exists($table)) $dbman->create_table($table);

        // ilp savepoint reached
        //upgrade_block_savepoint(true, 2012030108, 'ilp');
    }


    if ($oldversion < 2012030113) {

        $table = new xmldb_table('block_ilp_report_field');

        $table_summary = new $xmldb_field('summary');
        $table_summary->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, NULL);
        $table->addField($table_summary);

        if (!$dbman->field_exists($table,$table_summary)) {
            $dbman->add_field($table,$table_summary);
        }

        //changes to the report table
        $table = new $xmldb_table('block_ilp_report');

        $xmlfield	=	new $xmldb_field('reporttype');
        if (!$dbman->field_exists($table,$xmlfield)) {
            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('reportmaxentries');
        if (!$dbman->field_exists($table,$xmlfield)) {
            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('reportlockdate');
        if (!$dbman->field_exists($table,$xmlfield)) {
            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('recurfrequency');
        if (!$dbman->field_exists($table,$xmlfield)) {
            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('recurmax');
        if (!$dbman->field_exists($table,$xmlfield)) {
            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('recurstart');
        if (!$dbman->field_exists($table,$xmlfield)) {
            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('recurdate');
        if (!$dbman->field_exists($table,$xmlfield)) {
            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED);
            $dbman->add_field($table,$xmlfield);
        }

        //create preferences table
        $table = new $xmldb_table('block_ilp_preferences');

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_field = new $xmldb_field('report_id');
        $table_field->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED,null);
        $table->addField($table_field);

        $table_field = new $xmldb_field('entry_id');
        $table_field->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED,null);
        $table->addField($table_field);

        $table_field = new $xmldb_field('user_id');
        $table_field->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED,null);
        $table->addField($table_field);

        $table_field = new $xmldb_field('course_id');
        $table_field->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED,null);
        $table->addField($table_field);

        $table_field = new $xmldb_field('action');
        $table_field->$set_attributes(XMLDB_TYPE_TEXT,'large',null,XMLDB_NOTNULL);
        $table->addField($table_field);

        $table_field = new $xmldb_field('param');
        $table_field->$set_attributes(XMLDB_TYPE_TEXT,'large',null,XMLDB_NOTNULL);
        $table->addField($table_field);

        $table_field = new $xmldb_field('value');
        $table_field->$set_attributes(XMLDB_TYPE_TEXT,'large',null,XMLDB_NOTNULL);
        $table->addField($table_field);

        $table_field = new $xmldb_field('param2');
        $table_field->$set_attributes(XMLDB_TYPE_TEXT,'large',null,null);
        $table->addField($table_field);

        $table_field = new $xmldb_field('value2');
        $table_field->$set_attributes(XMLDB_TYPE_TEXT,'large',null,null);
        $table->addField($table_field);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        if (!$dbman->table_exists($table)) $dbman->create_table($table);

    }

    if ($oldversion < 2012073013) {

        //if it is not present we are going to add the block_ilp_plu_sts_ent table
        //this is used by the ilp_element_plugin_status form element. It gives the
        //user the option to save data into the plu_sts table or the userstatus table
        //(thus updating the users status)


        // create the new table to store responses to fields
        $table = new $xmldb_table( 'block_ilp_plu_sts_ent' );

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_maxlength = new $xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);

        $table_item_id = new $xmldb_field('value');	//foreign key -> $this->items_tablename
        $table_item_id->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_item_id);

        $table_report = new $xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $xmldb_key('listpluginentry_unique_fk');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), 'block_ilp_plu_sts_ent', 'id');
        $table->addKey($table_key);

        if (!$dbman->table_exists($table)) $dbman->create_table($table);

        //changes to the report table
        $table = new $xmldb_table('block_ilp_plu_sts');

        $xmlfield	=	new $xmldb_field('savetype');
        if (!$dbman->field_exists($table,$xmlfield)) {
            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED);
            $dbman->add_field($table,$xmlfield);
        }


    }



    if ($oldversion < 2012073014) {

        //Add missing ILP_temp table

        // create the new table to store responses to fields
        $table = new $xmldb_table( 'block_ilp_temp' );

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_misc = new $xmldb_field('misc');
        $table_misc->$set_attributes(XMLDB_TYPE_TEXT, 150000, XMLDB_UNSIGNED);
        $table->addField($table_misc);

        $table_data = new $xmldb_field('data');	//data field
        $table_data->$set_attributes(XMLDB_TYPE_TEXT, 150000, XMLDB_UNSIGNED);
        $table->addField($table_data);

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        if (!$dbman->table_exists($table)) $dbman->create_table($table);
    }

    if ($oldversion <  2012101815)   {

        //creates a index on timemodified and user_id in the entry table
        $table = new xmldb_table('block_ilp_entry');
        $index = new xmldb_index('entry_user_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified', 'user_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('entry_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_ilp_plu_tex_ent');
        $index = new xmldb_index('tex_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_ilp_plu_are_ent');
        $index = new xmldb_index('area_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        if ($dbman->table_exists('block_ilp_plu_datf_ent'))       {
            $table = new xmldb_table('block_ilp_plu_datf_ent');
            $index = new xmldb_index('datf_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        $table = new xmldb_table('block_ilp_plu_ste_ent');
        $index = new xmldb_index('ste_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_ilp_plu_ste_ent');
        $index = new xmldb_index('ste_entry_parent', XMLDB_INDEX_NOTUNIQUE, array('entry_id,parent_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        if ($dbman->table_exists('block_ilp_plu_ddl_ent')) {
            $table = new xmldb_table('block_ilp_plu_ddl_ent');
            $index = new xmldb_index('ddl_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_ddl_ent');
            $index = new xmldb_index('ddl_entry_parent', XMLDB_INDEX_NOTUNIQUE, array('entry_id,parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
        if ($dbman->table_exists('block_ilp_plu_cat_ent')) {
            $table = new xmldb_table('block_ilp_plu_cat_ent');
            $index = new xmldb_index('cat_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_cat_ent');
            $index = new xmldb_index('cat_entry_parent', XMLDB_INDEX_NOTUNIQUE, array('entry_id,parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
        if ($dbman->table_exists('block_ilp_plu_chb_ent')) {
            $table = new xmldb_table('block_ilp_plu_chb_ent');
            $index = new xmldb_index('chb_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_chb_ent');
            $index = new xmldb_index('chb_entry_parent', XMLDB_INDEX_NOTUNIQUE, array('entry_id,parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        if ($dbman->table_exists('block_ilp_plu_dd_ent')) {
            $table = new xmldb_table('block_ilp_plu_dd_ent');
            $index = new xmldb_index('dd_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_dd_ent');
            $index = new xmldb_index('dd_entry_parent', XMLDB_INDEX_NOTUNIQUE, array('entry_id,parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        if ($dbman->table_exists('block_ilp_plu_rdo_ent')) {
            $table = new xmldb_table('block_ilp_plu_rdo_ent');
            $index = new xmldb_index('rdo_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_rdo_ent');
            $index = new xmldb_index('rdo_entry_parent', XMLDB_INDEX_NOTUNIQUE, array('entry_id,parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        $table = new xmldb_table('block_ilp_user_status');
        $index = new xmldb_index('userstat_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_ilp_entry_comment');
        $index = new xmldb_index('entrycom_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }



    }

    // To sort out the position values mess in existing database.
    if ($oldversion < 2013031828)	{

        //include ilp db class
        require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

        require_once($CFG->dirroot.'/blocks/ilp/lib.php');
        $dbc                =   new ilp_db();

        $table = new $xmldb_table( 'block_ilp_report' );
        $xmlfield	=	new $xmldb_field('vault');
        if (!$dbman->field_exists($table,$xmlfield)) {

            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER,'10',null);
            $dbman->add_field($table,$xmlfield);
        }

        $reports    =   $dbc->get_reports_by_position(null,null,true,false);
        //first compile a list of all taken positions
        $position = 1;
        if (!empty($reports)) {
            foreach($reports as $report) {
                if($report->deleted == 1) {
                    $dbc->set_new_report_position($report->id, 0);
                } else {
                    $dbc->set_new_report_position($report->id,$position);
                    $position++;
                }
            }
        }
    }

    // for student status
    if($oldversion < 2013062801){

        $table = new $xmldb_table( 'block_ilp_plu_sts_items' );

        $xmlfield	=	new $xmldb_field('icon');
        if (!$dbman->field_exists($table,$xmlfield)) {

            $xmlfield->$set_attributes(XMLDB_TYPE_CHAR,'45',null);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('display_option');
        if (!$dbman->field_exists($table,$xmlfield)) {

            $xmlfield->$set_attributes(XMLDB_TYPE_CHAR,'4',null);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('description');
        if (!$dbman->field_exists($table,$xmlfield)) {

            $xmlfield->$set_attributes(XMLDB_TYPE_CHAR,'255',null);
            $dbman->add_field($table,$xmlfield);
        }

        $xmlfield	=	new $xmldb_field('bg_colour');
        if (!$dbman->field_exists($table,$xmlfield)) {

            $xmlfield->$set_attributes(XMLDB_TYPE_CHAR,'45',null);
            $dbman->add_field($table,$xmlfield);
        }
    }
    // add a new filed to report table
    if($oldversion < 2013071802){

        $table = new $xmldb_table( 'block_ilp_report' );

        $xmlfield	=	new $xmldb_field('vault');
        if (!$dbman->field_exists($table,$xmlfield)) {

            $xmlfield->$set_attributes(XMLDB_TYPE_INTEGER,10,0);
            $dbman->add_field($table,$xmlfield);
        }

        //Add new table for user choice

        // create the new table to store responses to fields
        $table = new $xmldb_table( 'block_ilp_user_choice' );

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_user_id = new $xmldb_field('user_id');
        $table_user_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_user_id);

        $table_element_id = new $xmldb_field('element_id');
        $table_element_id->$set_attributes(XMLDB_TYPE_TEXT, 150000, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_element_id);

        $table_choice = new $xmldb_field('choice');
        $table_choice->$set_attributes(XMLDB_TYPE_TEXT, 150000, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_choice);

        $table_modified = new $xmldb_field('modified');
        $table_modified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_modified);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        if (!$dbman->table_exists($table)) $dbman->create_table($table);


    }

    // Give legacy reports a value of zero.
    if ($oldversion < 2013073101){
        $reports = $DB->get_records('block_ilp_report');
        foreach ($reports as $report) {
            if (!$report->vault) {
                $report->vault = 0;
                $DB->update_record('block_ilp_report', $report);
            }
        }
    }

    // Give legacy reports a value of zero.
    if ($oldversion < 2013080603){
        // create the new table to store responses to fields
        $table = new $xmldb_table('block_ilp_plu_sts_ent');

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_maxlength = new $xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);

        $table_item_id = new $xmldb_field('value');	//foreign key -> $this->items_tablename
        $table_item_id->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_item_id);

        $table_report = new $xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $xmldb_key('listpluginentry_unique_fk');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), 'block_ilp_plu_sts', 'id');
        $table->addKey($table_key);

        if(!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }

    if ($oldversion < 2013081501){
        $table = new $xmldb_table( 'block_ilp_plu_wsts' );
        $set_attributes = method_exists($xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_report = new $xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, null);
        $table->addField($table_report);

        //1=single, 2=multi cf blocks/ilp/constants.php
        $table_optiontype = new $xmldb_field('selecttype');
        $table_optiontype->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, null);
        $table->addField($table_optiontype);

        //0= save to sts_ent, 2= save to userstatus (update user status)
        $table_optiontype = new $xmldb_field('savetype');
        $table_optiontype->$set_attributes(XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, null);
        $table->addField($table_optiontype);

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $xmldb_key('textplugin_unique_reportfield');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('reportfield_id'),'block_ilp_report_field','id');
        $table->addKey($table_key);


        if(!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new $xmldb_table( 'block_ilp_plu_wsts_items' );

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_textfieldid = new $xmldb_field('parent_id');
        $table_textfieldid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_textfieldid);

        $table_itemvalue = new $xmldb_field('value');
        $table_itemvalue->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
        $table->addField($table_itemvalue);

        $table_itemname = new $xmldb_field('name');
        $table_itemname->$set_attributes(XMLDB_TYPE_CHAR, 255, null, XMLDB_NOTNULL);
        $table->addField($table_itemname);

        $table_hexcolour = new $xmldb_field('hexcolour');
        $table_hexcolour->$set_attributes(XMLDB_TYPE_CHAR, 255, null);
        $table->addField($table_hexcolour);

        $table_icon = new $xmldb_field('icon');
        $table_icon->$set_attributes(XMLDB_TYPE_CHAR, 45, null);
        $table->addField($table_icon);

        $table_display_option = new $xmldb_field('display_option');
        $table_display_option->$set_attributes(XMLDB_TYPE_CHAR, 4, null);
        $table->addField($table_display_option);

        $table_description = new $xmldb_field('description');
        $table_description->$set_attributes(XMLDB_TYPE_CHAR, 255, null);
        $table->addField($table_description);

        $table_bg_colour = new $xmldb_field('bg_colour');
        $table_bg_colour->$set_attributes(XMLDB_TYPE_CHAR, 45, null);
        $table->addField($table_bg_colour);

        //special field to categorise states as pass or fail
        //0=unset,1=fail,2=pass
        $table_itempassfail = new $xmldb_field( 'passfail' );
        $table_itempassfail->$set_attributes( XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL, '0', null, null, '0' );
        $table->addField( $table_itempassfail );

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $xmldb_key('listplugin_unique_fk');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), 'block_ilp_plu_wsts', 'id');
        $table->addKey($table_key);

        if(!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // create the new table to store responses to fields
        $table = new $xmldb_table( 'block_ilp_plu_wsts_ent' );

        $table_id = new $xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_maxlength = new $xmldb_field('parent_id');
        $table_maxlength->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_maxlength);

        $table_item_id = new $xmldb_field('value');	//foreign key -> $this->items_tablename
        $table_item_id->$set_attributes(XMLDB_TYPE_CHAR, 255, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_item_id);

        $table_report = new $xmldb_field('entry_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);

        $table_timemodified = new $xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_userid = new $xmldb_field('user_id');
        $table_userid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_userid);

        $table_key = new $xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $xmldb_key('listpluginentry_unique_fk');
        $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), 'block_ilp_plu_wsts', 'id');
        $table->addKey($table_key);

        if(!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

    }
    if ($oldversion < 2013081502) {
        $start_item = new stdClass();
        $start_item->parent_id = 1;
        $start_item->value = 'w1';
        $start_item->name = 'W1';
        $start_item->hexcolour = '';
        $start_item->icon = 'icon';
        $start_item->display_option = 'text';
        $start_item->description = 'warning one';
        $start_item->bg_colour = '';
        $start_item->passfail = 0;
        $start_item->timemodified = time();
        $start_item->timecreated = time();
        $DB->insert_record('block_ilp_plu_wsts_items', $start_item);

        $start_item->value = 'w2';
        $start_item->name = 'W2';
        $start_item->description = 'warning two';
        $DB->insert_record('block_ilp_plu_wsts_items', $start_item);

        $start_item->value = 'w3';
        $start_item->name = 'W3';
        $start_item->description = 'warning three';
        $DB->insert_record('block_ilp_plu_wsts_items', $start_item);

        $start_item->value = 'w4';
        $start_item->name = 'W4';
        $start_item->description = 'warning four';
        $DB->insert_record('block_ilp_plu_wsts_items', $start_item);
    }


    if ($oldversion < 2014070100) {

        // Define index report_entry (not unique) to be added to block_ilp_entry
        $table = new xmldb_table('block_ilp_entry');
        $index = new xmldb_index('report_entry', XMLDB_INDEX_NOTUNIQUE, array('report_id', 'user_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        //creates a index on timemodified and user_id in the entry table
        $index = new xmldb_index('entry_user_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified', 'user_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $index = new xmldb_index('entry_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

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

        $table = new xmldb_table('block_ilp_plu_tex_ent');
        $index = new xmldb_index('tex_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_ilp_plu_are_ent');
        $index = new xmldb_index('area_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        if ($dbman->table_exists('block_ilp_plu_datf_ent'))       {
            $table = new xmldb_table('block_ilp_plu_datf_ent');
            $index = new xmldb_index('datf_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }


        $table = new xmldb_table('block_ilp_plu_ste_ent');
        $index = new xmldb_index('ste_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }


        $table = new xmldb_table('block_ilp_plu_ste_ent');
        $index = new xmldb_index('ste_entry_parent', XMLDB_INDEX_NOTUNIQUE, array('entry_id','parent_id'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        if ($dbman->table_exists('block_ilp_plu_ddl_ent')) {
            $table = new xmldb_table('block_ilp_plu_ddl_ent');
            $index = new xmldb_index('ddl_entry', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_ddl_ent');
            $index = new xmldb_index('ddl_entry_parent', XMLDB_INDEX_NOTUNIQUE, array('entry_id','parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }



 // indexes from ilp_element_plugin_itemlist.class that have same names

        if ($dbman->table_exists('block_ilp_plu_cat_ent')) {
            $table = new xmldb_table('block_ilp_plu_cat_ent');
            $index = new xmldb_index('entry_idx', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_cat_ent');
            $index = new xmldb_index('entry_parent_idx', XMLDB_INDEX_NOTUNIQUE, array('entry_id','parent_id'));
            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }
        if ($dbman->table_exists('block_ilp_plu_chb_ent')) {
            $table = new xmldb_table('block_ilp_plu_chb_ent');
            $index = new xmldb_index('entry_idx', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_chb_ent');
            $index = new xmldb_index('entry_parent_idx', XMLDB_INDEX_NOTUNIQUE, array('entry_id','parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        if ($dbman->table_exists('block_ilp_plu_dd_ent')) {
            $table = new xmldb_table('block_ilp_plu_dd_ent');
            $index = new xmldb_index('entry_idx', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_dd_ent');
            $index = new xmldb_index('entry_parent_idx', XMLDB_INDEX_NOTUNIQUE, array('entry_id','parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        if ($dbman->table_exists('block_ilp_plu_rdo_ent')) {
            $table = new xmldb_table('block_ilp_plu_rdo_ent');
            $index = new xmldb_index('entry_idx', XMLDB_INDEX_NOTUNIQUE, array('entry_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }

            $table = new xmldb_table('block_ilp_plu_rdo_ent');
            $index = new xmldb_index('entry_parent_idx', XMLDB_INDEX_NOTUNIQUE, array('entry_id','parent_id'));

            // Conditionally launch add index report_entry
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        $table = new xmldb_table('block_ilp_user_status');
        $index = new xmldb_index('userstat_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('block_ilp_entry_comment');
        $index = new xmldb_index('entrycom_timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));

        // Conditionally launch add index report_entry
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }


        // Ilp savepoint reached.
        upgrade_block_savepoint(true, 2014070100, 'ilp');


    }





    // Update default values in html_editor and datefield tables
    if ($oldversion < 2015033100)	{

        $table = new $xmldb_table( 'block_ilp_plu_hte' );

        $field = new xmldb_field('minimumlength',XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $dbman->change_field_default($table,$field);

        $field = new xmldb_field('maximumlength',XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $dbman->change_field_default($table,$field);

        $table = new $xmldb_table( 'block_ilp_plu_datf' );

        $field = new xmldb_field('datetype',XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $dbman->change_field_default($table,$field);

        $field = new xmldb_field('scalendar',XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $dbman->change_field_default($table,$field);

        $field = new xmldb_field('ucalendar',XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $dbman->change_field_default($table,$field);

        $field = new xmldb_field('reminder',XMLDB_TYPE_INTEGER, 1, XMLDB_UNSIGNED, XMLDB_NOTNULL,null,0);
        $dbman->change_field_default($table,$field);


        // Ilp savepoint reached.
        upgrade_block_savepoint(true, 2015033100, 'ilp');
    }








    return true;
}


?>