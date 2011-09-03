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
		},
		
		
		/**
		 * Executes an AJAX request and loads the content into the page.
		 * 
		 * @param elem_id The id of the element to serve the request into
		 * @param url The url of the request
		 * @return
		 */
		 ajax_request : function(elem_id, url) {
		    var callback = {
		        // if the action is successful then load the content into the page
		        success: function(o) {

		        	res	=	o.responseText;
console.log(res);

console.log('empty');
		            document.getElementById(elem_id).innerHTML = "<span id='user_status' class='hidden' style='color:"+res[0]+"'>"+res[1]+"</span>";
		            M.ilp_standard_functions.parse_scripts(elem_id);
		        },
		        // if it failed then do nothing
		        failure: function(o) {
		            //alert("ERROR: The AJAX request didn't work");
		        }
		    }

		    // fetch the requested page
		    YAHOO.util.Connect.asyncRequest('GET', url.replace(/&amp;/g, '&'), callback);

		    // return false to block the anchor firing
		    return false;
		},
		
		/**
		 * When ajax stuff comes back and gets added via innerHTML, the inline javascripts don't get run.
		 * This will run them.
		 */
		 parse_scripts	: function(elementid) {

		    var element = document.getElementById(elementid);
		    var scripts = element.getElementsByTagName('script');

		    for (var i = 0; i < scripts.length; i++) {

		        if (window.execScript) {
		            window.execScript(scripts[i].innerHTML);
		        } else {
		            window.setTimeout(scripts[i].text, 0);
		        }
		    }
		}
}

