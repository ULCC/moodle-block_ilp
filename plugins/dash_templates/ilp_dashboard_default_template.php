<?php
/**
 * The default dashboard template use this as an example when creating other template
 *
 * @abstract
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */



//require the ilp_plugin.php class
require_once($CFG->dirroot.'/blocks/ilp/classes/plugins/ilp_dashboard_template.class.php');

class ilp_dashboard_default_template extends ilp_dashboard_template {
	function __construct()	{

		//calling the parent constructor as I require some of the variables
		//that intialised in the parent class
		parent::__construct();

		//specify the regions that exist in the template file
		//note the region names in the template must be exactly
		//the same as the regions named. Also a region name must
		//be unique in a template
		$this->regions[]		=	'region1';	//the region where student information is usually displayed
		$this->regions[]		=	'region2';	//the region where ilp information is displayed

	}
}