<?php

    $configpath =   "../../../../../config.php";

    require_once($configpath);


    global  $CFG, $PARSER;

    $pchartpath =   "$CFG->dirroot/blocks/ilp/plugins/graph/externalclasses/pChart2.1.3";

    require_once($pchartpath."/class/pData.class.php");
    require_once($pchartpath."/class/pDraw.class.php");
    require_once($pchartpath."/class/pRadar.class.php");
    require_once($pchartpath."/class/pImage.class.php");

    // include the ilp db
    require_once($CFG->dirroot.'/blocks/ilp/classes/database/ilp_db.php');

    //the id of the report  that the field will be in
    $report_id = required_param('report_id', PARAM_INT);

    //the user id of the user
    $user_id = required_param('user_id', PARAM_INT);

    //the id of the reportfield used when editing
    $reportgraph_id = required_param('reportgraph_id' ,PARAM_INT);

    // instantiate the ilp db
    $dbc = new ilp_db();


    //get all entries for this user
    $userentries    =   $dbc->get_user_report_entries($report_id,$user_id);

        if (!empty($userentries)) {

            //get the report graph record for this reportgraph
            $reportgraph    =   $dbc->get_report_graph_data($reportgraph_id);

            //get the graph plugin for the graph
            $graphplugin    =   $dbc->get_graph_plugin_by_id($reportgraph->plugin_id);

            //get all data on report fields that will be used in this report
            $reportgraphfields      =   $dbc->get_graph_by_report($graphplugin->tablename,$reportgraph->id);

            $rgfarray   =   array();
            foreach ($reportgraphfields as $rgf)  {
                $rgfarray[]     =   array('fieldlabel'=>$rgf->fieldlabel,'reportfield_id'=>$rgf->reportfield_id);

            }

            $data       =   array();
            $labels     =   array();
            $counter    =   1;

            //loop through all entries
            foreach($userentries as $ue)   {

                $entry_data =   new object();

                for($i=0;$i<count($rgfarray);$i++)  {
                    $rf =   $dbc->get_reportfield_by_id($rgfarray[$i]['reportfield_id']);
                    if (!empty($rf))   {

                        //get the plugin record that for the plugin
                        $pluginrecord	=	$dbc->get_plugin_by_id($rf->plugin_id);

                        //take the name field from the plugin as it will be used to call the instantiate the plugin class
                        $classname = $pluginrecord->name;

                        // include the class for the plugin
                        include_once("{$CFG->dirroot}/blocks/ilp/plugins/form_elements/{$classname}.php");

                        if(!class_exists($classname)) {
                            print_error('noclassforplugin', 'block_ilp', '', $pluginrecord->name);
                        }

                        //instantiate the plugin class
                        $pluginclass	=	new $classname();

                        $pluginclass->load($rf->id);

                        //call the plugin class entry data method
                        $pluginclass->view_data($rf->id,$ue->id,$entry_data,true);
                    }
                }

                $propertycheck  =   true;

                //check that all fields are present in the entry_data object. We will only add the
                //data to the graph if all data fields are presnet
                foreach($rgfarray as $rgf)   {
                    if (!property_exists($entry_data,"{$rgf['reportfield_id']}_field")) $propertycheck  =   false;
                }

                //if the any property has not been found in the object move onto the next entry record
                if (empty($propertycheck)) continue;

                if (!empty($entry_data))  {
                    foreach($entry_data as $idx => $val)   {
                        //if the value is an array we will only make use of the first element
                        $data[$counter][]   = (is_array($val)) ?   current($val) :  $val;
                    }
                    $labels[$counter]    =   $ue->timecreated;
                    $counter++;
                }
            }

            for($i=0;$i<count($rgfarray);$i++)  {
                $chartpoints[]      =   $rgfarray[$i]['fieldlabel'];
            }

            $count = count($userentries);
            /* Create and populate the pData object */

            $MyData = new pData();
            $i = 1;

            while ($i <= $count){
                $MyData->addPoints($data[$i],$labels[$i]);
                $MyData->setSerieDescription($labels[$i],userdate($labels[$i], get_string('strftimedate')));
                $i++;
            }


            /* Define the absissa serie */

            $MyData->addPoints($chartpoints,"Labels");
            $MyData->setAbscissa("Labels");

            $myPicture = new pImage(550,400,$MyData,TRUE);
            /* Will replace the whole color scheme by the "light" palette */
            //$MyData->loadPalette("$CFG->dirroot/blocks/ilp/pchart/palettes/ulcc.color", TRUE);

            $myPicture->drawGradientArea(0,0,550,400,DIRECTION_VERTICAL,array("StartR"=>204, "StartG"=>255, "StartB"=>255, "EndR"=>255, "EndG"=>255, "EndB"=>255, "Alpha"=>50));

            $myPicture->drawRectangle(0,0,549,399,array("R"=>0,"G"=>0,"B"=>0));

            $myPicture->setShadow(TRUE,array("X"=>1,"Y"=>1,"R"=>50,"G"=>50,"B"=>50,"Alpha"=>20));

            $myPicture->setFontProperties(array("FontName"=>$pchartpath."/fonts/arial.ttf","FontSize"=>12));
            $myPicture->drawText(95,20,$reportgraph->name,array("Align"=>TEXT_ALIGN_MIDDLEMIDDLE, "R"=>51, "G"=>51, "B"=>51));

            /* Create the pRadar object */

            $myRadar = new pRadar();

            $myPicture->setGraphArea(20,20,520,369);
            $myPicture->setFontProperties(array("FontName"=>$pchartpath."/fonts/arial.ttf","FontSize"=>7));

            $Options = array("Layout"=>RADAR_LAYOUT_CIRCLE,"LabelPos"=>RADAR_LABELS_HORIZONTAL,"BackgroundGradient"=>array("StartR"=>255,"StartG"=>255,"StartB"=>255,"StartAlpha"=>50,"EndR"=>32,"EndG"=>109,"EndB"=>174,"EndAlpha"=>30));
            $myRadar->drawRadar($myPicture,$MyData,$Options);

            /* Render the picture (choose the best way) */

            return $myPicture->Stroke();
        }
