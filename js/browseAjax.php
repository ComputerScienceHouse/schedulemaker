<?php
////////////////////////////////////////////////////////////////////////////
// BROWSE AJAX CALLS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/browseAjax.php
// @descrip	Provides standalone JSON object retreival for the course 
//			browsing page
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once "../inc/config.php";
require_once "../inc/databaseConn.php";
require_once "../inc/timeFunctions.php";
require_once "../inc/ajaxError.php";

// POST PROCESSING /////////////////////////////////////////////////////////
$_POST = sanitize($_POST);

// MAIN EXECUTION //////////////////////////////////////////////////////////
if(empty($_POST['action'])) {
	die(json_encode(array("error" => "argument", "msg" => "You must provide an action")));
}

// Switch on the action
switch($_POST['action']) {
	case "getCourses":
		// Query for the courses in this department

		// Verify that we have department to get courses for and a quarter
		if(empty($_POST['department']) || !is_numeric($_POST['department'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a valid department")));
		} elseif(empty($_POST['quarter']) || !is_numeric($_POST['quarter'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a valid quarter")));
		}

		// Do the query
		$query = "SELECT title, department, course, description, id FROM courses ";
		$query .= "WHERE department = '{$_POST['department']}' AND quarter = '{$_POST['quarter']}' ORDER BY course";
		$result = mysql_query($query);
		if(!$result) {
			die(json_encode(array("error" => "mysql", "msg" => mysql_error())));
		}

		// Collect the courses and turn it into a json
		$courses = array();
		while($course = mysql_fetch_assoc($result)) {
			$courses[] = $course;
		}

		echo json_encode(array("courses" => $courses));

		break;

	case "getDepartments":
		// Query for the departments of the school
		
		// Verify that we have a school to get departments for
		if(empty($_POST['school']) || !is_numeric($_POST['school'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a school")));
		}

		// Verify that we have a quarter to make sure there are
		// courses in the department.
		if(empty($_POST['quarter']) || !is_numeric($_POST['quarter'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a quarter")));
		}

		// Do the query
		$query = "SELECT title, id, code FROM departments WHERE school = '{$_POST['school']}'";
		$query .= " AND (SELECT COUNT(*) FROM courses WHERE department=departments.id AND quarter='{$_POST['quarter']}') > 1 ORDER BY id";
		$result = mysql_query($query);
		if(!$result) {
			die(json_encode(array("error" => "mysql", "msg" => mysql_error())));
		}

		// Collect the departments and turn it into a json
		$departments = array();
		while($department = mysql_fetch_assoc($result)) {
			$departments[] = $department;
		}

		echo json_encode(array("departments" => $departments));

		break;

	case "getSections":
		// Query for the sections and times of a given course
		
		// Verify that we have a course to get sections for
		if(empty($_POST['course']) || !is_numeric($_POST['course'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a course")));
		}

		// Do the query
		$query = "SELECT c.title AS coursetitle, c.course, c.department, s.section, ";
		$query .= "s.instructor, s.id, s.type, s.maxenroll, s.curenroll, s.title AS sectiontitle ";
		$query .= "FROM sections AS s, courses AS c ";
		$query .= "WHERE s.course = c.id AND s.course = '{$_POST['course']}' AND s.status != 'X' ORDER BY c.course, s.section";
		$sectionResult = mysql_query($query);
		if(!$sectionResult) {
			die(json_encode(array("error" => "mysql", "msg" => mysql_error())));
		}
		
		// Collect the sections and their times, modify the section inline
		$sections = array();
		while($section = mysql_fetch_assoc($sectionResult)) {
			$section['times'] = array();

			// Set the course title depending on its section title
			if($section['sectiontitle'] != NULL) {
				$section['title'] = $section['sectiontitle'];
			} else {
				$section['title'] = $section['coursetitle'];
			}
			unset($section['sectiontitle']);
			unset($section['coursetitle']);

			// If it's online, don't bother looking up the times
			if($section['type'] == "O") {
				$section['online'] = true;
				$sections[] = $section;
				continue;
			}

			$query = "SELECT day, start, end, b.code, b.number, room ";
			$query .= "FROM times AS t JOIN buildings AS b ON b.number=t.building ";
			$query .= "WHERE t.section = '{$section['id']}' ORDER BY day, start";
			$timeResult = mysql_query($query);
			if(!$timeResult) {
				die(json_encode(array("error" => "mysql", "msg" => mysql_error())));
			}

			while($time = mysql_fetch_assoc($timeResult)) {
				$time['start'] = translateTime($time['start']);
				$time['end']   = translateTime($time['end']);
				$time['day']   = translateDay($time['day']);
				$time['building'] = array("code"=>$time['code'], "number"=>$time['number']);
				$section['times'][] = $time;
			}	

			$sections[] = $section;
		}

		// Spit out the json
		echo json_encode(array("sections" => $sections));
		break;
}
