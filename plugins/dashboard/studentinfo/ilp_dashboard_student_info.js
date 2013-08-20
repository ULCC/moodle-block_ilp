/**
 * Javascript for the onchange functions in the ilp_dashboard_student_info plugin
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

M.ilp_dashboard_student_info = {

        /**
         * When the edit icon is clicked, this will unhide the select thing and show the current DB grade.
         * Needs to use two rules as some elements are hidden to start with and others are visible, so we need
         * to cover both cases
         */
        showelement : function(element) {
            ele     =   Y.one('#'+element);
            if (ele) {
                ele.removeClass('hiddenelement');
                ele.addClass('visbileelement');
            }
        },

        /**
         * hides the select again once the edit icon is clicked again. If event is a string, it is
         * coming from the ajax call
         */
        hideelement : function(element) {
            ele     =   Y.one('#'+element);
            if (ele) {
                ele.removeClass('visbileelement');
                ele.addClass('hiddenelement');
            }
        },


        save_userstatus : function () {

            sidelement  = Y.one('#student_id');
            student_id  =   sidelement.get('value');

            select_userstatus  = Y.one('#select_userstatus');
            statusvalue  =   select_userstatus.get('value');

            M.ilp_dashboard_student_info.showelement('studentlistloadingicon');

            var cfg	=	{
                on: {
                    success: M.ilp_dashboard_student_info.callback.success,
                    failure: M.ilp_dashboard_student_info.callback.failure
                },
                data:   'ajax=true&student_id='+student_id+'&select_userstatus='+statusvalue,
                context: M.ilp_dashboard_student_info.callback
            };

            Y.io('save_userstatus.php',cfg);

            ajaxinprogress = true;
        },
        save_usersecondstatus : function () {
            var sidelement  = Y.one('#student_id');
            var student_id  =   sidelement.get('value');
            var select_userstatus  = Y.one('#select_usersecondstatus');
            var statusvalue  =   select_userstatus.get('value');
            if (statusvalue) {
                M.ilp_dashboard_student_info.showelement('secondstsloadingicon');

                var cfg	=	{
                    on: {
                        success: function(id,o,args) {
                            var response = Y.JSON.parse(o.responseText);
                            Y.all('.ilp_element_plugin_warningstatus').setHTML(response);
                            M.ilp_dashboard_student_info.hideelement('secondstsloadingicon');
                        }
                    },
                    data:   'student_id=' + student_id + '&secondstatus_val='+statusvalue,
                    context: M.ilp_dashboard_student_info.callback
                };

                Y.io('save_secondstatus.ajax.php',cfg);
            }
        },

        addselect : function () {

            M.ilp_dashboard_student_info.hideelement('edit_userstatus_icon');
            M.ilp_dashboard_student_info.hideelement('user_status');
            M.ilp_dashboard_student_info.showelement('select_userstatus');

            var studentstatussub    = Y.one('#studentstatussub');
            studentstatussub.setStyle('visibility','hidden');
        },

        callback	:	{
        	success : function(id,o,args) {
                var response = Y.JSON.parse(o.responseText);
                Y.one('.ajaxstatuschange_wrapper').setHTML(response.middle_studentinfo_block);
                M.ilp_dashboard_student_info.hideelement('studentlistloadingicon');
                M.ilp_dashboard_student_info.init(Y);
        	},

        	failure : function() {

        	}
        }

}


M.ilp_dashboard_student_info.init = function(Y,statusval) {
	//hide select and submit button 

    M.ilp_dashboard_student_info.hideelement('studentstatussub');
    var select_userstatus = Y.one('select#select_userstatus');
    if (select_userstatus) {
        select_userstatus.on('change',function () {M.ilp_dashboard_student_info.save_userstatus()},'#select_userstatus' );
    }

    var select_usersecondstatus = Y.one('select#select_usersecondstatus');
    if (select_usersecondstatus) {
        select_usersecondstatus.on('change',function () {M.ilp_dashboard_student_info.save_usersecondstatus()},'#select_usersecondstatus' );
    }

};
