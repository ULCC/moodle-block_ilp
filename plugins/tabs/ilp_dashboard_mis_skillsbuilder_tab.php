<?php

//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_tab.class.php');

class ilp_dashboard_mis_skillsbuilder_tab  extends ilp_dashboard_tab {

    public		$student_id;
    public		$course_id;
    public 		$filepath;
    public		$linkurl;
    public 		$selectedtab;


    function __construct($student_id=null,$course_id=NULL)	{
        global 	$CFG;

        //$this->linkurl				=	$CFG->wwwroot.$_SERVER["SCRIPT_NAME"]."?user_id=".$student_id."&course_id={$course_id}";

        $this->linkurl					=	$CFG->wwwroot."/blocks/ilp/actions/view_main.php?user_id=".$student_id."&course_id={$course_id}";

        $this->student_id	=	$student_id;
        $this->course_id	=	$course_id;
        $this->filepath		=	$CFG->dirroot."/blocks/ilp/plugins/tabs/entries/overview.php";


        //set the id of the tab that will be displayed first as default
        $this->default_tab_id	=	$this->plugin_id.'-1';

        //call the parent constructor
        parent::__construct();
    }

    /**
     * Return the text to be displayed on the tab
     */
    function display_name()	{
        return	get_string('ilp_dashboard_mis_skillsbuilder_tab_name','block_ilp');
    }

    /**
     * Override this to define the second tab row should be defined in this function
     */
    function define_second_row()	{
        global $CFG;


        //if the tab plugin has been installed we will use the id of the class in the block_ilp_dash_tab table
        //as part fo the identifier for sub tabs. ALL TABS SHOULD FOLLOW THIS CONVENTION
        if (!empty($this->plugin_id)) {

            $this->secondrow	=	array();


            //get all plugins that are of type misc and add them as a tab
            $plugins = $CFG->dirroot.'/blocks/ilp/plugins/mis';

            if ($this->dbc->get_mis_plugins() !== false) {


                $mis_plugins = ilp_records_to_menu($this->dbc->get_mis_plugins(), 'id', 'name');

                foreach ($mis_plugins as $plugin_file) {

                    if (file_exists($plugins.'/'.$plugin_file.".php")) {

                        require_once($plugins.'/'.$plugin_file.".php");

                        if ($plugin_file::plugin_type() == 'skillsbuilder') {
                            // instantiate the object
                            $class = basename($plugin_file, ".php");
                            $pluginobj = new $class();

                            $misplug	=	$this->dbc->get_mis_plugin_by_name($plugin_file);
                            $status =	get_config('block_ilp',$plugin_file.'_pluginstatus');
                            $status	=	(!empty($status)) ?  $status: 0;
                            if (!empty($misplug) & $status == ILP_ENABLED ) {
                                //NOTE names of tabs can not be get_string as this causes a nesting error
                                $this->secondrow[]	=	array('id'=>$misplug->id,'link'=>$this->linkurl,'name'=>$pluginobj->tab_name());
                            }
                        }

                    }

                }
            }





        }
    }


    /**
     * Returns the content to be displayed
     *
     * @param	string $selectedtab the tab that has been selected this variable
     * this variable should be used to determined what to display
     *
     * @return none
     */
    function display($selectedtab=null)	{
        global 	$CFG,$PARSER;


        $pluginoutput	=	"";

        //get the selecttab param if has been set
        $this->selectedtab = $PARSER->optional_param('selectedtab', NULL, PARAM_INT);

        //get the tabitem param if has been set
        $this->tabitem = $PARSER->optional_param('tabitem', NULL, PARAM_INT);

        //split the selected tab id on up 3 ':'
        $seltab	=	explode(':',$selectedtab);

        //if the seltab is empty then the highest level tab has been selected
        if (empty($seltab))	$seltab	=	array($selectedtab);

        $plugin_id	= (!empty($seltab[1])) ? $seltab[1] : $this->default_tab_id ;

        if ($this->dbc->get_user_by_id($this->student_id)) {

            $user	=	$this->dbc->get_user_by_id($this->student_id);

            //start buffering output
            ob_start();

            if ((!empty($seltab[1])) || !empty($this->secondrow))  {
                if (isset($seltab[1]) && !empty($seltab[1])) {
                    $misplugin	=	$this->dbc->get_mis_plugin_by_id($seltab[1]);
                } else {
                    $tabselection	=	$this->secondrow[0];
                    $misplugin	=	$this->dbc->get_mis_plugin_by_id($tabselection['id']);
                }

                require_once $CFG->dirroot.'/blocks/ilp/plugins/mis/'.$misplugin->name.'.php';

                $misplu	=	new $misplugin->name();

                $misplu->set_data($user->idnumber,$this->student_id);

                echo $misplu->display();
            }


            //pass the output instead to the output var
            $pluginoutput = ob_get_contents();

            ob_end_clean();

        } else {
            $pluginoutput	=	get_string('studentnotfound','block_ilp');
        }


        return $pluginoutput;
    }

    /**
     * Adds the string values from the tab to the language file
     *
     * @param	array &$string the language strings array passed by reference so we
     * just need to simply add the plugins entries on to it
     */
    static function language_strings(&$string) {
        $string['ilp_dashboard_mis_skillsbuilder_tab'] 					= 'Skills Builder PLUGINS';
        $string['ilp_dashboard_mis_skillsbuilder_tab_name'] 				= 'Skills Builder';

        return $string;
    }
}

?>
