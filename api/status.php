<?php
////////////////////////////////////////////////////////////////////////////
// STATUS
//
// @file	status.php
// @descrip	IT ALWAYS WORKS!!!
// @author	Ben Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

// FUNCTIONS ///////////////////////////////////////////////////////////////
function timeElapsed($time) {
	// Initialize the return string
	$return = "";

	// Divide off days
	$days = floor($time / (60 * 60 * 24));
	if($days) {
		$return .= "{$days} days ";
		$time -= $days * 60 * 60 * 24;
	}

	// Divide off hours
	$hours = floor($time / (60 * 60));
	if($hours) {
		$return .= "{$hours}:";
		$time -= $hours * 60 * 60;
	} else {
		$return .= "00:";
	}

	// Divide off minutes
	$mins = floor($time / 60);
	if($mins) {
		$return .= "{$mins}:";
		$time -= $mins * 60;
	} else {
		$return .= "00:";
	}

	// Divide off seconds
	$return .= str_pad($time, 2, "0", STR_PAD_LEFT);

	return $return;
}

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once("../inc/databaseConn.php");

// MAIN EXECUTION //////////////////////////////////////////////////////////
// Look up the last 20 scrape reports and store into an array
$query = "SELECT * FROM scrapelog ORDER BY timeStarted DESC LIMIT 20";
$result = mysql_query($query);
$lastLogs = array();
while($row = mysql_fetch_assoc($result)) {
	$lastLogs[] = $row;
}
echo json_encode($lastLogs);