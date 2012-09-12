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


    /**
     * Prints two rows of a table one show and the second hidden
     *
     * @param object $row One row from the DB results object
     * @param string $classname An optional CSS class for the row
     */
    function print_row($row, $classname = '',$onclick = null,$hiddendata=null) {

        static $suppress_lastrow = NULL;
        static $oddeven = 1;
        $rowclasses = array('r' . $oddeven);
        $oddeven = $oddeven ? 0 : 1;

        if ($classname) {
            $rowclasses[] = $classname;
        }

        $onclickevent =  (!empty($onclick)) ? ' onclick="'.$onclick.'" ' : '';

        echo '<tr  class="' . implode(' ', $rowclasses) . '">';

        // If we have a separator, print it
        if ($row === NULL) {
            $colcount = count($this->columns);
            echo '<td colspan="'.$colcount.'"><div class="tabledivider"></div></td>';
        } else {
            $colbyindex = array_flip($this->columns);
            foreach ($row as $index => $data) {
                $column = $colbyindex[$index];
                echo '<td '.$onclickevent.' class="cell c'.$index.$this->column_class[$column].'"'.$this->make_styles_string($this->column_style[$column]).'>';
                if (empty($this->sess->collapse[$column])) {
                    if ($this->column_suppress[$column] && $suppress_lastrow !== NULL && $suppress_lastrow[$index] === $data
                        && $suppress_lastrow[2] == $row[2] && $suppress_lastrow[3] == $row[3] && $suppress_lastrow[1] == $row[1] && $suppress_lastrow[0] == $row[0]) {
                        echo '&#12291;' ;
                    } else {
                        echo $data;
                    }

                } else {
                    echo '&nbsp;';
                }
                echo '</td>';
            }
        }

        echo '</tr>';

        if (!empty($hiddendata)) {
            //the hidden row
            echo '<tr  class="' . implode(' ', $rowclasses) . '">';

            $colcount = count($this->columns);
            foreach ($hiddendata as $index => $data) {
                echo '<td colspan="'.$colcount.'">';
                echo $data;
                echo '</td>';
            }
            echo "</tr>";
        }

        $suppress_enabled = array_sum($this->column_suppress);
        if ($suppress_enabled) {
            $suppress_lastrow = $row;
        }
    }





}