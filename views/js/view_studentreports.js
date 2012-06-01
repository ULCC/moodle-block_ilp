var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;

M.ilp_view_studentreports = {

    // params from PHP
    open_image : null,
    closed_image : null,

    init: function(Y, open_image, closed_image) {

        this.open_image = open_image;
        this.closed_image = closed_image;

       var heights = new Array();

       // get all the entry links
       var toggle = Dom.getElementsByClassName('entry_toggle');

        for(i=0; i<toggle.length; i++)   {

                // get the height of the container element
                heights[toggle[i].id] = M.ilp_standard_functions.get_height(Dom.get(toggle[i].id+'_entry'));

                // set the cursor style so the user can see this is clickable
                Dom.setStyle(toggle[i], "cursor", "pointer");

                // create the img icon and insert it into the start of the header
                img = document.createElement('img');
                img.setAttribute('id', toggle[i].id+'_icon');
                img.setAttribute('class', 'collapse');
                toggle[i].insertBefore(img, document.getElementById(toggle[i].id).firstChild);

                toggle[i].onclick = function() {
                    M.ilp_view_studentreports.toggle_container(this, heights[this.id], 0);
                 };

                // close and hide the container
                //Dom.setStyle(Dom.get(toggle[i].id+'_entry'), "display", "none");
                //Dom.setStyle(Dom.get(toggle[i].id+'_entry'), "overflow", "hidden");
                //Dom.setStyle(Dom.get(toggle[i].id+'_entry'), "height", "0px");

                // add the closed icon
                document.getElementById(toggle[i].id+'_icon').setAttribute('src', this.open_image);
        }

        // get the expand all link
        var expandall = document.getElementById('studentreport_expandall');

        expandall.onclick   =   function()  {

            console.log('expand all');
            // get all the entry links
            var toggle = Dom.getElementsByClassName('entry_toggle');

            for(i=0; i<toggle.length; i++)   {
                // get the height of the container element
                heights[toggle[i].id] = M.ilp_standard_functions.get_height(Dom.get(toggle[i].id+'_entry'));

                //if the entry window is closed open it
                if (heights[toggle[i].id]   == 0) {
                    console.log('expanding');
                    M.ilp_view_studentreports.toggle_container(toggle[i], 0, heights[this.id]);
                }
            }
        }


        // get the expand all link
        var collapseall = document.getElementById('studentreport_collapseall');

        collapseall.onclick   =   function()  {
            // get all the entry links
            var toggle = Dom.getElementsByClassName('entry_toggle');

            for(i=0; i<toggle.length; i++)   {
                // get the height of the container element
                heights[toggle[i].id] = M.ilp_standard_functions.get_height(Dom.get(toggle[i].id+'_entry'));

                //if the entry window is closed open it
                if (heights[toggle[i].id]   > 0) {
                    M.ilp_view_studentreports.toggle_container(toggle[i], heights[toggle[i].id], 0 );
                }
            }
        }



        stateselector   =   Dom.get('reportstateselect');

        stateselector.onchange  =   function () {

            console.log(stateselector.value);

            if (stateselector.value != '0')   {
                deadlineany =   Dom.get('deadline_any');
                deadlineany.checked = true;

                deadlineoverdue =   Dom.get('deadline_overdue');
                deadlineoverdue.disabled    =   true;

                deadlinecomplete =   Dom.get('deadline_complete');
                deadlinecomplete.disabled    =   true;


            } else if (stateselector.value == '0') {

                deadlineoverdue =   Dom.get('deadline_overdue');
                deadlineoverdue.disabled    =   false;

                deadlinecomplete =   Dom.get('deadline_complete');
                deadlinecomplete.disabled    =   false;
            }

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
        elem.onclick = null;

        // add the current id to the location bar
        //window.location.href = new RegExp("[^#]+").exec(window.location.href)+'#'+elem.id;;

        // get the top level div for the page
        var page = Dom.get('page');

        // get the container to animate
        var container = Dom.get(elem.id+"_entry");

        containerheight	=	M.ilp_standard_functions.get_height(container);
         if(to == 0) {

         // fix the height of the page so the animation isn't screwy
         Dom.setStyle(page, "height", M.ilp_standard_functions.get_height(page)+"px");

         // reset the desired height in case ajax has expanded the content
         from = M.ilp_standard_functions.get_height(container);


            // add the closed icon
            document.getElementById(elem.id+'_icon').setAttribute('src', this.closed_image);

         // set the overflow to hidden on the container so we don't get scroll bars
         Dom.setStyle(container, "overflow", "hidden");

         } else {
          //add the open icon
            document.getElementById(elem.id+'_icon').setAttribute('src', this.open_image);
         }

         // show the hidden div
         Dom.setStyle(container, "display", "block");

         // set the animation properties
         var attributes = { height: { from: from, to: to} };

         // create the animation object
         var anim = new YAHOO.util.Anim(elem.id+"_entry", attributes, Math.abs(from-to)/1000);

         // set the oncomplete callback
         anim.onComplete.subscribe(function() {

         // restore the onclick
         elem.onclick = function() { M.ilp_view_studentreports.toggle_container(this, to, from); };

         if(to == 0) {

         // hide the container
         Dom.setStyle(container, "display", "none");

         // allow the page size to drop back now the animation is complete
         Dom.setStyle(page, "height", "auto");

         } else {
         // set the height to auto so it can grow with new ajax content
         Dom.setStyle(container, "height", "auto");

         // set the overflow to auto so we can see any expanded content
         Dom.setStyle(container, "overflow", "auto");
         }

         });

         // do it
         anim.animate();

    }


}


