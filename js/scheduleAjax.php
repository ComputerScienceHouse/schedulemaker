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
					($itemTime['start'] <= $courseTime['start'] && $courseTime['start'] < $itemTime['end']) ||  // itemStart <= courseStart < itemEnd
					($itemTime['start'] < $courseTime['end'] && $courseTime['end'] <= $itemTime['end']) ||      // OR itemStart < courseEnd <= itemEnd
                    ($courseTime['start'] <= $itemTime['start'] && $courseTime['end'] >= $itemTime['end'])      // the course engulfs the item
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

/**
 * Converts a time string into the number of minutes the time is past midnight
 * @param $str  string  A string time format (eg: "8:00am")
 * @return  int The number of minutes past midnight the time is
 */
function timeStringToMinutes($str) {
    $time = strtotime($str);
    $hour = (int)date("G", $time);
    $minute = (int)date("i", $time);
    return $hour * 60 + $minute;
}

/**
 * Generates a render of schedule's SVG. The PNG render of the image will be
 * stored in /img/schedules/ with a filename equal to the id of the schedule.
 * @param   $svg    string  The SVG code for the image
 * @param   $id     string  The ID of the schedule, for file name generation
 * @return  bool    True on success, False otherwise.
 */
function renderSvg($svg, $id) {
    try {
        // Load the image into an ImageMagick object
        $im = new Imagick();
        $im->readimageblob($svg);

        // Convert it to png
        $im->setImageFormat("png24");
        $im->scaleimage(600, 600, true);


        // Write it to the filesystem
        $im->writeimage("../img/schedules/{$id}.png");
        $im->clear();
        $im->destroy();

        // Success!
        return true;

    } catch(Exception $e) {
        return false;
    }
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
        // @TODO: Move this over to the power search ajax handler
		// Verify that we got a course (or partial course) and a quarter
		if(empty($_POST['course'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide at least one partial course number")));
		}
		if(empty($_POST['term'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a term")));
		}

        // If it has dashes or whitespace, then strip them out
        $_POST['course'] = preg_replace("/[-\s]/", "", $_POST['course']);

        // Iterate over the multiple options
        $courseOptions = array();
        foreach(explode(',', $_POST['course']) as $course) {
            // If the course has enough characters for a lab section but
            // but doesn't match OR there are <= 9 characters but it isn't
            // numeric, then they fucked up.
            if(strlen($course) > 11) {
                die(json_encode(array("error" => "argument", "msg" => "Your courses must be in the format XXXX-XXX-XXLX")));
            }

            // Now we'll split the course into the various components and build the query for it all
            // Query base: Noncancelled courses from the requested term
            $query = "SELECT s.id
                      FROM courses AS c
                        JOIN sections AS s ON s.course = c.id
                        JOIN departments AS d ON c.department = d.id
                      WHERE
                        s.status != 'X'
                        AND c.quarter = '{$_POST['term']}'";

            // Component 1: Department
            $department = substr($course, 0, 4);
            if(strlen($department) != 4) {
                // We didn't get an entire department. We won't proceed
                die(json_encode(array("error" => "argument", "msg" => "You must provide at least a complete department")));
            }
            $query .= " AND (d.code = '{$department}' OR d.number = '{$department}')";

            // Component 2: Course number
            $coursenum = substr($course, 4, 3);
            if(!$coursenum || strlen($coursenum) != 3) {
                // We got a partial course. That's ok.
                $query .= " AND c.course LIKE '{$coursenum}%'";
            } else {
                $query .= " AND c.course = '{$coursenum}'";
            }

            // Component 3: Section number
            $section = substr($course, 7);
            if(!$section || strlen($coursenum) != 4) {
                // We got a partial section number. That's ok.
                $query .= " AND s.section LIKE '{$section}%'";
            } else {
                $query .= " AND s.section = '{$section}'";
            }

            // Ignore full courses option
            if($_POST['ignoreFull'] == 'true') {
                $query .= " AND s.curEnroll < s.maxEnroll";
            }

            // Close it up and provide order
            $query .= " ORDER BY c.course, s.section";

            $result = mysql_query($query);
            if(!$result) {
                die(json_encode(array("error" => "mysql", "msg" => "A database error occurred while searching for {$course}")));
            }
            if(mysql_num_rows($result) == 0) { continue; }

            // Fetch all the results and append them to the list
            while($row = mysql_fetch_assoc($result)) {
                $courseOptions[] = getCourseBySectionId($row['id']);
            }
        }

        if(count($courseOptions) == 0) {
            die(json_encode(array("error" => "result", "msg" => "No courses match")));
        }

        // Puke the results back to the user
		echo json_encode($courseOptions);

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
		$courseSet = array();
		for($i = 1; $i <= $_POST['courseCount']; $i++) {		// It's 1-indexed... :[
			// Iterate over the courses in that course slot
			if(!isset($_POST["courses{$i}Opt"])) { continue; }
			$courseSubSet = array();
			foreach($_POST["courses{$i}Opt"] as $course) {
				// Do a query to get the course specified
				$courseSubSet[] = getCourseBySectionId($course);
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

            // Create a time entry for each
			foreach($_POST["nonCourseDays{$i}"] as $day) {
				$nonCourse['times'][] = array(
					"day"   => translateDay($day),
					"start" => timeStringToMinutes($_POST["nonCourseStartTime{$i}"]),
					"end"   => timeStringToMinutes($_POST["nonCourseEndTime{$i}"])
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
					"start" => timeStringToMinutes($_POST["noCourseStartTime{$i}"]),
					"end"   => timeStringToMinutes($_POST["noCourseEndTime{$i}"])
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
		if(!isset($json['starttime']) || !isset($json['endtime']) || !isset($json['building']) || !isset($json['startday']) || !isset($json['endday'])) {
            die(json_encode(array("error" => "argument", "msg" => "A required schedule parameter was not provided")));
        }

		// Start the storing process with storing the data about the schedule
		$query = "INSERT INTO schedules (oldid, startday, endday, starttime, endtime, building, quarter)" .
				" VALUES('', '{$json['startday']}', '{$json['endday']}', '{$json['starttime']}', '{$json['endtime']}', '{$json['building']}', " .
				" '{$json['term']}')";
		$result = mysql_query($query);
		if(!$result) {
			die(json_encode(array("error" => "mysql", "msg" => "Failed to store the schedule: " . mysql_error($dbConn))));
		}

		// Grab the latest id for the schedule
		$schedId = mysql_insert_id();

        // Optionally process the svg for the schedule
        $image = false;
        if(!empty($_POST['svg']) && renderSvg(html_entity_decode($_POST['svg']), $schedId)) {
            $query = "UPDATE schedules SET image = ((1)) WHERE id = '{$schedId}'";
            mysql_query($query);  // We don't particularly care if this fails
        }

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
						" VALUES('{$schedId}', '{$item['id']}')";
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


