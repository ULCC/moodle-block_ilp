<?php
/**
 * AJAX Table class
 * It contains the basic definition to properly work with Ajax and
 * Horizontal/Vertical pagination
 *
 * @uses ilp_flexible_table()
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

// fetch the table library
require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_tablelib.class.php');

define('ILP_TABLE_VAR_PAGESIZE',   7);
define('ILP_TABLE_VAR_HOZOFFSET',  8);
define('ILP_TABLE_VAR_FILTERS',    9);

class ilp_ajax_table extends ilp_flexible_table {

    // urls for the table's links
    var $baseurl        = NULL;
    var $ajaxurl        = NULL;
    var $fragment       = NULL;

    // vertical pagination
    var $use_pages      = false;
    var $pagesize       = 10;
    var $totalrows      = 0;
    var $currpage       = 0;

    // horizontal pagination
    var $use_hozpages   = false;
    var $hozsize        = 10;
    var $totalcols      = 0;
    var $currhoz        = 0;
    var $hoz_string     = 'displayingcolumns';
    var $hozcols        = 0;

    var $headeronclick  = NULL;

    // filters
    var $filters        = array();

    // custom header style classes
    var $header_class   = 'header';

    // language token for nothing to display
    var $nothing        = 'nothingtodisplay';

    //used to determine whether the expand and collapse
    //buttns will be used
    var $use_expandcollapse = false;

    //
    var    $expandcollapse_cols  =   array();

    /**
     * Constructor
     * @param int $uniqueid The id of the table div
     * @param bool $displayperpage Do we want to display the pagination form? (breaks some pages e.g.
     * create moodle activity evidence because you end up with nested forms)
     * @todo Document properly
     */
    function __construct($uniqueid, $displayperpage=true) {
        global $CFG, $SESSION, $DB, $USER;


        $this->uniqueid = $uniqueid;
        $this->displayperpage = $displayperpage;
        $hozsize = get_config('block_ilp', 'defaulthozsize');
        $this->pagesize = get_config('block_ilp', 'display');
        $this->request = array(
            ILP_TABLE_VAR_SORT      => 'tsort',
            ILP_TABLE_VAR_HIDE      => 'thide',
            ILP_TABLE_VAR_SHOW      => 'tshow',
            ILP_TABLE_VAR_IFIRST    => 'tifirst',
            ILP_TABLE_VAR_ILAST     => 'tilast',
            ILP_TABLE_VAR_PAGE      => 'page',
            ILP_TABLE_VAR_PAGESIZE  => 'pagesize',
            ILP_TABLE_VAR_HOZOFFSET => 'currhoz',
            ILP_TABLE_VAR_FILTERS   => 'filters'
        );

        // include and instantiate the db class
        require_once($CFG->dirroot."/blocks/ilp/classes/database/ilp_db.php");
        $this->dbc = new ilp_db();

        if (!isset($SESSION->flextable)) {
            $SESSION->flextable = array();
        }
        // if session is not set for the flex table, then need to set.
        if(!isset($SESSION->flextable[$this->uniqueid])) {
            $SESSION->flextable[$this->uniqueid] = new stdClass;
            $SESSION->flextable[$this->uniqueid]->uniqueid = $this->uniqueid;
            $SESSION->flextable[$this->uniqueid]->collapse = array();
            $SESSION->flextable[$this->uniqueid]->sortby   = array();
            $SESSION->flextable[$this->uniqueid]->i_first  = '';
            $SESSION->flextable[$this->uniqueid]->i_last   = '';
            $SESSION->flextable[$this->uniqueid]->pagesize = $this->pagesize;
            $SESSION->flextable[$this->uniqueid]->currpage = $this->currpage;
            $SESSION->flextable[$this->uniqueid]->currhoz  = $this->currhoz;
            $SESSION->flextable[$this->uniqueid]->filters  = $this->filters;
            // now let's load the hidden column info from db

            $existing_data = $DB->get_record_select('block_ilp_user_choice',
                                                    "user_id = :user_id AND ".$DB->sql_compare_text('element_id').' = :element_id',
                                                    array('user_id' => $USER->id, 'element_id' => $uniqueid));
            if($existing_data){
                //load them
                $user_choice = explode(',',$existing_data->choice);
                foreach($user_choice as $choice){
                    $SESSION->flextable[$this->uniqueid]->collapse[$choice] = true;
                }
            }
        }

        
        $this->sess = &$SESSION->flextable[$this->uniqueid];


        
        if(isset($_POST[$this->uniqueid][$this->request[ILP_TABLE_VAR_PAGESIZE]])) {
            $this->sess->pagesize = $_POST[$this->uniqueid][$this->request[ILP_TABLE_VAR_PAGESIZE]];

            // reset the current page to zero as we're altering the number of pages
            $this->sess->currpage = 0;
        }

        $this->pagesize = $this->sess->pagesize;

        if(isset($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HOZOFFSET]])) {
            $this->sess->currhoz = $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HOZOFFSET]];
        }

        $this->currhoz = $this->sess->currhoz;

        if(isset($_POST[$this->uniqueid][$this->request[ILP_TABLE_VAR_FILTERS]])) {
            $this->sess->filters = $_POST[$this->uniqueid][$this->request[ILP_TABLE_VAR_FILTERS]];
        }

        $this->filters = $this->sess->filters;

        if(isset($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_PAGE]])) {
            $this->sess->currpage = $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_PAGE]];
        }

        // set the optional filters
        $this->set_default_filters();
    }

    /**
     * Sets the use_initials variable to the given boolean.
     * @param bool $bool
     * @return void
     */
    function use_expandcollapselinks($bool) {
        $this->use_expandcollapse = $bool;
    }

    /**
     * Set defaults for the optional filters.
     *
     */
    function set_default_filters() {

    }

    /**
     * Returns the current setting for a given filter
     *
     * @param string $filter the name of the filter that's required
     * @return filter
     */
    function get_filter($filter) {
        global $SESSION;

        return $SESSION->flextable[$this->uniqueid]->filters[$filter];
    }


    /**
     * Sets $this->ajaxurl to the given $url plus ? or &amp;
     * @param string $url the url with params needed to call up this page
     */
    function define_ajaxurl($url) {
        if(!strpos($url, '?')) {
            $this->ajaxurl = $url.'?';
        }
        else {
            $this->ajaxurl = $url.'&amp;';
        }
    }

    /**
     * Sets the fragment identifier to be used as $baseurl#$fragment
     *
     */
    function define_fragment($fragment) {
        $this->fragment = '#'.$fragment;
    }

    /**
     * Must be called after table is defined. Use methods above first. Cannot
     * use functions below till after calling this method.
     * @return type?
     */
    function setup() {
        global $SESSION, $CFG, $DB, $USER;

        if(empty($this->columns) || empty($this->uniqueid)) {
            return false;
        }

        if(!empty($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SHOW]]) && (isset($this->columns[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SHOW]]]) || $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SHOW]] == "expandall")) {

            $showcolumn   =   $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SHOW]];

            if ($showcolumn == 'expandall') {
                foreach($this->expandcollapse_cols as $ec) {
                    $this->sess->collapse[$ec] = false;
                }
            } else {
                // Show this column
                $this->sess->collapse[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SHOW]]] = false;
            }
        } else if(!empty($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HIDE]]) && (isset($this->columns[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HIDE]]]) ||  $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HIDE]] == "collapseall")) {
            $hidecolumn   =   $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HIDE]];
            if ($hidecolumn == 'collapseall') {
                foreach($this->expandcollapse_cols as $ec) {
                    $this->sess->collapse[$ec] = true;
                    if(array_key_exists($ec, $this->sess->sortby)) {
                        unset($this->sess->sortby[$ec]);
                    }
                }
            } else {
                // Hide this column

                $this->sess->collapse[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HIDE]]] = true;
                if(array_key_exists($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HIDE]], $this->sess->sortby)) {
                    unset($this->sess->sortby[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_HIDE]]]);
                }
            }
        }

        //save the latest changes to db for later use
        $user_choice = array();
        //$my_table = $SESSION->flextable;
        $data = new stdClass();
        $data->user_id = $USER->id;
        $data->element_id = $this->uniqueid;
        $data->modified = time();

        foreach($this->sess->collapse as $key=>$value){
            if($value == true){
                $user_choice[] = $key;
            }
        }

        if($user_choice){
            $data->choice = implode(',',$user_choice);
        }else{
            $data->choice = '';
        }

        $existing_data = $DB->get_record_select('block_ilp_user_choice',
                                                "user_id = :user_id and ".$DB->sql_compare_text('element_id').' = :element_id',
                                                array('user_id' => $USER->id, 'element_id' => $data->element_id));

        if($existing_data){
            $data->id = $existing_data->id;
            $DB->update_record('block_ilp_user_choice', $data);
        }else {
            $DB->insert_record('block_ilp_user_choice',$data);
        }
        
        // Now, update the column attributes for collapsed columns
        foreach(array_keys($this->columns) as $column) {
            if(!empty($this->sess->collapse[$column])) {
                $this->column_style[$column]['width'] = '10px';
            }
        }

        
        
        if(
            !empty($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]]) && $this->is_sortable($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]]) &&
            (isset($this->columns[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]]]) ||
                (($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]] == 'firstname' || $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]] == 'lastname') && isset($this->columns['fullname']))
            ))
        {
            if(empty($this->sess->collapse[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]]])) {
                if(array_key_exists($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]], $this->sess->sortby)) {
                    // This key already exists somewhere. Change its sortorder and bring it to the top.
                    $sortorder = $this->sess->sortby[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]]] == SORT_ASC ? SORT_DESC : SORT_ASC;
                    unset($this->sess->sortby[$_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]]]);
                    $this->sess->sortby = array_merge(array($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]] => $sortorder), $this->sess->sortby);
                }
                else {
                    // Key doesn't exist, so just add it to the beginning of the array, ascending order
                    $this->sess->sortby = array_merge(array($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_SORT]] => SORT_ASC), $this->sess->sortby);
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

        if(isset($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_ILAST]])) {
            if(empty($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_ILAST]]) || is_numeric(strpos(get_string('alphabet','langconfig'), $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_ILAST]]))) {
                $this->sess->i_last = $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_ILAST]];
            }
        }

        if(isset($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_IFIRST]])) {
            if(empty($_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_IFIRST]]) || is_numeric(strpos(get_string('alphabet','langconfig'), $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_IFIRST]]))) {
                $this->sess->i_first = $_GET[$this->uniqueid][$this->request[ILP_TABLE_VAR_IFIRST]];
            }
        }

        if(empty($this->baseurl)) {
            $getcopy  = !empty($_GET[$this->uniqueid]) ? $_GET[$this->uniqueid] : array();
            unset($getcopy[$this->request[ILP_TABLE_VAR_SHOW]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_HIDE]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_SORT]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_IFIRST]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_ILAST]]);
            unset($getcopy[$this->request[ILP_TABLE_VAR_PAGE]]);

            $strippedurl = strip_querystring(qualified_me());

            if (!empty($getcopy)) {
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

        $params = optional_param($this->uniqueid, array(),PARAM_RAW);
        $this->currpage = isset($params[$this->request[ILP_TABLE_VAR_PAGE]]) ? $params[$this->request[ILP_TABLE_VAR_PAGE]] : $SESSION->flextable[$this->uniqueid]->currpage;
        $this->setup = true;

    /// Always introduce the "flexible" class for the table if not specified
        // added header class instead of flexible
    /// No attributes, add flexible class

        if (empty($this->attributes)) {
            $this->attributes['class'] = 'flexible';
    /// No classes, add flexible class
        } else if (!isset($this->attributes['class'])) {
            $this->attributes['class'] = 'flexible';
    /// No flexible class in passed classes, add flexible class
        } else if (!in_array('flexible', explode(' ', $this->attributes['class']))) {
            $this->attributes['class'] = trim('flexible' . $this->attributes['class']);
        }

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

        // ensure that all the sort keys are actual columns
        foreach($this->sess->sortby as $key => $index) {
            if(!isset($this->columns[$key])) {
                if(!in_array($key, array('firstname', 'lastname')) ) {
                    unset($this->sess->sortby[$key]);
                }
            }
        }

        return $this->sess->sortby;
    }

    /**
     * @return string sql to add to where statement.
     */
    function get_sql_where() {
        $return = array();

        $where = parent::get_sql_where();
        
        if(!empty($where)) {
            $return[] = $where;
        }

        $filters = $this->get_sql_filters();
        if(!empty($filters)) {
            $return[] = $filters;
        }

        return implode(' AND ', $return);
    }

    /**
     * @return string sql to add to where statement.
     */
    function get_sql_filters() {
        return '';
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
                $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_IFIRST].']=';
                echo '<a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.$strall.'</a>';
            } else {
                echo '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if (isset($this->sess->i_first) && $letter == $this->sess->i_first) {
                    echo ' <strong>'.$letter.'</strong>';
                } else {
                    $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_IFIRST].']='.$letter.'&'.$this->uniqueid.'['.$this->request[ILP_TABLE_VAR_PAGE].']=0';
                    echo ' <a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.$letter.'</a>';
                } 
            }
            echo '</div>';

            // Bar of last initials

            echo '<div class="initialbar lastinitial">'.get_string('lastname').' : ';
            if(!empty($this->sess->i_last)) {
                $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_ILAST].']=';
                echo '<a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.$strall.'</a>';
            } else {
                echo '<strong>'.$strall.'</strong>';
            }
            foreach ($alpha as $letter) {
                if (isset($this->sess->i_last) && $letter == $this->sess->i_last) {
                    echo ' <strong>'.$letter.'</strong>';
                } else {
                    $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_ILAST].']='.$letter.'&'.$this->uniqueid.'['.$this->request[ILP_TABLE_VAR_PAGE].']=0';
                    echo ' <a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.$letter.'</a>';
                }
            }
            echo '</div>';

        }
    }

    /**
     * Prints the message that there is nothing to display instead of the table (with filters if needed)
     */
    function print_nothing_to_display(){
        global $OUTPUT;
        $this->print_filters();
        $this->print_initials_bar();

        echo $OUTPUT->heading(get_string($this->nothing, 'block_ilp'));
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
            $this->print_filters();
            $this->start_html();
            $this->print_hozpaging_bar();
            $this->print_headers();
        }
    }

    /**
     * Returns html to display filters for the table.
     *
     * To be overwritten by child class.
     *
     */
    function print_filters() {
        return null;
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

    /**
     * This function is not part of the public api.
     */
    function finish_html(){
        global $OUTPUT;
        if (!$this->started_output) {
            //no data has been added to the table.
            $this->print_nothing_to_display();
        } else {

            echo '</table>';

            if($this->use_pages) {
                $this->print_paging_bar();
            }

            $this->wrap_html_finish();
            // Paging bar
            if(in_array(ILP_TABLE_P_BOTTOM, $this->showdownloadbuttonsat)) {
                echo $this->download_buttons();
            }
        }
    }

    /**
     * This function is not part of the public api.
     */
    function print_headers(){
        global $CFG, $OUTPUT;

        // add a random element to the url so clicking the same sort column works
        // when javascript is disabled, as the link will have a URI fragment
        $this->baseurl = preg_replace('/&amp;rand=[0-9]+/', '', $this->baseurl).'rand='.rand().'&amp;';

        echo '<tr>';
        foreach($this->columns as $column => $index) {
            $icon_hide = '';
            $icon_sort = '';

            if($this->is_collapsible) {
                if(!empty($this->sess->collapse[$column])) {
                    // some headers contain < br/> tags, do not include in title
                    $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_SHOW].']='.$column;
                    $icon_hide = ' <a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');"><img src="'.$OUTPUT->pix_url('t/switch_plus') . '" title="'.get_string('show').' '.strip_tags($this->headers[$index]).'" alt="'.get_string('show').'" /></a>';
                }
                else if($this->headers[$index] !== NULL) {
                    // some headers contain < br/> tags, do not include in title
                    $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_HIDE].']='.$column;
                    $icon_hide = ' <a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');"><img src="'.$OUTPUT->pix_url('t/switch_minus') . '" title="'.get_string('hide').' '.strip_tags($this->headers[$index]).'" alt="'.get_string('hide').'" /></a>';
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
                    $icon_sort_first = '<img src="'.$OUTPUT->pix_url('t/move') . '" title="'.get_string('sortasc', 'grades').'" alt="'.get_string('asc').'" />';
                    $icon_sort_last = $icon_sort_first;

                    $fsortorder = get_string('asc');
                    $lsortorder = get_string('asc');

                    if($primary_sort_column == 'firstname') {
                        $lsortorder = get_string('asc');
                        if($primary_sort_order == SORT_ASC) {
                            $icon_sort_first = '<img src="'.$OUTPUT->pix_url('t/down') . '" title="'.get_string('sortdesc', 'grades').'" alt="'.get_string('desc').'" />';
                            $fsortorder = get_string('asc');
                        }
                        else {
                            $icon_sort_first = '<img src="'.$OUTPUT->pix_url('t/up') . '" title="'.get_string('sortasc', 'grades').'" alt="'.get_string('asc').'" />';
                            $fsortorder = get_string('desc');
                        }
                    }
                    else if($primary_sort_column == 'lastname') {
                        $fsortorder = get_string('asc');
                        if($primary_sort_order == SORT_ASC) {
                            $icon_sort_last = '<img src="'.$OUTPUT->pix_url('t/down') . '" title="'.get_string('sortdesc', 'grades').'" alt="'.get_string('desc').'" />';
                            $lsortorder = get_string('asc');
                        }
                        else {
                            $icon_sort_last = '<img src="'.$OUTPUT->pix_url('t/up') . '" title="'.get_string('sortasc', 'grades').'" alt="'.get_string('asc').'" />';
                            $lsortorder = get_string('desc');
                        }
                    }

                    $override = new stdClass();
                    $override->firstname = 'firstname';
                    $override->lastname = 'lastname';
                    $fullnamelanguage = get_string('fullnamedisplay', '', $override);

                    if (($CFG->fullnamedisplay == 'firstname lastname') or
                        ($CFG->fullnamedisplay == 'firstname') or
                        ($CFG->fullnamedisplay == 'language' and $fullnamelanguage == 'firstname lastname' )) {
                        $suffix1 = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_SORT].']=firstname';
                        $suffix2 = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_SORT].']=lastname';
                        $this->headers[$index] = get_string('firstname').'&nbsp;<a href="'.$this->baseurl.$suffix1.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix1.'\');">'.$icon_sort_first.'</a> / '.
                                                 get_string('lastname').'&nbsp;<a href="'.$this->baseurl.$suffix2.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix2.'\');">'.$icon_sort_last.'</a> ';
                    } else {
                        $suffix1 = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_SORT].']=lastname';
                        $suffix2 = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_SORT].']=firstname';
                        $this->headers[$index] = get_string('lastname').'&nbsp;<a href="'.$this->baseurl.$suffix1.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix1.'\');">'.$icon_sort_last.'</a> / '.
                                                 get_string('firstname').'&nbsp;<a href="'.$this->baseurl.$suffix2.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix2.'\');">'.$icon_sort_first.'</a> ';
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
                            $icon_sort = ' <img src="'.$OUTPUT->pix_url('t/down') . '" title="'.get_string('sortdesc', 'grades').'" alt="'.get_string('desc').'" />';
                            $localsortorder = get_string('desc');
                        }
                        else {
                            $icon_sort = ' <img src="'.$OUTPUT->pix_url('t/up') . '" title="'.get_string('sortasc', 'grades').'" alt="'.get_string('asc').'" />';
                            $localsortorder = get_string('asc');
                        }
                    } else {
                        $icon_sort = '<img src="'.$OUTPUT->pix_url('t/move') . '" title="'.get_string('sortasc', 'grades').'" alt="'.get_string('asc').'" />';
                        $localsortorder = get_string('asc');
                    }
                    $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_SORT].']='.$column;
                    $this->headers[$index] = $this->headers[$index].'&nbsp;<a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.$icon_sort.'</a>';
                }

                $this->headers[$index] .= $this->get_header_suffix($column);
            }

            if($this->headers[$index] === NULL) {
                echo '<th class="'.$this->header_class.' c'.$index.' '.$this->column_class[$column].'" scope="col">&nbsp;</th>';
            }
            else if(!empty($this->sess->collapse[$column])) {
                echo '<th class="'.$this->header_class.' c'.$index.' '.$this->column_class[$column].'" scope="col">'.$icon_hide.'</th>';
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

                $onclick = (!empty($this->headeronclick)) ? ' onclick="'.$this->headeronclick.'" ' : '';

                echo '<th class="'.$this->header_class.' c'.$index.$this->column_class[$column].'" '.$this->make_styles_string($usestyles).' scope="col" '.$onclick.' >'.$this->headers[$index].'<div class="commands">'.$icon_hide.'</div></th>';
            }

        }
        echo '</tr>';
    }

    /**
     * Adds a custom suffix to the column headers
     */
    function get_header_suffix($column) {

    }

    /**
     * This function is not part of the public api.
     */
    function start_html(){
        global $OUTPUT;
        // Do we need to print initial bars?
        $this->print_initials_bar();

        if(in_array(ILP_TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        if (!empty($this->use_expandcollapse)) {
            echo $this->expand_collapse();
        }

        $this->wrap_html_start();
        // Start of main data table

        echo '<table'.$this->make_attributes_string($this->attributes).'>';

    }


    /**
     * Prints a single paging bar to provide access to other pages  (usually in a search)
     *
     * @param bool $nocurr do not display the current page as a link
     * @param bool $return whether to return an output string or echo now
     * @return bool or string
     */
    function print_paging_bar($nocurr=false, $return=false) {
        global $OUTPUT;

        $maxdisplay = 18;
        $output = '';

        $seperator = '&nbsp;&nbsp;|&nbsp;&nbsp;';
        $output .= '<table class="'.$this->attributes['class'].' removeborder">';
        $output .= '<tr>';
        $output .= '<td class="cell paging" colspan="'.count($this->columns).'">';

        // output the text describing where we are
        $strings = new stdClass;

        if($this->currpage == 0) {
            $strings->startpos = 1;
        } else {
            $strings->startpos = (($this->currpage)*$this->pagesize)  + 1;
        }
        $endpos = $strings->startpos + $this->pagesize - 1;
        $strings->endpos = ($endpos > $this->totalrows) ? $this->totalrows : $endpos;
        $strings->total = $this->totalrows;

        $output .= get_string('showingpages', 'block_ilp', $strings);

        $output .= $seperator;

        // output the paging links
        $output .= get_string('page') .':';

        if ($this->currpage > 0) {
            $pagenum = $this->currpage - 1;
            $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_PAGE].']'.'='.$pagenum;
            $output .= '&nbsp;(<a class="previous" href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.get_string('previous').'</a>)&nbsp;';
        }
        if ($this->pagesize > 0) {
            $lastpage = ceil($this->totalrows / $this->pagesize);
        } else {
            $lastpage = 1;
        }
        if ($this->currpage > 15) {
            $startpage = $this->currpage - 10;
            $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_PAGE].']' .'=0';
            $output .= '&nbsp;<a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">1</a>&nbsp;...';
        } else {
            $startpage = 0;
        }
        $currpage = $startpage;
        $displaycount = $displaypage = 0;
        while ($displaycount < $maxdisplay and $currpage < $lastpage) {
            $displaypage = $currpage+1;
            if ($this->currpage == $currpage && empty($nocurr)) {
                $output .= '&nbsp;&nbsp;'. $displaypage;
            } else {
                $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_PAGE].']'.'='.$currpage;
                $output .= '&nbsp;&nbsp;<a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.$displaypage.'</a>';
            }
            $displaycount++;
            $currpage++;
        }
        if ($currpage < $lastpage) {
            $lastpageactual = $lastpage - 1;
            $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_PAGE].']' .'='. $lastpageactual;
            $output .= '&nbsp;...<a href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.$lastpage.'</a>&nbsp;';
        }
        $pagenum = $this->currpage + 1;
        if ($pagenum != $displaypage) {
            $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_PAGE].']'.'='.$pagenum;
            $output .= '&nbsp;&nbsp;(<a class="next" href="'.$this->baseurl.$suffix.$this->fragment.'" onclick="return M.ilp_standard_functions.ajax_request(\''.$this->uniqueid.'_container\', \''.$this->ajaxurl.$suffix.'\');">'.get_string('next').'</a>)';
        }

        if ($this->displayperpage) {

            $output .= $seperator;

            $output .= "<form id=\"{$this->uniqueid}_perpage\" action=\"{$this->baseurl}#{$this->fragment}\" method=\"post\" >";

            $output .= "<p>".get_string('display', 'block_ilp').'&nbsp;';

            $output .= "<select name='{$this->uniqueid}[pagesize]' id='{$this->uniqueid}_perpage_select' >";

            // output the select element for the display options
            foreach(array(5, 10, 20, 50, 100) as $i) {
                $selected = ($this->pagesize == $i) ? "selected='selected'" : '';
                $output .= "<option value='{$i}' {$selected}>{$i}</option>";
            }

            $output .= "</select>";

            $output .= '&nbsp;'.get_string('perpage', 'block_ilp').'&nbsp;';
            $output .= "</p>";
            $output .= "<p><noscript><input type='submit' value='".get_string('apply', 'block_ilp')."' /></noscript></p>";
            $output .= "</form>";

        }

        $output .= "
        <script type='text/javascript'>
            //<![CDATA[
            // tell the form to autosubmit onchange
            Y.on(\"change\",
                function() {
                    //M.ilp_standard_functions.ajax_submit('{$this->uniqueid}_perpage', '{$this->uniqueid}_container', '{$this->ajaxurl}');
                    Y.one('#{$this->uniqueid}_perpage').submit();
                },
                \"#{$this->uniqueid}_perpage_select\" );
            //]]>
        </script>";

        $output .='</td>';
        $output .='</tr>';
        $output .='</table>';

        if ($return) {
            return $output;
        }

        echo $output;
        return true;
    }

    /**
     * Prints the horizontal paging bar
     *
     */
    function print_hozpaging_bar() {
        global $CFG;

        if($this->use_hozpages) {

            // make sure the current offset is within range
            $this->currhoz = ($this->currhoz < 0) ? 0 : $this->currhoz;
            $this->currhoz = ($this->currhoz > $this->totalcols) ? $this->totalcols : $this->currhoz;

            // calculate the colspan for the hoz pagination links
            $colspan = ($this->totalcols < $this->hozsize) ? $this->totalcols : $this->hozsize;
            ?>
            <tr>
                <th class="headerrow category catlevel1 cell removebottomborder" colspan="<?php echo count($this->columns) - $this->hozcols; ?>">&nbsp;</th>
                <th class="headerrow category catlevel1 cell" colspan="<?php echo $colspan; ?>">
                    <?php
                    $hozleftdouble = ($this->currhoz == 0) ? false : $this->currhoz - $this->hozsize;
                    $hozleftdouble = ($hozleftdouble < 0) ? 0 : $hozleftdouble;

                    if($hozleftdouble !== false) {
                        $title = ($hozleftdouble == 0) ? get_string('movetoend', 'block_ilp') : get_string('moveleft', 'block_ilp').' '.abs($this->currhoz - $hozleftdouble);
                        $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_HOZOFFSET].']='.$hozleftdouble; ?>
                        <a href="<?php echo $this->baseurl.$suffix.$this->fragment; ?>" onclick="return M.ilp_standard_functions.M.ilp_standard_functions.ajax_request('<?php echo $this->uniqueid; ?>_container', '<?php echo $this->ajaxurl.$suffix; ?>');">
                            <img alt="&lt;&lt;" title="<?php echo $title; ?>" src="<?php echo $CFG->wwwroot; ?>/blocks/ilp/pix/icons/moveleft2.gif" />
                        </a>
                        <?php
                    } else { ?>
                        <img alt="&lt;&lt;" src="<?php echo $CFG->wwwroot; ?>/blocks/ilp/pix/icons/moveleft2_gray.gif" />
                        <?php
                    }

                    $hozleftsingle = ($this->currhoz == 0) ? false : $this->currhoz - 1;

                    if($hozleftsingle !== false) {
                        $title = get_string('moveleftone', 'block_ilp');
                        $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_HOZOFFSET].']='.$hozleftsingle; ?>
                        <a href="<?php echo $this->baseurl.$suffix.$this->fragment; ?>" onclick="return M.ilp_standard_functions.ajax_request('<?php echo $this->uniqueid; ?>_container', '<?php echo $this->ajaxurl.$suffix; ?>');">
                            <img alt="&lt;" title="<?php echo $title; ?>" src="<?php echo $CFG->wwwroot; ?>/blocks/ilp/pix/icons/moveleft.gif" />
                        </a>
                        <?php
                    } else { ?>
                        <img alt="&lt;" src="<?php echo $CFG->wwwroot; ?>/blocks/ilp/pix/icons/moveleft_gray.gif" />
                        <?php
                    }

                    // output the text describing where we are
                    $strings = new stdClass();
                    $strings->startpos = empty($this->totalcols) ? 0 : $this->currhoz+1;
                    $endpos = $this->currhoz+$this->hozsize;
                    $strings->endpos = ($endpos > $this->totalcols) ? $this->totalcols : $endpos;
                    $strings->totalcols = $this->totalcols;

                    echo get_string($this->hoz_string, 'block_ilp', $strings);

                    $hozrightsingle = ($this->currhoz+$this->hozsize >= $this->totalcols) ? false : $this->currhoz + 1;

                    if($hozrightsingle !== false) {
                        $title = get_string('moverightone', 'block_ilp');
                        $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_HOZOFFSET].']='.$hozrightsingle; ?>
                        <a href="<?php echo $this->baseurl.$suffix.$this->fragment; ?>" onclick="return M.ilp_standard_functions.ajax_request('<?php echo $this->uniqueid; ?>_container', '<?php echo $this->ajaxurl.$suffix; ?>');">
                            <img alt="&gt;" title="<?php echo $title; ?>" src="<?php echo $CFG->wwwroot; ?>/blocks/ilp/pix/icons/moveright.gif" />
                        </a>
                        <?php
                    } else { ?>
                        <img alt="&gt;" src="<?php echo $CFG->wwwroot; ?>/blocks/ilp/pix/icons/moveright_gray.gif" />
                        <?php
                    }

                    $hozrightdouble = ($this->currhoz >= $this->totalcols-$this->hozsize) ? false : $this->currhoz + $this->hozsize;
                    $hozrightdouble = ($hozrightdouble > $this->totalcols-$this->hozsize) ? $this->totalcols-$this->hozsize : $hozrightdouble;

                    if($hozrightdouble !== false) {
                        $title = ($hozrightdouble+$this->hozsize == $this->totalcols) ? get_string('movetoend', 'block_ilp') : get_string('moveright', 'block_ilp').' '.abs($this->currhoz - $hozrightdouble);
                        $suffix = $this->uniqueid.'['.$this->request[ILP_TABLE_VAR_HOZOFFSET].']='.$hozrightdouble; ?>
                        <a href="<?php echo $this->baseurl.$suffix.$this->fragment; ?>" onclick="return M.ilp_standard_functions.ajax_request('<?php echo $this->uniqueid; ?>_container', '<?php echo $this->ajaxurl.$suffix; ?>');">
                            <img alt="&gt;&gt;" title="<?php echo $title; ?>" src="<?php echo $CFG->wwwroot; ?>/blocks/ilp/pix/icons/moveright2.gif" />
                        </a>
                        <?php
                    } else { ?>
                        <img alt="&gt;&gt;" src="<?php echo $CFG->wwwroot; ?>/blocks/ilp/pix/icons/moveright2_gray.gif" />
                        <?php
                    } ?>
                </th>
            </tr>
            <?php
        }
    }

    /**
     * Sets the totalrows variable to the given integer, and the use_pages
     * variable to true.
     *
     * @param int $total
     * @return void
     */
    function totalrows($total) {
        $this->totalrows = $total;
        $this->use_pages = true;
    }

    /**
     * Sets the totalcols variable to the given integer, and the use_hozpages
     * variable to true.
     *
     * @param int $total
     * @return void
     */
    function totalcols($total) {
        $this->totalcols = $total;
        $this->use_hozpages = true;
    }

    /**
     * Applies horizontal pagination to the columns in the table
     *
     * @param array $collist The list of columns to paginate
     * @return array The sublist to show on the current page
     */
    function limitcols($collist, $hozsize = null) {
        if(!empty($hozsize)) {
            $this->hozsize = $hozsize;
        }

        // set the totalcols and turn on hozpagination
        $this->totalcols(count($collist));

        // are there too many units to show on the page
        if($this->totalcols > $this->hozsize) {
            // return the requested set of columns, whilst preserving keys
            $collist = array_slice($collist, $this->currhoz, $this->hozsize, true);
        }

        $this->hozcols = count($collist);

        return $collist;
    }

    function expand_collapse()  {

        $expandurl     =  $this->baseurl.$this->uniqueid.'['.$this->request[ILP_TABLE_VAR_SHOW].']=expandall'.$this->fragment;;
        $collapseurl     =  $this->baseurl.$this->uniqueid.'['.$this->request[ILP_TABLE_VAR_HIDE].']=collapseall'.$this->fragment;;


        echo "<div>";
        echo "<span><a href='{$expandurl}'  > ".get_string('expandall','block_ilp')." &nbsp;</a></span>/";
        echo "<span><a href='{$collapseurl}' >&nbsp; ".get_string('collapseall','block_ilp')."</a></span>";
        echo "</div>";
    }

    /**
     * passes the columns that will be allow to expanded and collapsed
     * with the expand collapse links
     *
     * @param $columns
     *
     */
    function define_expandcollapse($columns)    {
        $this->expandcollapse_cols  =   $columns;
    }

}