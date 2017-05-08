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


// There is no better place to put this, as all pages require this file.
//
// Never cache or store any api call/page load as reopening pages
// that are served differently by the api (e.g. /schedule/:hexcode)
// depending if the "Accept" header has application/json 
// in it or not. The browser does not factor this in into 
// loading the page from cache, so the browser will load the json response
// or html response for the same url even though there are very different.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

// Make a connection to the database
global $DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASS, $DATABASE_DB;

$dbConn = new mysqli($DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASS, $DATABASE_DB);

// Error check
if(!$dbConn) {
	die("Could not connect to database: " . $dbConn->connect_error);
}

////////////////////////////////////////////////////////////////////////////
// FUNCTIONS

/**
 * Checks if a section is a special section (lab/studio/etc)
 * Now also ignores "H" for honors classes, which are separate
 * @param $courseInfo
 * @return int
 */
function isSpecialSection($courseInfo) {
	return preg_match('/[A-GI-Z]\d{0,2}$/', $courseInfo['courseNum']) === 1;
}

/**
 * Retrieves the meeting information for a section
 * @param	$sectionData	array	Information about a section MUST HAVE:
 *									title, instructor, curenroll, maxenroll,
 *									department, course, section, section id,
 *									type.
 * @return	array	A course array with all the information about the course
 */
