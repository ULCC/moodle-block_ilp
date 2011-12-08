<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Nigel.Daley
 * Date: 08/12/11
 * Time: 18:00
 * This file clear the mis details from a mysql moodle database only. If another type of databse is in use then you will have to convert the file (not hard) yourself.
 */

require("../../../config.php");

require_login();

global $USER;

if (is_admin($USER))    {
    $dbconnection = mysql_pconnect($CFG->dbhost, $CFG->dbuser, $CFG->dbpass) or trigger_error(mysql_error(),E_USER_ERROR);

    mysql_select_db($CFG->dbname,$dbconnection);

    $sql            = "SELECT * FROM {$CFG->prefix}config_plugins WHERE plugin = 'block_ilp' AND name LIKE '%db%'";
    $results        = mysql_query($sql, $dbconnection) or die(mysql_error());
    $record        = mysql_fetch_assoc($results);
    $queryres       =   array();
    do {
        $updatesql  =   "UPDATE {$CFG->prefix}config_plugins SET value=' ' WHERE id = $record[id]";
        $Result1 = mysql_query($updatesql, $dbconnection) or die(mysql_error());

        $queryres[]  =   (!empty($Result1))  ? $record['name']." value has been cleared" : "<strong style='color:red;'>".$record['name']." has not been cleared </strong>";

    } while ($record = mysql_fetch_assoc($results));

    echo "<h1>Clear MIS</h1>";

    foreach ($queryres as $qr) {
        echo $qr."<br />";
    }
} else {
    echo 'only a moodle admin may use this file';
}
?>