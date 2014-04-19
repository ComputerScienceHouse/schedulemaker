<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE LOOKUP
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	schedule.php
// @descrip	Loads up the requested schedule from the database.
////////////////////////////////////////////////////////////////////////////

// REQUIRED FILES //////////////////////////////////////////////////////////
require_once('../inc/config.php');
require_once('../inc/databaseConn.php');
require_once('../inc/timeFunctions.php');

// FUNCTIONS ///////////////////////////////////////////////////////////////

function icalFormatTime($time) {
	// Get the GMT difference
	$gmtDiff = substr(date("O"), 0, 3);
	
	// Minutes->hrs mins
	$hr = (int)($time / 60);
	$min = $time % 60;
	
	return str_pad($hr % 24, 2, '0', STR_PAD_LEFT) 
		. str_pad($min, 2, '0', STR_PAD_LEFT)
		. "00";
}

function generateIcal($schedule) {
	// Globals
	global $HTTPROOTADDRESS;

	// We need to lookup the information about the quarter
	$term = mysql_real_escape_string($schedule['term']);
	$query = "SELECT start, end, breakstart, breakend FROM quarters WHERE quarter='{$term}'";
	$result = mysql_query($query);
	$term = mysql_fetch_assoc($result);
	$termStart = strtotime($term['start']);
	$termEnd = date("Ymd", strtotime($term['end']));

	// Start generating code
	$code = "";

	// Header
	$code .= "BEGIN:VCALENDAR\r\n";
	$code .= "VERSION:2.0\r\n";
	$code .= "PRODID: -//CSH ScheduleMaker//iCal4j 1.0//EN\r\n";
	$code .= "METHOD:PUBLISH\r\n";
	$code .= "CALSCALE:GREGORIAN\r\n";

	// Iterate over all the courses
	foreach($schedule['courses'][0] as $course) {
		// Skip classes that don't meet
		if(empty($course['times'])) {
			continue;
		}
		
		// Iterate over all the times
		foreach($course['times'] as $time) {
			$code .= "BEGIN:VEVENT\r\n";
			$code .= "UID:" . md5(uniqid(mt_rand(), true) . " @{$HTTPROOTADDRESS}");
			$code .= "\r\n";
			$code .= "TZID:America/New_York\r\n";
			$code .= "DTSTAMP:" . gmdate('Ymd') . "T" . gmdate("His") . "Z\r\n";

			$startTime = icalFormatTime($time['start']);
			$endTime = icalFormatTime($time['end']);

			// The start day of the event MUST be offset by it's day
			// the -1 is b/c quarter starts are on Monday(=1)
			// This /could/ be done via the RRULE WKST param, but that means
			// translating days from numbers to some other esoteric format.
            // @TODO: Retrieve the timezone from php or the config file
			$day = date("Ymd", $termStart + ((60*60*24)*($time['day']-1)));

            $code .= "DTSTART;TZID=America/New_York:{$day}T{$startTime}\r\n";
            $code .= "DTEND;TZID=America/New_York:{$day}T{$endTime}\r\n";
			$code .= "RRULE:FREQ=WEEKLY;UNTIL={$termEnd}\r\n";
			$code .= "ORGANIZER:RIT\r\n";
			
			// Course name
			$code .= "SUMMARY:{$course['title']}";
			if($course['courseNum'] != 'non') {
				$code .= " ({$course['courseNum']})";
			}
			$code .= "\r\n";

			// Meeting location
			if($course['courseNum'] != 'non') {
				$bldg = $time['bldg'][$schedule['bldgStyle']];
				$code .= "LOCATION:{$bldg}-{$time['room']}\r\n";
			}
			
			$code .= "END:VEVENT\r\n";
		}
	}

	$code .= "END:VCALENDAR\r\n";

	return $code;
}

function getScheduleFromId($id) {
	// Query to see if the id exists, if we can update the last accessed time,
	// then the id most definitely exists.
	$query = "UPDATE schedules SET datelastaccessed = NOW() WHERE id={$id}";
	$result = mysql_query($query);
	
	$query = "SELECT startday, endday, starttime, endtime, building, `quarter`, CAST(`image` AS unsigned int) AS `image` FROM schedules WHERE id={$id}";

	$result = mysql_query($query);
    if(!$result) {
        return NULL;
    }
	$scheduleInfo = mysql_fetch_assoc($result);
	if(!$scheduleInfo) {
		return NULL;
	}

	// Grab the metadata of the schedule
	$startDay  = (int)$scheduleInfo['startday'];
	$endDay    = (int)$scheduleInfo['endday'];
	$startTime = (int)$scheduleInfo['starttime'];
	$endTime   = (int)$scheduleInfo['endtime'];
	$building  = $scheduleInfo['building'];
	$term      = $scheduleInfo['quarter'];
    $image     = $scheduleInfo['image'] == 1;

	// Create storage for the courses that will be returned
	$schedule = array();

	// It exists, so grab all the courses that exist for this schedule
	$query = "SELECT section FROM schedulecourses WHERE schedule = {$id}";
	$result = mysql_query($query);
	while($course = mysql_fetch_assoc($result)) {
		$schedule[] = getCourseBySectionId($course['section']);
	}

	// Grab all the non courses that exist for this schedule
	$query = "SELECT * FROM schedulenoncourses WHERE schedule = $id";
	$result = mysql_query($query);
	if(!$result) {
		echo mysql_error();
	}
	while($nonCourseInfo = mysql_fetch_assoc($result)) {
		$schedule[] = array(
			"title"     => $nonCourseInfo['title'],
			"courseNum" => "non",
			"times"     => array(array(
							"day"   => $nonCourseInfo['day'],
							"start" => $nonCourseInfo['start'],
							"end"   => $nonCourseInfo['end']
							))
			);
	}

	return array(
			//REMOVED WEIRD NESTED ARRAY FOR 'courses'??????
			"courses"    => $schedule,
			"startTime"  => $startTime,
			"endTime"    => $endTime,
			"startDay"   => $startDay,
			"endDay"     => $endDay,
			"bldgStyle"  => $building,
			"term"       => $term,
            "image"      => $image
			);
}

