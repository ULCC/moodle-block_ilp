
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

M.ilp_standard_functions = {

    init:    function() {

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
                M.ilp_standard_functions.parse_scripts(elem_id);
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

                res = o.responseText;

                document.getElementById(elem_id).innerHTML = res;//"<span id='user_status' class='hidden' style='color:" + res[0] + "'>" + res[1] + "</span>";
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
    parse_scripts    : function(elementid) {

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
