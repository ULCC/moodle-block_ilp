/**
 * Javascript for the onchange functions in the ilp_mis_attendance_plugin_byclass plugin
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */


M.ilp_mis_attendance_plugin_byclass = {
		
        /**
         * When the edit icon is clicked, this will unhide the select thing and show the current DB grade.
         * Needs to use two rules as some elements are hidden to start with and others are visible, so we need
         * to cover both cases
         */
        showelement : function(element) {
            YAHOO.util.Dom.removeClass(element, 'hidden');
            YAHOO.util.Dom.addClass(element, 'nothidden');
        },

        /**
         * hides the select again once the edit icon is clicked again. If event is a string, it is
         * coming from the ajax call
         */
        hideelement : function(element) {
            YAHOO.util.Dom.addClass(element, 'hidden');
            YAHOO.util.Dom.removeClass(element, 'nothidden');
        }
        	
}   	
        	
        
        
        
        

 
M.ilp_mis_attendance_plugin_byclass.init = function(Y,statusval) {

	var submitbut 	= document.getElementById('ilp_mis_attendance_plugin_byclass_submit');
	var classform 	= document.getElementById('ilp_mis_attendance_plugin_byclass_form');
	M.ilp_mis_attendance_plugin_byclass.hideelement(submitbut);
	
	document.getElementById('ilp_mis_attendance_plugin_byclass_course').addEventListener(
		     'change',
		     function() {classform.submit();},
		     false
	);
	
	document.getElementById('ilp_mis_attendance_plugin_byclass_month').addEventListener(
		     'change',
		     function() {classform.submit();},
		     false
	);
	
	
	/*
	//hide select and submit button 
	 
    var statusform 	= document.getElementById('changestatus');
    var userstatus 	= document.getElementById('user_status');
    var statusform 	= document.getElementById('studentstatusform');
	
    M.ilp_dashboard_student_info.hideelement(statusform);
    
    M.ilp_dashboard_student_info.showelement(userstatus);
    M.ilp_dashboard_student_info.showelement(editicon);
    M.ilp_dashboard_student_info.hideelement(statusform);
    
	YAHOO.util.Event.addListener("edit_userstatus_icon", "click", M.ilp_dashboard_student_info.addselect);
	
	//add the onchange event to the select button
	document.getElementById('select_userstatus').addEventListener(
		     'change',
		     function() {M.ilp_dashboard_student_info.save_userstatus(this.value)},
		     false
		  );
*/
};


