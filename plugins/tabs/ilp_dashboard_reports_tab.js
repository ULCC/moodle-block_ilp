

/**
 * Initialisation function that sets up the javascript for the page.
 */
M.ilp_dashboard_reports_tab = {
    // params from PHP
    //open_image : null,
    //closed_image : null,
    Y : null,

    init : function(Y, openimage, closedimage, alternative_selector) {
        this.Y  =   Y;

        var my_container = '_container';

        var heights = new Array();
        var all_selector = '.view-comments';

        if (alternative_selector !== undefined) {
            all_selector = alternative_selector;
        }
        var all_comments = Y.all(all_selector); //get all the comments div
        for(var i = 0; i < all_comments._nodes.length; i++){
            var my_el = all_comments._nodes[i];
            var my_yui_el = Y.one(my_el);
            if (my_yui_el.hasClass('comment-js-added')) {
                continue;
            }
            var my_header = my_el.getElementsByClassName('commentheading');
            var my_item = my_header[0].id;
            var my_selector = '#';
            my_selector = my_selector.concat(my_item,my_container);
            headercontainer  =  Y.one(my_selector);
            if(headercontainer._node.childElementCount> 0){
                my_yui_el.one('.heading-switch-parent').prepend(M.ilp_dashboard_reports_tab.get_view_comments(my_item,headercontainer));
                headercontainer._hide();
                my_el.className += ' comment-js-added';
            }
        }
	},
    get_view_comments: function(el,my_container){
        var my_new_element          = document.createElement("span");
        my_new_element.name         = "view_comments";
        my_new_element.className    = "view_all_comments";
        my_new_element.id           = el+'_view_comments';
        my_new_element.innerHTML    = 'View Comments';
        my_new_element.onclick      = function (){ M.ilp_dashboard_reports_tab.show_hide(my_new_element, my_container);};
        return my_new_element;
    },
    show_hide: function(my_el,my_container){
        if(my_container._isHidden()){
            my_el.className = 'hide_all_comments';
            my_el.innerHTML = 'Hide Comments';
            my_container._show();
        }else{
            my_el.className = 'view_all_comments';
            my_el.innerHTML = 'Show Comments';
            my_container._hide();
        }
    }
}

