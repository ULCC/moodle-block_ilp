<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_element_plugin.class.php');
require_once($CFG->dirroot . '/blocks/ilp/classes/plugins/ilp_mis_plugin.class.php');

class ilp_element_plugin_goal extends ilp_element_plugin {

     //Field names and whether they are required or not. Intended to be shared with goal_mform class
     public static $fieldnames=array('tablenamefield'=>true,'courseidfield'=>true,'studentidfield'=>true,
				     'goalfield1'=>true,'goalfield2'=>false,'goalfield3'=>false,'goalfield4'=>false);

     public $tablename;
     public $data_entry_tablename;

     private $db;

     /**
      * Constructor
      */
     function __construct() {

	  parent::__construct();

	  $this->tablename = "block_ilp_plu_goal";
	  $this->data_entry_tablename = "block_ilp_plu_goal_ent";

     }
	
     /**
      * TODO comment this
      * called when user form is submitted
      */
     public function load($reportfield_id) {
	  $reportfield = $this->dbc->get_report_field_data($reportfield_id);
	  if (!empty($reportfield)) {
	       //set the reportfield_id var
	       $this->reportfield_id = $reportfield_id;

	       //get the record of the plugin used for the field
	       $plugin = $this->dbc->get_form_element_plugin($reportfield->plugin_id);

	       $this->plugin_id = $reportfield->plugin_id;

	       //get the form element record for the reportfield
	       $pluginrecord = $this->dbc->get_form_element_by_reportfield($this->tablename, $reportfield->id);
	       if (!empty($pluginrecord)) {
		    $this->label = $reportfield->label;
		    $this->description = $reportfield->description;
		    $this->req = $reportfield->req;


		    $this->position = $reportfield->position;
		    $this->tabletype = $pluginrecord->tabletype;
		    $this->parent_id = $pluginrecord->id;

		    return true;
	       }
	  }
	  return false;
     }

     /**
      * create tables for this plugin
      */
     public function install() {

	  // create the table to store report fields
	  $table = new $this->xmldb_table( $this->tablename );
	  $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

	  $table_id = new $this->xmldb_field('id');
	  $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
	  $table->addField($table_id);
        
	  $table_report = new $this->xmldb_field('reportfield_id');
	  $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	  $table->addField($table_report);

	  foreach(self::$fieldnames as $fieldname=>$required)
	  {
	       $newfield = new $this->xmldb_field($fieldname);
	       $newfield->$set_attributes(XMLDB_TYPE_CHAR, 100, null, null);
	       $table->addField($newfield);
	  }

	  $table_tabletype = new $this->xmldb_field('tabletype');
	  $table_tabletype->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	  $table->addField($table_tabletype);

	  $table_timemodified = new $this->xmldb_field('timemodified');
	  $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	  $table->addField($table_timemodified);

	  $table_timecreated = new $this->xmldb_field('timecreated');
	  $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	  $table->addField($table_timecreated);

	  $table_key = new $this->xmldb_key('primary');
	  $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
	  $table->addKey($table_key);

	  $table_key = new $this->xmldb_key('goalplugin_unique_reportfield');
	  $table_key->$set_attributes(XMLDB_KEY_FOREIGN_UNIQUE, array('reportfield_id'),'block_ilp_report_field','id');
	  $table->addKey($table_key);

	  if(!$this->dbman->table_exists($table)) {
	       $this->dbman->create_table($table);
	  }

	  // create the new table to store responses to fields
	  $table = new $this->xmldb_table( $this->data_entry_tablename );
	  $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

	  $table_id = new $this->xmldb_field('id');
	  $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
	  $table->addField($table_id);
        
	  $table_goal = new $this->xmldb_field('value');
	  $table_goal->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
	  $table->addField($table_goal);

	  $table_courseidnumber = new $this->xmldb_field('courseidnumber');
	  $table_courseidnumber->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
	  $table->addField($table_courseidnumber);

	  $table_entryid= new $this->xmldb_field('entry_id');
	  $table_entryid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	  $table->addField($table_entryid);

	  $table_parentid= new $this->xmldb_field('parent_id');
	  $table_parentid->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	  $table->addField($table_parentid);


	  $table_timemodified = new $this->xmldb_field('timemodified');
	  $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	  $table->addField($table_timemodified);

	  $table_timecreated = new $this->xmldb_field('timecreated');
	  $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
	  $table->addField($table_timecreated);

	  $table_key = new $this->xmldb_key('primary');
	  $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
	  $table->addKey($table_key);
        
	  $table_key = new $this->xmldb_key($this->tablename.'_foreign_key');
	  $table_key->$set_attributes(XMLDB_KEY_FOREIGN, array('parent_id'), $this->tablename ,'id');
	  $table->addKey($table_key);

	  $table_key = new $this->xmldb_key('unique');
	  $table_key->$set_attributes(XMLDB_KEY_UNIQUE, array('entry_id', 'parent_id'),$this->tablename, 'goalentry');
	  $table->addKey($table_key);

	  if(!$this->dbman->table_exists($table)) {
	       $this->dbman->create_table($table);
	  }
     }

