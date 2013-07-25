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

$course_id=isset($data->course_id) ? $data->course_id : 0 ;
$group_id=(isset($data->group_id)) ? $data->group_id  : 0 ;
$status_id=$data->status_id;

//get all of the students

if($course_id)
{
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
}
else
{
   $students=$usertutees;
}

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

print'<div style="text-align:left">';

require_once("$CFG->dirroot/blocks/ilp/plugins/dashboard/ilp_dashboard_student_info_print_plugin.php");

print '<div class="ilp">';

if($fullstudents=$dbc->get_studentlist_details(array_keys($students),$status_id,'','lastname asc'))
{
   foreach($fullstudents as $student)
   {
      $date=userdate(time());
      print "<div class='batchprint'>$date";

      $info=new ilp_dashboard_student_info_print_plugin($student->id,$data);
      $info->display(array());

      print "<p align='right'> $student->firstname $student->lastname</p>";
      print '</div><div class="page-break"></div>';
   }
}
else
{
   print get_string('nothingtodisplay','block_ilp');
}

print '</div>';
print '</div>';

print $OUTPUT->footer();
