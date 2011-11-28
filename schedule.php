<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE LOOKUP
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	schedule.php
// @descrip	Loads up the requested schedule from the database.
////////////////////////////////////////////////////////////////////////////

// FUNCTIONS ///////////////////////////////////////////////////////////////

function drawCourse($course, $startTime, $endTime, $startDay, $endDay, $headerColor) {
	$code = "";

	// Iterate over the times that the couse has session
	foreach($course['times'] as $time) {
		// Skip times that aren't part of the displayed days
		if($time['day'] < $startDay || $time['day'] > $endDay) {
			continue;
		}

		// Skip times that aren't part of displayed hours
		if($time['start'] < $startTime || $time['start'] > $endTime || $time['end'] > $endTime) {
			continue;
		}

		// Add a div for the time
		$code .= "<div class='day" . ($time['day'] - $startDay) . "' style = '";

		$height    = (ceil(($time['end'] - $time['start']) / 30) * 20) - 1;
		$topOffset = (floor(($time['start'] - $startTime) / 30) * 20) + 20;
		$code .= "height: {$height}px; top: {$topOffset}px";
		$code .= "'>";
		
		// Add information about the course
		$code .= "<h4 class='colorHeader{$headerColor}'>{$course['title']}</h4>";
		if($course['courseNum'] != "non") {
			$code .= "<div>{$course['courseNum']}<br />";
			if($height > 60) {
				$code .= $course['instructor'] . "<br />";
			}
			if($height > 40) {
				$code .= $time['bldg'] . "-" . $time['room'];
			}
			$code .= "</div>";
		}

		$code .= "</div>";
	}
	return $code;
}

function drawHeaders($startTime, $endTime, $startDay, $endDay) {
	// Draw the days of the week. We're doing it the fancy way.
	$code = "";
	switch($startDay) {
		case 0:
			$code .= "<div class='weekday day0'>Sunday</div>";
			if($endDay == 0) {break;}
		case 1:
			$code .= "<div class='weekday day" . (1 - $startDay) . "'>Monday</div>";
			if($endDay == 1) {break;}
		case 2:
			$code .= "<div class='weekday day" . (2 - $startDay) . "'>Tuesday</div>";
			if($endDay == 2) {break;}
		case 3:
			$code .= "<div class='weekday day" . (3 - $startDay) . "'>Wednesday</div>";
			if($endDay == 3) {break;}
		case 4:
			$code .= "<div class='weekday day" . (4 - $startDay) . "'>Thursday</div>";
			if($endDay == 4) {break;}
		case 5:
			$code .= "<div class='weekday day" . (5 - $startDay) . "'>Friday</div>";
			if($endDay == 5) {break;}
		case 6:
			$code .= "<div class='weekday day" . (6 - $startDay) . "'>Saturday</div>";
			if($endDay == 6) {break;}
	break;
	}

	// Draw the time divs
	for($time = $startTime; $time < $endTime; $time += 30) {
		$code .= "<div class='daytime' style='top:" . (floor((($time - $startTime) / 30) * 20) + 20) . "px'>";
		$code .= translateTime($time);
		$code .= "</div>";
	}

	return $code;
}

function generateIcal($schedule) {
	// Start generating code
	$code = "";

	// Iterate over all the courses
	foreach($schedule as $course) {
		// Iterate over all the times
		foreach($course['times'] as $time) {
			$code .= "BEGIN:VEVENT\n";
			$code .= "UID:" . md5(uniqueid(mt_rand(), true) . " @{$HTTPROOTADDRESS}\n");
			$code .= "DTSTAMP:" . gmdate('Ymd') . "T" . gmdate("His") . "Z\n";

			// Convert the times
			$startTime = str_replace(":", "", translateTime($time['start'])) . "00";
			$endTime   = str_replace(":", "", translateTime($time['end'])) . "00";

			$code .= "DTSTART:{$DATE}T{$startTime}Z\n";
			$code .= "DTEND:{$DATE}T{$endTime}Z\n";
			$code .= "RRULE:Hot dickings\n";
			$code .= "TZID:America/New_York\n";
			$code .= "LOCATION:{$time['bldg']}-{$time['room']}\n";
			$code .= "ORGANIZER:RIT";
			$code .= "SUMMARY:{$course['title']} ({$course['courseNum']})";
			
			$code .= "END:VEVENT\n";
		}
	}

	return $code;
}

