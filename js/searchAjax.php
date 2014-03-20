<?php
////////////////////////////////////////////////////////////////////////////
// SEARCH AJAX CALLS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/searchAjax.php
// @descrip	Provides standalone JSON object retreival via ajax for the course
//			roulette page
////////////////////////////////////////////////////////////////////////////

// FUNCTIONS ///////////////////////////////////////////////////////////////

/**
 * Halts execution if the provided variable is not numeric and it isn't null.
 * This also dumps a nice jSON encoded error message
 * @param	mixed	$var	The variable we're asserting is numeric
 * @param	string	$name	The name of the variable to include in the error
 *							message. Also the name of the argument.
 */
function assertNumeric($var, $name) {
	if(!is_numeric($var) && !is_null($var)) {
		die(json_encode(array("error" => "argument", "msg" => "You must provide a valid {$name}!", "arg" => $name)));
	}
}

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once "../inc/config.php";
require_once "../inc/databaseConn.php";
require_once "../inc/timeFunctions.php";
require_once "../inc/ajaxError.php";

// HEADERS /////////////////////////////////////////////////////////////////
header("Content-type: application/json");

// POST PROCESSING /////////////////////////////////////////////////////////
$_POST = sanitize($_POST);

// MAIN EXECUTION //////////////////////////////////////////////////////////

// We're providing JSON
header('Content-type: application/json');

