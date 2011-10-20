/**
 * Javascript for the onchange functions in the ilp_dashboard_student_info plugin
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

var editicon	= document.getElementById('edit_userstatus_icon');
var userstatus 	= document.getElementById('user_status');


M.ilp_dashboard_student_info = {
		
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
        },
		
        
        save_userstatus : function () {
        	
        	// get course_id and candidate_id from the form
            var student_id = document.getElementById('student_id').value;

            ajaxinprogress = true;
            
            YAHOO.util.Connect.asyncRequest('POST',
                                            'save_userstatus.php',  //ok as long as  save_user_status.php is in the same directory as the parent script ie /actions ... otherwise we will need to consider putting a fully qualified url here
                                            M.ilp_dashboard_student_info.callback,
                                            'ajax=true&student_id='+student_id+'&select_userstatus='+this.value);
        	
        },
        
        addselect : function () {
            M.ilp_dashboard_student_info.hideelement(document.getElementById('edit_userstatus_icon'));
            M.ilp_dashboard_student_info.hideelement(document.getElementById('user_status'));
            M.ilp_dashboard_student_info.showelement(document.getElementById('studentstatusform'));
            document.getElementById('studentstatussub').style.visibility='hidden';
        },
        
        callback	:	{
        	success : function(o) {
        		var statusdiv			=	document.getElementById('user_status');
        		statusdiv.innerHTML		=	o.responseText;
        		
        		M.ilp_dashboard_student_info.showelement(document.getElementById('user_status'));
        		M.ilp_dashboard_student_info.showelement(document.getElementById('edit_userstatus_icon'));

        		M.ilp_dashboard_student_info.hideelement(document.getElementById('studentstatusform'));
        		
        		//set value for the select to 
        			
        	},
        	
        	failure : function() {
        		
        	}
        }
}   	

 
M.ilp_dashboard_student_info.init = function(Y,statusval) {
	//hide select and submit button 
    var statusform 	= document.getElementById('changestatus');
    var userstatus 	= document.getElementById('user_status');
    var statusform 	= document.getElementById('studentstatusform');
    var sltusrsts	= document.getElementById('select_userstatus');	 
	
    M.ilp_dashboard_student_info.hideelement(statusform);
    
    M.ilp_dashboard_student_info.showelement(userstatus);
    M.ilp_dashboard_student_info.showelement(editicon);
    
    M.ilp_dashboard_student_info.hideelement(statusform);
    
    YAHOO.util.Event.addListener("edit_userstatus_icon", "click", M.ilp_dashboard_student_info.addselect);
    
	if (typeof(document.getElementById('select_userstatus')) != 'undefined') {
		YAHOO.util.Event.addListener("select_userstatus", "change", M.ilp_dashboard_student_info.save_userstatus,document.getElementById('select_userstatus'),true);
	}
	
};


