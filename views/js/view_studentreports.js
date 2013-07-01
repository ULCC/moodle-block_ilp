
M.ilp_view_studentreports = {

    // params from PHP
    open_image : null,
    closed_image : null,
    Y : null,

    init: function(Y,open_image, closed_image, open_text, close_text) {
        this.Y  =   Y;

        var toggle = Y.all('.entry_toggle');
        var assoc_entries = new Array();
        toggle.each( function (tog) {
            tog.setStyle('cursor', 'pointer');
            var toggleid = tog.get('id');
            var assoc_entry = Y.one('#' + toggleid + '_entry');
            if (assoc_entry) {
                assoc_entries[toggleid] = assoc_entry;
                assoc_entry.hide();
                tog.addClass('entry-hidden');
                tog.setStyle('background', 'url(\'' + closed_image + '\') no-repeat left center');
                tog.setStyle('padding-left', '15px');
                tog.setAttribute('title', open_text);
                tog.on('click', function() {
                    if (tog.hasClass('entry-hidden')) {
                        assoc_entry.show();
                        tog.removeClass('entry-hidden');
                        tog.setAttribute('title', close_text);
                        tog.setStyle('background', 'url(\'' + open_image + '\') no-repeat left center');
                    } else {
                        assoc_entry.hide();
                        tog.addClass('entry-hidden');
                        tog.setAttribute('title', open_text);
                        tog.setStyle('background', 'url(\'' + closed_image + '\') no-repeat left center');
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
    }

}


