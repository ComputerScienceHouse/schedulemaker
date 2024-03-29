<?php


namespace API;


use Exception;
use Imagick;

class Schedule
{
    private function icalFormatTime($time) {
        // Get the GMT difference
        $gmtDiff = substr(date("O"), 0, 3);

        // Minutes->hrs mins
        $hr = (int)($time / 60);
        $min = $time % 60;

        return str_pad($hr % 24, 2, '0', STR_PAD_LEFT)
            . str_pad($min, 2, '0', STR_PAD_LEFT)
            . "00";
    }

    private function firstDayAfterDate($weekday, $startDate) {
        $weekdayOfStart = date('w', $startDate);
        if ($weekdayOfStart > $weekday) {
            // Try next week:
            // 5 = 7 - 1 (we only go up to six) - 1 (we don't want to double count "today")
            $startDate += 60*60*24*(5-$weekDayOfStart);
            $weekdayOfStart = 0;
        }
        // weekday - weekDayOfStart = number of days between now and the first instance of that week day
        return $startDate + (60*60*24*($weekday-$weekdayOfStart));
    }

    private function hashTime($time, $schedule) {
        return ($time['start'] . "-" .
                $time['end'] . "-" .
                $time['bldg'][$schedule['bldgStyle']] . "-" .
                $time['room']);
    }

    public function generateIcal($schedule) {
        date_default_timezone_set('America/New_York');
        // Globals
        global $HTTPROOTADDRESS, $dbConn;

        // We need to lookup the information about the quarter
        $term = $dbConn->real_escape_string($schedule['term']);
        $query = "SELECT start, end FROM quarters WHERE quarter='{$term}'";
        $result = $dbConn->query($query);
        $term = $result->fetch_assoc();
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
        foreach($schedule['courses'] as $course) {
            // Skip classes that don't meet
            if(empty($course['times'])) {
                continue;
            }

            $scheduleByTime = array();
            foreach($course['times'] as $time) {
                $hash = $this->hashTime($time, $schedule);
                // Add to scheduleByTime array
                if (array_key_exists($hash, $scheduleByTime)) {
                    $scheduleByTime[$hash][] = $time;
                } else {
                    $scheduleByTime[$hash] = array($time);
                }
            }

            // Iterate over all the times
            foreach($scheduleByTime as $times) {
                $code .= "BEGIN:VEVENT\r\n";
                $code .= "UID:" . md5(uniqid(mt_rand(), true) . " @{$HTTPROOTADDRESS}");
                $code .= "\r\n";
                $code .= "TZID:America/New_York\r\n";
                $code .= "DTSTAMP:" . gmdate('Ymd') . "T" . gmdate("His") . "Z\r\n";

                $startTime = $this->icalFormatTime($times[0]['start']);
                $endTime = $this->icalFormatTime($times[0]['end']);

                // The start day of the event MUST be offset by it's day
                // the -1 is b/c quarter starts are on Monday(=1)
                // This /could/ be done via the RRULE WKST param, but that means
                // translating days from numbers to some other esoteric format.
                // @TODO: Retrieve the timezone from php or the config file
                $day = date("Ymd", $this->firstDayAfterDate($times[0]['day'], $termStart));

                $code .= "DTSTART;TZID=America/New_York:{$day}T{$startTime}\r\n";
                $code .= "DTEND;TZID=America/New_York:{$day}T{$endTime}\r\n";
                $dayCodes = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
                $days = array();
                foreach ($times as $time) {
                    $days[] = $dayCodes[$time['day']];
                }
                $dayString = implode(',', $days);
                $code .= "RRULE:FREQ=WEEKLY;INTERVAL=1;WKST=SU;BYDAY={$dayString};UNTIL={$termEnd}\r\n";
                $code .= "ORGANIZER:RIT\r\n";

                // Course name
                $code .= "SUMMARY:{$course['title']}";
                if($course['courseNum'] != 'non') {
                    $code .= " ({$course['courseNum']})";
                }
                $code .= "\r\n";

                // Meeting location
                if($course['courseNum'] != 'non') {
                    $bldg = $times[0]['bldg'][$schedule['bldgStyle']];
                    $code .= "LOCATION:{$bldg}-{$times[0]['room']}\r\n";
                }

                $code .= "END:VEVENT\r\n";
            }
        }

        $code .= "END:VCALENDAR\r\n";

        return $code;
    }

    public function getScheduleFromId($id) {
        global $dbConn;

        // Query to see if the id exists, if we can update the last accessed time,
        // then the id most definitely exists.
        $query = "UPDATE schedules SET datelastaccessed = NOW() WHERE id={$id}";
        $result = $dbConn->query($query);

        $query = "SELECT startday, endday, starttime, endtime, building, `quarter`, CAST(`image` AS unsigned int) AS `image` FROM schedules WHERE id={$id}";

        $result = $dbConn->query($query);
        if(!$result) {
            return NULL;
        }
        $scheduleInfo = $result->fetch_assoc();
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
        $result = $dbConn->query($query);
        while($course = $result->fetch_assoc()) {
            $schedule[] = getCourseBySectionId($course['section']);
        }

        // Grab all the non courses that exist for this schedule
        $query = "SELECT * FROM schedulenoncourses WHERE schedule = $id";
        $result = $dbConn->query($query);
        if(!$result) {
            echo $dbConn->error();
        }
        while($nonCourseInfo = $result->fetch_assoc()) {
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

    private function getScheduleFromOldId($id) {
        global $dbConn;

        $query = "SELECT id FROM schedules WHERE oldid = '{$id}'";
        $result = $dbConn->query($query);
        if(!$result || $result->num_rows != 1) {
            return NULL;
        } else {
            $newId = $result->fetch_assoc();
            $newId = $newId['id'];
            $schedule = $this->getScheduleFromId($newId);
            $schedule['id'] = $newId;
            return $schedule;
        }
    }

    /**
     * Generates a render of schedule's SVG. The PNG render of the image will be
     * stored in /img/schedules/ with a filename equal to the id of the schedule.
     * @param   $svg    string  The SVG code for the image
     * @param   $id     string  The ID of the schedule, for file name generation
     * @return  bool    True on success, False otherwise.
     */
    public function renderSvg($svg, $id) {
        try {
            global $s3ImageManager;
            // Prepend parsing info
            $svg = preg_replace('/(.*<svg[^>]* width=")(100\%)(.*)/', '${1}1000px${3}', $svg);
            $svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>' . $svg;

            // Load the image into an ImageMagick object
            $im = new Imagick();
            $im->readImageBlob($svg);

            // Convert it to png
            $im->setImageFormat("png24");

            $im->scaleimage(1000, 600, true);

            // Write it to s3
            //TODO Generate non-white image
            $s3ImageManager->saveImage($im, $id);
            $im->clear();
            $im->destroy();

            // Success!
            return true;

        } catch(Exception $e) {
            return false;
        }
    }
}
