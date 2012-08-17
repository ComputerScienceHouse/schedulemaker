<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE AJAX CALLS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/scheduleAjax.php
// @descrip	Provides standalone JSON object retreival for schedule designing
// 			form　and display
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES
require_once "../inc/config.php";
require_once "../inc/databaseConn.php";
require_once "../inc/timeFunctions.php";
require_once "../inc/ajaxError.php";

////////////////////////////////////////////////////////////////////////////
// GLOBALS
$ERRORS = array();		// Storage for course conflicts & recoverable errors

////////////////////////////////////////////////////////////////////////////
// ERROR HANDLING

function ajaxErrorHandler($errno, $errstr, $errfile, $errline) { 
	echo(json_encode(array("error" => "php", "msg" => $errstr, "num" => $errno, "file" => $errfile, "linenum" => $errline)));
	die();
}
set_error_handler('ajaxErrorHandler');

////////////////////////////////////////////////////////////////////////////
// FUNCTIONS
/**
 * Generates a list of valid courses using a recursive tree-traversing
 * algorithm. This also prunes any branches of the tree that are invalid.
 * @param	array	$courseSet		The list of courses set up as an array of
 *									course slots, which are arrays of course
 *									information.
 * @param	array	$nonCourses		The list of nonCourse items that are fixed
 *									in the schedule
 * @param	array	$noCourses		The list of times when the user does not
 *									want courses
 * @param	array	$chain			A partially built schedule. Basically the
 *									parent list of the tree traversal
 * @param	array	$results		The list of complete and valid schedules
 * @param	int		$level			What level of the tree we're currently at
 * @return	array	Returns an array of complete and valid schedules
 */
function generateSchedules($courses, $nonCourses, $noCourses, $chain=array(), $results=array(), $level=0) {
	// Pull in the noncourses and nocourses
	global $NONCOURSES, $NOCOURSES;

	// Iterate over the course choices in the level
	$oldChain = $chain; 		// Use this to preserve the chain to eliminate multiple sections in same schedule
	foreach($courses[$level] as $childCourse) {
		if(!overlap($chain, $childCourse) && !overlapNonCourse($nonCourses, $childCourse) && !overlapNoCourse($noCourses, $childCourse)) {
			// It doesn't overlap, so add it to the chain
			$chain[] = $childCourse;
			
			// If there are further children, recurse through them.
			if($level < count($courses)-1) {
				$results = generateSchedules($courses, $nonCourses, $noCourses, $chain, $results, $level+1);
			} else {
				// The schedule is complete and valid!
				$results[] = $chain;
			}
		}
		// Replace the chain
		$chain = $oldChain;
	}
	return $results;
}

function overlapBase($item, $course) {
	// If there isn't even times defined for this course, or item, then
	// return false
	if(empty($item['times']) || empty($course['times'])) {
		return false;
	}

	// Does the item overlap with the course?
	foreach($item['times'] as $itemTime) {
		foreach($course['times'] as $courseTime) {
			if(
				(
					($itemTime['start'] <= $courseTime['start'] && $courseTime['start'] < $itemTime['end']) || 	// itemStart <= courseStart < itemEnd
					($itemTime['start'] < $courseTime['end'] && $courseTime['end'] <= $itemTime['end'])			// OR itemStart < courseEnd <= itemEnd
				) && 
				$courseTime['day'] == $itemTime['day']															// AND the days are the same
			  ) {
				// They overlap.
				return true;
			}
		}
	}
	// The must not overlap
	return false;
}

/**
 * Determines if a course overlaps in a given partial schedule
 * @param	array	$schedule		A partial schedule (generally the chain
 *									from generateSchedules)
 * @param	array	$course			The course that could be added to the
 *									schedule. It will be validated
 * @return	bool	True if the course overlaps, false otherwise
 */
function overlap($schedule, $course) {
	// Pull in the error global
	global $ERRORS;

	// If there are no courses in the schedule, there is no overlap, duh!
	if(count($schedule) == 0) {
		return false;
	}

	// Now we need to do some comparisons. Do any course time slots overlap?
	foreach($schedule as $c) {
		if(overlapBase($c, $course)) {
			// It overlaps.
			$ERRORS[] = array("error" => "conflict", "msg" => "A schedule could not be generated because {$c['courseNum']} conflicts with {$course['courseNum']}");
			return true;
		}
	}

	// Aparrently it doesn't overlap!
	return false;
}

function overlapNonCourse($nonCourses, $course) {
	// Pull in the error global
	global $ERRORS;

	// If there's no nonCourses, there is no overlap, duh!
	if(count($nonCourses) == 0) {
		return false;
	}

	// Now we need to do comparisons. Do any of the nonCourse items overlap with this course?
	foreach($nonCourses as $c) {
		if(overlapBase($c, $course)) {
			// It overlaps.
			$ERRORS[] = array("error" => "conflict", "msg" => "A schedule could not be generated because {$course['courseNum']} conflicts with '{$c['title']}'");
			return true;
		}
	}

	// Aparrently, it doesn't overlap!
	return false;
}

