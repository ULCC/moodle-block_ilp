var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;

M.ilp_edit_report = {


    init: function() {

        openend         =   Dom.get('id_reporttype_1');
        recurring      =    Dom.get('id_reporttype_2');
        recurringfinaldate   =       Dom.get('id_reporttype_3');
        finaldate   =       Dom.get('id_reporttype_4');

        if (openend.checked == true)    {
            M.ilp_edit_report.recurring_disabled(true);
            M.ilp_edit_report.lockdate_disabled(true);
        }

        if (recurring.checked == true)  {
            M.ilp_edit_report.recurring_disabled(false);
            M.ilp_edit_report.lockdate_disabled(true);
        }

        if (recurringfinaldate.checked == true)  {
            M.ilp_edit_report.recurring_disabled(false);
            M.ilp_edit_report.lockdate_disabled(false);
        }

        if (finaldate.checked == true)  {
            M.ilp_edit_report.recurring_disabled(true);
            M.ilp_edit_report.lockdate_disabled(false);
        }


        openend.onchange  =   function () {
            M.ilp_edit_report.recurring_disabled(true);
            M.ilp_edit_report.lockdate_disabled(true);
        }

        recurring.onchange  =   function () {
            M.ilp_edit_report.recurring_disabled(false);
            M.ilp_edit_report.lockdate_disabled(true);
        }

        recurringfinaldate.onchange  =   function () {
            M.ilp_edit_report.recurring_disabled(false);
            M.ilp_edit_report.lockdate_disabled(false);
        }

        finaldate.onchange  =   function () {
            M.ilp_edit_report.recurring_disabled(true);
            M.ilp_edit_report.lockdate_disabled(false);
        }

    },

    recurring_disabled: function (state) {
        recurringelements   =   Dom.getElementsByClassName('recurring');

        for(i=0;i<recurringelements.length;i++) {
            recurringelements[i].disabled   =   state;
        }
    },

    lockdate_disabled: function (state) {
        lockdate   =   Dom.getElementsByClassName('lockdate');

        for(i=0;i<lockdate.length;i++) {
            lockdate[i].disabled   =   state;
        }
    }


}