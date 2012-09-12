<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULEMAKER - Course Dump Parser
//
// @file	tools/parseDump.php
// @descrip	This file parses the dump of course information. It basically does
//			two procedures: Parse the files into a temporary database and then
//			process the database into the database we already have
// @author	Benjamin Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

// WORKAROUNDS /////////////////////////////////////////////////////////////
// Make sure the working directory is correct
chdir(dirname($_SERVER['SCRIPT_FILENAME']));

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once("../inc/config.php");
require_once("../inc/databaseConn.php");
require_once("../inc/timeFunctions.php");
require_once("../inc/httphelper.php");

$dbConn = mysqli_connect($DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASS, $DATABASE_DB);

// FUNCTIONS ///////////////////////////////////////////////////////////////
function cleanup() {
	global $dbConn;
	// Emit a debug message
	debug("... Cleaning up temporary tables");
	
	// Drop the temporary tables
	if(!mysqli_query($dbConn, "DROP TABLE classes")) {
		echo("*** Failed to drop table classes (ignored)\n");
		echo("    " . mysqli_error($dbConn) ."\n");
	}
	if(!mysqli_query($dbConn, "DROP TABLE meeting")) {
		echo("*** Failed to drop table meeting (ignored)\n");
		echo("    " . mysqli_error($dbConn) ."\n");
	}
	if(!mysqli_query($dbConn, "DROP TABLE instructors")) {
		echo("*** Failed to drop table instructor (ignored)\n");
		echo("    " . mysqli_error($dbConn) . "\n");
	}
}

function debug($str, $nl = true) {
	global $debugMode;
	if($debugMode) {
		echo($str . (($nl) ? "\n" : ""));
	}
}

function cleanupExtraResults($dbConn) {
	// While there are more results, free them
	while(mysqli_next_result($dbConn)) {
		$set = mysqli_use_result($dbConn);
		if($set instanceof mysqli_results) {
			mysqli_free_result($set);
		}
	}
}

function insertOrUpdateCourse($quarter, $department, $course, $credits, $title, $description) {
	global $dbConn, $coursesUpdated, $coursesAdded;
	// Call the stored proc
	$query = "CALL InsertOrUpdateCourse({$quarter}, {$department}, {$course}, {$credits}, '{$title}', '{$description}')";
	$success = mysqli_multi_query($dbConn, $query);

	// Catch errors or return the id
	if(!$success) {
		return mysqli_error($dbConn);
	}

	// First result set is updated vs inserted
	$actionSet = mysqli_store_result($dbConn);
	$action = mysqli_fetch_assoc($actionSet);
	if($action['action'] == "updated") { 
		$coursesUpdated++; 
	} else { 
		$coursesAdded++;
	}
	mysqli_free_result($actionSet);

	// Second set is the id of the course
	mysqli_next_result($dbConn);
	$idSet = mysqli_store_result($dbConn);
	$id = mysqli_fetch_assoc($idSet);

	// Free up the other calls
	mysqli_free_result($idSet);
	cleanupExtraResults($dbConn);

	return $id['id'];
}

function insertOrUpdateSection($courseId, $section, $title, $instructor, $type, $status, $maxenroll, $curenroll) {
	global $dbConn, $sectUpdated, $sectAdded;
	
	// Query to call the stored proc
	$query = "CALL InsertOrUpdateSection({$courseId}, '{$section}', '{$title}', '{$instructor}', '{$type}', '{$status}',";
	$query .= "{$maxenroll},{$curenroll})";
	
	// Error check
	if(!mysqli_multi_query($dbConn, $query)) {
		return mysqli_error($dbConn);
	}

	// First result is the action performed
	$actionSet = mysqli_store_result($dbConn);
	$action = mysqli_fetch_assoc($actionSet);
	if($action['action'] == "updated") {
		$sectUpdated++;
	} else {
		$sectAdded++;
	}
	mysqli_free_result($actionSet);

	// Second result is the 
	mysqli_next_result($dbConn);
	$idSet = mysqli_store_result($dbConn);
	$id = mysqli_fetch_assoc($idSet);

	// Free up other results
	mysqli_free_result($idSet);
	cleanupExtraResults($dbConn);

	return $id['id'];
}

