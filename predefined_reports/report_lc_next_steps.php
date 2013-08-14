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
    'title' => 'Next Steps',
    'description' => 'Use this area to state what your intensions are after you finish your course of study. This could include such areas as going onto further study, employment, apprenticeship or volunteering.',
    'fieldlist' => array(
        array(
            'type' => 'course',
            'label' => 'Course',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'html',
            'label' => 'What are my next steps?',
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
