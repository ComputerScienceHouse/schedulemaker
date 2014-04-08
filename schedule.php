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

// MAIN EXECUTION //////////////////////////////////////////////////////////

// Determine the output mode
$mode = (empty($_REQUEST['mode'])) ? "schedule" : $_REQUEST['mode'];

// Switch on the mode
switch($mode) {
	case "print":
		// PRINTABLE SCHEDULE //////////////////////////////////////////////
		// No header, no footer, just the schedule
	
		$LAYOUT_MODE = 'print';
		require "./inc/header.inc";
		
		if($_GET['id'] != 'render') {
			$id = hexdec($_GET['id']);
			if($id > 0) {
				$schedule = getScheduleFromId($id);
				// Translate the schedule into json
				$json = json_encode($schedule);
		
		?>
			<script>var reloadSchedule = <?=$json?>;</script>		
		<?
			}
		}
		?>
		<div ng-controller="printScheduleCtrl">
			<div class="container hidden-print" ng-show="schedule.length > 0">
				<div class="vert-spacer-static-md"></div>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Print Options <small>For best results, print landscape, turn off headings/footers, and set the margins to .25"</small></h3>
					</div>
					<div class="panel-body form-horizontal">
						<div class="row">
							<div class="col-sm-5">
								<div class="form-group">
									<label for="printOptions-heading" class="col-sm-4 control-label">Heading:</label>
									<div class="col-sm-8">
										<input id="printOptions-heading" class="form-control" type="input" ng-model="heading">
									</div>
								</div>
							</div>
							<div class="col-sm-5">
								<div class="form-group">
									<label for="printOptions-theme" class="col-sm-4 control-label">Theme:</label>
									<div class="col-sm-8">
										<select id="printOptions-theme" class="form-control" ng-model="printTheme" ng-options="opt.value as opt.label for opt in printThemeOptions"></select>
									</div>
								</div>
							</div>
							<div class="col-sm-2">
								<button ng-click="print()" type="button" class="btn btn-info btn-block"><i class="fa fa-print"></i> Print</button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<h2 id="print_header" class="center" ng-bind="heading"></h2>
			<div ng-switch="schedule.length > 0">
				<div ng-class="printTheme" ng-switch-when="true" schedule print="true"></div>
				<div ng-switch-when="false" class="container">
					<div class="vert-spacer-static-md"></div>
					<div class="alert alert-info">
						<i class="fa fa-exclamation-circle"></i> Please press the print button in the previous window if you wish to print a schedule.
					</div>
				</div>
			</div>
		</div>
				
		<?
		require "./inc/footer.inc";
		
		
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
			<div class="container" ng-controller="scheduleCtrl">
				<div class="alert alert-info">
					<i class="fa fa-exclamation-circle"></i> This schedule was created with a really old version of ScheduleMaker
				</div>
				<div schedule existing="true"></div>
			</div>
			<?
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

		// Translate the schedule into json
		$json = json_encode($schedule);

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
