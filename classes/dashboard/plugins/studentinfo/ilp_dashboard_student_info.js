/**
 * Javascript for the onchange functions in the submissions table
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package AssMgr
 * @version 1.0
 */

M.ilp.ilp_dashboard_student_info = (function() {
	
	
	
	function removeselect(outcomeid) {

        var editicon = document.getElementById('editicon'+outcomeid);
        var grade = document.getElementById('columngrade'+outcomeid);
        var select = document.getElementById('columnselect'+outcomeid);

        M.assmgr.view_submissions.hideelement(select);
        M.assmgr.view_submissions.showelement(grade);
        M.assmgr.view_submissions.showelement(editicon);


    }
	
	
})();