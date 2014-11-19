<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE DATA CALLS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	api/entityData.php
// @descrip	Provides standalone JSON object retrieval for the course
//			browsing page
////////////////////////////////////////////////////////////////////////////

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

// Switch on the action
switch(getAction()) {
	case "getCourses":
		// Query for the courses in this department

		// Verify that we have department to get courses for and a quarter
		if(empty($_POST['department']) || !is_numeric($_POST['department'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a valid department")));
		} elseif(empty($_POST['term']) || !is_numeric($_POST['term'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a valid term")));
		}

		// Do the query
		$query = "SELECT c.title, c.course, c.description, c.id, d.number, d.code
                  FROM sections AS s
                  JOIN courses AS c ON s.course = c.id
                  JOIN departments AS d ON d.id = c.department
		          WHERE c.department = '{$_POST['department']}'
		            AND quarter = '{$_POST['term']}'
		            AND s.status != 'X'
		          GROUP BY c.id
		          ORDER BY course";
		$result = mysql_query($query);
		if(!$result) {
			die(json_encode(array("error" => "mysql", "msg" => mysql_error())));
		}

		// Collect the courses and turn it into a json
		$courses = array();
		while($course = mysql_fetch_assoc($result)) {
            $courses[] = array(
                "id" => $course['id'],
                "course" => $course['course'],
                "department" => array("code" => $course['code'], "number" =>$course['number']),
                "title" => $course['title'],
                "description" => $course['description']
            );
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
		if(empty($_POST['term']) || !is_numeric($_POST['term'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a term")));
		}

		// Do the query
        if($_POST['term'] > 20130) {
            // Get the department code and concat the numbers
            $query = "SELECT id, title, code, GROUP_CONCAT(number, ', ') AS number
                      FROM departments AS d
                      WHERE school = '{$_POST['school']}'
                        AND (SELECT COUNT(*) FROM courses AS c WHERE c.department=d.id AND quarter='{$_POST['term']}') >= 1
                        AND code IS NOT NULL
                      GROUP BY code
                      ORDER BY code";
        } else {
            $query = "SELECT id, title, number
                  FROM departments AS d
                  WHERE school = '{$_POST['school']}'
		            AND (SELECT COUNT(*) FROM courses AS c WHERE c.department=d.id AND quarter='{$_POST['term']}' ) >= 1
		            AND number IS NOT NULL
                  ORDER BY id";
        }
		$result = mysql_query($query);
		if(!$result) {
			die(json_encode(array("error" => "mysql", "msg" => mysql_error())));
		}

		// Collect the departments and turn it into a json
		$departments = array();
		while($department = mysql_fetch_assoc($result)) {
            $departments[] = array(
                "id" => $department['id'],
                "title" => $department['title'],
                "code" => isset($department['code']) ? $department['code'] : NULL,
                "number" => trim($department['number'], " ,")
            );
		}

		echo json_encode(array("departments" => $departments));

		break;

    case "getSchools":
        // REQUEST FOR LIST OF SCHOOLS /////////////////////////////////////
        // Query for the schools
        $query = "SELECT `id`, `number`, `code`, `title` FROM schools";
        $result = mysql_query($query);
        if(!$result) {
            die(json_encode(array("error" => "database", "msg" => "The list of schools could not be retrieved at this time.")));
        }

        // Build an array of schools
        $schools = array();
        while($school = mysql_fetch_assoc($result)) {
            $schools[] = $school;
        }

        // Return it to the user
        echo(json_encode($schools));
        break;
        
    case "getSchoolsForTerm":
        // REQUEST FOR LIST OF SCHOOLS FOR TERM ////////////////////////////
    	if(empty($_POST['term'])) {
    		die(json_encode(array("error" => "argument", "msg" => "You must provide a term")));
    	}
    	
    	$term = (int) $_POST['term'];
    	
    	// Determine if term was before quarters
		if ($term > 20130) {
			// School codes
			$query = "SELECT id, code AS code, title FROM schools WHERE code IS NOT NULL ORDER BY code";
		} else {
			// School numbers
			$query = "SELECT id, number AS code, title FROM schools WHERE number IS NOT NULL ORDER BY number";
		}
		// Query for the schools
        $result = mysql_query($query);
        if(!$result) {
        	die(json_encode(array("error" => "database", "msg" => "The list of schools could not be retrieved at this time.")));
        }
        
        // Build an array of schools
        $schools = array();
        while($school = mysql_fetch_assoc($result)) {
        	$schools[] = $school;
        }
        
        // Return it to the user
        echo(json_encode($schools));
        break;

	case "getSections":
		// Query for the sections and times of a given course
		
		// Verify that we have a course to get sections for
		if(empty($_POST['course']) || !is_numeric($_POST['course'])) {
			die(json_encode(array("error" => "argument", "msg" => "You must provide a course")));
		}

		// Do the query
		$query = "SELECT c.title AS coursetitle, c.course, d.number, d.code, s.section,
		            s.instructor, s.id, s.type, s.maxenroll, s.curenroll, s.title AS sectiontitle
		          FROM sections AS s
		            JOIN courses AS c ON s.course = c.id
		            JOIN departments AS d ON d.id = c.department
                  WHERE s.course = '{$_POST['course']}'
                    AND s.status != 'X'
                  ORDER BY c.course, s.section";
		$sectionResult = mysql_query($query);
		if(!$sectionResult) {
			die(json_encode(array("error" => "mysql", "msg" => mysql_error())));
		}
		
		// Collect the sections and their times, modify the section inline
		$sections = array();
		while($section = mysql_fetch_assoc($sectionResult)) {
			$section['times'] = array();

			// Set the course title depending on its section title
            // @TODO: Replace this with a conditional column in the query
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
			} else {
                // Look up the times the section meets
                $query = "SELECT day, start, end, b.code, b.number, b.off_campus AS off, room
                          FROM times AS t
                            JOIN buildings AS b ON b.number=t.building
                          WHERE t.section = '{$section['id']}'
                          ORDER BY day, start";
                $timeResult = mysql_query($query);
                if(!$timeResult) {
                    die(json_encode(array("error" => "mysql", "msg" => mysql_error())));
                }

                while($time = mysql_fetch_assoc($timeResult)) {
                    $timeOutput = array(
                        'start'    => $time['start'],
                        'end'      => $time['end'],
                        'day'      => $time['day'],
                        'bldg' => array(
                            'code'    => $time['code'],
                            'number'  => $time['number'],
                            'off_campus' => $time['off'] == '1'
                        ),
                        'room'     => $time['room']
                    );
                    $section['times'][] = $timeOutput;
                }
            }

            // Add the section to the result set
            $sections[] = array(
                "id"         => $section['id'],
                "department" => array("code" => $section['code'], "number" => $section['number']),
                "course"     => $section['course'],
                "section"    => $section['section'],
                "title"      => $section['title'],
                "instructor" => $section['instructor'],
                "type"       => $section['type'],
                "maxenroll"  => $section['maxenroll'],
                "curenroll"  => $section['curenroll'],
                "times"      => $section['times']
            );
		}

		// Spit out the json
		echo json_encode(array("sections" => $sections));
		break;

    case "courseForSection":
        echo json_encode(getCourseBySectionId($_POST['id'], true));
        break;

    default:
        die(json_encode(array("error" => "argument", "msg" => "You must provide a valid action.")));

}
