<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULEMAKER - Course Dump Parser
//
// @file	tools/parseDump.php
// @descrip	This file parses the dump of course information. It basically does
//			two procedures: Parse the files into a temporary database and then
//			process the database into the database we already have
// @author	Benjamin Russell (benrr101@csh.rit.edu)
// @author  Devin Matte (matted@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

// WORKAROUNDS /////////////////////////////////////////////////////////////
// Make sure the working directory is correct

chdir(dirname($_SERVER['SCRIPT_FILENAME']));

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once "../inc/config.php";
require_once "../inc/databaseConn.php";
require_once "../inc/timeFunctions.php";
require_once "Parser.php";

// IMPORTED CLASSES ////////////////////////////////////////////////////////
use Tools\Parser;

// COMMAND LINE ARGS ///////////////////////////////////////////////////////
$arguments = $_SERVER['argv'];

// DECLARING OBJECTS ///////////////////////////////////////////////////////
$dbConn = new mysqli($DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASS, $DATABASE_DB);

$parser = new Parser($dbConn, $arguments);

// START TIME //////////////////////////////////////////////////////////////
$timeStarted = time();
$quartersProc = 0;
$departmentsProc = 0;
$coursesAdded = 0;
$coursesUpdated = 0;
$sectAdded = 0;
$sectUpdated = 0;
$failures = 0;

// FILE EXIST? /////////////////////////////////////////////////////////////
// Verify that all the file locations are defined and they exist
if (empty($DUMPCLASSES) || !file_exists($DUMPCLASSES)) {
    $parser->halt("Fatal Error: Class dump file does not exist!");
}
if (empty($DUMPCLASSATTR) || !file_exists($DUMPCLASSATTR)) {
    $parser->halt("Fatal Error: Class attribute dump file does not exist!");
}
if (empty($DUMPINSTRUCT) || !file_exists($DUMPINSTRUCT)) {
    $parser->halt("Fatal Error: Instructor dump file does not exist!");
}
if (empty($DUMPMEETING) || !file_exists($DUMPMEETING)) {
    $parser->halt("Fatal Error: Class meeting pattern dump file does not exist!");
}
if (empty($DUMPNOTES) || !file_exists($DUMPNOTES)) {
    $parser->halt("Fatal Error: Class notes dump file does not exist!");
}

// FILE PARSING ////////////////////////////////////////////////////////////
// Open handles to the files that were given to us from ITS
$classFile = fopen($DUMPCLASSES, 'r');
$attrFile = fopen($DUMPCLASSATTR, 'r');
$instrFile = fopen($DUMPINSTRUCT, 'r');
$meetFile = fopen($DUMPMEETING, 'r');
$notesFile = fopen($DUMPNOTES, 'r');

//  Store how many bytes we have
$classSize = filesize($DUMPCLASSES);
$attrSize = filesize($DUMPCLASSATTR);
$instrSize = filesize($DUMPINSTRUCT);
$meetSize = filesize($DUMPMEETING);
$notesSize = filesize($DUMPNOTES);