     /**
      *
      */
     public function uninstall() {
	  $table = new $this->xmldb_table( $this->tablename );
	  drop_table($table);
        
	  $table = new $this->xmldb_table( $this->data_entry_tablename );
	  drop_table($table);
     }
	
     /**
      *
      */
     public function audit_type() {
	  return get_string('ilp_element_plugin_goal_type','block_ilp');
     }

     /**
      * function used to return the language strings for the plugin
      */
     static function language_strings(&$string) {
	  $string['ilp_element_plugin_goal'] 		= 'Goal';
	  $string['ilp_element_plugin_goal_type'] = 'Goal field';
	  $string['ilp_element_plugin_goal_description'] = 'A linked pair of selection fields for setting goals';
	  $string['ilp_element_plugin_goal_tablenamefield'] = 'Table name';
	  $string['ilp_element_plugin_goal_table_type']  = 'Table type';
	  $string['ilp_element_plugin_goal_courseidfield'] = 'Course title field';
	  $string['ilp_element_plugin_goal_studentidfield']  = 'Student id field';
	  $string['ilp_element_plugin_goal_goalfield1']  = 'Course goal field 1';
	  $string['ilp_element_plugin_goal_goalfield2']  = 'Course goal field 2';
	  $string['ilp_element_plugin_goal_goalfield3']  = 'Course goal field 3';
	  $string['ilp_element_plugin_goal_goalfield4']  = 'Course goal field 4';
	  $string['ilp_element_plugin_goal_nocourses']  = 'N/A';
	  $string['ilp_element_plugin_goal_nogoals']  = 'N/A';
	  $string['ilp_element_plugin_goal_courselabel']  = 'Course Title';
	  $string['ilp_element_plugin_goal_goallabel']  = 'Course Goal';
	  $string['ilp_element_plugin_goal_outputformat']  = '<div style="margin-left:2em"><P><strong>{$a->courselabel}'.
	       ': </strong>{$a->courseidnumber}'.
	       '<P><strong>{$a->goallabel}'.
	       ': </strong>{$a->value}</div>';

	  return $string;
     }

     /**
      * Delete a form element
      */
     public function delete_form_element($reportfield_id, $tablename=null, $extraparams=null) {
	  $reportfield		=	$this->dbc->get_report_field_data($reportfield_id);
	  $extraparams = array(
	       'audit_type' => $this->audit_type(),
	       'label' => $reportfield->label,
	       'description' => $reportfield->description,
	       'id' => $reportfield_id
	       );
	  return parent::delete_form_element(  $reportfield_id, $this->tablename, $extraparams );
     }
    

