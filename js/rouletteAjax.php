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

// POST PROCESSING /////////////////////////////////////////////////////////
$_POST = sanitize($_POST);

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
		$school     = (!empty($_POST['college']) && $_POST['college'] != 'any') ? $_POST['college'] * 100 : null;
		$credits    = (!empty($_POST['credits'])) ? $_POST['credits'] : null;
		$professor  = (!empty($_POST['professor'])) ? $_POST['professor'] : null;
		$level      = (!empty($_POST['level']) && $_POST['level'] != 'any') ? $_POST['level'] : null;
		if(!empty($_POST['department']) && $_POST['department'] != 'any') {
			$department = $_POST['department'];
			$school = null;		// We won't search for a school if department is assigned.
		} else {
			$department = null;
		}

		// Validate the numerical arguments we got
		assertNumeric($quarter, "quarter");
		assertNumeric($school, "school");
		assertNumeric($credits, "number of credits");
		assertNumeric($department, "department");

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
		$query .= " FROM courses AS c JOIN sections AS s ON s.course = c.id";
		$query .= " WHERE quarter = '{$quarter}'";
		$query .= " AND s.status != 'X'";
		$query .= ($school)     ? " AND c.department > '{$school}' AND c.department < '" . ($school+100) . "'" : "";
		$query .= ($department) ? " AND c.department = '{$department}'" : "";
		$query .= ($credits)    ? " AND c.credits = '{$credits}'" : "";
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
		
		$query = "SELECT day, start, end, b.code, b.number, room ";
		$query .= "FROM times AS t JOIN buildings AS b ON b.number = t.building ";
		$query .= "WHERE section='{$courses[$courseNum]['id']}' ORDER BY day, start";
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
				'bldg' => array("code"=>$row['code'], "number"=>$row['number']),
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
