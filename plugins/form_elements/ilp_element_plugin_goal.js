<script type='text/javascript'>
//<![CDATA[
    block_ilp_element_plugin_goal_<?php print $fieldname?>={
	initializegoals: function (id){
	    coursesel=document.getElementById('id_<?php print $fieldname ?>_sel1');
	    goalsel=document.getElementById('id_<?php print $fieldname ?>_sel2');

	    if(coursesel.done==1){
		return;
	    }

	    coursesel.done=1;

	    coursesel.mygoals=new Array();
<?php
	    $n=0;
	    foreach($courses as $c){   
		print "coursesel.mygoals[$n]=$coursegoals[$n];\n";
		$n++;
	    }

?>
		this.updatesubselect(id);
		<?php print "goalsel.options.selectedIndex=$realgoal;"; ?>
	},

	updatesubselect: function(id)
	{
	    goalsel=document.getElementById('id_<?php print $fieldname ?>_sel2');
	    coursesel=document.getElementById('id_<?php print $fieldname ?>_sel1');
	    newoptions=coursesel.mygoals[coursesel.selectedIndex];

	    goalsel.options.length=0;
	    for(index in newoptions) {
		goalsel.options[goalsel.options.length] = new Option(newoptions[index], index);
	    }
	}
    }
//]]>
</script>
