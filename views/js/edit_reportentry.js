
M.ilp_edit_reportentry = {


    init: function(Y) {
        this.Y = Y;

        this.currentpage = 0;
        var pagebreak = Y.one('.pagebreak-marker');
        if (pagebreak) {
            var allmarkers = Y.all('.pagebreak-marker');
            this.numberpages = allmarkers.size();
            this.add_wrapper_to_first_page(pagebreak);
            this.prepare_next();
            this.prepare_prev();
        }




        submitbuttons = Y.all("input[type=submit]").each(function (ele)  {


             Y.on("click",function (e,ele)  {
                 var path = window.location.pathname;
                 var pagename = path.split("/").pop();


                 //this action is only requried on edit_reportentry.php
                 if (pagename == 'edit_reportentry.php')  {
                    e.preventDefault();

                    res = validate_report_entry_mform(document.getElementById('mform1'));

                     if (res == true)   {
                         //create a hidden input that will hold the data that would have been passed by the button
                         //just in case its needed
                         var extrahidden = Y.Node.create('<input type="hidden">');
                         extrahidden.setAttribute('name', this.get("name"));
                         extrahidden.setAttribute('value', this.get("value"));

                         var submitid = this.get("id");
                         Y.one('form#mform1').append(extrahidden);

                         //disable the button
                         this.setAttribute('disabled', 'true');
                         //brings the button back to life just in case the user doesnt leave the page
                         setTimeout(function(){ document.getElementById("id_submitbutton").disabled = false; }, 2000);

                         //submit the form
                         Y.one('form#mform1').submit();
                     }
                 }

            }, ele);

        });

    },
    prepare_next: function() {
        var nextbutton = Y.one('#id_nextbutton');
        nextbutton.setAttribute('type', 'button');
        nextbutton.on('click', function(){
            var currentpage = M.ilp_edit_reportentry.currentpage;
            var current_dom = Y.one('.pagebreak-marker-' + currentpage);
            var nextInt = parseInt(currentpage) + 1;
            var next_dom = Y.one('.pagebreak-marker-' + nextInt);
            if (next_dom) {
                current_dom.addClass('hiddenelement');
                next_dom.removeClass('hiddenelement');
                M.ilp_edit_reportentry.currentpage ++;
                if (M.ilp_edit_reportentry.numberpages == M.ilp_edit_reportentry.currentpage) {
                    nextbutton.addClass('hiddenelement');
                }
                var prevbutton = Y.one('#id_previousbutton');
                if (prevbutton.hasClass('hiddenelement')) {
                    prevbutton.removeClass('hiddenelement');
                }
            }
        });

    },
    prepare_prev: function() {
        var prevbutton = Y.one('#id_previousbutton');
        prevbutton.setAttribute('type', 'button');
        prevbutton.on('click', function(){
            var currentpage = M.ilp_edit_reportentry.currentpage;
            var current_dom = Y.one('.pagebreak-marker-' + currentpage);
            var prevInt = parseInt(currentpage) - 1;
            var prev_dom = Y.one('.pagebreak-marker-' + prevInt);
            if (prev_dom) {
                current_dom.addClass('hiddenelement');
                prev_dom.removeClass('hiddenelement');
                M.ilp_edit_reportentry.currentpage --;
                if (M.ilp_edit_reportentry.currentpage == 0) {
                    prevbutton.addClass('hiddenelement');
                }
                var nextbutton = Y.one('#id_nextbutton');
                if (nextbutton.hasClass('hiddenelement')) {
                    nextbutton.removeClass('hiddenelement');
                }
            }
        });

    },
    add_wrapper_to_first_page: function(pagebreak) {
        var parent = pagebreak.ancestor();
        var firstpage = Y.one(document.createElement('div'));
        firstpage.addClass('pagebreak-marker');
        firstpage.addClass('pagebreak-marker-0');
        parent.prepend(firstpage);
        pagebreak.siblings().each( function (sibling) {
            if (!sibling.hasClass('pagebreak-marker') && !sibling.hasClass('fitem_actionbuttons')) {
                firstpage.append(sibling);
            }
        });

    }
}

