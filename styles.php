<?php
/**
 * Backwards compatibility support for block styles in Moodle 1.9.x
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */
global $CFG;

// TODO moodle 2 now uses .css for style sheets and url([[pix:t/expanded]]); notation for
// fetching images, so we need to update the styles.css file to use this notation and
// then parse those values here

require_once($CFG->dirroot.'/blocks/ilp/styles.css');

?>