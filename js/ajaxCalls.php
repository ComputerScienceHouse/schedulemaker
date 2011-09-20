<?php
////////////////////////////////////////////////////////////////////////////
// AJAX CALLS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/ajaxCalls.php
// @descrip	Provides standalone JSON object retreival via ajax
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES
require_once "../inc/config.php";
require_once "../inc/databaseConn.php";
require_once "../inc/timeFunctions.php";

////////////////////////////////////////////////////////////////////////////
// ERROR HANDLING

function ajaxErrorHandler($errno, $errstr) { 
	echo(json_encode(array("error" => "php", "msg" => $errstr, "num" => $errno)));
	die();
}
set_error_handler('ajaxErrorHandler');

/**
 * Escapes all the data in the argument. HO SHITZ IT'S RECURSIVEZ YO!
 * @param	mixed	$data	The data to escape
 * @return	mixed	The escaped data
 */
function escapeData($data) {
	if(is_array($data)) {
		foreach($data as $k => $d) {
			$data[$k] = escapeData($d);
		}
		return $data;
	} else {
		return mysql_real_escape_string(trim($data));
	}
}

// We're providing JSON
header('Content-type: application/json');

// Escape the post data
$_POST = escapeData($_POST);

// What action are we performing today?
if(empty($_POST['action'])) {
	$_POST['action'] = null;
}