function getTempSections($courseNum, $offerNum, $term, $sessionNum) {
	global $dbConn;
	
	// Query for the sections of the course
	$query = "SELECT class_section,descr,enrl_stat,class_stat,class_type,enrl_cap,enrl_tot,instruction_mode,schedule_print ";
	$query .= "FROM classes WHERE crse_id={$courseNum} AND crse_offer_nbr={$offerNum} AND strm={$term} ";
	$query .= "AND session_code={$sessionNum}";
	$results = mysqli_query($dbConn, $query);

	// Check for errors
	if(!$results) {
		return mysqli_error($dbConn);
	}

	// Turn the results into an array of results
	// @TODO: Can we do this with fetch_all? Do we have mysql_nd?
	$list = array();
	while($row = mysqli_fetch_assoc($results)) {
		$list[] = $row;
	}
	return $list;
}
	

function fileToTempTable($tableName, $file, $fields, $fileSize, $procFunc=NULL) {
	global $debugMode, $dbConn;

	// Process the file
	$procBytes = 0;
	$outPercent= array(0);
	debug("... Copying {$tableName} file to temporary table\n0%", false);
	while($str = fgets($file, 4096)) {
		// Trim those damn newlines
		$str = trim($str);

		// Progress bar
		if($debugMode) {
			$percent = floor(($procBytes / $fileSize) * 100);
			if($percent % 10 == 0 && !in_array($percent, $outPercent)) {
				$outPercent[] = $percent;
				echo("...{$percent}%");
			}
		}

		// If we don't have 23 pipes, then we need to read another line
		$lineSplit = explode("|", $str);
		while(count($lineSplit) < $fields + 1) {
			$str .= fgets($file, 4096);
			$lineSplit = explode("|", $str);
		}
		$procBytes += strlen($str) + 1;

		// If we don't have $fields+1 fields, shit's borked
		if(count($lineSplit) != $fields + 1) {
			echo("*** Malformed line {$fields}, " . count($lineSplit) . "\n");
			continue;
		}

		// We only need the first $fields, otherwise imploding will break
		$lineSplit = array_splice($lineSplit, 0, $fields, true);

		// Call the special attribute processing function
		if($procFunc) {
			$lineSplit = call_user_func($procFunc, $lineSplit);
			if($lineSplit === false) {
				// The proc function doesn't want us to proceed with
				// this line
				continue;
			}
		}

		// Build a query
		$insQuery = "INSERT INTO {$tableName} VALUES('" . implode("', '", $lineSplit) . "')";
		if(!mysqli_query($dbConn, $insQuery)) {
			echo("*** Failed to insert {$tableName}\n");
			echo("    " . mysqli_error($dbConn) . "\n");
			continue;
		}
	}

	debug("...100%");
}

// COMMAND LINE ARGS ///////////////////////////////////////////////////////
$arguments = $_SERVER['argv'];
$debugMode = in_array("-d", $arguments);
$quietMode = in_array("-q", $arguments);
if(in_array("-c", $arguments)) {
	// Cleanup mode cleans up old partial parses
	cleanup();
	die();
}

// START TIME //////////////////////////////////////////////////////////////
$timeStarted     = time();
$quartersProc    = 0;
$departmentsProc = 0;
$coursesAdded    = 0;
$coursesUpdated  = 0;
$sectAdded       = 0;
$sectUpdated     = 0;
$failures        = 0;

