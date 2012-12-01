<?php
// WORKAROUNDS /////////////////////////////////////////////////////////////
// Make sure the working directory is correct
chdir(dirname($_SERVER['SCRIPT_FILENAME']));

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once("../../inc/config.php");
require_once("../../inc/databaseConn.php");

$dbc = mysqli_connect($DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASS, $DATABASE_DB);

// Start a transaction
mysqli_autocommit($dbc, false);

// Create a table for the buildings
$query = "DROP TABLE IF EXISTS buildings";
mysqli_query($dbc, $query);

$query = "CREATE TABLE buildings (`number` VARCHAR(3) PRIMARY KEY, `code` VARCHAR(3) UNIQUE, `name` VARCHAR(100))Engine=MyISAM";
if(!$query || !mysqli_query($dbc, $query)) {
    echo("*** Failed to create table.\n");
    echo("*** " . mysqli_error($dbc) . "\n");
    mysqli_rollback($dbc);
    die();
}

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
    $number = mysqli_real_escape_string($dbc, $bldg[1]);
    if(!is_numeric($number) && strlen($number) > 3) {
        $number = substr($number, -3);
    }elseif(is_numeric($number) && $number < 100) {
        $number = substr($number, -2);
    }
    $code = mysqli_real_escape_string($dbc, $bldg[2]);
    $name = mysqli_real_escape_string($dbc, $bldg[3]);

    // Create a query for each building
    $query = "INSERT INTO buildings (number, code, name) ";
    $query .= "VALUES('{$number}', '{$code}', '{$name}')";

    // Verify!
    if(!mysqli_query($dbc, $query)) {
        echo("*** SHIT. '{$number}','{$code}','{$name}'\n");
        echo("*** " . mysqli_error($dbc) . "\n");
    } else {
        echo("... Adding {$number}, {$code}, {$name}\n");
    }
}

// Insert manual buildings
$manEnts = array(
    array("OFF", "OFF", "Off-Site"),
    array("DUB", "DUB", "Dubai"),
    array("TBA", "TBA", "To Be Announced")); // TBD NEEDS SPECIAL ATTENTION
foreach($manEnts as $entry) {
    $query = "INSERT INTO buildings (number, code, name) VALUES('{$entry[0]}','{$entry[1]}','{$entry[2]}')";
    if(!mysqli_query($dbc, $query)) {
        echo("*** Failed to insert manual building entries.\n***".mysqli_error($dbc));
        mysqli_rollback($dbc);
        die();
    }
}

// TBD=TBA
$query = "UPDATE times SET building = 'TBA' WHERE building='TBD'";
if(!mysqli_query($dbc, $query)) {
    echo("*** Failed to change TBD to TBA\n*** ".mysqli_error($dbc)."\n");
    mysqli_rollback($dbc);
    die();
}

// Add a field for the building type for stored schedules
$query = "ALTER TABLE schedules ADD COLUMN (`building` ";
$query .= "SET('code','number') DEFAULT 'number')";
if(!mysqli_query($dbc, $query)) {
    echo("*** Failed to add column to schedules\n");
    echo("*** " . mysqli_error($dbc) . "\n");
    mysqli_rollback($dbc);
    die();
}

// Anything that has a blank oldId set the type to code
$query = "UPDATE schedules SET building='code' WHERE oldid=''";
if(!mysqli_query($dbc, $query)) {
    echo("*** Failed to update saved schedule building style\n");
    echo("*** " . mysqli_error($dbc) . "\n");
    mysqli_rollback($dbc);
    die();
}

// Now we need to change existing courses to have correct building
$query = "SELECT code, number FROM buildings";
$r = mysqli_query($dbc, $query);
if(!$r) {
    echo("*** Failed to lookup the buildings.\n***" . mysqli_error($dbc) . "\n");
    mysqli_rollback($dbc);
    die();
}

$bldg = array();
while($row = mysqli_fetch_assoc($r)) {
    $bldg[$row['code']] = $row['number'];
}
mysqli_free_result($r);

$query = "SELECT DISTINCT(building) FROM times WHERE building REGEXP('[0-9]{3}') OR building REGEXP('[A-Z]{3}')";
$r = mysqli_query($dbc, $query);
if(!$r) {
    echo("*** Failed to lookup buildings that need conversion\n***".mysqli_error($dbc)."\n");
    mysqli_rollback($dbc);
    die();
}

while($row = mysqli_fetch_assoc($r)) {
    if(is_numeric($row['building']) && strlen($row['building']) && $row['building'] < 100) {
        $building = substr($row['building'], -2);
    } elseif(!is_numeric($row['building']) && !empty($bldg[$row['building']])) {
        $building = $bldg[$row['building']];
    } else {
        continue;
    }
    $query = "UPDATE times SET building='{$building}' WHERE building='{$row['building']}'";
    if(!mysqli_query($dbc, $query)) {
        echo("*** Failed to update building.\n***".mysqli_error($dbc)."\n");
	mysqli_rollback($dbc);
	die();
    }
}

// Anything left is unknown
$query = "INSERT INTO buildings (code, number, name) ";
$query .= "SELECT building, building, 'UNKNOWN' FROM times WHERE building REGEXP('[A-Za-z]{3}') AND building NOT IN(SELECT code FROM buildings) GROUP BY building";
if(!mysqli_query($dbc, $query)) {
    echo("*** Failed to handle unknown buildings\n*** ".mysqli_error($dbc)."\n");
    mysqli_rollback($dbc);
    die();
}


mysqli_commit($dbc);
mysqli_autocommit($dbc, true);
echo("SUCCESS, BITCHES.\n");
