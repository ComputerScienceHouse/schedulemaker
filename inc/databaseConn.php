<?php
////////////////////////////////////////////////////////////////////////////
// DATABASE CONNECTION
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	inc/databaseConn.php
// @descrip	Provides mysql database connection for the system.
////////////////////////////////////////////////////////////////////////////

// Bring in the config data
require_once dirname(__FILE__) . "/config.php";

// Make a connection to the database
global $DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASS, $DATABASE_DB;
$dbConn = mysql_connect($DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASS);
mysql_select_db($DATABASE_DB, $dbConn);

// Error check
if(!$dbConn) {
	die("Could not connect to database: " . mysql_error());
}

////////////////////////////////////////////////////////////////////////////
// FUNCTIONS

/**
 * Retrieves the meeting information for a section
 * @param	$sectionData	array	Information about a section MUST HAVE:
 *									title, instructor, curenroll, maxenroll,
 *									department, course, section, section id,
 *									type.
 * @return	array	A course array with all the information about the course
 */
function getMeetingInfo($sectionData) {
	// Store the course information

    $course = array(
        "title"      => $sectionData['title'],
        "instructor" => $sectionData['instructor'],
        "curenroll"  => $sectionData['curenroll'],
        "maxenroll"  => $sectionData['maxenroll'],
        "courseNum"  => "{$sectionData['department']}-{$sectionData['course']}-{$sectionData['section']}",
        "sectionId"  => $sectionData['id'],
        "online"     => $sectionData['type'] == "O"
        );

    // If the course is online, then don't even bother looking for it's times
    if($course['online']) { return $course; }

    // Now we query for the times of the section
    $query = "SELECT b.code, b.number, b.off_campus, t.room, t.day, t.start, t.end ";
    $query .= "FROM times AS t JOIN buildings AS b ON b.number=t.building ";
    $query .= "WHERE section = {$sectionData['id']}";
    $result = mysql_query($query);
    if(!$result) {
        throw new Exception("mysql:" . mysql_error());
    }
    while($row = mysql_fetch_assoc($result)) {
        $course["times"][] = array(
            "bldg"       => array("code"=>$row['code'], "number"=>$row['number']),
            "room"       => $row['room'],
            "day"        => $row['day'],
            "start"      => $row['start'],
            "end"        => $row['end'],
            "off_campus" => $row['off_campus'] == '1'
            );
    }

	return $course;
}

/**
 * Retrieves a course based on the id of a section
 * @param	$id		int		The if of the section
 * @return 	array	The information about the section
 */
function getCourseBySectionId($id) {
    // Sanity check for the section id
    if($id == "" || !is_numeric($id)) {
        trigger_error("A valid section id was not provided");
    }

	// Build the query to get section info
	$query = "SELECT s.id,
                (CASE WHEN (s.title != '') THEN s.title ELSE c.title END) AS title,
                s.instructor, s.curenroll, s.maxenroll, s.type, c.quarter, c.course, s.section, d.number, d.code
                FROM sections AS s
                  JOIN courses AS c ON s.course = c.id
                  JOIN departments AS d ON d.id = c.department
                WHERE s.id = '{$id}'";

	// Actually run the query
	$result = mysql_query($query);
	// @TODO: Error handling
	$row = mysql_fetch_assoc($result);
    if($row['quarter'] > 20130) {
        $row['department'] = $row['code'];
    } else {
        $row['department'] = $row['number'];
    }

	return ($row) ? getMeetingInfo($row) : null;
}

/**
 * Retrieves a course specified by very specific descriptors. The resulting
 * array will contain all the information needed for the course: title,
 * instructor, enrollment, times[building, room, day, start, end].
 * @param	int		$term	    The quarter that the course is in
 * @param	int		$dept	    The department the course is in
 * @param	int		$courseNum	The course number
 * @param	int		$sectNum	The section number of the course
 * @throws	Exception			Thrown if a database error occurs, the course
 *								could not reliably be determined, or the course
 *								does not exist "type:msg"
 * @return	array				Course formatted into array as described above
 */
function getCourse($term, $dept, $courseNum, $sectNum) {
	// Build the query
    if($term > 20130) {
        $query = "SELECT s.id,
                    (CASE WHEN (s.title != '') THEN s.title ELSE c.title END) AS title,
                    s.instructor, s.curenroll, s.maxenroll, s.type, d.code AS department, c.course, s.section
                  FROM sections AS s
                    JOIN courses AS c ON c.id=s.course
                    JOIN departments AS d ON d.id=c.department
                  WHERE c.quarter = '{$term}'
                    AND d.code = '{$dept}'
                    AND c.course = '{$courseNum}' AND s.section = '{$sectNum}'";
    } else {
        $query = "SELECT s.id,
                    (CASE WHEN (s.title != '') THEN s.title ELSE c.title END) AS title,
                    s.instructor, s.curenroll, s.maxenroll, s.type, d.number AS department, c.course, s.section
                  FROM sections AS s
                    JOIN courses AS c ON c.id=s.course
                    JOIN departments AS d ON d.id=c.department
                  WHERE c.quarter = '{$term}'
                    AND d.number = '{$dept}'
                    AND c.course = '{$courseNum}' AND s.section = '{$sectNum}'";
    }

	// Execute the query and error check
	$result = mysql_query($query);
	if(!$result) {
		throw new Exception("mysql:" . mysql_error());
	} elseif(mysql_num_rows($result) > 1) {
		throw new Exception("ambiguous:{$term}-{$dept}-{$courseNum}-{$sectNum}");
	} elseif(mysql_num_rows($result) == 0) {
		throw new Exception("objnotfound:{$term}-{$dept}-{$courseNum}-{$sectNum}");
	}

	return getMeetingInfo(mysql_fetch_assoc($result));
}

