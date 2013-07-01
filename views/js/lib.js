
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

        formObject  =   Y.one('#'+form_id);

        var callback = {
            // if the action is successful then load the content into the page
            success: function(id,o,args) {
                Y.one('#'+elem_id).setHTML(o.responseText);
                M.ilp_standard_functions.parse_scripts(elem_id);
            },
            // if it failed then do nothing
            failure: function(id,o,args) {
                //alert("ERROR: The AJAX request didn't work");
            }
        }

        var cfg	=	{
            on: {
                success: callback.success,
                failure: callback.failure
            },
            form: {
                id: formObject
            },
            context: callback
        };

        Y.io(url.replace(/&amp;/g, '&'),cfg);

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
            success: function(id,o,args) {
                res = o.responseText;
                Y.one('#'+elem_id).setHTML(res);
                M.ilp_standard_functions.parse_scripts(elem_id);
            },
            // if it failed then do nothing
            failure: function(o) {
                //alert("ERROR: The AJAX request didn't work");
            }
        };

        var cfg	=	{
            on: {
                success: callback.success,
                failure: callback.failure
            },
            context: callback
        };

        Y.io(url.replace(/&amp;/g, '&'),cfg);

        // return false to block the anchor firing
        return false;
    },

/**
 * Fetch some url and discard the result.
 * used to trigger mis caching in the target.
 */
    preload :  function(Y,url)
    {
        var callback = {
            //dummy
            success: function(id,o,args) {
            },
            // if it failed then do nothing
            failure: function(o) {
            }
        };

        var cfg =       {
            on: {
                success: callback.success,
                failure: callback.failure
            },
            context: callback
        };

        Y.io(url.replace(/&amp;/g, '&'),cfg);

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


    /**
     * Calculates the height attribute of a rendered element.
     *
     * @param elem
     * @return
     */
        /*
    get_height  : function(element) {
        // work out the height of the rendered element minus the extra bits
        var padding = parseFloat(element.getStyle("padding-top")) + parseFloat(element.getStyle("padding-bottom"));
        var border = parseFloat(element.getStyle("borderTopWidth")) + parseFloat(element.getStyle("borderBottomWidth"));
        //additional check added as IE would sometimes return isNaN
        if (isNaN(border)) border = 0;
        if (isNaN(padding)) padding = 0;

        return element.get('offsetHeight') - padding - border;
    },
*/
    /**
     * Calculates the height attribute of a rendered element.
     *
     * @param elem
     * @return
     */
        /*
    get_width : function(element) {

        // work out the width of the rendered element minus the extra bits
        var padding = parseFloat(element.getStyle("padding-left")) + parseFloat(element.getStyle("padding-right"));
        var border = parseFloat(element.getStyle("border-left")) + parseFloat(element.getStyle("border-right"));

        //additional check added as IE would sometimes return isNaN
        if (isNaN(border)) border = 0;

        return element.get('offsetWidth') - padding - border;
    }
    */

}