function getScheduleFromOldId($id) {
	$query = "SELECT id FROM schedules WHERE oldid = '{$id}'";
	$result = mysql_query($query);
	if(!$result || mysql_num_rows($result) != 1) {
		return NULL;
	} else {
		$newId = mysql_fetch_assoc($result);
		$newId = $newId['id'];
		$schedule = getScheduleFromId($newId);
		$schedule['id'] = $newId;
		return $schedule;
	}
}

function queryOldId($id) {
	// Grab all the courses that match the id
	$query = "SELECT c.section FROM schedules AS s, schedulecourses AS c WHERE s.id = c.section AND s.oldid = '{$id}'";
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
		 
		// Prepend parsing info
		$svg = preg_replace('/(.*<svg[^>]* width=")(100\%)(.*)/', '${1}1000px${3}', $svg);
		$svg = '<?xml version="1.1" encoding="UTF-8" standalone="no"?>' . $svg;
		// Load the image into an ImageMagick object
		$im = new Imagick();
		$im->readimageblob($svg);

		// Convert it to png
		$im->setImageFormat("png24");

		$im->scaleimage(1000, 600, true);


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

// MAIN EXECUTION //////////////////////////////////////////////////////////

// Not using getAction() here
$path = explode('/', $_SERVER['REQUEST_URI']);

$id = (empty($path[2]))? '': hexdec($path[2]);
// Determine the output mode
$mode = (empty($path[3])) ? "schedule" : $path[3];

// Switch on the mode
switch($mode) {
	
	case "ical":
		// iCAL FORMAT SCHEDULE ////////////////////////////////////////////
		// If we don't have a schedule, die!
		if(empty($_GET['id'])) {
			die("You must provide a schedule");
		}

		// Database connection is required
		require_once("inc/databaseConn.php");
		require_once("inc/timeFunctions.php");

		// Decode the schedule
		$schedule = getScheduleFromId(hexdec($_GET['id']));		

		// Set header for ical mime, output the xml
		header("Content-Type: text/calendar");
		header("Content-Disposition: attachment; filename=generated_schedule" . md5(serialize($schedule)) . ".ics");
		echo generateIcal($schedule);
		
		break;
	
	case "old":
		// OLD SCHEDULE FORMAT /////////////////////////////////////////////
		//TODO: Support the old format
		/*
		// Grab the schedule
		$schedule = getScheduleFromOldId($_GET['id']);
		if($schedule == NULL) {
			?>
			<div class="container">
				<div class="alert alert-danger">
					<i class="fa fa-exclamation-circle"></i> <strong>Fatal Error:</strong> The requested schedule does not exist!
				</div>
			</div>
			<?
		} else {
			$json = json_encode($schedule);
			?>
			<script>var reloadSchedule = <?=$json?>;</script>
			<div class="container" ng-controller="scheduleController">
				<div class="alert alert-info">
					<i class="fa fa-exclamation-circle"></i> This schedule was created with a really old version of ScheduleMaker
				</div>
				<div schedule existing="true"></div>
			</div>
			<?
		}
		*/
		echo json_encode(array("error" => "Not supported on this platform. Please use http://schedule-old.csh.rit.edu/"));
		
		break;

		
	case "schedule":
		// JSON DATA STRUCTURE /////////////////////////////////////////////
		// We're outputting json, so use that 
		header('Content-type: application/json');

		// Required parameters
		if(empty($id)) {
            die(json_encode(array("error"=>true, "msg"=>"You must provide a schedule")));
        }
        
		// Pull the schedule and output it as json
		$schedule = getScheduleFromId($id);
		if ( $schedule == NULL ) {
			echo json_encode(array(
					'error' => true,
					'msg' => 'Schedule not found'
				));
		} else {
			echo json_encode($schedule);
		}

		break;
		
	////////////////////////////////////////////////////////////////////////
	// STORE A SCHEDULE
		case "save":
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
			$url = "{$HTTPROOTADDRESS}schedule/{$hexId}";
		
			echo json_encode(array("url" => $url, "id" => $hexId));
		
			break;

	default:
		// INVALID OPTION //////////////////////////////////////////////////
		 die(json_encode(array("error" => "argument", "msg" => "You must provide a valid action.")));
		break;
}
?>