/**
 * Does a query for all the terms in the database and then dumps them to
 * a handy drop down field. Parses them like 'Spring ####' for display val.
 * The option value will be the 5 digit number
 * @param	string	$fieldName	The name of the field (useful for multiple
 *								quarter fields in a single form)
 * @param	string	$selected	The selected value to add to the field
 * @return	string	A dropdown field as described
 */
function getTermField($fieldName = "term", $selected = null) {
	// Build the start of the field
	$return = "<select id='{$fieldName}' name='{$fieldName}'>";
	
	// Query the database for the quarters
	$query = "SELECT quarter FROM quarters ORDER BY quarter DESC";
	$result = mysql_query($query);
	
	// Output the quarters as options
    $curYear = 0;
    $optGroupOpen = false;
	while($row = mysql_fetch_assoc($result)) {
		$term = $row['quarter'];

		// Parse it into a year-quarter thingy
		$year = substr(strval($term), 0, 4);
        $termNum = substr(strval($term), -1);
        if($year >= 2013) {
            switch($termNum) {
                case 1: $termName = "Fall"; break;
                case 3: $termName = "Winter Intersession"; break;
                case 5: $termName = "Spring"; break;
                case 8: $termName = "Summer"; break;
                default: $termName = "Unknown";
            }
        } else {
            switch($termNum) {
                case 1: $termName = "Fall"; break;
                case 2: $termName = "Winter"; break;
                case 3: $termName = "Spring"; break;
                case 4: $termName = "Summer"; break;
                default: $termName = "Unknown";
            }
        }

        // Output the year as a grouping
        if($curYear != $year) {
            $curYear = $year;
            $nextYear = (int)$year + 1;
            if($optGroupOpen) {
                $return .= "</optgroup>";
            }
            $optGroupOpen = true;
            $return .= "<optgroup label='{$year} - {$nextYear}'>";
        }

		// Now output it
		$return .= "<option value='{$term}'" . (($selected == $term) ? " selected='selected'" : "") . ">{$year} {$termName}</option>";
	}

	// Close it up and return it
    if($optGroupOpen) {
        $return .= "</optgroup>";
    }
	$return .= "</select>";
	return $return;
}

function getCollegeField($fieldname = "school", $selected = null, $any = false) {
	$return = "<select id='{$fieldname}' name='{$fieldname}'>";
	$return .= ($any) ? "<option value='any'>Any College</option>" : "";
	
	// Query for the schools
	$query = "SELECT id, number, title FROM schools WHERE number IS NOT NULL ORDER BY id";
	$result = mysql_query($query);

	// Output the schools as options
	while($row = mysql_fetch_assoc($result)) {
		$return .= "<option value='{$row['id']}'" . (($selected == $row['id']) ? " selected='selected'" : "") . ">{$row['number']} {$row['title']}</option>";
	}

	// Close it up and return it
	$return .= "</select>";
	return $return;
}

function getDepartmentField($fieldname = "department", $selected = null, $any = false) {
	$return = "<select id='{$fieldname}' name='{$fieldname}'>";
	$return .= ($any) ? "<option value='any'>Any Department</option>" : "";
	
	// Query the database for the departments
	$query = "SELECT number, title FROM departments ORDER BY number";
	$result = mysql_query($query);
	
	// Output the departments as options
	while($row = mysql_fetch_assoc($result)) {
		$deptNum = $row['number'];
		$deptTitle = $row['title'];
		$return .= "<option value='{$deptNum}'" . (($selected == $deptNum) ? " selected='selected'" : "") . ">{$deptNum} {$deptTitle}</option>";
	}

	// Close it up and return it
	$return .= "</select>";
	return $return;
}

/**
 * Recursively sanitizes all the information passed to it
 * @param	mixed	$item	The item to sanitize, can be an array
 * @return	mixed	The item after it has been sanitized
 */
function sanitize($item) {
	if(is_array($item)) {
		// If it's an array, then recursively call it on the item
		foreach($item as $key => $value) {
			$item[$key] = sanitize($value);
		}
		return $item;
	} else {
		// Base case, return the sanitized item
		$item = htmlentities($item, ENT_QUOTES);
		return mysql_real_escape_string($item);
	}
}
