<?php

//Coming in from an include in batch_print.html
//which itself is included from define_batch_print.php
//The important in-scope variables are:
// $dbc database connection
// $mform the submitted print definition form
// $data the data from that form.

// $data->course_id has already been tested for content

//This is a prelude to moving this code into a central function
//to remove duplication between here and view_studentlist.php

$course_id=$data->course_id;
$group_id=$data->group_id;
$status_id=$data->status_id;

//get all of the students in this class
$course=$dbc->get_course_by_id($course_id);

$groups=groups_get_all_groups($course->id);

if (!empty($groups))
{
   $groupmode = groups_get_course_groupmode($course);   // Groups are being used
   $isseparategroups = ($course->groupmode == SEPARATEGROUPS &&
                        !has_capability('moodle/site:accessallgroups', $context));
}
else
{
   $group_id=0;
}

$groupexists=groups_get_group($group_id);

if (empty($groupexists))
{
   $group_id=0;
}
else
{
   $groupincourse=groups_get_group_by_name($course_id,$groupexists->name);

   if (empty($groupincourse))
      $group_id = 0;
}

$students=$dbc->get_course_users($course_id,$group_id);

// setup the navigation breadcrumbs
if (!empty($course_id)) {
   $listurl="{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=0&course_id={$course_id}";
} else {
   $listurl="{$CFG->wwwroot}/blocks/ilp/actions/view_studentlist.php?tutor=1&course_id=0";
}

//add the page title
$PAGE->navbar->add(get_string('ilps','block_ilp'),$listurl,'title');

//add the page title
$title = get_string('print','block_ilp');

//block name
$PAGE->navbar->add($title,null,'title');

// setup the page title and heading

$SITE = $dbc->get_course_by_id(SITEID);
$PAGE->set_title($SITE->fullname." : ".get_string('blockname','block_ilp'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagetype('ilp-reportlist');
$PAGE->set_pagelayout('embedded');
$PAGE->set_url($baseurl);

print $OUTPUT->header();

print_object($dbc->get_studentlist_details(array_keys($students),$status_id,'','lastname asc'));

print $OUTPUT->footer();