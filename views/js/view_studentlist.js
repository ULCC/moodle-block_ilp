M.ilp_view_studentlist = {

    init: function() {
    	
    	var select_course_id =	document.getElementById('select_course_id');
    	var select_course_id =	document.getElementById('select_course_id');
    	
    	YAHOO.util.Event.addListener(select_course_id,'change',M.ilp_view_studentlist.post_from());
    	YAHOO.util.Event.addListener('select_status','change',M.ilp_view_studentlist.post_from());

        //hide the submit button
        YAHOO.util.Dom.setStyle('coursesubmit', 'visibility', 'hidden');


    },

    post_from : function() {

        //post the form
        document.getElementById('filter_form').submit();
    }

}


