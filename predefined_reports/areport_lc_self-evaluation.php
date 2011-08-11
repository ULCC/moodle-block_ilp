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
			'title' => 'Self Evaluation',
			'description' => 'A simple self-evaluation report',
			'fieldlist' => array(
				array(
					'type' => 'textarea',
					'label' => 'What have I enjoyed and why ?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What have I learnt and why is it important ?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What has gone less well and what would I do differently in the future ?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'Did anything get in the way of me achieving my targets? If so, what can I do to stop this happening again?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What support can I get to help me?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What targets have I met and how can I show this?',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'textarea',
					'label' => 'What is the next step I will take?',
					'description' => 'generic description',
					'req' => 0
				)
			)
		);
