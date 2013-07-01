<?php
require_once($CFG->dirroot.'/blocks/ilp/classes/tables/ilp_ajax_table.class.php');


/****
 * ilp_mis_ajax_table class
 *
 * extending the ilp_ajax_table class so a div may be put
 * around it using the wrap_html_start and wrap_start_finish
 *
 */
class ilp_mis_ajax_table extends ilp_ajax_table {

	public 	$wrap_label;
	public 	$wrap_data;
	public	$wrap_finish_extra;
	public	$wrap_start_extra;

	function __construct($uniqueid, $displayperpage=true,$wrapid='') {
		$this->wrapid				=	$wrapid;
		$this->wrap_start_extra		=	'';
		$this->wrap_finish_extra	=	'';
		parent::__construct($uniqueid,$displayperpage);
	}

	function wrap_html_start() {
		echo "<div id='{$this->wrapid}' >".$this->wrap_start_extra;
	}

	function wrap_html_finish() {
		echo $this->wrap_finish_extra."</div>";
	}

	function wrap_extra() {
		return (!empty($this->wrap_label) && !empty($this->wrap_data)) ? "<div id='ilp_mis_learner_profile_qualifications_average'><label>{$this->wrap_label}</label>{$this->wrap_data}</div>" : '';
	}

    /**
     * This function is not part of the public api.
     */
    function print_nothing_to_display(){
        global $OUTPUT;
        $this->print_initials_bar();

        echo $this->wrap_html_start();

        echo $OUTPUT->heading(get_string('nothingtodisplay'));

        echo $this->wrap_html_finish();
    }
}

?>