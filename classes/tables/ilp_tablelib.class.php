<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** ILP_TABLE_VAR_SORT = 1 */
define('ILP_TABLE_VAR_SORT',   1);
/** ILP_TABLE_VAR_HIDE = 2 */
define('ILP_TABLE_VAR_HIDE',   2);
/** ILP_TABLE_VAR_SHOW = 3 */
define('ILP_TABLE_VAR_SHOW',   3);
/** ILP_TABLE_VAR_IFIRST = 4 */
define('ILP_TABLE_VAR_IFIRST', 4);
/** ILP_TABLE_VAR_ILAST = 5 */
define('ILP_TABLE_VAR_ILAST',  5);
/** ILP_TABLE_VAR_PAGE = 6 */
define('ILP_TABLE_VAR_PAGE',   6);

/** ILP_TABLE_P_TOP = 1 */
define('ILP_TABLE_P_TOP',   1);
/** ILP_TABLE_P_BOTTOM = 2 */
define('ILP_TABLE_P_BOTTOM',  2);

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_flexible_table {

    var $uniqueid        = NULL;
    var $attributes      = array();
    var $headers         = array();
    var $columns         = array();
    var $column_style    = array();
    var $column_class    = array();
    var $column_suppress = array();
    var $column_nosort   = array('userpic');
    var $setup           = false;
    var $sess            = NULL;
    var $baseurl         = NULL;
    var $request         = array();

    var $is_collapsible = false;
    var $is_sortable    = false;
    var $use_pages      = false;
    var $use_initials   = false;

    var $maxsortkeys = 2;
    var $pagesize    = 30;
    var $currpage    = 0;
    var $totalrows   = 0;
    var $sort_default_column = NULL;
    var $sort_default_order  = SORT_ASC;

    /**
     * Array of positions in which to display download controls.
     */
    var $showdownloadbuttonsat= array(ILP_TABLE_P_TOP);


    /**
     * @var string Key of field returned by db query that is the id field of the
     * user table or equivalent.
     */
    public $useridfield = 'id';

    /**
     * @var string which download plugin to use. Default '' means none - print
     * html table with paging. Property set by is_downloading which typically
     * passes in cleaned data from $
     */
    var $download  = '';

    /**
     * @var boolean whether data is downloadable from table. Determines whether
     * to display download buttons. Set by method downloadable().
     */
    var $downloadable = false;

    /**
     * @var string which download plugin to use. Default '' means none - print
     * html table with paging.
     */
    var $defaultdownloadformat  = 'csv';

    /**
     * @var boolean Has start output been called yet?
     */
    var $started_output = false;

    var $exportclass = null;

    /**
     * Constructor
     * @param int $uniqueid
     * @todo Document properly
     */
    function ilp_flexible_table($uniqueid) {
        $this->uniqueid = $uniqueid;
        $this->request  = array(
            ILP_TABLE_VAR_SORT    => 'tsort',
            ILP_TABLE_VAR_HIDE    => 'thide',
            ILP_TABLE_VAR_SHOW    => 'tshow',
            ILP_TABLE_VAR_IFIRST  => 'tifirst',
            ILP_TABLE_VAR_ILAST   => 'tilast',
            ILP_TABLE_VAR_PAGE    => 'page'
        );
    }

    /**
     * Call this to pass the download type. Use :
     *         $download = optional_param('download', '', PARAM_ALPHA);
     * To get the download type. We assume that if you call this function with
     * params that this table's data is downloadable, so we call is_downloadable
     * for you (even if the param is '', which means no download this time.
     * Also you can call this method with no params to get the current set
     * download type.
     * @param string $download download type. One of csv, tsv, xhtml, ods, etc
     * @param string $filename filename for downloads without file extension.
     * @param string $sheettitle title for downloaded data.
     * @return string download type.  One of csv, tsv, xhtml, ods, etc
     */
    function is_downloading($download = null, $filename='', $sheettitle=''){
        if ($download!==null){
            $this->sheettitle = $sheettitle;
            $this->is_downloadable(true);
            $this->download = $download;
            $this->filename = clean_filename($filename);
            $this->export_class_instance();
        }
        return $this->download;
    }

    function export_class_instance(&$exportclass=null){
        if (!is_null($exportclass)){
            $this->started_output = true;
            $this->exportclass =& $exportclass;
            $this->exportclass->table =& $this;
        } elseif (is_null($this->exportclass) && !empty($this->download)){
            $classname = 'table_'.$this->download.'_export_format';
            $this->exportclass = new $classname($this);
            if (!$this->exportclass->document_started()){
                $this->exportclass->start_document($this->filename);
            }
        }
        return $this->exportclass;
    }


    /**
     * Probably don't need to call this directly. Calling is_downloading with a
     * param automatically sets table as downloadable.
     *
     * @param boolean $downloadable optional param to set whether data from
     * table is downloadable. If ommitted this function can be used to get
     * current state of table.
     * @return boolean whether table data is set to be downloadable.
     */
    function is_downloadable($downloadable = null){
        if ($downloadable !== null){
            $this->downloadable = $downloadable;
        }
        return $this->downloadable;
    }

    /**
     * Where to show download buttons.
     * @param array $showat array of postions in which to show download buttons.
     * Containing ILP_TABLE_P_TOP and/or ILP_TABLE_P_BOTTOM
     */
    function show_download_buttons_at($showat){
        $this->showdownloadbuttonsat = $showat;
    }


    /**
     * Sets the is_sortable variable to the given boolean, sort_default_column to
     * the given string, and the sort_default_order to the given integer.
     * @param bool $bool
     * @param string $defaultcolumn
     * @param int $defaultorder
     * @return void
     */
    function sortable($bool, $defaultcolumn = NULL, $defaultorder = SORT_ASC) {
        $this->is_sortable = $bool;
        $this->sort_default_column = $defaultcolumn;
        $this->sort_default_order  = $defaultorder;
    }

    /**
     * Do not sort using this column
     * @param string column name
     */
    function no_sorting($column) {
        $this->column_nosort[] = $column;
    }

    /**
     * Is the column sortable?
     * @param string column name, null means table
     * @return bool
     */
    function is_sortable($column=null) {
        if (empty($column)) {
            return $this->is_sortable;
        }
        if (!$this->is_sortable) {
            return false;
        }
        return !in_array($column, $this->column_nosort);
    }
    /**
     * Sets the is_collapsible variable to the given boolean.
     * @param bool $bool
     * @return void
     */
    function collapsible($bool) {
        $this->is_collapsible = $bool;
    }

    /**
     * Sets the use_pages variable to the given boolean.
     * @param bool $bool
     * @return void
     */
    function pageable($bool) {
        $this->use_pages = $bool;
    }

    /**
     * Sets the use_initials variable to the given boolean.
     * @param bool $bool
     * @return void
     */
    function initialbars($bool) {
        $this->use_initials = $bool;
    }

    /**
     * Sets the pagesize variable to the given integer, the totalrows variable
     * to the given integer, and the use_pages variable to true.
     * @param int $perpage
     * @param int $total
     * @return void
     */
    function pagesize($perpage, $total) {
        $this->pagesize  = $perpage;
        $this->totalrows = $total;
        $this->use_pages = true;
    }

    /**
     * Assigns each given variable in the array to the corresponding index
     * in the request class variable.
     * @param array $variables
     * @return void
     */
    function set_control_variables($variables) {
        foreach($variables as $what => $variable) {
            if(isset($this->request[$what])) {
                $this->request[$what] = $variable;
            }
        }
    }

    /**
     * Gives the given $value to the $attribute index of $this->attributes.
     * @param string $attribute
     * @param mixed $value
     * @return void
     */
    function set_attribute($attribute, $value) {
        $this->attributes[$attribute] = $value;
    }

    /**
     * What this method does is set the column so that if the same data appears in
     * consecutive rows, then it is not repeated.
     *
     * For example, in the quiz overview report, the fullname column is set to be suppressed, so
     * that when one student has made multiple attempts, their name is only printed in the row
     * for their first attempt.
     * @param integer $column the index of a column.
     */
    function column_suppress($column) {
        if(isset($this->column_suppress[$column])) {
            $this->column_suppress[$column] = true;
        }
    }

    /**
     * Sets the given $column index to the given $classname in $this->column_class.
     * @param integer $column
     * @param string $classname
     * @return void
     */
    function column_class($column, $classname) {
        if(isset($this->column_class[$column])) {
            $this->column_class[$column] = ' '.$classname; // This space needed so that classnames don't run together in the HTML
        }
    }

    /**
     * Sets the given $column index and $property index to the given $value in $this->column_style.
     * @param integer $column
     * @param string $property
     * @param mixed $value
     * @return void
     */
    function column_style($column, $property, $value) {
        if(isset($this->column_style[$column])) {
            $this->column_style[$column][$property] = $value;
        }
    }

    /**
     * Sets all columns' $propertys to the given $value in $this->column_style.
     * @param integer $property
     * @param string $value
     * @return void
     */
    function column_style_all($property, $value) {
        foreach(array_keys($this->columns) as $column) {
            $this->column_style[$column][$property] = $value;
        }
    }

    /**
     * Sets $this->reseturl to the given $url, and $this->baseurl to the given $url plus ? or &amp;
     * @param string $url the url with params needed to call up this page
     */
    function define_baseurl($url) {
        $this->reseturl = $url;
        if(!strpos($url, '?')) {
            $this->baseurl = $url.'?';
        }
        else {
            $this->baseurl = $url.'&amp;';
        }
    }

    /**
     * @param array $columns an array of identifying names for columns. If
     * columns are sorted then column names must correspond to a field in sql.
     */
    function define_columns($columns) {
   	
    	$this->columns = array();
        $this->column_style = array();
        $this->column_class = array();
        $colnum = 0;

        foreach($columns as $column) {
            $this->columns[$column]         = $colnum++;
            $this->column_style[$column]    = array();
            $this->column_class[$column]    = '';
            $this->column_suppress[$column] = false;
        }

    }

    /**
     * @param array $headers numerical keyed array of displayed string titles
     * for each column.
     */
    function define_headers($headers) {
        $this->headers = $headers;
    }




    /**
     * Must be called after table is defined. Use methods above first. Cannot
     * use functions below till after calling this method.
     * @return type?
     */
    function setup() {
        global $SESSION, $CFG;

        if(empty($this->columns) || empty($this->uniqueid)) {
            return false;
        }

        if (!isset($SESSION->flextable)) {
            $SESSION->flextable = array();
        }

        if(!isset($SESSION->flextable[$this->uniqueid])) {
            $SESSION->flextable[$this->uniqueid] = new stdClass;
            $SESSION->flextable[$this->uniqueid]->uniqueid = $this->uniqueid;
            $SESSION->flextable[$this->uniqueid]->collapse = array();
            $SESSION->flextable[$this->uniqueid]->sortby   = array();
            $SESSION->flextable[$this->uniqueid]->i_first  = '';
            $SESSION->flextable[$this->uniqueid]->i_last   = '';
        }

        $this->sess = &$SESSION->flextable[$this->uniqueid];


        if(!empty($_GET[$this->request[ILP_TABLE_VAR_SHOW]]) && isset($this->columns[$_GET[$this->request[ILP_TABLE_VAR_SHOW]]])) {
            // Show this column



            $this->sess->collapse[$_GET[$this->request[ILP_TABLE_VAR_SHOW]]] = false;



        }
        else if(!empty($_GET[$this->request[ILP_TABLE_VAR_HIDE]]) && isset($this->columns[$_GET[$this->request[ILP_TABLE_VAR_HIDE]]])) {
            // Hide this column
            $this->sess->collapse[$_GET[$this->request[ILP_TABLE_VAR_HIDE]]] = true;
            if(array_key_exists($_GET[$this->request[ILP_TABLE_VAR_HIDE]], $this->sess->sortby)) {
                unset($this->sess->sortby[$_GET[$this->request[ILP_TABLE_VAR_HIDE]]]);
            }
        }

        // Now, update the column attributes for collapsed columns
        foreach(array_keys($this->columns) as $column) {
            if(!empty($this->sess->collapse[$column])) {
                $this->column_style[$column]['width'] = '10px';
            }
        }

        if(
            !empty($_GET[$this->request[ILP_TABLE_VAR_SORT]]) && $this->is_sortable($_GET[$this->request[ILP_TABLE_VAR_SORT]]) &&
            (isset($this->columns[$_GET[$this->request[ILP_TABLE_VAR_SORT]]]) ||
                (($_GET[$this->request[ILP_TABLE_VAR_SORT]] == 'firstname' || $_GET[$this->request[ILP_TABLE_VAR_SORT]] == 'lastname') && isset($this->columns['fullname']))
            ))
        {
            if(empty($this->sess->collapse[$_GET[$this->request[ILP_TABLE_VAR_SORT]]])) {
                if(array_key_exists($_GET[$this->request[ILP_TABLE_VAR_SORT]], $this->sess->sortby)) {
                    // This key already exists somewhere. Change its sortorder and bring it to the top.
                    $sortorder = $this->sess->sortby[$_GET[$this->request[ILP_TABLE_VAR_SORT]]] == SORT_ASC ? SORT_DESC : SORT_ASC;
                    unset($this->sess->sortby[$_GET[$this->request[ILP_TABLE_VAR_SORT]]]);
                    $this->sess->sortby = array_merge(array($_GET[$this->request[ILP_TABLE_VAR_SORT]] => $sortorder), $this->sess->sortby);
                }
                else {
                    // Key doesn't exist, so just add it to the beginning of the array, ascending order
                    $this->sess->sortby = array_merge(array($_GET[$this->request[ILP_TABLE_VAR_SORT]] => SORT_ASC), $this->sess->sortby);
                }
                // Finally, make sure that no more than $this->maxsortkeys are present into the array
                if(!empty($this->maxsortkeys) && ($sortkeys = count($this->sess->sortby)) > $this->maxsortkeys) {
                    while($sortkeys-- > $this->maxsortkeys) {
                        array_pop($this->sess->sortby);
                    }
                }
            }
        }

        // If we didn't sort just now, then use the default sort order if one is defined and the column exists
        if(empty($this->sess->sortby) && !empty($this->sort_default_column))  {
            $this->sess->sortby = array ($this->sort_default_column => ($this->sort_default_order == SORT_DESC ? SORT_DESC : SORT_ASC));
        }

        if(isset($_GET[$this->request[ILP_TABLE_VAR_ILAST]])) {
            if(empty($_GET[$this->request[ILP_TABLE_VAR_ILAST]]) || is_numeric(strpos(get_string('alphabet'), $_GET[$this->request[ILP_TABLE_VAR_ILAST]]))) {
                $this->sess->i_last = $_GET[$this->request[ILP_TABLE_VAR_ILAST]];
            }
        }

        if(isset($_GET[$this->request[ILP_TABLE_VAR_IFIRST]])) {
            if(empty($_GET[$this->request[ILP_TABLE_VAR_IFIRST]]) || is_numeric(strpos(get_string('alphabet'), $_GET[$this->request[ILP_TABLE_VAR_IFIRST]]))) {
                $this->sess->i_first = $_GET[$this->request[ILP_TABLE_VAR_IFIRST]];
            }
        }

        if(empty($this->baseurl)) {
            $getcopy  = $_GET;
            unset($getcopy[$this->request[ILP_TABLE_VAR_SHOW]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_HIDE]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_SORT]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_IFIRST]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_ILAST]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_PAGE]]);

            $strippedurl = strip_querystring(qualified_me());

            if(!empty($getcopy)) {
                $first = false;
                $querystring = '';
                foreach($getcopy as $var => $val) {
                    if(!$first) {
                        $first = true;
                        $querystring .= '?'.$var.'='.$val;
                    }
                    else {
                        $querystring .= '&amp;'.$var.'='.$val;
                    }
                }
                $this->reseturl =  $strippedurl.$querystring;
                $querystring .= '&amp;';
            }
            else {
                $this->reseturl =  $strippedurl;
                $querystring = '?';
            }

            $this->baseurl = strip_querystring(qualified_me()) . $querystring;
        }

        // If it's "the first time" we 've been here, forget the previous initials filters
        if(qualified_me() == $this->reseturl) {
            $this->sess->i_first = '';
            $this->sess->i_last  = '';
        }

        $this->currpage = optional_param($this->request[ILP_TABLE_VAR_PAGE], 0, PARAM_INT);
        $this->setup = true;

    /// Always introduce the "flexible" class for the table if not specified
    /// No attributes, add flexible class
        if (empty($this->attributes)) {
            $this->attributes['class'] = 'flexible';
    /// No classes, add flexible class
        } else if (!isset($this->attributes['class'])) {
            $this->attributes['class'] = 'flexible';
    /// No flexible class in passed classes, add flexible class
        } else if (!in_array('flexible', explode(' ', $this->attributes['class']))) {
            $this->attributes['class'] = trim('flexible ' . $this->attributes['class']);
        }
    }

    /**
     * Get the order by clause from the session, for the table with id $uniqueid.
     * @param string $uniqueid the identifier for a table.
     * @return SQL fragment that can be used in an ORDER BY clause.
     */
    public static function get_sort_for_table($uniqueid) {
        global $SESSION;
        if(empty($SESSION->flextable[$uniqueid])) {
           return '';
        }

        $sess = &$SESSION->flextable[$uniqueid];
        if (empty($sess->sortby)) {
            return '';
        }

        return self::construct_order_by($sess->sortby);
    }

    /**
     * Prepare an an order by clause from the list of columns to be sorted.
     * @param array $cols column name => SORT_ASC or SORT_DESC
     * @return SQL fragment that can be used in an ORDER BY clause.
     */
    public static function construct_order_by($cols) {
        $bits = array();

        foreach($cols as $column => $order) {
            if ($order == SORT_ASC) {
                $bits[] = $column . ' ASC';
            } else {
                $bits[] = $column . ' DESC';
            }
        }

        return implode(', ', $bits);
    }

    /**
     * @return SQL fragment that can be used in an ORDER BY clause.
     */
    public function get_sql_sort() {
        return self::construct_order_by($this->get_sort_columns());
    }

    /**
     * Get the columns to sort by, in the form required by {@link construct_order_by()}.
     * @return array column name => SORT_... constant.
     */
    public function get_sort_columns() {
        if (!$this->setup) {
            throw new coding_exception('Cannot call get_sort_columns until you have called setup.');
        }

        if (empty($this->sess->sortby)) {
            return array();
        }

        return $this->sess->sortby;
    }

    /**
     * @return integer the offset for LIMIT clause of SQL
     */
    function get_page_start() {
        if(!$this->use_pages) {
            return '';
        }
        return $this->currpage * $this->pagesize;
    }

    /**
     * @return integer the pagesize for LIMIT clause of SQL
     */
    function get_page_size() {
        if(!$this->use_pages) {
            return '';
        }
        return $this->pagesize;
    }

    /**
     * @return string sql to add to where statement.
     */
    function get_sql_where() {
        global $DB;
        if(!isset($this->columns['fullname'])) {
            return '';
        }
        
        //$LIKE = (method_exists($DB,'sql_ilike')) ? $DB->sql_ilike()  : $DB->sql_ilike();
        
        if(!empty($this->sess->i_first) && !empty($this->sess->i_last)) {
            //return 'firstname '.$LIKE.' \''.$this->sess->i_first.'%\' AND lastname '.$LIKE.' \''.$this->sess->i_last.'%\'';
            
            $firstname	=	$DB->sql_like('firstname',"'{$this->sess->i_first}%'");
            $lastname	=	$DB->sql_like('lastname',"'{$this->sess->i_last}%'");

            return  $firstname.' AND '.$lastname;
        }
        else if(!empty($this->sess->i_first)) {
     	
			return $DB->sql_like('firstname',"'{$this->sess->i_first}%'");
        	//return 'firstname '.$LIKE.' \''.$this->sess->i_first.'%\'';
        }
        else if(!empty($this->sess->i_last)) {
            return $DB->sql_like('lastname',"'{$this->sess->i_last}%'");
        	
        	//return 'lastname '.$LIKE.' \''.$this->sess->i_last.'%\'';
        }

        return '';
    }

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
    function add_data_keyed($rowwithkeys, $classname = '',$onclick = NULL){
        $this->add_data($this->get_row_from_keyed($rowwithkeys), $classname, $onclick);
    }

    /**
     * Add a seperator line to table.
     */
    function add_separator() {
        if(!$this->setup) {
            return false;
        }
        $this->add_data(NULL);
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
    function add_data($row, $classname = '',$onclick = NULL) {

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
            $this->print_row($row, $classname, $onclick);
        }
        return true;
    }



    /**
     * You should call this to finish outputting the table data after adding
     * data to the table with add_data or add_data_keyed.
     *
     */
    function finish_output($closeexportclassdoc = true){
        if ($this->exportclass!==null){
            $this->exportclass->finish_table();
            if ($closeexportclassdoc){
                $this->exportclass->finish_document();
            }
        }else{
            $this->finish_html();
        }
    }

    /**
     * Hook that can be overridden in child classes to wrap a table in a form
     * for example. Called only when there is data to display and not
     * downloading.
     */
    function wrap_html_start(){
    }

    /**
     * Hook that can be overridden in child classes to wrap a table in a form
     * for example. Called only when there is data to display and not
     * downloading.
     */
    function wrap_html_finish(){
    }


    /**
     *
     * @param array $row row of data from db used to make one row of the table.
     * @return array one row for the table, added using add_data_keyed method.
     */
    function format_row($row){
        $formattedrow = array();
        foreach (array_keys($this->columns) as $column){
            $colmethodname = 'col_'.$column;
            if (method_exists($this, $colmethodname)){
                $formattedcolumn = $this->$colmethodname($row);
            } else {
                $formattedcolumn = $this->other_cols($column, $row);
                if ($formattedcolumn===NULL){
                    $formattedcolumn = $row->$column;
                }
            }
            $formattedrow[$column] = $formattedcolumn;
        }
        return $formattedrow;
    }

    /**
     * Fullname is treated as a special columname in tablelib and should always
     * be treated the same as the fullname of a user.
     * @uses $this->useridfield if the userid field is not expected to be id
     * then you need to override $this->useridfield to point at the correct
     * field for the user id.
     *
     */
    function col_fullname($row){
        global $COURSE, $CFG;

        if (!$this->download){
            if ($COURSE->id == SITEID) {
                return '<a href="'.$CFG->wwwroot.'/user/profile.php?id='.$row->{$this->useridfield}.  '">'.
                        fullname($row).'</a>';
            } else {
                return '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$row->{$this->useridfield}.
                        '&amp;course='.$COURSE->id.'">'.fullname($row).'</a>';
            }
        } else {
            return fullname($row);
        }
    }

    /**
     * You can override this method in a child class. See the description of
     * build_table which calls this method.
     */
    function other_cols($column, $row){
        return NULL;
    }


    /**
     * Used from col_* functions when text is to be displayed. Does the
     * right thing - either converts text to html or strips any html tags
     * depending on if we are downloading and what is the download type. Params
     * are the same as format_text function in weblib.php but some default
     * options are changed.
     */
    function format_text($text, $format=FORMAT_MOODLE, $options=NULL, $courseid=NULL){
        if (!$this->is_downloading()){
            return format_text_with_options($text, $format, $options);
        } else {
            $eci =& $this->export_class_instance();
            return $eci->format_text($text, $format, $options, $courseid);
        }
    }
    /**
     * This method is deprecated although the old api is still supported.
     * @deprecated 1.9.2 - Jun 2, 2008
     */
    function print_html() {
        if(!$this->setup) {
            return false;
        }
        $this->finish_html();
    }

    /**
     * This function is not part of the public api.
     * @return string initial of first name we are currently filtering by
     */
    function get_initial_first() {
        if(!$this->use_initials) {
            return NULL;
        }

        return $this->sess->i_first;
    }

    /**
     * This function is not part of the public api.
     * @return string initial of last name we are currently filtering by
     */
    function get_initial_last() {
        if(!$this->use_initials) {
            return NULL;
        }

        return $this->sess->i_last;
    }

    /**
     * This function is not part of the public api.
     */
    function print_initials_bar(){
        if ((!empty($this->sess->i_last) || !empty($this->sess->i_first) || $this->use_initials)
                    && isset($this->columns['fullname'])) {

            $strall = get_string('all');
            $alpha  = explode(',', get_string('alphabet', 'langconfig'));

            // Bar of first initials

            echo '<div class="initialbar firstinitial">'.get_string('firstname').' : ';
            if(!empty($this->sess->i_first)) {
                echo '<a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_IFIRST].'=">'.$strall.'</a>';
            } else {
                echo '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if (isset($this->sess->i_first) && $letter == $this->sess->i_first) {
                    echo ' <strong>'.$letter.'</strong>';
                } else {
                    echo ' <a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_IFIRST].'='.$letter.'">'.$letter.'</a>';
                }
            }
            echo '</div>';

            // Bar of last initials

            echo '<div class="initialbar lastinitial">'.get_string('lastname').' : ';
            if(!empty($this->sess->i_last)) {
                echo '<a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_ILAST].'=">'.$strall.'</a>';
            } else {
                echo '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if (isset($this->sess->i_last) && $letter == $this->sess->i_last) {
                    echo ' <strong>'.$letter.'</strong>';
                } else {
                    echo ' <a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_ILAST].'='.$letter.'">'.$letter.'</a>';
                }
            }
            echo '</div>';

        }
    }

    /**
     * This function is not part of the public api.
     */
    function print_nothing_to_display(){
        global $OUTPUT;
        $this->print_initials_bar();

        echo $OUTPUT->heading(get_string('nothingtodisplay'));
    }

    /**
     * This function is not part of the public api.
     */
    function get_row_from_keyed($rowwithkeys){
        if (is_object($rowwithkeys)){
            $rowwithkeys = (array)$rowwithkeys;
        }
        $row = array();
        foreach (array_keys($this->columns) as $column){
            if (isset($rowwithkeys[$column])){
                $row [] = $rowwithkeys[$column];
            } else {
                $row[] ='';
            }
        }
        return $row;
    }
    /**
     * This function is not part of the public api.
     */
    function get_download_menu(){
        $allclasses= get_declared_classes();
        $exportclasses = array();
        foreach ($allclasses as $class){
            $matches = array();
            if (preg_match('/^table\_([a-z]+)\_export\_format$/', $class, $matches)){
                $type = $matches[1];
                $exportclasses[$type]= get_string("download$type", 'table');
            }
        }
        return $exportclasses;
    }

    /**
     * This function is not part of the public api.
     */
    function download_buttons(){
        global $OUTPUT;
        if ($this->is_downloadable() && !$this->is_downloading()){
            $downloadoptions = $this->get_download_menu();
            $html = '<form action="'. $this->baseurl .'" method="post">';
            $html .= '<div class="mdl-align">';
            $html .= '<input type="submit" value="'.get_string('downloadas', 'table').'"/>';
            $html .= html_writer::select($downloadoptions, 'download', $this->defaultdownloadformat, false);
            $html .= $OUTPUT->old_help_icon('tableexportformats', get_string('tableexportformats', 'table'));
            $html .= '</div></form>';

            return $html;
        } else {
            return '';
        }
    }
    /**
     * This function is not part of the public api.
     * You don't normally need to call this. It is called automatically when
     * needed when you start adding data to the table.
     *
     */
    function start_output(){
        $this->started_output = true;
        if ($this->exportclass!==null){
            $this->exportclass->start_table($this->sheettitle);
            $this->exportclass->output_headers($this->headers);
        } else {
            $this->start_html();
            $this->print_headers();
        }
    }

    /**
     * This function is not part of the public api.
     */
    function print_row($row, $classname = '',$onclick = null) {
        static $suppress_lastrow = NULL;
        static $oddeven = 1;
        $rowclasses = array('r' . $oddeven);
        $oddeven = $oddeven ? 0 : 1;

        if ($classname) {
            $rowclasses[] = $classname;
        }

        $onclickevent =  (!empty($onclick)) ? ' onclick="'.$onclick.'" ' : '';

        echo '<tr '.$onclickevent.' class="' . implode(' ', $rowclasses) . '">';

        // If we have a separator, print it
        if ($row === NULL) {
            $colcount = count($this->columns);
            echo '<td colspan="'.$colcount.'><div class="tabledivider"></div></td>';
        } else {
            $colbyindex = array_flip($this->columns);
            foreach ($row as $index => $data) {
                $column = $colbyindex[$index];
                echo '<td '.$onclickevent.' class="cell c'.$index.$this->column_class[$column].'"'.$this->make_styles_string($this->column_style[$column]).'>';
                if (empty($this->sess->collapse[$column])) {
                    if ($this->column_suppress[$column] && $suppress_lastrow !== NULL && $suppress_lastrow[$index] === $data) {
                        echo '&nbsp;';
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

        $suppress_enabled = array_sum($this->column_suppress);
        if ($suppress_enabled) {
            $suppress_lastrow = $row;
        }
    }
    /**
     * This function is not part of the public api.
     */
    function finish_html(){
        global $OUTPUT, $CFG;
        if (!$this->started_output) {
            //no data has been added to the table.
            $this->print_nothing_to_display();
        } else {
            echo '</table>';
            $this->wrap_html_finish();
            // Paging bar
            if(in_array(ILP_TABLE_P_BOTTOM, $this->showdownloadbuttonsat)) {
                echo $this->download_buttons();
            }
            if($this->use_pages) {
                // disable debugging for this function call
                $debug = $CFG->debug;
                $CFG->debug = false;
                print_paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl, $this->request[ILP_TABLE_VAR_PAGE]);
                $CFG->debug = $debug;
            }
        }
    }
    /**
     * This function is not part of the public api.
     */
    function print_headers(){
        global $CFG, $OUTPUT;

        echo '<tr>';
        foreach($this->columns as $column => $index) {
            $icon_hide = '';
            $icon_sort = '';

            if($this->is_collapsible) {
                if(!empty($this->sess->collapse[$column])) {
                    // some headers contain < br/> tags, do not include in title
                    $icon_hide = ' <a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_SHOW].'='.$column.'"><img src="'.$OUTPUT->pix_url('t/switch_plus') . '" title="'.get_string('show').' '.strip_tags($this->headers[$index]).'" alt="'.get_string('show').'" /></a>';
                }
                else if($this->headers[$index] !== NULL) {
                    // some headers contain < br/> tags, do not include in title
                    $icon_hide = ' <a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_HIDE].'='.$column.'"><img src="'.$OUTPUT->pix_url('t/switch_minus') . '" title="'.get_string('hide').' '.strip_tags($this->headers[$index]).'" alt="'.get_string('hide').'" /></a>';
                }
            }

            $primary_sort_column = '';
            $primary_sort_order  = '';
            if(reset($this->sess->sortby)) {
                $primary_sort_column = key($this->sess->sortby);
                $primary_sort_order  = current($this->sess->sortby);
            }

            switch($column) {

                case 'fullname':
                if($this->is_sortable($column)) {
                    $icon_sort_first = $icon_sort_last = '';
                    if($primary_sort_column == 'firstname') {
                        $lsortorder = get_string('asc');
                        if($primary_sort_order == SORT_ASC) {
                            $icon_sort_first = ' <img src="'.$OUTPUT->pix_url('t/down') . '" alt="'.get_string('asc').'" />';
                            $fsortorder = get_string('asc');
                        }
                        else {
                            $icon_sort_first = ' <img src="'.$OUTPUT->pix_url('t/up') . '" alt="'.get_string('desc').'" />';
                            $fsortorder = get_string('desc');
                        }
                    }
                    else if($primary_sort_column == 'lastname') {
                        $fsortorder = get_string('asc');
                        if($primary_sort_order == SORT_ASC) {
                            $icon_sort_last = ' <img src="'.$OUTPUT->pix_url('t/down') . '" alt="'.get_string('asc').'" />';
                            $lsortorder = get_string('asc');
                        }
                        else {
                            $icon_sort_last = ' <img src="'.$OUTPUT->pix_url('t/up') . '" alt="'.get_string('desc').'" />';
                            $lsortorder = get_string('desc');
                        }
                    } else {
                        $fsortorder = get_string('asc');
                        $lsortorder = get_string('asc');
                    }

                    $override = new object();
                    $override->firstname = 'firstname';
                    $override->lastname = 'lastname';
                    $fullnamelanguage = get_string('fullnamedisplay', '', $override);

                    if (($CFG->fullnamedisplay == 'firstname lastname') or
                        ($CFG->fullnamedisplay == 'firstname') or
                        ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
                        $this->headers[$index] = '<a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_SORT].'=firstname">'.get_string('firstname').get_accesshide(get_string('sortby').' '.get_string('firstname').' '.$fsortorder).'</a> '.$icon_sort_first.' / '.
                                                 '<a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_SORT].'=lastname">'.get_string('lastname').get_accesshide(get_string('sortby').' '.get_string('lastname').' '.$lsortorder).'</a> '.$icon_sort_last;
                    } else {
                        $this->headers[$index] = '<a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_SORT].'=lastname">'.get_string('lastname').get_accesshide(get_string('sortby').' '.get_string('lastname').' '.$lsortorder).'</a> '.$icon_sort_last.' / '.
                                                 '<a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_SORT].'=firstname">'.get_string('firstname').get_accesshide(get_string('sortby').' '.get_string('firstname').' '.$fsortorder).'</a> '.$icon_sort_first;
                    }
                }
                break;

                case 'userpic':
                    // do nothing, do not display sortable links
                break;

                default:
                if($this->is_sortable($column)) {
                    if($primary_sort_column == $column) {
                        if($primary_sort_order == SORT_ASC) {
                            $icon_sort = ' <img src="'.$OUTPUT->pix_url('t/down') . '" alt="'.get_string('asc').'" />';
                            $localsortorder = get_string('asc');
                        }
                        else {
                            $icon_sort = ' <img src="'.$OUTPUT->pix_url('t/up') . '" alt="'.get_string('desc').'" />';
                            $localsortorder = get_string('desc');
                        }
                    } else {
                        $localsortorder = get_string('asc');
                    }
                    $this->headers[$index] = '<a href="'.$this->baseurl.$this->request[ILP_TABLE_VAR_SORT].'='.$column.'">'.$this->headers[$index].get_accesshide(get_string('sortby').' '.$this->headers[$index].' '.$localsortorder).'</a>';
                }
            }

            if($this->headers[$index] === NULL) {
                echo '<th class="header c'.$index.$this->column_class[$column].'" scope="col">&nbsp;</th>';
            }
            else if(!empty($this->sess->collapse[$column])) {
                echo '<th class="header c'.$index.$this->column_class[$column].'" scope="col">'.$icon_hide.'</th>';
            }
            else {
                // took out nowrap for accessibility, might need replacement
                if (!is_array($this->column_style[$column])) {
                    // $usestyles = array('white-space:nowrap');
                    $usestyles = '';
                 } else {
                    // $usestyles = $this->column_style[$column]+array('white-space'=>'nowrap');
                    $usestyles = $this->column_style[$column];
                 }
                echo '<th class="header c'.$index.$this->column_class[$column].'" '.$this->make_styles_string($usestyles).' scope="col">'.$this->headers[$index].$icon_sort.'<div class="commands">'.$icon_hide.'</div></th>';
            }

        }
        echo '</tr>';
    }

    /**
     * This function is not part of the public api.
     */
    function start_html(){
        global $OUTPUT, $CFG;
        // Do we need to print initial bars?
        $this->print_initials_bar();

        // Paging bar
        if($this->use_pages) {
            // disable debugging for this function call
            $debug = $CFG->debug;
            $CFG->debug = false;
            print_paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl, $this->request[ILP_TABLE_VAR_PAGE]);
            $CFG->debug = $debug;
        }

        if(in_array(ILP_TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();
        // Start of main data table

        echo '<table'.$this->make_attributes_string($this->attributes).'>';

    }

    /**
     * This function is not part of the public api.
     * @todo Document
     * @return type?
     */
    function make_styles_string(&$styles) {
        if(empty($styles)) {
            return '';
        }

        $string = ' style="';
        foreach($styles as $property => $value) {
            $string .= $property.':'.$value.';';
        }
        $string .= '"';
        return $string;
    }

    /**
     * This function is not part of the public api.
     * It adds classes to the table.
     * @todo Document
     * @return type?
     */
    function make_attributes_string(&$attributes) {
        if(empty($attributes)) {
            return '';
        }

        $string = ' ';
        foreach($attributes as $attr => $value) {
            $string .= ($attr.'="'.$value.'" ');
        }

        return $string;
    }
}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_sql extends ilp_flexible_table{

    public $countsql = NULL;
    public $countparams = NULL;
    /**
     * @var object sql for querying db. Has fields 'fields', 'from', 'where', 'params'.
     */
    public $sql = NULL;
    /**
     * @var array Data fetched from the db.
     */
    public $rawdata = NULL;

    /**
     * @var boolean Overriding default for this.
     */
    public $is_sortable    = true;
    /**
     * @var boolean Overriding default for this.
     */
    public $is_collapsible = true;


    /**
     * @param string $uniqueid a string identifying this table.Used as a key in
     *                          session  vars.
     */
    function ilp_table_sql($uniqueid){
        parent::ilp_flexible_table($uniqueid);
        // some sensible defaults
        $this->set_attribute('cellspacing', '0');
        $this->set_attribute('class', 'generaltable generalbox');
    }

    /**
     * Take the data returned from the db_query and go through all the rows
     * processing each col using either col_{columnname} method or other_cols
     * method or if other_cols returns NULL then put the data straight into the
     * table.
     */
    function build_table(){
        if ($this->rawdata){
            foreach($this->rawdata as $row){
                $formattedrow = $this->format_row($row);
                $this->add_data_keyed($formattedrow);
            }
        }
    }


    /**
     * This is only needed if you want to use different sql to count rows.
     * Used for example when perhaps all db JOINS are not needed when counting
     * records. You don't need to call this function the count_sql
     * will be generated automatically.
     *
     * We need to count rows returned by the db seperately to the query itself
     * as we need to know how many pages of data we have to display.
     */
    function set_count_sql($sql, $params=array()){
        $this->countsql = $sql;
        $this->countparams = $params;
    }

    /**
     * Set the sql to query the db. Query will be :
     *      SELECT $fields FROM $from WHERE $where
     * Of course you can use sub-queries, JOINS etc. by putting them in the
     * appropriate clause of the query.
     */
    function set_sql($fields, $from, $where, $params=array()){
        $this->sql = new object();
        $this->sql->fields = $fields;
        $this->sql->from = $from;
        $this->sql->where = $where;
        $this->sql->params = $params;
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param integer $pagesize size of page for paginated displayed table.
     * @param boolean $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
     */
    function query_db($pagesize, $useinitialsbar=true){
        global $DB;
        if (!$this->is_downloading()) {
            if ($this->countsql === NULL){
                $this->countsql = 'SELECT COUNT(1) FROM '.$this->sql->from.' WHERE '.$this->sql->where;
            }
            if ($useinitialsbar && !$this->is_downloading()) {
                $totalinitials = $DB->count_records_sql($this->countsql, $this->countparams);
                $this->initialbars($totalinitials>$pagesize);
            }

            if ($this->get_sql_where()) {
                $this->countsql .= ' AND '.$this->get_sql_where();
                $this->sql->where .= ' AND '.$this->get_sql_where();
                $total  = $DB->count_records_sql($this->countsql, $this->countparams);
            } else {
                $total = $totalinitials;
            }


            $this->pagesize($pagesize, $total);
        }

        // Fetch the attempts
        $sort = $this->get_sql_sort();
        $sort = $sort?" ORDER BY {$sort}":'';
        $sql = "SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}{$sort}";
        if (!$this->is_downloading()) {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params, $this->get_page_start(), $this->get_page_size());
        } else {
            $this->rawdata = $DB->get_records_sql($sql, $this->sql->params);
        }
    }


    /**
     * Convenience method to call a number of methods for you to display the
     * table.
     */
    function out($pagesize, $useinitialsbar, $downloadhelpbutton=''){
        global $DB;
        if (!$this->columns){
            $onerow = $DB->get_record_sql("SELECT {$this->sql->fields} FROM {$this->sql->from} WHERE {$this->sql->where}", $this->sql->params);
            //if columns is not set then define columns as the keys of the rows returned
            //from the db.
            $this->define_columns(array_keys((array)$onerow));
            $this->define_headers(array_keys((array)$onerow));
        }
        $this->setup();
        $this->query_db($pagesize, $useinitialsbar);
        $this->build_table();
        $this->finish_output();
    }
}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_default_export_format_parent{
    /**
     * @var ilp_flexible_table or child class reference pointing to table class
     * object from which to export data.
     */
    var $table;

    /**
     * @var boolean output started. Keeps track of whether any output has been
     * started yet.
     */
    var $documentstarted = false;
    function ilp_table_default_export_format_parent(&$table){
        $this->table =& $table;
    }

    function set_table(&$table){
        $this->table =& $table;
    }

    function add_data($row) {
        return false;
    }
    function add_seperator() {
        return false;
    }
    function document_started(){
        return $this->documentstarted;
    }
    /**
     * Given text in a variety of format codings, this function returns
     * the text as safe HTML or as plain text dependent on what is appropriate
     * for the download format. The default removes all tags.
     */
    function format_text($text, $format=FORMAT_MOODLE, $options=NULL, $courseid=NULL){
        //use some whitespace to indicate where there was some line spacing.
        $text = str_replace(array('</p>', "\n", "\r"), '   ', $text);
        return strip_tags($text);
    }
}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_spreadsheet_export_format_parent extends ilp_table_default_export_format_parent{
    var $rownum;
    var $workbook;
    var $worksheet;
    /**
     * @var object format object - format for normal table cells
     */
    var $formatnormal;
    /**
     * @var object format object - format for header table cells
     */
    var $formatheaders;

    /**
     * should be overriden in child class.
     */
    var $fileextension;

    /**
     * This method will be overridden in the child class.
     */
    function define_workbook(){
    }
    function start_document($filename){
        $filename = $filename.'.'.$this->fileextension;
        $this->define_workbook();
        // format types
        $this->formatnormal =& $this->workbook->add_format();
        $this->formatnormal->set_bold(0);
        $this->formatheaders =& $this->workbook->add_format();
        $this->formatheaders->set_bold(1);
        $this->formatheaders->set_align('center');
        // Sending HTTP headers
        $this->workbook->send($filename);
        $this->documentstarted = true;
    }
    function start_table($sheettitle){
        $this->worksheet =& $this->workbook->add_worksheet($sheettitle);
        $this->rownum=0;
    }
    function output_headers($headers){
        $colnum = 0;
        foreach ($headers as $item) {
            $this->worksheet->write($this->rownum,$colnum,$item,$this->formatheaders);
            $colnum++;
        }
        $this->rownum++;
    }
    function add_data($row){
        $colnum = 0;
        foreach($row as $item){
            $this->worksheet->write($this->rownum,$colnum,$item,$this->formatnormal);
            $colnum++;
        }
        $this->rownum++;
        return true;
    }
    function add_seperator() {
        $this->rownum++;
        return true;
    }

    function finish_table(){
    }
    function finish_document(){
        $this->workbook->close();
        exit;
    }
}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_excel_export_format extends ilp_table_spreadsheet_export_format_parent{
    var $fileextension = 'xls';

    function define_workbook(){
        global $CFG;
        require_once("$CFG->libdir/excellib.class.php");
        // Creating a workbook
        $this->workbook = new MoodleExcelWorkbook("-");
    }

}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_ods_export_format extends ilp_table_spreadsheet_export_format_parent{
    var $fileextension = 'ods';
    function define_workbook(){
        global $CFG;
        require_once("$CFG->libdir/odslib.class.php");
        // Creating a workbook
        $this->workbook = new MoodleODSWorkbook("-");
    }
}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_text_export_format_parent extends ilp_table_default_export_format_parent{
    var $seperator = "\t";
    function start_document($filename){
        $this->filename = $filename.".txt";
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"{$filename}.txt\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");
        $this->documentstarted = true;
    }
    function start_table($sheettitle){
        //nothing to do here
    }
    function output_headers($headers){
        echo implode($this->seperator, $headers)."\n";
    }
    function add_data($row){
        echo implode($this->seperator, $row)."\n";
        return true;
    }
    function finish_table(){
        echo "\n\n";
    }
    function finish_document(){
        exit;
    }
}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_tsv_export_format extends ilp_table_text_export_format_parent{
    var $seperator = "\t";

}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_csv_export_format extends ilp_table_text_export_format_parent{
    var $seperator = ",";

}

/**
 * @package   moodlecore
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilp_table_xhtml_export_format extends ilp_table_default_export_format_parent{
    function start_document($filename){
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename.html\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");
        //html headers
        echo <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml"
  xml:lang="en" lang="en">
<head>
<style type="text/css">/*<![CDATA[*/

.flexible th {
white-space:normal;
}
th.header, td.header, div.header {
border-color:#DDDDDD;
background-color:lightGrey;
}
.flexible th {
white-space:nowrap;
}
th {
font-weight:bold;
}

.generaltable {
border-style:solid;
}
.generalbox {
border-style:solid;
}
body, table, td, th {
font-family:Arial,Verdana,Helvetica,sans-serif;
font-size:100%;
}
td {
    border-style:solid;
    border-width:1pt;
}
table {
    border-collapse:collapse;
    border-spacing:0pt;
    width:80%;
    margin:auto;
}

h1, h2{
    text-align:center;
}
.bold {
font-weight:bold;
}
.mdl-align {
    text-align:center;
}


/*]]>*/</style>
<title>$filename</title>
</head>
<body>
EOF;
        $this->documentstarted = true;
    }
    function start_table($sheettitle){
        $this->table->sortable(false);
        $this->table->collapsible(false);
        echo "<h2>{$sheettitle}</h2>";
        $this->table->start_html();
    }


    function output_headers($headers){
        $this->table->print_headers();
    }
    function add_data($row){
        $this->table->print_row($row);
        return true;
    }
    function add_seperator() {
        $this->table->print_row(NULL);
        return true;
    }
    function finish_table(){
        $this->table->finish_html();
    }
    function finish_document(){
        echo "</body>\n</html>";
        exit;
    }
    function format_text($text, $format=FORMAT_MOODLE, $options=NULL, $courseid=NULL){
        return format_text_with_options($text, $format, $options);
    }
}
