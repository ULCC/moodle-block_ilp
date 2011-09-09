M.ilp_view_studentlist = {

    init: function() {
        if (document.getElementById('select_course_id') != null) {
            //add the onchange event to the select drop down
            document.getElementById('select_course_id').addEventListener(
                'change',
                function() {
                    M.ilp_view_studentlist.post_from()
                },
                false
            );
        }

        document.getElementById('select_status').addEventListener(
            'change',
            function() {
                M.ilp_view_studentlist.post_from()
            },
            false
        );


        //hide the submit button
        YAHOO.util.Dom.setStyle('coursesubmit', 'visibility', 'hidden');


    },

    post_from : function() {

        //post the form
        document.getElementById('filter_form').submit();
    }

}


