<?php

/*
 * if the field is being moved up all other fields have postion value increased
 * if the field is being moved down all other fields have postion value decreased
 */
function manage_position($field, $item_id, $move) {
    if ($field->id != $item_id) {
        //move up = 1 move down = 0
        $newposition = (empty($move)) ? $field->position-1 : $field->position+1;
    } else {
        //move the field
        $newposition = (!empty($move)) ? $field->position- 1 : $field->position+1;
    }
    return $newposition;
}