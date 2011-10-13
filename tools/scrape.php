<?php
////////////////////////////////////////////////////////////////////////////
// COURSE SCRAPER
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	tools/scrape.php
// @descrip	A stand alone tool for scraping the nightly course dump and then
//			scraping the register.rit.edu pages for further details not
//			provided in the dump. This script is crucial, and if the RIT
//			site/course structure is changed this script will probably have
//			to be rewritten
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once "../inc/config.php";
require_once "../inc/databaseConn.php";

// MAIN EXECUTION //////////////////////////////////////////////////////////
// Open up the dump file
$dumpHandle = fopen($DUMPLOCATION, "r");
if(!$dumpHandle) {
	die("*** Could not open a handle to the dump file ({$DUMPLOCATION})\n");
}

// Variable to avoid doing extra queries on the quarters and courses
$curQuarter = "";
$curCourse  = "";
$courseId   = 0;
// Read off all the courses
while($line = fgets($dumpHandle, 4096)) {
	$lineSplit = explode('|', $line);

	// Grab the quarter number
	$quarter    = $lineSplit[0];

	// Have we already looked at this quarter?
	if($curQuarter != $quarter) {
		echo("... Processing Quarter: {$quarter}\n");
		// Nope. Insert the quarter if it doesn't already exist
		$curQuarter = $quarter;

		// Sanitize the quarter
		$quarter = mysql_real_escape_string($quarter);

		// Get and sanitize the start and end dates of the quarter
		$qStart = mysql_real_escape_string($lineSplit[3]);
		$qEnd   = mysql_real_escape_string($lineSplit[4]);
		
		$query = "INSERT INTO quarters (quarter, start, end) VALUES({$quarter}, {$qStart}, {$qEnd}) ";
		$query .= "ON DUPLICATE KEY UPDATE start={$qStart}, end={$qEnd}";
		$result = mysql_query($query);
		if(!$result) {
			echo("*** Could not add quarter! " . mysql_error() . "\n");
		}
	}

	// Determine the course numer of this line	
	$department = substr($lineSplit[1], 0, 4);
	$course     = substr($lineSplit[1], 4, 3);
	$section    = substr($lineSplit[1], -2);
	$courseNum   = $department . $course;

	// Have we already looked at this course?
	if($curCourse != $courseNum) {
		echo("   ... Processing Course: {$department}-{$course}\n");
		// Nope. Insert the course if it doesn't already exist
		$curCourse = $courseNum;
		
		$credits = $lineSplit[11];	// MAX-CREDIT
		
		// OK, since the dump from RIT is absolutely fucking retarded, we have
		// to do an old school scrape.
		// FUCK ITS.
		$coursePage = file_get_contents("https://register.rit.edu/courseSchedule/{$department}{$course}");
		if(!$coursePage) {
			echo("      *** Could not load https://register.rit.edu/courseSchedule/{$department}{$course}\n");
			continue;
		}
		
		// Now let's run some regexps on it to get down to the good stuff
		$pattern = "/<strong>Course Title: <\/strong>.*<td.*>(.*)<\/td>.*<strong>Description:<\/strong>.*<td.*>(.*)<\/td>/msU";
		$matches = array();
		if(preg_match($pattern, $coursePage, $matches) != 1) {
			echo("      *** Could not match the regexp for course title and description! https://register.rit.edu/courseSchedule/{$department}{$course}\n");
			continue;
		}
		$title       = mysql_real_escape_string($matches[1]);
		$description = mysql_real_escape_string($matches[2]);
		
		// Build a query to insert the course
		$query = "INSERT INTO courses (department, course, credits, quarter, title, description) ";
		$query .= "VALUES ({$department}, {$course}, {$credits}, {$quarter}, '{$title}', '{$description}') ";
		$query .= "ON DUPLICATE KEY UPDATE credits={$credits}, title='{$title}', description='{$description}' ";
		$result = mysql_query($query);
		if(!$result) {
			echo("      *** Could not add the course\n");
			continue;
		}

		// Query real quick for the course Id
		$query = "SELECT id FROM courses ";
		$query .= "WHERE quarter = {$quarter} AND course = {$course} AND department = {$department}";
		$result = mysql_query($query);
		if(!$result || mysql_num_rows($result) != 1) {
			echo("      *** Failed to lookup course after insert/update\n{$query}\n" . mysql_error() . "\n");
			continue;
		}

		$courseId = mysql_fetch_assoc($result);
		$courseId = $courseId['id'];
	}

	// The courseID is preserved between iterations in this loop.

	// What's left in the line is information on the section
	$instructor = mysql_real_escape_string(ucfirst(strtolower($lineSplit[21])) . ' ' . ucfirst(strtolower($lineSplit[22])));
	$maxEnroll  = mysql_real_escape_string($lineSplit[12]);
	$curEnroll  = mysql_real_escape_string($lineSplit[13]);
	$status     = mysql_real_escape_string($lineSplit[6]);

	// Does this section already exist
	$query = "SELECT id FROM sections WHERE course = {$courseId} AND section = {$section}";
	$result = mysql_query($query);
	if(!$result) {
		echo("      *** Failed attempting to lookup section\n");
		continue;
	}

	if(mysql_num_rows($result)) {
		// The section already exists, so we need to update it
		$sectionId = mysql_fetch_assoc($result);
		$sectionId = $sectionId['id'];

		echo("      ... Updating Section: {$department}-{$course}-{$section}\n");
		
		$query = "UPDATE sections SET instructor = '{$instructor}', maxenroll = {$maxEnroll}, curenroll = {$curEnroll}, status = '{$status}'";
		$query .= " WHERE id = {$sectionId}";
		$result = mysql_query($query);
		if(!$result) {
			echo("         *** Failed to insert section\n");
			continue;
		}
	} else {
		// The section does not exist, so it needs to be inserted
		echo("      ... Inserting Section: {$department}-{$course}-{$section}\n");
		
		$query = "INSERT INTO sections (course, section, status, instructor, maxenroll, curenroll) ";
		$query .= "VALUES ({$course}, {$section}, '{$status}', '{$instructor}', {$maxEnroll}, {$curEnroll})";
		$sectionId = mysql_insert_id();
	}

	// Now for the fun part: times
	// First step is to delete all the times that the section currently has	
	$query = "DELETE FROM times WHERE section = {$sectionId}";
	$result = mysql_query($query);
	if(!$result) {
		echo("         *** Failed to delete old section's times");
		continue;
	}
	
	// Next, we'll check each time slot from the dump and see if we need to
	// insert a row
	if(!empty($lineSplit[26])) {
		// Split it by , to get each piece of information
		$timeSplit = explode($lineSplit[26], ',');

		// Process the first time		
		if(count($timeSplit) >= 5) {
			$day   = mysql_real_escape_string($timeSplit[0]);
			$start = mysql_real_escape_string(translateTimeDump($timeSplit[1]));
			$end   = mysql_real_escape_string(translateTimeDump($timeSplit[2]));
			$bldg  = mysql_real_escape_string($timeSplit[3]);
			$room  = mysql_real_escape_string($timeSplit[4]);

			$query = "INSERT INTO times (section, day, start, end, building, room) ";
			$query .= "VALUES ({$sectionId}, {$day}, {$start}, {$end}, '{$building}', '{$room}')";
		}

		// Process the second time
		if(count($timeSplit) >= 10) {
			$day   = mysql_real_escape_string($timeSplit[5]);
			$start = mysql_real_escape_string(translateTimeDump($timeSplit[6]));
			$end   = mysql_real_escape_string(translateTimeDump($timeSplit[7]));
			$bldg  = mysql_real_escape_string($timeSplit[8]);
			$room  = mysql_real_escape_string($timeSplit[9]);

			$query .= ", ({$sectionId}, {$day}, {$start}, {$end}, '{$building}', '{$room}')";
		}

		// Process the third time
		if(count($timeSplit) >= 15) {
			$day   = mysql_real_escape_string($timeSplit[10]);
			$start = mysql_real_escape_string(translateTimeDump($timeSplit[11]));
			$end   = mysql_real_escape_string(translateTimeDump($timeSplit[12]));
			$bldg  = mysql_real_escape_string($timeSplit[13]);
			$room  = mysql_real_escape_string($timeSplit[14]);

			$query .= ", ({$sectionId}, {$day}, {$start}, {$end}, '{$building}', '{$room}')";
		}

		// Process the fourth time
		if(count($timeSplit) >= 20) {
			$day   = mysql_real_escape_string($timeSplit[15]);
			$start = mysql_real_escape_string(translateTimeDump($timeSplit[16]));
			$end   = mysql_real_escape_string(translateTimeDump($timeSplit[17]));
			$bldg  = mysql_real_escape_string($timeSplit[18]);
			$room  = mysql_real_escape_string($timeSplit[19]);

			$query .= ", ({$sectionId}, {$day}, {$start}, {$end}, '{$building}', '{$room}')";
		}
		
		// Run the query
		$result = mysql_query($query);
		if(!$query) {
			echo("         *** Could not add times for section!\n");
			continue;
		}
	}
}
