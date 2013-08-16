<?php

public function format_text_with_options($text, $format, $options) {
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


public function create_plugin_from_optionlist($optionlist, $itemrecord) {
    foreach( $optionlist as $key=>$itemname ){
        $itemrecord->value = $key;
        $itemrecord->name = $itemname;
        $this->dbc->create_plugin_record($this->items_tablename,$itemrecord);
    }
}

?>