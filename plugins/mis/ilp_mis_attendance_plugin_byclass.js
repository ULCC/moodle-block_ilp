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
            element.replaceClass('hidden','nothidden');
        },

        /**
         * hides the select again once the edit icon is clicked again. If event is a string, it is
         * coming from the ajax call
         */
        hideelement : function(element) {
            element.replaceClass('nothidden','hidden');
        }
}
        	

M.ilp_mis_attendance_plugin_byclass.init = function(Y,statusval) {
    var submitbut 	=   Y.one('ilp_mis_attendance_plugin_byclass_submit');
    var classform   =   Y.one('ilp_mis_attendance_plugin_byclass_form');
    M.ilp_mis_attendance_plugin_byclass.hideelement(submitbut);

    Y.on('change',
        function () {classform.submit();},
        Y.one('#ilp_mis_attendance_plugin_byclass_course'));

    Y.on('change',
        function () {classform.submit();},
        Y.one('#ilp_mis_attendance_plugin_byclass_month'));
};


