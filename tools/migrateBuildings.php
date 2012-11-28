<?php
////////////////////////////////////////////////////////////////////////////
// MIGRATE BUILDINGS AND SHIT
// @author  Benjamin Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

// REQUIURED FILES /////////////////////////////////////////////////////////
require_once("../inc/databaseConn.php");

// MAIN EXECUTION //////////////////////////////////////////////////////////
// Grab the source of the buildings list
$web = "http://www.rit.edu/fa/facilities/campus/buildingidentitylist";
$web = file_get_contents($web);
if(empty($web)) {
    echo("*** FUCK. We couldn't get the page\n");
    die();
}

// Iterate over the buildings
$regex = "/<td>([0-9]{3}(?:[A-Z])?)<\/td>\s*<td>(.*)<\/td>\s*<td>(.*)<\/td>/m";
if(!preg_match_all($regex, $web, $out, PREG_SET_ORDER)) {
    echo("*** FUCK. Nothing matched\n");
}
foreach($out as $bldg) {
    $number = mysql_real_escape_string($bldg[1]);
    if(!is_numeric($number) && strlen($number) > 3) {
        $number = substr($number, -3);
    }elseif(is_numeric($number) && $number < 100) {
        $number = substr($number, -2);
    }
    $code = mysql_real_escape_string($bldg[2]);
    $name = mysql_real_escape_string($bldg[3]);

    // Create a query for each building
    $query = "INSERT INTO buildings (number, code, name) ";
    $query .= "VALUES('{$number}', '{$code}', '{$name}')";

    // Verify!
    if(!mysql_query($query)) {
        echo("*** SHIT. '{$number}','{$code}','{$name}'\n");
        echo("*** " . mysql_error() . "\n");
    } else {
        echo("... Adding {$number}, {$code}, {$name}\n");
    }
}
