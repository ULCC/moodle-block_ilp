<?php
	
	$string['addprompt'] 			= 	'Add Prompt';
	$string['addfield'] 			= 	'Add Field';
	$string['addpromptdots'] 		= 	'Select Field Type'; 
	$string['apply'] 				= 	'Apply'; 
	$string['blockname'] 			= 	'ILP 2.0';
	$string['createreport']			=	'Create Report';
	$string['createnewreport']		=	'Create New Report';
	
	$string['continue']				=	'Continue';
	
	$string['contextcourse']		=	'Context Course';
	$string['contextuser']			=	'Continue User';
	$string['contextself']			=	'Context Self';
	
	$string['defaulthozsize'] 		= 	'Default number of columns';
	$string['defaulthozsizeconfig'] = 	'The default number of columns for all AJAX tables that do not override this setting.';
	$string['defaultverticalperpage'] = 'Default number of table rows';
	$string['defaultverticalperpageconfig'] = 'How many rows to show in the tables as the default for the vertical pagination';
	
	$string['description']			=	'Description';
	$string['display'] 				= 	'Display';
	$string['disablereport']		= 	'Disable Report';
	$string['editfields']			=	'Edit Fields';
	$string['editprompt']			=	'Edit Prompt';
	$string['editpermissions']		=	'Edit Permisssions';
	$string['editreport']			=	'Edit Report';
	$string['editreportfields']		=	'Edit Report Fields';
	$string['enablereport'] 		= 	'Enable Report';
	$string['fieldcreationsuc']		=	'The field was successfully created';
	$string['returnreportprompt']	=	'Returning to report fields page';
	$string['formelementdeletesuc']	=	'The field was successfully deleted';
	
	$string['fieldmovesuc']			=	'The field was successfully moved';
	$string['fieldreqsuc']			=	'The field was required status was successfully changed';
		
	$string['label']				=	'Label';
	$string['name']					=	'Name';
	$string['notrequired']	 		= 	'Not required';
	$string['maxedit'] 				= 	'Use Maximum Edit';	
	$string['move']		 			= 'Move';
	$string['movedown'] 			= 'Move down';
	$string['moveleft'] 			= 'Move left';
	$string['moveleftone'] 			= 'Move left 1';
	$string['moveright'] 			= 'Move right';
	$string['moverightone'] 		= 'Move right 1';
	$string['movetoend'] 			= 'Move to end';
	$string['moveup']		 		= 'Move up';
	$string['perpage'] 				=	'per page';
	$string['pluginname'] 			= 	'ILP block';
	$string['plugintype'] 			= 	'Plugin Type';
	
	$string['preview'] 				= 	'Preview';
	$string['previewreport'] 		= 	'Preview Report';
	$string['previewdescription']	= 	'Below is a preview of the report you are creating if you are happy with the report click continue to proceed to the next page to assign permissions to your report if you are not happy click previous to edit the report';
	$string['req'] 					= 	'Required';
	$string['reports'] 				= 	'Reports';
	$string['reportconfiguration'] 	= 	'Report Configuration';
	$string['reportfields'] 				= 	'Report Fields';
	$string['reportconfigurationdesc'] 	= 	'The report configuration admininstration area allows you to create and edit reports. ';
	$string['reportcreationsuc'] 	= 	'The report was successfully created';
	$string['reportfields'] 		= 	'Report Fields';
	$string['reportmustcontainfields'] 	= 	'The report must contain fields';
	$string['reportname'] 			= 	'Report Name:';
	$string['reportprompt']			= 	'Report Prompts';
	$string['required']	 			= 	'Required';
	$string['reportpermissions']	= 	'Report Permissions';
	$string['reportpermissionsuc']	= 	'The Permissions have been successfully assigned to the {$a->name} report';
	$string['reportpermissionsdescription']	= 	'Use the matrix below to assign permissions to the {$a->name} report. if you do not enter any permissions then default role permissions will be used on the report';
	$string['statuschangesuc'] 		= 	'The reports status was successfully changed';
	$string['showingpages'] = 'Showing {$a->startpos} - {$a->endpos} of {$a->total}';
	$string['submitanddisplay'] 	= 	'Submit and display';
	$string['selecttype'] 			= 	'Select Field Type';
	
	
	
	$string['type'] 				= 	'Type';
	$string['viewreportpreview'] 				= 	'View Report Preview';

	
	
	

	//CAPABILITY STRINGS
	$string['ilp:creeddelreport'] 		= 	'Create, edit & delete reports';
	$string['ilp:addreport'] 			= 	'Add a report';
	$string['ilp:editreport'] 			= 	'Edit a report';
	$string['ilp:deletereport'] 		= 	'Delete a report';
	$string['ilp:updatestatus'] 		= 	'Update Status';
	$string['ilp:viewreport'] 			= 	'View a report';
	
	//ERROR MESSAGES CHANGING THESE IS NOT RECOMMENDED
	$string['reportcreationerror'] 			= 	'A error occurred whilst creating the report';
	$string['fieldcreationerror'] 			= 	'A error occurred whilst creating the field';
	$string['fieldmoveerror'] 				= 	'A has error occurred the field was not moved';
	$string['formelementdeleteerror']		= 	'A error occurred whilst trying to delete the form element';
	$string['reportnotfouund']				= 	'The report with the id given was not found';
	$string['statuschangeerror'] 			= 	'A error occurred whilst changing the report status. The status was not changed';
	$string['fieldreqerror']				=	'A error occurred the required status was not updated';
	
	

	
	global $CFG;

	// Include ilp db class
	require_once($CFG->dirroot.'/blocks/ilp/db/ilp_db.php');
	
	$dbc = new ilp_db();
	$plugins = $CFG->dirroot.'/blocks/ilp/classes/form_elements/plugins';
	
	// get all the currently installed form element plugins
	$form_element_plugins = ilp_records_to_menu($dbc->get_form_element_plugins(), 'id', 'name');
	
	//this section gets language strings for all plugins
	foreach ($form_element_plugins as $plugin_file) {
		
	    if (file_exists($plugins.'/'.$plugin_file.".php")) 
	    {
	        require_once($plugins.'/'.$plugin_file.".php");
	        // instantiate the object
	        $class = basename($plugin_file, ".php");
	        $resourceobj = new $class();
	        $method = array($resourceobj, 'language_strings');

	        
	        //check whether the language string element has been defined
	        if (is_callable($method,true)) {
	            $resourceobj->language_strings($string);
	        }
	    }
	}