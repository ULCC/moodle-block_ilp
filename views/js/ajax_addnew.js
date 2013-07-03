
M.ilp_ajax_addnew = {

    // params from PHP
    Y : null,
    root : null,

    init: function(Y, root) {
        this.Y  =   Y;
        this.root = root;
        var commentadds = Y.all('.add-comment-ajax');
        commentadds.each( function (commentadd) {
            commentadd.setStyle('cursor', 'pointer');
            commentadd.on('click', function() {
                var entryid = commentadd.get('id');
                var report_id = Y.one('.' + entryid + '-report_id').get('text');
                var user_id = Y.one('.' + entryid + '-user_id').get('text');
                var selectedtab = Y.one('.' + entryid + '-selectedtab').get('text');
                var tabitem = Y.one('.' + entryid + '-tabitem').get('text');
                var course_id = Y.one('.' + entryid + '-course_id').get('text');
                var url_params = 'report_id=' + report_id + '&user_id=' + user_id + '&selectedtab=' + selectedtab + '&tabitem=' + tabitem + '&courseid=' + course_id;
                var cfg = {
                    method: "POST",
                    on: {
                        success : function(id, o, args) {
                            var formarea = Y.one('.add-form-' + entryid);
                            var response = Y.JSON.parse(o.responseText)
                            var form = Y.Node.create(response.html); // Create a Node from the HTML
                            formarea.setHTML(form);
                            scriptel = document.createElement('script'); // Create a <script> tag
                            scriptel.textContent = response.script; // Put the Javascript inside the script tag
                            document.body.appendChild(scriptel); // Add the script tag to the page.

                            Y.one('.add-form-' + entryid + ' ' + '#id_cancel').set('disabled', 'disabled');
                            YUI().use('event', function (Y) {
                                Y.one('#mform1').on('submit', function (e) {
                                    // Whatever else you want to do goes here
                                    M.ilp_ajax_addnew.submit_form(e, this._node, formarea);
                                });
                            });
                        }
                    }
                };
                var numerical_entry_id = entryid.replace('ajax_com-', '');
                Y.io(root + '/blocks/ilp/actions/edit_entrycomment.ajax.php?entry_id=' + numerical_entry_id + '&' + url_params, cfg);
            });
        });
        /**/
    },
    submit_form: function(e, mform, formarea) {
        var Y = this.Y;

        // Stop the form submitting normally
        e.preventDefault();

        // Form serialisation works best if we get the form using getElementById, for some reason
        var form = Y.one('#mform1')._node;
        var formwrapper =new Object();
        formwrapper.id = 'mform1';

        // Send the request
        Y.io(this.root + '/blocks/ilp/actions/edit_entrycomment.ajax.php?entry_id=4&report_id=1&user_id=202&selectedtab=7&tabitem=7&courseid=3&process=1', {
            method: "POST",
            on: {
                success: function(id, o) {
                    console.log(o);
                    formarea.setHTML("");
                }
            },
            form: formwrapper,
            context: this
        });

    }


}