// Build the temporary tables
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `classes` (
  `crse_id` int(6) UNSIGNED NOT NULL,
  `crse_offer_nbr` int(2) UNSIGNED NOT NULL,
  `strm` int(4) UNSIGNED NOT NULL,
  `session_code` varchar(4) NOT NULL,
  `class_section` varchar(4) NOT NULL,
  `subject` VARCHAR (4) NOT NULL,
  `catalog_nbr` VARCHAR(4) NOT NULL,
  `descr` text NOT NULL,
  `topic` text NOT NULL,
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

if (mysqli_query($dbConn, $tempQuery)) {
    $parser->debug("... Temporary class table created successfully");
} else {
    $parser->halt(["Error: Failed to create temporary class table", mysqli_error($dbConn)]);
}

$parser->fileToTempTable("classes", $classFile, 24, $classSize, "procClassArray");
fclose($classFile);

// Build a temporary table for the meeting patterns
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `meeting` (
  `crse_id` int(6) NOT NULL,
  `crse_offer_nbr` int(2) NOT NULL,
  `strm` int(4) NOT NULL,
  `session_code` varchar(4) NOT NULL,
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

if (mysqli_query($dbConn, $tempQuery)) {
    $parser->debug("... Temporary meeting pattern table created successfully");
} else {
    $parser->halt(["Error: Failed to create temporary meeting pattern table", mysqli_error($dbConn)]);
}

$parser->fileToTempTable("meeting", $meetFile, 19, $meetSize, 'procMeetArray');


// Process the instructor file
$tempQuery = <<<ENE
CREATE TABLE IF NOT EXISTS `instructors` (
  `crse_id` int(6) NOT NULL,
  `crse_offer_nbr` int(2) NOT NULL,
  `strm` int(4) NOT NULL,
  `session_code` varchar(4) NOT NULL,
  `class_section` varchar(4) NOT NULL,
  `class_mtg_nbr` int(2) NOT NULL,
  `last_name` varchar(30) NOT NULL,
  `first_name` varchar(30) NOT NULL,
  INDEX (`crse_id`,`crse_offer_nbr`,`strm`,`session_code`,`class_section`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
ENE;

if (mysqli_query($dbConn, $tempQuery)) {
    $parser->debug("... Temporary instructor table created successfully");
} else {
    $parser->halt(["Error: Failed to create temporary instructor table", mysqli_error($dbConn)]);
}

$parser->fileToTempTable("instructors", $instrFile, 8, $instrSize, 'procInstrArray');

// DATABASE PARSING ////////////////////////////////////////////////////////
// Select all the 'quarters' from the meeting pattern to get the start/end
// times for the quarter. Then insert into the quarters table
$quarterQuery = "SELECT strm, start_dt, end_dt FROM meeting GROUP BY strm";
$parser->debug("... Creating quarters\n0%", false);
$quarterResult = mysqli_query($dbConn, $quarterQuery);
$procQuart = 0;
$totQuart = mysqli_num_rows($quarterResult);
$outPercent = [0];
$quarters = [];
while ($row = mysqli_fetch_assoc($quarterResult)) {
    // Progress bar
    if ($parser->debugMode) {
        $percent = floor(($procQuart / $totQuart) * 100);
        if ($percent % 10 == 0 && !in_array($percent, $outPercent)) {
            $outPercent[] = $percent;
            echo("...{$percent}%");
        }
    }

    // We're not ignant. 5 digit terms!
    preg_match("/(\d)(\d{3})/", $row['strm'], $match);
    $row['strm'] = $match[1] . 0 . $match[2];

    // Insert the quarter
    // TODO: Change schema from quarters to semesters (I doubt they're switching back anytime soon)
    $query = "INSERT INTO quarters (`quarter`, `start`, `end`)";
    $query .= " VALUES({$row['strm']}, '{$row['start_dt']}', '{$row['end_dt']}')";
    $query .= " ON DUPLICATE KEY UPDATE";
    $query .= " start='{$row['start_dt']}', end='{$row['end_dt']}'";

    if (mysqli_query($dbConn, $query)) {
        // Success! 2 rows are affected if it was a duplicate
        $quartersProc++;
        $quarters[] = $row['strm'];
    } else {
        // Failure.
        echo("    *** Error: Failed to insert/update quarter {$row['strm']}\n");
        echo("        " . mysqli_error($dbConn) . "\n" . $query . "\n");
        $failures++;
    }
}
$parser->debug("...100%");

// Mark all existing sections as cancelled. If they truly exist, they will be
// reinstated later in the run
$quarters = implode(",", $quarters);
$cancelQuery = "UPDATE sections AS s
                  JOIN courses AS c ON c.id = s.course
                SET status = 'X'
                WHERE c.quarter IN ({$quarters})";
$parser->debug("... Marking all sections as canceled");
if (!mysqli_query($dbConn, $cancelQuery)) {
    echo("*** Error: Failed to mark sections as canceled.\n");
    echo("    " . mysqli_error($dbConn) . "\n");
    echo("    " . $cancelQuery . "\n");
    $failures++;
    die();
}

// Update all the school
// NOTE: After semesters start, we can no longer use the subject as a lookup
// for the schools. Subjects are not provided with semester data, and the schools
// for quarters are well defined. We shall no longer update numeric schools.
$schoolQuery = "INSERT INTO schools (code)
                SELECT acad_group FROM classes
                  WHERE acad_group NOT IN(SELECT code FROM schools WHERE code IS NOT NULL)";
$parser->debug("... Updating schools");
if (!mysqli_query($dbConn, $schoolQuery)) {
    echo("*** Error: Failed to update school listings\n");
    echo("    " . mysqli_error($dbConn) . "\n");
    echo("    " . $schoolQuery . "\n");
    $failures++;
}

// Select all the departments to add/update
// NOTE: Again, we're not going to pay attention to numeric schools any longer.
$departmentQuery = "INSERT INTO departments(`code`, `school`)
                      SELECT c.`acad_org`, s.`id`
                      FROM classes AS c
                          JOIN schools AS s ON s.`code` = c.`acad_group`
                      WHERE strm > 2130
                      GROUP BY c.`acad_org`
                    ON DUPLICATE KEY UPDATE school=VALUES(school)";
$parser->debug("... Updating departments");
if (!mysqli_query($dbConn, $departmentQuery)) {
    echo("*** Error: Failed to update department listings\n");
    echo("    " . mysqli_error($dbConn) . "\n");
    $failures++;
}
$departmentsProc = mysqli_affected_rows($dbConn);

// Grab each COURSE from the classes table
$courseQuery = "SELECT strm, subject, units, acad_org, catalog_nbr, descr, course_descrlong,";
$courseQuery .= " crse_id, crse_offer_nbr, session_code";
$courseQuery .= " FROM classes WHERE strm < 20130 GROUP BY crse_id, strm, session_code";
$parser->debug("... Updating courses\n0%", false);
$courseResult = mysqli_query($dbConn, $courseQuery);
if (!$courseResult) {
    echo("*** Error: Failed to get courses\n");
    echo("    " . mysqli_error($dbConn) . "\n");
    $failures++;
}
$procCourses = 0;
$totCourses = mysqli_num_rows($courseResult);
$outPercent = [0];
while ($row = mysqli_fetch_assoc($courseResult)) {
    // Progress Bar
    if ($parser->debugMode) {
        $percent = floor(($procCourses / $totCourses) * 100);
        if ($percent % 10 == 0 && !in_array($percent, $outPercent)) {
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
    @$courseId = $parser->insertOrUpdateCourse($row['qtr'], $row['acad_org'], $row['subject'], $row['catalog_nbr'],
        (int)$row['units'], $row['descr'], $row['course_descrlong']);
    if (!is_numeric($courseId)) {
        echo("    *** Error: Failed to update {$row['qtr']} {$row['subject']}-{$row['catalog_nbr']}\n");
        echo("    ");
        var_dump($courseId);
        echo("\n");
        echo("    ");
        var_dump($row);
        echo("\n");
        $failures++;
    } else {
        // Process the sections that this course has
        // Step 2) Grab the sections that this course has from temp tables
        $sections = $parser->getTempSections($row['crse_id'], $row['crse_offer_nbr'], $row['strm'], $row['session_code']);
        if (!is_array($sections) || count($sections) == 0) {
            // We couldn't lookup the sections.
            echo("*** Failed to lookup sections for course\n");
            echo("    " . mysqli_error($dbConn) . "\n");
            continue;
        }

        // Iterate over the sections of the course
        foreach ($sections as $sect) {
            // Fetch the first instructor for the section
            $instQuery = "SELECT CONCAT(first_name,' ',last_name) AS i FROM instructors";
            $instQuery .= " WHERE crse_id={$row['crse_id']} AND crse_offer_nbr={$row['crse_offer_nbr']}";
            $instQuery .= " AND strm={$row['strm']} AND session_code='{$row['session_code']}'";
            $instQuery .= " AND class_section='{$sect['class_section']}' LIMIT 1";
            $instResult = mysqli_query($dbConn, $instQuery);
            if (!$instResult) {
                $parser->halt(["Failed to find an instructor for course", mysqli_error($dbConn)]);
            }
            $instructor = mysqli_fetch_assoc($instResult);
            if (!$instructor || $instructor['i'] == null) {
                $instructor = "TBA";
            } else {
                $instructor = $instructor['i'];
            }


            // Process the information about the sesction
            // Status --
            if ($sect['class_stat'] == 'X' || $sect['schedule_print'] == 'N') {
                // Cancelled class (Cancelled, Nonenrollment, Non-printing)
                $status = 'X';
            } else {
                $status = $sect['enrl_stat'];
            }

            // Type --
            if ($sect['instruction_mode'] == 'P') {
                // Regular mode
                $type = 'R';
            } else {
                // Just listen to the mode
                $type = $sect['instruction_mode'];
            }

            // Escapables --
            $title = (empty($sect['topic'])) ? "" : $sect['topic'];
            $title = mysqli_real_escape_string($dbConn, $title);
            $instructor = mysqli_real_escape_string($dbConn, $instructor);

            // Insert into the sections table
            $sectId = $parser->insertOrUpdateSection($courseId, $sect['class_section'], $title, $instructor, $type,
                $status, $sect['enrl_cap'], $sect['enrl_tot']);
            if (!is_numeric($sectId)) {
                echo("*** Failed to insert/update section!\n");
                echo("    " . mysqli_error($dbConn) . "\n");
                $failures++;
                continue;
            }

            // PROCESS MEETING TIMES ///////////////////////////////////////
            // Remove the meeting times for the section
            $delQuery = "DELETE FROM times WHERE section = {$sectId}";
            if (!mysqli_query($dbConn, $delQuery)) {
                echo("*** Failed to remove section times\n");
                echo("    " . mysqli_error($dbConn) . "\n");
                $failures++;
                continue;
            }

            // Select all the meeting times of the section
            $timeQuery = "SELECT bldg, room_nbr, meeting_time_start, meeting_time_end, mon, tues, wed, thurs, fri, sat, sun";
            $timeQuery .= " FROM meeting WHERE crse_id={$row['crse_id']} AND crse_offer_nbr={$row['crse_offer_nbr']}";
            $timeQuery .= " AND strm={$row['strm']} AND session_code='{$row['session_code']}'";
            $timeQuery .= " AND class_section='{$sect['class_section']}'";
            $timeResult = mysqli_query($dbConn, $timeQuery);
            if (!$timeResult) {
                echo("*** Failed to query for meeting times\n");
                echo("    " . mysqli_error($dbConn) . "\n");
                $failures++;
                continue;
            }

            // Now iterate over them and insert
            while ($time = mysqli_fetch_assoc($timeResult)) {
                $origBldg = $time['bldg'];
                // Process the meeting pattern
                // Meeting Time --
                $matches;
                preg_match('/(\d\d):(\d\d):\d\d/', $time['meeting_time_start'], $matches);
                $startTime = ($matches[1] * 60) + $matches[2];
                preg_match('/(\d\d):(\d\d):\d\d/', $time['meeting_time_end'], $matches);
                $endTime = ($matches[1] * 60) + $matches[2];

                // Special Buildings
                switch ($time['bldg']) {
                    case "UNKNOWN":
                    case "TBD":
                        $time['bldg'] = 'TBA';
                        $time['room_nbr'] = 'TBA';
                        break;

                    case "OFFC":
                        $time['bldg'] = 'OFF';
                        $time['room_nbr'] = 'SITE';
                        break;

                    case "ONLINE":
                        $time['bldg'] = 'ON';
                        $time['room_nbr'] = 'LINE';
                        break;
                }

                // Lop off a leading 0 (if < 100)
                if (is_numeric($time['bldg']) && strlen($time['bldg']) >= 3 && $time['bldg'] < 100) {
                    $time['bldg'] = substr($time['bldg'], -2);
                }

                // Building 7/Institute Hall Situations
                if (preg_match("/[0-9]{3}[A-Za-z]/", $time['bldg'])) {
                    $time['bldg'] = substr($time['bldg'], -3);
                }

                // Escapables --
                $time['bldg'] = mysqli_real_escape_string($dbConn, $time['bldg']);
                $time['room_nbr'] = mysqli_real_escape_string($dbConn, $time['room_nbr']);

                // Iterate over the and execute a query
                $days = [$time['sun'], $time['mon'], $time['tues'], $time['wed'], $time['thurs'], $time['fri'], $time['sat']];
                foreach ($days as $i => $dayTruth) {
                    if ($dayTruth == 'Y') {
                        // TODO: Fix schema to allow `room` to be larger than varchar(4)
                        $timeInsert = "INSERT INTO times (section, day, start, end, building, room)";
                        $timeInsert .= " VALUES({$sectId}, {$i}, {$startTime}, {$endTime}, ";
                        $timeInsert .= "'{$time['bldg']}', '{$time['room_nbr']}')";
                        if (!mysqli_query($dbConn, $timeInsert)) {
                            echo("*** Failed to insert meeting time\n");
                            echo("    " . mysqli_error($dbConn) . "\n");
                            echo("    {$origBldg}=>{$time['bldg']}");
                            $failures++;
                        }
                    }
                }
            }
        }
    }
    $procCourses++;
}
$parser->debug("...100%");

// I guess we're done!
// Cleanup time
$parser->cleanup();

// Insert processing statistics
$query = "INSERT INTO scrapelog (timeStarted, timeEnded, quartersAdded, coursesAdded, coursesUpdated, sectionsAdded, sectionsUpdated, failures) ";
$query .= "VALUES('{$timeStarted}', '" . time() . "', '{$quartersProc}', '{$coursesAdded}', '{$coursesUpdated}', '{$sectAdded}', '{$sectUpdated}', '{$failures}')";
if (!mysqli_query($dbConn, $query)) {
    echo("*** Failed to update scrape log");
    echo("    " . mysqli_error($dbConn));
}