function generateScheduleFromCourses($courses) {
	// Grab the start/end time/day
	$courseList = $courses['courses'];
	$startTime  = $courses['startTime'];
	$endTime    = $courses['endTime'];
	$startDay   = $courses['startDay'];
	$endDay     = $courses['endDay'];

	// Do some calculations for height/width
	$schedHeight = floor(($endTime - $startTime) / 30) * 20 + 20;
	$schedWidth  = (($endDay - $startDay) * 100) + 200;

	// Start outputting the code
	$code = "<div class='scheduleWrapper' style='height:{$schedHeight}px; width:{$schedWidth}px'>";
	$code .= "<div class='schedule' style='height:{$schedHeight}px; width:{$schedWidth}px'>";
	$code .= "<img src='img/grid.png'>";
	$code .= drawHeaders($startTime, $endTime, $startDay, $endDay);

	for($i = 0; $i < count($courseList); $i++) {
		$color = $i % 4;
		$code .= drawCourse($courseList[$i], $startTime, $endTime, $startDay, $endDay, $color);
	}
	$code .= "</div></div>";

	return $code;
}

function getScheduleFromId($id) {
	// Query to see if the id exists, if we can update the last accessed time,
	// then the id most definitely exists.
	$query = "UPDATE schedules SET datelastaccessed = NOW() WHERE id={$id}";
	$result = mysql_query($query);
	if(!$result || mysql_affected_rows() != 1) {
		return NULL;
	}
	
	$query = "SELECT startday, endday, starttime, endtime FROM schedules WHERE id={$id}";
	$result = mysql_query($query);
	$scheduleInfo = mysql_fetch_assoc($result);

	// Grab the metadata of the schedule
	$startDay  = $scheduleInfo['startday'];
	$endDay    = $scheduleInfo['endday'];
	$startTime = $scheduleInfo['starttime'];
	$endTime   = $scheduleInfo['endtime'];

	// Create storage for the courses that will be returned
	$schedule = array();

	// It exists, so grab all the courses that exist for this schedule
	$query = "SELECT section FROM schedulecourses WHERE schedule = {$id}";
	$result = mysql_query($query);
	while($course = mysql_fetch_assoc($result)) {
		// Query for the section's information
		$query = "SELECT * FROM sections WHERE id={$course['section']}";
		$sectionResult = mysql_query($query);
		$sectionInfo = mysql_fetch_assoc($sectionResult);

		// Query for the course's information
		$query = "SELECT * FROM courses WHERE id={$sectionInfo['course']}";
		$courseResult = mysql_query($query);
		$courseInfo = mysql_fetch_assoc($courseResult);

		// Generate the information for the course
		$course = array(
			"title"      => $courseInfo['title'],
			"instructor" => $sectionInfo['instructor'],
			"curenroll"  => $sectionInfo['curenroll'],
			"maxenroll"  => $sectionInfo['maxenroll'],
			"courseNum"  => "{$courseInfo['department']}-{$courseInfo['course']}-{$sectionInfo['section']}",
			"times"      => array()
			);
		
		// Query for the times that the course has
		$query = "SELECT * FROM times WHERE section = {$sectionInfo['id']}";
		$timeResult = mysql_query($query);
		while($timeInfo = mysql_fetch_assoc($timeResult)) {
			// Add the course's times to the course information
			$course['times'][] = array(
				"bldg"  => $timeInfo['building'],
				"room"  => $timeInfo['room'],
				"day"   => $timeInfo['day'],
				"start" => $timeInfo['start'], 
				"end"   => $timeInfo['end']
				);
		}

		// Add the course to the schedule
		$schedule[] = $course;
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
			"courses"   => $schedule,
			"startTime" => $startTime,
			"endTime"   => $endTime,
			"startDay"  => $startDay,
			"endDay"    => $endDay
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

// MAIN EXECUTION //////////////////////////////////////////////////////////

// Determine the output mode
$mode = (empty($_REQUEST['mode'])) ? "schedule" : mysql_real_escape_string($_REQUEST['mode']);

// Switch on the mode
switch($mode) {
	case "print":
		// PRINTABLE SCHEDULE //////////////////////////////////////////////
		// No header, no footer, just the schedule
	
		?>
		<html>
		<head>
			<title>Your Schedule - Schedule Maker</title>
			<link href='./inc/style_print.css' rel='stylesheet' type='text/css' />
			<script src='./js/jquery.js' type='text/javascript'></script>
			<script src='./js/schedule.js' type='text/javascript'></script>
		</head>
		<body>
			<div id='schedules'></div>
		</body>
		<script type='text/javascript'>
			// Load the data out of the local storage and store it in the
			// global fields
			data = eval("(" + window.sessionStorage.getItem("scheduleJson") + ")");
			schedules = data.courses;
			startday  = data.startDay;
			endday    = data.endDay;
			starttime = data.startTime;
			endtime   = data.endTime;

			// Calculate the schedule height and width
			schedHeight = (Math.floor((endtime - starttime) / 30) * 20) + 20;
			schedWidth  = ((endday - startday) * 100) + 200;

			// Run the show schedules thing
			drawPage(0, true);

			// Load the print dialog
			$(document).ready(function() {
				if($("div").length) {
					window.print();
				}
				});
		</script>
		<? require "inc/footer_print.inc"; ?>
		</html>
		<?
		break;
	
	case "ical":
		// iCAL FORMAT SCHEDULE ////////////////////////////////////////////
		// If we don't have a schedule, die!
		if(empty($_POST['schedule'])) {
			die("You must provide a schedule");
		}

		// Decode the schedule
		$schedule = json_decode(stripslashes($_POST['schedule']), true);		

		// Set header for ical mime, output the xml
		header("Content-Type: text/calendar");
		header("Content-Disposition: attachment; filename='generated_schedule" . md5(serialize($schedule)) . ".ics'");
		?>
BEGIN:VCALENDAR
PRODID: -//CSH ScheduleMaker//iCal4j 1.0//EN
VERSION:2.0
METHOD:PUBLISH
CALSCALE:GREGORIAN
<?= generateIcal($schedule) ?>
END:VCALENDAR
		<?
		break;
	
	case "old":
		// OLD SCHEDULE FORMAT /////////////////////////////////////////////
		require "./inc/header.inc";
		
		// Grab the schedule
		$schedule = getScheduleFromOldId($_GET['id']);
		if($schedule == NULL) {
			?>
			<div class='schedUrl error'>
				<p><span style='font-weight:bold'>Fatal Error:</span> The requested schedule does not exist!</p>
			</div>
			<?
		} else {
			?>
			<div class='schedUrl'>
				<p>This schedule was created using the old schedule maker!</p>
				<p>You should now access this schedule at:
					<a href="<?= $HTTPROOTADDRESS ?>schedule.php?id=<?= dechex($schedule['id']) ?>">
						<?= $HTTPROOTADDRESS ?>schedule.php?id=<?= dechex($schedule['id']) ?>
					</a>
				</p>
			</div>
			<?
			echo generateScheduleFromCourses($schedule);
		}
		require "./inc/footer.inc";
		break;

	case "schedule":
		// DEFAULT SCHEDULE FORMAT /////////////////////////////////////////
		require "./inc/header.inc";
		
		$schedule = getScheduleFromId(hexdec($_GET['id']));
		if($schedule == NULL) {
			?>
			<div class='schedUrl error'>
				<p><span style='font-weight:bold'>Fatal Error:</span> The requested schedule does not exist!</p>
			</div>
			<?
		} else {
			echo generateScheduleFromCourses($schedule);
		}

		require "./inc/footer.inc";
		break;

	default:
		// INVALID OPTION //////////////////////////////////////////////////
		echo "Invalid option!";
		break;
}
?>
