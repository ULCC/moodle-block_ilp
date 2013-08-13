@block_ilp
Feature: Installing the ILP Block

    In order to use the block
    As a teacher
    I need to be able to add an instance to a course
    So that I can  see it and configure it

    Scenario: The block is available in the blocks dropdown
        And the following "courses" exists:
            | fullname | shortname | format |
            | Course 1 | C1        | topics |
        And I log in as "admin"
        And I follow "Course 1"
        And I turn editing mode on
        Then I should see the ILP block in the blocks dropdown