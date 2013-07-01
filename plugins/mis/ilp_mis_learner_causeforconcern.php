<?php 


            require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

                        class ilp_mis_learner_causeforconcern extends ilp_mis_plugin	{

                                protected 	$fields;
                                protected 	$mis_user_id;
       
            public  function    __construct($params=array())  {
                parent::__construct($params);

                $this->tabletype    =   get_config('block_ilp','mis_learner_causeforconcern_tabletype');
                $this->fields       =   array();
            }
        
                

               /**
                * Adds settings for this plugin to the admin settings
                * @see ilp_mis_plugin::config_settings()
                */
                public function config_settings(&$settings)	{
                    global $CFG;

                    $link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_causeforconcern&plugintype=mis">'.get_string('ilp_mis_learner_causeforconcern_pluginnamesettings', 'block_ilp').'</a>';
                    $settings->add(new admin_setting_heading('block_ilp_learner_causeforconcern', '', $link));
                 }

        
                    

                    /**
                     * Adds config settings for the plugin to the given mform
                     * @see ilp_plugin::config_form()
                     */
 	                 function config_form(&$mform)	{

 	                        $this->config_text_element($mform,'mis_learner_causeforconcern_table',get_string('ilp_mis_learner_causeforconcern_table', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_tabledesc', 'block_ilp'),'');
 	                        $this->config_text_element($mform,'mis_learner_causeforconcern_studentid',get_string('ilp_mis_learner_causeforconcern_studentid', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_studentiddesc', 'block_ilp'),'');

                          $this->config_text_element($mform,'mis_learner_causeforconcern_academicyear',get_string('ilp_mis_learner_causeforconcern_academicyear', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_academicyeardesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_qualcode',get_string('ilp_mis_learner_causeforconcern_qualcode', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_qualcodedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_qualyear',get_string('ilp_mis_learner_causeforconcern_qualyear', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_qualyeardesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_qualoccl',get_string('ilp_mis_learner_causeforconcern_qualoccl', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_qualoccldesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_qualname',get_string('ilp_mis_learner_causeforconcern_qualname', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_qualnamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_tutortitle',get_string('ilp_mis_learner_causeforconcern_tutortitle', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_tutortitledesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_tutorfnm',get_string('ilp_mis_learner_causeforconcern_tutorfnm', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_tutorfnmdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_tutorsurn',get_string('ilp_mis_learner_causeforconcern_tutorsurn', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_tutorsurndesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_surname',get_string('ilp_mis_learner_causeforconcern_surname', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_surnamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_forename',get_string('ilp_mis_learner_causeforconcern_forename', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_forenamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_statusc',get_string('ilp_mis_learner_causeforconcern_statusc', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_statuscdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_lecsurname',get_string('ilp_mis_learner_causeforconcern_lecsurname', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_lecsurnamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_lecforename',get_string('ilp_mis_learner_causeforconcern_lecforename', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_lecforenamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_lectitle',get_string('ilp_mis_learner_causeforconcern_lectitle', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_lectitledesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_concerndescript',get_string('ilp_mis_learner_causeforconcern_concerndescript', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_concerndescriptdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_concernaction',get_string('ilp_mis_learner_causeforconcern_concernaction', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_concernactiondesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_attendance',get_string('ilp_mis_learner_causeforconcern_attendance', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_attendancedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_workcomp',get_string('ilp_mis_learner_causeforconcern_workcomp', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_workcompdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_behaviour',get_string('ilp_mis_learner_causeforconcern_behaviour', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_behaviourdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_punctuality',get_string('ilp_mis_learner_causeforconcern_punctuality', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_punctualitydesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_other',get_string('ilp_mis_learner_causeforconcern_other', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_otherdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_createddate',get_string('ilp_mis_learner_causeforconcern_createddate', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_createddatedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_datagenerated',get_string('ilp_mis_learner_causeforconcern_datagenerated', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_datagenerateddesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_ssuid',get_string('ilp_mis_learner_causeforconcern_ssuid', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_ssuiddesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforconcern_',get_string('ilp_mis_learner_causeforconcern_', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_desc', 'block_ilp'),'');

                          $this->config_text_element($mform,'mis_learner_causeforconcern_prelimcalls',get_string('ilp_mis_learner_causeforconcern_prelimcalls', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_prelimcallsdesc', 'block_ilp'),'');

                            $options = array(
                                 ILP_DISABLED => get_string('disabled', 'block_ilp'),
                                 ILP_ENABLED => get_string('enabled', 'block_ilp')
                            );

                            $this->config_select_element($mform, 'mis_learner_causeforconcern_yearfilter', $options, get_string('ilp_mis_learner_causeforconcern_yearfilter', 'block_ilp'), get_string('ilp_mis_learner_causeforconcern_yearfilterdesc', 'block_ilp'), 0);

                            $this->config_text_element($mform, 'mis_learner_causeforconcern_yearfilter_field', get_string('ilp_mis_learner_causeforconcern_yearfilter_field', 'block_ilp'), get_string('ilp_mis_learner_causeforconcern_yearfilter_fielddesc', 'block_ilp'), 'year');

                            $this->config_text_element($mform, 'mis_learner_causeforconcern_yearfilter_year', get_string('ilp_mis_learner_causeforconcern_yearfilter_year', 'block_ilp'), get_string('ilp_mis_learner_causeforconcern_yearfilter_yeardesc', 'block_ilp'), date('Y'));



                            $options = array(
                                 ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
                                 ILP_IDTYPE_INT		=> get_string('intid','block_ilp')
                            );

 	 	                    $this->config_select_element($mform,'mis_learner_causeforconcern_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);


                            $options = array(
                                 ILP_MIS_TABLE => get_string('table','block_ilp'),
                                 ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp')
                            );

                            $this->config_select_element($mform,'mis_learner_causeforconcern_tabletype',$options,get_string('ilp_mis_learner_causeforconcern_tabletype', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_tabletypedesc', 'block_ilp'),1);

                            $options = array(
                                ILP_ENABLED => get_string('enabled','block_ilp'),
                                ILP_DISABLED => get_string('disabled','block_ilp')
                            );

                            $this->config_select_element($mform,'ilp_mis_learner_causeforconcern_pluginstatus',$options,get_string('ilp_mis_learner_causeforconcern_pluginstatus', 'block_ilp'),get_string('ilp_mis_learner_causeforconcern_pluginstatusdesc', 'block_ilp'),0);
                    }
        
            

         	/**
 	         * Retrieves data from the mis
 	         *
 	         * @param	$mis_user_id	the id of the user in the mis used to retrieve the data of the user
 	         *
 	         * @return	null
 	         */
             public function set_data( $mis_user_id, $user_id=null ) {

                $this->mis_user_id      =   $mis_user_id;

                $table  =  get_config('block_ilp','mis_learner_causeforconcern_table');

                if (!empty($table)) {

                    $sidfield   =   get_config('block_ilp','mis_learner_causeforconcern_studentid');

                    //is the id a string or a int
                    $idtype     =   get_config('block_ilp','mis_learner_causeforconcern_idtype');
                    $mis_user_id    =   (empty($idtype))    ?  "'$mis_user_id'" : $mis_user_id;

                    $keyfields      =   array();

                    $useyearfilter  =   get_config('block_ilp','mis_learner_causeforconcern_yearfilter');

                    if (!empty($useyearfilter))     {

                        $yearfilterfield    = get_config('block_ilp','mis_learner_causeforconcern_yearfilter_field');
                        $yearfilteryear     = get_config('block_ilp','mis_learner_causeforconcern_yearfilter_year');

                        $keyfields[$yearfilterfield] = array('=' => $yearfilteryear);
                    }

                    //create the key that will be used in sql query
                    $keyfields[$sidfield]   =   array('=' => $mis_user_id);

                    
                                    //check if the academicyear config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_academicyear')) $this->fields['academicyear']  = get_config('block_ilp','mis_learner_causeforconcern_academicyear');
                                    
                                    //check if the qualcode config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_qualcode')) $this->fields['qualcode']  = get_config('block_ilp','mis_learner_causeforconcern_qualcode');
                                    
                                    //check if the qualyear config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_qualyear')) $this->fields['qualyear']  = get_config('block_ilp','mis_learner_causeforconcern_qualyear');
                                    
                                    //check if the qualoccl config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_qualoccl')) $this->fields['qualoccl']  = get_config('block_ilp','mis_learner_causeforconcern_qualoccl');
                                    
                                    //check if the qualname config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_qualname')) $this->fields['qualname']  = get_config('block_ilp','mis_learner_causeforconcern_qualname');
                                    
                                    //check if the tutortitle config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_tutortitle')) $this->fields['tutortitle']  = get_config('block_ilp','mis_learner_causeforconcern_tutortitle');
                                    
                                    //check if the tutorfnm config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_tutorfnm')) $this->fields['tutorfnm']  = get_config('block_ilp','mis_learner_causeforconcern_tutorfnm');
                                    
                                    //check if the tutorsurn config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_tutorsurn')) $this->fields['tutorsurn']  = get_config('block_ilp','mis_learner_causeforconcern_tutorsurn');
                                    
                                    //check if the surname config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_surname')) $this->fields['surname']  = get_config('block_ilp','mis_learner_causeforconcern_surname');
                                    
                                    //check if the forename config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_forename')) $this->fields['forename']  = get_config('block_ilp','mis_learner_causeforconcern_forename');
                                    
                                    //check if the statusc config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_statusc')) $this->fields['statusc']  = get_config('block_ilp','mis_learner_causeforconcern_statusc');
                                    
                                    //check if the lecsurname config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_lecsurname')) $this->fields['lecsurname']  = get_config('block_ilp','mis_learner_causeforconcern_lecsurname');
                                    
                                    //check if the lecforename config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_lecforename')) $this->fields['lecforename']  = get_config('block_ilp','mis_learner_causeforconcern_lecforename');
                                    
                                    //check if the lectitle config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_lectitle')) $this->fields['lectitle']  = get_config('block_ilp','mis_learner_causeforconcern_lectitle');
                                    
                                    //check if the concerndescript config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_concerndescript')) $this->fields['concerndescript']  = get_config('block_ilp','mis_learner_causeforconcern_concerndescript');
                                    
                                    //check if the concernaction config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_concernaction')) $this->fields['concernaction']  = get_config('block_ilp','mis_learner_causeforconcern_concernaction');
                                    
                                    //check if the attendance config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_attendance')) $this->fields['attendance']  = get_config('block_ilp','mis_learner_causeforconcern_attendance');
                                    
                                    //check if the workcomp config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_workcomp')) $this->fields['workcomp']  = get_config('block_ilp','mis_learner_causeforconcern_workcomp');
                                    
                                    //check if the behaviour config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_behaviour')) $this->fields['behaviour']  = get_config('block_ilp','mis_learner_causeforconcern_behaviour');
                                    
                                    //check if the punctuality config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_punctuality')) $this->fields['punctuality']  = get_config('block_ilp','mis_learner_causeforconcern_punctuality');
                                    
                                    //check if the other config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_other')) $this->fields['other']  = get_config('block_ilp','mis_learner_causeforconcern_other');
                                    
                                    //check if the createddate config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_createddate')) $this->fields['createddate']  = get_config('block_ilp','mis_learner_causeforconcern_createddate');
                                    
                                    //check if the datagenerated config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_datagenerated')) $this->fields['datagenerated']  = get_config('block_ilp','mis_learner_causeforconcern_datagenerated');
                                    
                                    //check if the ssuid config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_ssuid')) $this->fields['ssuid']  = get_config('block_ilp','mis_learner_causeforconcern_ssuid');
                                    
                                    //check if the  config has been set and pass the value
                                    if (get_config('block_ilp','mis_learner_causeforconcern_')) $this->fields['']  = get_config('block_ilp','mis_learner_causeforconcern_');
                                    
                      $prelimdbcalls   =    get_config('block_ilp','mis_learner_causeforconcern_prelimcalls');

                      $this->data	=	$this->dbquery( $table, $keyfields, $this->fields,null,$prelimdbcalls);
                      
 		          }
             }
        

                /**
 	             *
 	             * @see ilp_mis_plugin::display()
 	             */
 	             function display()	{

 		                global $CFG;

 		                if (!empty($this->data)) {


                            $pluginoutput =    '';
                            //buffer output
                            ob_start();


                  echo " 
                <div id='ilp_mis_learner_causeforconcern'>
                    <h3>". get_string('ilp_mis_learner_causeforconcern_disp_tabname','block_ilp') ."</h3>
             "; foreach ($this->data    as  $misdata)  { echo "<div class='causeforconcern_display'>"; 

                            //call the html file for the plugin
                            require($CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_learner_causeforconcern.html');
                         echo "</div>"; } echo " </div>
               <div class='clearer'></div>
         "; $pluginoutput .= ob_get_contents();
                            ob_end_clean();

                            return $pluginoutput;
	                    } else {
                            echo '<div id="plugin_nodata">'.get_string('nodataornoconfig', 'block_ilp').'</div>';
                	    }
 	             }
        
                    

                    static function language_strings(&$string) {

                            $string['ilp_mis_learner_causeforconcern_table']						    = 'Database table';
                            $string['ilp_mis_learner_causeforconcern_tabledesc']				        = 'The name of the database table where the data for this plugin is held';

                            $string['ilp_mis_learner_causeforconcern_studentid']						    = 'Student Id field';
                            $string['ilp_mis_learner_causeforconcern_studentiddesc']				        = 'The id field used to find the student data in the database table';

                            $string['ilp_mis_learner_causeforconcern_tabletype']						= 'Table type';
                            $string['ilp_mis_learner_causeforconcern_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';

                            $string['ilp_mis_learner_causeforconcern_pluginstatus']				    = 'Status';
                            $string['ilp_mis_learner_causeforconcern_pluginstatusdesc']			    = 'Is the block enabled or disabled';

                        $string['ilp_mis_learner_causeforconcern_prelimcalls']						= 'Preliminary db calls';
                        $string['ilp_mis_learner_causeforconcern_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';

                            $string['ilp_mis_learner_causeforconcern_yearfilter']                      = 'Year filter';
                            $string['ilp_mis_learner_causeforconcern_yearfilterdesc']                  = 'Is a year filter used when selecting data from the MIS';

                            $string['ilp_mis_learner_causeforconcern_yearfilter_field']                = 'Year filter field';
                            $string['ilp_mis_learner_causeforconcern_yearfilter_fielddesc']            = 'If a MIS year filter is being used enter the field that will be filter on. (if stored procedure and field not needed leave field as year)';

                            $string['ilp_mis_learner_causeforconcern_yearfilter_year']               = 'Year filter date';
                            $string['ilp_mis_learner_causeforconcern_yearfilter_yeardesc']           = 'The date that will be filtered on';

                            $string['ilp_mis_learner_causeforconcern_pluginname']					= 'Cause for Concern';
                            $string['ilp_mis_learner_causeforconcern_pluginnamesettings']			= 'Cause for Concern configuration';

            

                             $string['ilp_mis_learner_causeforconcern_academicyear']							= 'Academic Year data field';
                             $string['ilp_mis_learner_causeforconcern_academicyeardesc']						= 'The field that holds Academic Year data';

                             $string['ilp_mis_learner_causeforconcern_qualcode']							= 'Qualification Code data field';
                             $string['ilp_mis_learner_causeforconcern_qualcodedesc']						= 'The field that holds Qualification Code data';

                             $string['ilp_mis_learner_causeforconcern_qualyear']							= 'Qualification Year data field';
                             $string['ilp_mis_learner_causeforconcern_qualyeardesc']						= 'The field that holds Qualification Year data';

                             $string['ilp_mis_learner_causeforconcern_qualoccl']							= 'Qualification Occl data field';
                             $string['ilp_mis_learner_causeforconcern_qualoccldesc']						= 'The field that holds Qualification Occl data';

                             $string['ilp_mis_learner_causeforconcern_qualname']							= 'Qualification Name data field';
                             $string['ilp_mis_learner_causeforconcern_qualnamedesc']						= 'The field that holds Qualification Name data';

                             $string['ilp_mis_learner_causeforconcern_tutortitle']							= 'Tutor Title data field';
                             $string['ilp_mis_learner_causeforconcern_tutortitledesc']						= 'The field that holds Tutor Title data';

                             $string['ilp_mis_learner_causeforconcern_tutorfnm']							= 'Tutor Firstname data field';
                             $string['ilp_mis_learner_causeforconcern_tutorfnmdesc']						= 'The field that holds Tutor Firstname data';

                             $string['ilp_mis_learner_causeforconcern_tutorsurn']							= 'Tutor Surname data field';
                             $string['ilp_mis_learner_causeforconcern_tutorsurndesc']						= 'The field that holds Tutor Surname data';

                             $string['ilp_mis_learner_causeforconcern_surname']							= 'Surname data field';
                             $string['ilp_mis_learner_causeforconcern_surnamedesc']						= 'The field that holds Surname data';

                             $string['ilp_mis_learner_causeforconcern_forename']							= 'Firstname data field';
                             $string['ilp_mis_learner_causeforconcern_forenamedesc']						= 'The field that holds Firstname data';

                             $string['ilp_mis_learner_causeforconcern_statusc']							= 'Status data field';
                             $string['ilp_mis_learner_causeforconcern_statuscdesc']						= 'The field that holds Status data';

                             $string['ilp_mis_learner_causeforconcern_lecsurname']							= 'Lecturer Surname data field';
                             $string['ilp_mis_learner_causeforconcern_lecsurnamedesc']						= 'The field that holds Lecturer Surname data';

                             $string['ilp_mis_learner_causeforconcern_lecforename']							= 'Lecturer Firstname data field';
                             $string['ilp_mis_learner_causeforconcern_lecforenamedesc']						= 'The field that holds Lecturer Firstname data';

                             $string['ilp_mis_learner_causeforconcern_lectitle']							= 'Lecturer Title data field';
                             $string['ilp_mis_learner_causeforconcern_lectitledesc']						= 'The field that holds Lecturer Title data';

                             $string['ilp_mis_learner_causeforconcern_concerndescript']							= 'Concern Description data field';
                             $string['ilp_mis_learner_causeforconcern_concerndescriptdesc']						= 'The field that holds Concern Description data';

                             $string['ilp_mis_learner_causeforconcern_concernaction']							= 'Concern Action data field';
                             $string['ilp_mis_learner_causeforconcern_concernactiondesc']						= 'The field that holds Concern Action data';

                             $string['ilp_mis_learner_causeforconcern_attendance']							= 'Attendance  data field';
                             $string['ilp_mis_learner_causeforconcern_attendancedesc']						= 'The field that holds Attendance  data';

                             $string['ilp_mis_learner_causeforconcern_workcomp']							= 'Work Completed data field';
                             $string['ilp_mis_learner_causeforconcern_workcompdesc']						= 'The field that holds Work Completed data';

                             $string['ilp_mis_learner_causeforconcern_behaviour']							= 'Behaviour data field';
                             $string['ilp_mis_learner_causeforconcern_behaviourdesc']						= 'The field that holds Behaviour data';

                             $string['ilp_mis_learner_causeforconcern_punctuality']							= 'Punctuality  data field';
                             $string['ilp_mis_learner_causeforconcern_punctualitydesc']						= 'The field that holds Punctuality  data';

                             $string['ilp_mis_learner_causeforconcern_other']							= 'Other data field';
                             $string['ilp_mis_learner_causeforconcern_otherdesc']						= 'The field that holds Other data';

                             $string['ilp_mis_learner_causeforconcern_createddate']							= 'Creation Date data field';
                             $string['ilp_mis_learner_causeforconcern_createddatedesc']						= 'The field that holds Creation Date data';

                             $string['ilp_mis_learner_causeforconcern_datagenerated']							= 'Data Generated data field';
                             $string['ilp_mis_learner_causeforconcern_datagenerateddesc']						= 'The field that holds Data Generated data';

                             $string['ilp_mis_learner_causeforconcern_ssuid']							= 'SSUID data field';
                             $string['ilp_mis_learner_causeforconcern_ssuiddesc']						= 'The field that holds SSUID data';

                             $string['ilp_mis_learner_causeforconcern_']							= ' data field';
                             $string['ilp_mis_learner_causeforconcern_desc']						= 'The field that holds  data';
$string['ilp_mis_learner_causeforconcern_disp_tabname']							= 'Cause for Concern';
$string['ilp_mis_learner_causeforconcern_disp_academicyear']							= 'Academic Year';
$string['ilp_mis_learner_causeforconcern_disp_qualcode']							= 'Qualification Code';
$string['ilp_mis_learner_causeforconcern_disp_qualyear']							= 'Qualification Year';
$string['ilp_mis_learner_causeforconcern_disp_qualoccl']							= 'Qualification Occl';
$string['ilp_mis_learner_causeforconcern_disp_qualname']							= 'Qualification Name';
$string['ilp_mis_learner_causeforconcern_disp_tutortitle']							= 'Tutor Title';
$string['ilp_mis_learner_causeforconcern_disp_tutorfnm']							= 'Tutor Firstname';
$string['ilp_mis_learner_causeforconcern_disp_tutorsurn']							= 'Tutor Surname';
$string['ilp_mis_learner_causeforconcern_disp_surname']							= 'Surname';
$string['ilp_mis_learner_causeforconcern_disp_forename']							= 'Firstname';
$string['ilp_mis_learner_causeforconcern_disp_statusc']							= 'Status';
$string['ilp_mis_learner_causeforconcern_disp_lecsurname']							= 'Lecturer Surname';
$string['ilp_mis_learner_causeforconcern_disp_lecforename']							= 'Lecturer Firstname';
$string['ilp_mis_learner_causeforconcern_disp_lectitle']							= 'Lecturer Title';
$string['ilp_mis_learner_causeforconcern_disp_concerndescript']							= 'Concern Description';
$string['ilp_mis_learner_causeforconcern_disp_concernaction']							= 'Concern Action';
$string['ilp_mis_learner_causeforconcern_disp_attendance']							= 'Attendance ';
$string['ilp_mis_learner_causeforconcern_disp_workcomp']							= 'Work Completed';
$string['ilp_mis_learner_causeforconcern_disp_behaviour']							= 'Behaviour';
$string['ilp_mis_learner_causeforconcern_disp_punctuality']							= 'Punctuality ';
$string['ilp_mis_learner_causeforconcern_disp_other']							= 'Other';
$string['ilp_mis_learner_causeforconcern_disp_createddate']							= 'Creation Date';
$string['ilp_mis_learner_causeforconcern_disp_datagenerated']							= 'Data Generated';
$string['ilp_mis_learner_causeforconcern_disp_ssuid']							= 'SSUID';
$string['ilp_mis_learner_causeforconcern_disp_']							= '';
$string['ilp_mis_learner_causeforconcern_tab_name']							= 'Cause for Concern';}

                          static function plugin_type()	{
                                return 'learnerprofile';
                          }
                

                /**
                 * This function is used if the plugin is displayed in the tab menu.
                 * Do not use a menu string in this function as it will cause errors
                 *
                 */
                function tab_name() {
                    return get_string('ilp_mis_learner_causeforconcern_tab_name','block_ilp');
                }
                }?>