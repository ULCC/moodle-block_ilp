
M.ilp_view_studentreports = {

    // params from PHP: Images and Title Text
    open_image : null,
    closed_image : null,
    Y : null,

    init: function(Y,open_image, closed_image, open_text, close_text, show_comments, hide_comments) {
        this.Y  =   Y;
        M.ilp_view_studentreports.prepare_entry_showhide(Y,open_image, closed_image, open_text, close_text);
        M.ilp_view_studentreports.prepare_comment_showhide(show_comments, hide_comments);
    },
    prepare_entry_showhide: function(Y,open_image, closed_image, open_text, close_text) {
        var toggle = Y.all('.entry_toggle');
        var assoc_entries = new Array();
        toggle.each( function (tog) {
            var toggleid = tog.get('id');
            var assoc_entry = Y.one('#' + toggleid + '_entry');
            if (assoc_entry) {
                assoc_entries[toggleid] = assoc_entry;
                assoc_entry.hide();
                assoc_entry.ancestor("tr").hide();
                tog.addClass('entry-hidden');
                tog.addClass('expand_icon');
                tog.setAttribute('title', open_text);
                tog.on('click', function() {
                    if (tog.hasClass('entry-hidden')) {
                        assoc_entry.show();
                        assoc_entry.ancestor("tr").show();
                        tog.removeClass('entry-hidden');
                        tog.setAttribute('title', close_text);
                        tog.addClass('collapse_icon');
                        tog.removeClass('expand_icon');
                    } else {
                        assoc_entry.hide();
                        assoc_entry.ancestor("tr").hide();
                        tog.addClass('entry-hidden');
                        tog.setAttribute('title', open_text);
                        tog.addClass('expand_icon');
                        tog.removeClass('collapse_icon');
                    }
                });
            }
        });
        var expandall = Y.one('#studentreport_expandall');
        var collapseall = Y.one('#studentreport_collapseall');
        expandall.on('click', function() {
            toggle.each(function(tog) {
                tog.addClass('entry-hidden');
                tog.simulate('click');
            });
        });
        collapseall.on('click', function() {
            toggle.each(function(tog) {
                tog.removeClass('entry-hidden');
                tog.simulate('click');
            });
        });
    },
    prepare_comment_showhide: function(show_comments, hide_comments) {
        var toggle = Y.all('.comment_toggle');
        toggle.each( function (tog) {
            tog.on('click', function() {
                var dom_id = tog.getData('identifier');
                var comments = Y.one('.comments-' + dom_id);
                comments.toggleClass('hiddenelement');
                if (comments.hasClass('hiddenelement')) {
                    tog.set('text', show_comments);
                } else {
                    tog.set('text', hide_comments);
                }
            });
        });
    }

}


