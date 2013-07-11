M.ilp_view_print_preview=
    {
        init: function()
        {
            if(Y.one('#id_course_id') !=null &&
               Y.one('#id_group_id') != null)
            {
                Y.on('change',function()
                     {
                         var sbox=document.getElementById('id_course_id');
                         var index = sbox.selectedIndex;
                         var value = sbox.options[index].value;

                         M.ilp_view_print_preview.update_groups(value);
                     }
                    );
                var sbox=document.getElementById('id_course_id');
                M.ilp_view_print_preview.update_groups(sbox.options[sbox.selectedIndex].value);
            }
        },

        update_groups: function(courseid)
        {
            if(Y.one('#id_group_id') != null)
            {
                var sbox=document.getElementById('id_group_id');
                sbox.options.length=0;
                sbox.selectedIndex=0;
                var url='/blocks/ilp/brain.php?fn=groups_in_course&id='+courseid;
                var text=Y.io(url,{sync:'true'}).responseText;
                Y.JSON.parse(text,
                             function(key,val)
                             {
                                 sbox.options[sbox.options.length]=new Option(val,key);
                             }
                            );

                if(sbox.options.length==0)
                {
                    Y.one('id_group_id').hide();
                }
                else
                {
                    Y.one('id_group_id').show();
                }
            }
        }
    }
