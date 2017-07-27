<?php

/**
 * Class to enable the logging of user actions in the ilp admin
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ilp
 * @version 2.0
 */

class ilp_logging {

    public $dbc;

    /**
     * Protected member function to update a record and
     * add a entry into the ilp_log table
     *
     * @param string $table The name of the table being updated
     * @param $paramsobj
     * @internal param object $paramsobject the object that contains the data that will be used in the update
     * @return mixed The success of the action
     */
    protected function update_record($table,$paramsobj) {
        // set the timestamp

        $paramsobj->timemodified = time();


        $currobject = (!empty($paramsobj->id)) ? $this->dbc->get_record($table, array('id' => $paramsobj->id)) : false ;

         $success = $this->dbc->update_record($table, $paramsobj);
         
        if( $success ){
         $this->add_to_audit($table,ILP_LOG_UPDATE,$paramsobj,$currobject);
        }
         return $success;
     }

    /**
     * Protected member function to create a record and add an entry into the
     * ilp_log table
     *
     * @param string $table The name of the table where the record will be created
     * @param $paramsobj
     * @internal param object $paramsobject the object that contains the data that will be used to create the record
     * @return mixed The id of the insert or false if unsuccessful
     */
    protected function insert_record($table,$paramsobj) {
        // set the timestamp
        $paramsobj->timecreated = $paramsobj->timemodified = time();
        
        $paramsobj->id = $this->dbc->insert_record($table, $paramsobj);

        if ($paramsobj->id) {
          $this->add_to_audit($table,ILP_LOG_ADD,$paramsobj);
        }

        return $paramsobj->id;
     }

    /**
     * Protected member function to delete a record and add a entry into the
     * ilp_log table
     *
     * @param string $table The name of the table where the record will be created
     * @param mixed $params the object (or array) that contains the data that will be used to create the record
     * @param $extraparams
     * @extraparams mixed $key=>$value pairs of extra data to be inseted in logging table
     * @return mixed The success of the action
     */
    protected function delete_records( $table,$params, $extraparams ) {
         //deletes are often carried out with an array
         //audit expects a object so a quick conversion
         //is needed
         if (is_array($params)) $auditobj = (array) $params;
         $deleteobject = $this->dbc->get_records($table, $params );
         $success = $this->dbc->delete_records($table, $params);

         if (!empty($deleteobject)) {
             foreach ($deleteobject as $delobj) {
                foreach( $extraparams as $key=>$value ){
                        $delobj->$key = $value;
                }
                $this->add_to_audit($table,ILP_LOG_DELETE,$delobj);
             }

         }

         return $success;
     }

