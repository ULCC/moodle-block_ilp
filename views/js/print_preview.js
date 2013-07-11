M.ilp_view_print_preview=
{
    init: function()
    {
        if(Y.one('#id_course_id') !=null &&
           Y.one('#id_group_id') != null)
        {
            Y.on('change',function()
                 {
                     var index = Y.get("#id_course_id").get('selectedIndex');
                     var value = Y.get("#id_course_id").get("options").item(index).getAttribute('value');
                     M.ilp_view_print_preview.update_groups(value);
                 }
                );
            var value = Y.get("#id_course_id").get("options").item(index).getAttribute('value');
            M.ilp_view_print_preview.update_groups(value);
        }
    },

    update_groups: function(courseid)
    {
        if(Y.one('#id_group_id') != null)
        {
            var sbox=document.getElementById('id_group_id');
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
