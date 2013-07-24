

/**
 * Initialisation function that sets up the javascript for the page.
 */
M.ilp_dashboard_vault_tab = {
    Y : null,

    init : function(Y) {
        //at first need to take care of comments
        var comments_counter = Y.all('.comment_counter')._nodes;
        for(var i=0; i <comments_counter.length; i++){
            var this_comments_counter = comments_counter[i];
            var my_comments_container = Y.one('#comment_for_'+ this_comments_counter.id);window.console.log(this_comments_counter.id);
            var new_el = this_comments_counter.appendChild(
                M.ilp_dashboard_vault_tab.get_element_show_hide_comments(this_comments_counter.id,my_comments_container)
            );
            if (my_comments_container) {
                my_comments_container.hide();
            }
        }
        // now take care of entries
        var entry_counter = Y.all('.vault_report_entry_count')._nodes;
        for(var i=0; i <entry_counter.length; i++){
            var this_counter = entry_counter[i];
            var my_container = Y.one('#vault_show_entries_'+ this_counter.id);
            var new_el = this_counter.appendChild(
                M.ilp_dashboard_vault_tab.get_element_show_hide(this_counter.id,my_container)
            );
            my_container.hide();
        }
	},
    get_element_show_hide: function(el,my_container){
        var my_new_element          = document.createElement("div");
        my_new_element.name         = "Show Hide";
        my_new_element.className    = "vault_show_entries";
        my_new_element.id           = 'vault_show_entries_'+el;
        my_new_element.innerHTML    = 'Show';
        my_new_element.onclick      = function (){ M.ilp_dashboard_vault_tab.show_hide_entries(my_new_element, my_container);};
        return my_new_element;
    },
    show_hide_entries: function(my_el,my_container){
        if(my_container._isHidden()){
            my_container._show();
            my_el.className = 'vault_hide_entries';
            my_el.innerHTML = 'Hide';
        }else{
            my_container._hide();
            my_el.className = 'vault_show_entries';
            my_el.innerHTML = 'Show';
        }
    },

    get_element_show_hide_comments: function(el,my_container){
        var my_new_element          = document.createElement("div");
        my_new_element.name         = "Show Hide";
        my_new_element.className    = "vault_show_entries_comments";
        my_new_element.id           = 'vault_show_entries_comments_'+el;
        my_new_element.innerHTML    = 'Show';
        my_new_element.onclick      = function (){ M.ilp_dashboard_vault_tab.show_hide_comments(my_new_element, my_container);};
        return my_new_element;
    },
    show_hide_comments: function(my_el,my_container){
        if (my_container) {
            if(my_container._isHidden()){
                my_container._show();
                my_el.className = 'vault_hide_entries_comments';
                my_el.innerHTML = 'Hide';
            }else{
                my_container._hide();
                my_el.className = 'vault_show_entries_comments';
                my_el.innerHTML = 'Show';
            }
        }
    }

}

