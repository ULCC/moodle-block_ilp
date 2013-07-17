<?php
//Coming in from an include in batch_print.html
//which itself is included from define_batch_print.php
//The important in-scope variables are:
// $dbc database connection
// $mform the submitted print definition form
// $data the data from that form.

// $data->course_id has already been tested for content

if(!$dbc->ilp_admin())
{
   print_error(get_string('nopermission'));
}

$course_id=$data->course_id;
$group_id=(isset($data->group_id))?  $group_id=$data->group_id : 0 ;
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
}

if($fullstudents=$dbc->get_studentlist_details(array_keys($dbc->get_course_users($course_id,$group_id)),$status_id,'','lastname asc'))
{
   $report=ilp_report::from_id($data->reportselect);

   $report->export_all_entries($fullstudents,$data->format);
}
else
{
   print_string('nothingtodisplay','block_ilp');
}

exit;
