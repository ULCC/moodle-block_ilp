<?php

function format_text_with_options($text, $format, $options) {
    if (is_null($options)){
        $options = new stdClass;
    }
    //some sensible defaults
    if (!isset($options->para)){
        $options->para = false;
    }
    if (!isset($options->newlines)){
        $options->newlines = false;
    }
    if (!isset($options->smiley)) {
        $options->smiley = false;
    }
    if (!isset($options->filter)) {
        $options->filter = false;
    }
    return format_text($text, $format, $options);
}


function create_plugin_from_optionlist($optionlist, $itemrecord, $tablename, $dbc) {
    foreach( $optionlist as $key=>$itemname ){
        $itemrecord->value = $key;
        $itemrecord->name = $itemname;
        $dbc->create_plugin_record($tablename,$itemrecord);
    }
}

?>