function getMeetingInfo($sectionData) {
	global $dbConn;

	// Store the course information
    $course = array(
        "title"      => $sectionData['title'],
        "instructor" => $sectionData['instructor'],
        "curenroll"  => $sectionData['curenroll'],
        "maxenroll"  => $sectionData['maxenroll'],
        "courseNum"  => "{$sectionData['department']}-{$sectionData['course']}-{$sectionData['section']}",
        "courseParentNum" => "{$sectionData['department']}-{$sectionData['course']}",
        "courseId"   => $sectionData['courseId'],
        "id"         => $sectionData['id'],
        "online"     => $sectionData['type'] == "O",
		"credits"	 => $sectionData['credits']
        );

	if(isset($sectionData['description'])) {
		$course['description'] = $sectionData['description'];
	}

	// Make sure special sections don't get double-counted for their credits
	if(isSpecialSection($course)) {
		$course['credits'] = "0"; // string for consistency's sake
	}

    // Now we query for the times of the section
    $query = "SELECT b.code, b.number, b.off_campus, t.room, t.day, t.start, t.end ";
    $query .= "FROM times AS t JOIN buildings AS b ON b.number=t.building ";
    $query .= "WHERE section = {$sectionData['id']}";
    $result = $dbConn->query($query);
    if(!$result) {
        throw new Exception("mysql:" . $dbConn->error);
    }
    while($row = $result->fetch_assoc()) {
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
 * @param    $id              int	The if of the section
 * @param    $withDescription bool	If to include the description
 * @throws Exception
 * @return    array    The information about the section
 */
function getCourseBySectionId($id, $withDescription = false) {
	global $dbConn;

    // Sanity check for the section id
    if($id == "" || !is_numeric($id)) {
        trigger_error("A valid section id was not provided");
    }

	// Setup the SQL if we want descriptions
	$descriptionSQL = ($withDescription)? ', c.description': '';

	// Build the query to get section info
	$query = "SELECT s.id,
                (CASE WHEN (s.title != '') THEN s.title ELSE c.title END) AS title,
                c.id AS courseId,
                s.instructor, s.curenroll, s.maxenroll, s.type, c.quarter, c.credits, c.course{$descriptionSQL}, s.section, d.number, d.code
                FROM sections AS s
                  JOIN courses AS c ON s.course = c.id
                  JOIN departments AS d ON d.id = c.department
                WHERE s.id = '{$id}'";

	// Actually run the query
	$result = $dbConn->query($query);
	// @TODO: Error handling
	$row = $result->fetch_assoc();
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
	global $dbConn;

	// Build the query
    if($term > 20130) {
        $query = "SELECT s.id,
                    (CASE WHEN (s.title != '') THEN s.title ELSE c.title END) AS title,
                    s.instructor, s.curenroll, s.maxenroll, s.type, d.code AS department, c.course, c.credits, s.section
                  FROM sections AS s
                    JOIN courses AS c ON c.id=s.course
                    JOIN departments AS d ON d.id=c.department
                  WHERE c.quarter = '{$term}'
                    AND d.code = '{$dept}'
                    AND c.course = '{$courseNum}' AND s.section = '{$sectNum}'";
    } else {
        $query = "SELECT s.id,
                    (CASE WHEN (s.title != '') THEN s.title ELSE c.title END) AS title,
                    s.instructor, s.curenroll, s.maxenroll, s.type, d.number AS department, c.course, c.credits, s.section
                  FROM sections AS s
                    JOIN courses AS c ON c.id=s.course
                    JOIN departments AS d ON d.id=c.department
                  WHERE c.quarter = '{$term}'
                    AND d.number = '{$dept}'
                    AND c.course = '{$courseNum}' AND s.section = '{$sectNum}'";
    }

	// Execute the query and error check
	$result = $dbConn->query($query);
	if(!$result) {
		throw new Exception("mysql:" . $dbConn->error);
	} elseif($result->num_rows > 1) {
		throw new Exception("ambiguous:{$term}-{$dept}-{$courseNum}-{$sectNum}");
	} elseif($result->num_rows == 0) {
		throw new Exception("objnotfound:{$term}-{$dept}-{$courseNum}-{$sectNum}");
	}

	return getMeetingInfo($result->fetch_assoc());
}

/**
 * Does a query for all the terms in the database and parses them like 
 * term:'Spring ####' for display val.
 * @return the array of terms
 */
function getTerms() {
	global $dbConn;

	$terms = array();

	// Query the database for the quarters
	$query = "SELECT quarter FROM quarters ORDER BY quarter DESC";
	$result = $dbConn->query($query);

	// Output the quarters as options
	$curYear = 0;
	$termGroupName = "";
	
	while($row = $result->fetch_assoc()) {
		$term = $row['quarter'];

		// Parse it into a year-quarter thingy
		$year = (int) substr(strval($term), 0, 4);
		$nextYear = $year + 1;
		$useYear = $year;
		$termNum = substr(strval($term), -1);
		if($year >= 2013) {
			switch($termNum) {
				case 1: $termName = "Fall"; break;
				case 3: $termName = "Winter Intersession"; $useYear = $nextYear; break;
				case 5: $termName = "Spring"; $useYear = $nextYear; break;
				case 8: $termName = "Summer"; $useYear = $nextYear; break;
				default: $termName = "Unknown";
			}
		} else {
			switch($termNum) {
				case 1: $termName = "Fall"; break;
				case 2: $termName = "Winter"; break;
				case 3: $termName = "Spring"; $useYear = $nextYear; break;
				case 4: $termName = "Summer"; $useYear = $nextYear; break;
				default: $termName = "Unknown";
			}
		}
		
		if($curYear != $year) {
			$curYear = $year;
			$termGroupName = "{$year} - {$nextYear}";
		}
		
		// Now add it to the array
		$terms[] = array(
			"value" => (int) $term,
			"name" => "{$termName} {$useYear}",
			"group" => $termGroupName
		);
	}
	return $terms;
}


/**
 * Recursively sanitizes all the information passed to it
 * @param	mixed	$item	The item to sanitize, can be an array
 * @return	mixed	The item after it has been sanitized
 */
function sanitize($item) {
	global $dbConn;

	if(is_array($item)) {
		// If it's an array, then recursively call it on the item
		foreach($item as $key => $value) {
			$item[$key] = sanitize($value);
		}
		return $item;
	} else {
		// Base case, return the sanitized item
		$item = htmlentities($item, ENT_QUOTES);
		return $dbConn->real_escape_string($item);
	}
}
