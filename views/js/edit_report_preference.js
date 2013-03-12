
M.ilp_edit_report_preference = {


    init: function() {

        //the recurring maximum field may not exist in the add extension form depending on the make up of the report
        //so we test to see if it is there first
        var element = Y.one('#id_userecurmax');

        recurmaxexists              =   (typeof(element) != 'undefined' && element != null)  ?  true : false;

        if (recurmaxexists) {
            userecurmax                     =       Y.one('#id_userecurmax');
            recurmax                        =       Y.one('#id_recurmax');
            recurmax.set('disabled',true);

            Y.on('change',
                function () {
                    var userecurmaxchecked  =  userecurmax.get('checked');
                    var disablesetting  =    (userecurmaxchecked) ?  false   :  true;
                    recurmax.set('disabled',disablesetting);
             },userecurmax);
        }


        var element = Y.one('#id_reportlockdate_day');
        reportlockdateexists              =   (typeof(element) != 'undefined' && element != null)  ?  true : false;

        if (reportlockdateexists) {
            usereportlockdate                       =       Y.one('#id_usereportlockdate');

            //get all elements with class lockdate
            lockdate    =   Y.all('.lockdate');

            lockdate.each(function (ld) {
                //disable all lockdates
                ld.set('disabled',true);
            })

            Y.on('change', function () {

                var usereportlockdatechecked  =  usereportlockdate.get('checked');
                var disablesetting  =    (usereportlockdatechecked) ?  false   :  true;
                lockdate    =   Y.all('.lockdate');
                lockdate.each(function (ld) {
                    //set all lockdates to disabled setting
                    ld.set('disabled',disablesetting);
                })
            },usereportlockdate);
        }

        usemaxentries                   =       Y.one('#id_usemaxentries');
        maxentries                      =       Y.one('#id_maxentries');
        maxentries.set('disabled',true);

        Y.on('change', function () {
            var usereportlockdatechecked  =  usereportlockdate.get('checked');
            var disabledsetting = (usereportlockdatechecked) ? false  :  true;
            maxentries.set('disabled',disabledsetting);

        },usemaxentries);
    }

}