     /**
      * this function returns the mform elements that will be added to a report form
      *
      */
     public function entry_form( &$mform ) {
	  global $PAGE;
	  $fieldname	=	"{$this->reportfield_id}_field";

	  $entry_id=optional_param('entry_id',0,PARAM_INT);

	  if (!empty($this->description)) {
	       $mform->addElement('static', "{$fieldname}_desc", $this->label, strip_tags(html_entity_decode($this->description,
                                                                                                         ENT_QUOTES,
                                                                                                         'UTF-8'),ILP_STRIP_TAGS_DESCRIPTION));
	       $this->label = '';
	  }

	  list($courses,$goals)=$this->get_courses_and_goals($mform->_elements[$mform->_elementIndex['user_id']]->_attributes['value']);

//prepare json strings for when there is javascript,
	  $allgoals=array();
	  $coursegoals=array();
	  $courseidx=0;
	  $goalidx=0;
	  foreach($goals as $group)
	  {
	       $coursegoals[$courseidx]=json_encode($group,JSON_HEX_QUOT);
	       $goalidx=0;
	       foreach($group as $g)
	       {
		    $allgoals["{$courseidx}_{$goalidx}"]=$g;
		    $goalidx++;
	       }
	       $courseidx++;
	  }

	  if($entry_id)
	  {
	       $currentdata=$this->dbc->get_pluginentry($this->tablename,$entry_id,$this->reportfield_id);
	       $currentindex=array_search($currentdata->value,$allgoals);
	  }
	  else
	  {
	       $currentdata=new stdClass;
	       $currentindex=0;
	  }


	  $currentcourse=array_search($currentdata->courseidnumber,$courses);
	  $currentgoal=array_search($currentdata->value,$allgoals);  //No javascript

	  ($realgoal=array_search($currentdata->value,$goals[$currentcourse]) or $realgoal=0);  // Javascript

	  ob_start();
	  include_once('ilp_element_plugin_goal.js');
	  $jscode	=	ob_get_contents();
	  ob_end_clean();
	  $mform->addElement('html',$jscode);

	  //Create element
	  $mform->addElement('html',"<div id='block_ilp_element_plugin_goal_{$fieldname}_div' >");
	  $mform->addElement('select', $fieldname.'_sel1', 
			     get_string('ilp_element_plugin_goal_courselabel','block_ilp'),
			     $courses,array('class' => 'form_input',
					    'onchange'=>"block_ilp_element_plugin_goal_$fieldname.updatesubselect('id_{$fieldname}_sel2')"));

	  $mform->addElement('html','</div>');

	  $mform->addElement('select', $fieldname.'_sel2', get_string('ilp_element_plugin_goal_goallabel','block_ilp'),
			     $allgoals,array('class' => 'form_input'));


	  $PAGE->requires->js_init_call("block_ilp_element_plugin_goal_$fieldname.initializegoals",array("id_{$fieldname}_sel2"));
	  $PAGE->requires->js_init_code("document.getElementById('block_ilp_element_plugin_goal_{$fieldname}_div').style.display='block'");

	  $mform->setDefault($fieldname.'_sel1',$currentcourse);
	  $mform->setDefault($fieldname.'_sel2',$currentgoal);

	  if (!empty($this->req))
           $fieldname = "id_".$fieldname;
	       $mform->addRule($fieldname, null, 'required', null, 'client');
     }


     /*
      * sets up an mis connection and sets the instance variable db to store it
      */
     protected function get_mis_connection()
     {
	  if(isset($this->db))
	       return;

	  ($this->db = new ilp_mis_connection() or print_error('nomis'));

     }


     /*
      * Returns an array with two items: the courses that the user has possible goals for
      * in the mis table, and the possible goals, which can be caught by a list"($courses,$goals)="
      * style construction.
      * @paramn int $userid
      * @return array
      */
     protected function get_courses_and_goals($userid)
     {
          global $DB;
          $userid=$DB->get_field('user','idnumber',array('id'=>$userid));

	  $mydata=$this->dbc->get_form_element_data($this->tablename,$this->parent_id);

	  $courses=array(get_string('ilp_element_plugin_goal_nocourses','block_ilp'));
	  $goals=array(array(get_string('ilp_element_plugin_goal_nogoals','block_ilp')));

          if(!(isset($userid) and
               $misinfo = $this->dbquery($mydata->tablenamefield,array($mydata->studentidfield=>array('='=>$userid)))))
	  {
	       return array($courses,$goals);
	  }
	  $tempc=1;
	  foreach($misinfo as $item)
	  {
	       $courses[$tempc]=$item[$mydata->courseidfield];
	       $goalfields=array();
	       foreach(range(1,4) as $idx)
	       {
		    $name="goalfield$idx";
		    if($data=$item[$mydata->$name]) //Leave empty goal fields null
		    {
			 $goalfields[]=$data;
		    }
	       }
	       $goals[$tempc++]=$goalfields;
	  }

	  //This array can't have named keys (eg., "courses"=>$courses) as this breaks PHP's list function. Shame.
	  return array($courses,$goals);
     }