function overlapNoCourse($noCourses, $course) {
	// Pull in the error global
	global $ERRORS;
	
	// If there's no noCourses, there's no overlap.
	if(count($noCourses) == 0) {
		return false;
	}

	// Compare the noCourse times with the time of the course
	foreach($noCourses as $c) {
		if(overlapBase($c, $course)) {
			// It overlaps.
			$ERRORS[] = array("error" => "conflict", "msg" => "A schedule could not be generated because {$course['courseNum']} occurs during a time you don't want classes");
			return true;
		}
	}

	// Aparrently, it doesn't overlap!
	return false;
}


////////////////////////////////////////////////////////////////////////////
// MAIN EXECUTION

// We're providing JSON
header('Content-type: application/json');

// Escape the post data
$_POST = sanitize($_POST);

// What action are we performing today?
if(empty($_POST['action'])) {
	$_POST['action'] = null;
}

switch($_POST['action']) {
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

		// If it has dashes, then strip them out
		$course = str_replace("-", "", $_POST['course']);

		// If it doesn't match the regexp for a course, then we cannot process it
		if(preg_match("/(\d){9}L\d/", $course)) {
			die(json_encode(array("error" => "argument", "msg" => "Your course must be in the format XXXX-XXX-XXLX", "arg" => "course")));
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
		if(!$section || strlen($coursenum) != 4) {
			// We got a partial section number. That's ok.
			$partialSection = true;
		} else {
			$partialSection = false;
		}

		// Build a query and run it
		$query = "SELECT c.department, c.course, s.section FROM courses AS c, sections AS s WHERE";
		$query .= " s.course = c.id";
		$query .= " AND c.quarter = '{$_POST['quarter']}'";
		$query .= " AND c.department = '{$department}'";
		$query .= " AND s.status != 'X'";
		if($partialCourse) {
			$query .= " AND c.course LIKE '{$coursenum}%'";
		} else {
			$query .= " AND c.course = '{$coursenum}'";
		}
		if($partialSection) {
			$query .= " AND s.section LIKE '{$section}%'";
		} else {
			$query .= " AND s.section = '{$section}'";
		}
		if($_POST['ignoreFull'] == 'true') {
			$query .= " AND s.curEnroll < s.maxEnroll";
		}	
		$query .= " ORDER BY c.course, s.section";
		
		$result = mysql_query($query);
		if(!$result) {
			die(json_encode(array("error" => "mysql", "msg" => "There was a database error!", "arg" => "course")));
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
	// GET TIME DROPDOWNS
	case "getTimeField":
		// Verify that we have the field name and the default time
		if(empty($_POST['name'])) {
			die(json_encode(array("error"=>"argument", "msg"=>"A field name must be provided", "arg"=>"name")));
		}
		if(empty($_POST['default'])) {
			die(json_encode(array("error"=>"argument", "msg"=>"A default time must be provided", "arg"=>"default")));
		}

		// Return the code
		echo json_encode(array("code" => getTimeField($_POST['name'], $_POST['default'])));

		break;

	////////////////////////////////////////////////////////////////////////
	// GET MATCHING SCHEDULES
	case "getMatchingSchedules":
		// Process the list of courses that were selected
		$couseSet = array();
		for($i = 1; $i <= $_POST['courseCount']; $i++) {		// It's 1-indexed... :[
			// Iterate over the courses in that course slot
			if(!isset($_POST["courses{$i}Opt"])) { continue; }
			$courseSubSet = array();
			foreach($_POST["courses{$i}Opt"] as $course) {
				// Split it by the -'s to get dept-course-sect
				$courseSplit = explode('-', $course);
				
				// Do a query to get the course specified
				$courseSubSet[] = getCourse($_POST['quarter'], $courseSplit[0], $courseSplit[1], $courseSplit[2]);
			}
			$courseSet[] = $courseSubSet;
		}

		// Process the list of nonCourse Items
		$nonCourseSet = array();
		for($i = 1; $i <= $_POST['nonCourseCount']; $i++) {
			// If there are no days set for the item, ignore it
			if(empty($_POST["nonCourseDays{$i}"])) { continue; }

			// Create a new nonCourse Item
			$nonCourse = array();
			$nonCourse['title'] = $_POST["nonCourseTitle{$i}"];
			$nonCourse['courseNum'] = "non";
			$nonCourse['times'] = array();
			foreach($_POST["nonCourseDays{$i}"] as $day) {
				$nonCourse['times'][] = array(
					"day"   => translateDay($day),
					"start" => $_POST["nonCourseStartTime{$i}"],
					"end"   => $_POST["nonCourseEndTime{$i}"]
					);
			}
			$nonCourseSet[] = $nonCourse;
		}

		// If both the nonCourse items AND the course items list is empty, we can't draw a schedule
		if(empty($courseSet) && empty($nonCourseSet)) {
			die(json_encode(array("error" => "user", "msg" => "Cannot generate schedules because no courses or course items were provided")));
		}

		// Process the list of noCourse Times
		$noCourseSet = array();
		for($i = 1; $i <= $_POST['noCourseCount']; $i++) {
			// If there are no days set for the time slot, ignore it
			if(empty($_POST["noCourseDays{$i}"])) { continue; }
			
			// Create a new noCourse time slot
			$noCourse = array();
			$noCourse['times'] = array();
			foreach($_POST["noCourseDays{$i}"] as $day) {
				$noCourse['times'][] = array(
					"day"   => translateDay($day),
					"start" => $_POST["noCourseStartTime{$i}"],
					"end"   => $_POST["noCourseEndTime{$i}"]
					);
			}
			$noCourseSet[] = $noCourse;
		}

		// Generate valid schedules, and include the errors if we're being verbose
		$results = array();
		if(!empty($courseSet)) {
			$results['schedules'] = generateSchedules($courseSet, $nonCourseSet, $noCourseSet);
		} else {
			$results['schedules'] = array(array());
		}
		// Add the nonCourse items to the schedules (they are guaranteed not to overlap via generateSchedules)
		foreach($results['schedules'] as $k => $schedule) {
			foreach($nonCourseSet as $nonCourse) {
				$results['schedules'][$k][] = $nonCourse;
			}
		}

		if(isset($_POST['verbose']) && $_POST['verbose'] && count($ERRORS)) {
			$results['errors'] = $ERRORS;
		}
		
		echo json_encode($results);
		
		break;

	////////////////////////////////////////////////////////////////////////
	// STORE A SCHEDULE
	case "saveSchedule":
		// There has to be a json object given
		if(empty($_POST['data'])) {
			die(json_encode(array("error" => "argument", "msg" => "No schedule was provided", "arg" => "schedule")));
		}
		$_POST['data'] = html_entity_decode($_POST['data'], ENT_QUOTES);
		$json = stripslashes($_POST['data']);
		
		// Make sure the object was successfully decoded
		$json = json_decode($json, true);
		if($json == null) {
			die(json_encode(array("error" => "argument", "msg" => "The schedule could not be decoded", "arg" => "schedule")));
		}

		// Start the storing process with storing the data about the schedule
		$query = "INSERT INTO schedules (startday, endday, starttime, endtime)" .
				" VALUES('{$json['startday']}', '{$json['endday']}', '{$json['starttime']}', '{$json['endtime']}')";
		$result = mysql_query($query);
		if(!$result) {
			die(json_encode(array("error" => "mysql", "msg" => "Failed to store the schedule: " . mysql_error($dbConn))));
		}
		
		// Grab the latest id for the schedule
		$schedId = mysql_insert_id();

		// Now iterate through the schedule
		foreach($json['schedule'] as $item) {
			// Process it into schedulenoncourses if the item is a non-course item
			if($item['courseNum'] == "non") {
				// Process each time as a seperate item
				foreach($item['times'] as $time) {
					$query = "INSERT INTO schedulenoncourses (title, day, start, end, schedule)" .
							" VALUES('{$item['title']}', '{$time['day']}', '{$time['start']}', '{$time['end']}', '{$schedId}')";
					$result = mysql_query($query);
					if(!$result) {
						die(json_encode(array("error" => "mysql", "msg" => "Storing non-course item '{$item['title']}' failed: " . mysql_error($dbConn))));
					}
				}
			} else {
				// Process each course. It's crazy simple now.
				$query = "INSERT INTO schedulecourses (schedule, section)" .
						" VALUES('{$schedId}', '{$item['sectionId']}')";
				$result = mysql_query($query);
				if(!$result) {
					die(json_encode(array("error" => "mysql", "msg" => "Storing a course '{$item['courseNum']}' failed: " . mysql_error($dbConn))));
				}
			}
		}

		// Everything was successful, return a nice, simple URL to the schedule
		// To make it cool, let's make it a hex id
		$hexId = dechex($schedId);
		$url = "{$HTTPROOTADDRESS}schedule.php?id={$hexId}";
		
		echo json_encode(array("url" => $url, "id" => $hexId));

		break;

	////////////////////////////////////////////////////////////////////////
	// DEFAULT ACTION	
	default:
		echo json_encode(array("error" => "argument", "msg" => "Invalid or no action provided", "arg" => "action"));
		break;

}