// FILE EXIST? /////////////////////////////////////////////////////////////
// Verify that all the file locations are defined and they exist
if(empty($DUMPCLASSES) || !file_exists($DUMPCLASSES)) {
	echo "*** Fatal Error: Class dump file does not exist!\n";
	cleanup();
	die();
}
if(empty($DUMPCLASSATTR) || !file_exists($DUMPCLASSATTR)) {
	echo "*** Fatal Error: Class attribute dump file does not exist!\n";
	cleanup();
	die();
}
if(empty($DUMPINSTRUCT) || !file_exists($DUMPINSTRUCT)) {
	echo "*** Fatal Error: Instructor dump file does not exist!\n";
	cleanup();
	die();
}
if(empty($DUMPMEETING) || !file_exists($DUMPMEETING)) {
	echo "*** Fatal Error: Class meeting pattern dump file does not exist!\n";
	cleanup();
	die();
}
if(empty($DUMPNOTES) || !file_exists($DUMPNOTES)) {
	echo "*** Fatal Error: Class notes dump file does not exist!\n";
	cleanup();
	die();
}

// FILE PARSING ////////////////////////////////////////////////////////////
// Open handles to the files that were given to us from ITS
$classFile = fopen($DUMPCLASSES, 'r');
$attrFile  = fopen($DUMPCLASSATTR, 'r');
$instrFile = fopen($DUMPINSTRUCT, 'r');
$meetFile  = fopen($DUMPMEETING, 'r');
$notesFile = fopen($DUMPNOTES, 'r');

//  Store how many bytes we have
$classSize = filesize($DUMPCLASSES);
$attrSize  = filesize($DUMPCLASSATTR);
$instrSize = filesize($DUMPINSTRUCT);
$meetSize  = filesize($DUMPMEETING);
$notesSize = filesize($DUMPNOTES);

