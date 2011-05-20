<?php 


// -----------------------------------------------------------------------------
// --                         GENERAL STUFF                                   --
// -----------------------------------------------------------------------------
//param array defined with 0x40000 in line with other mooodle PARAM constant
define('PARAM_ARRAY',               0x40000);

// a blacklist of file extensions commonly blocked because they can carry viruses
define('FILE_EXTENSION_BLACKLIST', 'ade, adp, app, asp, bas, bat, chm, cmd, com, cpl, crt, csh,exe, fxp, hlp, hta, htr, inf, ins, isp, jar, js, jse, ksh, lnk, mda, mdb, mde, mdt, mdw, mdz, mht, msc, msi, msp, mst, ops, pcd, pif, prf, prg, reg, scf, scr, sct, shb, shs, url, vb, vbe, vbs, wsc, wsf, wsh');

define('REDIRECT_DELAY',            1);

define('MAXLENGTH_BREADCRUMB',      130);

define('BLOCK_NAME',                'ilp');

//used when changing the position of a field in a report
define('MOVE_UP',                  '1');
define('MOVE_DOWN',                '0');

//used by the date and date_deadline plugin to define what type of date may be 
//accepted in a report
define('PASTDATE',    1 );
define('PRESENTDATE', 2 );
define('FUTUREDATE',  3 );
define('ANYDATE',     0 );

//ilp context used to define what a user may do to their own reports
define('CONTEXT_SELF',	05);

define( 'OPTIONSINGLE' , 1 );
define( 'OPTIONMULTI' , 2 );







?>
