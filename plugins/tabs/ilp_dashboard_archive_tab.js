


// hide the accordions from view while the page is being rendered
//Dom.addClass('content', 'hideaccordion');






/**
 * Initialisation function that sets up the javascript for the page.
 */
M.ilp_dashboard_archive_tab = {
    // params from PHP
    open_image : null,
    closed_image : null,

    init : function(Y, open_image, closed_image) {

    	this.open_image = open_image;
		this.closed_image = closed_image;

        var heights = new Array();

        // get all the accordion headers
        //var headers = Dom.getElementsByClassName('commentheading', 'h3');

        var headers = Y.all('.commentheading');

        // get the currently selected accordion
        var current = new RegExp("#(.+)").exec(window.location.href);

        headers.each( function (headernode)   {

            headernode_id   =   headernode.get('id');

            headercontainer  =  Y.one('#'+headernode_id+'_container');

            // get the height of the container element
            heights[headernode_id] = M.ilp_dashboard_archive_tab.get_height(headercontainer);

            // set the cursor style so the user can see this is clickable
            headernode.setStyle("cursor", "pointer");

            // create the img icon and insert it into the start of the header
            var img = Y.Node.create('<img id="'+headernode_id+'_icon" class="collapse">');
            //the before function does not seem to be functioning correctly as the image is being inserted after
            headernode.insert(img,'before');
            //headernode.insert(img,'after');

            if(!current || current[1] != headernode_id) {
                // set the onclick to open the container
                Y.on('click',function () {M.ilp_dashboard_reports_tab.toggle_container(headernode, 0, heights[headernode_id]);},headernode );
                // close and hide the container
                headercontainer.setStyle("display", "none");
                headercontainer.setStyle("overflow", "hidden");

                // add the closed icon
                Y.one('#'+headernode_id+'_icon').set('src', closed_image);
            } else {
                // set the onclick to close the container
                Y.on('click',function () { M.ilp_dashboard_reports_tab.toggle_container(headernode, heights[headernode_id], 0);},headernode );

                // add the open icon
                Y.one('#'+headernode_id+'_icon').set('src', open_image);
            }

        })

        if (Y.one('#reportstateselect') != 'null') {
            //add the onchange event to the select button
            Y.on('change',function () {},'#reportstateselect' );
        }
	},


    /**
     * Animates the opening and closing of accordions.
     *
     * @param elem
     * @param from
     * @param to
     * @return
     */
    toggle_container : function(elem, from, to) {


        // disable the onclick so it can't be pressed twice
        elem.detach('click');

        var page = Y.one('#page');

        element_id  =   elem.get('id');

        // get the container to animate
        var container   = Y.one('#'+element_id+'_container');

        if(to == 0) {
            // fix the height of the page so the animation isn't screwy
            page.setStyle("height",M.ilp_dashboard_archive_tab.get_height(page)+"px");

            // reset the desired height in case ajax has expanded the content
            from = M.ilp_dashboard_archive_tab.get_height(container);

            // add the closed icon
            Y.one('#'+element_id+'_icon').set('src', M.ilp_dashboard_archive_tab.closed_image);

            // set the overflow to hidden on the container so we don't get scroll bars
            container.setStyle("overflow", "hidden");

        } else {
            // add the open icon
            Y.one('#'+element_id+'_icon').set('src', M.ilp_dashboard_archive_tab.open_image);

        }

        // show the hidden div
        container.setStyle("display", "block");

        // create the animation object
        // set the animation properties
        var animation  = new this.Y.Anim({
            node: container,
            duration: 0.3,
            to: {height:to},
            from: {height:from}
        });


        animation.on('end', function(e){
            elem.on('click',function () { M.ilp_dashboard_archive_tab.toggle_container(elem, to, from);});

            if (to == 0)   {
                container.setStyle("display","none");
                page.setStyle("height","auto");
            }   else {
                container.setStyle("overflow","auto");
                container.setStyle("height","auto");
            }

        });

        animation.run();

    },


    get_height :    function(element) {
        // work out the height of the rendered element minus the extra bits
        var padding = parseFloat(element.getStyle("padding-top")) + parseFloat(element.getStyle("padding-bottom"));
        var border = parseFloat(element.getStyle("borderTopWidth")) + parseFloat(element.getStyle("borderBottomWidth"));
        //additional check added as IE would sometimes return isNaN
        if (isNaN(border)) border = 0;
        if (isNaN(padding)) padding = 0;
        return element.get('offsetHeight') - padding - border;
    }


}

