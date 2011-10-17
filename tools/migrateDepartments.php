<?php
////////////////////////////////////////////////////////////////////////////
// DEPARTMENT MIGRATOR
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	tools/migrateDepartment.php
// @descrip	A stand alone tool for migrating department/college information
//		which should be run once a quarter or so to avoid colleges
//		not being shown.
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once "../inc/config.php";
require_once "../inc/databaseConn.php";

// MAIN EXECUTION //////////////////////////////////////////////////////////
// Grab the page listing the colleges
$collegeFile = file_get_contents("https://register.rit.edu/courseSchedule/{$CURRENT_QUARTER}");

// Now grab the colleges from that data
$colleges = array();
$pattern = "/<a href=[\"']https:\/\/register.rit.edu\/courseSchedule\/{$CURRENT_QUARTER}\/(\d\d)\/[\"']>.*- (.*)<\/a>/msU";
$matches = preg_match_all($pattern, $collegeFile, $colleges);
if(!$matches) {
	die("*** Could not match the regex for colleges!\n");
}

// Iterate over each college, add them to the table, and grab their departments
for($i = 0; $i < $matches; $i++) {
	$collegeName = mysql_real_escape_string($colleges[2][$i]);
	$collegeNum  = mysql_real_escape_string($colleges[1][$i]);

	// Build the insert/update query
	$query = "INSERT INTO schools (id, title) VALUES ('{$collegeNum}', '{$collegeName}') ";
	$query .= "ON DUPLICATE KEY UPDATE title = '{$collegeName}'";
	$result = mysql_query($query);
	if(!$result) {
		echo("*** Failed to insert {$collegeName} " . mysql_error() . "\n");	
	}

	// Grab the page listing the college's departments
	$departmentFile = file_get_contents("https://register.rit.edu/courseSchedule/{$CURRENT_QUARTER}/{$collegeNum}/");

	// Parse it's data into stuff we can handle
	$departments = array();
	$pattern	 = "/<a href=[\"']https:\/\/register\.rit\.edu\/courseSchedule\/{$CURRENT_QUARTER}\/\d\d\/(\d\d)\/[\"']>.*- (.*)<\/a>/msU";
	$deptMatches = preg_match_all($pattern, $departmentFile, $departments);
	if(!$deptMatches) {
		echo("*** Could not match the regex for {$collegeName}'s departments\n");
		continue;
	}

	// Now iterate over the departments and add them to the table
	for($j = 0; $j < $deptMatches; $j++) {
		$deptName = mysql_real_escape_string($deptMatches[2][$j]);
		$deptNum  = mysql_real_escape_string($collegeNum . $deptMatches[1][$j]);

		// Build and execute the insert/update query
		$query = "INSERT INTO departments (id, title, school) VALUES ('{$deptNum}', '{$deptName}', '{$collegeNum}') ";
		$query .= "ON DUPLICATE KEY UPDATE title = '{$deptName}'";
		$result = mysql_query($query);
		if(!$result) {
			echo("*** Failed to insert {$deptName} " . mysql_error() . "\n");
		}
	}
}
