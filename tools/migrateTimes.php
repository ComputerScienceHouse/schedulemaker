<?php
////////////////////////////////////////////////////////////////////////////
// TIME MIGRATOR
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	tools/migrateTimes.php
// @descrip	One time use tool to shift from the balls retarded hrmn format to
// a much more logical time format.
////////////////////////////////////////////////////////////////////////////

// Bring in the database connection.
require "../inc/databaseConn.php";
global $dbConn;

// Migrate the times in the times table
echo "--- Starting migration of times table\n";

$query = "SELECT id, start, end FROM times";
$result = mysql_query($query);
if(!$result) {
	die("*** Query Failed: " . mysql_error($dbConn) . "\n");
}
while($row = mysql_fetch_assoc($result)) {
	// Build the new-style time
	$newStart = substr($row['start'], 0, 2) * 60 + substr($row['start'], -2);
	$newEnd   = substr($row['end'], 0, 2) * 60 + substr($row['end'], -2);
	$queryU = "UPDATE times SET start = {$newStart}, end = {$newEnd} WHERE id={$row['id']}";
	$resultU = mysql_query($queryU);
	if(!$resultU) {
		echo("*** Query Failed: " . mysql_error($dbConn) . "\n");
	}
}


// Migrate the times in the schedules table
echo "--- Starting migration of schedules table\n";

$query = "SELECT id, starttime, endtime FROM schedules";
$result = mysql_query($query);
if(!$result) {
	die("*** Query Failed: " . mysql_error($dbConn) . "\n");
}
while($row = mysql_fetch_assoc($result)) {
	// Build the new-style time
	$newStart = substr($row['starttime'], 0, 2) * 60 + substr($row['starttime'], -2);
	$newEnd   = substr($row['endtime'], 0, 2) * 60 + substr($row['endtime'], -2);
	$queryU = "UPDATE schedules SET starttime = {$newStart}, endtime = {$newEnd} WHERE id={$row['id']}";
	$resultU = mysql_query($queryU);
	if(!$resultU) {
		echo("*** Query Failed: " . mysql_error($dbConn) . "\n");
	}
}

// Migrate the times in the schedulenoncourses table
echo "--- Starting migration of schedulesnoncourses table\n";

$query = "SELECT id, start, end FROM schedulenoncourses";
$result = mysql_query($query);
if(!$result) {
	die("*** Query Failed: " . mysql_error($dbConn) . "\n");
}
while($row = mysql_fetch_assoc($result)) {
	// Build the new-style time
	$newStart = substr($row['start'], 0, 2) * 60 + substr($row['start'], -2);
	$newEnd   = substr($row['end'], 0, 2) * 60 + substr($row['end'], -2);
	$queryU = "UPDATE schedulenoncourses SET start = {$newStart}, end = {$newEnd} WHERE id={$row['id']}";
	$resultU = mysql_query($queryU);
	if(!$resultU) {
		echo("*** Query Failed: " . mysql_error($dbConn) . "\n");
	}
}

