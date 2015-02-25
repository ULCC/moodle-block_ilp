<?php


// -----------------------------------------------------------------------------
// --                         GENERAL STUFF                                   --
// -----------------------------------------------------------------------------
//param array defined with 0x40000 in line with other mooodle PARAM constant

define('ILP_PARAM_ARRAY',               0x40000);


// a blacklist of file extensions commonly blocked because they can carry viruses

define('ILP_REDIRECT_DELAY',            1);


define('ILP_MAXLENGTH_BREADCRUMB',      130);

//used when changing the position of a field in a report
define('ILP_MOVE_UP',                  '1');
define('ILP_MOVE_DOWN',                '0');

//used by the date and date_deadline plugin to define what type of date may be
//accepted in a report
define('ILP_PASTDATE',    1 );
define('ILP_PRESENTDATE', 2 );
define('ILP_FUTUREDATE',  3 );
define('ILP_ANYDATE',     0 );

define( 'ILP_OPTIONSINGLE' , 1 );
define( 'ILP_OPTIONMULTI' , 2 );

//defines whether something is enabled or disabled
define( 'ILP_ENABLED' , 1 );
define( 'ILP_DISABLED' , 0 );

define( 'ILP_STATE_UNSET' , 0 );
define( 'ILP_STATE_FAIL' , 1 );
define( 'ILP_STATE_PASS' , 2 );
define( 'ILP_STATE_NOTCOUNTED' , 3 );

//this is the default status record that will be used for all users it should be set to 1
//as the user status record is created on installation any changes to the status items can
//be made from the block settings page
define( 'ILP_DEFAULT_USERSTATUS_RECORD' , '1' );

//the id of the user who we will give say is responsible for all changes made by the block
//e.g staus creation
define( 'ILP_DEFAULT_USER_ID' , '1' );

//constants used by ilp_logging class
define( 'ILP_LOG_ADD', 1 );

define( 'ILP_LOG_UPDATE', 2 );

define( 'ILP_LOG_DELETE', 3 );

define( 'ILP_LOG_ASSESSMENT', 4 );


//default css passfail colours
define('ILP_CSSCOLOUR_FAIL','#FF0000');
define('ILP_CSSCOLOUR_PASS','#C0FF3E');
define('ILP_CSSCOLOUR_MID','#FF4500');

//default css passfail colours
define('ILP_DEFAULT_FAIL_PERCENTAGE',50);
define('ILP_DEFAULT_PASS_PERCENTAGE',75);

//The mamximum size of uploaded files
define('ILP_MAXFILE_SIZE',1048576 );

//the type of files that may be uploaded as icons
define('ILP_ICON_TYPES','jpg,png, jpeg, gif');

//The default number of reports displayed in a list
define('ILP_DEFAULT_LIST_REPORTS',10 );

//the types that the data for mis plugins may be retrieved from
define('ILP_MIS_TABLE','1');
define('ILP_MIS_STOREDPROCEDURE','0');

//the types that the id may be string or int. strings have commas appended to them
define('ILP_IDTYPE_STRING','0');
define('ILP_IDTYPE_INT','1');

define('ILP_EVENT',           0);

define('ILP_STRIP_TAGS_DESCRIPTION', '');


//GRAPH DISPLAY
define('ILP_DISPLAYGRAPHTHUMBS', 1);
define('ILP_DISPLAYGRAPHLINKS', 2);
define('ILP_DISPLAYGRAPHICON', 3);

//GRAPH DATA
define('ILP_GRAPH_ONEMONTHDATA', 1);
define('ILP_GRAPH_THREEMONTHDATA', 2);
define('ILP_GRAPH_SIXMONTHDATA', 3);
define('ILP_GRAPH_YEARDATA', 4);
define('ILP_GRAPH_ALLDATA', 0);

//REPORT TYPES
define('ILP_RT_OPENEND', 1);
define('ILP_RT_RECURRING', 2);
define('ILP_RT_RECURRING_FINALDATE', 3);
define('ILP_RT_FINALDATE', 4);

//RECURRING RESETs
define('ILP_RECURRING_DAY',101);
define('ILP_RECURRING_WEEK',1);
define('ILP_RECURRING_2WEEK',2);
define('ILP_RECURRING_3WEEK',3);
define('ILP_RECURRING_4WEEK',4);
define('ILP_RECURRING_5WEEK',5);
define('ILP_RECURRING_6WEEK',6);
define('ILP_RECURRING_7WEEK',7);
define('ILP_RECURRING_8WEEK',8);
define('ILP_RECURRING_9WEEK',9);
define('ILP_RECURRING_10WEEK',10);
define('ILP_RECURRING_11WEEK',11);
define('ILP_RECURRING_12WEEK',12);
define('ILP_RECURRING_13WEEK',13);
define('ILP_RECURRING_14WEEK',14);
define('ILP_RECURRING_15WEEK',15);
define('ILP_RECURRING_16WEEK',16);

//RECURRING REPORT START
define('ILP_RECURRING_REPORTCREATION',1);
define('ILP_RECURRING_FIRSTENTRY',2);
define('ILP_RECURRING_SPECIFICDATE',3);

define('ILP_CREATED_BY_USER', '1');
define('ILP_NOTCREATED_BY_USER', '2');

define('ILP_DATEFIELD_DATE', 0);
define('ILP_DATEFIELD_DEADLINE', 1);
define('ILP_DATEFIELD_REVIEWDATE', 2);

define('ILP_CORE_TAB_PLUGINS', 'ilp_dashboard_archive_tab|ilp_dashboard_entries_tab|ilp_dashboard_reports_tab|ilp_dashboard_vault_tab');

define('ILP_PAGELAYOUT', 'standard');