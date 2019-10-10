<?php
/***************************************************************************
 * GENERATION AJAX CALLS
 * Provides standalone JSON object retrieval for schedule designing form　and display
 *
 * PHP Version 7
 *
 * @author Ben Russell <benrr101@csh.rit.edu>
 * @file   api/generate.php
 ***************************************************************************/

// REQUIRED FILES
if (file_exists('../inc/config.php')) {
    include_once "../inc/config.php";
} else {
    include_once "../inc/config.env.php";
}
require_once "../inc/databaseConn.php";
require_once "../inc/timeFunctions.php";
require_once "../inc/ajaxError.php";
require_once "../api/src/Generate.php";

// IMPORTS
use API\Generate;

// GLOBALS /////////////////////////////////////////////////////////////////
$ERRORS = [];        // Storage for course conflicts & recoverable errors
$generator = new Generate();

// ERROR HANDLING //////////////////////////////////////////////////////////

function ajaxErrorHandler($errno, $errstr, $errfile, $errline) {
    echo(json_encode(["error" => "php", "msg" => $errstr, "num" => $errno, "file" => $errfile, "linenum" => $errline]));
    die();
}

set_error_handler('ajaxErrorHandler');

// MAIN EXECUTION //////////////////////////////////////////////////////////

// We're providing JSON
header('Content-type: application/json');

// Escape the post data
$_POST = sanitize($_POST);

