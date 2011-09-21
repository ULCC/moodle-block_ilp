M.ilp_view_studentlist = {

    init: function() {
    	
    	//ok I have removed all of this javascript as ie7 & 8 have issues with addeventlistener.
    	//And I have also tried to use YUI 2.8 event addListener which only resulted in loops on tyhe page.
    	
    	
        //if (document.getElementById('select_course_id') != null) {
            //add the onchange event to the select drop down
          //  document.getElementById('select_course_id').addEventListener(
            //    'click',
              //  function() {
              //      M.ilp_view_studentlist.post_from()
              //  },
              //  false
            //);
        //}

        //document.getElementById('select_status').addEventListener(
          //  'click',
          //  function() {
          //      M.ilp_view_studentlist.post_from()
          //  },
          //  false
        //);


        //hide the submit button
        //YAHOO.util.Dom.setStyle('coursesubmit', 'visibility', 'hidden');


    },

    post_from : function() {

        //post the form
        document.getElementById('filter_form').submit();
    }

}


