<?php 

    require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

    class ilp_mis_learner_studentreview extends ilp_mis_plugin	{

            protected 	$fields;
            protected 	$mis_user_id;
       
            public  function    __construct($params=array())  {
                parent::__construct($params);

                $this->tabletype    =   get_config('block_ilp','mis_learner_studentreview_tabletype');
                $this->fields       =   array();
            }
        
                

            /**
             * Adds settings for this plugin to the admin settings
             * @see ilp_mis_plugin::config_settings()
             */
             public function config_settings(&$settings)	{
                 global $CFG;

                 $link ='<a href="'.$CFG->wwwroot.'/blocks/ilp/actions/edit_plugin_config.php?pluginname=ilp_mis_learner_studentreview&plugintype=mis">'.get_string('ilp_mis_learner_studentreview_pluginnamesettings', 'block_ilp').'</a>';
                 $settings->add(new admin_setting_heading('block_ilp_learner_studentreview', '', $link));
             }

        
                    

            /**
             * Adds config settings for the plugin to the given mform
             * @see ilp_plugin::config_form()
             */
             function config_form(&$mform)	{

 	                $this->config_text_element($mform,'mis_learner_studentreview_table',get_string('ilp_mis_learner_studentreview_table', 'block_ilp'),get_string('ilp_mis_learner_studentreview_tabledesc', 'block_ilp'),'');
 	                $this->config_text_element($mform,'mis_learner_studentreview_studentid',get_string('ilp_mis_learner_studentreview_studentid', 'block_ilp'),get_string('ilp_mis_learner_studentreview_studentiddesc', 'block_ilp'),'');

 	                $this->config_text_element($mform,'mis_learner_studentreview_surname',get_string('ilp_mis_learner_studentreview_surname', 'block_ilp'),get_string('ilp_mis_learner_studentreview_surnamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_forename',get_string('ilp_mis_learner_studentreview_forename', 'block_ilp'),get_string('ilp_mis_learner_studentreview_forenamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_qualcode',get_string('ilp_mis_learner_studentreview_qualcode', 'block_ilp'),get_string('ilp_mis_learner_studentreview_qualcodedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_qualyear',get_string('ilp_mis_learner_studentreview_qualyear', 'block_ilp'),get_string('ilp_mis_learner_studentreview_qualyeardesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_qualoccl',get_string('ilp_mis_learner_studentreview_qualoccl', 'block_ilp'),get_string('ilp_mis_learner_studentreview_qualoccldesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_qualname',get_string('ilp_mis_learner_studentreview_qualname', 'block_ilp'),get_string('ilp_mis_learner_studentreview_qualnamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_qualstatus',get_string('ilp_mis_learner_studentreview_qualstatus', 'block_ilp'),get_string('ilp_mis_learner_studentreview_qualstatusdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_leccode',get_string('ilp_mis_learner_studentreview_leccode', 'block_ilp'),get_string('ilp_mis_learner_studentreview_leccodedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_lecturername',get_string('ilp_mis_learner_studentreview_lecturername', 'block_ilp'),get_string('ilp_mis_learner_studentreview_lecturernamedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_qualleccnt',get_string('ilp_mis_learner_studentreview_qualleccnt', 'block_ilp'),get_string('ilp_mis_learner_studentreview_qualleccntdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_attendperc',get_string('ilp_mis_learner_studentreview_attendperc', 'block_ilp'),get_string('ilp_mis_learner_studentreview_attendpercdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_lateperc',get_string('ilp_mis_learner_studentreview_lateperc', 'block_ilp'),get_string('ilp_mis_learner_studentreview_latepercdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_notifiedperc',get_string('ilp_mis_learner_studentreview_notifiedperc', 'block_ilp'),get_string('ilp_mis_learner_studentreview_notifiedpercdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_currentgrade',get_string('ilp_mis_learner_studentreview_currentgrade', 'block_ilp'),get_string('ilp_mis_learner_studentreview_currentgradedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_predictedgrade',get_string('ilp_mis_learner_studentreview_predictedgrade', 'block_ilp'),get_string('ilp_mis_learner_studentreview_predictedgradedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_academicyear',get_string('ilp_mis_learner_studentreview_academicyear', 'block_ilp'),get_string('ilp_mis_learner_studentreview_academicyeardesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_reviewtype',get_string('ilp_mis_learner_studentreview_reviewtype', 'block_ilp'),get_string('ilp_mis_learner_studentreview_reviewtypedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_qualtype',get_string('ilp_mis_learner_studentreview_qualtype', 'block_ilp'),get_string('ilp_mis_learner_studentreview_qualtypedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_term',get_string('ilp_mis_learner_studentreview_term', 'block_ilp'),get_string('ilp_mis_learner_studentreview_termdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_effort',get_string('ilp_mis_learner_studentreview_effort', 'block_ilp'),get_string('ilp_mis_learner_studentreview_effortdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_cfcongrats',get_string('ilp_mis_learner_studentreview_cfcongrats', 'block_ilp'),get_string('ilp_mis_learner_studentreview_cfcongratsdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_comments',get_string('ilp_mis_learner_studentreview_comments', 'block_ilp'),get_string('ilp_mis_learner_studentreview_commentsdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_commentflag',get_string('ilp_mis_learner_studentreview_commentflag', 'block_ilp'),get_string('ilp_mis_learner_studentreview_commentflagdesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_firsteditdate',get_string('ilp_mis_learner_studentreview_firsteditdate', 'block_ilp'),get_string('ilp_mis_learner_studentreview_firsteditdatedesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_uniquerowid',get_string('ilp_mis_learner_studentreview_uniquerowid', 'block_ilp'),get_string('ilp_mis_learner_studentreview_uniquerowiddesc', 'block_ilp'),'');$this->config_text_element($mform,'mis_learner_studentreview_',get_string('ilp_mis_learner_studentreview_', 'block_ilp'),get_string('ilp_mis_learner_studentreview_desc', 'block_ilp'),'');

                    $this->config_text_element($mform,'mis_learner_studentreview_prelimcalls',get_string('ilp_mis_learner_studentreview_prelimcalls', 'block_ilp'),get_string('ilp_mis_learner_studentreview_prelimcallsdesc', 'block_ilp'),'');

                    $options = array(
                         ILP_DISABLED => get_string('disabled', 'block_ilp'),
                         ILP_ENABLED => get_string('enabled', 'block_ilp')
                    );

                    $this->config_select_element($mform, 'mis_learner_studentreview_yearfilter', $options, get_string('ilp_mis_learner_studentreview_yearfilter', 'block_ilp'), get_string('ilp_mis_learner_studentreview_yearfilterdesc', 'block_ilp'), 0);

                    $this->config_text_element($mform, 'mis_learner_studentreview_yearfilter_field', get_string('ilp_mis_learner_studentreview_yearfilter_field', 'block_ilp'), get_string('ilp_mis_learner_studentreview_yearfilter_fielddesc', 'block_ilp'), 'year');

                    $this->config_text_element($mform, 'mis_learner_studentreview_yearfilter_year', get_string('ilp_mis_learner_studentreview_yearfilter_year', 'block_ilp'), get_string('ilp_mis_learner_studentreview_yearfilter_yeardesc', 'block_ilp'), date('Y'));



                    $options = array(
                         ILP_IDTYPE_STRING 	=> get_string('stringid','block_ilp'),
                         ILP_IDTYPE_INT		=> get_string('intid','block_ilp')
                    );

                    $this->config_select_element($mform,'mis_learner_studentreview_idtype',$options,get_string('idtype', 'block_ilp'),get_string('idtypedesc', 'block_ilp'),1);


                    $options = array(
                         ILP_MIS_TABLE => get_string('table','block_ilp'),
                         ILP_MIS_STOREDPROCEDURE	=> get_string('storedprocedure','block_ilp')
                    );

                    $this->config_select_element($mform,'mis_learner_studentreview_tabletype',$options,get_string('ilp_mis_learner_studentreview_tabletype', 'block_ilp'),get_string('ilp_mis_learner_studentreview_tabletypedesc', 'block_ilp'),1);

                    $options = array(
                        ILP_ENABLED => get_string('enabled','block_ilp'),
                        ILP_DISABLED => get_string('disabled','block_ilp')
                    );

                    $this->config_select_element($mform,'ilp_mis_learner_studentreview_pluginstatus',$options,get_string('ilp_mis_learner_studentreview_pluginstatus', 'block_ilp'),get_string('ilp_mis_learner_studentreview_pluginstatusdesc', 'block_ilp'),0);
             }
        
            

         	/**
 	         * Retrieves data from the mis
 	         *
 	         * @param	$mis_user_id	the id of the user in the mis used to retrieve the data of the user
 	         *
 	         * @return	null
 	         */
             public function set_data( $mis_user_id, $user_id = false ) {

                 //this check is in place as we have to make sure the user_id is populated
                 if (empty($user_id))  return false;
                $this->mis_user_id      =   $mis_user_id;

                $table  =  get_config('block_ilp','mis_learner_studentreview_table');

                if (!empty($table)) {

                    $sidfield   =   get_config('block_ilp','mis_learner_studentreview_studentid');

                    //is the id a string or a int
                    $idtype     =   get_config('block_ilp','mis_learner_studentreview_idtype');
                    $mis_user_id    =   (empty($idtype))    ?  "'$mis_user_id'" : $mis_user_id;

                    $keyfields      =   array();

                    $useyearfilter  =   get_config('block_ilp','mis_learner_studentreview_yearfilter');

                    if (!empty($useyearfilter))     {

                        $yearfilterfield    = get_config('block_ilp','mis_learner_studentreview_yearfilter_field');
                        $yearfilteryear     = get_config('block_ilp','mis_learner_studentreview_yearfilter_year');

                        $keyfields[$yearfilterfield] = array('=' => $yearfilteryear);
                    }

                    //create the key that will be used in sql query
                    $keyfields[$sidfield]   =   array('=' => $mis_user_id);

                    
                    //check if the surname config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_surname')) $this->fields['surname']  = get_config('block_ilp','mis_learner_studentreview_surname');

                    //check if the forename config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_forename')) $this->fields['forename']  = get_config('block_ilp','mis_learner_studentreview_forename');

                    //check if the qualcode config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_qualcode')) $this->fields['qualcode']  = get_config('block_ilp','mis_learner_studentreview_qualcode');

                    //check if the qualyear config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_qualyear')) $this->fields['qualyear']  = get_config('block_ilp','mis_learner_studentreview_qualyear');

                    //check if the qualoccl config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_qualoccl')) $this->fields['qualoccl']  = get_config('block_ilp','mis_learner_studentreview_qualoccl');

                    //check if the qualname config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_qualname')) $this->fields['qualname']  = get_config('block_ilp','mis_learner_studentreview_qualname');

                    //check if the qualstatus config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_qualstatus')) $this->fields['qualstatus']  = get_config('block_ilp','mis_learner_studentreview_qualstatus');

                    //check if the leccode config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_leccode')) $this->fields['leccode']  = get_config('block_ilp','mis_learner_studentreview_leccode');

                    //check if the lecturername config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_lecturername')) $this->fields['lecturername']  = get_config('block_ilp','mis_learner_studentreview_lecturername');

                    //check if the qualleccnt config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_qualleccnt')) $this->fields['qualleccnt']  = get_config('block_ilp','mis_learner_studentreview_qualleccnt');

                    //check if the attendperc config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_attendperc')) $this->fields['attendperc']  = get_config('block_ilp','mis_learner_studentreview_attendperc');

                    //check if the lateperc config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_lateperc')) $this->fields['lateperc']  = get_config('block_ilp','mis_learner_studentreview_lateperc');

                    //check if the notifiedperc config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_notifiedperc')) $this->fields['notifiedperc']  = get_config('block_ilp','mis_learner_studentreview_notifiedperc');

                    //check if the currentgrade config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_currentgrade')) $this->fields['currentgrade']  = get_config('block_ilp','mis_learner_studentreview_currentgrade');

                    //check if the predictedgrade config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_predictedgrade')) $this->fields['predictedgrade']  = get_config('block_ilp','mis_learner_studentreview_predictedgrade');

                    //check if the academicyear config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_academicyear')) $this->fields['academicyear']  = get_config('block_ilp','mis_learner_studentreview_academicyear');

                    //check if the reviewtype config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_reviewtype')) $this->fields['reviewtype']  = get_config('block_ilp','mis_learner_studentreview_reviewtype');

                    //check if the qualtype config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_qualtype')) $this->fields['qualtype']  = get_config('block_ilp','mis_learner_studentreview_qualtype');

                    //check if the term config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_term')) $this->fields['term']  = get_config('block_ilp','mis_learner_studentreview_term');

                    //check if the effort config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_effort')) $this->fields['effort']  = get_config('block_ilp','mis_learner_studentreview_effort');

                    //check if the cfcongrats config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_cfcongrats')) $this->fields['cfcongrats']  = get_config('block_ilp','mis_learner_studentreview_cfcongrats');

                    //check if the comments config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_comments')) $this->fields['comments']  = get_config('block_ilp','mis_learner_studentreview_comments');

                    //check if the commentflag config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_commentflag')) $this->fields['commentflag']  = get_config('block_ilp','mis_learner_studentreview_commentflag');

                    //check if the firsteditdate config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_firsteditdate')) $this->fields['firsteditdate']  = get_config('block_ilp','mis_learner_studentreview_firsteditdate');

                    //check if the uniquerowid config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_uniquerowid')) $this->fields['uniquerowid']  = get_config('block_ilp','mis_learner_studentreview_uniquerowid');

                    //check if the  config has been set and pass the value
                    if (get_config('block_ilp','mis_learner_studentreview_')) $this->fields['']  = get_config('block_ilp','mis_learner_studentreview_');

                    $prelimdbcalls   =    get_config('block_ilp','mis_learner_studentreview_prelimcalls');

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


                        echo "<div id='ilp_mis_learner_studentreview'>
                                <h3>". get_string('ilp_mis_learner_studentreview_disp_tabname','block_ilp') ."</h3>
                            ";

                        foreach ($this->data    as  $misdata)  {

                            echo "<div class='Student Review_display'>";

                            //call the html file for the plugin
                            require($CFG->dirroot.'/blocks/ilp/plugins/mis/ilp_mis_learner_studentreview.html');
                            echo "</div>";
                        }

                        echo "   </div>
                                 <div class='clearer'></div>";

                            $pluginoutput .= ob_get_contents();

                            ob_end_clean();

                            return $pluginoutput;
	                    } else {
                            echo '<div id="plugin_nodata">'.get_string('nodataornoconfig', 'block_ilp').'</div>';
                	    }
 	         }
        
                    

            static function language_strings(&$string) {

                    $string['ilp_mis_learner_studentreview_table']						    = 'Database table';
                    $string['ilp_mis_learner_studentreview_tabledesc']				        = 'The name of the database table where the data for this plugin is held';

                    $string['ilp_mis_learner_studentreview_studentid']						    = 'Student Id field';
                    $string['ilp_mis_learner_studentreview_studentiddesc']				        = 'The id field used to find the student data in the database table';

                    $string['ilp_mis_learner_studentreview_tabletype']						= 'Table type';
                    $string['ilp_mis_learner_studentreview_tabletypedesc']					= 'Does this plugin connect to a table or stored procedure';

                    $string['ilp_mis_learner_studentreview_pluginstatus']				    = 'Status';
                    $string['ilp_mis_learner_studentreview_pluginstatusdesc']			    = 'Is the block enabled or disabled';

                    $string['ilp_mis_learner_studentreview_prelimcalls']						= 'Preliminary db calls';
                    $string['ilp_mis_learner_studentreview_prelimcallsdesc']					= 'preliminary calls that need to be made to the db before the sql is executed';

                    $string['ilp_mis_learner_studentreview_yearfilter']                      = 'Year filter';
                    $string['ilp_mis_learner_studentreview_yearfilterdesc']                  = 'Is a year filter used when selecting data from the MIS';

                    $string['ilp_mis_learner_studentreview_yearfilter_field']                = 'Year filter field';
                    $string['ilp_mis_learner_studentreview_yearfilter_fielddesc']            = 'If a MIS year filter is being used enter the field that will be filter on. (if stored procedure and field not needed leave field as year)';

                    $string['ilp_mis_learner_studentreview_yearfilter_year']               = 'Year filter date';
                    $string['ilp_mis_learner_studentreview_yearfilter_yeardesc']           = 'The date that will be filtered on';

                    $string['ilp_mis_learner_studentreview_pluginname']					= 'Student Review';
                    $string['ilp_mis_learner_studentreview_pluginnamesettings']			= 'Student Review configuration';



                     $string['ilp_mis_learner_studentreview_surname']							= 'Surname data field';
                     $string['ilp_mis_learner_studentreview_surnamedesc']						= 'The field that holds Surname data';

                     $string['ilp_mis_learner_studentreview_forename']							= 'Firstname data field';
                     $string['ilp_mis_learner_studentreview_forenamedesc']						= 'The field that holds Firstname data';

                     $string['ilp_mis_learner_studentreview_qualcode']							= 'Qualification Code data field';
                     $string['ilp_mis_learner_studentreview_qualcodedesc']						= 'The field that holds Qualification Code data';

                     $string['ilp_mis_learner_studentreview_qualyear']							= 'Qualification Year data field';
                     $string['ilp_mis_learner_studentreview_qualyeardesc']						= 'The field that holds Qualification Year data';

                     $string['ilp_mis_learner_studentreview_qualoccl']							= 'Qualification Occ data field';
                     $string['ilp_mis_learner_studentreview_qualoccldesc']						= 'The field that holds Qualification Occ data';

                     $string['ilp_mis_learner_studentreview_qualname']							= 'Qualification Name data field';
                     $string['ilp_mis_learner_studentreview_qualnamedesc']						= 'The field that holds Qualification Name data';

                     $string['ilp_mis_learner_studentreview_qualstatus']							= 'Qualification Status data field';
                     $string['ilp_mis_learner_studentreview_qualstatusdesc']						= 'The field that holds Qualification Status data';

                     $string['ilp_mis_learner_studentreview_leccode']							= 'Lecturer Code data field';
                     $string['ilp_mis_learner_studentreview_leccodedesc']						= 'The field that holds Lecturer Code data';

                     $string['ilp_mis_learner_studentreview_lecturername']							= 'Lecturer Name data field';
                     $string['ilp_mis_learner_studentreview_lecturernamedesc']						= 'The field that holds Lecturer Name data';

                     $string['ilp_mis_learner_studentreview_qualleccnt']							= 'Qualification Lecturer data field';
                     $string['ilp_mis_learner_studentreview_qualleccntdesc']						= 'The field that holds Qualification Lecturer data';

                     $string['ilp_mis_learner_studentreview_attendperc']							= 'Attent Percentage data field';
                     $string['ilp_mis_learner_studentreview_attendpercdesc']						= 'The field that holds Attent Percentage data';

                     $string['ilp_mis_learner_studentreview_lateperc']							= 'Lateness Percentage data field';
                     $string['ilp_mis_learner_studentreview_latepercdesc']						= 'The field that holds Lateness Percentage data';

                     $string['ilp_mis_learner_studentreview_notifiedperc']							= 'Notified Percentage data field';
                     $string['ilp_mis_learner_studentreview_notifiedpercdesc']						= 'The field that holds Notified Percentage data';

                     $string['ilp_mis_learner_studentreview_currentgrade']							= 'Current Grade data field';
                     $string['ilp_mis_learner_studentreview_currentgradedesc']						= 'The field that holds Current Grade data';

                     $string['ilp_mis_learner_studentreview_predictedgrade']							= 'Predicted Grade data field';
                     $string['ilp_mis_learner_studentreview_predictedgradedesc']						= 'The field that holds Predicted Grade data';

                     $string['ilp_mis_learner_studentreview_academicyear']							= 'Academic Year data field';
                     $string['ilp_mis_learner_studentreview_academicyeardesc']						= 'The field that holds Academic Year data';

                     $string['ilp_mis_learner_studentreview_reviewtype']							= 'Review Type data field';
                     $string['ilp_mis_learner_studentreview_reviewtypedesc']						= 'The field that holds Review Type data';

                     $string['ilp_mis_learner_studentreview_qualtype']							= 'Qualification Type data field';
                     $string['ilp_mis_learner_studentreview_qualtypedesc']						= 'The field that holds Qualification Type data';

                     $string['ilp_mis_learner_studentreview_term']							= 'Term data field';
                     $string['ilp_mis_learner_studentreview_termdesc']						= 'The field that holds Term data';

                     $string['ilp_mis_learner_studentreview_effort']							= 'Effort data field';
                     $string['ilp_mis_learner_studentreview_effortdesc']						= 'The field that holds Effort data';

                     $string['ilp_mis_learner_studentreview_cfcongrats']							= 'CF Congrats data field';
                     $string['ilp_mis_learner_studentreview_cfcongratsdesc']						= 'The field that holds CF Congrats data';

                     $string['ilp_mis_learner_studentreview_comments']							= 'Comments data field';
                     $string['ilp_mis_learner_studentreview_commentsdesc']						= 'The field that holds Comments data';

                     $string['ilp_mis_learner_studentreview_commentflag']							= 'Comment flag data field';
                     $string['ilp_mis_learner_studentreview_commentflagdesc']						= 'The field that holds Comment flag data';

                     $string['ilp_mis_learner_studentreview_firsteditdate']							= 'First Edit Date data field';
                     $string['ilp_mis_learner_studentreview_firsteditdatedesc']						= 'The field that holds First Edit Date data';

                     $string['ilp_mis_learner_studentreview_uniquerowid']							= 'Unique Row Id data field';
                     $string['ilp_mis_learner_studentreview_uniquerowiddesc']						= 'The field that holds Unique Row Id data';

                     $string['ilp_mis_learner_studentreview_']							= ' data field';
                     $string['ilp_mis_learner_studentreview_desc']						= 'The field that holds  data';
                    $string['ilp_mis_learner_studentreview_disp_tabname']							= 'Student Review';
                    $string['ilp_mis_learner_studentreview_disp_surname']							= 'Surname';
                    $string['ilp_mis_learner_studentreview_disp_forename']							= 'Firstname';
                    $string['ilp_mis_learner_studentreview_disp_qualcode']							= 'Qualification Code';
                    $string['ilp_mis_learner_studentreview_disp_qualyear']							= 'Qualification Year';
                    $string['ilp_mis_learner_studentreview_disp_qualoccl']							= 'Qualification Occ';
                    $string['ilp_mis_learner_studentreview_disp_qualname']							= 'Qualification Name';
                    $string['ilp_mis_learner_studentreview_disp_qualstatus']							= 'Qualification Status';
                    $string['ilp_mis_learner_studentreview_disp_leccode']							= 'Lecturer Code';
                    $string['ilp_mis_learner_studentreview_disp_lecturername']							= 'Lecturer Name';
                    $string['ilp_mis_learner_studentreview_disp_qualleccnt']							= 'Qualification Lecturer';
                    $string['ilp_mis_learner_studentreview_disp_attendperc']							= 'Attent Percentage';
                    $string['ilp_mis_learner_studentreview_disp_lateperc']							= 'Lateness Percentage';
                    $string['ilp_mis_learner_studentreview_disp_notifiedperc']							= 'Notified Percentage';
                    $string['ilp_mis_learner_studentreview_disp_currentgrade']							= 'Current Grade';
                    $string['ilp_mis_learner_studentreview_disp_predictedgrade']							= 'Predicted Grade';
                    $string['ilp_mis_learner_studentreview_disp_academicyear']							= 'Academic Year';
                    $string['ilp_mis_learner_studentreview_disp_reviewtype']							= 'Review Type';
                    $string['ilp_mis_learner_studentreview_disp_qualtype']							= 'Qualification Type';
                    $string['ilp_mis_learner_studentreview_disp_term']							= 'Term';
                    $string['ilp_mis_learner_studentreview_disp_effort']							= 'Effort';
                    $string['ilp_mis_learner_studentreview_disp_cfcongrats']							= 'CF Congrats';
                    $string['ilp_mis_learner_studentreview_disp_comments']							= 'Comments';
                    $string['ilp_mis_learner_studentreview_disp_commentflag']							= 'Comment flag';
                    $string['ilp_mis_learner_studentreview_disp_firsteditdate']							= 'First Edit Date';
                    $string['ilp_mis_learner_studentreview_disp_uniquerowid']							= 'Unique Row Id';
                    $string['ilp_mis_learner_studentreview_disp_']							= '';
                    $string['ilp_mis_learner_studentreview_tab_name']							= 'Student Review';
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
                return get_string('ilp_mis_learner_studentreview_tab_name','block_ilp');
            }
    }
?>