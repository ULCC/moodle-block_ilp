<?php

/**
 * Handles downloads for form elements
 *
 * @copyright &copy; @{YEAR} University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package form_library
 * @version 1.0
 *
 */

require_once('../../../../../config.php');
require_once('../../../../../lib/filelib.php');
require_once('../../../lib.php');

$relativepath = get_file_argument();
$forcedownload = optional_param('forcedownload', 0, PARAM_BOOL);


// extract relative path components
$args = explode('/', ltrim($relativepath, '/'));

if (count($args) < 3) { // always at least context, component and filearea
    print_error('invalidarguments');
}

$contextid = (int)array_shift($args);
$component = clean_param(array_shift($args), PARAM_COMPONENT);
$filearea  = clean_param(array_shift($args), PARAM_AREA);

list($context, $course, $cm) = get_context_info_array($contextid);

ilp_pluginfile($context, $filearea, $args, $forcedownload);