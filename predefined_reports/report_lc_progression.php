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
			'title' => 'Your Progression',
			'description' => 'A simple self-evaluation report',
			'fieldlist' => array(
				array(
					'type' => 'textarea',
					'label' => 'My Progression',
					'description' => 'generic description',
					'req' => 0
				)
			)
		);
