
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
                var formarea_icon = Y.one('.loader-icon-' + entryid + ' .ajaxloadicon');
                formarea_icon.removeClass('hiddenelement');
                var report_id = Y.one('.' + entryid + '-report_id').get('text');
                var user_id = Y.one('.' + entryid + '-user_id').get('text');
                var selectedtab = Y.one('.' + entryid + '-selectedtab').get('text');
                var tabitem = Y.one('.' + entryid + '-tabitem').get('text');
                var course_id = Y.one('.' + entryid + '-course_id').get('text');
                var url_params = 'report_id=' + report_id + '&user_id=' + user_id + '&selectedtab=' + selectedtab + '&tabitem=' + tabitem + '&courseid=' + course_id;
                var numerical_entry_id = entryid.replace('ajax_com-', '');
                var cfg = {
                    method: "POST",
                    on: {
                        success : function(id, o, args) {
                            var formarea = Y.one('.add-form-' + entryid);
                            var response = Y.JSON.parse(o.responseText)
                            var form = Y.Node.create(response.html); // Create a Node from the HTML
                            formarea_icon.addClass('hiddenelement');
                            formarea.setHTML(form);
                            scriptel = document.createElement('script'); // Create a <script> tag
                            scriptel.textContent = response.script; // Put the Javascript inside the script tag
                            document.body.appendChild(scriptel); // Add the script tag to the page.

                            var comment_send_params =new Object();
                            comment_send_params.dom_entryid = entryid;
                            comment_send_params.entryid = numerical_entry_id;
                            comment_send_params.report_id = report_id;
                            comment_send_params.user_id = user_id;
                            comment_send_params.selectedtab = selectedtab;
                            comment_send_params.tabitem = tabitem;
                            comment_send_params.course_id = course_id;
                            Y.one('.add-form-' + entryid + ' ' + '#id_cancel').set('disabled', 'disabled');
                            YUI().use('event', function (Y) {
                                Y.one('#mform1').on('submit', function (e) {
                                    var loadericon = Y.one('.add-form-' + entryid + ' .ajaxloadicon');
                                    Y.one('.add-form-' + entryid + ' .fitem_actionbuttons .felement.fgroup').prepend(loadericon);
                                    console.log(loadericon);
                                    loadericon.removeClass('hiddenelement');
                                    // Whatever else you want to do goes here
                                    M.ilp_ajax_addnew.submit_form(e, this._node, formarea, comment_send_params, loadericon);
                                });
                            });
                        }
                    }
                };

                Y.io(root + '/blocks/ilp/actions/edit_entrycomment.ajax.php?entry_id=' + numerical_entry_id + '&' + url_params, cfg);
            });
        });
        M.ilp_ajax_addnew.prepare_edits_for_ajax();
        M.ilp_ajax_addnew.prepare_deletes_for_ajax();
        /**/
    },
    submit_form: function(e, mform, formarea, comment_send_params, loadericon) {
        var Y = this.Y;

        // Stop the form submitting normally
        e.preventDefault();

        var formwrapper =new Object();
        formwrapper.id = 'mform1';

        var process_url = this.root + '/blocks/ilp/actions/edit_entrycomment.ajax.php';
        var process_params = '?entry_id=' + comment_send_params.entryid + '&report_id=' + comment_send_params.report_id + '&user_id=' + comment_send_params.user_id;
        process_params += '&selectedtab=' + comment_send_params.selectedtab + '&tabitem=' + comment_send_params.tabitem + '&courseid=' + comment_send_params.course_id + '&process=1';
        process_url += process_params;

        // Send the request
        Y.io(process_url, {
            method: "POST",
            on: {
                success: function(id, o) {
                    formarea.setHTML("");
                    var formarea_icon = Y.one('.loader-icon-' + comment_send_params.dom_entryid + ' .ajaxloadicon');
                    formarea_icon.addClass('hiddenelement');
                    var comments_container = Y.one('#entry_' + comment_send_params.entryid + '_container');
                    comments_container.setHTML(o.response);
                    var numcomments = Y.one('span.numcomments-' + comment_send_params.dom_entryid);
                    numcomments.set('text', parseInt(numcomments.get('text')) + 1);
                    loadericon.addClass('hiddenelement');
                    M.ilp_ajax_addnew.prepare_edits_for_ajax();
                    M.ilp_ajax_addnew.prepare_deletes_for_ajax();
                }
            },
            form: formwrapper,
            context: this
        });

    },
    prepare_edits_for_ajax: function() {
        var commentedits = Y.all('.edit-comment-ajax');
        commentedits.each( function (commentedit) {
            commentedit.setStyle('cursor', 'pointer');
            commentedit.on('click', function() {
                var comment_id_dom = commentedit.get('id');
                var comment_id = comment_id_dom.replace('edit-comment-ajax-', '');
                var edit_loader_icon = Y.one('.editcomment-loader-icon-' + comment_id + ' .ajaxloadicon');
                edit_loader_icon.removeClass('hiddenelement');
                var editarea = Y.one('.editarea-' + comment_id);
                var url = commentedit.getData('link');
                var entry_id = commentedit.getData('entry');

                var cfg = {
                    method: "POST",
                    on: {
                        success : function(id, o, args) {
                            var formarea = Y.one('.editarea-' + comment_id);
                            var response = Y.JSON.parse(o.responseText)
                            var form = Y.Node.create(response.html); // Create a Node from the HTML

                            edit_loader_icon.addClass('hiddenelement');
                            formarea.setHTML(form);
                            scriptel = document.createElement('script'); // Create a <script> tag
                            scriptel.textContent = response.script; // Put the Javascript inside the script tag
                            document.body.appendChild(scriptel); // Add the script tag to the page.

                            Y.one('.editarea-' + comment_id + ' ' + '#id_cancel').set('disabled', 'disabled');
                            YUI().use('event', function (Y) {
                                Y.one('.editarea-' + comment_id + ' #mform1').on('submit', function (e) {
                                    var loadericon = Y.one('.editarea-' + comment_id + ' .ajaxloadicon');
                                    Y.one('.editarea-' + comment_id + ' .fitem_actionbuttons .felement.fgroup').prepend(loadericon);
                                    console.log(loadericon);
                                    loadericon.removeClass('hiddenelement');
                                    M.ilp_ajax_addnew.submit_edit_form(e, this._node, formarea, url, entry_id, loadericon);
                                });
                            });
                        }
                    }
                };
                Y.io(url, cfg);
            });


        });

    },
    submit_edit_form: function(e, mform, formarea, url, entry_id, loadericon) {
        var Y = this.Y;

        // Stop the form submitting normally
        e.preventDefault();

        var formwrapper =new Object();
        formwrapper.id = 'mform1';

        // Send the request
        Y.io(url, {
            method: "POST",
            on: {
                success: function(id, o) {
                    formarea.setHTML("");
                    var comments_container = Y.one('#entry_' + entry_id + '_container');
                    comments_container.setHTML(o.response);
                    // The entry's comments have been re-loaded to capture the new changes; these need the seem script attached.
                    M.ilp_ajax_addnew.prepare_edits_for_ajax();
                    M.ilp_ajax_addnew.prepare_deletes_for_ajax();
                    loadericon.addClass('hiddenelement');
                }
            },
            form: formwrapper,
            context: this
        });

    },
    prepare_deletes_for_ajax: function() {
        var commentdeletes = Y.all('.delete-comment-ajax');
        commentdeletes.each( function (commentdelete) {
            commentdelete.setStyle('cursor', 'pointer');
            commentdelete.on('click', function() {
                var comment_id_dom = commentdelete.get('id');
                var comment_id = comment_id_dom.replace('delete-comment-ajax-', '');
                var delete_loader_icon = Y.one('.deletecomment-loader-icon-' + comment_id + ' .ajaxloadicon');
                delete_loader_icon.removeClass('hiddenelement');

                var url = commentdelete.getData('link');
                var entry_id = commentdelete.getData('entry');

                var cfg = {
                    method: "POST",
                    on: {
                        success : function(id, o, args) {
                            Y.one('#comment-id-' + comment_id).hide();
                            var numcomments = Y.one('span.numcomments-' + 'ajax_com-' + entry_id);
                            numcomments.set('text', parseInt(numcomments.get('text')) - 1);
                            delete_loader_icon.addClass('hiddenelement');
                        }
                    }
                };
                Y.io(url, cfg);
            });


        });
    }


}


