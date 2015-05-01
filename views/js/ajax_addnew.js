
M.ilp_ajax_addnew = {

    // params from PHP
    Y : null,
    root : null,
    pagename : null,
    addnew_clicked : null,
    edit_clicked : null,

    edit_button_clicked : null,
    editcomment_button_clicked : null,
    delete_button_clicked : null,
    addnew_button_clicked : null,
    addcomment_button_clicked : null,
    deletecomment_button_clicked : null,


    init: function(Y, root, pagename) {
        this.Y  =   Y;
        this.root = root;
        this.pagename =  pagename;
        this.edit_button_clicked    =   [];
        this.delete_button_clicked = [];
        this.editcomment_button_clicked = [];
        this.addnew_button_clicked = false;
        this.addcomment_button_clicked = [];
        this.deletecomment_button_clicked    =   [];

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

        if (commentadds.isEmpty() == false) {
            commentadds.each(function (commentadd) {


                var addcomment_id_dom = commentadd.get('id');
                M.ilp_ajax_addnew.addcomment_button_clicked[addcomment_id_dom] = false;
                commentadd.setStyle('cursor', 'pointer');


                commentadd.on('click', function () {
                    var entryid = commentadd.get('id');

                    if (M.ilp_ajax_addnew.addcomment_button_clicked[entryid] == false) {

                        M.ilp_ajax_addnew.addcomment_button_clicked[entryid] = true;

                        M.ilp_ajax_addnew.disable_ajax_buttons(null);

                        commentadd.setStyle('cursor', 'not-allowed');
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
                                success: function (id, o, args) {
                                    var formarea = Y.one('.add-form-' + entryid);
                                    var response = Y.JSON.parse(o.responseText);
                                    var form = Y.Node.create(response.html); // Create a Node from the HTML
                                    formarea_icon.addClass('hiddenelement');
                                    formarea.setHTML(form);
                                    scriptel = document.createElement('script'); // Create a <script> tag
                                    scriptel.textContent = response.script; // Put the Javascript inside the script tag
                                    document.body.appendChild(scriptel); // Add the script tag to the page.

                                    var comment_send_params = new Object();
                                    comment_send_params.dom_entryid = entryid;
                                    comment_send_params.entryid = numerical_entry_id;
                                    comment_send_params.report_id = report_id;
                                    comment_send_params.user_id = user_id;
                                    comment_send_params.selectedtab = selectedtab;
                                    comment_send_params.tabitem = tabitem;
                                    comment_send_params.course_id = course_id;
                                    var cancel = Y.one('.add-form-' + entryid + ' ' + '#id_cancel');
                                    cancel.setAttribute('type', 'button');

                                    cancel.on('click', function () {
                                        formarea.setHTML('');
                                        commentadd.setStyle('cursor', 'pointer');
                                        M.ilp_ajax_addnew.addcomment_button_clicked[entryid] = false;
                                        M.ilp_ajax_addnew.enable_ajax_buttons();

                                    });

                                    var mceText = Y.one('.mceText');
                                    if (mceText) {
                                        mceText.simulate("click");
                                    }

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
                    }
                });
            });
        }
    },
    submit_form: function(e, mform, formarea, comment_send_params, loadericon) {
        var Y = this.Y;

        var pagename = this.pagename;
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
                    M.ilp_ajax_addnew.enable_ajax_buttons();
                    if (this.pagename !== 'view_studentreports') {
                        M.ilp_dashboard_reports_tab.init(Y);
                    }
                }
            },
            form: formwrapper,
            context: this
        });

    },
    prepare_edits_for_ajax: function() {
        var commentedits = Y.all('.edit-comment-ajax');

        if (commentedits.isEmpty() == false) {

            commentedits.each(function (commentedit) {

                var comment_id_dom = commentedit.get('id');

                M.ilp_ajax_addnew.editcomment_button_clicked[comment_id_dom] = false;

                commentedit.setStyle('cursor', 'pointer');
                commentedit.on('click', function () {

                    var comment_id_dom = commentedit.get('id');

                    if (M.ilp_ajax_addnew.editcomment_button_clicked[comment_id_dom] == false) {

                        M.ilp_ajax_addnew.editcomment_button_clicked[comment_id_dom] = true;

                        M.ilp_ajax_addnew.disable_ajax_buttons();

                        var comment_id = comment_id_dom.replace('edit-comment-ajax-', '');
                        var edit_loader_icon = Y.one('.editcomment-loader-icon-' + comment_id + ' .ajaxloadicon');
                        edit_loader_icon.removeClass('hiddenelement');
                        var editarea = Y.one('.editarea-' + comment_id);
                        var url = commentedit.getData('link');
                        var entry_id = commentedit.getData('entry');

                        var cfg = {
                            method: "POST",
                            on: {
                                success: function (id, o, args) {
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
                                    cancel.on('click', function () {
                                        formarea.setHTML('');
                                        M.ilp_ajax_addnew.enable_ajax_buttons();
                                        M.ilp_ajax_addnew.editcomment_button_clicked[comment_id_dom] = false;

                                    });

                                    YUI().use('event', function (Y) {
                                        Y.one('.editarea-' + comment_id + ' #mform1').on('submit', function (e) {
                                            var loadericon = Y.one('.editarea-' + comment_id + ' .ajaxloadicon');
                                            Y.one('.editarea-' + comment_id + ' .fitem_actionbuttons .felement.fgroup').prepend(loadericon);
                                            loadericon.removeClass('hiddenelement');

                                            M.ilp_ajax_addnew.submit_edit_form(e, this._node, formarea, url, entry_id, loadericon, comment_id_dom);
                                        });
                                    });
                                }
                            }
                        };
                        Y.io(url, cfg);
                    }
                });

            });
        }

    },
    submit_edit_form: function(e, mform, formarea, url, entry_id, loadericon, comment_id_dom) {
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

                    M.ilp_ajax_addnew.enable_ajax_buttons();
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

        if (commentdeletes.isEmpty() == false) {

            commentdeletes.each(function (commentdelete) {

                var comment_id_dom = commentdelete.get('id');

                M.ilp_ajax_addnew.deletecomment_button_clicked[comment_id_dom] = false;

                commentdelete.setStyle('cursor', 'pointer');
                commentdelete.on('click', function () {


                    var comment_id_dom = commentdelete.get('id');

                    if (M.ilp_ajax_addnew.deletecomment_button_clicked[comment_id_dom] == false) {

                        M.ilp_ajax_addnew.deletecomment_button_clicked[comment_id_dom] = true;

                        var comment_id = comment_id_dom.replace('delete-comment-ajax-', '');
                        var delete_loader_icon = Y.one('.deletecomment-loader-icon-' + comment_id + ' .ajaxloadicon');
                        delete_loader_icon.removeClass('hiddenelement');

                        var url = commentdelete.getData('link');
                        var entry_id = commentdelete.getData('entry');

                        var cfg = {
                            method: "POST",
                            on: {
                                success: function (id, o, args) {
                                    Y.one('#comment-id-' + comment_id).hide();
                                    var numcomments = Y.one('span.numcomments-' + 'ajax_com-' + entry_id);
                                    numcomments.set('text', parseInt(numcomments.get('text')) - 1);
                                    delete_loader_icon.addClass('hiddenelement');

                                    if (parseInt(numcomments.get('text') == 0)) {


                                    }

                                }
                            }
                        };
                        Y.io(url, cfg);
                    }
                });

            });
        }
    },
    prepare_addnewentry_for_ajax: function() {
        var newentrylink = Y.one('._addnewentry');
        var newentryarea = Y.one('._addnewentryarea');
        if (newentrylink) {
            newentrylink.setStyle('cursor', 'pointer');
            newentrylink.on('click', function(){
                M.ilp_ajax_addnew.addnew_clicked = this;

                if (M.ilp_ajax_addnew.addnew_button_clicked == false)   {

                    newentrylink.setStyle('cursor', 'not-allowed');


                    M.ilp_ajax_addnew.disable_ajax_buttons(null);

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
                                    M.ilp_ajax_addnew.enable_ajax_buttons();
                                    newentrylink.setStyle('cursor', 'pointer');
                                });

                                YUI().use('event', function (Y) {
                                    Y.one('#mform1').on('submit', function (e) {
                                        var submitbuttonloadericon = Y.one('._addnewentryarea .ajaxloadicon');
                                        submitbuttonloadericon.removeClass('hiddenelement');
                                        Y.one('._addnewentryarea .fitem_actionbuttons .felement.fgroup').prepend(submitbuttonloadericon);
                                        M.ilp_ajax_addnew.addnew_button_clicked = false;
                                        newentrylink.setStyle('cursor', 'pointer');
                                        M.ilp_ajax_addnew.prepare_entry_edits_for_ajax();
                                        M.ilp_ajax_addnew.submit_addnewentry_form(e, url, newentryarea, submitbuttonloadericon);
                                    });
                                });
                            }
                        }
                    };
                    Y.io(url, cfg);
                }
            });
        }
    },
    prepare_addnewentries_for_ajax: function() {
        var newentrylink = Y.all('._addnewentry');
        if (newentrylink.isEmpty() == false) {
            newentrylink.setStyle('cursor', 'pointer');
            newentrylink.each(function (current_entry) {
                var studentid = current_entry.getData('studentid');
                var newentryarea = Y.one('.sid' + studentid + ' ._addnewentryarea');
                var loadericon = Y.one('.sid' + studentid + ' .addnewentry-loader .ajaxloadicon');
                current_entry.on('click', function () {
                    M.ilp_ajax_addnew.addnew_clicked = this;
                    loadericon.removeClass('hiddenelement');
                    var url = current_entry.getData('link');
                    var cfg = {
                        method: "POST",
                        on: {
                            success: function (id, o, args) {
                                loadericon.addClass('hiddenelement');
                                var response = Y.JSON.parse(o.responseText);
                                var form = Y.Node.create(response.html);
                                newentryarea.setHTML(form);
                                scriptel = document.createElement('script');
                                scriptel.textContent = response.script;
                                document.body.appendChild(scriptel);

                                var cancel = Y.one('._addnewentryarea #id_cancel');
                                cancel.setAttribute('type', 'button');
                                cancel.on('click', function () {
                                    newentryarea.setHTML('');
                                    M.ilp_ajax_addnew.enable_ajax_buttons();
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
        }
    },
    submit_addnewentry_form: function(e, url, formarea, submitbuttonloadericon) {
        var Y = this.Y;
        e.preventDefault();
        if (typeof tinyMCE !== 'undefined' && tinyMCE.hasOwnProperty('triggerSave')) {
            tinyMCE.triggerSave();
        }
        var formwrapper =new Object();
        formwrapper.id = 'mform1';

        if (this.pagename != 'view_studentreports') {
            var display_summary = M.ilp_ajax_addnew.addnew_clicked.getData('displaysummary');
            url += '&summary=' + display_summary;
        }

        M.ilp_ajax_addnew.addnew_button_clicked = false;

        var multiple_entries = M.ilp_ajax_addnew.addnew_clicked.getData('multiple_entries');
        Y.io(url + '&processing=1', {
            method: "POST",
            on: {
                success: function(id, o) {
                    submitbuttonloadericon.addClass('hiddenelement');
                    formarea.setHTML("");
                    if (this.pagename == 'view_studentreports') {
                        var studentid = M.ilp_ajax_addnew.addnew_clicked.getData('studentid');
                        var newentry_url = Y.one('.thisurl').get('text') + '&gen_new_entry=1&single_user=' + studentid;
                        var display_summary = M.ilp_ajax_addnew.addnew_clicked.getData('displaysummary');
                        newentry_url += '&summary=' + display_summary;
                        var cfg = {
                            method: "POST",
                            on: {
                                success : function(id, o, args) {
                                    var user_id;
                                    var report_id;
                                    var response = Y.JSON.parse(o.responseText);
                                    // Get userid and reportid from url.
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

                                    M.ilp_ajax_addnew.enable_ajax_buttons();
                                    M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
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
                        var comments = entrycontainer.one('.view-comments');
                        if (comments) {
                            comments.addClass('new-entry');
                            M.ilp_dashboard_reports_tab.init(Y);
                            Y.one('.reports-container-container').one('.view-comments').removeClass('new-entry');
                        }

                        M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
                        M.ilp_ajax_addnew.enable_ajax_buttons();

                        // depends on the newly created entry with a 2nd status being the first in the list.
                        var warningstatus = entrycontainer.one('.ilp_element_plugin_warningstatus');

                        if (warningstatus) {
                            Y.all('.ilp_element_plugin_warningstatus').setHTML(warningstatus.getHTML());
                        }
                        var nothingtodisplay = Y.one('.nothingtodisplay');
                        if (nothingtodisplay) {
                            nothingtodisplay.addClass('hiddenelement');
                        }
                    }
                    if (multiple_entries == 0) {
                        M.ilp_ajax_addnew.addnew_clicked.addClass('hiddenelement');
                    }
                }
            },
            form: formwrapper,
            context: this
        });
    },
    prepare_delete_entries_for_ajax: function() {
        var pagename = this.pagename;
        var entrydeletes = Y.all('.entry-deletion');

        if (entrydeletes.isEmpty() == false ) {
                entrydeletes.each( function (entrydelete) {

                    var entry_id_dom = entrydelete.getData('id');
                    var entry_id = entrydelete.getData('entry');

                    entrydelete.setStyle('cursor', 'pointer');

                    M.ilp_ajax_addnew.delete_button_clicked[entry_id]    = false;



                    console.log(entry_id);

                    console.log(M.ilp_ajax_addnew.delete_button_clicked[entry_id]);

                    entrydelete.on('click', function() {
                        var entry_id_dom = entrydelete.get('id');
                        var entry_id = entrydelete.getData('entry');

                        console.log(entry_id);

                        console.log(M.ilp_ajax_addnew.delete_button_clicked[entry_id]);

                        if (M.ilp_ajax_addnew.delete_button_clicked[entry_id] == false) {

                            var delete_loader_icon = Y.one('.delete_entry-loader-' + entry_id + ' .ajaxloadicon');
                            delete_loader_icon.removeClass('hiddenelement');

                            var url = entrydelete.getData('link');

                            var cfg = {
                                method: "POST",
                                on: {
                                    success : function(id, o, args) {
                                        Y.one('.reports-container-' + entry_id).hide();
                                        delete_loader_icon.addClass('hiddenelement');
                                        if (pagename == 'view_studentreports') {
                                            var studentid = Y.one('.reports-container-' + entry_id).getData('studentid');
                                            var numentries_dom = Y.one('.numentries-' + studentid);
                                            var numentries_int = parseInt(numentries_dom.get('text'));
                                            numentries_int = numentries_int - 1;
                                            numentries_dom.set('text', numentries_int);
                                            var addentry = Y.one('.sid' + studentid + ' ._addnewentry');
                                        } else {
                                            var addentry = Y.one('._addnewentry');
                                        }



                                        if (addentry) {
                                            if (addentry.hasClass('hiddenelement')) {
                                                addentry.removeClass('hiddenelement');
                                            }
                                        }
                                    }
                                }
                            };

                            Y.io(url, cfg);

                        }

                    });

                });
        }
    },
    prepare_entry_edits_for_ajax: function() {
        var edits = Y.all('.entry-edition');

        if (edits.isEmpty() == false) {
            edits.each(function (edit) {

                var edit_id_dom = edit.get('id');
                M.ilp_ajax_addnew.edit_button_clicked[edit_id_dom] = false;

                edit.setStyle('cursor', 'pointer');
                edit.on('click', function () {

                    var edit_id_dom = edit.get('id');

                    if (M.ilp_ajax_addnew.edit_button_clicked[edit_id_dom] == false) {

                        M.ilp_ajax_addnew.edit_button_clicked[edit_id_dom] = true;

                        var edit_id = edit.getData('entry');
                        edit.getData('link');

                        //tell the delete button that it can not be used while we are editing;
                        M.ilp_ajax_addnew.delete_button_clicked[edit_id] = true;



                        M.ilp_ajax_addnew.edit_clicked = this;
                        var edit_loader_icon = Y.one('.edit_entry-loader-' + edit_id + ' .ajaxloadicon');
                        edit_loader_icon.removeClass('hiddenelement');
                        var editarea = Y.one('.edit-entry-area-' + edit_id);
                        var url = edit.getData('link');
                        var entry_id = edit.getData('entry');

                        M.ilp_ajax_addnew.disable_ajax_buttons(null);

                        var cfg = {
                            method: "POST",
                            on: {
                                success: function (id, o, args) {
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
                                    cancel.on('click', function () {
                                        formarea.setHTML('');
                                        M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
                                        M.ilp_ajax_addnew.enable_ajax_buttons();
                                        M.ilp_ajax_addnew.edit_button_clicked[edit_id_dom] = false;

                                    });
                                    YUI().use('event', function (Y) {
                                        Y.one('.edit-entry-area-' + edit_id + ' #mform1').on('submit', function (e) {
                                            var loadericon = Y.one('.edit-entry-area-' + edit_id + ' .ajaxloadicon');
                                            Y.one('.edit-entry-area-' + edit_id + ' .fitem_actionbuttons .felement.fgroup').prepend(loadericon);
                                            loadericon.removeClass('hiddenelement');
                                            M.ilp_ajax_addnew.submit_editentry_form(e, url, formarea, loadericon, edit_id, edit_id_dom);
                                        });
                                    });
                                }
                            }
                        };
                        Y.io(url, cfg);

                    }
                });

            });
        }
    },
    submit_editentry_form: function(e, url, formarea, submitbuttonloadericon, edit_id, edit_id_dom) {
        var Y = this.Y;
        e.preventDefault();

        if (typeof tinyMCE !== 'undefined' && tinyMCE.hasOwnProperty('triggerSave')) {
            tinyMCE.triggerSave();
        }

        var formwrapper =new Object();
        formwrapper.id = 'mform1';
        var pagename_param = '';
        if (this.pagename == 'view_studentreports') {
            pagename_param = '&pagename=' + this.pagename;
        } else {
            var display_summary = M.ilp_ajax_addnew.edit_clicked.getData('displaysummary');
            url += '&summary=' + display_summary;
        }

        Y.io(url + '&processing=1&editing=1' + pagename_param, {
            method: "POST",
            on: {
                success: function(id, o) {

                    M.ilp_ajax_addnew.edit_button_clicked[edit_id_dom]    = false;

                    if (this.pagename == 'view_studentreports') {
                        var studentid = M.ilp_ajax_addnew.edit_clicked.getData('studentid');
                        var newentry_url = Y.one('.thisurl').get('text') + '&gen_new_entry=1&single_user=' + studentid;
                        var display_summary = M.ilp_ajax_addnew.edit_clicked.getData('displaysummary');
                        newentry_url += '&summary=' + display_summary;
                        var cfg = {
                            method: "POST",
                            on: {
                                success : function(id, o, args) {
                                    var response = Y.JSON.parse(o.responseText);
                                    Y.one('.reports-container-' + edit_id).setHTML(Y.Node.create(response.html));
                                    M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
                                    M.ilp_view_studentreports.prepare_comment_showhide();

                                    M.ilp_ajax_addnew.enable_ajax_buttons();
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

                        M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();
                        M.ilp_ajax_addnew.enable_ajax_buttons();

                        var warningstatus = Y.one('.reports-container-' + edit_id).one('.ilp_element_plugin_warningstatus');

                        if (warningstatus) {
                            Y.all('.ilp_element_plugin_warningstatus').setHTML(warningstatus.getHTML());
                        }
                    }
                }
            },
            form: formwrapper,
            context: this
        });
    },


    //central function that calls the functions that disable all clickable
    // links whilst creation or editing is taking place
    disable_ajax_buttons: function(e)   {

        //comment buttons
        M.ilp_ajax_addnew.disable_addcomments_for_ajax(null,null);
        M.ilp_ajax_addnew.disable_editcomments_for_ajax(null,null);
        M.ilp_ajax_addnew.disable_deletecomments_for_ajax(null,null);

        //entry buttons
        M.ilp_ajax_addnew.disable_delete_for_ajax(null,null);
        M.ilp_ajax_addnew.disable_addnew_for_ajax(null,null);
        M.ilp_ajax_addnew.disable_entry_edits_for_ajax(null,null);



    },

    //central function that calls the functions that enable all clickable
    //links
    enable_ajax_buttons: function(e) {
        M.ilp_ajax_addnew.prepare_addcomments_for_ajax();
        M.ilp_ajax_addnew.prepare_entry_edits_for_ajax();
        M.ilp_ajax_addnew.prepare_delete_entries_for_ajax();

        M.ilp_ajax_addnew.enable_addnew_for_ajax();
        M.ilp_ajax_addnew.prepare_edits_for_ajax();
        M.ilp_ajax_addnew.prepare_deletes_for_ajax();

    },


    //this function disables all edit entry buttons
    disable_entry_edits_for_ajax: function(e, edit_id_dom)    {

        var edits = Y.all('.entry-edition');
        edits.each( function (edit) {

            var local_edit_id_dom = edit.get('id');

            if (edit_id_dom != local_edit_id_dom) {
                M.ilp_ajax_addnew.edit_button_clicked[local_edit_id_dom]    =   true;
                edit.setStyle('cursor', 'not-allowed');
            }
        });
    },


    //this function disables all add comment buttons
    disable_addcomments_for_ajax: function(e, comment_id_dom)    {

        var commentadds = Y.all('.add-comment-ajax');
        commentadds.each( function (commentadd) {

            var local_comment_id_dom = commentadd.get('id');

            if (comment_id_dom != local_comment_id_dom) {
                M.ilp_ajax_addnew.addcomment_button_clicked[local_comment_id_dom]    =   true;
                commentadd.setStyle('cursor', 'not-allowed');
            }
        });
    },

    disable_delete_for_ajax: function(e, delete_id) {

        var deletebuttons = Y.all('.entry-deletion');
        if (deletebuttons.isEmpty() == false) {
            deletebuttons.each(function (deletebutton) {

                var local_delete_id = deletebutton.get('entry');

                if (delete_id != local_delete_id) {
                    M.ilp_ajax_addnew.delete_button_clicked[local_delete_id] = true;
                    deletebutton.setStyle('cursor', 'not-allowed');

                }
            });
        }
    },

    disable_editcomments_for_ajax: function(e, editcomment_id)    {
        var editcommentsbuttons = Y.all('.edit-comment-ajax');
        editcommentsbuttons.each( function (editcommentsbut) {

            var local_editcomment_id = editcommentsbut.get('id');

            if (editcomment_id != local_editcomment_id) {
                M.ilp_ajax_addnew.editcomment_button_clicked[local_editcomment_id]    =   true;
                editcommentsbut.setStyle('cursor', 'not-allowed');
            }
        });
    },

    disable_deletecomments_for_ajax: function(e, delcomment_id)    {
        var deletecommentsbuttons = Y.all('.delete-comment-ajax');
        if (deletecommentsbuttons.isEmpty() == false) {
            deletecommentsbuttons.each(function (deletecommentsbut) {

                var local_delcomment_id = deletecommentsbut.get('id');

                if (delcomment_id != local_delcomment_id) {
                    M.ilp_ajax_addnew.deletecomment_button_clicked[local_delcomment_id] = true;
                    deletecommentsbut.setStyle('cursor', 'not-allowed');
                }
            });
        }
    },




    //disables the add new entry button
    disable_addnew_for_ajax: function(e)   {
        M.ilp_ajax_addnew.addnew_button_clicked = true;
    },


    //disables the add new entry button
    enable_addnew_for_ajax: function(e)   {
        M.ilp_ajax_addnew.addnew_button_clicked = false;
    }








}


