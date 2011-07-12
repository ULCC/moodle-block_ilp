M.ilp_standard_functions = {
		
		init:	function() {
			
		},
		
		printfunction : function() {
			window.print();
		},
		
		/**
		 * Submits a form using AJAX and loads the result into the page.
		 * 
		 * @param elem_id The id of the element to serve the request into
		 * @param url The url of the request
		 * @return
		 */
		 ajax_submit : function (form_id, elem_id, url) {

		    var callback = {
		        // if the action is successful then load the content into the page
		        success: function(o) {
		            document.getElementById(elem_id).innerHTML = o.responseText;
		            parse_scripts(elem_id);
		        },
		        // if it failed then do nothing
		        failure: function(o) {
		            //alert("ERROR: The AJAX request didn't work");
		        }
		    }

		    // get the form object
		    var formObject = document.getElementById(form_id);

		    // fetch the form contents
		    YAHOO.util.Connect.setForm(formObject);
		    
		    // submit the form
			YAHOO.util.Connect.asyncRequest('POST', url.replace(/&amp;/g, '&'), callback);
			
		    // return false to block the anchor firing
		    return false;
		}
}

