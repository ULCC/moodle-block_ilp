<?php

require_once($CFG->dirroot.'/blocks/ilp/classes/form_elements/ilp_element_plugin_itemlist_mform.php');

class ilp_element_plugin_category_mform  extends ilp_element_plugin_itemlist_mform {

	function __construct($report_id,$plugin_id,$creator_id,$reportfield_id=null) {
		parent::__construct($report_id,$plugin_id,$creator_id,$reportfield_id=null);
        //remember to define $this->tablename and $this->items_tablename in the child class
        $this->tablename = 'block_ilp_plu_cat';
        $this->items_tablename = 'block_ilp_plu_cat_items';
	}
}