switch (getAction()) {
    // GET COURSE OPTIONS //////////////////////////////////////////////////
    case "getCourseOpts":
        // @TODO: Move this over to the power search ajax handler
        // Verify that we got a course (or partial course) and a quarter
        if (empty($_POST['course'])) {
            die(json_encode(["error" => "argument", "msg" => "You must provide at least one partial course number"]));
        }
        if (empty($_POST['term'])) {
            die(json_encode(["error" => "argument", "msg" => "You must provide a term"]));
        }

        // Iterate over the multiple options
        $courseOptions = [];
        foreach (explode(',', $_POST['course']) as $course) {
            // If the course has enough characters for a lab section but
            // but doesn't match OR there are <= 12 characters but it isn't
            // numeric, then they fucked up.
            if (strlen($course) > 13) {
                die(json_encode(["error" => "argument", "msg" => "Your courses must be in the format XXXX-XXX-XXLX"]));
            }

            $course = strtoupper($course);
            preg_match('/([A-Z]{4})[-\s]*(\d{0,3}[A-Z]?)?(?:[-\s]+(\d{0,2}[A-Z]?\d?))?/', $course, $courseParts);

            // Query base: Noncancelled courses from the requested term
            $query = "SELECT s.id
                      FROM courses AS c
                        JOIN sections AS s ON s.course = c.id
                        JOIN departments AS d ON c.department = d.id
                      WHERE
                        s.status != 'X'
                        AND c.quarter = '{$_POST['term']}'";

            // Component 1: Department
            $department = $courseParts[1];
            if (strlen($department) != 4) {
                // We didn't get an entire department. We won't proceed
                die(json_encode(["error" => "argument", "msg" => "You must provide at least a complete department"]));
            }
            $query .= " AND (d.code = '{$department}' OR d.number = '{$department}')";

            // Component 2: Course number
            $coursenum = array_key_exists(2, $courseParts) ? $courseParts[2] : null;
            if (!$coursenum || (strlen($coursenum) != 3 && strlen($coursenum) != 4)) {
                // We got a partial course. That's ok.
                $query .= " AND c.course LIKE '{$coursenum}%'";
            } else {
                // The user has specified a 3 or 4 character course number. If its 4 chars then the user had better know
                // what they're doing.
                $query .= " AND c.course = '{$coursenum}'";
            }

            // Component 3: Section number
            $section = array_key_exists(3, $courseParts) ? $courseParts[3] : null;
            if (!$section || strlen($coursenum) != 4) {
                // We got a partial section number. That's ok.
                $query .= " AND s.section LIKE '{$section}%'";
            } else {
                $query .= " AND s.section = '{$section}'";
            }

            // Ignore full courses option
            if ($_POST['ignoreFull'] == 'true') {
                $query .= " AND s.curEnroll < s.maxEnroll";
            }

            // Close it up and provide order
            $query .= " ORDER BY c.course, s.section";

            $result = $dbConn->query($query);
            if (!$result) {
                die(json_encode(["error" => "mysql", "msg" => "A database error occurred while searching for {$course}"]));
            }
            if ($result->num_rows == 0) {
                continue;
            }

            // Fetch all the results and append them to the list
            while ($row = $result->fetch_assoc()) {
                $courseOptions[] = getCourseBySectionId($row['id']);
            }
        }

        if (count($courseOptions) == 0) {
            die(json_encode(["error" => "result", "msg" => "No courses match"]));
        }

        // Puke the results back to the user
        echo json_encode($courseOptions);

        break;

    // GET MATCHING SCHEDULES //////////////////////////////////////////////
    case "getMatchingSchedules":
        // Process the list of courses that were selected

        // Keep track of grouped classes by both clean course name (sections) and by course input index
        /**
         * array(string {cleanCourseNum} => array(string {courseNum} => {courseInfo array})))
         */
        $courseGroups = [];

        /**
         * array(int {course input index} => array(integer {courseId} => array(string {courseNum} => {courseInfo array})))
         */
        $courseGroupsByCourseId = [];

        $courseSet = [];

        // Check to make sure schedule wont exceed 10,000 options
        $totalSchedules = 1;
        for ($i = 1; $i <= $_POST['courseCount']; $i++) {
            if (!isset($_POST["courses{$i}Opt"])) {
                continue;
            }
            $totalSchedules *= count($_POST["courses{$i}Opt"]);
        }
        if ($totalSchedules >= 10000) {
            echo json_encode([
                "error" => "argument",
                "msg" => "Too many schedule possibilities to generate, try to remove classes from your shopping cart. 
            Adding classes like YearOne or classes with hundreds of sections can cause this to occur.",
                "arg" => "action"
            ]);
            break;
        }

        for ($i = 1; $i <= $_POST['courseCount']; $i++) {        // It's 1-indexed... :[
            // Iterate over the courses in that course slot
            if (!isset($_POST["courses{$i}Opt"])) {
                continue;
            }
            $courseSubSet = [];
            $courseGroupsByCourseId[$i] = [];
            foreach ($_POST["courses{$i}Opt"] as $course) {

                // Do a query to get the course specified
                $courseInfo = getCourseBySectionId($course);

                // courseIndex is only used by the frontend UI to determine what color/grouping to use
                $courseInfo['courseIndex'] = $i;

                // Remove the potential special indicators from the end of the courseNum
                $cleanCourseNum = $generator->getCleanCourseNum($courseInfo);

                // Create the group if it does not already exist
                if (!array_key_exists($cleanCourseNum, $courseGroups)) {
                    $courseGroups[$cleanCourseNum] = [];
                }

                // Create the group by index and course id. Can probably ignore courseId, but will be eventually useful
                if (!array_key_exists($courseInfo['courseId'], $courseGroupsByCourseId[$i])) {
                    $courseGroupsByCourseId[$i][$courseInfo['courseId']] = [];
                }

                // Check if the section is a special course: courseNum ending in a letter, then one or two digits
                if (isSpecialSection($courseInfo)) {

                    if (!array_key_exists($courseInfo['courseNum'], $courseGroups[$cleanCourseNum])) {
                        // Add this course to its group
                        $courseGroups[$cleanCourseNum][$courseInfo['courseNum']] = $courseInfo;
                    }

                    if (!array_key_exists($courseInfo['courseNum'], $courseGroupsByCourseId[$i][$courseInfo['courseId']])) {
                        // Add this course to its group by course id
                        $courseGroupsByCourseId[$i][$courseInfo['courseId']][$courseInfo['courseNum']] = $courseInfo;
                    }

                } else {
                    // This is a normal class, it can be added like normal to the sub set
                    $courseSubSet[] = $courseInfo;
                }
            }

            // Add the normal subset to the main set
            if (count($courseSubSet) > 0) {
                $courseSet[] = $courseSubSet;
            }
        }


        // Loop through each course groups' courses and flatten the array
        if (count($courseGroups) > 0) {
            foreach ($courseGroupsByCourseId as $courseGroupsByIndex) {
                $specialCourseSubSet = [];
                foreach ($courseGroupsByIndex as $courseGroup) {
                    // Get each special course
                    foreach ($courseGroup as $specialCourse) {
                        $specialCourseSubSet[] = $specialCourse;
                    }
                }

                // Add any special courses for this index to the main courseSet.
                if (count($specialCourseSubSet) > 0) {
                    $courseSet[] = $specialCourseSubSet;
                }
            }
        }

        // Set the courseIndex for the remaining nonCourse/noCourse routines
        $courseIndex = $i;

        // Process the list of nonCourse Items
        $nonCourseSet = [];
        for ($i = 1; $i <= $_POST['nonCourseCount']; $i++) {
            // If there are no days set for the item, ignore it
            if (empty($_POST["nonCourseDays{$i}"])) {
                continue;
            }

            // Create a new nonCourse Item
            $nonCourse = [];
            $nonCourse['title'] = $_POST["nonCourseTitle{$i}"];
            $nonCourse['courseNum'] = "non";
            $nonCourse['courseIndex'] = $courseIndex++;
            $nonCourse['times'] = [];

            // Create a time entry for each
            foreach ($_POST["nonCourseDays{$i}"] as $day) {
                $nonCourse['times'][] = [
                    "day" => translateDay($day),
                    "start" => intval($_POST["nonCourseStartTime{$i}"]),
                    "end" => intval($_POST["nonCourseEndTime{$i}"])
                ];
            }
            $nonCourseSet[] = $nonCourse;
        }

        // If both the nonCourse items AND the course items list is empty, we can't draw a schedule
        if (empty($courseSet) && empty($nonCourseSet)) {
            die(json_encode([
                "error" => "user",
                "msg" =>
                    "Cannot generate schedules because no courses or course items were provided"
            ]));
        }

        // Process the list of noCourse Times
        $noCourseSet = [];
        for ($i = 1; $i <= $_POST['noCourseCount']; $i++) {
            // If there are no days set for the time slot, ignore it
            if (empty($_POST["noCourseDays{$i}"])) {
                continue;
            }

            // Create a new noCourse time slot
            $noCourse = [];
            $noCourse['times'] = [];
            foreach ($_POST["noCourseDays{$i}"] as $day) {
                $noCourse['times'][] = [
                    "day" => translateDay($day),
                    "start" => intval($_POST["noCourseStartTime{$i}"]),
                    "end" => intval($_POST["noCourseEndTime{$i}"])
                ];
            }
            $noCourseSet[] = $noCourse;
        }

        // Generate valid schedules, and include the errors if we're being verbose
        $results = [];
        if (!empty($courseSet)) {
            $results['schedules'] = $generator->pruneSpecialCourses(
                $generator->generateSchedules($courseSet, $nonCourseSet, $noCourseSet), $courseGroups);
        } else {
            $results['schedules'] = [[]];
        }
        // Add the nonCourse items to the schedules (they are guaranteed not to overlap via generateSchedules)
        foreach ($results['schedules'] as $k => $schedule) {
            foreach ($nonCourseSet as $nonCourse) {
                $results['schedules'][$k][] = $nonCourse;
            }
        }

        if (isset($_POST['verbose']) && $_POST['verbose'] && count($ERRORS)) {
            $results['errors'] = $ERRORS;
        }

        echo json_encode($results);

        break;

    // DEFAULT ACTION //////////////////////////////////////////////////////
    default:
        echo json_encode(["error" => "argument", "msg" => "Invalid or no action provided", "arg" => "action"]);
        break;

}