    /**
     * Adds a entry to the audit table
     *
     * @param string $table The name of the table where the record will be created
     * @param string $action the action that took place
     * @param object $newobject the object that contains the data that will be used to create the record
     * @param object $currobject the object that represents the state the record used to be in
     * @return mixed The success of the action
     */
    function add_to_audit($table, $action, $newobject, $currobject=NULL)  {
        global $USER;
        $attributes    =   array();
        $now = time();

        switch($table) {

            case 'block_ilp_report':
                $attributes =    array( 'id', 'creator_id', 'name', 'description' );
                break;


            case 'block_ilp_reportpermissions':
                $attributes =    array( 'id', 'role_id', 'capability_id', 'report_id' );
                break;

            case 'block_ilp_report_field':
                $attributes =    array( 'id' , 'label', 'audit_type', 'description', 'req' );
                break;

            case 'block_ilp_entry':
                $attributes = array();
                return;

            case 'block_ilp_plu_sts_items':
        		$newobject->record_id = $newobject->id;
                $attributes =    array( 'id', 'key', 'value');
                break;

            default:
                $attributes = array();

            if( ILP_LOG_UPDATE == $action ){
                if( 'block_ilp_plu_' == substr( $table, 0, 14 ) ){
                    $newobject->record_id = $newobject->id;
                    $attributes[] = 'minimumlength';
                    $attributes[] = 'maximumlength';
                    $newobject->entity = get_string( 'ilp_report_field' , 'block_ilp' );
                }
            }
        }

        $oplist = array(
                ILP_LOG_ADD => 'INSERT',
                ILP_LOG_UPDATE => 'UPDATE',
                ILP_LOG_DELETE => 'DELETE',
         );

         
         
        $ferecords 	=	$this->formelement_plugins();
        
        if (!empty($ferecords)) {
        	foreach($ferecords as $fe)	{
				$fetables[]	=	$fe->tablename."_ent";        		
        	}
        }
         
        //if the data is from an entry then 
        if( in_array( $table, $fetables ) )	{
        	
                    $newobject->entry_table = $table;
                    $attributes = array( 'id', 'entry_id', 'parent_id', 'value' , 'entry_table', 'audit_type' );

	                $log = new stdClass();
	                $log->creator_id = $USER->id;
	                $log->user_id = $USER->id;
	                $log->candidate_id = $USER->id;
	                $log->course_id = false;

                    $log->type = " " . $oplist[ $action ];
                    $log->entity = get_string( 'entrydata', 'block_ilp' );
                    
                    //we want all data fromt he same entry to be retrievable together so take entry id
                    $log->record_id = $newobject->entry_id;
	                $log->timecreated = $now;
	                $log->timemodified = $now;

                    foreach( $attributes as $value ){
                        $log->attribute = $value;
                        $log->newvalue = isset( $newobject->$value ) ? $newobject->$value : get_string('notapplicable','block_ilp');

                        $log->oldvalue = '';
	                    if ($action != ILP_LOG_ADD && 'value' == $value ) {
                            if( !empty( $currobject ) ){
                                $log->oldvalue = $currobject->value;
                            }
                        }
                        
                        //do not log the event if it is an update and this field is unchanged
                        if( trim( $log->oldvalue ) != trim( $log->newvalue ) ){
	                        $this->dbc->insert_record('block_ilp_log',$log);
                        }
                    }
        }
        else{
            //log management changes

            $validation_paramlist = array(
                'req' , 'minimumlength', 'maximumlength'
            );
	        foreach ($newobject as $key => $val) {
	            if ((in_array($key,$attributes) &&
	                ($this->diff_object($table,$newobject,$currobject,$key,$action) || $action == ILP_LOG_DELETE))) {
	
	                $log = new stdClass();
	                $log->creator_id = $USER->id;
	
	                $log->user_id = $USER->id;
	                $log->candidate_id = $USER->id;
	                $log->course_id = false;
	
                    $log->type = ( isset( $newobject->capability_id ) ) ? get_string( 'reportpermissions' , 'block_ilp' ) :  " " . $oplist[ $action ];
                    if( in_array( $key, $validation_paramlist ) ){
                        $log->type .= " " . 'validation';
                    }
	
	                $log->entity = $this->entity_type($table,$newobject);
                    
	                //record id pertain to the actual submission or
        			if( isset( $newobject->record_id ) ){
	             			$log->record_id = $newobject->record_id;
           			}
		        	else{
	                     	$log->record_id = $this->log_record_id($table,$newobject);
        			}

                    $log->attribute = $key;

	                if ( $action != ILP_LOG_ADD ) {
                        if( isset( $currobject->$key ) ){
                            $log->oldvalue = $currobject->$key;
                        }
                        elseif( isset( $newobject->$key ) ){
                            $log->oldvalue = $newobject->$key;
                        }
	                }

	                $log->newvalue = ($action != ILP_LOG_DELETE ) ? $newobject->$key : NULL;
	                $log->timecreated = $now;
	                $log->timemodified = $now;
	
	                $id = $this->dbc->insert_record('block_ilp_log',$log);
	
	
	            }
	        }
        }
    }


    /**
     * returns the record id that the given obj pertains to
     *
     * @param string $table The name of the table that will hold the object
     * @param object $obj the object that contains the data that will be logged
     * @return mixed The success of the action (object), false if it failed or null as default
     */
    private function log_record_id($table, $obj) {

        switch($table) {
            case 'block_ilp_report':
                return $obj->id;
            case 'block_ilp_reportpermissions':
                return $obj->id;
            case 'block_ilp_report_field':
                return $obj->id;
            default:
                $record_id = NULL;
        }
        return $record_id;
     }


    /**
     * Interprets the value of the attribute given
     * so that it can be read in human form
     *
     * @param string $table The name of the table that will hold the object
     * @param object $obj the object that contains the data that will be logged
     * @param string $attrib the name of the attribute
     * @param int $value the value of the attribute
     * @return mixed The success of the action
     */
    private function interpret_value($table,$obj,$attrib,$value) {
	    $new_value = NULL;

        switch($table) {
            case 'block_ilp_report':
                $new_value = $obj->$attrib;
                break;

            default:
                $new_value = $value;
        }
        return trim($new_value);

     }



