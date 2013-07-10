M.ilp_view_print_preview=
{
    init()
    {
        if(Y.one('#select_course_id') !=null &&
           Y.one('#select_group_id') != null)
        {
            Y.on('change',function())
            {
                var index = Y.get("#select_course_id").get('selectedIndex');
                var value = Y.get("#select_course_id").get("options").item(index).getAttribute('value');
                M.ilp_view_print_preview.update_groups(value);
            }
        }
    },

    update_groups(courseid)
    {
        if(Y.one('#select_group_id') != null)
        {
            sbox=document.getElementById('select_group_id');
            sbox.options.length=0;
            sbox.selectedIndex=0;

            var newoptions=Y.JSON.parse(Y.io('blocks/ilp/brain.php?fn=groups_by_course&id='.courseid),
                                        function(key,val)
                                        {
                                            sbox.options[sbox.options.length]=new Option(val,key);
                                        }
                                       );
        }
    }
}
