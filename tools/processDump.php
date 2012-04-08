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

// FUNCTIONS ///////////////////////////////////////////////////////////////
function debug($str, $nl = true) {
	global $debugMode;
	if($debugMode) {
		echo($str . (($nl) ? "\n" : ""));
	}
}

function fileToTempTable($tableName, $file, $fields, $fileSize, $procFunc=NULL) {
	global $debugMode;

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
		}

		// Build a query
		$insQuery = "INSERT INTO {$tableName} VALUES('" . implode("', '", $lineSplit) . "')";
		if(!mysql_query($insQuery)) {
			echo("*** Failed to insert {$tableName}\n");
			echo("    " . mysql_error() . "\n");
			continue;
		}
	}

	debug("...100%");
}

// COMMAND LINE ARGS ///////////////////////////////////////////////////////
$arguments = $_SERVER['argv'];
$debugMode = in_array("-d", $arguments);
$quietMode = in_array("-q", $arguments);

// FILE EXIST? /////////////////////////////////////////////////////////////
// Verify that all the file locations are defined and they exist
if(empty($DUMPCLASSES) || !file_exists($DUMPCLASSES)) {
	echo "*** Fatal Error: Class dump file does not exist!\n";
	die();
}
if(empty($DUMPCLASSATTR) || !file_exists($DUMPCLASSATTR)) {
	echo "*** Fatal Error: Class attribute dump file does not exist!\n";
	die();
}
if(empty($DUMPINSTRUCT) || !file_exists($DUMPINSTRUCT)) {
	echo "*** Fatal Error: Instructor dump file does not exist!\n";
	die();
}
if(empty($DUMPMEETING) || !file_exists($DUMPMEETING)) {
	echo "*** Fatal Error: Class meeting pattern dump file does not exist!\n";
	die();
}
if(empty($DUMPNOTES) || !file_exists($DUMPNOTES)) {
	echo "*** Fatal Error: Class notes dump file does not exist!\n";
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
mysql_query("DROP TABLE classes");
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `classes` (
  `crse_id` int(6) NOT NULL,
  `crse_offer_nbr` int(2) NOT NULL,
  `strm` int(4) NOT NULL,
  `session_code` int(1) NOT NULL,
  `class_section` int(4) NOT NULL,
  `subject` int(4) UNSIGNED ZEROFILL NOT NULL,
  `catalog_nbr` int(3) UNSIGNED ZEROFILL NOT NULL,
  `descr` text NOT NULL,
  `class_nbr` int(5) NOT NULL,
  `ssr_component` varchar(3) NOT NULL,
  `enrl_stat` varchar(1) NOT NULL,
  `class_stat` varchar(1) NOT NULL,
  `class_type` varchar(1) NOT NULL,
  `schedule_print` varchar(1) NOT NULL,
  `enrl_cap` int(4) NOT NULL,
  `enrl_tot` int(4) NOT NULL,
  `institution` varchar(5) NOT NULL,
  `acad_org` varchar(10) NOT NULL,
  `acad_group` varchar(5) NOT NULL,
  `acad_career` varchar(4) NOT NULL,
  `instruction_mode` varchar(2) NOT NULL,
  `course_descrlong` text NOT NULL,
  PRIMARY KEY (`crse_id`,`crse_offer_nbr`,`strm`,`session_code`,`class_section`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ENE;
if(mysql_query($tempQuery)) {
	debug("... Temporary class table created successfully");
} else {
	echo("*** Error: Failed to create temporary class table\n");
	echo("    " . mysql_error() . "\n");
	die();
}


// Process the class file
function procClassArray($lineSplit) {
	$lineSplit[7]  = mysql_real_escape_string($lineSplit[7]);	// Class title
	$lineSplit[21] = mysql_real_escape_string($lineSplit[21]);	// Class description
	return $lineSplit;
}
fileToTempTable("classes", $classFile, 22, $classSize, "procClassArray");
fclose($classFile);

// Build a temporary table for the meeting patterns
mysql_query("DROP TABLE meeting");
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `meeting` (
  `crse_id` int(6) NOT NULL,
  `crse_offer_nbr` int(2) NOT NULL,
  `strm` int(4) NOT NULL,
  `session_code` int(1) NOT NULL,
  `class_section` int(4) NOT NULL,
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
if(mysql_query($tempQuery)) {
	debug("... Temporary meeting pattern table created successfully");
} else {
	echo("*** Error: Failed to create temporary meeting pattern table\n");
	echo("    " . mysql_error() . "\n");
	die();
}

// Process the meeting pattern file
function procMeetArray($lineSplit) {
	// Turn the start/end times from 03:45 PM to 154500
	preg_match("/(\d\d):(\d\d) ([A-Z]{2})/", $lineSplit[10], $start);
	$lineSplit[10] = (($start[3] == 'PM') ? $start[1] + 12 : $start[1]) . $start[2] . "00";
	preg_match("/(\d\d):(\d\d) ([A-Z]{2})/", $lineSplit[11], $end);
	$lineSplit[11] = (($end[3] == 'PM') ? $end[1] + 12 : $end[1]) . $end[2] . "00";
	return $lineSplit;
}
fileToTempTable("meeting", $meetFile, 19, $meetSize, 'procMeetArray');


// Process the instructor file
mysql_query("DROP TABLE instructors");
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `instructors` (
  `crse_id` int(6) NOT NULL,
  `crse_offer_nbr` int(2) NOT NULL,
  `strm` int(4) NOT NULL,
  `session_code` int(1) NOT NULL,
  `class_section` int(4) NOT NULL,
  `class_mtg_nbr` int(2) NOT NULL,
  `last_name` varchar(30) NOT NULL,
  `first_name` varchar(30) NOT NULL,
  INDEX (`crse_id`,`crse_offer_nbr`,`strm`,`session_code`,`class_section`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ENE;
if(mysql_query($tempQuery)) {
	debug("... Temporary instructor table created successfully");
} else {
	echo("*** Error: Failed to create temporary instructor table\n");
	echo("    " . mysql_error() . "\n");
	die();
}

function procInstrArray($lineSplit) {
	// Escape the instructor names
	$lineSplit[6] = mysql_real_escape_string($lineSplit[6]);
	$lineSplit[7] = mysql_real_escape_string($lineSplit[7]);
	return $lineSplit;
}
fileToTempTable("instructors", $instrFile, 8, $instrSize, 'procInstrArray');

// DATABASE PARSING ////////////////////////////////////////////////////////
// Setup for counting statistics
$failures = 0;
$quartersAdded = 0;
$departmentsAdded = 0;
$coursesAdded = 0;

// Select all the 'quarters' from the meeting pattern to get the start/end
// times for the quarter. Then insert into the quarters table
$quarterQuery = "SELECT strm, start_dt, end_dt FROM meeting GROUP BY strm";
debug("... Creating quarters");
$quarterResult = mysql_query($quarterQuery);
while($row = mysql_fetch_assoc($quarterResult)) {
	// We're not ignant. 5 digit terms!
	preg_match("/(\d)(\d{3})/", $row['strm'], $match);
	$row['strm'] = $match[1] . 0 . $match[2];

	// Insert the quarter
	$query = "INSERT INTO quarters (quarter, start, end)";
	$query .= " VALUES({$row['strm']}, '{$row['start_dt']}', '{$row['end_dt']}')";
	$query .= " ON DUPLICATE KEY UPDATE";
	$query .= " start='{$row['start_dt']}', end='{$row['end_dt']}'";

	if(mysql_query($query)) {
		// Success! 2 rows are affected if it was a duplicate
		debug("    ... Processed {$row['strm']}");
		$quartersAdded += mysql_affected_rows();
	} else {
		// Failure.
		echo("    *** Error: Failed to insert/update quarter {$row['strm']}\n");
		echo("        " . mysql_error() . "\n" . $query . "\n");
		$failures++;
	}
}

// Update all the school
$schoolQuery = "INSERT IGNORE INTO schools (id, code)";
$schoolQuery .= " SELECT SUBSTR( subject, 1, 2 ), acad_group FROM classes GROUP BY acad_group ORDER BY subject";
debug("... Updating schools");
if(!mysql_query($schoolQuery)) {
	echo("*** Error: Failed to update school listings\n");
	echo("    " . mysql_error() . "\n");
	$failures++;
}

// Select all the departments to add/update
$departmentQuery = "INSERT IGNORE INTO departments (id, code)";
$departmentQuery .= " SELECT subject, acad_org FROM classes GROUP BY acad_org";
debug("... Updating departments");
if(!mysql_query($departmentQuery)) {
	echo("*** Error: Failed to update department listings\n");
	echo("    " . mysql_error() . "\n");
	$failures++;
}

// Grab each COURSE from the classes table
$courseQuery = "SELECT strm, subject, catalog_nbr, descr, course_descrlong,";
$courseQuery .= " crse_id, crse_offer_nbr, session_code";
$courseQuery .= " FROM classes GROUP BY crse_id";
debug("... Updating courses");
$courseResult = mysql_query($courseQuery);
while($row = mysql_fetch_assoc($courseResult)) {
	// Make the term number correct
	preg_match("/(\d)(\d{3})/", $row['strm'], $match);
	$row['qtr'] = $match[1] . 0 . $match[2];

	// Escape the necessary fields
	$row['descr'] = mysql_real_escape_string($row['descr']);
	$row['course_descrlong'] = mysql_real_escape_string($row['course_descrlong']);

	debug("    ... Processing {$row['qtr']} {$row['subject']}-{$row['catalog_nbr']}");

	// Insert or update the course
	$cUpdate = "INSERT INTO courses (quarter, department, course, title, description)";
	$cUpdate .= " VALUES({$row['qtr']},{$row['subject']},{$row['catalog_nbr']},'{$row['descr']}','{$row['course_descrlong']}')";
	$cUpdate .= " ON DUPLICATE KEY UPDATE title='{$row['descr']}', description='{$row['course_descrlong']}'";

	if(!mysql_query($cUpdate)) {
		echo("    *** Error: Failed to update {$row['qtr']} {$row['subject']}-{$row['catalog_nbr']}\n");
		echo("    " . mysql_error() . "\n");
		$failures++;
	} else {
		// Successfully updated/added the course
		$coursesAdded += mysql_affected_rows();

		// Process the sections that this course has
		// Step 1) Get the id of the course from the permament tables
		$idQuery = "SELECT id FROM courses";
		$idQuery .= " WHERE quarter={$row['qtr']} AND department={$row['subject']} AND course={$row['catalog_nbr']}";
		$idResult = mysql_query($idQuery);
		if(!$idResult || !mysql_num_rows($idResult)) {
			echo("        *** Failed to lookup id for course\n");
			echo("            " . mysql_error() . "\n");
			continue;
		}

		// Fetch the id into a var
		$id = mysql_fetch_assoc($idResult);
		$id = $id['id'];

		// Step 2) Grab the sections that this course has from temp tables
		$sectSel = "SELECT class_section, descr, enrl_stat, class_stat, class_type, enrl_cap, enrl_tot, instruction_mode";
		$sectSel .= " FROM classes WHERE crse_id={$row['crse_id']} AND crse_offer_nbr={$row['crse_offer_nbr']}";
		$sectSel .= " AND strm={$row['strm']} AND session_code={$row['session_code']}";
		$sectResult = mysql_query($sectSel);
		if(!$sectResult || !mysql_num_rows($sectResult)) {
			// We couldn't lookup the sections.
			echo("        *** Failed to lookup sections for course\n");
			echo("            " . mysql_error() . "\n");
			continue;
		}

		// Iterate over the sections of the course
		while($sRow = mysql_fetch_assoc($sectResult)) {
			debug("        ... Processing section {$sRow['class_section']}");

			// Fetch the first instructor for the section
			$instQuery = "SELECT CONCAT(first_name,' ',last_name) AS i FROM instructors";
			$instQuery .= " WHERE crse_id={$row['crse_id']} AND crse_offer_nbr={$row['crse_offer_nbr']}";
			$instQuery .= " AND strm={$row['strm']} AND session_code={$row['session_code']}";
			$instQuery .= " AND class_section={$sRow['class_section']} LIMIT 1";
			$instResult = mysql_query($instQuery);
			if(!$instResult) {
				echo(mysql_error() . "\n");
				echo($instQuery);
				die();
			}
			$instructor = mysql_fetch_assoc($instResult);
			if(!$instructor || $instructor['i'] == NULL) {
				debug("            --- Instructor not found");
				$instructor = NULL;
			} else {
				$instructor = $instructor['i'];
			}
			
			// Process the information about the sesction
			// Status --
			if($sRow['class_stat'] == 'X' || $sRow['class_type'] == 'N') {
				// Cancelled class
				$status = 'X';
			} else {
				$status = $sRow['enrl_stat'];
			}

			// Type --
			if($sRow['instruction_mode'] == 'P') {
				// Regular mode
				$type = 'R';
			} else {
				// Just listen to the mode
				$type = $sRow['instruction_mode'];
			}

			// Escapables --
			$title = mysql_real_escape_string($sRow['descr']);
			$instructor = mysql_real_escape_string($instructor);

			// Insert into the sections table
			$sectQuery = "INSERT INTO sections (course,section,title,instructor,type,status,maxenroll,curenroll)";
			$sectQuery .= " VALUES({$id}, {$sRow['class_section']}, '{$title}', '{$instructor}', '{$type}',";
			$sectQuery .= " '{$status}', {$sRow['enrl_cap']}, {$sRow['enrl_tot']} )";
			$sectQuery .= " ON DUPLICATE KEY UPDATE title='{$title}', instructor='{$instructor}', status='{$status}',";
			$sectQuery .= " maxenroll={$sRow['enrl_cap']}, curenroll={$sRow['enrl_tot']}";
			
			if(!mysql_query($sectQuery)) {
				echo("            *** Failed to insert section!\n");
				echo("            " . mysql_error() . "\n");
			}
		}
	}
}

// Process the meeting times

// Insert processing statistics

