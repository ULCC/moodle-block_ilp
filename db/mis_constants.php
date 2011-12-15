<?php

define("START_DAY", 01);
define("START_MONTH", 8);
define("START_YEAR", 2010);
define("DATE_TERMSTART", START_DAY."-".START_MONTH."-".START_YEAR); //this must be the date the term started
define("TERMSTART_WEEK", date("W",strtotime(DATE_TERMSTART)));
define("WEEK_OFFSET", 53 - date("W",strtotime(DATE_TERMSTART)));



define("NUMBEROFTERMS", 6);

define("TERMSTART_1", 7);
define("TERMEND_1", 12);
define("TERMSTART_2", 14);
define("TERMEND_2", 20);
define("TERMSTART_3", 23);
define("TERMEND_3", 29);
define("TERMSTART_4", 31);
define("TERMEND_4", 36);
define("TERMSTART_5", 39);
define("TERMEND_5", 43);
define("TERMSTART_6", 45);
define("TERMEND_6", 47);



define("COURSE_DEFINITION_FIELD", 'COURSEID');
define("COURSE_MARK_FIELD", 'MARK');
define("COURSE_NAME_FIELD", 'COURSENAME');
define("SESSION_DATE_FIELD", 'DATE');
define("SESSION_TIME_FIELD", 'REGISTERNAME');

$PRESENT_CODE = array('4','6','1','#','V','E','P','A','C','/','L', 'X');
$ABSENT_CODE =  array('Z','R','S','O');
$AUTH_ABSENT_CODE =  array('@','H','5','0');
$LATE_CODE =  array('L','X');   //shouold be a subset of $PRESENT_CODE
$NO_CLASS_CODE = array('N','-','Y',NULL);

$total = array();
$present = array();
$late = array();
$absent = array();
$att_perc = array();
$pun_perc = array();
$att_class = array();

for($i = 0; $i <= NUMBEROFTERMS; $i++) {
	$total[$i] = 0;
	$present[$i] = 0;
	$late[$i] = 0;
	$absent[$i] = 0;
	$att_perc[$i] = 0;
	$pun_perc[$i] = 0;
	$att_class[$i] = 'none';
}



?>
