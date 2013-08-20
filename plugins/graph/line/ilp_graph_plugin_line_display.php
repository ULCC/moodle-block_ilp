<?php

    $configpath =   "../../../../../config.php";

    require_once($configpath);

    global  $CFG, $PARSER;

    $pchartpath =   "$CFG->dirroot/blocks/ilp/plugins/graph/externalclasses/pChart2.1.3";

    require_once($pchartpath."/class/pData.class.php");
    require_once($pchartpath."/class/pDraw.class.php");
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
            $calculation    =   0;
            foreach ($reportgraphfields as $rgf)  {
                $rgfarray[]     =  $rgf->reportfield_id;
                //all of the calculation types for all fields in a graph should be the same so
                //it is safe to use one variable
                $calculation    =   $rgf->calculation;
            }

            $data       =   array();
            $labels     =   array();
            $counter    =   1;

            //loop through all entries
            foreach($userentries as $ue)   {

                $entry_data =   new object();

                for($i=0;$i<count($rgfarray);$i++)  {
                    $rf =   $dbc->get_reportfield_by_id($rgfarray[$i]);
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
                    if (!property_exists($entry_data,"{$rgf}_field")) $propertycheck  =   false;
                }

                //if the any property has not been found in the object move onto the next entry record
                if (empty($propertycheck)) continue;

                if (!empty($entry_data))  {
                    $z  =   0;
                    $total  =   0;
                    foreach($entry_data as $idx => $val)   {
                        //if the value is an array we will only make use of the first element

                        $total   += (is_array($val)) ?   current($val) :  $val;
                        $z++;
                    }

                    //if calulation is empty then the data should be averaged
                    $data['total'][]    =   (empty($calculation))   ?  $total / $z :   $total;

                    $labels[$counter]    =   $ue->timecreated;
                    $counter++;
                }
            }

            $count = count($rgfarray);
            /* Create and populate the pData object */

            $MyData = new pData();

            $MyData->addPoints($data['total'],'Total');

            /* Create the X serie */
            $MyData->addPoints($labels,"Timestamp");
            $MyData->setSerieDescription("Timestamp","Dates");
            $MyData->setAbscissa("Timestamp");
            $MyData->setXAxisDisplay(AXIS_FORMAT_DATE);

            $myPicture = new pImage(650,400,$MyData,TRUE);
            /* Will replace the whole color scheme by the "light" palette */
            $MyData->loadPalette("{$pchartpath}/palettes/navy.color", TRUE);
            $myPicture->drawGradientArea(0,0,650,400,DIRECTION_VERTICAL,array("StartR"=>204, "StartG"=>255, "StartB"=>255, "EndR"=>255, "EndG"=>255, "EndB"=>255, "Alpha"=>50));

            $myPicture->drawRectangle(0,0,649,399,array("R"=>0,"G"=>0,"B"=>0));

            $myPicture->setFontProperties(array("R"=>0,"G"=>0,"B"=>0,"FontName"=>"{$pchartpath}/fonts/arial.ttf","FontSize"=>12));

            $myPicture->setGraphArea(60,60,619,369);
            $myPicture->setFontProperties(array("R"=>0,"G"=>0,"B"=>0,"FontName"=>"{$pchartpath}/fonts/arial.ttf","FontSize"=>8));

            $Settings = array("Pos"=>SCALE_POS_LEFTRIGHT, "Mode"=>SCALE_MODE_FLOATING, "LabelingMethod"=>LABELING_ALL, "GridR"=>255, "GridG"=>255, "GridB"=>255, "GridAlpha"=>50, "TickR"=>0, "TickG"=>0, "TickB"=>0, "TickAlpha"=>50, "LabelRotation"=>0, "CycleBackground"=>1, "DrawXLines"=>1, "DrawSubTicks"=>1, "SubTickR"=>255, "SubTickG"=>0, "SubTickB"=>0, "SubTickAlpha"=>50, "DrawYLines"=>ALL);
            $myPicture->drawScale($Settings);

            $myPicture->drawLineChart();
            $myPicture->drawPlotChart();
            /* Write a legend box */
            //$myPicture->setFontProperties(array("FontName"=>"$CFG->dirroot/blocks/ulcc_customers/pchart/fonts/arial.ttf","FontSize"=>8));
            //$myPicture->drawLegend(600,20,array("Style"=>LEGEND_NOBORDER,"Mode"=>LEGEND_HORIZONTAL,"BoxWidth"=>10,"BoxHeight"=>10));

            /* Render the picture (choose the best way) */
            return $myPicture->Stroke();
        }