switch($_POST['action']) {
	case "find":
		// Check that the required fields are provided
        $term = $_POST['term'];
		if(empty($term)) {
			// We cannot continue!
			echo json_encode(array("error" => "argument", "msg" => "You must provide a term", "arg" => "term"));
		}

		// Term, school, department, credits, times-any, times, professor
		// School and department will be empty strings if any OR not selected
		$school     = (!empty($_POST['college']) && $_POST['college'] != 'any') ? $_POST['college'] : null;
		$credits    = (!empty($_POST['credits'])) ? $_POST['credits'] : null;
		$professor  = (!empty($_POST['professor'])) ? $_POST['professor'] : null;
		$title  = (!empty($_POST['title'])) ? $_POST['title'] : null;
		$description  = (!empty($_POST['description'])) ? explode(',', $_POST['description']) : array();
		$level      = (!empty($_POST['level']) && $_POST['level'] != 'any') ? $_POST['level'] : null;
		if(!empty($_POST['department']) && $_POST['department'] != 'any') {
			$department = $_POST['department'];
			$school = null;		// We won't search for a school if department is assigned.
		} else {
			$department = null;
		}
		
		if(count($description) > 0) {
			$keyword_SQL = 'AND (';
			foreach ($description as $keyword) {
				$keyword = trim($keyword);
				$keyword_SQL .= "c.description LIKE '%{$keyword}%' OR ";
			}
			$keyword_SQL = substr($keyword_SQL, 0, -4) . ")";
		} else {
			$keyword_SQL = null;
		}
		
		// Validate the numerical arguments we got
		assertNumeric($term, "term");
		assertNumeric($school, "school");
		assertNumeric($credits, "number of credits");

		// Times (Search for any if any is selected, search for the specified times, OR don't specify any time data)
		if(!empty($_POST['timesAny']) && $_POST['timesAny'] == 'any') {
			$times = null;
			$timesAny = true;		
		} elseif(empty($_POST['timesAny']) && !empty($_POST['times'])) {
			$times = (is_array($_POST['times'])) ? $_POST['times'] : array($_POST['times']);
			$timesAny = false;
		} else {
			$times = null;
			$timesAny = true;
		}
		// Days (same process as the time)
		if(!empty($_POST['daysAny']) && $_POST['daysAny'] == 'any') {
			$days = null;
			$daysAny = true;		
		} elseif(empty($_POST['daysAny']) && !empty($_POST['days'])) {
			$days = (is_array($_POST['days'])) ? $_POST['days'] : array($_POST['days']);
			$daysAny = false;
		} else {
			$days = null;
			$daysAny = true;
		}

		// Build the query
		$query = "SELECT s.id";
		$query .= " FROM courses AS c ";
        $query .= " JOIN sections AS s ON s.course = c.id";
        $query .= " JOIN departments AS d ON d.id = c.department";
		$query .= " WHERE quarter = '{$term}'";
		$query .= " AND s.status != 'X'";
		//$query .= ($school)     ? " AND d.number > '{$school}' AND d.number < '" . ($school+100) . "'" : "";
        $query .= ($school)     ? " AND d.school = '{$school}'" : "";
		$query .= ($department) ? " AND c.department = '{$department}'" : "";
		$query .= ($credits)    ? " AND c.credits = '{$credits}'" : "";
		$query .= ($professor)  ? " AND s.instructor LIKE '%{$professor}%'" : "";
		$query .= ($title)  ? " AND c.title LIKE '%{$title}%'" : "";
		$query .= ($keyword_SQL)  ? $keyword_SQL : "";
		if($level) { // Process the course level
			if($level == 'beg') { $query .= " AND c.course < 300"; }
			if($level == 'int') { $query .= " AND c.course >= 300 AND c.course < 600"; }
			if($level == 'grad') { $query .= " AND c.course >= 600"; }
		}
		$timeConstraints = array();
		if($times) { // Process the time constraints
			$timequery = array();
			if(in_array("morn", $times)) { $timequery[] = "(start >= 480 AND start < 720)"; }
			if(in_array("aftn", $times)) { $timequery[] = "(start >= 720 AND start < 1020)"; }
			if(in_array("even", $times)) { $timequery[] = "(start > 1020)"; }
			$timeConstraints[] = "(" . implode(" OR ", $timequery) . ")"; // Make it a single string (condition OR condition ...)
		}
		if($days) { // Process the day constraints
			$dayquery = array();
			foreach($days as $day) {
				$dayquery[] = "day = " . translateDay($day);
			}
			$timeConstraints[] = "(" . implode(" OR ", $dayquery) . ")"; // Do the same as we did with the times
		}
		if(count($timeConstraints)) {
			// Now cram the two together into one concise subquery
			$query .= " AND s.id IN (SELECT section FROM times WHERE " . implode(" AND ", $timeConstraints) . ")";
		}

		// Run it!
		$result = mysql_query($query);
		if(!$result) {
			echo json_encode(array("error" => "mysql", "msg" => "An error occurred while searching the database."));
			break;
		}
		if(mysql_num_rows($result) == 0) {
			echo json_encode(array("error" => "result", "msg" => "No courses matched your criteria"));
			break;
		} 

		// Now we build an array of the results
		$courses = array();
		while($row = mysql_fetch_assoc($result)) {
			$courses[] = $row['id'];
		}
		// @todo: store this in session to avoid lengthy and costly queries

		// Loop through all results and fill them out
        $matchingCourses = array();
        foreach($courses as $sectionId) {
            
            // Look up the course
            $course = getCourseBySectionId($sectionId);

            // Do we need to exclude it because it's online?
            if($course['online'] == true && (!isset($_POST['online']) || $_POST['online'] != true)) {
                // Yes, it should be excluded
                continue;
            }

            // Determine if its on campus
            $offCampus = false;
            if(!empty($course['times'])) {
                foreach($course['times'] as $time) {
                    if($time['off_campus']) {
                        $offCampus = true;
                        break;
                    }
                }
            }

            // Do we need to exclude this course?
            if((isset($_POST['offCampus']) && $_POST['offCampus'] == 'true') || !$offCampus) {
                // No need to exclude it -- match found
                $matchingCourses[] = $course;
            }
        }

        // Courses will be empty if there are no results
        if(count($matchingCourses) == 0) {
            echo json_encode(array("error" => "result", "msg" => "No courses matched your criteria"));
        } else {
        	echo json_encode($matchingCourses);
        }
		break;

	default:
		echo json_encode(array("error" => "argument", "msg" => "Invalid or no action provided", "arg" => "action"));
		break;
}
