<?php
////////////////////////////////////////////////////////////////////////////
// ROULETTE AJAX CALLS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/rouletteAjax.php
// @descrip	Provides standalone JSON object retreival via ajax for the course
//			roulette page
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once "../inc/config.php";
require_once "../inc/databaseConn.php";
require_once "../inc/timeFunctions.php";

// MAIN EXECUTION //////////////////////////////////////////////////////////

// We're providing JSON
header('Content-type: application/json');

switch($_POST['action']) {
	case "rouletteSpin":
		// Check that the required fields are provided
		if(empty($_POST['quarter'])) {
			// We cannot continue!
			echo json_encode(array(
					"error" => "argument", 
					"msg" => "You must provide a quarter for random course selection", 
					"arg" => "quarter"
				));
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
		$timeConstraints = array();
		if($times) { // Process the time constraints
			$timequery = array();
			if(in_array("morn", $times)) { $timequery[] = "(start >= 480 AND start < 720)"; }
			if(in_array("aftn", $times)) { $timequery[] = "(start >= 720 AND start < 1020)"; }
			if(in_array("even", $times)) { $timequery[] = "(start > 1020)"; }
			$timeConstriants[] = "(" . implode(" OR ", $timequery) . ")"; // Make it a single string (condition OR condition ...)
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
		$query .= " AND s.course = c.id";

		// Run it!
		$result = mysql_query($query);
		if(!$result) {
			echo json_encode(array("error" => "mysql", "msg" => mysql_error(), "guru" => $query));
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

	default:
		echo json_encode(array("error" => "argument", "msg" => "Invalid or no action provided", "arg" => "action"));
		break;
}
