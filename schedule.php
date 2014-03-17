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
require_once('./inc/config.php');
require_once('./inc/databaseConn.php');
require_once('./inc/timeFunctions.php');

// FUNCTIONS ///////////////////////////////////////////////////////////////

function drawCourse($course, $startTime, $endTime, $startDay, $endDay, $color, $bldg) {
	$code = "";

	// Iterate over the times that the course has session
    if(empty($course['times'])) { return ""; }
	foreach($course['times'] as $time) {
		// Skip times that aren't part of the displayed days
		if($time['day'] < $startDay || $time['day'] > $endDay) {
			continue;
		}

        // Shorten classes that lie across the displayed hours
        if($time['start'] < $startTime) {
            if($time['end'] > $startTime) {
                echo("short!");
                $time['start'] = $startTime;    // Class starts before schedule but ends after sched starts. Shorten it.
                $shortenStart = true;
            } else {
                continue;                       // Class starts and ends before schedule. Skip it.
            }
        }

        if($time['end'] > $endTime) {
            if($time['start'] < $endTime) {
                $time['end'] = $endTime;        // Class ends after schedule starts, but starts before sched ends. Shorten it.
                $shortenEnd = true;
            } else {
                continue;                       // Class starts and ends after the schedule. Skip it.
            }
        }

		// Add a div for the time
        // Build out the classes to use
        $classes = "day" . (($time['day']) - $startDay);
        $classes .= " color{$color}";
        $classes .= " timeContainer";
        $classes .= (!empty($shortenStart)) ? " shortenTop" : "";
        $classes .= (!empty($shortenEnd)) ? " shortenBottom" : "";

        // Build out the style for positioning/size
        $height    = (ceil(($time['end'] - $time['start']) / 30) * 20) - 1;
        $topOffset = (floor(($time['start'] - $startTime) / 30) * 20) + 20;
        $style = "height: {$height}px; top: {$topOffset}px";

		$code .= "<div class='{$classes}' style = '{$style}'>";
		
		// Add information about the course
        $shortHeader = ($height <= 40) ? "class='shortHeader'" : "";
		$code .= "<h4 {$shortHeader}>{$course['title']}</h4>";
		$code .= "<div>";
		if($course['courseNum'] != "non") {
		    $code .= $course['courseNum'] . "<br />";
			$code .= $course['instructor'] . "<br />";
			$code .= $time['bldg'][$bldg] . "-" . $time['room'];
		}
		$code .= "</div>";
		

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

function generateScheduleFromCourses($courses) {
	// Grab the start/end time/day
	$courseList = $courses['courses'][0];
	$startTime  = $courses['startTime'];
	$endTime    = $courses['endTime'];
	$startDay   = $courses['startDay'];
	$endDay     = $courses['endDay'];

	// Do some calculations for height/width
	$schedHeight = floor(($endTime - $startTime) / 30) * 20 + 20;
	$schedWidth  = (($endDay - $startDay) * 100) + 200;

	// Start outputting the code
	$code = "<div class='schedSupaWrapper'>";
	$code .= "<div class='scheduleWrapper' style='height:{$schedHeight}px; width:{$schedWidth}px'>";
	$code .= "<div class='schedule' style='height:{$schedHeight}px; width:{$schedWidth}px'>";
	$code .= "<img src='img/grid.png'>";
	$code .= drawHeaders($startTime, $endTime, $startDay, $endDay);

	// Storage for potential online courses
	$onlineCourses = array();

	// Output each of the courses in the schedule
	for($i = 0; $i < count($courseList); $i++) {
		if($courseList[$i]['courseNum'] != 'non' && $courseList[$i]['online']) {
			// Add it to the list of online courses
			$onlineCourses[] = $courseList[$i];
			continue;
		}

		$color = $i % 4;
		$code .= drawCourse($courseList[$i], $startTime, $endTime, $startDay, $endDay, $color, $courses['bldgStyle']);
	}
	$code .= "</div></div>";
	
	// Output a notice if there were online courses
	if(count($onlineCourses)) {
		$code .= "<div class='schedNotes' style='width:{$schedWidth}px'>";
		$code .= "<p>Notice: This schedule contains online courses: ";
		foreach($onlineCourses as $course) {
			$code .= $course['courseNum'];
		}
		$code .= "</p></div>";
	}
	$code .= "</div>";

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

// MAIN EXECUTION //////////////////////////////////////////////////////////

// Determine the output mode
$mode = (empty($_REQUEST['mode'])) ? "schedule" : $_REQUEST['mode'];

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
			<h1 id='header' style='text-align:center'></h1>
			<div id='schedules'></div>
			<? require "inc/footer_print.inc"; ?>
		</body>
		<script type='text/javascript'>
			// Load the data out of the local storage and store it in the
			// global fields
			data = eval("(" + window.sessionStorage.getItem("scheduleJson") + ")");
			schedules   = data.courses;
			startday    = data.startDay;
			endday      = data.endDay;
			starttime   = data.startTime;
			endtime     = data.endTime;
			SCHEDPERPAGE= 1;

			// Calculate the term for header purposes
            // Split it up and store it as the header
            var year = parseInt(data.term.substring(0,4));
            var term = data.term.substring(4);
            if(year >= 2013) {
                switch(term) {
                    case '1': term = "Fall"; break;
                    case '3': term = "Winter Intersession"; break;
                    case '5': term = "Spring"; break;
                    case '8': term = "Summer"; break;
                    default:  term = "Unknown";
                }
            } else {
                switch(term) {
                    case '1': term = "Fall"; break;
                    case '2': term = "Winter"; break;
                    case '3': term = "Spring"; break;
                    case '4': term = "Summer"; break;
                    default:  term = "Unknown";
                }
            }
            $("#header").html("My " + year + "-" + (year+1) + " " + term  + " Schedule");

            // Add a super-jank building style hidden element so the buildings
            // will always be what the user requested, not the default
            // @TODO: This is SUPER JANK. And really, I don't like the print/schedule javascript.
            var bldgHidden = $("<input type='hidden' id='buildingStyle'/>");
            bldgHidden.val(data.bldgStyle);
            bldgHidden.appendTo($("body"));

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
		</html>
		<?
		break;
	
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
		require "./inc/header.inc";
		
		// Grab the schedule
		$schedule = getScheduleFromOldId($_GET['id']);
		if($schedule == NULL) {
			?>
			<div class="alert alert-danger">
				<i class="fa fa-exclamation-circle"></i> <span style='font-weight:bold'>Fatal Error:</span> The requested schedule does not exist!
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
        $id = hexdec($_GET['id']);
        $schedule = getScheduleFromId($id);

        // Make sure the schedule exists
		if($schedule == NULL) {
            // Schedule does not exist. Error out and die.
            require "./inc/header.inc";
			?>
			<div class="container">
				<div class="alert alert-danger">
					<i class="fa fa-exclamation-circle"></i> <strong>Fatal Error:</strong> The requested schedule does not exist!
				</div>
			</div>
			<?
            require "./inc/footer.inc";
            die();
		}

        // Schedule exists! Output it.
        // Set image location (if it exists)
        if($schedule['image'] == 1) {
            $IMGURL = "{$HTTPROOTADDRESS}img/schedules/{$id}.png";
        }
        $TITLE = "My Schedule"; //@TODO: Generate this with term titles

        require "./inc/header.inc";

		// Translate the schedule into json and escape '
		$json = json_encode($schedule);
		//$json = htmlentities($json, ENT_COMPAT);
		?>
		<script>var reloadSchedule = <?=$json?>;</script>
		<div class="container" ng-controller="scheduleCtrl">
			<div schedule existing="true"></div>
		</div>
		
		<?
		require "./inc/footer.inc";
		break;
		
	case "json":
		// JSON DATA STRUCTURE /////////////////////////////////////////////
		// We're outputting json, so use that 
		header('Content-type: application/json');

		// Required parameters
		if(empty($_GET['id'])) {
            die(json_encode(array("error"=>true, "msg"=>"You must provide a schedule")));
        }

        // Database connection is required
        require_once("inc/databaseConn.php");
        require_once("inc/timeFunctions.php");

		// Pull the schedule and output it as json
		$schedule = getScheduleFromId(hexdec($_GET['id']));
		if ( $schedule == NULL ) {
			echo json_encode(array(
					'error' => true,
					'msg' => 'Schedule not found'
				));
		} else {
			echo json_encode($schedule);
		}

		break;

	default:
		// INVALID OPTION //////////////////////////////////////////////////
		echo "Invalid option!";
		break;
}
?>
