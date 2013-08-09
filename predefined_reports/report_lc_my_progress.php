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
			'title' => 'My Progress',
			'description' => 'Use this area to state how well you feel your progress throughout your course of study is going. Include anything you are proud of or think you need to improve on.',
			'fieldlist' => array(
				array(
					'type' => 'course',
					'label' => 'Course',
					'description' => 'generic description',
					'req' => 0
				),
                array(
                    'type' => 'html',
                    'label' => 'How am I getting on?',
                    'description' => 'generic description',
                    'req' => 0
                ),
                array(
                    'type' => 'date',
                    'label' => 'Date',
                    'description' => 'generic description',
                    'req' => 0
                )
			)
		);
