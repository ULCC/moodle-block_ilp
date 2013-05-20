
M.ilp_view_studentreports = {

    // params from PHP
    open_image : null,
    closed_image : null,
    Y : null,

    init: function(Y,open_image, closed_image) {
        this.Y  =   Y;
        this.open_image = open_image;
        this.closed_image = closed_image;

        var heights = new Array();

        var widths = new Array();

       // get all the entry links
       var toggle = Y.all('.entry_toggle');

        toggle.each( function (tog) {

            toggle_id   = tog.get('id');



            // get the height of the container element
            heights[toggle_id]  =    M.ilp_standard_functions.get_height(Y.one('#'+toggle_id+'_entry'));

            //get width
            widths[toggle_id]   =   M.ilp_standard_functions.get_width(Y.one('#'+toggle_id+'_entry'));

            tog.setStyle("cursor", "pointer");

            // create the img icon and insert it into the start of the header
            var img = Y.Node.create('<img id="'+toggle_id+'_icon" class="collapse">');

            //the before function does not seem to be functioning correctly as the image is being inserted after
            tog.insert(img,'before');

            Y.on('click',function () {M.ilp_view_studentreports.toggle_container(tog, heights[toggle_id], 0);},tog );

            // add the open icon
            Y.one('#'+toggle_id+'_icon').set('src', open_image);

        })

        var expandall = Y.one('#studentreport_expandall');

        Y.on('click', function () {
            var toggle =  Y.all('.entry_toggle');

            toggle.each( function (tog){

                toggle_id   = tog.get('id');

                // get the height of the container element
                heights[toggle_id]  =    M.ilp_standard_functions.get_height(Y.one('#'+toggle_id+'_entry'));

                //if the entry window is closed open it
                if (heights[toggle_id]   == 0) {
                    M.ilp_view_studentreports.toggle_container(tog, 0, heights[toggle_id])
                }
            });
        },
        expandall)


        var collapseall = Y.one('#studentreport_collapseall');

        Y.on('click', function () {

            var toggle =  Y.all('.entry_toggle');

            toggle.each(function(tog) {


                toggle_id   = tog.get('id');

                elem    = Y.one('#'+toggle_id+'_entry');

                // get the height of the container element
                heights[toggle_id]  =    M.ilp_standard_functions.get_height(elem);

                //if the entry window is closed open it
                if (heights[toggle_id]   > 0) {
                    M.ilp_view_studentreports.toggle_container(tog, heights[toggle_id], 0);
                }
            })
        }, collapseall);


        var stateselector = Y.one('#reportstateselect');

        if (typeof(stateselector) != "undefined" && stateselector != null) {

            Y.on('change',
                function() {
                    deadlineany = Y.one("#deadline_any");
                    deadlineoverdue = Y.one('#deadline_overdue');
                    deadlinecomplete = Y.one('#deadline_complete');

                    if (stateselector.get('value') != '0')   {
                        deadlineany.set('checked',true);
                        deadlineoverdue.set('disabled',true);
                        deadlinecomplete.set('disabled',true);

                    } else if (stateselector.value == '0') {
                        deadlineoverdue.set('disabled',true);
                        deadlinecomplete.set('disabled',true);
                    }
                }, stateselector);
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


        // global variables

        // disable the onclick so it can't be pressed twice
        elem.detach('click');

        // add the current id to the location bar
        //window.location.href = new RegExp("[^#]+").exec(window.location.href)+'#'+elem.id;;

        // get the top level div for the page
        var page =  Y.one('#page');

        element_id   = elem.get('id');


        // get the container to animate
        var container = Y.one('#'+element_id+"_entry");

        containerheight	=	M.ilp_standard_functions.get_height(container);
         if(to == 0) {
            // fix the height of the page so the animation isn't screwy
             page.setStyle("height",M.ilp_standard_functions.get_height(page)+"px");

             // reset the desired height in case ajax has expanded the content
             from = M.ilp_standard_functions.get_height(container);

             elemicon    =   Y.one('#'+element_id+'_icon');

             // add the closed icon
             elemicon.set('src',this.closed_image);

             // set the overflow to hidden on the container so we don't get scroll bars
              container.setStyle("overflow", "hidden");

         } else {
             //add the open icon
             elemicon   =   Y.one('#'+element_id+'_icon');
             elemicon.set('src', this.open_image);
         }

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
            elem.on('click',function () { M.ilp_view_studentreports.toggle_container(elem, to, from);});

            if (to == 0)   {
                container.setStyle("display","none");
                page.setStyle("height","auto");
            }   else {
                container.setStyle("overflow","auto");
                container.setStyle("height","auto");
            }

        });

        animation.run();

    }


}


