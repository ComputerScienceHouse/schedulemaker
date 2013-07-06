<?php
////////////////////////////////////////////////////////////////////////////
// SAVED SCHEDULE PRUNER
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	tools/pruneSchedules.php
// @descrip	A stand alone tool (that should be cronjobbed) to prune saved
//			schedules that are older than 90 days.
////////////////////////////////////////////////////////////////////////////

// Make sure the working directory is correct
chdir(dirname($_SERVER['SCRIPT_FILENAME']));

// Bring in the database connection
require_once("../inc/databaseConn.php");
global $dbConn;

// Build a where clause that will be resued
$where = "WHERE (NOW() - datelastaccessed > (60 * 60 * 24 * 90))";

// Run the query to delete the courses of the schedule that are older than
// 90 days
$ninetyDaysAgo = date("Y-m-d H:i:s", strtotime("-90 days"));
$query = "DELETE FROM schedules WHERE datelastaccessed < '{$ninetyDaysAgo}'";
$result = mysql_query($query, $dbConn);
if(!$result) {
    echo("*** Failed to run pruning query:\n");
    echo(mysql_error() . "\n");
} else {
    echo("... " . mysql_affected_rows($dbConn) . " schedules deleted\n");
}

?>
