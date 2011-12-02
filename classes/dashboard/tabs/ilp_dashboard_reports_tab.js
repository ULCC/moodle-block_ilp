

// global variables
var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;

// hide the accordions from view while the page is being rendered
//Dom.addClass('content', 'hideaccordion');

function get_height(elem) {
	// work out the height of the rendered element minus the extra bits 
	var padding = parseFloat(Dom.getStyle(elem, "padding-top")) + parseFloat(Dom.getStyle(elem, "padding-bottom"));
	var border = parseFloat(Dom.getStyle(elem, "border-top-width")) + parseFloat(Dom.getStyle(elem, "border-bottom-width"));
	//additional check added as IE would sometimes return isNaN
	if (isNaN(border)) border = 0;
	
	return elem.offsetHeight - padding - border;
}

/**
 * Animates the opening and closing of accordions.
 * 
 * @param elem
 * @param from
 * @param to
 * @return
 */
function toggle_container(elem, from, to) {
	
	// disable the onclick so it can't be pressed twice
	elem.onclick = null;

	// add the current id to the location bar
	//window.location.href = new RegExp("[^#]+").exec(window.location.href)+'#'+elem.id;;
	
	// get the top level div for the page
	var page = Dom.get('page');
	
	// get the container to animate
	var container = Dom.get(elem.id+'_container');
	
	if(to == 0) {
		// fix the height of the page so the animation isn't screwy
		Dom.setStyle(page, "height", get_height(page)+"px");
		
		// reset the desired height in case ajax has expanded the content
		from = get_height(container);

		// add the closed icon
		document.getElementById(elem.id+'_icon').setAttribute('src', M.ilp_dashboard_reports_tab.closed_image);
		
		// set the overflow to hidden on the container so we don't get scroll bars
		Dom.setStyle(container, "overflow", "hidden");
		
	} else {
		// add the open icon
		document.getElementById(elem.id+'_icon').setAttribute('src', M.ilp_dashboard_reports_tab.open_image);
	}

	// show the hidden div
	Dom.setStyle(container, "display", "block");	
	
	// set the animation properties
	var attributes = { height: { from: from, to: to} };
	
	// create the animation object
	var anim = new YAHOO.util.Anim(elem.id+'_container', attributes, Math.abs(from-to)/1000);

	// set the oncomplete callback
	anim.onComplete.subscribe(function() {
		// restore the onclick
		elem.onclick = function() { toggle_container(this, to, from); };

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


/**
 * Initialisation function that sets up the javascript for the page.
 */
M.ilp_dashboard_reports_tab = {
    // params from PHP
    open_image : null,
    closed_image : null,

    init : function(Y, open_image, closed_image) {

    	this.open_image = open_image;
		this.closed_image = closed_image;

		var heights = new Array();

		// get all the accordion headers
		var headers = Dom.getElementsByClassName('commentheading', 'h3');

		// get the currently selected accordion
		var current = new RegExp("#(.+)").exec(window.location.href);

		for(i=0; i<headers.length; i++) {
			
			//cjheck if the _selector div exists if it doesn't there are no comments and thus no need for the 
			//onclick
			if (document.getElementById(headers[i].id+'_container') != null) {
				// get the height of the container element
				heights[headers[i].id] = get_height(Dom.get(headers[i].id+'_container'));
	
				Dom.setStyle(Dom.get(headers[i].id+'_container'),"hiddencontainer");
				
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
	
					// add the closed icon
					document.getElementById(headers[i].id+'_icon').setAttribute('src', closed_image);
				} else {
					// set the onclick to close the container
					headers[i].onclick = function() { toggle_container(this, heights[this.id], 0); };
	
					// add the open icon
					document.getElementById(headers[i].id+'_icon').setAttribute('src', open_image);
				}
			}
		}

		// allow the accordions to be seen now that rendering is complete
		Dom.removeClass('content', 'hideaccordion');
		
		if (document.getElementById('reportstateselect') != 'null') {
			//add the onchange event to the select button
			document.getElementById('reportstateselect').addEventListener(
				     'change',
				     function() {},
				     false
				  );			
			
			
		}
		

		
		
	}

}

