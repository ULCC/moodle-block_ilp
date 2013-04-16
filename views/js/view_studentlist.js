M.ilp_view_studentlist = {
    init: function() {
        if (Y.one('#select_course_id') != null)   {
            Y.on('change',function () {M.ilp_view_studentlist.post_from()},'#select_course_id' );
        }

        if (Y.one('#select_group_id') != null)   {
            Y.on('change',function () {M.ilp_view_studentlist.post_from()},'#select_group_id' );
        }

        Y.on('change',function () {M.ilp_view_studentlist.post_from()},'#select_status' );

        //hide the submit button
        submitbutton  = Y.one('#coursesubmit');
        submitbutton.setStyle('visibility', 'hidden');
    },

    post_from : function() {
        //post the form
        filterform  = Y.one('#filter_form');
        filterform.submit();
    }
}


