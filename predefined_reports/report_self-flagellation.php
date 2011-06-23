<?php
$reportlist[] = array(
			'title' => 'Self Flagellation',
			'description' => 'An exercise in guilt',
			'fieldlist' => array(
				array(
					'type' => 'dropdown',
					'label' => 'form of punishment',
					'description' => 'owch',
					'req' => 1,
                    'selecttype' => 2,
					'opts' => array(
						1 => 'whips',
						2 => 'chains',
						3 => 'ropes',
						4 => 'thumbscrews'
					)
				),
				array(
					'type' => 'text',
					'label' => 'name',
					'description' => 'generic description',
					'req' => 1
				),
				array(
					'type' => 'dropdown',
					'label' => 'Pick a colour',
					'description' => 'generic description',
					'req' => 1,
			                    'opts' => array(
                       				 'bl'=>'black','br'=>'brown', 'oc'=>'ochre'
                    				)
				),
				array(
					'type' => 'state',
					'label' => 'Your mood',
					'description' => 'generic description',
					'req' => 1,
			                    'opts' => array(
                       				 'ang' => 'angry', 'dep' => 'depressed', 'exc' => 'excited'
			                    )
				),
				array(
					'type' => 'date',
					'label' => 'Date of birth',
					'description' => 'generic description',
					'req' => 0
				),
				array(
					'type' => 'course',
					'label' => 'Course',
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
