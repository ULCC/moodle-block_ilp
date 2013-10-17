<?php

/**
* A pie chart graph plugin this
*
* @abstract
*
* @copyright &copy; 2011 University of London Computer Centre
* @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
* @license http://www.gnu.org/copyleft/gpl.html GNU Public License
* @package ILP
* @version 2.0
*/



//require the ilp_graph_plugin.class.php file
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_graph_plugin.class.php');

class ilp_graph_plugin_radar extends ilp_graph_plugin {

    public $tablename;

    /**
     * Constructor
     */
    function __construct() {

        $this->tablename = "block_ilp_plu_graph_radar";

        parent::__construct();
    }

    /********************************
     * Creates the table in the moodle database to store details of the fields that will be referenced for
     * chart data
     */

    function install($plugin_id=null)  {
        // create the table to store radar graph data
        $table = new $this->xmldb_table( $this->tablename );
        $set_attributes = method_exists($this->xmldb_key, 'set_attributes') ? 'set_attributes' : 'setAttributes';

        $table_id = new $this->xmldb_field('id');
        $table_id->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addField($table_id);

        $table_report = new $this->xmldb_field('reportgraph_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);

        $table_report = new $this->xmldb_field('reportfield_id');
        $table_report->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_report);

        $table_title = new $this->xmldb_field('fieldlabel');
        $table_title->$set_attributes(XMLDB_TYPE_CHAR, 255, null, null);
        $table->addField($table_title);

        $table_timemodified = new $this->xmldb_field('timemodified');
        $table_timemodified->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timemodified);

        $table_timecreated = new $this->xmldb_field('timecreated');
        $table_timecreated->$set_attributes(XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL);
        $table->addField($table_timecreated);

        $table_key = new $this->xmldb_key('primary');
        $table_key->$set_attributes(XMLDB_KEY_PRIMARY, array('id'));
        $table->addKey($table_key);

        $table_key = new $this->xmldb_key('radar_unique_reportgraph_graph');
        $table_key->$set_attributes(XMLDB_KEY_UNIQUE, array('reportgraph_id','reportfield_id'));
        $table->addKey($table_key);

        if(!$this->dbman->table_exists($table)) {
            $this->dbman->create_table($table);
        }


    }

    /**
     *  overriden function displays the graph
    */
    function display($user_id,$report_id,$reportgraph_id,$size='large',$return=false)  {
        global  $CFG;

        $reportgraph    =   $this->dbc->get_report_graph_data($reportgraph_id);

        switch  ($size)  {

            case    'small':

                $height =   '100px';
                $width  =   '200px';

                break;

            case    'medium':

                $height =   '200px';
                $width  =   '300px';

                break;

            default:

                $height =   '400px';
                $width  =   '500px';
        }

        $start  = null;
        $end    = null;

        switch  ($reportgraph->datacollected)  {

            case ILP_GRAPH_ONEMONTHDATA: //past months data
                $start  =   strtotime("-4 weeks");
                $end    =   time();
                break;

            case ILP_GRAPH_THREEMONTHDATA:
                $start  =   strtotime("-12 weeks");
                $end    =   time();
                break;

            case ILP_GRAPH_SIXMONTHDATA:
                $start  =   strtotime("-24 weeks");
                $end    =   time();
                break;

            case ILP_GRAPH_YEARDATA:
                $start  =   strtotime("-1 year");
                $end    =   time();
                break;

            default :
                $start  =   null;
                $end    =   null;
        };

        //get all entries for this user
        $userentries    =   $this->dbc->get_user_report_entries_between_time($report_id,$user_id,$start,$end);

        if (!empty($userentries))   {
            if (empty($return))   {
                echo "<div id='graph_container'>";
                echo html_entity_decode($reportgraph->description, ENT_QUOTES, 'UTF-8');
                echo "<img src='$CFG->wwwroot/blocks/ilp/plugins/graph/radar/ilp_graph_plugin_radar_display.php?report_id=$report_id&user_id=$user_id&reportgraph_id=$reportgraph_id' height='$height' width='$width' />";
                echo "</div>";
            }   else    {
                return "<img src='$CFG->wwwroot/blocks/ilp/plugins/graph/radar/ilp_graph_plugin_radar_display.php?report_id=$report_id&user_id=$user_id&reportgraph_id=$reportgraph_id'  height='$height' width='$width'  />";
            }
        } else {
            if (empty($return)) {
                echo get_string('nodatafoundgraph','block_ilp');
            } else {
                return get_string('nodatafoundgraph','block_ilp');
            }
        }
    }

    /**
     * function used to return the language strings for the plugin
     */
    static function language_strings(&$string) {
        $string['ilp_graph_plugin_radar_type'] 		        = 'Radar Graph';
        $string['ilp_graph_plugin_radar_reportfield']       = 'Report Field {no}';
        $string['ilp_graph_plugin_radar_label']             = 'Label {no}';
        $string['ilp_graph_plugin_radar_description']              = 'Radar graph';
        return $string;
    }


    function audit_type()   {
        return get_string('ilp_graph_plugin_radar_type','block_ilp');
    }

    /**
     * Delete a form element
     */
    public function delete_graph($reportgraph_id, $extraparams=array()) {
        $reportgraph		=	$this->dbc->get_report_graph_data($reportgraph_id);
        $extraparams = array(
            'audit_type' => $this->audit_type(),
            'label' => $reportgraph->name,
            'description' => $reportgraph->description,
            'id' => $reportgraph_id
        );


       $this->dbc->delete_record("block_ilp_plu_graph_radar",array('reportgraph_id'=>$reportgraph_id),$extraparams);

        return parent::delete_graph( $reportgraph_id, $extraparams );
    }


    public function specific_edit(&$reportgraph,$multipleitems = false,$non_attrib=NULL)  {
        parent::specific_edit($reportgraph,true);
    }

    /**
     * returns a icon that can be used to represents a graph
     *
     * @return string
     */
    function icon() {
        global $CFG;
        return  "<img src='$CFG->wwwroot/blocks/ilp/plugins/graph/radar/icon.jpg' class='graphicon inlinegraphicon'  />";
    }
}