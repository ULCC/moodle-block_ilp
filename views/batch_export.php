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

$fullstudents=$dbc->get_studentlist_details(array_keys($dbc->get_course_users($course_id,$group_id)),$status_id,'','lastname asc');

if(isset($data->showattendance) and $fullstudents)
{
   $userheaders=array('idnumber','username','firstname','lastname','email','status'=>'u_status','userid'=>'id','punctuality','attendance');

   $rows=$headers=array();

   foreach($userheaders as $altstring=>$h)
   {
      if(!is_numeric($altstring))
      {
         $headers[$h]=get_string($altstring,'block_ilp');
      }
      else
      {
         $headers[$h]=get_string($h);
      }
   }

   $rows=array();
   foreach($fullstudents as $student)
   {
      if($t=ilp_mis_attendance_plugin::get_summary($student->idnumber))
      {
         $row=array();

         foreach($userheaders as $h)
         {
            $row[$h]=$user->$h;
         }
         $row['punctuality']=$t['punctuality'];
         $row['attendance']=$t['attendance'];
      }
   }

   $table=new flexible_table('exporter');

   $table->setup();
   $table->define_columns(array_keys($headers));
   $table->define_headers($headers);

   $exname="table_{$format}_export_format";

   $ex=new $exname($table);

   $ex->start_document(config('block_ilp','attendance'));
   $ex->start_table('Sheet1');

   $ex->output_headers($headers);

   foreach($rows as $row)
   {
      $ex->add_data($table->get_row_from_keyed($row));
   }

   $ex->finish_table();

   $ex->finish_document();

}
elseif($fullstudents)
{
   $report=ilp_report::from_id($data->reportselect);

   $report->export_all_entries($fullstudents,$data->format);
}
else
{
   print_string('nothingtodisplay','block_ilp');
}

exit;