// Build the temporary tables
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `classes` (
  `crse_id` int(6) UNSIGNED NOT NULL,
  `crse_offer_nbr` int(2) UNSIGNED NOT NULL,
  `strm` int(4) UNSIGNED NOT NULL,
  `session_code` int(1) UNSIGNED NOT NULL,
  `class_section` varchar(4) NOT NULL,
  `subject` int(4) UNSIGNED ZEROFILL NOT NULL,
  `catalog_nbr` int(3) UNSIGNED ZEROFILL NOT NULL,
  `descr` text NOT NULL,
  `class_nbr` int(5) UNSIGNED NOT NULL,
  `ssr_component` varchar(3) NOT NULL,
  `units` int(1) UNSIGNED NOT NULL,
  `enrl_stat` varchar(1) NOT NULL,
  `class_stat` varchar(1) NOT NULL,
  `class_type` varchar(1) NOT NULL,
  `schedule_print` varchar(1) NOT NULL,
  `enrl_cap` int(4) UNSIGNED NOT NULL,
  `enrl_tot` int(4) UNSIGNED NOT NULL,
  `institution` varchar(5) NOT NULL,
  `acad_org` varchar(10) NOT NULL,
  `acad_group` varchar(5) NOT NULL,
  `acad_career` varchar(4) NOT NULL,
  `instruction_mode` varchar(2) NOT NULL,
  `course_descrlong` text NOT NULL,
  PRIMARY KEY (`crse_id`,`crse_offer_nbr`,`strm`,`session_code`,`class_section`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ENE;
if(mysqli_query($dbConn, $tempQuery)) {
	debug("... Temporary class table created successfully");
} else {
	echo("*** Error: Failed to create temporary class table\n");
	echo("    " . mysqli_error($dbConn) . "\n");
	cleanup();
	die();
}

// Process the class file
function procClassArray($lineSplit) {
	// Escape class title and description (respectively)
	$lineSplit[7]  = mysql_real_escape_string($lineSplit[7]);
	$lineSplit[22] = mysql_real_escape_string($lineSplit[22]);

	// Grab the integer credit count (they give it to us as a decimal)
	preg_match('/(\d)+\.\d\d/', $lineSplit[10], $match);
	$lineSplit[10] = $match[1];

	// Make the section number at least 2 digits
	$lineSplit[4] = str_pad($lineSplit[4], 2, '0', STR_PAD_LEFT);
	return $lineSplit;
}
fileToTempTable("classes", $classFile, 23, $classSize, "procClassArray");
fclose($classFile);

// Build a temporary table for the meeting patterns
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `meeting` (
  `crse_id` int(6) NOT NULL,
  `crse_offer_nbr` int(2) NOT NULL,
  `strm` int(4) NOT NULL,
  `session_code` int(1) NOT NULL,
  `class_section` varchar(4) NOT NULL,
  `class_mtg_nbr` int(2) NOT NULL,
  `start_dt` date NOT NULL,
  `end_dt` date NOT NULL,
  `bldg` varchar(10) NOT NULL,
  `room_nbr` varchar(10) NOT NULL,
  `meeting_time_start` time NOT NULL,
  `meeting_time_end` time NOT NULL,
  `mon` varchar(1) NOT NULL,
  `tues` varchar(1) NOT NULL,
  `wed` varchar(1) NOT NULL,
  `thurs` varchar(1)  NOT NULL,
  `fri` varchar(1) NOT NULL,
  `sat` varchar(1) NOT NULL,
  `sun` varchar(1) NOT NULL,
  PRIMARY KEY (`crse_id`,`crse_offer_nbr`,`strm`,`session_code`,`class_section`, `class_mtg_nbr`),
  INDEX(`crse_id`, `crse_offer_nbr`, `strm`, `session_code`, `class_section`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ENE;
if(mysqli_query($dbConn, $tempQuery)) {
	debug("... Temporary meeting pattern table created successfully");
} else {
	echo("*** Error: Failed to create temporary meeting pattern table\n");
	echo("    " . mysqli_error($dbConn) . "\n");
	cleanup();
	die();
}

// Process the meeting pattern file
function procMeetArray($lineSplit) {
	// Turn the start/end times from 03:45 PM to 154500
	// Hours must be mod'd by 12 so 12:00 PM does not become
	// 24:00 and 12 AM does not become 12:00
	if(!preg_match("/(\d\d):(\d\d) ([A-Z]{2})/", $lineSplit[10], $start)) {
		// Odds are the class is TBD (which means we can't represent it)
		return false;
	}
	$lineSplit[10] = (($start[3] == 'PM') ? ($start[1] % 12) + 12 : $start[1] % 12) . $start[2] . "00";
	preg_match("/(\d\d):(\d\d) ([A-Z]{2})/", $lineSplit[11], $end);
	$lineSplit[11] = (($end[3] == 'PM') ? ($end[1] % 12) + 12 : $end[1] % 12) . $end[2] . "00";

	// Section number needs to be padded to at least 2 digits
	$lineSplit[4] = str_pad($lineSplit[4], 2, '0', STR_PAD_LEFT);
	return $lineSplit;
}
fileToTempTable("meeting", $meetFile, 19, $meetSize, 'procMeetArray');


// Process the instructor file
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `instructors` (
  `crse_id` int(6) NOT NULL,
  `crse_offer_nbr` int(2) NOT NULL,
  `strm` int(4) NOT NULL,
  `session_code` int(1) NOT NULL,
  `class_section` varchar(4) NOT NULL,
  `class_mtg_nbr` int(2) NOT NULL,
  `last_name` varchar(30) NOT NULL,
  `first_name` varchar(30) NOT NULL,
  INDEX (`crse_id`,`crse_offer_nbr`,`strm`,`session_code`,`class_section`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ENE;
if(mysqli_query($dbConn, $tempQuery)) {
	debug("... Temporary instructor table created successfully");
} else {
	echo("*** Error: Failed to create temporary instructor table\n");
	echo("    " . mysqli_error($dbConn) . "\n");
	cleanup();
	die();
}

function procInstrArray($lineSplit) {
	global $dbConn;
	// Escape the instructor names
	$lineSplit[6] = mysqli_real_escape_string($dbConn, $lineSplit[6]);
	$lineSplit[7] = mysqli_real_escape_string($dbConn, $lineSplit[7]);

	// Section number needs to be padded to at lease 2 digits
	$lineSplit[4] = str_pad($lineSplit[4], 2, '0', STR_PAD_LEFT);
	return $lineSplit;
}
fileToTempTable("instructors", $instrFile, 8, $instrSize, 'procInstrArray');

// DATABASE PARSING ////////////////////////////////////////////////////////
// Select all the 'quarters' from the meeting pattern to get the start/end
// times for the quarter. Then insert into the quarters table
$quarterQuery = "SELECT strm, start_dt, end_dt FROM meeting GROUP BY strm";
debug("... Creating quarters\n0%", false);
$quarterResult = mysqli_query($dbConn, $quarterQuery);
$procQuart = 0;
$totQuart = mysqli_num_rows($quarterResult);
$outPercent = array(0);
while($row = mysqli_fetch_assoc($quarterResult)) {
	// Progress bar
	if($debugMode) {
		$percent = floor(($procQuart / $totQuart) * 100);
		if($percent % 10 == 0 && !in_array($percent, $outPercent)) {
			$outPercent[] = $percent;
			echo("...{$percent}%");
		}
	}	

	// We're not ignant. 5 digit terms!
	preg_match("/(\d)(\d{3})/", $row['strm'], $match);
	$row['strm'] = $match[1] . 0 . $match[2];

	// Insert the quarter
	$query = "INSERT INTO quarters (quarter, start, end)";
	$query .= " VALUES({$row['strm']}, '{$row['start_dt']}', '{$row['end_dt']}')";
	$query .= " ON DUPLICATE KEY UPDATE";
	$query .= " start='{$row['start_dt']}', end='{$row['end_dt']}'";

	if(mysqli_query($dbConn, $query)) {
		// Success! 2 rows are affected if it was a duplicate
		$quartersProc++;
	} else {
		// Failure.
		echo("    *** Error: Failed to insert/update quarter {$row['strm']}\n");
		echo("        " . mysqli_error($dbConn) . "\n" . $query . "\n");
		$failures++;
	}
}
debug("...100%");

// Update all the school
$schoolQuery = "INSERT INTO schools (id, code)";
$schoolQuery .= " SELECT SUBSTR( subject, 1, 2 ) AS school, acad_group FROM classes GROUP BY acad_group ORDER BY subject";
$schoolQuery .= " ON DUPLICATE KEY UPDATE code=(";
$schoolQuery .= " SELECT GROUP_CONCAT(DISTINCT(acad_group)) FROM classes WHERE id=SUBSTR(subject,1,2) GROUP BY SUBSTR(subject,1,2))";
debug("... Updating schools");
if(!mysqli_query($dbConn, $schoolQuery)) {
	echo("*** Error: Failed to update school listings\n");
	echo("    " . mysqli_error($dbConn) . "\n");
	echo("    " . $schoolQuery . "\n");
	$failures++;
}

// Select all the departments to add/update
$departmentQuery = "INSERT INTO departments (id, code, school)";
$departmentQuery .= " SELECT subject, acad_org, SUBSTR(subject,1,2) FROM classes GROUP BY subject ORDER BY subject";
$departmentQuery .= " ON DUPLICATE KEY UPDATE code=(SELECT acad_org FROM classes WHERE id=subject LIMIT 1),";
$departmentQuery .= " school=(SELECT SUBSTR(subject, 1,2) FROM classes WHERE id=subject LIMIT 1)";
debug("... Updating departments");
if(!mysqli_query($dbConn, $departmentQuery)) {
	echo("*** Error: Failed to update department listings\n");
	echo("    " . mysqli_error($dbConn) . "\n");
	$failures++;
}
$departmentsProc = mysqli_affected_rows($dbConn);

// Grab each COURSE from the classes table
$courseQuery = "SELECT strm, subject, catalog_nbr, descr, course_descrlong,";
$courseQuery .= " crse_id, crse_offer_nbr, session_code";
$courseQuery .= " FROM classes WHERE strm < 2131 GROUP BY crse_id, strm";
debug("... Updating courses\n0%", false);
$courseResult = mysqli_query($dbConn, $courseQuery);
if(!$courseResult) {
	echo("*** Error: Failed to get courses\n");
	echo("    " . mysqli_error($dbConn) . "\n");
	$failures++;
}
$procCourses = 0;
$totCourses = mysqli_num_rows($courseResult);
$outPercent = array(0);
while($row = mysqli_fetch_assoc($courseResult)) {
	// Progress Bar
	if($debugMode) {
		$percent = floor(($procCourses / $totCourses) * 100);
		if($percent % 10 == 0 && !in_array($percent, $outPercent)) {
			$outPercent[] = $percent;
			echo("...{$percent}%");
		}
	}

	// Make the term number correct
	preg_match("/(\d)(\d{3})/", $row['strm'], $match);
	$row['qtr'] = $match[1] . 0 . $match[2];

	// Escape the necessary fields
	$row['descr'] = mysqli_real_escape_string($dbConn, $row['descr']);
	$row['course_descrlong'] = mysqli_real_escape_string($dbConn, $row['course_descrlong']);

	// Insert or update the course
    $courseId = insertOrUpdateCourse($row['qtr'], $row['subject'], $row['catalog_nbr'],
	                                 0, $row['descr'], $row['course_descrlong']);
	if(!is_numeric($courseId)) {
		echo("    *** Error: Failed to update {$row['qtr']} {$row['subject']}-{$row['catalog_nbr']}\n");
		echo("    " . mysqli_error($dbConn) . "\n");
		$failures++;
	} else {
		// Process the sections that this course has
		// Step 2) Grab the sections that this course has from temp tables
		$sections = getTempSections($row['crse_id'], $row['crse_offer_nbr'], $row['strm'], $row['session_code']);
		if(!is_array($sections) || count($sections) == 0) {
			// We couldn't lookup the sections.
			echo("*** Failed to lookup sections for course\n");
			echo("    " . mysqli_error($dbConn) . "\n");
			continue;
		}

		// Iterate over the sections of the course
		foreach($sections as $sect) {
			// Fetch the first instructor for the section
			$instQuery = "SELECT CONCAT(first_name,' ',last_name) AS i FROM instructors";
			$instQuery .= " WHERE crse_id={$row['crse_id']} AND crse_offer_nbr={$row['crse_offer_nbr']}";
			$instQuery .= " AND strm={$row['strm']} AND session_code={$row['session_code']}";
			$instQuery .= " AND class_section='{$sect['class_section']}' LIMIT 1";
			$instResult = mysqli_query($dbConn, $instQuery);
			if(!$instResult) {
				echo(mysqli_error($dbConn) . "\n");
				echo($instQuery);
				cleanup();
				die();
			}
			$instructor = mysqli_fetch_assoc($instResult);
			if(!$instructor || $instructor['i'] == NULL) {
				$instructor = "TBA";
			} else {
				$instructor = $instructor['i'];
			}

			
			// Process the information about the sesction
			// Status --
			if($sect['class_stat'] == 'X' || $sect['schedule_print'] == 'N') {
				// Cancelled class (Cancelled, Nonenrollment, Non-printing)
				$status = 'X';
			} else {
				$status = $sect['enrl_stat'];
			}

			// Type --
			if($sect['instruction_mode'] == 'P') {
				// Regular mode
				$type = 'R';
			} else {
				// Just listen to the mode
				$type = $sect['instruction_mode'];
			}

			// Escapables --
			$title = mysqli_real_escape_string($dbConn, $sect['descr']);
			$instructor = mysqli_real_escape_string($dbConn, $instructor);

			// Insert into the sections table
			$sectId = insertOrUpdateSection($courseId, $sect['class_section'], $title, $instructor, $type,
			                                $status, $sect['enrl_cap'], $sect['enrl_tot']);
			if(!is_numeric($sectId)) {
				echo("*** Failed to insert/update section!\n");
				echo("    " . mysqli_error($dbConn) . "\n");
				$failures++;
				continue;
			}

			// PROCESS MEETING TIMES ///////////////////////////////////////
			// Remove the meeting times for the section
			$delQuery = "DELETE FROM times WHERE section = {$sectId}";
			if(!mysqli_query($dbConn, $delQuery)) {
				echo("*** Failed to remove section times\n");
				echo("    " . mysqli_error($dbConn) . "\n");
				$failures++;
				continue;
			}

			// Select all the meeting times of the section
			$timeQuery = "SELECT bldg, room_nbr, meeting_time_start, meeting_time_end, mon, tues, wed, thurs, fri, sat, sun";
			$timeQuery .= " FROM meeting WHERE crse_id={$row['crse_id']} AND crse_offer_nbr={$row['crse_offer_nbr']}";
			$timeQuery .= " AND strm={$row['strm']} AND session_code={$row['session_code']}";
			$timeQuery .= " AND class_section='{$sect['class_section']}'";
			$timeResult = mysqli_query($dbConn, $timeQuery);
			if(!$timeResult) {
				echo("*** Failed to query for meeting times\n");
				echo("    " . mysqli_error($dbConn) . "\n");
				$failures++;
				continue;
			}

			// Now iterate over them and insert
			while($time = mysqli_fetch_assoc($timeResult)) {
				// Process the meeting pattern
				// Meeting Time --
				$matches;
				preg_match('/(\d\d):(\d\d):\d\d/', $time['meeting_time_start'], $matches);
				$startTime = ($matches[1] * 60) + $matches[2];
				preg_match('/(\d\d):(\d\d):\d\d/', $time['meeting_time_end'], $matches);
				$endTime = ($matches[1] * 60) + $matches[2];

				// TBD times --
				if($time['bldg'] == 'UNKNOWN') {
					$time['bldg'] = 'TBA';
				}
				if($time['room_nbr'] == 'UNKNOWN') {
					$time['room_nbr'] = 'TBA';
				}

				// Escapables --
				$time['bldg'] = mysqli_real_escape_string($dbConn, $time['bldg']);
				$time['room_nbr'] = mysqli_real_escape_string($dbConn, $time['room_nbr']);

				// Iterate over the and execute a query
				$days = array($time['sun'], $time['mon'], $time['tues'], $time['wed'], $time['thurs'], $time['fri'], $time['sat']);
				foreach($days as $i => $dayTruth) {
					if($dayTruth == 'Y') {
						$timeInsert = "INSERT INTO times (section, day, start, end, building, room)";
						$timeInsert .= " VALUES({$sectId}, {$i}, {$startTime}, {$endTime}, ";
						$timeInsert .= "'{$time['bldg']}', '{$time['room_nbr']}')";
						if(!mysqli_query($dbConn, $timeInsert)) {
							echo("*** Failed to insert meeting time\n");
							echo("    " . mysqli_error($dbConn) . "\n");
							$failures++;
						}
					}
				}
			}
		}
	}
	$procCourses++;
}
debug("...100%");

// I guess we're done!
// Cleanup time
cleanup();

// Insert processing statistics
$query = "INSERT INTO scrapelog (timeStarted, timeEnded, quartersAdded, coursesAdded, coursesUpdated, sectionsAdded, sectionsUpdated, failures) ";
$query .= "VALUES('{$timeStarted}', '".time()."', '{$quartersProc}', '{$coursesAdded}', '{$coursesUpdated}', '{$sectAdded}', '{$sectUpdated}', '{$failures}')";
if(!mysqli_query($dbConn, $query)) {
	echo("*** Failed to update scrape log");
	echo("    " . mysqli_error($dbConn));
}
