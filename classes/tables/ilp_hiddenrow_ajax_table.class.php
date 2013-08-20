<?php
    require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');


    /**
     * ilp_hiddenrow_ajax_table class
     *
     * extending the ilp_ajax_table class so we can add a hidden row to the table
     * the row can then be controlled with an onclick event (shoiw/hiiide)
     *
     */
class ilp_hiddenrow_ajax_table extends ilp_ajax_table {



    /**
     * Add a row of data to the table. This function takes an array with
     * column names as keys.
     * It ignores any elements with keys that are not defined as columns. It
     * puts in empty strings into the row when there is no element in the passed
     * array corresponding to a column in the table. It puts the row elements in
     * the proper order.
     * @param $rowwithkeys array
     * @param string $classname CSS class name to add to this row's tr tag.
     */
    function add_data_keyed($rowwithkeys, $classname = '',$onclick = NULL, $hiddendata = NULL){
        $this->add_data($this->get_row_from_keyed($rowwithkeys), $classname, $onclick,$hiddendata);
    }


    /**
     * This method actually directly echoes the row passed to it now or adds it
     * to the download. If this is the first row and start_output has not
     * already been called this method also calls start_output to open the table
     * or send headers for the downloaded.
     * Can be used as before. print_html now calls finish_html to close table.
     *
     * @param array $row a numerically keyed row of data to add to the table.
     * @param string $classname CSS class name to add to this row's tr tag.
     * @return boolean success.
     */
    function add_data($row, $classname = '',$onclick = NULL,$hiddendata = NULL) {

        if(!$this->setup) {
            return false;
        }
        if (!$this->started_output){
            $this->start_output();
        }
        if ($this->exportclass!==null){
            if ($row === null){
                $this->exportclass->add_seperator();
            } else {
                $this->exportclass->add_data($row);
            }
        } else {
            $this->print_row($row, $classname, $onclick,$hiddendata);
        }
        return true;
    }

}