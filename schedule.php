<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE LOOKUP
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	schedule.php
// @descrip	Loads up the requested schedule from the database.
////////////////////////////////////////////////////////////////////////////

// CONSTANTS ///////////////////////////////////////////////////////////////
$OLDID = 0;
$NEWID = 1;
$CANNOTDETERMINE = -1;

// FUNCTIONS ///////////////////////////////////////////////////////////////
function determineIdType($id) {
	// Can it be converted from hex to int, then it's probably a new one
	if(preg_match("/[a-z0-9]/", $id)) {
		// If it's length of 7, we can't tell...
		if(strlen($id) == 7) {
			return $CANNOTDETERMINE;
		} else {
			return $NEWID;
		}
	} else {
		return $OLDID;
	}
}

function generateScheduleFromId($id) {
	// Figure out if it's an old id or a new id
	$idType = determineIdType($id);

	// Do a different query based on the type

	// Query for the id
	$query = 

function queryOldId($id) {
	// Grab all the courses that match the id
	$query = "SELECT c.section FROM schedules AS s, schedulecourses AS c WHERE s.id = c.section AND s.oldid = '{$id}'";
}

// MAIN EXECUTION //////////////////////////////////////////////////////////
// If we weren't given an id, then blow up
if(empty($_POST['id'])) {
	require "./inc/header.inc";
	echo("<div class='error'>You must provide a schedule id to load</div>");
	require "./inc/footer.inc";
	die();
}

// Clean the id
$_POST['id'] = mysql_real_escape_string($_POST['id']);

// Determine the output mode
$mode = (empty($_POST['mode'])) ? "schedule" : mysql_real_escape_string($_POST['mode']);

// Switch on the mode
switch($mode) {
	case "print":
		// PRINTABLE SCHEDULE //////////////////////////////////////////////
		// No header, no footer, just the schedule

		break;
	
	case "ical":
		// iCAL FORMAT SCHEDULE ////////////////////////////////////////////
		// Set header for ical mime, output the xml
	
		break;
	
	case "schedule":
		// DEFAULT SCHEDULE FORMAT /////////////////////////////////////////
		require "./inc/header.inc";
		
		echo 

		require "./inc/footer.inc";

	default:
		// INVALID OPTION //////////////////////////////////////////////////

}
?>
