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
	
	
    // keeps track of the outcome that the last request was sent for so that the returned grade
    // can be put in the right place

    var ajaxinprogress = false;
    

    function saveuserstatus(userstatus) {

        //get the studentstatusform
        var form = document.getElementById('studentstatusform');
        
        //get the student_id
        var student_id 	= form.student_id.value;
        
        //get the id of the modifying user
        var user_modified_id 	= form.user_modified_id.value;
        
        //get the status that has been choosen
        var status_id 	= userstatus;
        
        ajaxinprogress = true;

        YAHOO.util.Connect.asyncRequest('POST',
                                        '/blocks/ilp/actions/save_userstatus.php',
                                       // M.ilp.ilp_dashboard_student_info.callback,
                                        null,
                                        'ajax=true&student_id='+student_id+'user_id='+user_id+'status_id='+status_id);

    }
	
    return {
	
		function addselect() {
	
	        var editicon = document.getElementById('edit_userstatus_icon');
	        var statustext = document.getElementById('userstatus');
	        var select = document.getElementById('select_userstatus');
	
	        M.ilp.ilp_dashboard_student_info.hideelement(editicon);
	        M.ilp.ilp_dashboard_student_info.hideelement(statustext);
	        M.ilp.ilp_dashboard_student_info.showelement(select);
	    }
		
		/**
	     * When the edit icon is clicked, this will unhide the select box.
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
		
		
	    add_loader_icon : function(div) {
	        div.innerHTML = '<img src="/pix/i/loading_small.gif" />';
	    },
	
	    remove_loader_icon : function(div) {
	        div.innerHTML = '';
	    },
	
	    add_error_icon : function(div) {
	        div.innerHTML = '<img src="/pix/i/cross_red_big.gif" />';
	    },
	
	    add_style : function(classname, style) {
	        var S1 = document.createElement('style');
	        S1.type = 'text/css';
	        var T = classname+' { '+style+'; }';
	        T = document.createTextNode(T);
	        S1.appendChild(T);
	        document.body.appendChild(S1);
	    },
    }
})();