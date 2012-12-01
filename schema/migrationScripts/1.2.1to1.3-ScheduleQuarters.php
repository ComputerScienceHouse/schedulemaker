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

// Add a field for the building type for stored schedules
$query = "ALTER TABLE schedules ADD COLUMN (`quarter` ";
$query .= "SMALLINT(5) NULL DEFAULT NULL)";
if(!mysqli_query($dbc, $query)) {
    echo("*** Failed to add column to schedules\n");
    echo("*** " . mysqli_error($dbc) . "\n");
    mysqli_rollback($dbc);
    die();
}

// Find the schedules that need to add a quarter
echo("... OH GAWD THIS IS A LONG QUERY\n");
$query = "SELECT s.id, c.quarter ";
$query .= "FROM `schedules` AS s ";
$query .= "JOIN schedulecourses AS sc ON sc.schedule = s.id ";
$query .= "JOIN sections AS ss ON sc.section = ss.id ";
$query .= "JOIN courses AS c ON ss.course = c.id ";
$query .= "GROUP BY sc.schedule";
$r = mysqli_query($dbc, $query);
if(!$r) {
    echo("*** Failed to lookup schedule=>quarter .\n***" . mysqli_error($dbc) . "\n");
    mysqli_rollback($dbc);
    die();
}
echo("... AND IT'S DONE\n");

// Update for each of those records
$bldg = array();
while($row = mysqli_fetch_assoc($r)) {
    $query2 = "UPDATE schedules SET quarter = {$row['quarter']} WHERE id={$row['id']}";
	$r2 = mysqli_query($dbc, $query2);
	if(!$r2) {
		echo("*** Failed to update quarter for schedule\n***" . mysqli_error($dbc) ."\n");
	}
}
mysqli_free_result($r);
mysqli_commit($dbc);
mysqli_autocommit($dbc, true);
echo("SUCCESS, BITCHES.\n");
