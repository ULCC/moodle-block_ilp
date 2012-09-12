var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;

M.ilp_edit_report = {


    init: function() {

        //the recurring maximum field may not exist in the add extension form depending on the make up of the report
        //so we test to see if it is there first
        var element = Dom.get('id_userecurmax');
        recurmaxexists              =   (typeof(element) != 'undefined' && element != null)  ?  true : false;

        if (recurmaxexists) {
            userecurmax                     =       Dom.get('id_userecurmax');
            recurmax                        =       Dom.get('id_recurmax');
            recurmax.disabled               =       true;

            userecurmax.onchange  =   function () {
                recurmax.disabled  =    (userecurmax.checked) ?  false   :  true     ;
            }
        }

        var element = Dom.get('id_reportlockdate_day');
        reportlockdateexists              =   (typeof(element) != 'undefined' && element != null)  ?  true : false;

        if (reportlockdateexists) {
            usereportlockdate                       =       Dom.get('id_usereportlockdate');

            lockdate    =   Dom.getElementsByClassName('lockdate');

            for(i=0;i<lockdate.length;i++) {
                lockdate[i].disabled   =   true;
            }

            usereportlockdate.onchange  =   function () {
                state       =  (usereportlockdate.checked) ?  false  : true;
                for(i=0;i<lockdate.length;i++) {
                    lockdate[i].disabled   =   state;
                }
            }
        }

        usemaxentries                   =       Dom.get('id_usemaxentries');
        maxentries                      =       Dom.get('id_maxentries');
        maxentries.disabled             =       true;

        usemaxentries.onchange  =   function () {
            maxentries.disabled  = (usemaxentries.checked) ? false  :  true;
        }
    }

}