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
if (file_exists('../inc/config.php')) {
    require_once "../inc/config.php";
} else {
    require_once "../inc/config.env.php";
}
require_once('../vendor/autoload.php');
require_once('../inc/databaseConn.php');
require_once('../inc/timeFunctions.php');
require_once('../api/src/S3Manager.php');
require_once "../api/src/Schedule.php";

// IMPORTS
use Connections\S3Manager;
use API\Schedule;

// GLOBALS /////////////////////////////////////////////////////////////////
global $s3ImageManager;
$s3ImageManager = new S3Manager($S3_KEY, $S3_SECRET, $S3_SERVER, $S3_IMAGE_BUCKET);
$scheduleApi = new Schedule();

// MAIN EXECUTION //////////////////////////////////////////////////////////

// Not using getAction() here
$path = explode('/', $_SERVER['REQUEST_URI']);

if ($path[2] != "new") {
    $id = (empty($path[2])) ? '' : hexdec($path[2]);
    $mode = (empty($path[3])) ? "schedule" : $path[3];
} else {
    $id = null;
    $mode = "save";
}


// Switch on the mode
switch ($mode) {

    case "ical":
        // iCAL FORMAT SCHEDULE ////////////////////////////////////////////
        // If we don't have a schedule, die!
        if (empty($id)) {
            die("You must provide a schedule");
        }

        // Decode the schedule
        $schedule = $scheduleApi->getScheduleFromId($id);

        // Set header for ical mime, output the xml
        header("Content-Type: text/calendar");
        header("Content-Disposition: attachment; filename=generated_schedule" . md5(serialize($schedule)) . ".ics");
        echo $scheduleApi->generateIcal($schedule);

        break;

    case "old":
        echo json_encode(array("error" => "Not supported on this platform. Please use http://schedule-old.csh.rit.edu/"));
        break;


    case "schedule":
        // JSON DATA STRUCTURE /////////////////////////////////////////////
        // We're outputting json, so use that
        header('Content-type: application/json');

        // Required parameters
        if (empty($id)) {
            die(json_encode(array("error" => true, "msg" => "You must provide a schedule")));
        }

        // Pull the schedule and output it as json
        $schedule = $scheduleApi->getScheduleFromId($id);
        if ($schedule == NULL) {
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
        if (empty($_POST['data'])) {
            die(json_encode(array("error" => "argument", "msg" => "No schedule was provided", "arg" => "schedule")));
        }
        // This will be raw data since there is no more sanatize like there used to be in the old code
        $json = $_POST['data'];

        // Make sure the object was successfully decoded
        $json = sanitize(json_decode($json, true));
        if ($json == null) {
            die(json_encode(array("error" => "argument", "msg" => "The schedule could not be decoded", "arg" => "schedule")));
        }
        if (!isset($json['starttime']) || !isset($json['endtime']) || !isset($json['building']) || !isset($json['startday']) || !isset($json['endday'])) {
            die(json_encode(array("error" => "argument", "msg" => "A required schedule parameter was not provided")));
        }

        // Start the storing process with storing the data about the schedule
        $query = "INSERT INTO schedules (oldid, startday, endday, starttime, endtime, building, quarter)" .
            " VALUES('', '{$json['startday']}', '{$json['endday']}', '{$json['starttime']}', '{$json['endtime']}', '{$json['building']}', " .
            " '{$json['term']}')";
        $result = $dbConn->query($query);
        if (!$result) {
            die(json_encode(array("error" => "mysql", "msg" => "Failed to store the schedule: " . $dbConn->error)));
        }

        // Grab the latest id for the schedule
        $schedId = $dbConn->insert_id;

        // Optionally process the svg for the schedule
        $image = false;
        if (!empty($_POST['svg']) && $scheduleApi->renderSvg($_POST['svg'], $schedId)) {
            $query = "UPDATE schedules SET image = ((1)) WHERE id = '{$schedId}'";
            $dbConn->query($query);  // We don't particularly care if this fails
        }

        // Now iterate through the schedule
        foreach ($json['schedule'] as $item) {
            // Process it into schedulenoncourses if the item is a non-course item
            if ($item['courseNum'] == "non") {
                // Process each time as a seperate item
                foreach ($item['times'] as $time) {
                    $query = "INSERT INTO schedulenoncourses (title, day, start, end, schedule)" .
                        " VALUES('{$item['title']}', '{$time['day']}', '{$time['start']}', '{$time['end']}', '{$schedId}')";
                    $result = $dbConn->query($query);
                    if (!$result) {
                        die(json_encode(array("error" => "mysql", "msg" => "Storing non-course item '{$item['title']}' failed: " . $dbConn->error)));
                    }
                }
            } else {
                // Process each course. It's crazy simple now.
                $query = "INSERT INTO schedulecourses (schedule, section)" .
                    " VALUES('{$schedId}', '{$item['id']}')";
                $result = $dbConn->query($query);
                if (!$result) {
                    die(json_encode(array("error" => "mysql", "msg" => "Storing a course '{$item['courseNum']}' failed: " . $dbConn->erorr)));
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
