<?php
////////////////////////////////////////////////////////////////////////////
// SAVED SCHEDULE MIGRATOR
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	tools/migrateSchedules.php
// @descrip	A stand alone tool for migrating saved schedules.
////////////////////////////////////////////////////////////////////////////

// CONFIGURATION DATA
ini_set("memory_limit", "1024M");		// I'M GIVIN' 'ER ALL SHE GOT, CAP"N
$MIGRATEFOLDER = "/home/web/schedulemigratesaves/";
$IGNOREFILES   = array("..", ".");
$CONTROLTOKENS = array("section", "title", "days", "status", "max", "instructor", "current", "id");

// Bring in the database connection.
require "../inc/databaseConn.php";
global $dbConn;

$timeStart = microtime();

// Now load up the list of courses to migrate
$files = scandir($MIGRATEFOLDER);
$files = array_diff($files, $IGNOREFILES);

// Iterate over the files and parse them
foreach($files as $file) {
		// Grab the old index and the date changed
		$lastmodified = filectime($MIGRATEFOLDER . $file);
		$lastmodified = ($lastmodified) ? $lastmodified : time();
		$fileSplit = explode('.', $file);
		$oldIndex = mysql_real_escape_string($fileSplit[0]);

		echo "... Processing $oldIndex\n";

		// Create a database record for the schedule
		$query = "INSERT INTO schedules (datelastaccessed, oldid) VALUES(FROM_UNIXTIME({$lastmodified}), '$oldIndex')";
		$result = mysql_query($query);
		if(!$result) {
			if(mysql_errno() == 1022 || mysql_errno() == 1062) {
				// We already processed this schedule, skip it
				echo "    *** Duplicate! Skipping\n";
				continue;
			} else {
				echo "    *** MySQL Error: " . mysql_error() . "\n";
				continue;
			}
		}
		$scheduleID = mysql_insert_id();

		// Open a handle to the file and read the (hopefully) only line
		$fhandle = fopen($MIGRATEFOLDER . $file, 'r');
		while($line = fgets($fhandle, 2048)) {
			// Match this silly regexp and grab the first bit of it
			if(!preg_match('/^([^:]*),,\w*::(.*)$/', $line, $lineSplit)) {
				echo "    *** Malformed line!\n";
				continue;
			}

			// Here's the dealyo. 
			// [0] is everything. 
			// [1] is course summary. 
			// [2] is detailed info about the courses. For courses, it's useless. For non-courses, it's invaluable.
			
			// Split off the courses in the schedule ( they'll be like: (dept-course-sect)(,$1)* )
			$summarySplit = explode(',,', $lineSplit[1]);
			$courses = explode(',', $summarySplit[0]);
			$details = explode(',,', $lineSplit[2]);
			
			// Figure out the quarter and the year
			$quarter = mysql_real_escape_string($summarySplit[2]) . mysql_real_escape_string($summarySplit[1]);

			// For every course, do a query to insert it
			foreach($courses as $course) {
				if(preg_match("/non[0-9]+/", $course, $courseMatch)) {
					// We're processing a non-course object!
					foreach($details as $detail) {
						// If we can match this pattern, the detail should be for our non-course item
						if(!preg_match("/id::{$courseMatch[0]}/", $detail)) { continue; }
						
						// Grab the relavent information from the detail
						preg_match("/title::(.*?)(::|$|,,)/", $detail, $titleMatches);
						preg_match("/days::(.*)(::|$|,,)/", $detail, $daysMatches);
						$title = mysql_real_escape_string($titleMatches[1]);
						$times = $daysMatches[1];
					
						// Now we process the times
						foreach(explode(',', $times) as $time) {
							// SO MANY FUCKING FOREACH LOOPS. HOLY FUCK.
							preg_match("/([MTWRFSU])-[0-9]*-[0-9]*-[0-6]\.([0-2]?[0-9])\.([0-5]?[0-9])-[0-6]\.([0-2]?[0-9])\.([0-5]?[0-9])/", $time, $timeSplit);
						
							switch($timeSplit[1]) {
								case 'M':
									$day = 1;
									break;
								case 'T':
									$day = 2;
									break;
								case 'W':
									$day = 3;
									break;
								case 'R':
									$day = 4;
									break;
								case 'F':
									$day = 5;
									break;
								case 'S':
									$day = 6;
									break;
								case 'U':
								default:
									$day = 0;
									break;
							}
							$start = $timeSplit[2] * 60 + str_pad($timeSplit[3], 2, "0");
							$end   = $timeSplit[4] * 60 + str_pad($timeSplit[5], 2, "0");

							// [1] - The day letter, [2] - Start hour, [3] - Start minute, [4] - End hour, [5] - End minute
							// Build a query that inserts the non-course item
							$query = "INSERT INTO schedulenoncourses (title, day, start, end, schedule) ";
							$query .= "VALUES('{$title}', {$day}, {$start}, {$end}, {$scheduleID})";
							if(!mysql_query($query)) {
								echo "    *** MySQL Error: " . mysql_error() . "\n";
							} else {
								echo "    ... Adding non-course item {$title}\n";
							}
						}
					}
				} else {
					// We're processing a course object!

					preg_match("/^([0-9]{4})-([0-9]{3})-([0-9]{2})/", $course, $courseSplit);
					$department = $courseSplit[1];
					$courseno   = $courseSplit[2];
					$section    = $courseSplit[3];

					// Get the section ID
					$query = "SELECT s.id FROM courses AS c, sections AS s ";
					$query .= "WHERE c.quarter={$quarter} AND c.department={$department} AND c.course={$courseno} AND s.section={$section} AND c.id=s.course";
					$result = mysql_query($query);
					if(!$result || mysql_num_rows($result) != 1) {
						echo "    *** Course number is not in database! ({$quarter}-{$department}-{$courseno}-{$section})\n";
						continue;
					}
					$result = mysql_fetch_assoc($result);
					$sectionID = $result['id'];

					// Insert the course into the schedule
					$query = "INSERT INTO schedulecourses (schedule, section) ";
					$query .= "VALUES({$scheduleID}, {$sectionID})";
					$result = mysql_query($query);
					if(!$result) {
						echo "    *** MySQL Error: " . mysql_error() . "\n";
					} else {
						echo "    ... Adding course {$course} \n";
					}
				}
			}
		}
		fclose($fhandle);
}

echo "TOTAL TIME TO PROCESS: " . (microtime() - $timeStart) . "\n";
