

/**
 * Initialisation function that sets up the javascript for the page.
 */
M.ilp_dashboard_reports_tab = {
    // params from PHP
    open_image : null,
    closed_image : null,
    Y : null,

    init : function(Y, open_image, closed_image) {
        this.Y  =   Y;
    	this.open_image = open_image;
		this.closed_image = closed_image;

        var my_container = '_container';

        var heights = new Array();
        var all_comments = Y.all('.view-comments'); //get all the comments div
        for(var i = 0; i < all_comments._nodes.length; i++){
            var my_el = all_comments._nodes[i];
            var my_header = my_el.getElementsByClassName('commentheading');
            var my_item = my_header[0].id;
            var my_selector = '#';
            my_selector = my_selector.concat(my_item,my_container);
            headercontainer  =  Y.one(my_selector);
            if(headercontainer){
                var new_el = my_el.appendChild(M.ilp_dashboard_reports_tab.get_view_comments(my_item,headercontainer));
                headercontainer._hide();
            }
        }
	},
    get_view_comments: function(el,my_container){
        var my_new_element          = document.createElement("div");
        my_new_element.name         = "view_comments";
        my_new_element.className    = "view_all_comments";
        my_new_element.id           = el+'_view_comments';
        my_new_element.innerHTML    = 'View Comments';
        my_new_element.onclick      = function (){ M.ilp_dashboard_reports_tab.show_hide(my_new_element, my_container);};
        return my_new_element;
    },
    show_hide: function(my_el,my_container){
        if(my_container._isHidden()){
            my_el.classList.add('hide_all_comments');
            my_el.classList.remove('view_all_comments');
            my_el.innerHTML = 'Hide Comments';
            my_container._show();
        }else{
            my_el.classList.add('view_all_comments');
            my_el.classList.remove('hide_all_comments');
            my_el.innerHTML = 'Show Comments';
            my_container._hide();
        }
    }
}

