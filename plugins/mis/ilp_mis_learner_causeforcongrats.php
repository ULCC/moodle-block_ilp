<?php 


require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

class ilp_mis_learner_causeforcongrats extends ilp_mis_plugin	{

            protected 	$fields;
            protected 	$mis_user_id;
       
            public  function    __construct($params=array())  {
                parent::__construct($params);

                $this->tabletype    =   get_config('block_ilp','mis_learner_causeforcongrats_tabletype');
                $this->fields       =   array();
            }
        
                

            /**
             * Adds settings for this plugin to the admin settings
             * @see ilp_mis_plugin::config_settings()
             */
             public function config_settings(&$settings)	{
                 global $CFG;

                 $link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_causeforcongrats&plugintype=mis">'.get_string('ilp_mis_learner_causeforcongrats_pluginnamesettings', 'block_ilp').'</a>';
                 $settings->add(new admin_setting_heading('block_ilp_learner_causeforcongrats', '', $link));
             }

        
                    

             /**
              * Adds config settings for the plugin to the given mform
              * @see ilp_plugin::config_form()
              */
              function config_form(&$mform)	{

 	                 $this->config_text_element($mform,'mis_learner_causeforcongrats_table',get_string('ilp_mis_learner_causeforcongrats_table', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_tabledesc', 'block_ilp'),'');
 	                 $this->config_text_element($mform,'mis_learner_causeforcongrats_studentid',get_string('ilp_mis_learner_causeforcongrats_studentid', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_studentiddesc', 'block_ilp'),'');

 	                 $this->config_text_element($mform,'mis_learner_causeforcongrats_academicyear',get_string('ilp_mis_learner_causeforcongrats_academicyear', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_academicyeardesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_qualcode',get_string('ilp_mis_learner_causeforcongrats_qualcode', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_qualcodedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_qualyear',get_string('ilp_mis_learner_causeforcongrats_qualyear', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_qualyeardesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_qualoccl',get_string('ilp_mis_learner_causeforcongrats_qualoccl', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_qualoccldesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_qualname',get_string('ilp_mis_learner_causeforcongrats_qualname', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_qualnamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_tutortitle',get_string('ilp_mis_learner_causeforcongrats_tutortitle', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_tutortitledesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_tutorfnm',get_string('ilp_mis_learner_causeforcongrats_tutorfnm', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_tutorfnmdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_tutorsurn',get_string('ilp_mis_learner_causeforcongrats_tutorsurn', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_tutorsurndesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_surname',get_string('ilp_mis_learner_causeforcongrats_surname', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_surnamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_forename',get_string('ilp_mis_learner_causeforcongrats_forename', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_forenamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_statusc',get_string('ilp_mis_learner_causeforcongrats_statusc', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_statuscdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_lecsurname',get_string('ilp_mis_learner_causeforcongrats_lecsurname', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_lecsurnamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_lecforename',get_string('ilp_mis_learner_causeforcongrats_lecforename', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_lecforenamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_lectitle',get_string('ilp_mis_learner_causeforcongrats_lectitle', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_lectitledesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_cfcdescript',get_string('ilp_mis_learner_causeforcongrats_cfcdescript', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_cfcdescriptdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_attendance',get_string('ilp_mis_learner_causeforcongrats_attendance', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_attendancedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_workcomp',get_string('ilp_mis_learner_causeforcongrats_workcomp', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_workcompdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_behaviour',get_string('ilp_mis_learner_causeforcongrats_behaviour', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_behaviourdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_punctuality',get_string('ilp_mis_learner_causeforcongrats_punctuality', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_punctualitydesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_other',get_string('ilp_mis_learner_causeforcongrats_other', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_otherdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_causeforcongrats_',get_string('ilp_mis_learner_causeforcongrats_', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_desc', 'block_ilp'),'');

                     $this->config_text_element($mform,'mis_learner_causeforcongrats_prelimcalls',get_string('ilp_mis_learner_causeforcongrats_prelimcalls', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_prelimcallsdesc', 'block_ilp'),'');

                     $options = array(
                          ILP_DISABLED => get_string('disabled', 'block_ilp'),
                          ILP_ENABLED => get_string('enabled', 'block_ilp')
                     );

                     $this->config_select_element($mform, 'mis_learner_causeforcongrats_yearfilter', $options, get_string('ilp_mis_learner_causeforcongrats_yearfilter', 'block_ilp'), get_string('ilp_mis_learner_causeforcongrats_yearfilterdesc', 'block_ilp'), 0);

                     $this->config_text_element($mform, 'mis_learner_causeforcongrats_yearfilter_field', get_string('ilp_mis_learner_causeforcongrats_yearfilter_field', 'block_ilp'), get_string('ilp_mis_learner_causeforcongrats_yearfilter_fielddesc', 'block_ilp'), 'year');

                     $this->config_text_element($mform, 'mis_learner_causeforcongrats_yearfilter_year', get_string('ilp_mis_learner_causeforcongrats_yearfilter_year', 'block_ilp'), get_string('ilp_mis_learner_causeforcongrats_yearfilter_yeardesc', 'block_ilp'), date('Y'));

                     $options = array(
                         ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
                         ILP_IDTYPE_INT		=> get_string('intid','block_ilp')
                     );

                    $this->config_select_element($mform,'mis_learner_causeforcongrats_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);


                    $options = array(
                         ILP_MIS_TABLE => get_string('table','block_ilp'),
                         ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp')
                    );

                    $this->config_select_element($mform,'mis_learner_causeforcongrats_tabletype',$options,get_string('ilp_mis_learner_causeforcongrats_tabletype', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_tabletypedesc', 'block_ilp'),1);

                    $options = array(
                        ILP_ENABLED => get_string('enabled','block_ilp'),
                        ILP_DISABLED => get_string('disabled','block_ilp')
                    );

                    $this->config_select_element($mform,'ilp_mis_learner_causeforcongrats_pluginstatus',$options,get_string('ilp_mis_learner_causeforcongrats_pluginstatus', 'block_ilp'),get_string('ilp_mis_learner_causeforcongrats_pluginstatusdesc', 'block_ilp'),0);
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

                $table  =  get_config('block_ilp','mis_learner_causeforcongrats_table');

                if (!empty($table)) {

                    $sidfield   =   get_config('block_ilp','mis_learner_causeforcongrats_studentid');

                    //is the id a string or a int
                    $idtype     =   get_config('block_ilp','mis_learner_causeforcongrats_idtype');
                    $mis_user_id    =   (empty($idtype))    ?  "'$mis_user_id'" : $mis_user_id;

                    $keyfields      =   array();

                    $useyearfilter  =   get_config('block_ilp','mis_learner_causeforcongrats_yearfilter');

                    if (!empty($useyearfilter))     {

                        $yearfilterfield    = get_config('block_ilp','mis_learner_causeforcongrats_yearfilter_field');
                        $yearfilteryear     = get_config('block_ilp','mis_learner_causeforcongrats_yearfilter_year');

                        $keyfields[$yearfilterfield] = array('=' => $yearfilteryear);
                    }

                    //create the key that will be used in sql query
                    $keyfields[$sidfield]   =   array('=' => $mis_user_id);

                    
                    //check if the academicyear config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_academicyear')) $this->fields['academicyear']  = get_config('block_ilp','mis_learner_causeforcongrats_academicyear');

                    //check if the qualcode config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_qualcode')) $this->fields['qualcode']  = get_config('block_ilp','mis_learner_causeforcongrats_qualcode');

                    //check if the qualyear config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_qualyear')) $this->fields['qualyear']  = get_config('block_ilp','mis_learner_causeforcongrats_qualyear');

                    //check if the qualoccl config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_qualoccl')) $this->fields['qualoccl']  = get_config('block_ilp','mis_learner_causeforcongrats_qualoccl');

                    //check if the qualname config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_qualname')) $this->fields['qualname']  = get_config('block_ilp','mis_learner_causeforcongrats_qualname');

                    //check if the tutortitle config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_tutortitle')) $this->fields['tutortitle']  = get_config('block_ilp','mis_learner_causeforcongrats_tutortitle');

                    //check if the tutorfnm config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_tutorfnm')) $this->fields['tutorfnm']  = get_config('block_ilp','mis_learner_causeforcongrats_tutorfnm');

                    //check if the tutorsurn config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_tutorsurn')) $this->fields['tutorsurn']  = get_config('block_ilp','mis_learner_causeforcongrats_tutorsurn');

                    //check if the surname config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_surname')) $this->fields['surname']  = get_config('block_ilp','mis_learner_causeforcongrats_surname');

                    //check if the forename config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_forename')) $this->fields['forename']  = get_config('block_ilp','mis_learner_causeforcongrats_forename');

                    //check if the statusc config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_statusc')) $this->fields['statusc']  = get_config('block_ilp','mis_learner_causeforcongrats_statusc');

                    //check if the lecsurname config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_lecsurname')) $this->fields['lecsurname']  = get_config('block_ilp','mis_learner_causeforcongrats_lecsurname');

                    //check if the lecforename config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_lecforename')) $this->fields['lecforename']  = get_config('block_ilp','mis_learner_causeforcongrats_lecforename');

                    //check if the lectitle config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_lectitle')) $this->fields['lectitle']  = get_config('block_ilp','mis_learner_causeforcongrats_lectitle');

                    //check if the cfcdescript config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_cfcdescript')) $this->fields['cfcdescript']  = get_config('block_ilp','mis_learner_causeforcongrats_cfcdescript');

                    //check if the attendance config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_attendance')) $this->fields['attendance']  = get_config('block_ilp','mis_learner_causeforcongrats_attendance');

                    //check if the workcomp config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_workcomp')) $this->fields['workcomp']  = get_config('block_ilp','mis_learner_causeforcongrats_workcomp');

                    //check if the behaviour config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_behaviour')) $this->fields['behaviour']  = get_config('block_ilp','mis_learner_causeforcongrats_behaviour');

                    //check if the punctuality config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_punctuality')) $this->fields['punctuality']  = get_config('block_ilp','mis_learner_causeforcongrats_punctuality');

                    //check if the other config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_other')) $this->fields['other']  = get_config('block_ilp','mis_learner_causeforcongrats_other');

                    //check if the  config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_causeforcongrats_')) $this->fields['']  = get_config('block_ilp','mis_learner_causeforcongrats_');

                    $prelimdbcalls   =    get_config('block_ilp','mis_learner_causeforcongrats_prelimcalls');

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

                        echo "<div id='ilp_mis_learner_causeforcongrats'>
                                <h3>". get_string('ilp_mis_learner_causeforcongrats_disp_tabname','block_ilp') ."</h3>
                            ";

                        foreach ($this->data    as  $misdata)  { echo "<div class='Cause for Congratulations_display'>";

                            //call the html file for the plugin
                            require($CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_learner_causeforcongrats.html');
                            echo "</div>";
                        }

                        echo " </div>
                                <div class='clearer'></div>
                             ";

                        $pluginoutput .= ob_get_contents();

                        ob_end_clean();

                        return $pluginoutput;

                    } else {
                            echo '<div id="plugin_nodata">'.get_string('nodataornoconfig', 'block_ilp').'</div>';
                	}
 	         }
        
                    

            static function language_strings(&$string) {

                    $string['ilp_mis_learner_causeforcongrats_table']						    = 'Database table';
                    $string['ilp_mis_learner_causeforcongrats_tabledesc']				        = 'The name of the database table where the data for this plugin is held';

                    $string['ilp_mis_learner_causeforcongrats_studentid']						    = 'Student Id field';
                    $string['ilp_mis_learner_causeforcongrats_studentiddesc']				        = 'The id field used to find the student data in the database table';

                    $string['ilp_mis_learner_causeforcongrats_tabletype']						= 'Table type';
                    $string['ilp_mis_learner_causeforcongrats_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';

                    $string['ilp_mis_learner_causeforcongrats_pluginstatus']				    = 'Status';
                    $string['ilp_mis_learner_causeforcongrats_pluginstatusdesc']			    = 'Is the block enabled or disabled';

                    $string['ilp_mis_learner_causeforcongrats_prelimcalls']						= 'Preliminary db calls';
                    $string['ilp_mis_learner_causeforcongrats_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';

                    $string['ilp_mis_learner_causeforcongrats_yearfilter']                      = 'Year filter';
                    $string['ilp_mis_learner_causeforcongrats_yearfilterdesc']                  = 'Is a year filter used when selecting data from the MIS';

                    $string['ilp_mis_learner_causeforcongrats_yearfilter_field']                = 'Year filter field';
                    $string['ilp_mis_learner_causeforcongrats_yearfilter_fielddesc']            = 'If a MIS year filter is being used enter the field that will be filter on. (if stored procedure and field not needed leave field as year)';

                    $string['ilp_mis_learner_causeforcongrats_yearfilter_year']               = 'Year filter date';
                    $string['ilp_mis_learner_causeforcongrats_yearfilter_yeardesc']           = 'The date that will be filtered on';

                    $string['ilp_mis_learner_causeforcongrats_pluginname']					= 'Cause for Congratulations';
                    $string['ilp_mis_learner_causeforcongrats_pluginnamesettings']			= 'Cause for Congratulations configuration';



                     $string['ilp_mis_learner_causeforcongrats_academicyear']							= 'Academic Year data field';
                     $string['ilp_mis_learner_causeforcongrats_academicyeardesc']						= 'The field that holds Academic Year data';

                     $string['ilp_mis_learner_causeforcongrats_qualcode']							= 'Qualification Code data field';
                     $string['ilp_mis_learner_causeforcongrats_qualcodedesc']						= 'The field that holds Qualification Code data';

                     $string['ilp_mis_learner_causeforcongrats_qualyear']							= 'Qualification Year data field';
                     $string['ilp_mis_learner_causeforcongrats_qualyeardesc']						= 'The field that holds Qualification Year data';

                     $string['ilp_mis_learner_causeforcongrats_qualoccl']							= 'Qualification Occ data field';
                     $string['ilp_mis_learner_causeforcongrats_qualoccldesc']						= 'The field that holds Qualification Occ data';

                     $string['ilp_mis_learner_causeforcongrats_qualname']							= 'Qualification Name data field';
                     $string['ilp_mis_learner_causeforcongrats_qualnamedesc']						= 'The field that holds Qualification Name data';

                     $string['ilp_mis_learner_causeforcongrats_tutortitle']							= 'Tutor title data field';
                     $string['ilp_mis_learner_causeforcongrats_tutortitledesc']						= 'The field that holds Tutor title data';

                     $string['ilp_mis_learner_causeforcongrats_tutorfnm']							= 'Tutor Firstname data field';
                     $string['ilp_mis_learner_causeforcongrats_tutorfnmdesc']						= 'The field that holds Tutor Firstname data';

                     $string['ilp_mis_learner_causeforcongrats_tutorsurn']							= 'Tutor Surname data field';
                     $string['ilp_mis_learner_causeforcongrats_tutorsurndesc']						= 'The field that holds Tutor Surname data';

                     $string['ilp_mis_learner_causeforcongrats_surname']							= 'Student Surname data field';
                     $string['ilp_mis_learner_causeforcongrats_surnamedesc']						= 'The field that holds Student Surname data';

                     $string['ilp_mis_learner_causeforcongrats_forename']							= 'Student Firstname data field';
                     $string['ilp_mis_learner_causeforcongrats_forenamedesc']						= 'The field that holds Student Firstname data';

                     $string['ilp_mis_learner_causeforcongrats_statusc']							= 'Student Status data field';
                     $string['ilp_mis_learner_causeforcongrats_statuscdesc']						= 'The field that holds Student Status data';

                     $string['ilp_mis_learner_causeforcongrats_lecsurname']							= 'Lecturer Surname data field';
                     $string['ilp_mis_learner_causeforcongrats_lecsurnamedesc']						= 'The field that holds Lecturer Surname data';

                     $string['ilp_mis_learner_causeforcongrats_lecforename']							= 'Lecturer Firstname data field';
                     $string['ilp_mis_learner_causeforcongrats_lecforenamedesc']						= 'The field that holds Lecturer Firstname data';

                     $string['ilp_mis_learner_causeforcongrats_lectitle']							= 'Lecturer Title data field';
                     $string['ilp_mis_learner_causeforcongrats_lectitledesc']						= 'The field that holds Lecturer Title data';

                     $string['ilp_mis_learner_causeforcongrats_cfcdescript']							= 'CFC description data field';
                     $string['ilp_mis_learner_causeforcongrats_cfcdescriptdesc']						= 'The field that holds CFC description data';

                     $string['ilp_mis_learner_causeforcongrats_attendance']							= 'Attendance data field';
                     $string['ilp_mis_learner_causeforcongrats_attendancedesc']						= 'The field that holds Attendance data';

                     $string['ilp_mis_learner_causeforcongrats_workcomp']							= 'Work Comp data field';
                     $string['ilp_mis_learner_causeforcongrats_workcompdesc']						= 'The field that holds Work Comp data';

                     $string['ilp_mis_learner_causeforcongrats_behaviour']							= 'Behaviour data field';
                     $string['ilp_mis_learner_causeforcongrats_behaviourdesc']						= 'The field that holds Behaviour data';

                     $string['ilp_mis_learner_causeforcongrats_punctuality']							= 'Punctuality data field';
                     $string['ilp_mis_learner_causeforcongrats_punctualitydesc']						= 'The field that holds Punctuality data';

                     $string['ilp_mis_learner_causeforcongrats_other']							= 'Other data field';
                     $string['ilp_mis_learner_causeforcongrats_otherdesc']						= 'The field that holds Other data';

                     $string['ilp_mis_learner_causeforcongrats_']							= ' data field';
                     $string['ilp_mis_learner_causeforcongrats_desc']						= 'The field that holds  data';
                    $string['ilp_mis_learner_causeforcongrats_disp_tabname']							= 'Cause for Congratulations';
                    $string['ilp_mis_learner_causeforcongrats_disp_academicyear']							= 'Academic Year';
                    $string['ilp_mis_learner_causeforcongrats_disp_qualcode']							= 'Qualification Code';
                    $string['ilp_mis_learner_causeforcongrats_disp_qualyear']							= 'Qualification Year';
                    $string['ilp_mis_learner_causeforcongrats_disp_qualoccl']							= 'Qualification Occ';
                    $string['ilp_mis_learner_causeforcongrats_disp_qualname']							= 'Qualification Name';
                    $string['ilp_mis_learner_causeforcongrats_disp_tutortitle']							= 'Tutor title';
                    $string['ilp_mis_learner_causeforcongrats_disp_tutorfnm']							= 'Tutor Firstname';
                    $string['ilp_mis_learner_causeforcongrats_disp_tutorsurn']							= 'Tutor Surname';
                    $string['ilp_mis_learner_causeforcongrats_disp_surname']							= 'Student Surname';
                    $string['ilp_mis_learner_causeforcongrats_disp_forename']							= 'Student Firstname';
                    $string['ilp_mis_learner_causeforcongrats_disp_statusc']							= 'Student Status';
                    $string['ilp_mis_learner_causeforcongrats_disp_lecsurname']							= 'Lecturer Surname';
                    $string['ilp_mis_learner_causeforcongrats_disp_lecforename']							= 'Lecturer Firstname';
                    $string['ilp_mis_learner_causeforcongrats_disp_lectitle']							= 'Lecturer Title';
                    $string['ilp_mis_learner_causeforcongrats_disp_cfcdescript']							= 'CFC description';
                    $string['ilp_mis_learner_causeforcongrats_disp_attendance']							= 'Attendance';
                    $string['ilp_mis_learner_causeforcongrats_disp_workcomp']							= 'Work Comp';
                    $string['ilp_mis_learner_causeforcongrats_disp_behaviour']							= 'Behaviour';
                    $string['ilp_mis_learner_causeforcongrats_disp_punctuality']							= 'Punctuality';
                    $string['ilp_mis_learner_causeforcongrats_disp_other']							= 'Other';
                    $string['ilp_mis_learner_causeforcongrats_disp_']							= '';
                    $string['ilp_mis_learner_causeforcongrats_tab_name']							= 'Cause for Congratulations';

            }

            static function plugin_type()	{
                  return 'learnerprofile';
            }


            /**
             * This function is used if the plugin is displayed in the tab menu.
             * Do not use a menu string in this function as it will cause errors
             *
             */
            function tab_name() {
                return get_string('ilp_mis_learner_causeforcongrats_tab_name','block_ilp');
            }

}
?>