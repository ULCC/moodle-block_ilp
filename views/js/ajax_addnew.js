
M.ilp_ajax_addnew = {

    // params from PHP
    Y : null,
    root : null,
    pagename : null,
    addnew_clicked : null,
    edit_clicked : null,

    init: function(Y, root, pagename) {
        this.Y  =   Y;
        this.root = root;
        this.pagename =  pagename;
        if (pagename == 'view_studentreports') {
            M.ilp_ajax_addnew.prepare_addcomments_for_ajax();
            M.ilp_ajax_addnew.prepare_edits_for_ajax();
            M.ilp_ajax_addnew.prepare_deletes_for_ajax();
            M.ilp_ajax_addnew.prepare_addnewentries_for_ajax();
            M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
            M.ilp_ajax_addnew.prepare_entry_edits_for_ajax();
        } else {
            M.ilp_ajax_addnew.prepare_addcomments_for_ajax();
            M.ilp_ajax_addnew.prepare_edits_for_ajax();
            M.ilp_ajax_addnew.prepare_deletes_for_ajax();
            M.ilp_ajax_addnew.prepare_addnewentry_for_ajax();
            M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
            M.ilp_ajax_addnew.prepare_entry_edits_for_ajax();
        }
    },
    prepare_addcomments_for_ajax: function() {
        var Y = this.Y;
        var root = this.root;
        var commentadds = Y.all('.add-comment-ajax');
        commentadds.each( function (commentadd) {
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
                            var response = Y.JSON.parse(o.responseText);
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
                            var cancel = Y.one('.add-form-' + entryid + ' ' + '#id_cancel');
                            cancel.setAttribute('type', 'button');

                            cancel.on('click', function(){
                                formarea.setHTML('');
                            });


                            YUI().use('event', function (Y) {
                                Y.one('#mform1').on('submit', function (e) {
                                    var loadericon = Y.one('.add-form-' + entryid + ' .ajaxloadicon');
                                    Y.one('.add-form-' + entryid + ' .fitem_actionbuttons .felement.fgroup').prepend(loadericon);
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
                    var response=Y.JSON.parse(o.responseText);
                    comments_container.setHTML(response);
                    var numcomments = Y.one('span.numcomments-' + comment_send_params.dom_entryid);
                    numcomments.set('text', parseInt(numcomments.get('text')) + 1);
                    loadericon.addClass('hiddenelement');
                    M.ilp_ajax_addnew.prepare_edits_for_ajax();
                    M.ilp_ajax_addnew.prepare_deletes_for_ajax();
                    M.ilp_dashboard_reports_tab.init(Y);
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
                            var response = Y.JSON.parse(o.responseText);
                            var form = Y.Node.create(response.html);

                            edit_loader_icon.addClass('hiddenelement');
                            formarea.setHTML(form);
                            scriptel = document.createElement('script');
                            scriptel.textContent = response.script;
                            document.body.appendChild(scriptel);

                            var cancel = Y.one('.editarea-' + comment_id + ' ' + '#id_cancel');
                            cancel.setAttribute('type', 'button');
                            cancel.on('click', function(){
                                formarea.setHTML('');
                            });

                            YUI().use('event', function (Y) {
                                Y.one('.editarea-' + comment_id + ' #mform1').on('submit', function (e) {
                                    var loadericon = Y.one('.editarea-' + comment_id + ' .ajaxloadicon');
                                    Y.one('.editarea-' + comment_id + ' .fitem_actionbuttons .felement.fgroup').prepend(loadericon);
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
                    var content = Y.JSON.parse(o.responseText);
                    comments_container.setHTML(content);
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
    },
    prepare_addnewentry_for_ajax: function() {
        var newentrylink = Y.one('._addnewentry');
        var newentryarea = Y.one('._addnewentryarea');
        if (newentrylink) {
            newentrylink.setStyle('cursor', 'pointer');
            newentrylink.on('click', function(){
                M.ilp_ajax_addnew.addnew_clicked = this;
                var loadericon = Y.one('.addnewentry-loader .ajaxloadicon');
                loadericon.removeClass('hiddenelement');
                var url = newentrylink.getData('link');
                var cfg = {
                    method: "POST",
                    on: {
                        success : function(id, o, args) {
                            loadericon.addClass('hiddenelement');
                            var response = Y.JSON.parse(o.responseText);
                            var form = Y.Node.create(response.html);
                            newentryarea.setHTML(form);
                            scriptel = document.createElement('script');
                            scriptel.textContent = response.script;
                            document.body.appendChild(scriptel);

                            var cancel = Y.one('._addnewentryarea #id_cancel');
                            cancel.setAttribute('type', 'button');
                            cancel.on('click', function(){
                                newentryarea.setHTML('');
                            });

                            YUI().use('event', function (Y) {
                                Y.one('#mform1').on('submit', function (e) {
                                    var submitbuttonloadericon = Y.one('._addnewentryarea .ajaxloadicon');
                                    submitbuttonloadericon.removeClass('hiddenelement');
                                    Y.one('._addnewentryarea .fitem_actionbuttons .felement.fgroup').prepend(submitbuttonloadericon);
                                    M.ilp_ajax_addnew.submit_addnewentry_form(e, url, newentryarea, submitbuttonloadericon);
                                });
                            });
                        }
                    }
                };
                Y.io(url, cfg);
            });
        }
    },
    prepare_addnewentries_for_ajax: function() {
        var newentrylink = Y.all('._addnewentry');
        //
        newentrylink.setStyle('cursor', 'pointer');
        newentrylink.each( function(current_entry) {
            var studentid = current_entry.getData('studentid');
            var newentryarea = Y.one('.sid' + studentid + ' ._addnewentryarea');
            var loadericon = Y.one('.sid' + studentid + ' .addnewentry-loader .ajaxloadicon');
            current_entry.on('click', function(){
                M.ilp_ajax_addnew.addnew_clicked = this;
                loadericon.removeClass('hiddenelement');
                var url = current_entry.getData('link');
                var cfg = {
                    method: "POST",
                    on: {
                        success : function(id, o, args) {
                            loadericon.addClass('hiddenelement');
                            var response = Y.JSON.parse(o.responseText);
                            var form = Y.Node.create(response.html);
                            newentryarea.setHTML(form);
                            scriptel = document.createElement('script');
                            scriptel.textContent = response.script;
                            document.body.appendChild(scriptel);

                            var cancel = Y.one('._addnewentryarea #id_cancel');
                            cancel.setAttribute('type', 'button');
                            cancel.on('click', function(){
                                newentryarea.setHTML('');
                            });

                            YUI().use('event', function (Y) {
                                Y.one('#mform1').on('submit', function (e) {
                                    var submitbuttonloadericon = Y.one('._addnewentryarea .ajaxloadicon');
                                    submitbuttonloadericon.removeClass('hiddenelement');
                                    Y.one('._addnewentryarea .fitem_actionbuttons .felement.fgroup').prepend(submitbuttonloadericon);
                                    M.ilp_ajax_addnew.submit_addnewentry_form(e, url, newentryarea, submitbuttonloadericon);
                                });
                            });
                        }
                    }
                };
                Y.io(url, cfg);
            });
        });

    },
    submit_addnewentry_form: function(e, url, formarea, submitbuttonloadericon) {
        var Y = this.Y;
        e.preventDefault();

        var formwrapper =new Object();
        formwrapper.id = 'mform1';

        Y.io(url + '&processing=1', {
            method: "POST",
            on: {
                success: function(id, o) {
                    submitbuttonloadericon.addClass('hiddenelement');
                    formarea.setHTML("");
                    if (this.pagename == 'view_studentreports') {
                        var studentid = M.ilp_ajax_addnew.addnew_clicked.getData('studentid');
                        var newentry_url = Y.one('.thisurl').get('text') + '&gen_new_entry=1&single_user=' + studentid;
                        var cfg = {
                            method: "POST",
                            on: {
                                success : function(id, o, args) {
                                    var user_id;
                                    var report_id;
                                    var response = Y.JSON.parse(o.responseText);
                                    var userid_check = /[?&]user_id=([^&]+)/i;
                                    var match = userid_check.exec(url);
                                    if (match != null) {
                                        user_id = match[1];
                                    } else {
                                        user_id = "";
                                    }
                                    var reportid_check = /[?&]report_id=([^&]+)/i;
                                    var match_report = reportid_check.exec(url);
                                    if (match_report != null) {
                                        reportid = match_report[1];
                                    } else {
                                        reportid = "";
                                    }
                                    var entrycontainer = Y.one('.reports-container-container#row' + reportid + user_id + '_entry');
                                    var reportentrycolour = '';

                                    if (entrycontainer.hasClass('next-entry-grey')) {
                                        reportentrycolour += 'grey';
                                        entrycontainer.replaceClass('next-entry-grey', 'next-entry-white');
                                    } else {
                                        reportentrycolour += 'white';
                                        entrycontainer.replaceClass('next-entry-white', 'next-entry-grey');
                                    }
                                    var responsehtml = '<div class="report-entry reports-container-' + response.entryid + ' report-entry-' + reportentrycolour + '" data-studentid="' + user_id + '">' + response.html + '</div>';
                                    var newentry = Y.Node.create(responsehtml);

                                    entrycontainer.prepend(newentry);
                                    var numentries_dom = Y.one('.numentries-' + user_id);
                                    var numentries_int = parseInt(numentries_dom.get('text'));
                                    numentries_int ++;
                                    numentries_dom.set('text', numentries_int);
                                    M.ilp_ajax_addnew.prepare_addcomments_for_ajax();
                                    M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
                                    M.ilp_ajax_addnew.prepare_entry_edits_for_ajax();
                                    M.ilp_view_studentreports.prepare_comment_showhide();
                                }
                            }
                        };
                        Y.io(newentry_url, cfg);

                    } else {
                        var content = Y.JSON.parse(o.responseText);
                        var newentry = Y.Node.create(content);
                        var entrycontainer = Y.one('.reports-container-container');
                        entrycontainer.prepend(newentry);
                        entrycontainer.one('.view-comments').addClass('new-entry');
                        M.ilp_ajax_addnew.prepare_addcomments_for_ajax();
                        M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
                        M.ilp_ajax_addnew.prepare_entry_edits_for_ajax();
                        M.ilp_dashboard_reports_tab.init(Y);
                        Y.one('.reports-container-container').one('.view-comments').removeClass('new-entry');
                        var nothingtodisplay = Y.one('.nothingtodisplay');
                        if (nothingtodisplay) {
                            nothingtodisplay.addClass('hiddenelement');
                        }
                    }
                }
            },
            form: formwrapper,
            context: this
        });
    },
    prepare_delete_entries_for_ajax: function() {
        var entrydeletes = Y.all('.entry-deletion');
        entrydeletes.each( function (entrydelete) {
            entrydelete.setStyle('cursor', 'pointer');
            entrydelete.on('click', function() {
                var entry_id_dom = entrydelete.get('id');
                var entry_id = entrydelete.getData('entry');

                var delete_loader_icon = Y.one('.delete_entry-loader-' + entry_id + ' .ajaxloadicon');
                delete_loader_icon.removeClass('hiddenelement');

                var url = entrydelete.getData('link');

                var cfg = {
                    method: "POST",
                    on: {
                        success : function(id, o, args) {
                            Y.one('.reports-container-' + entry_id).hide();
                            delete_loader_icon.addClass('hiddenelement');
                            if (M.ilp_ajax_addnew.pagename == 'view_studentreports') {
                                var studentid = Y.one('.reports-container-' + entry_id).getData('studentid');
                                var numentries_dom = Y.one('.numentries-' + studentid);
                                var numentries_int = parseInt(numentries_dom.get('text'));
                                numentries_int = numentries_int - 1;
                                numentries_dom.set('text', numentries_int);
                            }
                        }
                    }
                };
                Y.io(url, cfg);
            });

        });
    },
    prepare_entry_edits_for_ajax: function() {
        var edits = Y.all('.entry-edition');
        edits.each( function (edit) {
            edit.setStyle('cursor', 'pointer');
            edit.on('click', function() {
                M.ilp_ajax_addnew.edit_clicked = this;
                var edit_id_dom = edit.get('id');
                var edit_id = edit.getData('entry');
                var edit_loader_icon = Y.one('.edit_entry-loader-' + edit_id + ' .ajaxloadicon');
                edit_loader_icon.removeClass('hiddenelement');
                var editarea = Y.one('.edit-entry-area-' + edit_id);
                var url = edit.getData('link');
                var entry_id = edit.getData('entry');

                var cfg = {
                    method: "POST",
                    on: {
                        success : function(id, o, args) {
                            var formarea = editarea;
                            var response = Y.JSON.parse(o.responseText)
                            var form = Y.Node.create(response.html);

                            edit_loader_icon.addClass('hiddenelement');
                            formarea.setHTML(form);
                            scriptel = document.createElement('script');
                            scriptel.textContent = response.script;
                            document.body.appendChild(scriptel);

                            var cancel = Y.one('.edit-entry-area-' + edit_id + ' ' + '#id_cancel');
                            cancel.setAttribute('type', 'button');
                            cancel.on('click', function(){
                                formarea.setHTML('');
                            });
                            YUI().use('event', function (Y) {
                                Y.one('.edit-entry-area-' + edit_id + ' #mform1').on('submit', function (e) {
                                    var loadericon = Y.one('.edit-entry-area-' + edit_id + ' .ajaxloadicon');
                                    Y.one('.edit-entry-area-' + edit_id + ' .fitem_actionbuttons .felement.fgroup').prepend(loadericon);
                                    loadericon.removeClass('hiddenelement');
                                    M.ilp_ajax_addnew.submit_editentry_form(e, url, formarea, loadericon, edit_id);
                                });
                            });
                        }
                    }
                };
                Y.io(url, cfg);
            });

        });

    },
    submit_editentry_form: function(e, url, formarea, submitbuttonloadericon, edit_id) {
        var Y = this.Y;
        e.preventDefault();

        var formwrapper =new Object();
        formwrapper.id = 'mform1';
        var pagename_param = '';
        if (this.pagename == 'view_studentreports') {
            pagename_param = '&pagename=' + this.pagename;
        }

        Y.io(url + '&processing=1&editing=1' + pagename_param, {
            method: "POST",
            on: {
                success: function(id, o) {
                    if (this.pagename == 'view_studentreports') {
                        var studentid = M.ilp_ajax_addnew.edit_clicked.getData('studentid');
                        var newentry_url = Y.one('.thisurl').get('text') + '&gen_new_entry=1&single_user=' + studentid;
                        var cfg = {
                            method: "POST",
                            on: {
                                success : function(id, o, args) {
                                    var response = Y.JSON.parse(o.responseText);
                                    Y.one('.reports-container-' + edit_id).setHTML(Y.Node.create(response.html));
                                    M.ilp_ajax_addnew.prepare_addcomments_for_ajax();
                                    M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
                                    M.ilp_ajax_addnew.prepare_entry_edits_for_ajax();
                                    M.ilp_view_studentreports.prepare_comment_showhide();
                                }
                            }
                        };
                        Y.io(newentry_url, cfg);

                    } else {
                        submitbuttonloadericon.addClass('hiddenelement');
                        formarea.setHTML("");

                        var response = Y.JSON.parse(o.responseText);

                        var left_report = Y.Node.create(response.left_report);
                        Y.one('.left-report-cont-' + edit_id).setHTML(left_report);
                        var right_report = Y.Node.create(response.right_report);
                        Y.one('.right-report-cont-' + edit_id).setHTML(right_report);
                        M.ilp_ajax_addnew.prepare_addcomments_for_ajax();
                        M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
                        M.ilp_ajax_addnew.prepare_entry_edits_for_ajax();
                    }
                }
            },
            form: formwrapper,
            context: this
        });
    }

}


