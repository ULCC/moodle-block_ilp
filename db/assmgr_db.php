<?php
/**
 * Databse classes for the Assessment Manager block module.
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 2.0
 */
require_once($CFG->dirroot.'/blocks/assmgr/classes/assmgr_logging.class.php');

/**
 * Main database class, with functions to encode and decode stuff to and from the DB
 *
 * Acts as a wrapper for {@link assmgr_db_functions} with a magic method to intercept
 * function calls.
 */
class assmgr_db {

    /**
     * Constructor to instantiate the db connection.
     *
     * @return void
     */
    function __construct() {
        global $CFG;

        // include the static constants
        require_once($CFG->dirroot.'/blocks/assmgr/lib.php');

        // instantiate the Assessment manager database
        $this->dbc = new assmgr_db_functions();
    }

    /**
     * A PHP magic method that intercepts all calls to the database class and
     * encodes all the data being input.
     *
     * @param string $method The name of the method being called.
     * @param array $params The array of parameters passed to the method.
     * @return mixed The result of the query.
     */
    function __call($method, $params) {
        // sanatise everything coming into the database here
        $params = $this->encode($params);

        // hand control to the assmgr_db_functions()
        return call_user_func_array(array($this->dbc, $method), $params);
    }

    /**
     * Encodes mixed params before they are sent to the database.
     *
     * @param mixed $data The unencoded object/array/string/etc
     * @return mixed The encoded version
     */
    static function encode(&$data) {
        if(is_object($data) || is_array($data)) {
            // skip the flexible_table
            if(!is_a($data, 'flexible_table')) {
                foreach($data as $index => &$datum) {
                    $datum = assmgr_db::encode($datum);
                }
            }
            return $data;
        } else {
            // decode any special characters prevent malicious code slipping through
            $data = assmgr_db::decode_htmlchars($data, ENT_QUOTES);

            // purify all data (e.g. validate html, remove js and other bad stuff)
            $data = purify_html($data);

            // encode the purified string
            $data = trim(preg_replace('/\\\/', '&#92;', htmlentities($data, ENT_QUOTES, 'utf-8', false)));

            // convert the empty string into null as such values break nullable FK fields
            return ($data == '') ? null : $data;
        }
    }

    /**
     * Decodes mixed params.
     *
     * @param mixed The encoded object/array/string/etc
     * @return mixed The decoded version
     */
    static function decode(&$data) {
        if(is_object($data) || is_array($data)) {
            foreach($data as $index => &$datum) {
                $datum = assmgr_db::decode($datum);
            }
            return $data;
        } else {
            return html_entity_decode($data, ENT_QUOTES, 'utf-8');
        }
    }

    /**
     * Decodes mixed params.
     *
     * @param mixed The encoded object/array/string/etc
     * @return mixed The decoded version
     */
    static function decode_htmlchars(&$data) {
        if(is_object($data) || is_array($data)) {
            foreach($data as $index => &$datum) {
                $datum = assmgr_db::decode_htmlchars($datum);
            }
            return $data;
        } else {
            return str_replace(array('&quot;', '&#039;', '&lt;', '&gt;'), array('"', "'", '<', '>'), $data);
        }
    }
}

/**
 * Databse class holding functions to actually perform the queries.
 *
 * This extends the logging class which intercepts all insert, update and delete
 * actions that are executed on the database and makes a record of what data was
 * changed. Instantiated as $dbc in the {@link assmgr_db} class.
 */
class assmgr_db_functions extends assmgr_logging {

    /**
     * The Moodle 2 database, or the emulator.
     *
     * @var ADODB connection
     */
    var $dbc;

    /**
     * Constructor for the assmgr_db_functions class
     *
     * @return void
     */
    function __construct() {
        global $CFG, $DB;

        // if this is empty then we're using Moodle 1.9.x, so we need the 2.0 emulator
        if(empty($DB)) {
            require_once($CFG->dirroot.'/blocks/assmgr/db/moodle2_emulator.php');
            $this->dbc = new moodle2_db_emulator();
        } else {
            $this->dbc = $DB;
        }

        // include the static constants
        require_once($CFG->dirroot.'/blocks/assmgr/constants.php');
    }

    /**
     * Creates a folder record.
     *
     * @param string $name The name of the folder
     * @param int $candidate_id the user id of the candidate
     * @param int $parent_id The id of the parent folder (optional, defaults to null)
     * @return mixed The id of the new folder or false if it fails
     */
    function create_folder($name, $candidate_id, $parent_id = null) {

        // make a new folder object
        $folder = new object();
        $folder->name = $name;
        $folder->folder_id = $parent_id;
        $folder->candidate_id = $candidate_id;

        return $this->insert_record('block_assmgr_folder', $folder);
    }

    /**
     * Updates a folder record.
     *
     * @param int $id The id of the folder
     * @param string $name The name of the folder
     * @param int $parent_id The id of the parent folder (optional, defaults to null)
     * @return bool The success of the action
     */
    function set_folder($id, $name, $parent_id = null) {
        global $USER;

        $folder = new object();
        $folder->id = $id;
        $folder->name = $name;
        $folder->folder_id = $parent_id;

        return $this->update_record('block_assmgr_folder', $folder);
    }

    /**
     * Gets the folder given by id
     *
     * @param int $folder_id The id of the folder
     * @return array folder object
     */
    function get_folder($folder_id) {

        return $this->dbc->get_record('block_assmgr_folder', array('id' => $folder_id));
    }

    /**
     * Gets all the folders for the given user id, and returns them with their
     * full path names.
     *
     * @param int $candidate_id The id of the user
     * @param object $parent (optional, defaults to null) only find folders that are children of this folder
     * @return array folder objects
     */
    function get_folders($candidate_id, $parent = null) {
        $parent->id = empty($parent->id) ? null : $parent->id;
        $parent->name = empty($parent->name) ? null : $parent->name;

        // get the child folders of the current parent
        $children = $this->get_child_folders($candidate_id, $parent->id);

        $merged = array();
        if(!empty($children)) {
            foreach($children as $id => $folder) {
                // add the parent name to the front of the child name
                $folder->name = $parent->name.'/'.$folder->name;
                // push the folder into the result array
                $merged[$folder->id] = $folder;
                // recursively get that folder's children
                $grandchildren = $this->get_folders($candidate_id, $folder);
                // merge the grandchildren with children
                if(!empty($grandchildren)) {
                    foreach($grandchildren as $grandchild) {
                        $merged[$grandchild->id] = $grandchild;
                    }
                }
            }
        }

        return $merged;
    }

