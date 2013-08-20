<?php

require_once(dirname(__FILE__).'/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Holds all the step definitions for the behat tests in the AJAX Marking block
 */
class behat_block_ilp extends behat_base {

    /**
     * @Then /^I should see the ILP block in the blocks dropdown$/
     */
    public function i_should_see_the_ilp_block_in_the_blocks_dropdown() {
        $xpath = "//select[@name='bui_addblock']/option[text()='ILP 2.0']";
        $exception = new ElementNotFoundException($this->getSession(), "ILP in add block dropdown");
        $this->find('xpath', $xpath, $exception);
    }
}