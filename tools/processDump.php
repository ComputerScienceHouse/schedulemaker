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
$tempQuery = <<<ENE
CREATE TEMPORARY TABLE `classes` (
  `crse_id` int(6) NOT NULL,
  `crse_offer_nbr` int(2) NOT NULL,
  `strm` int(4) NOT NULL,
  `session_code` int(1) NOT NULL,
  `class_section` int(4) NOT NULL,
  `subject` int(8) NOT NULL,
  `catalog_nbr` int(3) NOT NULL,
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
$tempQuery = <<<ENE
CREATE TEMPORARY TABLE `meeting` (
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
$tempQuery = <<<ENE
CREATE TEMPORARY TABLE `instructors` (
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
