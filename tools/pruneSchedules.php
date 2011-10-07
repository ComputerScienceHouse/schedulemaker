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

// Bring in the database connection
require "../inc/databaseConn.php";
global $dbConn;

// Build a where clause that will be resued
$where = "WHERE (NOW() - datelastaccessed > (60 * 60 * 24 * 90))";

// Run the query to delete the courses of the schedule that are older than
// 90 days
$query = "DELETE FROM schedulecourses WHERE schedule = (SELECT id FROM schedules {$where})";
$result = mysql_query($query);
if(!$result) { echo(mysql_error() . "\n"); }

// Run the query to delete the non-course items of the schedule that are older
// than 90 days
$query = "DELETE FROM schedulenoncourses WHERE schedule = (SELECT id FROM schedules {$where})";
$result = mysql_query($query);
if(!$result) { echo(mysql_error() . "\n"); }

// Run the query to delete the schedules that are older than 90 days
$query = "DELETE FROM schedules {$where}";
$result = mysql_query($query);
if(!$result) { echo(mysql_error() . "\n"); }

?>
