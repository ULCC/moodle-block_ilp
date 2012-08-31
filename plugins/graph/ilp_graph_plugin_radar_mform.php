<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_graph_plugin_mform.class.php');

class ilp_graph_plugin_radar_mform extends ilp_graph_plugin_mform {

    /**
     * The constructor for this graph plugin. This function has been created in order to find out the number of
     * reportfields used in this graph and pass that information to the sepcific definition function.
     *
     * @param $report_id    int the id of the report this graph will be attached to.
     * @param $plugin_id    int the id of this graph plugin
     * @param $creator_id   int the id of the
     * @param null $reportgraph_id
     * @param null $reportgraph
     *
     *
     */

    function __construct($report_id,$plugin_id,$creator_id,$reportgraph_id=null,$reportgraph=null)  {
        $this->rfcount  =   (!empty($reportgraph))  ? count($reportgraph->reportfield_id)    : 1  ;

        parent::__construct($report_id,$plugin_id,$creator_id,$reportgraph_id=null,$reportgraph=null);
    }


    protected function specific_definition($mform) {
        //set the maximum length of the field default to 255

        $reportfields   =   $this->dbc->get_report_fields_by_position($this->report_id);

         if (!empty($reportfields))   {

            $optionlist     =   array();

            foreach ($reportfields as $rf)  {
                //check if the report field element can be added to this type of graph
                if ($this->check_elements($rf->id))  {
                    $optionlist[$rf->id]     =       $rf->label;
                }
            }

             $repeatelements     =        array(
                $mform->createElement(
                    'text',
                    'fieldlabel',
                    get_string('ilp_graph_plugin_radar_label', 'block_ilp'),
                    array('class' => 'form_input')
                ),
                $mform->createElement(
                    'select',
                    'reportfield_id',
                    get_string( 'ilp_graph_plugin_radar_reportfield' , 'block_ilp' ),
                    $optionlist
                )
            );

            $optypes['type']    =   PARAM_ALPHA;
            $options['label']   =   $optypes;

            $optypes['type']    =   PARAM_INT;
            $options['reportfield_id']   =   $optypes;

            //count the number of fields we will not allow the user to have more
            //than this number of points on the radar
            $maxfields    =   count($optionlist);

            $this->repeat_elements($repeatelements, $this->rfcount, $options, 'numfields', 'addfields',1,null,false,$maxfields);


        }
    }

    protected function specific_validation($data) {
        $data = (object) $data;

        $rftemp      =   $data->reportfield_id;

        for($i=0;$i < count($data->reportfield_id);$i++) {
            $temp_id     =   array_pop($rftemp);
            if (is_array($rftemp) && in_array($temp_id,$rftemp))  {
                $fieldnum    =   $i  +1;
                $this->errors["reportfield_id[{$i}]"]  =   get_string('reportfield','block_ilp')." {$fieldnum} ".get_string('duplicated','block_ilp');
            }
        }

        return $this->errors;
    }

    protected function specific_process_data($data) {

        $plgrec = (!empty($data->reportgraph_id)) ? $this->dbc->get_graph_by_report("block_ilp_plu_graph_radar",$data->reportgraph_id) : false;

        if (!empty($plgrec)) {
            //delete all records already attached to this graph as we will be recreating them all
            $this->dbc->delete_record("block_ilp_plu_graph_radar",array('reportgraph_id'=>$data->reportgraph_id));
        }

        $fieldcount  =   count($data->fieldlabel);

        for($i=0; $i < $fieldcount; $i++)   {
            $itemrecord = new stdClass();
            $itemrecord->fieldlabel  =   $data->fieldlabel[$i];
            $itemrecord->reportfield_id  =   $data->reportfield_id[$i];
            $itemrecord->reportgraph_id  =   $data->reportgraph_id;

            $this->dbc->create_plugin_record("block_ilp_plu_graph_radar",$itemrecord);
        }
    }


    function definition_after_data() {

    }

    /**
     *  The elements define the elements that this plugin can will accept
     *  for its data
     */
    function form_elements()  {
        $this->allowed_form_elements[]  =       'ilp_element_plugin_rdo';
        $this->allowed_form_elements[]  =       'ilp_element_plugin_dd';
    }



}
