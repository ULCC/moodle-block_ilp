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
			'title' => 'My Targets',
			'description' => 'Use this area to state your targets to help you progress throughout your course of study. The targets should be specific to you, measurable, be attainable and realistic, and be able to be set to a deadline.',
			'fieldlist' => array(
				array(
					'type' => 'course',
					'label' => 'Course',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'html',
					'label' => 'What do I want to achieve?',
					'description' => 'generic description',
					'req' => 0
				),
                array(
                    'type' => 'html',
                    'label' => 'How will I measure the achievement of this?',
                    'description' => 'generic description',
                    'req' => 0
                ),
                array(
                    'type' => 'html',
                    'label' => 'What do I need to do and do I need any support?',
                    'description' => 'generic description',
                    'req' => 0
                ),
                array(
                    'type' => 'date',
                    'label' => 'Deadline',
                    'description' => 'generic description',
                    'req' => 0
                )
			)
		);