     /*
      * Handle the user's selection
      */
     public function entry_process_data($reportfield_id,$entry_id,$data)
     {
	  //Make sure we have the database connection we need
	  $this->get_mis_connection();  //$this->db is mis db

	  list($courses,$goals)=$this->get_courses_and_goals($data->user_id);

	  $datafieldcourse=$reportfield_id.'_field_sel1';
	  $datafieldgoal=$reportfield_id.'_field_sel2';

	  if(strpos($data->$datafieldgoal,'_')===false) //Javascript on
	  {
	       $coursefield=$data->$datafieldcourse;
	       $goalfield=$data->$datafieldgoal;
	  }
	  else //JS was off
	  {
	       list($coursefield,$goalfield)=explode('_',$data->$datafieldgoal);
	  }

	  $course=$courses[$coursefield];
	  $goal=$goals[$coursefield][$goalfield];

	  $fieldname =	$reportfield_id."_field";

	  //get the plugin table record that has the reportfield_id
	  $pluginrecord	=	$this->dbc->get_plugin_record($this->tablename,$reportfield_id);
	  if (empty($pluginrecord)) {
	       print_error('pluginrecordnotfound');
	  }

	  //get the _entry table record that has the pluginrecord id
	  $pluginentry 	=	$this->dbc->get_pluginentry($this->tablename,$entry_id,$reportfield_id);

	  //if no record has been created create the entry record
	  if (empty($pluginentry)) {
	       $pluginentry	=	new stdClass();
	       $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
	       $pluginentry->entry_id = $entry_id;
	       $pluginentry->value	=	$goal;
	       $pluginentry->courseidnumber =  $course;
	       $pluginentry->parent_id	=	$pluginrecord->id;
	       $result	= $this->dbc->create_plugin_entry($this->data_entry_tablename,$pluginentry);
	  } else {
	       //update the current record
	       $pluginentry->audit_type = $this->audit_type(); //send the audit type through for logging purposes
	       $pluginentry->value	=	$goal;
	       $pluginentry->courseidnumber =  $course;
	       $result	= $this->dbc->update_plugin_entry($this->data_entry_tablename,$pluginentry);
	  }

	  return (!empty($result));

     }

     /**
      * handle user input
      **/
     public	function entry_specific_process_data($reportfield_id,$entry_id,$data) {
	  /*
	   * parent method is fine for simple form element types
	   * dd types will need something more elaborate to handle the intermediate
	   * items table and foreign key
	   */
	  return $this->entry_process_data($reportfield_id,$entry_id,$data); 	
     }

     /*
      * user's input.
      */
     public function entry_data($reportfield_id, $entry_id, &$entryobj) {
	  $entry = $this->dbc->get_pluginentry($this->tablename, $entry_id, $reportfield_id);

	  $fieldname	=	$reportfield_id."_field";

	  if(empty($entry))
	  {
	       //fake default data if not set
	       $entry=new stdClass;
	       $entry->courseidnumber=0;
	       $entry->value='';
	  }

	  $entryobj->$fieldname = array($entry->courseidnumber,$entry->value);

     }

     /*
      * user's input.
      */
     public function view_data($reportfield_id, $entry_id, &$entryobj, $returnvalue = false) {
	  $entry = $this->dbc->get_pluginentry($this->tablename, $entry_id, $reportfield_id);

	  $fieldname	=	$reportfield_id."_field";

	  if(empty($entry))
	  {
	       //fake default data if not set
	       $entry=new stdClass;
	       $entry->courseidnumber=get_string('ilp_element_plugin_goal_nocourses','block_ilp');
	       $entry->value=get_string('ilp_element_plugin_goal_nogoals','block_ilp');
	  }

	  $entryobj->$fieldname=get_string('ilp_element_plugin_goal_outputformat','block_ilp',
					   (object)array('courselabel'=>get_string('ilp_element_plugin_goal_courselabel','block_ilp'),
							 'goallabel'=>get_string('ilp_element_plugin_goal_goallabel','block_ilp'),
							 'courseidnumber'=>$entry->courseidnumber,
							 'value'=>$entry->value));
     }


     //Single instance per report
     public function can_add( $report_id ){
	  return !$this->dbc->element_type_exists( $report_id, $this->tablename );
     }

     /*
      * read data from the MIS db connection
      * @param string $table
      * @param array $whereparams
      * @param string $fields
      * @param array $additionalargs
      * @return array
      */
     protected function dbquery($table, $params = null, $fields = '*', $addionalargs = null,$prelimcalls = null)
     {
	  $this->get_mis_connection();

	  if (!empty($prelimcalls))	$this->db->prelimcalls[]	=	$prelimcalls;

	  return ($this->tabletype == ILP_MIS_STOREDPROCEDURE)
	       ? $this->db->return_stored_values($table, $params)
	       : $this->db->return_table_values($table, $params, $fields, $addionalargs);
     }
}
