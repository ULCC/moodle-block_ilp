/**
 *
 * @version  2.0
 *
 * @copyright &copy; 2009-2010 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

// global variables
var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;

// hide the accordions from view while the page is being rendered
Dom.addClass('content', 'hideaccordion');

/**
 * Initialisation function that sets up the javascript for the page.
 */
M.blocks_ilp_animate_accordions = {
    // params from PHP
    open_image : null,
    closed_image : null,

    init : function(Y, open_image, closed_image) {

		this.open_image = open_image;
		this.closed_image = closed_image;

		var heights = new Array();

		// get all the accordion headers
		var headers = Dom.getElementsByClassName('headingblock', 'h3');

		// get the currently selected accordion
		var current = new RegExp("#(.+)").exec(window.location.href);

		for(i=0; i<headers.length; i++) {
			// get the height of the container element
			heights[headers[i].id] = get_height(Dom.get(headers[i].id+'_container'));

			// set the cursor style so the user can see this is clickable
			Dom.setStyle(headers[i], "cursor", "pointer");

			// create the img icon and insert it into the start of the header
			img = document.createElement('img');
			img.setAttribute('id', headers[i].id+'_icon');
			img.setAttribute('class', 'collapse');
			headers[i].insertBefore(img, document.getElementById(headers[i].id).firstChild);

			// check if this container should be closed
			if(!current || current[1] != headers[i].id) {
				// set the onclick to open the container
				headers[i].onclick = function() { toggle_container(this, 0, heights[this.id]); };

				// close and hide the container
				Dom.setStyle(Dom.get(headers[i].id+'_container'), "display", "none");
				Dom.setStyle(Dom.get(headers[i].id+'_container'), "overflow", "hidden");
				Dom.setStyle(Dom.get(headers[i].id+'_container'), "height", "0px");

				// add the closed icon
				document.getElementById(headers[i].id+'_icon').setAttribute('src', closed_image);
			} else {
				// set the onclick to close the container
				headers[i].onclick = function() { toggle_container(this, heights[this.id], 0); };

				// add the open icon
				document.getElementById(headers[i].id+'_icon').setAttribute('src', open_image);
			}
		}

		for(i=0; i<helplinks.length; i++) {
			YAHOO.util.Event.addListener(helplinks[i], "click", suppressClick);
		}

		// allow the accordions to be seen now that rendering is complete
		Dom.removeClass('content', 'hideaccordion');
	}
}