switch($_POST['action']) {
	////////////////////////////////////////////////////////////////////////
	// COURSE ROULETTE

	case "rouletteSpin":
		// Check that the required fields are provided
		if(empty($_POST['quarter'])) {
			// We cannot continue!
			echo json_encode(array("error" => "argument", "msg" => "You must provide a quarter for random course selection", "arg" => "quarter"));
		}

		// Quarter, school, department, credits, times-any, times, professor
		// School and department will be empty strings if any OR not selected
		$quarter    = $_POST['quarter'];
		$school     = (!empty($_POST['school']) && $_POST['school'] != 'any') ? $_POST['school'] * 100 : null;
		$credits    = (!empty($_POST['credits'])) ? $_POST['credits'] : null;
		$professor  = (!empty($_POST['professor'])) ? $_POST['professor'] : null;
		$level      = (!empty($_POST['level']) && $_POST['level'] != 'any') ? $_POST['level'] : null;
		if(!empty($_POST['department']) && $_POST['department'] != 'any') {
			$department = $_POST['department'];
			$school = null;		// We won't search for a school if department is assigned.
		} else {
			$department = null;
		}
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
		$query = "SELECT c.department, c.course, s.section, c.title, s.instructor, s.id";
		$query .= " FROM courses AS c, sections AS s";
		$query .= " WHERE quarter = {$quarter}";
		$query .= ($school)     ? " AND c.department > {$school} AND c.department < " . ($school+100) : "";
		$query .= ($department) ? " AND c.department = {$department}" : "";
		$query .= ($credits)    ? " AND c.credits = {$credits}" : "";
		$query .= ($professor)  ? " AND s.instructor LIKE '%{$professor}%'" : "";
		if($level) { // Process the course level
			if($level == 'beg') { $query .= " AND c.course < 300"; }
			if($level == 'int') { $query .= " AND c.course >= 300 AND c.course < 600"; }
			if($level == 'grad') { $query .= " AND c.course >= 600"; }
		}
		if($times || $days) { // Process the time constraints
			$timequery = array();
			if(in_array("morn", $times)) { $timequery[] = "(start >= 800 AND start < 1200)"; }
			if(in_array("aftn", $times)) { $timequery[] = "(start >= 1200 AND start < 1700)"; }
			if(in_array("even", $times)) { $timequery[] = "(start > 1700)"; }
			$timequery = "(" . implode(" OR ", $timequery) . ")"; // Make it a single string (condition OR condition ...)

			$dayquery = array();
			if($days) {
				foreach($days as $day) {
					$dayquery[] = "day = " . translateDay($day);
				}
			}
			$dayquery = "(" . implode(" OR ", $dayquery) . ")"; // Do the same as we did with the times
					
			// Now cram the two together into one concise subquery
			$query .= " AND s.id IN (SELECT section FROM times WHERE " . implode(" AND ", array($timequery, $dayquery)) . ")";
		}
		$query .= " AND s.course = c.id";

		// Run it!
		$result = mysql_query($query);
		if(!$result) {
			echo json_encode(array("error" => "mysql", "msg" => mysql_error()));
			break;
		}
		if(mysql_num_rows($result) == 0) {
			echo json_encode(array("error" => "result", "msg" => "No courses matched your criteria"));
			break;
		} 

		// Now we build an array of the results
		$courses = array();
		while($row = mysql_fetch_assoc($result)) {
			$courses[] = $row;
		}
		// @todo: store this in session to avoid lengthy and costly queries

		// Now pick a course at random, grab it's times,
		$courseNum = rand(0, count($courses) - 1);
		
		$query = "SELECT day, start, end, building, room FROM times WHERE section={$courses[$courseNum]['id']}";
		$result = mysql_query($query);
		if(!$result) {
			echo json_encode(array("error" => "mysql", "msg" => mysql_error()));
			break;
		}
		$courses[$courseNum]['times'] = array();
		while($row = mysql_fetch_assoc($result)) {
			$session = array(
				'day' => translateDay($row['day']), 
				'start' => translateTime($row['start']), 
				'end' => translateTime($row['end']),
				'bldg' => $row['building'],
				'room' => $row['room']
			);
			$courses[$courseNum]['times'][] = $session;
		}

		echo json_encode($courses[$courseNum]);
		break;

	////////////////////////////////////////////////////////////////////////
	// GET COURSE OPTIONS
	case "getCourseOpts":
		// Verify that we got a course (or partial course) and a quarter
		if(empty($_POST['course'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide at least a department number", "arg" => "course")));
		}
		if(empty($_POST['quarter'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a quarter", "arg" => "course")));
		}

		// If it's not a number, we'll remove the dashes
		if(!is_numeric($_POST['course'])) {
			$course = str_replace("-", "", $_POST['course']);
		} else {
			$course = $_POST['course'];
		}

		// If it's still not a number, then we can't process it
		if(preg_match("/[a-zA-Z]+/", $course)) {
			die(json_encode(array("error" => "argument", "msg" => "Your course must contain numbers", "arg" => "course")));
		}		
		if(!is_numeric($course)) {
			die(json_encode(array("error" => "argument", "msg" => "Your course must be in the format XXXX-XXX-XX", "arg" => "course")));
		}

		// Now we'll split the course into the various components
		$department = substr($course, 0, 4);
		if(strlen($department) != 4) {
			// We didn't get an entire department. We won't proceed
			die(json_encode(array("error" => "argument", "msg" => "You must provide at least a complete department number", "arg" => "course")));
		}

		$coursenum = substr($course, 4, 3);
		if(!$coursenum || strlen($coursenum) != 3) {
			// We got a partial course. That's ok.
			$partialCourse = true;
		} else {
			$partialCourse = false;
		}

		$section = substr($course, 7);
		if(!$section || strlen($coursenum) != 2) {
			// We got a partial section number. That's ok. Dumb, but OK (in the case of condition 2)
			$partialSection = true;
		} else {
			$partialSection = false;
		}

		// Build a query and run it
		$query = "SELECT c.department, c.course, s.section FROM courses AS c, sections AS s WHERE";
		$query .= " s.course = c.id";
		$query .= " AND c.quarter = {$_POST['quarter']}";
		$query .= " AND c.department = {$department}";
		if($partialCourse) {
			$query .= " AND c.course LIKE '{$coursenum}%'";
		} else {
			$query .= " AND c.course = {$coursenum}";
		}
		if($partialSection) {
			$query .= " AND s.section LIKE '{$section}%'";
		} else {
			$query .= " AND s.section = {$section}";
		}		
		$query .= " ORDER BY c.course, s.section";
		
		$result = mysql_query($query);
		if(!$result) {
			die(json_encode(array("error" => "mysql", "msg" => "There was a database error!", "arg" => "course", 'guru' => $query)));
		}
		if(mysql_num_rows($result) == 0) {
			die(json_encode(array("error" => "result", "msg" => "No courses match")));
		}

		// Now we can process it into a list of courses. It's pretty simple from here
		$return = array();
		while($row = mysql_fetch_assoc($result)) {
			$return[] = implode('-', $row);
		}
		
		echo json_encode($return);

		break;

	////////////////////////////////////////////////////////////////////////
	// DEFAULT ACTION	
	default:
		echo json_encode(array("error" => "argument", "msg" => "Invalid or no action provided", "arg" => "action"));
		break;
}



		