    /**
     * Gets the child folders of the given parent id.
     *
     * @param int $candidate_id The id of the user
     * @param int $parent_id The id of the parent folder
     * @return array results objects (folders)
     */
    function get_child_folders($candidate_id, $parent_id) {

        $parent_id = is_null($parent_id) ? 'NULL' : $parent_id;

        $sql = "SELECT id, name, folder_id
                  FROM {block_assmgr_folder}
                 WHERE candidate_id = {$candidate_id}
                   AND folder_id <=> {$parent_id}
              ORDER BY name ASC";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Checks whether the folder with the given outcomes exist.
     *
     * @param int $id The id of the folder
     * @param int $candidate_id The id of the user
     * @return bool stating whether the folder exists
     */
    function folder_exists($id, $candidate_id) {

        return $this->dbc->record_exists('block_assmgr_folder', array('id' => $id, 'candidate_id' => $candidate_id));
    }

    /**
     * Checks whether a folder with the given name already exists
     *
     * @param string $name the name that you want to check exists
     * @param int    $id id of the folder used in the case of edit where you will
     *               want to exclude the folder
     * @return bool stating whether the folder name exists
     */
     function folder_name_exists($folder_name, $user_id, $parent_id, $folder_id=null) {

         $id_query = (!empty($folder_id)) ? "AND id != '{$folder_id}'" : "" ;

         $sql = "SELECT *
                   FROM {block_assmgr_folder}
                  WHERE name = '{$folder_name}'
                    AND candidate_id = {$user_id}
                    AND folder_id <=> {$parent_id}
                        {$id_query}";

        return $this->dbc->record_exists_sql($sql);
     }

    /**
     * Returns the folder with the given name that belongs to the given candidate
     *
     * @TODO this should take the course_id as a param not the name to enforce
     * consistency accross the site. There should also be a create_candidate_course_folder().
     *
     * @param string $course_name the name of the folder that you want to retrieve
     * @param int    $canidate_id id of the candidate who the folder belongs to
     * @return array containing folder object that matches criteria
     */
    function get_default_folder($course_id, $candidate_id) {

        $name = $this->dbc->get_field('course', 'shortname', array('id' => $course_id));

        $params = array('name' => $name, 'candidate_id' => $candidate_id);

        if(!$this->dbc->record_exists('block_assmgr_folder', $params)) {
            $this->create_folder($name, $candidate_id);
        }

        return $this->dbc->get_record('block_assmgr_folder', $params);
    }

    /**
     * Returns the folder with the given name that belongs to the given candidate
     *
     * @param string $course_name the name of the folder that you want to retrieve
     * @param int    $canidate_id id of the candidate who the folder belongs to
     * @param int    $parent_id id of the the parent folder where the module folder can be found
     * @return array containing folder object that matches criteria
     */
    function get_candidate_course_module_folder($module_name,$candidate_id,$parent_id) {
        return $this->dbc->get_record('block_assmgr_folder', array('name'=>$module_name, 'candidate_id'=>$candidate_id,'folder_id'=>$parent_id));
    }

    /**
     * Delete all folders that match the given id.
     *
     * @param int $id folder id
     * @return boolean true if delete succeded
     */
    function delete_folder($id) {

        $this->delete_records('block_assmgr_folder', array('id' => $id));
    }

    /**
     * Get the grade outcome record.
     *
     * @param int $outcome_id The id of the grade outcome
     * @return array The outcome object
     */
    function get_outcome($outcome_id) {

        return $this->dbc->get_record('grade_outcomes', array('id' => $outcome_id));
    }

    /**
     * Get the grade outcomes for the given course, and return them sorted in the
     * order set in the gradebook.
     *
     * @param int $course_id
     * @return array outcomes objects that match given course
     */
    function get_outcomes($course_id, $outcome_id = null) {

        $outcome_condition = empty($outcome_id) ? '' : " AND o.id = {$outcome_id} ";

        $sql = "SELECT o.*, gi.categoryid, gi.gradepass
                  FROM {grade_outcomes_courses} AS oc,
                       {grade_outcomes} AS o

             LEFT JOIN {grade_items} AS gi
                    ON gi.outcomeid = o.id
                   AND gi.courseid = {$course_id}
                   AND gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                   AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                   AND gi.iteminstance IS NULL

             LEFT JOIN {grade_items} AS gic
                    ON gic.iteminstance = gi.categoryid
                   AND gic.itemtype = 'category'
                   AND gic.itemmodule IS NULL

                 WHERE oc.courseid = {$course_id}
                   AND oc.outcomeid = o.id
                       {$outcome_condition}

              ORDER BY gic.sortorder, gi.sortorder, oc.id";

       if(empty($outcome_id)) {
            return $this->dbc->get_records_sql($sql);
       } else {
           return $this->dbc->get_record_sql($sql);
       }
    }

    /**
     * Gets the count of grade outcomes attached to a course.
     *
     * @param array $courselist
     * @return array count of courses that matches given outcomes
     */
    function count_outcomes($courselist) {

        if(empty($courselist)) {
            return false;
        }

        $courselist = implode(',', $courselist);

        $sql = "SELECT COUNT(id)
                  FROM {grade_outcomes_courses}
                 WHERE courseid IN ({$courselist})";

        return $this->dbc->get_field_sql($sql);
    }

    function get_verification_outcomes($category_id,$course_id,$assessor_id) {
        //TODO work out sql for if an assessor is chosen

        $select = "SELECT  o.* ";

        $from   = "FROM   {course} AS cou,
                          {course_categories} AS cat,
                          {grade_outcomes} AS o ";

        $where  = "WHERE  o.courseid = cou.id
                   AND    cou.category = cat.id ";

        if (!empty($category_id)) {
            $where .= "AND cat.id = $category_id ";
        }

        if (!empty($course_id)) {
            $where .= "AND cou.id = $course_id ";
        }

        return $this->dbc->get_records_sql($select.$from.$where);

    }

    /**
     * Get the grade outcomes for the given portfolio (either achieved, unachieved or both depending
     * on the current filter).
     *
     * @param object $portfolio The portfolio record
     * @param int $flextable Table that we are showing, with the filter to apply to the outcomes
     * @return array outcome objects that matches given portfolio and filters
     */
    function get_outcomes_set($portfolio, $flextable) {

        $outcomes_set = $flextable->get_filter('show_outcomes_set');

        if($outcomes_set == OUTCOMES_SHOW_ALL) {
            // fetch all the outcomes
            $outcomes = $this->get_outcomes($portfolio->course_id);
        } else {

            // WHAT select changes from "show achivied" and "show unachivied"
            // so here the switch
            if ($outcomes_set == OUTCOMES_SHOW_UNATTEMPTED) {
                $select = "o.id ";
            } else {
                $select = "o.*, gi.gradepass, gi.categoryid";
            }

            // "show achivied" query
            $sql = "SELECT $select
                     FROM {block_assmgr_portfolio} AS portf,
                          {grade_grades} AS gg,
                          {grade_outcomes} AS o ";

            // only add the sorting stuff if it's not a subquery
            if ($outcomes_set != OUTCOMES_SHOW_UNATTEMPTED) {
                 $sql .= ", {grade_items} AS gi,
                            {grade_items} AS gic ";
            }

            $sql .= " WHERE portf.id = {$portfolio->id}
                       AND gi.courseid = portf.course_id
                       AND gi.id = gg.itemid
                       AND gg.userid = portf.candidate_id
                       AND gg.finalgrade IS NOT NULL ";

            // remainder of the sorting stuff
            if ($outcomes_set != OUTCOMES_SHOW_UNATTEMPTED) {

                $grade_condition = ($outcomes_set == OUTCOMES_SHOW_COMPLETE) ? ' AND gg.finalgrade >= gi.gradepass ' : ' AND gg.finalgrade < gi.gradepass ';

                $sql .= "AND gi.outcomeid = o.id
                         AND gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                         AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                         AND gi.iteminstance IS NULL
                             {$grade_condition}
                         AND gic.iteminstance = gi.categoryid
                         AND gic.itemtype = 'category'
                         AND (gic.itemmodule IS NULL
                           OR gic.itemmodule = '')

                    ORDER BY gic.sortorder, gi.sortorder ";
             }

            // "show unachievied" query (basically the "all query" - "show achievied query")
            if($outcomes_set == OUTCOMES_SHOW_UNATTEMPTED) {
                // NB the previous value of $sql is inserted into this as a sub-query
                $sql = "SELECT o.*,
                               gi.gradepass,
                               gi.categoryid
                          FROM {grade_outcomes} AS o,
                               {grade_items} AS gi,
                               {grade_items} AS gic,
                               {grade_outcomes_courses} AS oc,
                               {block_assmgr_portfolio} AS portf
                         WHERE portf.id = {$portfolio->id}
                           AND portf.course_id = oc.courseid
                           AND oc.outcomeid = o.id
                           AND o.id NOT IN ({$sql})

                           AND gi.outcomeid = o.id
                           AND gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                           AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                           AND gi.iteminstance IS NULL

                           AND gic.iteminstance = gi.categoryid
                           AND gic.itemtype = 'category'
                           AND (gic.itemmodule IS NULL
                           OR gic.itemmodule = '')

                      ORDER BY gic.sortorder, gi.sortorder";
            }

            $outcomes = $this->dbc->get_records_sql($sql);
        }

        return $outcomes;
    }

    /**
     * Gets all the outcomes attached to a course that have a submission grade
     * for a given candidate.
     *
     * @param int $candidate_id the user id of the candidate
     * @param int $course_id the id of the course
     * @return array outcome ids
     */
    function get_outcomes_with_submission_grades($candidate_id, $course_id) {

        $sql = "SELECT DISTINCT grade.outcome_id
                  FROM {block_assmgr_portfolio} AS port,
                       {block_assmgr_submission} AS sub,
                       {block_assmgr_grade} AS grade
                 WHERE port.course_id = {$course_id}
                   AND port.candidate_id = {$candidate_id}
                   AND port.id = sub.portfolio_id
                   AND sub.id = grade.submission_id
                   AND grade.grade IS NOT NULL";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Gets all the outcomes attached to a course that have a submission claim
     * for a given candidate.
     *
     * @param int $candidate_id the user id of the candidate
     * @param int $course_id the id of the course
     * @return array outcome ids
     */
    function get_outcomes_with_submission_claims($candidate_id, $course_id) {

        $sql = "SELECT DISTINCT clm.outcome_id
                  FROM {block_assmgr_portfolio} AS port,
                       {block_assmgr_submission} AS sub,
                       {block_assmgr_claim} AS clm
                 WHERE port.course_id = {$course_id}
                   AND port.candidate_id = {$candidate_id}
                   AND port.id = sub.portfolio_id
                   AND sub.id = clm.submission_id";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Get the evidence type using its name.
     *
     * @param int $evidence_type_id the id of the evidence type if you just want one (optional, defaults to null)
     * @return array containing evidence type(s) as objects
     */
    function get_evidence_type_by_name($evidence_type_name) {
        return $this->dbc->get_record('block_assmgr_evidence_type', array('name'=>$evidence_type_name));
    }

    /**
     * Returns the first date of evidences for a candidate
     *
     * @param int $candidate_id
     * @param int $course_id
     * @return stdClass The date
     */
    function get_evidence_first_date($candidate_id, $course_id = null) {

        $select = "SELECT evid.timemodified ";

        $from = "FROM {block_assmgr_evidence} AS evid,
                      {block_assmgr_resource} AS res,
                      {block_assmgr_resource_type} AS resty,
                      {block_assmgr_confirmation} AS conf,
                      {block_assmgr_submission} AS sub,
                      {block_assmgr_portfolio} AS portf,
                      {course} AS course,
                      {user} AS cand ";

        $where = "WHERE portf.course_id = course.id
                    AND portf.id = sub.portfolio_id
                    AND sub.evidence_id = evid.id
                    AND evid.candidate_id = cand.id
                    AND evid.candidate_id = {$candidate_id}
                    AND evid.id = res.evidence_id
                    AND res.resource_type_id = resty.id
                    AND conf.evidence_id = evid.id ";

        if($course_id != null) {
            $where .= "AND course.id = {$course_id} ";
        }

        $sort = "ORDER BY evid.timemodified ASC ";

        return $this->dbc->get_record_sql(
            $select.$from.$where.$sort,
            null,
            0,
            1
        );
    }

    /**
     * Get the evidence types for this moodle.
     *
     * @param int $evidence_type_id the id of the evidence type if you just want one (optional, defaults to null)
     * @return array containing evidence type(s) as objects
     */
    function get_evidence_types($evidence_type_id = NULL) {

        $where = (!empty($evidence_type_id)) ? "WHERE id = {$evidence_type_id}" : '';

        $sql = "SELECT *
                  FROM {block_assmgr_evidence_type}
                {$where}";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Get the evidence and the related resource that matches the given id.
     *
     * @param int $candidate_id The id of the candidate
     * @param int $folder_id The id of the folder
     * @return array The evidence objects in this folder
     */
    function get_evidence_by_folder($candidate_id, $folder_id) {

        $folder_id = is_null($folder_id) ? 'NULL' : $folder_id;

        $sql = "SELECT evid.*,
                       res.id AS resource_id,
                       resty.name AS resource_type,
                       resty.id AS resource_type_id
                  FROM {block_assmgr_evidence} AS evid,
                       {block_assmgr_resource} AS res,
                       {block_assmgr_resource_type} AS resty
                 WHERE evid.folder_id <=> {$folder_id}
                   AND evid.candidate_id = {$candidate_id}
                   AND evid.id = res.evidence_id
                   AND res.resource_type_id = resty.id";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Gets all the evidence belonging to a given candidate, optionally filtered
     * by creator.
     *
     * @param int $candidate_id
     * @param int $creator_id (optional)
     * @return array Result objects
     */
    function get_evidence_by_candidate($candidate_id, $creator_id = null) {

        $creator_condition = '';

        if(!empty($creator_id)) {
            $creator_condition = " AND creator_id = {$creator_id}";
        }

        $sql = "SELECT evid.*,
                       res.id AS resource_id,
                       resty.name AS resource_type
                  FROM {block_assmgr_evidence} AS evid,
                       {block_assmgr_resource} AS res,
                       {block_assmgr_resource_type} AS resty
                 WHERE evid.candidate_id = {$candidate_id}
                   AND evid.id = res.evidence_id
                   AND res.resource_type_id = resty.id
                       $creator_condition";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Get the evidence and the related resource that matches the given id.
     *
     * @param int $evidence_id The id of the evidence
     * @return array Result object
     */
    function get_evidence_resource($evidence_id) {

        $sql = "SELECT evid.*,
                       res.*,
                       evid.id AS id,
                       evid.timemodified,
                       evid.timecreated,
                       res.id AS resource_id,
                       resty.name AS resource_type
                  FROM {block_assmgr_evidence} AS evid,
                       {block_assmgr_resource} AS res,
                       {block_assmgr_resource_type} AS resty
                 WHERE evid.id = {$evidence_id}
                   AND evid.id = res.evidence_id
                   AND res.resource_type_id = resty.id";

        return $this->dbc->get_record_sql($sql);
    }

    /**
     * Returns all evidence of a particular type.
     *
     * @param int $candidate_id The id of the user
     * @return bool
     */
    function get_evidence_resource_by_type($resource_type, $candidate_id, $course_id) {

        $sql = "SELECT evid.*,
                       res.*,
                       evid.id AS id,
                       evid.timemodified,
                       evid.timecreated,
                       res.id AS resource_id,
                       resty.name AS resource_type
                FROM {block_assmgr_evidence} AS evid,
                     {block_assmgr_submission} AS sub,
                     {block_assmgr_portfolio} AS port,
                     {block_assmgr_resource} AS res,
                     {block_assmgr_resource_type} AS resty
                WHERE resty.name = '{$resource_type}'
                  AND evid.id = res.evidence_id
                  AND evid.candidate_id = {$candidate_id}
                  AND res.resource_type_id = resty.id
                  AND evid.id = sub.evidence_id
                  AND sub.portfolio_id = port.id
                  AND port.course_id = {$course_id}
                GROUP BY evid.id";

        return $this->dbc->get_records_sql($sql);
    }



    /**
     * Returns whether the user with the given id has any evidence.
     *
     * @param int $candidate_id The id of the user
     * @return bool
     */
    function user_evidence_exits($candidate_id) {

        return $this->dbc->record_exists('block_assmgr_evidence', array('candidate_id' => $candidate_id));
    }

    /**
     * Checks whether a portfolio exists,
     *
     * @param int $id The id of the portfolio
     * @return bool
     */
    function portfolio_exists($portfolio_id) {
        return $this->dbc->record_exists('block_assmgr_portfolio', array('id' => $portfolio_id));
    }

    /**
     * Creates a portfolio record.
     *
     * @param int $candidate_id The id of the user
     * @param int $course_id The id of the course
     * @return int|bool The id of the portfolio created or false if unsuccessful
     */
    function create_portfolio($candidate_id, $course_id) {

        $portfolio = new object();
        $portfolio->candidate_id = $candidate_id;
        $portfolio->course_id = $course_id;

        return $this->insert_record('block_assmgr_portfolio', $portfolio);
    }

    /**
     * Flags a portfolio as needing assessment.
     *
     * @param int $portfolio_id The id of the portfolio
     * @param bool $needsassess The optional value for needsassess
     * @return int Needsassess
     */
    function set_portfolio_needsassess($portfolio_id, $needsassess = null) {

        // get the portfolio
        $port = $this->dbc->get_record('block_assmgr_portfolio', array('id' => $portfolio_id));

        if(is_null($needsassess)) {
            // check if there any unassessed submissions
            $needsassess = $this->dbc->record_exists_sql(
                "SELECT *
                   FROM {block_assmgr_submission} AS sub
              LEFT JOIN {block_assmgr_grade} AS grade
                     ON (sub.id = grade.submission_id AND (grade.grade IS NOT NULL OR grade.feedback IS NOT NULL))
                  WHERE sub.portfolio_id = {$portfolio_id}
                    AND sub.hidden = 0
                    AND grade.id IS NULL"
            );
        }

        $port->needsassess = (int) $needsassess;

        $this->update_record('block_assmgr_portfolio', $port);

        return $port->needsassess;
    }

    /**
     * Creates the grade item records in the gradebook so the assessor can
     * save marks against the portfolio (i.e. course).
     *
     * This should take the form of one grade item for the course, with a
     * grade type of text grade (i.e. no score, just text feedback), and
     * one additional grade item for each of the outcomes attached to this
     * course.
     *
     * @param int $course_id The id of the course
     * @return void
     */
    function create_portfolio_grade_items($course_id) {
        global $CFG;

        // include the grade library
        require_once($CFG->libdir.'/gradelib.php');

        // requesting the course grade category item will create the category if
        // it does not exist, it will also create the course grade item
        $course_category = grade_category::fetch_course_category($course_id);

        $course_params = array(
            'courseid'     => $course_id,
            'itemtype'     => 'course',
            'iteminstance' => $course_category->id
        );

        // fetch the course grade item
        $grade_item = grade_item::fetch_all($course_params);

        // we still need to check if the course grade item exists, as fetching the
        // grade category will only create the item if the category is missing
        if (empty($grade_item)) {
            // create a new grade item for the course
            $grade_item = new grade_item($course_params, false);
            $grade_item->gradetype = GRADE_TYPE_SCALE;
            $grade_item->insert('system');
        }

        $outcome_params = array(
            'courseid' => $course_id,
            'parent'   => $course_category->id,
            'fullname' => get_string('assessmentcriteria', 'block_assmgr')
        );

        // now check that the outcome grade category exists
        $outcome_category = grade_category::fetch($outcome_params);

        // does it exist already
        if (empty($outcome_category)) {
            // create the grade category to store the portfolio grade items
            $outcome_category = new grade_category($outcome_params, false);
            $outcome_category->insert();
        }

        // get all the outcomes attached to the course
        $outcomes = $this->get_outcomes($course_id);

        // get any existing outcome items
        $outcome_items = grade_item::fetch_all(
            array(
                'courseid'     => $course_id,
                'itemtype'     => GRADE_ASSMGR_ITEMTYPE,
                'itemmodule'   => GRADE_ASSMGR_ITEMMODULE,
                'iteminstance' => null
            )
        );

        // we only care about outcomes that don't already have a grade item
        if(!empty($outcome_items)) {

            foreach($outcome_items as $item) {
                unset($outcomes[$item->outcomeid]);
            }
        }

        // TODO what if there are $outcome_items for outcomes no longer attached
        // to the course???

        // create grade items for any remaining outcomes
        if(!empty($outcomes)) {

            foreach($outcomes as $outcome) {
                $outcome_item = new grade_item();
                $outcome_item->courseid     = $course_id;
                $outcome_item->categoryid   = $outcome_category->id;
                $outcome_item->itemtype     = GRADE_ASSMGR_ITEMTYPE;
                $outcome_item->itemmodule   = GRADE_ASSMGR_ITEMMODULE;
                $outcome_item->iteminstance = null;
                $outcome_item->itemnumber   = $outcome->id;
                $outcome_item->itemname     = $outcome->fullname;
                $outcome_item->outcomeid    = $outcome->id;
                $outcome_item->gradetype    = GRADE_TYPE_SCALE;
                $outcome_item->scaleid      = $outcome->scaleid;
                $outcome_item->insert();
            }
        }

        // if we've added any $outcome_items then change the sortorder on the
        // $outcome_category so it sits after all the outcome items
        if(!empty($outcome_item)) {
            // TODO this may not be necessary as there is a setting in 2.0 to choose
            // if the category should display at the start or at the end
            $outcome_category->move_after_sortorder($outcome_item->get_sortorder());
        }

        // TODO we need to add in the marking rules for the outcomes, in the form
        // of a calculation stored in the the outcomes grade category
    }

    /**
     * Creates the grade_grade records which store the assessor's grades for the
     * portfolio.
     *
     * TODO - this never returns anything because grade_update_outcomes() doesn't
     *
     * @param int $course_id The id of the course
     * @param int $candidate_id The id of the user being graded
     * @param array $grades An associative array of itemnumber => grade
     * @return boolean Success of the operation
     */
    function set_portfolio_outcomes($course_id, $candidate_id, $grades) {
        global $CFG;

        //include the grade library
        require_once($CFG->libdir.'/gradelib.php');

        $old_grades = grade_get_grades(
            $course_id,
            GRADE_ASSMGR_ITEMTYPE,
            GRADE_ASSMGR_ITEMMODULE,
            null,
            $candidate_id
        );

        // insert the portfolio outcome grades
        $success = grade_update_outcomes(
            GRADE_ASSMGR_SOURCE,
            $course_id,
            GRADE_ASSMGR_ITEMTYPE,
            GRADE_ASSMGR_ITEMMODULE,
            null,
            $candidate_id,
            $grades
        );

        $new_grades = grade_get_grades(
            $course_id,
            GRADE_ASSMGR_ITEMTYPE,
            GRADE_ASSMGR_ITEMMODULE,
            null,
            $candidate_id
        );

        foreach($grades as $outcome_id => $grade) {
             //build object so the portfolio grade maybe logged

             $oldgrades = $this->flatten_grade_object($old_grades,$outcome_id);
             $newgrades = $this->flatten_grade_object($new_grades,$outcome_id);

             $oldgrades->candidate_id = $candidate_id;
             $oldgrades->course_id = $course_id;
             $oldgrades->outcome_id = $outcome_id;
             $oldgrades->rawgrade = $grade;

             $newgrades->candidate_id = $candidate_id;
             $newgrades->course_id = $course_id;
             $newgrades->outcome_id = $outcome_id;
             $newgrades->rawgrade = $grade;

             $this->add_to_audit('portfolio_outcome_grade',LOG_ASSESSMENT,$newgrades,$oldgrades);

        }

        return $success;
    }

    /**
     * Sets the comment field in the grade_grade record for the portfolio.
     *
     * @param int    $course_id The id of the course
     * @param int    $candidate_id The id of the candidate
     * @param int    $scale_item_id The id of the scale item
     * @param string $comments The assessors comments on the portfolio
     * @return void
     */
    function set_portfolio_grade($course_id, $candidate_id, $scale_item_id, $comments) {
        global $CFG, $USER;

        $oldgrade = $this->get_portfolio_grade($course_id, $candidate_id);
        $oldgrade->course_id = $course_id;
        $oldgrade->candidate_id = $candidate_id;

        //include the grade library
        require_once($CFG->libdir.'/gradelib.php');

        // get the course grade item
        $gi = grade_item::fetch(
            array(
                'itemtype'      => 'course',
                'itemmodule'    => null,
                'courseid'      => $course_id
            )
        );

        $gi->update_final_grade($candidate_id, $scale_item_id, 'gradebook', $comments, FORMAT_HTML);

        $newgrade = $this->get_portfolio_grade($course_id, $candidate_id);
        $newgrade->course_id = $course_id;
        $newgrade->candidate_id = $candidate_id;

        $this->add_to_audit('portfolio_grade', LOG_ASSESSMENT, $newgrade,$oldgrade);

        return true;
    }

    /**
     * Updates a portfolio record.
     *
     * @param object $portfolio The portfolio object representing the DB row to update
     * @return mixed the success of the action (int) or false if it failed
     */
    function set_portfolio($portfolio) {

        return $this->update_record('block_assmgr_portfolio', $portfolio);
    }

    /**
     * Get portfolio that matches the given id.
     *
     * @param int $id The id of the portfolio
     * @return array Result object
     */
    function get_portfolio_by_id($portfolio_id) {

        return $this->dbc->get_record('block_assmgr_portfolio', array('id' => $portfolio_id));
    }

    /**
     * Get portfolio for the given user and course.
     *
     * @param int $candidate_id
     * @param int $course_id
     * @return array Result object
     */
    function get_portfolio($candidate_id, $course_id) {

        return $this->dbc->get_record('block_assmgr_portfolio', array('candidate_id' => $candidate_id, 'course_id' => $course_id));
    }

    /**
     * Returns all the portfolios.
     *
     * @return array Result object
     */
    function get_portfolios() {

        return $this->dbc->get_records('block_assmgr_portfolio');
    }

    /**
     * Create evidence submission record.
     *
     * @param object containing evidence submission record
     * @return int|bool containing the id of the evidence submission or false if it fails
     */
    function create_submission($submission) {

        return $this->insert_record('block_assmgr_submission', $submission);
    }

    /**
     * Update evidence submission record.
     *
     * @param object containing evidence submission record
     * @return mixed record id or false depending on outcome of update
     */
    function set_submission($submission) {
        return $this->update_record('block_assmgr_submission', $submission);
    }

    /**
     * Get all submissions based on a portfolio id
     *
     * @param int $portfolio_id The portfolio id
     * @return array The submissions objects
     */
    function get_submissions($portfolio_id) {

        return $this->dbc->get_records('block_assmgr_submission', array('portfolio_id' => $portfolio_id));
    }

    /**
     * Get all submissions linked to a piece of evidence id
     *
     * @param int $evidence_id The portfolio id
     * @return array The submissions objects
     */
    function get_submissions_by_evidence($evidence_id) {

        return $this->dbc->get_records('block_assmgr_submission', array('evidence_id' => $evidence_id));
    }

    /**
     * Gets the courses where a piece of evidence has been submitted
     */
    function get_submission_courses_by_evidence($evidence_id) {

        $sql = "SELECT c.id, c.fullname, c.shortname
                  FROM {course} c
            INNER JOIN {block_assmgr_portfolio} p
                    ON c.id = p.course_id
            INNER JOIN {block_assmgr_submission} s
                    ON p.id = s.portfolio_id
                 WHERE s.evidence_id = {$evidence_id}";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Get the evidence types, based on submission_id.
     *
     * @param int $submission_id id of the evidence submission
     * @return array containing all evidence types as objects
     */
    function get_submission_evidence_types($submission_id) {

        $sql = "SELECT subevty.evidence_type_id, subevty.*
                  FROM {block_assmgr_sub_evid_type} AS subevty
                 WHERE subevty.submission_id = {$submission_id}";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Create an evidence submission type record.
     *
     * @param int $submission_id
     * @param array $evidence_types Array of evidence_type_ids
     * @return
     */
    function set_submission_evidence_types($submission_id, $types) {
        global $USER;

        // get all the existing evidence types
        $existing_types = $this->get_submission_evidence_types($submission_id);
        $existing_types = empty($existing_types) ? array() : $existing_types;

        // delete any removed types
        $del_types = array_diff_key($existing_types, $types);
        foreach($del_types as $evidence_type_id => $type) {
            $this->delete_records(
                'block_assmgr_sub_evid_type',
                array(
                    'submission_id'     => $submission_id,
                    'evidence_type_id'  => $evidence_type_id
                )
            );
        }

        // create any new types
        $new_types = array_diff_key($types, $existing_types);
        foreach($new_types as $evidence_type_id => $type) {
            $type = new object();
            $type->submission_id = $submission_id;
            $type->evidence_type_id = $evidence_type_id;
            $type->creator_id = $USER->id;

            $this->insert_record('block_assmgr_sub_evid_type', $type);
        }

        // update any old types
        $old_types = array_intersect_key($existing_types, $types);
        foreach($old_types as $evidence_type_id => $type) {
            if($type->creator_id != $USER->id) {
                $type->creator_id = $USER->id;
                $this->update_record('block_assmgr_sub_evid_type', $type);
            }
        }
    }

    /**
     * Gets the user record for the candidate who's submission this is.
     *
     * @param int $submission_id The id of the submission
     * @return array The user record object for the candidate
     */
    function get_submission_candidate($submission_id) {

        $sql = "SELECT usr.*
                  FROM {user} AS usr,
                       {block_assmgr_portfolio} AS portf,
                       {block_assmgr_submission} AS sub
                 WHERE sub.id = {$submission_id}
                   AND sub.portfolio_id = portf.id
                   AND portf.candidate_id = usr.id";

        return $this->dbc->get_record_sql($sql);
    }

    /**
     * Gets the portfolio record for the submission.
     *
     * @param int $submission_id The id of the submission
     * @return array The portfolio record object for the submission
     */
    function get_submission_portfolio($submission_id) {

        $sql = "SELECT port.*
                  FROM {block_assmgr_portfolio} AS port,
                       {block_assmgr_submission} AS sub
                 WHERE sub.id = {$submission_id}
                   AND sub.portfolio_id = port.id";

        return $this->dbc->get_record_sql($sql);
    }

    /**
     * Get evidence record based on id given
     *
     * @param int $evidence_id The evidence id
     * @return array containing evidence object that matches given outcomes
     */
    function get_evidence($evidence_id) {
        return $this->dbc->get_record('block_assmgr_evidence', array('id' => $evidence_id));
    }

    /**
     * Gets the submission record given by id
     *
     * @param int $submission_id The id of the submission
     * @return array The results objects
     */
    function get_submission_by_id($submission_id) {

        return $this->dbc->get_record('block_assmgr_submission', array('id' => $submission_id));
    }

    /**
     * Gets the submission record given by id
     *
     * @param int $submission_id The id of the submission
     * @return array The results objects
     */
    function get_evidence_submission($evidence_id,$course_id) {

        $sql = "SELECT      *
                FROM        {block_assmgr_submission} as sub,
                            {block_assmgr_evidence} as evid,
                            {block_assmgr_portfolio} as port
                WHERE       evid.id = {$evidence_id}
                AND         port.course_id = {$course_id}
                AND         evid.id = sub.evidence_id
                AND         sub.portfolio_id = port.id";

        return  $this->dbc->get_record_sql($sql);
    }


    /**
     * Gets the evidence submission record.
     *
     * @param int $evidence_id The id of the evidence
     * @param int $portfolio_id The id of the portfolio
     * @return array The results objects
     */
    function get_submission($evidence_id, $portfolio_id) {

        $conditions = array(
            'evidence_id' => $evidence_id,
            'portfolio_id' => $portfolio_id
        );

        return $this->dbc->get_record('block_assmgr_submission', $conditions);
    }

    /**
     * Checks whether an evidence instance has a submission yet
     *
     * @param int $evidence_id
     * @param int $portfolio_id (optional, defaults to null)
     * @return bool true if it has one
     */
    function has_submission($evidence_id, $portfolio_id = null) {
        if(empty($portfolio_id)) {
            return $this->dbc->record_exists('block_assmgr_submission', array('evidence_id' => $evidence_id));
        } else {
            $sub = $this->get_submission($evidence_id, $portfolio_id);
            return !empty($sub);
        }
    }

    /**
     * Checks whether an evidence instance has a submission
     * in a particular course
     * @param int $evidence_id
     * @param int $course_id
     * @return bool true if it has one
     */
    function has_submission_in_course($evidence_id, $course_id) {

        $sql = "SELECT  sub.id AS submission_id,
                        sub.*,
                        port.*
                FROM    {block_assmgr_submission} AS sub,
                        {block_assmgr_portfolio} as port
                WHERE   sub.portfolio_id = port.id
                AND     sub.evidence_id = {$evidence_id}
                AND     port.course_id = {$course_id}";


            return $this->dbc->get_record_sql($sql);
    }

    /**
     * Get a scale record object for the specified scale
     *
     * @param int $scale_id The id of scale record
     * @param int $gradepass The optional grade pass threshold
     * @return object the scale object
     */
    function get_scale($scale_id, $gradepass = null) {
        global $CFG;

        // include the custom scale class
        require_once($CFG->dirroot.'/blocks/assmgr/classes/assmgr_scale.class.php');

        return new assmgr_scale(array('id' => $scale_id, 'gradepass' => $gradepass));
    }

    /**
     * Deletes the assessment event with the given id
     *
     * @param int $id the assessment id
     * @return bool The result of the delete operation
     */
    function delete_assessment_event($id) {
          return $this->delete_records('event', array('id' => $id));
    }

    /**
     * returns true or false depending on whether the user with the given
     * user_id has any calendar events. A group id can be added so that events
     * for that group will be included too.
     *
     * @param int $user_id
     * @param int $course_id
     * @param int $group_id (optional)
     * @return bool
     */
     function future_assessment_event_exists($user_id, $course_id, $group_id) {

         $group = (!empty($group_id)) ? "OR groupid = {$group_id}" : '' ;

         $sql = "  SELECT *
                     FROM {event} as events,
                          {block_assmgr_calendar_event} as assevent
                    WHERE (courseid = {$course_id}
                       OR userid = {$user_id}
                          {$group})
                      AND events.id = assevent.event_id
                      AND eventtype = ".CANDIDATE_EVENT;

         return $this->dbc->record_exists_sql($sql);
     }

    /**
     * Gets the calendar event of the event that matches the given event id.
     *
     * @param int $event_id
     * @return array The result object
     */
    function get_future_assessment_event_by_id($event_id) {
        return $this->dbc->get_record('event', array('id' => $event_id));
    }

    /**
     * Updates the date field of all event records that have a repeatid that
     * matches the given id.
     *
     * @param int $event_date timestamp of the date the event will take place on
     * @param int $event_id id of the event that is being updated
     * @return bool true if it works
     */
    function update_event_date($event_date, $event_id) {
        return $this->dbc->set_field('event', 'timestart', $event_date, array('repeatid' => $event_id));
    }

    /**
     * Updates the description field of all event records that have a repeatid that
     * matches the given id.
     *
     * @param int $event_comment comment given by the assessor
     * @param int $event_id id of the event that is being updated
     * @return bool The result of the set field operation
     */
    function update_event_comment($event_comment, $event_id) {
        return $this->dbc->set_field('event', 'description', $event_comment, array('repeatid' => $event_id));
    }

    /**
     * Updates a specific event record that corresponds to the supplied event object.
     *
     * @param int $event the event object representing a whole or partial DB row with the id
     * @return bool The result of the set field operation
     */
     function update_candidate_event($event) {
           return $this->dbc->update_record('event', $event);
     }

    /**
     * Gets the calendar event of the event that matches the given credentials.
     *
     * @param int $portfolio_id The id of the portfolio
     * @param int $user_id The user who the event is saved for
     * @param int $type The event type ASSESOR_EVENT or CANDIDATE_EVENT
     * @param int $event_id (optional) The event id used to match event ids in the uuid field
     * @return array The result object
     */
     function get_calendar_event($event_id) {

        $sql = "SELECT *, event.id as id, ce.id as calendar_event_id
                  FROM {event} AS event,
                       {block_assmgr_calendar_event} as ce
                 WHERE event.id = ce.event_id
                 AND   event.id = {$event_id}
              ORDER BY timestart DESC";

        return $this->dbc->get_record_sql($sql);
    }

    /**
     * Gets the user given by id.
     *
     * @param int $candidate_id The id of the user
     * @return array containing user details objects
     */
    function get_user($candidate_id) {

        return $this->dbc->get_record('user', array('id' => $candidate_id));
    }

    /**
     * Gets the record of the group with the given id.
     *
     * @param int $group_id The id of the group
     * @return array containing group details objects
     */
    function get_group($group_id) {
        return $this->dbc->get_record('groups', array('id' => $group_id));
    }

    /**
     * Gets the users in the group id given.
     *
     * @param int $group_id The id of the group
     * @return array containing user details objects
     */
    function get_group_users($group_id) {

        $sql = "SELECT *
                  FROM {groups_members} AS gm,
                       {user} AS u,
                       {groups} AS g
                 WHERE gm.userid = u.id
                   AND g.id = gm.groupid
                   AND gm.groupid = {$group_id}";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Gets a DB record of a single resource types.
     *
     * @param int $id the id of the resource type
     * @return array Result objects
     */
    function get_resource_type($id) {

        return $this->dbc->get_record('block_assmgr_resource_type', array('id' => $id));
    }

    /**
     * Gets a DB record of a single resource types.
     * using the resource type name
     *
     * @param int $id the id of the resource type
     * @return array Result objects
     */
    function get_resource_type_by_name($resource_name) {
        return $this->dbc->get_record('block_assmgr_resource_type', array('name' => $resource_name));
    }


    /**
     * Gets the full list of evidence resource types.
     *
     * @return array Result objects
     */
    function get_resource_types() {
        // check for the presence of a table to determine which query to run
        $tableexists = $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_assmgr_resource_type}'");

        // return resource types or false
        return (!empty($tableexists)) ? $this->dbc->get_records('block_assmgr_resource_type', array()) : false;

    }

    /**
     * Creates a new resource type record.
     *
     * @param string $name the name of the new resource type
     * @return mixed the id of the inserted record or false
     */
    function create_resource_type($name) {
        $type = new object();
        $type->name = $name;

        return $this->insert_record('block_assmgr_resource_type', $type);
    }

    /**
     * Gets the assessor's final grade for the portfolio.
     *
     * @param int $course_id the id of the course
     * @param int $course_id the id of the candidate
     * @return array containing the course grade
     */
    function get_portfolio_grade($course_id, $candidate_id) {
        global $CFG;

        //include the grade library
        require_once($CFG->libdir.'/gradelib.php');

        // get the course grade item
        $gi = grade_item::fetch(
            array(
                'itemtype'      => 'course',
                'itemmodule'    => null,
                'courseid'      => $course_id
            )
        );

        // initialise the grade in case there is no db record
        $gg = null;

        if(!empty($gi)) {
            // get the course grade
            $grade = grade_get_grades(
                $course_id,
                'course',
                null,
                $gi->iteminstance,
                $candidate_id
            );

            if(!empty($grade->items)) {
                // there should only be one grade item, and one grade
                $gg = reset(reset($grade->items)->grades);
            }
        }

        return $gg;
    }

    /**
     * Gets the assessor's grades for all the outcomes in the portfolio.
     *
     * @param int $portfolio_id The id of the portfolio
     * @param int $outcome_id The (optional) outcome in case you are only interested in one
     * @return array The outcome grade objects
     */
    function get_portfolio_outcome_grades($portfolio_id, $outcome_id=NULL) {

        $outcome_criteria = ($outcome_id == NULL) ? 'IS NOT NULL': " = {$outcome_id}";

        $sql = "SELECT gi.outcomeid AS outcome_id,
                       gi.scaleid AS scale_id,
                       gi.gradepass,
                       gg.finalgrade AS scale_item
                  FROM {block_assmgr_portfolio} AS portf,
                       {grade_items} AS gi,
                       {grade_grades} AS gg
                 WHERE portf.id = {$portfolio_id}
                   AND gi.courseid = portf.course_id
                   AND gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                   AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                   AND gi.iteminstance IS NULL
                   AND gi.outcomeid {$outcome_criteria}
                   AND gi.id = gg.itemid
                   AND gg.userid = portf.candidate_id
                   AND gg.finalgrade IS NOT NULL ";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Gets all the assessor's grades for a given submission.
     *
     * @param int $submission_id The id of the submission
     * @param int $outcome_id The (optional) outcome in case you are only interested in the grade for this
     * @return array The result objects
     */
    function get_submission_grades($submission_id, $outcome_id=NULL) {

        $outcome_criteria = ($outcome_id == NULL) ? 'IS NOT NULL': " = {$outcome_id}";

        $sql = "SELECT grade.outcome_id, grade.*
                  FROM {block_assmgr_grade} AS grade
                 WHERE grade.submission_id = {$submission_id}
                   AND grade.outcome_id {$outcome_criteria}";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Takes a given moodle grade data object and flattens it
     * so only the grade object is returned
     *
     * @param object $gradesobject the moodle grades object
     * @param int $outcome_id The id of the outcome that the grade belongs to
     * @return mixed flattened grade object or false
     */
    function flatten_grade_object($gradesobject, $outcome_id) {
        //note the index of the outcome outcome is the same as the outcome_id

        foreach ($gradesobject->outcomes as $index => $outcome) {
            if ($index == $outcome_id) {
                 return current($outcome->grades);
            }
        }

        return false;
    }

    /**
     * Saves the assessor's grades for a submission.
     *
     * @param int $submission_id The id of the submission
     * @param array $grades An associative array of outcome_id => grade
     * @return boolean Success of the operation
     */
    function set_submission_grades($submission_id, $grades, $creator_id = null) {
        global $USER;

        $grades_added = false;

        // resolve the creator id
        $creator_id = empty($creator_id) ? $USER->id : $creator_id;

        // get all the existing grades
        $existing_grades = $this->get_submission_grades($submission_id);
        $existing_grades = empty($existing_grades) ? array() : $existing_grades;

        // delete any removed grades
        $del_grades = array_diff_key($existing_grades, $grades);
        foreach($del_grades as $outcome_id => $grade) {
            $this->delete_records(
                'block_assmgr_grade',
                array(
                    'submission_id' => $submission_id,
                    'outcome_id'    => $outcome_id
                )
            );
        }

        // create any new grades
        $new_grades = array_diff_key($grades, $existing_grades);
        foreach($new_grades as $outcome_id => $new_grade) {
            $grade = new object();
            $grade->submission_id = $submission_id;
            $grade->outcome_id = $outcome_id;
            $grade->creator_id = $creator_id;
            $grade->grade = $new_grade;

            $this->insert_record('block_assmgr_grade', $grade);

            $grades_added = true;
        }

        // update any old grades
        $old_grades = array_intersect_key($existing_grades, $grades);
        foreach($old_grades as $outcome_id => $grade) {
            if($grade->grade != $grades[$outcome_id]) {
                $grade->grade = $grades[$outcome_id];
                $grade->creator_id = $creator_id;
                $this->update_record('block_assmgr_grade', $grade);

                $grades_added = true;
            }
        }


        if($grades_added) {

            // get the portfolio for the submission
            $port = $this->get_submission_portfolio($submission_id);

            // get all the portfolio outcome grades
            $portgrades = $this->get_portfolio_outcome_grades($port->id);

            $newportgrades = array();

            // set all the corresponding portfolio outcome grades to "incomplete" if they are null
            foreach($grades as $outcome_id => $grade) {
                // TODO fetch the gradepass for this outcome and if it is greater than 1 then set it to one
                $newportgrades[$outcome_id] = (empty($portgrades[$outcome_id])) ?  '1' : $portgrades[$outcome_id]->scale_item;
            }

            $this->set_portfolio_outcomes($port->course_id, $port->candidate_id, $newportgrades);
        }
    }

    /**
     * Sets the comment field in the grade_grade record for the submission.
     *
     * @param int $submission_id The id of the submission
     * @param string $comment The assessor's comment on the submission
     * @return boolean Success of the operation
     */
    function set_submission_comment($submission_id, $comment, $creator_id = null) {
        global $USER;

        // resolve the creator id
        $creator_id = empty($creator_id) ? $USER->id : $creator_id;

        $old_comment = $this->get_submission_comment($submission_id);

        if(empty($comment)) {
            // delete the comment record
            if(!empty($old_comment)) {
                $this->delete_records('block_assmgr_grade', array('id' => $old_comment->id));
            }
        } elseif(!empty($old_comment)) {
            if($old_comment->feedback != $comment) {
                // update the comment record
                $new_comment = clone $old_comment;
                $new_comment->creator_id = $creator_id;
                $new_comment->feedback = $comment;
                $this->update_record('block_assmgr_grade', $new_comment);
            }
        } else {
            // create a new comment record
            $new_comment = new object();
            $new_comment->submission_id = $submission_id;
            $new_comment->creator_id = $creator_id;
            $new_comment->feedback = $comment;
            $this->insert_record('block_assmgr_grade', $new_comment);
        }
    }

    /**
     * Gets the assessor's comment for a particular submission.
     *
     * @param int $submission_id The id of the submission
     * @return array of comments objects
     */
    function get_submission_comment($submission_id) {

        $sql = "SELECT grade.*, creator.firstname, creator.lastname
                  FROM {block_assmgr_grade} AS grade,
                       {user} AS creator
                 WHERE grade.submission_id = {$submission_id}
                   AND grade.feedback IS NOT NULL
                   AND grade.creator_id = creator.id";

        return $this->dbc->get_record_sql($sql);
    }

    /**
     * Gets all the unique submission comments that have been made for a particular submission
     *
     * @TODO this query cannot distinguish between the same comment made twice
     * @param int $portfolio_id The id of the portfolio
     * @return array of comments objects
     */
    function get_portfolio_comments($portfolio_id) {

        $sql = "SELECT gg.feedback, gg.timemodified, usr.firstname, usr.lastname, usr.id as userid
                  FROM {block_assmgr_portfolio} AS portf,
                       {grade_items} AS gi,
                       {grade_grades_history} AS gg,
                       {user} AS usr
                 WHERE portf.id = {$portfolio_id}
                   AND gi.courseid = portf.course_id
                   AND gi.itemtype = 'course'
                   AND gi.itemmodule IS NULL
                   AND gi.outcomeid IS NULL
                   AND gg.itemid = gi.id
                   AND gg.userid = portf.candidate_id
                   AND gg.usermodified = usr.id
                   AND gg.feedback IS NOT NULL
                 GROUP BY gg.feedback
                 ORDER BY gg.timemodified ASC";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Saves the candidate's claims for a submission.
     *
     * @param int $submission_id The id of the submission
     * @param array $grades An associative array of outcome_id => grade
     * @return boolean Success of the operation
     */
    function set_submission_claims($submission_id, $claims) {
        global $USER;

        // get all the existing claims
        $existing_claims = $this->get_submission_claims($submission_id);
        $existing_claims = empty($existing_claims) ? array() : $existing_claims;

        // delete any removed claims
        $del_claims = array_diff_key($existing_claims, $claims);
        foreach($del_claims as $outcome_id => $claim) {
            $this->delete_records(
                'block_assmgr_claim',
                array(
                    'submission_id' => $submission_id,
                    'outcome_id'    => $outcome_id
                )
            );
        }

        // create any new claims
        $new_claims = array_diff_key($claims, $existing_claims);
        foreach($new_claims as $outcome_id => $new_claim) {
            $claim = new object();
            $claim->submission_id = $submission_id;
            $claim->outcome_id = $outcome_id;

            $this->insert_record('block_assmgr_claim', $claim);
        }
    }

    /**
     * Gets all the candidate's claims for a given submission.
     *
     * @param int $submission_id The id of the submission
     * @return array of objects containing claim matching the outcomes
     */
    function get_submission_claims($submission_id) {

        $sql = "SELECT claim.outcome_id, claim.*
                FROM {block_assmgr_claim} AS claim
                WHERE claim.submission_id = {$submission_id}";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Create a feedback file for a submission
     *
     * @param int $submission_id The submission ID
     * @param string $name The file name
     * @return bool true if insert succeded
     */
    function create_submission_feedback($submission_id, $name) {
        global $USER;

        // make a new feedback
        $feedback = new object();
        $feedback->submission_id = $submission_id;
        $feedback->filename = $name;
        $feedback->timemodified = time();
        $feedback->timecreated = time();
        $feedback->creator_id = $USER->id;

        return $this->insert_record('block_assmgr_feedback', $feedback);
    }

    /**
     * Get the submission feedbacks for the given submission.
     *
     * @param int $submission_id The id of the submission
     * @return array of objects with the feedbacks
     */
    function get_submission_feedbacks($submission_id) {
        $sql = "SELECT *
                  FROM {block_assmgr_feedback}
                 WHERE submission_id = {$submission_id}
              ORDER BY timemodified ASC";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Checks whether the user has any evidence across all thier portfolios.
     *
     * @param int $candidate_id The id of the user
     * @param int $creator id (optional) add this if interested only in evidence from one creator
     * @return bool true if portfolio exists, false if not
     */
    function evidence_exists($candidate_id, $creator_id = null) {

        $conditions = array('candidate_id' => $candidate_id);

        if(!empty($creator_id)) {
            $conditions['creator_id'] = $creator_id;
        }

        return $this->dbc->record_exists('block_assmgr_evidence', $conditions);
    }

    /**
     * Checks whether the given evidence has been submitted.
     *
     * @param int $evidence_id The id of the evidence
     * @return bool True if the evidence has been submitted
     */
    function submission_exists($evidence_id) {

        return $this->dbc->record_exists('block_assmgr_submission', array('evidence_id' => $evidence_id));
    }

    /**
     * Gets course ids of all courses that the assessment manager is installed
     * in
     *
     * @return array with an object containing the block ids
     */
    function get_block_course_ids($course_id=null) {
        if (!empty($course_id) && is_array($course_id)) {
           $course_condidtion = ' AND bi.pageid IN ('.implode(',',$course_id).')';
        } else {
            $course_condidtion = (!empty($course_id)) ? " AND bi.pageid = {$course_id}" : '';
        }

        $sql = "SELECT  pageid
                 FROM   {block_instance} as bi,
                        {block} as b
                 WHERE  b.id = bi.blockid
                 AND    b.name = '".BLOCK_NAME."'
                 {$course_condidtion}";

        return $this->dbc->get_records_sql($sql);
    }


    /**
     * Gets the information for a Moodle course module.
     *
     * @param int $course_module_id The id of the course module
     * @return array with an object containing the course module information
     */
    function get_course_module($course_module_id) {

        $sql = "SELECT *
                  FROM {course_modules} AS cm,
                       {modules} AS modu
                 WHERE cm.module = modu.id
                   AND cm.id = {$course_module_id}";

        return $this->dbc->get_record_sql($sql);
    }

    /**
     * Gets all the Moodle modules attached to this course.
     *
     * @param int $course_id The id of the course
     * @return array The moodle module objects
     */
    function get_course_modules($course_id) {

        return $this->dbc->get_records('course_modules', array('course' => $course_id));
    }

    /**
     * Get the Moodle module instance attached to this course.
     *
     * @param int $module_id The id of the module
     * @param int $instance_id The id of the activity instance
     * @return array The moodle module objects
     */
    function get_course_modules_by_instance($module_id,$instance_id) {

        return $this->dbc->get_record('course_modules', array('module' => $module_id,'instance'=>$instance_id));
    }

    /**
     * Gets all the Moodle modules .
     *
     * @return array with the moodle module object
     */
    function get_modules() {
        return $this->dbc->get_records('modules');
    }

    /**
     * Gets all the Moodle modules given by id.
     *
     * @param int $module_id The id of the module
     * @return array with the moodle module object
     */
    function get_module($module_id) {

        return $this->dbc->get_record('modules', array('id' => $module_id));
    }

    /**
     * Gets all the Moodle modules given by id.
     *
     * @param int $module_id The id of the module
     * @return array with the moodle module object
     */
    function get_module_by_name($module_name) {

        return $this->dbc->get_record('modules', array('name' => $module_name));
    }

    /**
     * Gets the instance of a Moodle module that is being imported as evidence.
     *
     * @param string $module The name of the module
     * @param int $instance_id The id of the instance of the module
     * @return array with the moodle module object
     */
    function get_module_instance($module, $instance_id) {

        return $this->dbc->get_record($module, array('id' => $instance_id));
    }

    /**
     * Moves all evidence in one folder into either the root folder or the
     * folder specified.
     *
     * @param int $oldfolder_id The id of the source folder
     * @param int $newfolder_id (optional) The id of the destination folder
     * @return bool true if delete succeded
     */
    function move_evidence($oldfolder_id, $newfolder_id = null) {

        return $this->dbc->set_field('block_assmgr_evidence', 'folder_id', $newfolder_id, array('folder_id' => $oldfolder_id));
    }

    /**
     * Moves all subfolders in one folder into either the root folder or the
     * folder specified.
     *
     * @param int $oldfolder_id The id of the source folder
     * @param int $newfolder_id (optional) The id of the destination folder
     * @return bool true if delete succeded
     */
    function move_subfolders($oldfolder_id, $newfolder_id = null) {

        return $this->dbc->set_field('block_assmgr_folder', 'folder_id', $newfolder_id, array('folder_id' => $oldfolder_id));
    }

    /**
     * Deletes claim records for evidence submissions.
     *
     * @param int $submission_id The id of the evidence submission
     * @return bool true if any claim was deleted
     */
    function delete_submission_claims($submission_id) {

        return $this->delete_records('block_assmgr_claim', array('submission_id' => $submission_id));
    }

    /**
     * Deletes evidence submission record.
     *
     * @param int $submission_id The id of the evidence
     * @return bool stated whether the evidence submission record was was deleted
     */
    function delete_submission($submission_id) {

        return $this->delete_records('block_assmgr_submission', array('id' => $submission_id));
    }

    /**
     * Deletes the evidence given by id.
     *
     * @param int $evidence_id The id of the evidence to delete.
     * @param bool $recursive (optional, defaults to true) Whether to delete the attached records as well
     * @return bool states whether any evidence submission record was deleted
     */
    function delete_evidence($evidence_id, $recursive = true) {

        if($recursive) {
            // delete the resource
            $this->delete_records('block_assmgr_resource', array('evidence_id'=> $evidence_id));
        }

        return $this->delete_records('block_assmgr_evidence', array('id'=> $evidence_id));
    }

    /**
     * Retrieve the information for the course with the given outcomes
     *
     * @param int $course_id The id of the course
     * @return array containing a course object that matches the outcomes
     */
    function get_course($course_id) {

        return $this->dbc->get_record('course', array('id' => $course_id));
    }

    /**
     * Retrieve all courses
     *
     * @return array containing courses
     */
    function get_courses() {

        return $this->dbc->get_records('course');
    }

    /**
     * Retrieve all candidates with portfolios in the course with the id specified
     * @param int $course_id The id of the course
     *
     * @return array containing candidates
     */
     function get_course_portfolios($course_id) {
        return $this->dbc->get_records('block_assmgr_portfolio',array('course_id' => $course_id));
     }

    /**
     * Gets the list of courses for a given qualification.
     *
     * @param int $category_id the id of the qualification's course category
     * @return array the course objects
     */
    function get_courses_by_category($category_id) {

        return $this->dbc->get_records('course', array('category' => $category_id));
    }

    /**
     * Gets the list of courses that a given candidate is
     * enrolled in.
     *
     * @param int $candidate_id the user id of the candidate
     * @param int $category_id (optional) specified if you are interested only in courses from one category
     * @return array of course objects
     */
    function get_enrolled_courses($candidate_id, $category_id = null) {
        global $USER;

        $accessinfo = get_user_access_sitewide($candidate_id);

        // find all the courses thats the candidate is currently enrolled in
        $courses = get_user_courses_bycap(
            $candidate_id,
            "block/assmgr:creddelevidenceforself",
            $accessinfo,
            true,
            'c.sortorder ASC',
            array('fullname','category')
        );

        $enrolled = array();

        if(!empty($courses)) {
            foreach ($courses as $course) {
                // if a category is defined then check this course is in that category
                if(empty($category_id) || $category_id == $course->category) {
                    $enrolled[$course->id] = $course;
                }
            }
        }

        return $enrolled;
    }

    /**
     * Get course catgory information.
     *
     * @param int $category_id
     * @return array containing course category object
     */
    function get_category($category_id) {

        return $this->dbc->get_record('course_categories', array('id' => $category_id));
    }

    /**
     * Gets the qualification (course category) for a given course.
     *
     * @param int $course_id
     * @return array containing the course category object
     */
    function get_category_by_course($course_id) {

        // find qualification
        $sql = "SELECT ccat.*
                  FROM {course} as c,
                       {course_categories} as ccat
                 WHERE ccat.id = c.category
                   AND c.id = {$course_id}";

        return $this->dbc->get_record_sql($sql);
    }

    /**
     * Returns an array containing all work that is yet to be confirmed in the
     * given courses
     *
     * @param array $courselist (optional) the ids of the courses to check
     * @param array $userlist (optional) the ids of the users to check
     * @param object $flextable the table that will display the data and which is supplying sort params
     * @param int $group (optional) the id of the group to use
     * @return array unconfirmed work objects
     */
    function get_unconfirmed_evidence_matrix($courselist, $userlist, $flextable, $group) {
        global $USER;

        $conditions = array();

        if(!empty($courselist)) {
            $conditions[] = 'portf.course_id IN ('.implode(',', $courselist).')';
        }

        if(!empty($userlist)) {
            $conditions[] = 'portf.candidate_id IN ('.implode(',', $userlist).')';
        }

        // no contexts where the current user can confirm
        if(empty($conditions)) {
            return false;
        }

        $select = "SELECT sub.id AS submission_id,
                          evid.id AS evidence_id,
                          cand.id AS candidate_id,
                          cand.lastname,
                          cand.firstname,
                          evid.name AS evidence_name,
                          res.resource_type_id,
                          resty.name AS resource_type,
                          evid.timemodified,
                          course.id AS course_id,
                          course.shortname AS course_name ";

        $from = "FROM {block_assmgr_evidence} AS evid,
                      {block_assmgr_resource} AS res,
                      {block_assmgr_resource_type} AS resty,
                      {block_assmgr_confirmation} AS conf,
                      {block_assmgr_submission} AS sub,
                      {block_assmgr_portfolio} AS portf,
                      {course} AS course,
                      {user} AS cand ";

        $where = "WHERE (".implode(' OR ', $conditions).")
                    AND portf.course_id = course.id
                    AND portf.id = sub.portfolio_id
                    AND sub.evidence_id = evid.id
                    AND evid.candidate_id = cand.id
                    AND evid.candidate_id != {$USER->id}
                    AND evid.id = res.evidence_id
                    AND res.resource_type_id = resty.id
                    AND conf.evidence_id = evid.id
                    AND conf.status = ".CONFIRMATION_PENDING;

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        if(!empty($group)) {
            $from  .= "LEFT JOIN {groups_members} AS group_mem
                              ON (cand.id = group_mem.userid) ";

            $where .= " AND group_mem.groupid = {$group} ";
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     * Checks whether a piece of evidence has been confirmed
     *
     * @param int $evidence_id
     * @param int $status (optional) add this if only interested in a particular confirmation status
     * @return bool
     */
    function has_confirmation($evidence_id, $status = null) {

        $conditions = array('evidence_id' => $evidence_id);

        if(!is_null($status)) {
            $conditions['status'] = $status;
        }

        return $this->dbc->record_exists('block_assmgr_confirmation', $conditions);
    }

    /**
     * Gets the confirmation record for a particular piece of evidence
     *
     * @param int $evidence_id
     * @return array result object from the confirmation table
     */
    function get_confirmation($evidence_id) {

        return $this->dbc->get_record('block_assmgr_confirmation', array('evidence_id' => $evidence_id));
    }

    /**
     * Deletes the confirmation record for a particular piece of evidence
     *
     * @param int $evidence_id
     * @param int $status (optional) add this if you wish to delete the record only if it has a certain status
     * @return bool was the delete successful?
     */
    function delete_confirmation($evidence_id, $status = null) {

        $conditions = array('evidence_id' => $evidence_id);

        if(!is_null($status)) {
            $conditions['status'] = $status;
        }

        return $this->delete_records('block_assmgr_confirmation', $conditions);
    }

    /**
     * Creates or updates the confirmation status for a particular piece of evidence
     *
     * @param int $evidence_id
     * @param int $status the status code (see constants.php)
     * @param string $feedback (optional)
     * @return mixed the id of the inserted record (or false), or true/false for creation
     */
    function set_confirmation($evidence_id, $status, $feedback = null) {
        global $USER;

        // get any existing confirmation record
        $conf = $this->get_confirmation($evidence_id);

        if(empty($conf)) {
            // create a new record
            $conf = new object();
            $conf->evidence_id = $evidence_id;
        }

        // set the fields
        $conf->creator_id = $USER->id;
        $conf->status = $status;
        $conf->feedback = $feedback;

        if(empty($conf->id)) {
            return $this->insert_record('block_assmgr_confirmation', $conf);
        } else {
            return $this->update_record('block_assmgr_confirmation', $conf);
        }
    }

    /**
     * Create an evidence record.
     *
     * @param object $evidence contain evidence record
     * @return mixed the id of the evidence submission record or false
     */
    function create_evidence($evidence) {

        return $this->insert_record('block_assmgr_evidence', $evidence);
    }

    /**
     * Update the data in an evidence record.
     *
     * @param object $evidence contain evidence record
     * @return bool true false depending on result of update
     */
    function set_evidence($evidence) {

        return $this->update_record('block_assmgr_evidence', $evidence);
    }

    /**
     * Gets an evidence resource based on an evidence id
     *
     * @param int $evidence_id
     * @return array containing the resource object
     */
    function get_resource($evidence_id) {

        return $this->dbc->get_record('block_assmgr_resource', array('evidence_id' => $evidence_id));
    }

    /**
     * Gets an evidence resource based on id
     *
     * @param int $evidence_id
     * @return array containing the resource object
     */
    function get_resource_by_id($id) {

        return $this->dbc->get_record('block_assmgr_resource', array('id' => $id));
    }

    /**
     * Create an evidence resource record.
     *
     * @param object $resource contains evidence record
     * @return mixed the id of the evidence record or false
     */
    function create_resource($resource) {

        return $this->insert_record('block_assmgr_resource', $resource);
    }

    /**
     * Update the data in an evidence resource record.
     *
     * @param object $resource contain evidence record
     * @return bool true false depending on result of update
     */
    function set_resource($resource) {

        return $this->update_record('block_assmgr_resource', $resource);
    }

    /**
     * Count evidence submission records.
     *
     * @param int $candidate_id The id of the candidate
     * @param int $course_id The id of the course
     * @return int The count of submissions
     */
    function count_submissions($candidate_id, $course_id) {

        $sql = "SELECT COUNT(*)
                FROM {block_assmgr_portfolio} AS portf,
                     {block_assmgr_submission} AS sub
                WHERE portf.id = sub.portfolio_id
                  AND portf.candidate_id = {$candidate_id}
                  AND portf.course_id = {$course_id}";

        return $this->dbc->count_records_sql($sql);
    }

    /**
     * Returns the users that did actions that have taken place to a particular
     * portfolio
     *
     * @param int $candidate_id The id of the candidate
     * @param int $course_id
     * @return array the matrix as an array of row objects
     */
    function get_log_users($candidate_id, $course_id) {

        $select = "SELECT creator_id,
                          firstname,
                          lastname ";

        $from =  "FROM   {block_assmgr_log} as logtable,
                          {user} as creator  ";

        $where = "WHERE logtable.candidate_id = {$candidate_id}
                  AND   logtable.course_id = {$course_id}
                  AND   logtable.creator_id = creator.id ";

        $sort = "ORDER BY firstname, lastname";

        $sql = $select.$from.$where.$sort;

        return $this->dbc->get_records_sql($sql);

    }

    /**
     * Returns the users that did actions that have taken place to a particular
     * portfolio
     *
     * @param int $candidate_id The id of the candidate
     * @param int $course_id
     * @return array the matrix as an array of row objects
     */
    function get_log_verifiers($verification_ids) {

        if(empty($verification_ids)) {
            return false;
        }

        $verification_ids = '('.implode(', ', $verification_ids).')';

        $select = "SELECT creator_id,
                          firstname,
                          lastname ";

        $from =  "FROM   {block_assmgr_log} as logtable,
                          {user} as creator  ";

        $verifywhere = " WHERE logtable.creator_id = creator.id
                           AND (logtable.type = 'Verification' OR logtable.entity = 'Verification sample')
                           AND logtable.record_id IN {$verification_ids}";

        $sort = "ORDER BY firstname, lastname";

        $sql = $select.$from.$verifywhere.$sort;

        return $this->dbc->get_records_sql($sql);

    }

    /**
     * Returns the first date (timecreated) that did actions that have taken place to a particular
     * portfolio
     *
     * @param int $candidate_id The id of the candidate
     * @param int $course_id
     * @return stdClassthe date
     */
    function get_log_first_date($candidate_id, $course_id) {

$select = "SELECT MIN(logtable.timecreated) AS timecreated
                   FROM {block_assmgr_log} as logtable,
                        {user} as creator
                   WHERE logtable.candidate_id = {$candidate_id}
                     AND logtable.course_id = {$course_id}
                     AND logtable.creator_id = creator.id ";

        return $this->dbc->get_record_sql($select);
    }

    /**
     * TODO comment this!
     *
     */
    function get_candidate_activities($candidate_id, $flextable) {

        $select = "SELECT   gi.itemname AS assignment_name,
                            coursetable.shortname AS course_name,
                            gi.itemmodule AS module_name,
                            gi.iteminstance as activity_id ";

        $from = "FROM       {grade_items} AS gi,
                            {grade_grades} AS gg,
                            {course} AS coursetable ";

        $where = "WHERE     gi.itemtype = 'mod'
                  AND       gi.itemmodule != '".GRADE_ASSMGR_ITEMMODULE."'
                  AND       gg.itemid = gi.id
                  AND       gi.courseid = coursetable.id
                  AND       gg.userid = {$candidate_id}
                  AND       gi.idnumber IS NOT NULL";

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // fetch the perpage limit
        if(array_key_exists("perpage", $flextable)) { // ajax case
            $perpage = $flextable->perpage;
        } else {
            $perpage = get_user_preferences('target_perpage', get_config('block_assmgr', 'defaultverticalperpage'));
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->pagesize($perpage, $count);
        //$flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }


    /**
     * Returns all of the moodle assignments belonging to the user.
     *
     * @param int $candidate_id the user id of the candidate
     * @param object $flextable The flexible table to display the results
     * @return array The result objects
     */
    function get_moodle_assignments($candidate_id, $flextable) {

        $select = "SELECT cmod.id AS cmid,
                          coursetable.shortname AS course_name,
                          assignmenttable.name AS assignment_name,
                          m.name AS module_name,
                          assignmenttable.id AS activity_id ";

        $from = "FROM {course} AS coursetable,
                      {assignment} AS assignmenttable,
                      {assignment_submissions} AS asignsubtable,
                      {course_modules} AS cmod,
                      {modules} AS m ";

        $where = "WHERE assignmenttable.course = coursetable.id
                    AND asignsubtable.assignment = assignmenttable.id
                    AND asignsubtable.userid = {$candidate_id}
                    AND cmod.course = coursetable.id
                    AND cmod.module = m.id
                    AND assignmenttable.id = cmod.instance
                    AND m.name = 'assignment'";

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // fetch the perpage limit
        if(array_key_exists("perpage", $flextable)) { // ajax case
            $perpage = $flextable->perpage;
        } else {
            $perpage = get_user_preferences('target_perpage', get_config('block_assmgr', 'defaultverticalperpage'));
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->pagesize($perpage, $count);
        //$flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }


    /**
     * Gets all the evidence belonging to a given candidate, optionally filtered
     * by creator.
     *
     * @param int $candidate_id
     * @param int $creator_id (optional)
     * @param object $flextable the table where the matrix will be displayed
     * @return array the matrix as an array of row objects
     */
    function get_evidence_by_candidate_matrix($candidate_id, $creator_id = null, $flextable) {

        $creator_condition = '';

        if(!empty($creator_id)) {
            $creator_condition = " AND creator_id = {$creator_id}";
        }

        $select = "SELECT evid.*,
                       res.id AS resource_id,
                       resty.name AS resource_type";

        $from = "
                    FROM {block_assmgr_evidence} AS evid,
                       {block_assmgr_resource} AS res,
                       {block_assmgr_resource_type} AS resty";

        $where = "
                    WHERE evid.candidate_id = {$candidate_id}
                   AND evid.id = res.evidence_id
                   AND res.resource_type_id = resty.id
                       $creator_condition";

        $sort = "";

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );

    }

    /**
     * Returns a matrix of evidences
     *
     *
     * @param int $candidate_id
     * @param int $folder_id
     * @param object $flextable the table where the matrix will be displayed
     * @return array the matrix as an array of row objects
     */
    function get_evidence_matrix($candidate_id, $folder_id, $flextable) {

        $folder_id = is_null($folder_id) ? 'NULL' : $folder_id;

        $select = "SELECT evid.*,
                       res.id AS resource_id,
                       resty.name AS resource_type,
                       resty.id AS resource_type_id";

        $from = "
                    FROM {block_assmgr_evidence} AS evid,
                       {block_assmgr_resource} AS res,
                       {block_assmgr_resource_type} AS resty";

        $where = "
                    WHERE evid.folder_id <=> {$folder_id}
                   AND evid.candidate_id = {$candidate_id}
                   AND evid.id = res.evidence_id
                   AND res.resource_type_id = resty.id";

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     * Returns a matrix of candidates and their results across a list of courses.
     *
     * This query does not return the actual percentage that the candidates
     * hs achieved, but rahter it returns an int in the form (achieved * 10000
     * + incomplete * 100 + claims * 1).
     *
     * N.B. This can be one very expensive query!!!!!
     *
     * @param array $candidatelist the users to include
     * @param array $courselist the courses to include
     * @param object $flextable the table where the results are being displayed
     * @param int $group (optional) a group that the users must be members of
     * @return array results objects (matrix rows) including blank spaces if no results
     */
    function get_portfolio_matrix($candidatelist, $courselist, $flextable, $group) {

        // no candidates
        if(empty($candidatelist)) {
            return false;
        }

        $select = "SELECT cand.id AS candidate_id,
                          cand.firstname,
                          cand.lastname ";

        $from = "FROM {user} AS cand ";

        // we need to manually add the left joined sub-queries
        foreach($courselist as $id => $courseobj) {

            // get the list of candidates in this course
            $enrolment = !empty($courseobj->candidates) ? implode(',', $courseobj->candidates) : '-1';

            // if there are no outcomes achieved then we need to check if this
            // is because the user is not an actual candidate (NULL), or simply
            // because they havent achieved anything yet (0)
            $select .= ", IFNULL(c{$id}, IF(cand.id IN ({$enrolment}), 0, NULL)) AS course{$id}
                        , IFNULL(port{$id}.needsassess, 0) AS needsassess{$id} ";

            // this looks inefficient, but it actually works rather well with query caching
            $from .= "LEFT JOIN (
                        SELECT userid, SUM(c{$id}) AS c{$id}
                        FROM (

                            # get the achieved and incomplete grades (weighted for sorting)
                            SELECT gg.userid,
                                   SUM(IF(finalgrade < gradepass, 100, 10000)) AS c{$id}
                              FROM {grade_items} AS gi,
                                   {grade_grades} AS gg
                             WHERE gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                               AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                               AND gi.courseid = {$id}
                               AND gi.iteminstance IS NULL
                               AND gi.outcomeid IS NOT NULL
                               AND gi.id = gg.itemid
                               AND gg.finalgrade IS NOT NULL
                          GROUP BY gg.userid

                             UNION

                            # get the claim grades that are not already either achieved or incomplete
                            SELECT port.candidate_id AS userid,
                                   COUNT(DISTINCT claim.outcome_id) AS c{$id}
                              FROM {block_assmgr_claim} AS claim,
                                   {block_assmgr_submission} AS sub,
                                   {block_assmgr_portfolio} AS port
                             WHERE port.course_id = {$id}
                               AND claim.submission_id = sub.id
                               AND sub.portfolio_id = port.id
                               AND ROW(port.candidate_id, claim.outcome_id) NOT IN (
                                     SELECT gg.userid,
                                            gi.outcomeid
                                       FROM {grade_items} AS gi,
                                            {grade_grades} AS gg
                                      WHERE gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                                        AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                                        AND gi.courseid = {$id}
                                        AND gi.iteminstance IS NULL
                                        AND gi.outcomeid IS NOT NULL
                                        AND gi.id = gg.itemid
                                        AND gg.finalgrade IS NOT NULL
                                   )
                           GROUP BY port.candidate_id
                        ) AS progress
                        GROUP BY userid

                     ) AS cit{$id}_achieved ON (cit{$id}_achieved.userid = cand.id)

                     LEFT JOIN {block_assmgr_portfolio} AS port{$id} ON (port{$id}.candidate_id = cand.id AND port{$id}.course_id = {$id}) ";
        }

        $where = "WHERE cand.id IN (".implode(',', $candidatelist).") ";

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        if(!empty($group)) {
            $from  .= "LEFT JOIN {groups_members} AS group_mem
                              ON (cand.id = group_mem.userid) ";

            $where .= " AND group_mem.groupid = {$group} ";
        }

        // TODO we don't actually need all the left joins to get the count
        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     * Returns the progress of a candidate across a list of courses.
     *
     * This query does not return the actual percentage that the candidates
     * hs achieved, but rahter it returns an int in the form (achieved * 10000
     * + incomplete * 100 + claims * 1).
     *
     * @param int $candidate_id The canidate id
     * @param array $courselist The courses to include
     * @return array result objects
     */
    function get_candidate_progress($candidate_id, $courselist) {

        // no candidates
        if(empty($courselist)) {
            return false;
        }

        $courselist = implode(',', $courselist);

        $sql = "SELECT SUM(progress) AS progress
                  FROM (
                    # get the achieved and incomplete grades (weighted for sorting)
                    SELECT SUM(IF(finalgrade < gradepass, 100, 10000)) AS progress
                      FROM {grade_items} AS gi,
                           {grade_grades} AS gg
                     WHERE gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                       AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                       AND gi.courseid IN ({$courselist})
                       AND gi.iteminstance IS NULL
                       AND gi.outcomeid IS NOT NULL
                       AND gi.id = gg.itemid
                       AND gg.finalgrade IS NOT NULL
                       AND gg.userid = {$candidate_id}

                     UNION

                    # get the claim grades that are not already either achieved or incomplete
                    SELECT COUNT(DISTINCT claim.outcome_id) AS progress
                      FROM {block_assmgr_claim} AS claim,
                           {block_assmgr_submission} AS sub,
                           {block_assmgr_portfolio} AS port
                     WHERE port.course_id IN ({$courselist})
                       AND claim.submission_id = sub.id
                       AND sub.portfolio_id = port.id
                       AND port.candidate_id = {$candidate_id}
                       AND ROW(port.candidate_id, port.course_id, claim.outcome_id) NOT IN (
                             SELECT gg.userid,
                                    gi.courseid,
                                    gi.outcomeid
                               FROM {grade_items} AS gi,
                                    {grade_grades} AS gg
                              WHERE gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                                AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                                AND gi.iteminstance IS NULL
                                AND gi.outcomeid IS NOT NULL
                                AND gi.id = gg.itemid
                                AND gg.finalgrade IS NOT NULL
                           )
                ) AS progress";

        return $this->dbc->get_field_sql($sql);
    }

    /**
     * Returns a matrix with the assessment dates for a particular student, course or group.
     * Only one is necessary and the portfolio id is not currently used for anything
     *
     * @param int $portfolio_id
     * @param int $course_id
     * @param int $user_id
     * @param int $group_id (optional)
     * @param object $flextable the table where the matrix is being displayed
     * @return array matrix of table row objects
     */
    function get_assessment_date_matrix($portfolio_id, $course_id, $user_id, $group_id, $flextable) {

        $group = (!empty($group_id)) ? "OR    groupid = {$group_id}" : '' ;

        $select = "SELECT events.id,
                          events.timestart as 'date',
                          events.description,
                          groupid,
                          repeatid,
                          courseid ";

        $from =  "FROM   {event} as events,
                         {block_assmgr_calendar_event} as assevents ";

        $where = "WHERE (courseid = {$course_id}
                  OR    userid = {$user_id}
                  {$group})
                  AND   events.id = assevents.event_id
                  AND   eventtype = ".CANDIDATE_EVENT;

        $sort = " ORDER BY date ";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*)'.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     * Returns a matrix with the actions that have taken place to a particular piece
     * of evidence.
     *
     * @TODO not written yet
     *
     * @param int $candidate_id
     * @param int $course_id
     * @param object $flextable the table where the matrix will be displayed
     */
    function get_evidence_log_matrix($candidate_id, $course_id, $flextable) {

    }

    /**
     * Returns a matrix with the actions that have taken place to a particular
     * portfolio
     *
     * @param int $candidate_id The id of the candidate
     * @param int $course_id
     * @param object $flextable the table where the matrix will be displayed
     * @return array the matrix as an array of row objects
     */
    function get_log_matrix_portfolio($candidate_ids, $course_ids, $flextable) {

        if(empty($candidate_ids) || empty($course_ids)) {
            return false;
        }

        $candidate_ids    = '('.implode(', ', $candidate_ids).')';
        $course_ids       = '('.implode(', ', $course_ids).')';

        $select = "SELECT logtable.id,
                          logtable.timecreated as 'date',
                          firstname,
                          lastname,
                          type,
                          entity,
                          attribute as 'fieldheader',
                          CONCAT_WS(' => ',old_value,new_value) as 'change' ";

        $from =  "FROM   {block_assmgr_log} as logtable,
                         {user} as creator ";

        $where = "WHERE logtable.candidate_id IN {$candidate_ids}

                  AND   course_id IN {$course_ids}
                  AND   logtable.creator_id = creator.id ";

        $sort = " ";

        $from2 = "FROM  {block_assmgr_log} as logtable,
                        {user} as creator,
                        {block_assmgr_submission} as subtable,
                        {block_assmgr_portfolio} as port ";

        $where2 = " WHERE port.course_id IN {$course_ids}
                    AND   port.candidate_id IN {$candidate_ids}
                    AND   entity = 'Evidence'
                    AND   subtable.portfolio_id = port.id
                    AND   subtable.evidence_id = logtable.record_id
                    AND   logtable.creator_id = creator.id";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
            $where2 .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        $count = $this->dbc->count_records_sql('SELECT ( SELECT COUNT(*)'.$from.$where.') + ( SELECT COUNT(*)'.$from2.$where2.')');

        // tell the table how many pages it needs
        $flextable->totalrows($count);


        return $this->dbc->get_records_sql(
            "(".$select.$from.$where.") UNION (".$select.$from2.$where2.")".$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

/**
     * Returns a matrix with the actions that have taken place to a particular
     * submission
     *
     * @param int $submission_ids  The id of the submissions
     * @param object $flextable the table where the matrix will be displayed
     * @return array the matrix as an array of row objects
     */
    function get_log_matrix_submission($submission_ids, $flextable) {

        if(empty($submission_ids)) {
            return false;
        }

        $submission_ids = '('.implode(', ', $submission_ids).')';

        $select = "SELECT logtable.id,
                          logtable.timecreated as 'date',
                          firstname,
                          lastname,
                          type,
                          entity,
                          attribute as 'fieldheader',
                          CONCAT_WS(' => ',old_value,new_value) as 'change' ";

        $from =  "FROM   {block_assmgr_log} as logtable,
                         {user} as creator,
                         {block_assmgr_submission} as subtable ";

        $where = " WHERE   subtable.id IN {$submission_ids}
                    AND    logtable.creator_id = creator.id
                    AND  ((entity = 'Evidence'
                    AND    subtable.evidence_id = logtable.record_id)
                    OR    (entity = 'Submission'
                    AND    subtable.id = logtable.record_id))";

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*)'.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

   /**
     * Returns a matrix with the actions that have taken place to a particular
     * piece of evidence
     *
     * @param int $evidence_ids The id of the pieces of evidence
     * @param object $flextable the table where the matrix will be displayed
     * @return array the matrix as an array of row objects
     */
    function get_log_matrix_evidence($evidence_ids, $flextable) {

        if(empty($evidence_ids)) {
            return false;
        }

        $evidence_ids = '('.implode(', ', $evidence_ids).')';

        $select = "SELECT logtable.id,
                          logtable.timecreated as 'date',
                          firstname,
                          lastname,
                          type,
                          entity,
                          attribute as 'fieldheader',
                          CONCAT_WS(' => ',old_value,new_value) as 'change' ";

        $from =  "FROM   {block_assmgr_log} as logtable,
                         {user} as creator ";

        $where = " WHERE entity = 'Evidence'
                    AND   logtable.creator_id = creator.id
                    AND   logtable.record_id IN {$evidence_ids}";

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*)'.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }


    /**
     * Returns a matrix with the actions that have taken place to a particular
     * portfolio
     *
     * @param int $verification_ids The id of the verifications
     * @param object $flextable the table where the matrix will be displayed
     * @return array the matrix as an array of row objects
     */
    function get_log_matrix_verification($verification_ids, $flextable) {

        if(empty($verification_ids)) {
            return false;
        }

        $verification_ids = '('.implode(', ', $verification_ids).')';

        $select = "SELECT logtable.id,
                          logtable.timecreated as 'date',
                          firstname,
                          lastname,
                          type,
                          entity,
                          attribute as 'fieldheader',
                          CONCAT_WS(' => ',old_value,new_value) as 'change' ";

        $from =  "FROM   {block_assmgr_log} as logtable,
                         {user} as creator ";

        $where = " WHERE logtable.creator_id = creator.id
                           AND (logtable.type = 'Verification' OR logtable.entity = 'Verification sample')
                           AND logtable.record_id IN {$verification_ids}";

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*)'.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     * Returns a matrix of submissions and their outcomes for displaying a
     * candidate's portfolio.
     *
     * @param int $portfolio_id
     * @param int $candidate_id
     * @param object $flextable the table where the matrix will be displayed
     * @param array $outcomes an array of outcomes keyed by their ids
     * @return array the matrix as an array of row objects
     */
    function get_submission_matrix($portfolio_id, $candidate_id, $flextable, $outcomes) {

        $select = "SELECT sub.id AS submission_id,
                          port.id AS portfolio_id,
                          port.course_id,
                          port.candidate_id,
                          sub.evidence_id,
                          evid.name,
                          sub.hidden ";

        $from = "FROM {block_assmgr_portfolio} AS port,
                      {block_assmgr_evidence} AS evid ";

        // are we showing the details columns
        if($flextable->get_filter('show_details')) {

            // add the fields needed for the details columns
            $select .= ", evid.description,
                          sub.timecreated AS submission_date,
                          resty.name AS resource_type,
                          (evid.candidate_id!=sub.creator_id) AS assorevid,
                          conf.status AS confirmation ";

            $from .= "LEFT JOIN {block_assmgr_confirmation} AS conf
                             ON (evid.id = conf.evidence_id)

                      LEFT JOIN {block_assmgr_resource} AS res
                             ON (evid.id = res.evidence_id)

                      LEFT JOIN {block_assmgr_resource_type} AS resty
                             ON (res.resource_type_id = resty.id) ";
        }

        $from .= ", {block_assmgr_submission} AS sub ";

        $where = "WHERE port.id = {$portfolio_id}
                    AND port.id = sub.portfolio_id
                    AND sub.evidence_id = evid.id ";

        // are we hiding hidden submissions
        if($flextable->access_isassessor) {
            $where .= " AND sub.hidden = 0 ";
        }

        // are we hiding assessed submissions
        if(!$flextable->get_filter('show_assessed')) {

            $from .= "LEFT JOIN (
                         SELECT grade.id, grade.submission_id
                           FROM {block_assmgr_grade} AS grade
                          WHERE grade.grade IS NOT NULL
                             OR grade.feedback IS NOT NULL
                      ) AS assessed ON (assessed.submission_id = sub.id) ";

            $where .= "AND assessed.id IS NULL";
        }

        // are we showing outcomes or evidence types
        if($flextable->get_filter('show_outcomes')) {

            // we need to manually add each of the outcomes and claims
            foreach($outcomes as $id => $outcome) {

                $select .= ", out{$id}.grade AS outcome{$id}
                            , clm{$id}.id AS claim{$id} ";

                // join the assessor outcome grades
                $from .= "LEFT JOIN {block_assmgr_grade} AS out{$id}
                                 ON (sub.id = out{$id}.submission_id
                                     AND out{$id}.outcome_id = {$id}) ";

                // join the candidate outcome claims
                $from .= "LEFT JOIN {block_assmgr_claim} AS clm{$id}
                                 ON (sub.id = clm{$id}.submission_id
                                     AND clm{$id}.outcome_id = {$id}) ";
            }

        } else {

            // get the list of evidence types
            $evidincetypes = $this->get_evidence_types();

            // we need to manually add each of the evidence types
            foreach($evidincetypes as $id => $type) {
                $select .= ", IF(subevty{$id}.id, 1, 0) AS subevty{$id}
                            , IF(clmevty{$id}.id, 1, 0) AS clmevty{$id} ";

                // join the assessor evidence types
                $from .= "LEFT JOIN {block_assmgr_sub_evid_type} AS subevty{$id}
                                 ON (sub.id = subevty{$id}.submission_id
                                     AND subevty{$id}.evidence_type_id = {$id})
                                     AND subevty{$id}.creator_id != {$candidate_id} ";

                // join the candidate evidence type claims
                $from .= "LEFT JOIN {block_assmgr_sub_evid_type} AS clmevty{$id}
                                 ON (sub.id = clmevty{$id}.submission_id
                                     AND clmevty{$id}.evidence_type_id = {$id})
                                     AND clmevty{$id}.creator_id = {$candidate_id} ";
            }
        }

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        $data = $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );

        return $data;
    }

    /**
     * Checks to see if the current submission was submitted by the current user
     *
     * @global <type> $USER
     * @param <type> $submission_id
     * @return <type>
     */
    function is_submission_mine($submission_id) {
        global $USER;

        return $this->dbc->record_exists_sql(
            "SELECT id
               FROM {block_assmgr_submission} AS sub
              WHERE sub.id = {$submission_id}
                AND sub.creator_id = {$USER->id}"
        );
    }

    /**
     * Checks to see if there are any grades for the submission.
     *
     * @param int $submission_id The id of the submission
     * @return bool Was the submission graded
     */
    function has_submission_grades($submission_id) {

        return $this->dbc->record_exists_sql(
            "SELECT grade.id
               FROM {block_assmgr_grade} AS grade
              WHERE grade.submission_id = {$submission_id}
                AND (grade.grade IS NOT NULL
                 OR grade.feedback IS NOT NULL)"
        );
    }

    /**
     * Returns the config options for a specific instance of the assessment
     * manager.
     *
     * N.B. There is no function to do this in Moodle.
     * @see http://moodle.org/mod/forum/discuss.php?d=129799#p568635
     *
     * @param int $course_id The id of the course for which to get the config instance
     * @return object An object containing config information
     */
    function get_instance_config($course_id) {

        $encoded = '';

        // get the global config options
        $config = get_config('block_assmgr');

        // check for the presence of a table to determine which query to run
        $oldtable = $this->dbc->get_records_sql("SHOW TABLES LIKE '{block_instance}'");

        // fetch the local settings
        if(!empty($oldtable)) {
            // version 1.9x
            $encoded = $this->dbc->get_field_sql(
                "SELECT ins.configdata
                 FROM {block} AS blk,
                      {block_instance} AS ins
                 WHERE blk.name = 'assmgr'
                   AND blk.id = ins.blockid
                   AND ins.pageid = {$course_id}
                   AND ins.pagetype = 'course-view'"
            );
        } else {
            // version 2.x
            $encoded = $this->dbc->get_field_sql(
                "SELECT ins.configdata
                 FROM {block_instances} AS ins,
                      {context} AS con
                 WHERE ins.blockname = 'assmgr'
                   AND ins.pagetypepattern = 'course-view-*'
                   AND ins.parentcontextid = con.id
                   AND con.instanceid = {$course_id}
                   AND con.contextlevel = ".CONTEXT_COURSE

            );
        }

        if(!empty($encoded)) {
            $local = unserialize(base64_decode($encoded));
            // merge the two together
            foreach($local as $name => $value) {
                // the instance config can not override a negative value
                if(!isset($config->{$name}) || $config->{$name} == 1) {
                    $config->{$name} = $value;
                }
            }
        }

        return $config;
    }

    /**
     * Get a calendar event.
     *
     * @param int $event_id The id of the event
     * @return array containing the result object
     */
    function get_event($event_id) {

        return $this->dbc->get_record('event', array('id' => $event_id));
    }

    /**
     * Get all assmgr_calendar_event records.
     *
     * @param int $event_id The id of the event
     * @return array containing the result object
     */
    function get_assmgr_calendar_events() {

        return $this->dbc->get_records('block_assmgr_calendar_event');
    }

    /**
     * Creates a new calendar event.
     *
     * @param iobject $event The new event object
     * @return int The event id or false if it failed
     */
    function create_event($event) {
        return add_event($event);
    }


   /**
     * Create evidence submission record.
     *
     * @param object containing assmgr calendar event record
     * @return int|bool containing the id of the calendar event or false if it fails
     */
    function create_assmgr_event($event) {
        return $this->insert_record('block_assmgr_calendar_event', $event);
    }

    /**
     * Checks if a portfolio is locked, and optionally, if it is locked by a
     * specific user.
     *
     * @param int $portfolio_id
     * @param int $creator_id (optional)
     * @return bool Is it locked? Will be false if locked by a creator other than the one specified
     */
    function lock_exists($portfolio_id, $creator_id=null) {

        // delete any expired locks
        $this->release_expired_locks();

        $conditions = array('portfolio_id' => $portfolio_id);

        if(!empty($creator_id)) {
            $conditions['creator_id'] = $creator_id;
        }

        return $this->dbc->record_exists('block_assmgr_lock', $conditions);
    }

    /**
     * Create a lock on a portfolio.
     *
     * @param int $portfolio_id The id of the portfolio to lock
     * @param int $creator_id The id of the user making the lock
     * @param int $expiry (optional, usually the defaultexpirytime setting) The number of seconds to lock the portfolio
     * @return bool did the lock apply successfully? False if it's locked already
     */
    function create_lock($portfolio_id, $creator_id, $expiry=null) {

        if (empty($expiry)) {
            $expiry = get_config('block_assmgr', 'defaultexpirytime');
        }

        // delete any expired locks
        $this->release_expired_locks();

        // check if there is already a lock
        if($this->lock_exists($portfolio_id)) {
            return false;
        } else {
            // make a new lock
            $lock = new object();
            $lock->portfolio_id = $portfolio_id;
            $lock->creator_id = $creator_id;
            $lock->expire = $expiry + time();

            // TODO put this back in when done testing/developing
            //return $this->insert_record('block_assmgr_lock', $lock);
        }

        return true;
    }

    /**
     * Gets the current lock (if any) on a portfolio
     *
     * @param int $portfolio_id
     * @return array containing the lock object if it exists
     */
    function get_lock($portfolio_id) {

        // delete any expired locks
        $this->release_expired_locks();

        return $this->dbc->get_record('block_assmgr_lock', array('portfolio_id' => $portfolio_id));
    }

    /**
     * Renews the time a lock will endure for, either to the default, or a specified time span
     *
     * @param int $portfolio_id
     * @param int $creator_id
     * @param int $expiry (optional, usually the defaultexpirytime setting) The number of seconds to lock the portfolio
     */
    function renew_lock($portfolio_id, $creator_id, $expiry=null) {

        if (empty($expiry)) {
            $expiry = get_config('block_assmgr', 'defaultexpirytime');
        }

        // delete any expired locks
        $this->release_expired_locks();

        $lock = $this->get_lock($portfolio_id, $creator_id);

        if($lock) {

            // update the expiry time
            $lock->expire = $expiry + time();

            return $this->update_record('block_assmgr_lock', $lock);
        } else {
            return false;
        }
    }

    /**
     * Releases any locks that have expired.
     * Should be called at the top of every lock function.
     *
     * @return bool did the delete occur?
     */
    private function release_expired_locks() {

        return $this->delete_records_select('block_assmgr_lock', 'expire < '.time());
    }

    /**
     * Returns the scale that a portfolio uses
     *
     * @param int $portfolio_id
     * @return object the scale as an object
     */
    function get_portfolio_scale($portfolio_id) {
        global $CFG;

        // get the portfolio
        $port = $this->get_portfolio_by_id($portfolio_id);

        // get the course grade item
        $gi = grade_item::fetch(
            array(
                'itemtype'      => 'course',
                'itemmodule'    => null,
                'courseid'      => $port->course_id
            )
        );

        $scale_id = empty($gi->scaleid) ? null : $gi->scaleid;

        return $this->get_scale($scale_id, $gi->gradepass);
    }

    /**
     * Makes a new evidence record for any resource plugin
     *
     * @param object $evidence_type
     * @return mixed the id of the new record or false if it fails
     */
    function create_evidence_type($evidence_type) {
        return $this->insert_record('block_assmgr_evidence_type',$evidence_type);
    }

    /**
     * Checks if a record exists with $field matching a given value in $data
     *
     * @param string $tablename The name of the table
     * @param string $fields The name of the field
     * @param string $data The data to be checked
     * @return bool True if the record exists
     */
    function exists($tablename, $fields, $data) {
        global $DB, $CFG;

        $conditions = array();

        if(!empty($data['id'])) {
            $conditions[] = "id != {$data['id']}";
        }

        foreach($fields as $field) {
            // make sure the comparison is case insensitive
            $conditions[] = "UCASE({$field}) = UCASE('{$data[$field]}')";
        }

        $conditions = implode(' AND ', $conditions);

        return $DB->record_exists_select('block_assmgr_'.$tablename, $conditions);
    }

    /**
     * Makes a new evidence record for any resource plugin
     *
     * @param string $tablename the resource type's table
     * @param object $resource e.g. a url, file etc
     * @return mixed the id of the new record or false if it fails
     */
    function create_resource_plugin($tablename, $resource) {

        return $this->insert_record($tablename, $resource);
    }

    /**
     * Update a evidence record for any resource plugin
     *
     * @param string $tablename the resource type's table
     * @param object $resource e.g. a url, file etc
     * @return mixed the id of the new record or false if it fails
     */
    function update_resource_plugin($tablename, $resource) {

        return $this->update_record($tablename, $resource);
    }

   /**
    * Gets an evidence record for any resource plugin
    *
    * @param string $tablename the resource type's table
    * @param int $id the id of the record that's required
    * @return array containing the result object
    */
    function get_resource_plugin($tablename,$id) {

        return $this->dbc->get_record($tablename, array('id'=>$id));
    }

    /**
     * Deletes an evidence record for any resource plugin
     *
     * @param string $tablename the resource type's table
     * @param int $id the id of the record that's to be deleted
     * @return bool did it work?
     */
    function delete_resource_plugin($tablename, $id) {

      return $this->delete_records($tablename, array('id' => $id));
    }

    /**
     * Fetch a specific verification sample.
     *
     * @param int $verification_id The id of the verification record
     */
    public function get_verification($verification_id) {

        return $this->dbc->get_record('block_assmgr_verification', array('id' => $verification_id));
    }

    /**
     * Create a new verification record.
     *
     * @param object $verification The post data from the verification mform
     */
    public function create_verification($verification) {

        // rename the sample params

        $verification->category_id = $verification->sample[0];
        $verification->course_id   = $verification->sample[1];
        $verification->assessor_id = $verification->sample[2];

        return $this->insert_record('block_assmgr_verification', $verification);
    }

    /**
     * Update an existing verification record.
     *
     * @param object $verification The post data from the verification mform
     */
    public function set_verification($verification) {

        // rename the sample params
        if (isset($verification->sample)) {
            $verification->category_id = $verification->sample[0];
            $verification->course_id   = $verification->sample[1];
            $verification->assessor_id = $verification->sample[2];
        }

        return $this->update_record('block_assmgr_verification', $verification);
    }

    /**
     * Returns a paginated list of all the verifications.
     *
     * @param object $flextable the table where the matrix will be displayed
     */
    function get_verification_matrix($flextable) {

        $select = "SELECT ver.*,
                          verifier.firstname AS v_firstname,
                          verifier.lastname AS v_lastname,
                          assessor.firstname AS a_firstname,
                          assessor.lastname AS a_lastname,
                          course.fullname AS course,
                          cat.name AS category,
                          ver.timecreated AS started,
                          IF(ver.complete, ver.timemodified, null) AS complete ";

        $from = "FROM {block_assmgr_verification} AS ver
                     LEFT JOIN {user} AS verifier
                            ON (ver.verifier_id = verifier.id)
                     LEFT JOIN {user} AS assessor
                            ON (ver.assessor_id = assessor.id)
                     LEFT JOIN {course} AS course
                            ON (ver.course_id = course.id)
                     LEFT JOIN {course_categories} AS cat
                            ON (course.category = cat.id) ";

        $where = "";

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     * Returns a paginated list of all the portfolios in the chosen sample.
     *
     * @param object $flextable the table where the matrix will be displayed
     */
    public function get_verification_portfolio_matrix($category_id, $course_id, $assessor_id, $flextable) {

        $select = "SELECT port.*,
                          port.id AS portfolio_id,
                          cour.shortname AS course,
                          cand.firstname,
                          cand.lastname,
                          cand.id as candidate_id,
                          progress.total AS progress,
                          gi.scaleid AS scale_id,
                          gi.gradepass,
                          gg.finalgrade,
                          ver.id as verify_form_id,
                          ver.accurate,
                          ver.accurate_comment,
                          ver.constructive,
                          ver.constructive_comment,
                          ver.needs_amending,
                          ver.amendment_comment,
                          IF(ver.id, 1, 0) AS verified ";

        $from = "FROM {user} AS cand,
                      {course} AS cour,
                      {block_assmgr_portfolio} AS port

                     LEFT JOIN (
                       SELECT gg.userid,
                              gi.courseid,
                              COUNT(*) AS total
                       FROM {grade_items} AS gi,
                            {grade_grades} AS gg
                       WHERE gi.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                         AND gi.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                         AND gi.iteminstance IS NULL
                         AND gi.outcomeid IS NOT NULL
                         AND gi.id = gg.itemid
                         AND gg.finalgrade IS NOT NULL
                       GROUP BY gg.userid
                     ) AS progress ON (progress.userid = port.candidate_id AND progress.courseid = port.course_id)

                     LEFT JOIN {grade_items} AS gi ON (
                        gi.itemtype = 'course' AND gi.itemmodule IS NULL AND gi.courseid = port.course_id
                     )

                     LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid AND gg.userid = port.candidate_id)

                     LEFT JOIN {block_assmgr_verify_form} AS ver ON (ver.portfolio_id = port.id) ";

        $where = "WHERE port.candidate_id = cand.id
                    AND port.course_id = cour.id
                    ";

        //TODO changed LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid )
        //     to      LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid AND gg.userid = port.candidate_id)
        //     to prevent gg from other portfolios being attached being attached to

        if(!empty($category_id)) {
            $where .= " AND cour.category = {$category_id} ";
        }

        if(!empty($course_id)) {
            $where .= " AND cour.id = {$course_id} ";
        }

        if(!empty($assessor_id)) {
            // TODO add a sub-query that checks if this user has actually assessed any of submissions or the portfolio itself
        }

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     * Returns a paginated list of all the submissions in the chosen sample.
     *
     * @param object $flextable the table where the matrix will be displayed
     */
    public function get_verification_submission_matrix($category_id, $course_id, $assessor_id, $flextable) {

        $select = "SELECT port.*,
                          port.id AS portfolio_id,
                          sub.*,
                          sub.id AS submission_id,
                          sub.name AS evidence,
                          cour.shortname AS course,
                          cand.firstname,
                          cand.lastname,
                          ver.id as verify_form_id,
                          ver.accurate,
                          ver.accurate_comment,
                          ver.constructive,
                          ver.constructive_comment,
                          ver.needs_amending,
                          ver.amendment_comment,
                          IF(ver.id, 1, 0) AS verified ";

        $from = "FROM {user} AS cand,
                      {course} AS cour,
                      {block_assmgr_portfolio} AS port,
                      {block_assmgr_submission} AS sub

                      LEFT JOIN {block_assmgr_verify_form} AS ver ON (ver.submission_id = sub.id) ";

        $where = "WHERE port.candidate_id = cand.id
                    AND port.course_id = cour.id
                    AND port.id = sub.portfolio_id";

        if(!empty($category_id)) {
            $where .= " AND cour.category = {$category_id} ";
        }

        if(!empty($course_id)) {
            $where .= " AND cour.id = {$course_id} ";
        }

        if(!empty($assessor_id)) {
            // TODO add a sub-query that checks if this user has actually assessed any of submissions or the portfolio itself
        }

        $sort = "";

        // fetch any additional filters provided by the table
        $sql_where = $flextable->get_sql_where();
        if(!empty($sql_where)) {
            $where .= ' AND '.$sql_where;
        }

        // fetch any sort keys provided by the table
        $sql_sort = $flextable->get_sql_sort();
        if(!empty($sql_sort)) {
            $sort = ' ORDER BY '.$sql_sort;
        }

        // get a count of all the records for the pagination links
        $count = $this->dbc->count_records_sql('SELECT COUNT(*) '.$from.$where);

        // tell the table how many pages it needs
        $flextable->totalrows($count);

        return $this->dbc->get_records_sql(
            $select.$from.$where.$sort,
            null,
            $flextable->get_page_start(),
            $flextable->get_page_size()
        );
    }

    /**
     * Gets a verification form for a supplied id
     *
     * @param int $verify_form_id
     * @return object
     */
    public function get_verification_form($verify_form_id) {
       return $this->dbc->get_record('block_assmgr_verify_form', array('id' => $verify_form_id));
    }

    /**
     * Gets the verification form for a given portfolio id
     *
     * @param int $portfolio_id the id of the portfolio
     * @param int $verification_id the id of the verification that this verify form is part of
     * @return array
     */
    public function get_verification_form_by_portfolio($portfolio_id, $verification_id) {

        $sql = "SELECT *
                FROM {block_assmgr_verify_form}
                WHERE verification_id = {$verification_id}
                AND portfolio_id = {$portfolio_id}
                AND submission_id IS NULL
                ";
        return $this->dbc->get_record_sql($sql);
    }

    /**
     * Returns all verification forms in a particular verification
     *
     * @param $verificationid
     * @return array the results objects
     */
    public function get_verification_forms_by_verification($verificationid) {
        return $this->dbc->get_records('block_assmgr_verify_form', array('verification_id' => $verificationid));
    }

    /**
     * Creates a new verification form int he database
     *
     * @param $data the object representing a row in block_assmgr_verify_form
     * @return int the id of the DB record created
     */
    public function create_verification_form($data) {
        return $this->insert_record('block_assmgr_verify_form', $data);
    }

    /**
     * Updates a verification form with new data
     *
     * @param object $data represents a table row in block_assmgr_verify_form
     * @return bool true or exception if error
     */
    public function set_verification_form($data) {

        return $this->update_record('block_assmgr_verify_form', $data);
    }

    /**
     * This deletes all grade item history records associated with the block
     *
     * @return array an array of the grade objects if any or false
     */
    public function delete_item_history() {
        // remove all records associated with the assessment manager from the items history table
        $this->dbc->delete_records('grade_items_history', array('itemtype' => GRADE_ASSMGR_ITEMTYPE,'itemmodule' => GRADE_ASSMGR_ITEMMODULE));
    }

    /**
     * This deletes all grade grade history records associated with the block
     *
     * @return array an array of the grade objects if any or false
     */
    public function delete_grade_history() {
        // remove all records associated with the assessment manager from the grades history table
        $this->dbc->delete_records('grade_grades_history', array('source' => GRADE_ASSMGR_SOURCE));
    }

    /**
     * This deletes all calendar records associated with the block
     *
     * @return array an array of the grade objects if any or false
     */
    public function delete_assmgr_calendar_event($event_id) {
        // remove all records associated with the assessment manager from the grades history table
        return $this->dbc->delete_records('block_assmgr_calendar_event', array('event_id' => $event_id));
    }




    /**
     * This deletes all block configuration options
     *
     * @return array an array of the grade objects if any or false
     */
    public function delete_block_config() {
        // remove all records associated with the assessment manager from the grades history table
        $this->dbc->delete_records('config_plugins', array('plugin' => GRADE_ASSMGR_ITEMMODULE));
    }

    /**
     * Gets the parent categories for the outcomes listed in view_submissions
     *
     * @return <type>
     */
    public function get_outcome_categories($outcomes) {

        if(empty($outcomes)) {
            return false;
        }

        // TODO sometimes, there will not be a grade item yet, for manually created outcomes.
        // how to get empty categories where they belong if there is no sortorder?

        $sql = "SELECT c.id, c.fullname, d.description, COUNT(i.id) as colspan

                      FROM {grade_outcomes} o
                INNER JOIN {grade_items} i
                        ON (i.outcomeid = o.id)
                INNER JOIN {grade_items} AS gic
                        ON gic.iteminstance = i.categoryid
                INNER JOIN {grade_categories} c
                        ON c.id = i.categoryid
                 LEFT JOIN {block_assmgr_grade_cat_desc} d
                        ON d.grade_category_id = c.id

                     WHERE o.id IN (".implode(', ', $outcomes).")

                       AND i.itemtype = '".GRADE_ASSMGR_ITEMTYPE."'
                       AND i.itemmodule = '".GRADE_ASSMGR_ITEMMODULE."'
                       AND i.iteminstance IS NULL

                       AND gic.iteminstance = i.categoryid
                       AND gic.itemtype = 'category'
                       AND gic.itemmodule IS NULL

                  GROUP BY c.id
                  ORDER BY gic.sortorder, i.sortorder";

        return $this->dbc->get_records_sql($sql);
    }

    /**
     * Gets the parent categories for the outcomes listed in view_submissions
     *
     * @return <type>
     */
    public function get_portfolio_candidates() {
        $sql = "SELECT  candidate_id
                FROM    {block_assmgr_portfolio}
                GROUP BY  candidate_id";

        return $this->dbc->get_records_sql($sql);
    }

   /**
     * Create submission grade
     *
     * @param object containing submission grade
     * @return int|bool containing the id of the submission grade or false if it fails
     */
    function create_submission_grade($submission_grade) {
        return $this->insert_record('block_assmgr_grade', $submission_grade);
    }

    /**
     * Given an array of categoryids, this will get their names and descriptions
     *
     * @param array $categoryids the ids of the categories to retrieve
     * @return array the results as an array of objects
     */
    function get_grade_categories($categoryids) {

        if (empty($categoryids)) {
            return array();
        }

        $sql = "SELECT gc.id, gc.fullname, gcd.description
                  FROM {grade_categories} gc
             LEFT JOIN {block_assmgr_grade_cat_desc} gcd
                    ON gc.id = gcd.grade_category_id
                 WHERE gc.id IN (".implode(",", $categoryids).")";

        return $this->dbc->get_records_sql($sql);
    }

}
?>