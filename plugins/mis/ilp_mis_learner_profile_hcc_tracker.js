/**
 * Javascript for the the ilp_mis_learner_profile_hcc_tracker plugin
 *
 * @copyright &copy; 2011 University of London Computer Centre
 * @author http://www.ulcc.ac.uk, http://moodle.ulcc.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ILP
 * @version 2.0
 */

 
M.ilp_mis_learner_profile_hcc_tracker =	{
	
    init:    function(Y) {
    	
    	var tooltipelements 	= document.getElementsByClassName('tooltip');
   	
    	Y.Array.each(tooltipelements, function(element, index, array) {
    		
    		new YAHOO.widget.Tooltip('ttA'.element.id, {
				   context:element.id,
				   effect:{effect:YAHOO.widget.ContainerEffect.FADE,duration:0.2},
				   autodismissdelay: 200000
			   });
    	});
    	
    	
    	
    }
	
	
	
	
	

};


