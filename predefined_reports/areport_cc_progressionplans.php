<?php
/*
* This file should consists of one or more entries of the form
* $reportlist[] = array();
* each array represents 1 report, with a title, description and fieldlist.
* fieldlist is an array of arrays, each of which represents one field.
* if the field array is a list type (dropdown, cat, status, state or radio) it should contain a further nested array
* called 'opts', listing the available options for that element
*/
$reportlist[] = array(
			'title' => 'My Progression Plans(Coulsdon)',
			'description' => 'A simple self-evaluation report',
			'fieldlist' => array(
				array(
					'type' => 'textarea',
					'label' => 'What I plan to do next year',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'Things I need to do to achieve my plan',
					'description' => 'generic description',
					'req' => 0
				)
			)
		);
