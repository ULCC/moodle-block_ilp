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
    'title' => 'All fields report (dev)2',
    'description' => 'A report containing all fields',
    'fieldlist' => array(
        array(
            'type' => 'textarea',
            'label' => 'Manchego',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'textarea',
            'label' => 'Tetilla',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'text',
            'label' => 'Cabrales',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'status',
            'label' => 'Halloumi',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'status',
            'label' => 'Monterey Jack',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'state',
            'label' => 'Feta',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'radio',
            'label' => 'Mozzarela',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'html',
            'label' => 'Emmental',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'dropdown',
            'label' => 'Stilton',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'date',
            'label' => 'Danish blue',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'course',
            'label' => 'Applewood smoked Cheddar',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'cat',
            'label' => 'Paneer',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'pagebreak',
            'label' => 'Gruyere',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'goal',
            'label' => 'Roquefort',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'freehtml',
            'label' => 'Camembert',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'file',
            'label' => 'Blue Rathgore',
            'description' => 'generic description',
            'req' => 0
        ),
        array(
            'type' => 'checkbox',
            'label' => 'Red Leicester',
            'description' => 'generic description',
            'req' => 0,
            'opts' => array(
                'Soft'=>'Soft',
                'Strong'=>'Strong',
                'Mild'=>'Mild'
                )
        )
    )
);