     /**
     * Private member function to check if the given object attrib differs
     * from the value of its record field in the database
     *
     * @param string $table The name of the table where the record will be created
     * @param object $newobj the object that contains the new data to be checked
     * @param object $currobj the object containing the data that is currently in the DB
     * @param string $attrib the name of the attribute of the objects to be compared
     * @param int $action the database operation about to be performed
     * @return bool The success of the action
     */
     private function diff_object($table,$newobj,$currobj,$attrib,$action) {
         if ($action == ILP_LOG_UPDATE || $action == ILP_LOG_DELETE || $action == ILP_LOG_ASSESSMENT) {
             if( !isset( $currobj->$attrib ) ) {
                if( isset( $newobj->$attrib ) )	{
                    //one is set and the other isn't - they must be different
                    return true;
                }
                return false;
             }
             if (empty($currobj->$attrib)) return true;
             return ( $newobj->$attrib != $currobj->$attrib ) ? true : false;
         }

         return true;
     }

     
    /**
     * function to return an array of the form element plugins that
     * are currently installed in ilp  
     *
     * @return mixed array a list of plugin tablenames or bool false
     */
	function formelement_plugins()	{
		return $this->dbc->get_records('block_ilp_plugin');
	}

    /**
     * function to return the action type of given action
     *
     * @param string $table the table the action is targetting
     * @param int $action the action whose type we want
     * @param int $candidate_id the user id of the creator
     * @param $creator_id
     * @return string the attribute type
     */
	private function action_type($table,$action,$candidate_id,$creator_id) {
            switch($action) {
                case ILP_LOG_ADD:
                    if( $table == 'block_ilp_report' ) return get_string( 'ilp_report', 'block_ilp' );
                    if( $table == 'block_ilp_reportpermissions' ) return get_string( 'reportpermissions', 'block_ilp' );
                    if( $table == 'block_ilp_report_field' ) return get_string( 'ilp_element_plugin_add', 'block_ilp' );
                    break;
                case ILP_LOG_UPDATE:
                    if( $table == 'block_ilp_report' ) return get_string( 'ilp_report', 'block_ilp' );
                    if( $table == 'block_ilp_reportpermissions' ) return get_string( 'reportpermissions', 'block_ilp' );
                    if( $table == 'block_ilp_report_field' ) return get_string( 'ilp_element_plugin_update', 'block_ilp' );
                    break;
                case ILP_LOG_DELETE:
                    if( $table == 'block_ilp_report' ) return get_string( 'ilp_report', 'block_ilp' );
                    if( $table == 'block_ilp_reportpermissions' ) return get_string( 'reportpermissions', 'block_ilp' );
                    if( $table == 'block_ilp_report_field' ) return get_string( 'ilp_element_plugin_delete', 'block_ilp' );
                    break;

               default:
                   return get_string('unknown', 'block_ilp');
            }
     }




     /**
     * function to return the entity type of records in the given table
     *
     * @param string $table The name of the table where the record will be created
     * @param object $obj the object with the new data (optional)
     * @return string the entity type 'evidence', 'submission' or 'portfolio'
     */
     private function entity_type($table, $obj=NULL) {
		global $CFG;
		
     	//get all currently installed plugins
        $ferecords 	=	$this->dbc->get_records('block_ilp_plugin');
        
        if (!empty($ferecords)) {
        	foreach($ferecords as $fe)	{
				$fetables[]	=	$fe->tablename;        		
        	}
        }

        //check if the table is a form element plugin 
     	if (in_array($table,$fetables)) {
      		//get the form element by its tablename
     		$formelement	=	$this->get_plugin_by_tablename($table);

     		if (!empty($formelement)) {
     			//the path to the class file
     			$classfile = $CFG->dirroot."/blocks/ilp/plugins/form_elements/{$formelement->name}.php";
     			
     			require_once($classfile);
     			
     			//instantiate the elements course
				$feclass	=	new $formelement->name;
     			if (method_exists($feclass, 'audit_type'))	{
	     			//return the audit type
    	 			return $feclass->audit_type();
     			} else {
     				return get_string('unknown', 'block_ilp');
     			}
     		}
     		
     	} else {
	        switch($table) {
	            case 'block_ilp_report':
	                return get_string( 'ilp_report', 'block_ilp' );
	            case 'block_ilp_reportpermissions':
	                return get_string( 'ilp_report', 'block_ilp' );
	            case 'block_ilp_report_field':
	                return get_string( 'ilp_report_field', 'block_ilp' );
	            default:
	                return get_string('unknown', 'block_ilp');
	        }
     	}

     }


}

?>
