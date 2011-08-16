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
			'title' => 'Planning For Success',
			'description' => 'A simple self-evaluation report',
			'fieldlist' => array(
				array(
					'type' => 'textarea',
					'label' => 'What job would you like in the future?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What do you want to get out of your time at college?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What is your next step after this course?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What are your interests?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'Is there anything that may get in the way of your studies/learning? ',
					'description' => 'Is there anything that could affect your studies? For example: responsibilities including childcare, difficulties with money, housing, immigration, money &hellip;',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'How do you learn best?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What things are you good at?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'Tell us about your work experience',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What languages do you speak? ',
					'description' => 'What is your first language?',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'Is there anything you think you will need help with this year? ',
					'description' => '(for example: English, Maths, Dyslexia, study skills &hellip;)',
					'req' => 0
				)
			)
		);
