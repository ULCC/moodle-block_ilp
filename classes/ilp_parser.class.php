<?php
/**
 * This is a library class that provides reworked versions of some functions
 * from /lib/moodlelib.php, which are used to parse data received via POST
 * and GET.
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

// fetch the table library
require_once($CFG->dirroot.'/blocks/ilp/constants.php');

class ilp_parser {

    var $params;

    /**
     * Returns all the params parsed for a given page.
     *
     */
    public function get_params() {
        return $this->params;
    }

    /**
     * Returns a particular value for the named variable, taken from
     * POST or GET.  If the parameter doesn't exist then an error is
     * thrown because we require this variable.
     *
     * This function should be used to initialise all required values
     * in a script that are based on parameters.  Usually it will be
     * used like this:
     *    $id = required_param('id');
     *
     * This function is being modified so that it will now fail if
     * a empty string is passed
     *
     * @uses required_param()
     * @param string $parname the name of the page parameter we want
     * @param int $type expected type of parameter
     * @return mixed
     */
    function required_param($parname, $type=PARAM_CLEAN) {

        // detect_unchecked_vars addition
        global $CFG;
        if (!empty($CFG->detect_unchecked_vars)) {
            global $UNCHECKED_VARS;
            unset ($UNCHECKED_VARS->vars[$parname]);
        }

        if (isset($_POST[$parname])) {       // POST has precedence
            $param = $_POST[$parname];
        } else if (isset($_GET[$parname])) {
            $param = $_GET[$parname];
        } else {
            print_error('missingparam', 'block_ilp', '', $parname);
        }

        if ($parname == "") {
            print_error('emptyparam', 'block_ilp', '', $parname);
        }

        $retparam = $this->clean_param($param, $type);

        if ($retparam === false) {
            print_error('wrongparam', 'block_ilp', '', $parname);
        } else {
            // add the param to the list
            $this->params[$parname] = $retparam;

            return $retparam;
        }
    }

    /**
     * Returns a particular value for the named variable, taken from
     * POST or GET, otherwise returning a given default.
     *
     * This function should be used to initialise all optional values
     * in a script that are based on parameters.  Usually it will be
     * used like this:
     *    $name = optional_param('name', 'Fred');
     *
     * This function has been modifed so that any param that contains the empty
     * string will now pass the default value instead.
     *
     * @uses optional_param()
     * @param string $parname the name of the page parameter we want
     * @param mixed  $default the default value to return if nothing is found
     * @param int $type expected type of parameter
     * @return mixed
     */
    function optional_param($parname, $default=NULL, $type=PARAM_CLEAN) {

        // detect_unchecked_vars addition
        global $CFG;
        if (!empty($CFG->detect_unchecked_vars)) {
            global $UNCHECKED_VARS;
            unset ($UNCHECKED_VARS->vars[$parname]);
        }

        if (isset($_POST[$parname])) {       // POST has precedence
            $param = $_POST[$parname];
        } else if (isset($_GET[$parname])) {
            $param = $_GET[$parname];
        } else {
            // add the param to the list
            $this->params[$parname] = $default;

            return $default;
        }

        if ($param == "") {
            // add the param to the list
            $this->params[$parname] = $default;

            return $default;
        }

        $retparam = $this->clean_param($param, $type);

        if ($retparam === false) {
            print_error('wrongparamopt', 'block_ilp', '', $parname);
        } else {
            // add the param to the list
            $this->params[$parname] = $retparam;

            return $retparam;
        }
    }

    /**
     * This is a wrapper function used to give clean_param the ability to distinguish
     * if a int has been passed when PARAM_INT is declared as the variable type.
     * If a int has not been passed false is returned. For all other types normal
     * operation of the clean_param function takes place.
     *
     * @uses clean_param()
     * @param mixed $param the variable we are cleaning
     * @param int $type expected format of param after cleaning.
     * @return mixed
     */
    function clean_param($param, $type) {
        if ($type == PARAM_INT) {
                if(preg_match('/[0-9]+/', $param) == 0) {
                    return false;
                }
            }

        if ($type == ILP_PARAM_ARRAY) {
                if(!is_array($param)) {
                    return false;
                } else {
                    //TODO need to code some tests on the array
                    return $param;
                }
            }

        return clean_param($param, $type);
    }
}

// create a global instance of the parser class
global $PARSER;
$PARSER = new ilp_parser();
?>