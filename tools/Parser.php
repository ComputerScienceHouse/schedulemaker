<?php

namespace Tools;


use mysqli;
use mysqli_result;

/**
 * Dump Parser
 *
 * A parsing object used by the processDump script
 *
 * @package Tools
 */
class Parser {

    private $dbConn;
    public $debugMode;
    private $quietMode;

    public function __construct(mysqli $dbConn, array $arguments) {
        $this->dbConn = $dbConn;
        $this->debugMode = in_array("-d", $arguments);
        $this->quietMode = in_array("-q", $arguments);
        if (in_array("-c", $arguments)) {
            // Cleanup mode cleans up old partial parses
            $this->cleanup();
            die();
        }
    }

    function cleanup() {
        // Emit a debug message
        $this->debug("... Cleaning up temporary tables");

        // Drop the temporary tables
        if (!mysqli_query($this->dbConn, "DROP TABLE classes")) {
            echo("*** Failed to drop table classes (ignored)\n");
            echo("    " . mysqli_error($this->dbConn) . "\n");
        }
        if (!mysqli_query($this->dbConn, "DROP TABLE meeting")) {
            echo("*** Failed to drop table meeting (ignored)\n");
            echo("    " . mysqli_error($this->dbConn) . "\n");
        }
        if (!mysqli_query($this->dbConn, "DROP TABLE instructors")) {
            echo("*** Failed to drop table instructor (ignored)\n");
            echo("    " . mysqli_error($this->dbConn) . "\n");
        }
    }

    function debug($str, $nl = true) {
        if ($this->debugMode) {
            echo($str . (($nl) ? "\n" : ""));
        }
    }

    function cleanupExtraResults($dbConn) {
        // While there are more results, free them
        while (mysqli_next_result($dbConn)) {
            $set = mysqli_use_result($dbConn);
            if ($set instanceof mysqli_result) {
                mysqli_free_result($set);
            }
        }
    }

    /**
     * Outputs error messages, cleans up temporaray tables, then dies
     * NOTE: Halts execution via die();
     * @param $messages mixed   Array of messages to output
     */
    function halt($messages) {
        // Iterate over the messages, cleanup and die
        if (is_array($messages)) {
            foreach ($messages as $message) {
                echo "*** {$message}\n";
            }
        } else {
            echo "*** {$messages}\n";
        }
        $this->cleanup();
        die();
    }

    /**
     * Inserts or updates a course. This function calls the stored procedure for
     * inserting or updating a course.
     * @param $quarter      int     The term that the course is in
     * @param $departCode   string  The code of the department
     * @param $classCode    string  The code for the class
     * @param $course       string  The number of the course
     * @param $credits      int     The credits the course offers
     * @param $title        string  The title of the course
     * @param $description  string  The description for the course
     * @return  mixed   String of error message returned on failure.
     *                  Integer of course ID returned on success
     */
    function insertOrUpdateCourse(int $quarter, string $departCode, string $classCode, string $course, int $credits, string $title, string $description) {
        global $coursesUpdated, $coursesAdded;
        // Call the stored proc
        // TODO: Refactor out department ID number (0000)
        $query = "CALL InsertOrUpdateCourse({$quarter}, 0000, '{$classCode}', '{$course}', {$credits}, '{$title}', '{$description}')";
        $success = mysqli_multi_query($this->dbConn, $query);

        // Catch errors or return the id
        if (!$success) {
            // If the class code errors out, try the department code
            // TODO: Refactor out department ID number (0000)
            $query = "CALL InsertOrUpdateCourse({$quarter}, 0000, '{$departCode}', '{$course}', {$credits}, '{$title}', '{$description}')";
            $success = mysqli_multi_query($this->dbConn, $query);
            if (!$success) {
                return mysqli_error($this->dbConn);
            }
        }

        // First result set is updated vs inserted
        $actionSet = mysqli_store_result($this->dbConn);
        $action = mysqli_fetch_assoc($actionSet);
        if ($action['action'] == "updated") {
            $coursesUpdated++;
        } else {
            $coursesAdded++;
        }
        mysqli_free_result($actionSet);

        // Second set is the id of the course
        mysqli_next_result($this->dbConn);
        $idSet = mysqli_store_result($this->dbConn);
        $id = mysqli_fetch_assoc($idSet);

        // Free up the other calls
        mysqli_free_result($idSet);
        $this->cleanupExtraResults($this->dbConn);

        return $id['id'];
    }

    function insertOrUpdateSection($courseId, $section, $title, $instructor, $type, $status, $maxenroll, $curenroll) {
        global $sectUpdated, $sectAdded;

        // Query to call the stored proc
        $query = "CALL InsertOrUpdateSection({$courseId}, '{$section}', '{$title}', '{$instructor}', '{$type}', '{$status}',";
        $query .= "{$maxenroll},{$curenroll})";

        // Error check
        if (!mysqli_multi_query($this->dbConn, $query)) {
            return mysqli_error($this->dbConn);
        }

        // First result is the action performed
        $actionSet = mysqli_store_result($this->dbConn);
        $action = mysqli_fetch_assoc($actionSet);
        if ($action['action'] == "updated") {
            $sectUpdated++;
        } else {
            $sectAdded++;
        }
        mysqli_free_result($actionSet);

        // Second result is the
        mysqli_next_result($this->dbConn);
        $idSet = mysqli_store_result($this->dbConn);
        $id = mysqli_fetch_assoc($idSet);

        // Free up other results
        mysqli_free_result($idSet);
        $this->cleanupExtraResults($this->dbConn);

        return $id['id'];
    }

    function getTempSections($courseNum, $offerNum, $term, $sessionNum) {
        // Query for the sections of the course
        $query = "SELECT class_section,descr,topic,enrl_stat,class_stat,class_type,enrl_cap,enrl_tot,instruction_mode,schedule_print ";
        $query .= "FROM classes WHERE crse_id={$courseNum} AND crse_offer_nbr={$offerNum} AND strm={$term} ";
        $query .= "AND session_code='{$sessionNum}'";
        $results = mysqli_query($this->dbConn, $query);

        // Check for errors
        if (!$results) {
            return mysqli_error($this->dbConn);
        }

        // Turn the results into an array of results
        // @TODO: Can we do this with fetch_all? Do we have mysql_nd?
        $list = [];
        while ($row = mysqli_fetch_assoc($results)) {
            $list[] = $row;
        }
        return $list;
    }

    function fileToTempTable(string $tableName, $file, $fields, $fileSize, string $procFunc = null) {
        // Process the file
        $procBytes = 0;
        $outPercent = [0];
        $this->debug("... Copying {$tableName} file to temporary table\n0%", false);
        while ($str = fgets($file, 4096)) {
            // Trim those damn newlines
            $str = trim($str);

            // Progress bar
            if ($this->debugMode) {
                $percent = floor(($procBytes / $fileSize) * 100);
                if ($percent % 10 == 0 && !in_array($percent, $outPercent)) {
                    $outPercent[] = $percent;
                    echo("...{$percent}%");
                }
            }

            // If we don't have 23 pipes, then we need to read another line
            $lineSplit = explode("|", $str);
            while (count($lineSplit) < $fields + 1) {
                $str .= fgets($file, 4096);
                $lineSplit = explode("|", $str);
            }
            $procBytes += strlen($str) + 1;

            // If we don't have $fields+1 fields, shit's borked
            if (count($lineSplit) != $fields + 1) {
                echo("*** Malformed line {$fields}, " . count($lineSplit) . "\n");
                echo($str . "\n");
                continue;
            }

            // We only need the first $fields, otherwise imploding will break
            $lineSplit = array_splice($lineSplit, 0, $fields, true);

            // Call the special attribute processing function
            if ($procFunc) {
                $lineSplit = call_user_func([$this, $procFunc], $lineSplit);
                if ($lineSplit === false) {
                    // The proc function doesn't want us to proceed with
                    // this line
                    continue;
                }
            }

            // Build a query
            $insQuery = "INSERT INTO {$tableName} VALUES('" . implode("', '", $lineSplit) . "')";
            if (!mysqli_query($this->dbConn, $insQuery)) {
                echo("*** Failed to insert {$tableName}\n");
                echo("    " . mysqli_error($this->dbConn) . "\n");
                continue;
            }
        }

        $this->debug("...100%");
    }

    // Process the class file
    function procClassArray(array $lineSplit): array {
        // Escape class title, description, and course number (since it needs to be trimmed)
        $lineSplit[6] = $this->dbConn->real_escape_string(trim($lineSplit[6]));
        $lineSplit[7] = $this->dbConn->real_escape_string($lineSplit[7]);
        $lineSplit[8] = $this->dbConn->real_escape_string(trim($lineSplit[8]));
        $lineSplit[23] = $this->dbConn->real_escape_string($lineSplit[23]);

        // Grab the integer credit count (they give it to us as a decimal)
        preg_match('/(\d)+\.\d\d/', $lineSplit[11], $match);
        $lineSplit[11] = $match[1];

        // Make the section number at least 2 digits
        $lineSplit[4] = str_pad($lineSplit[4], 2, '0', STR_PAD_LEFT);
        return $lineSplit;
    }

    // Process the meeting pattern file
    function procMeetArray(array $lineSplit) {
        // Turn the start/end times from 03:45 PM to 154500
        // Hours must be mod'd by 12 so 12:00 PM does not become
        // 24:00 and 12 AM does not become 12:00
        $timePreg = "/(\d\d):(\d\d) ([A-Z]{2})/";
        if (!preg_match($timePreg, $lineSplit[10], $start) || !preg_match($timePreg, $lineSplit[11], $end)) {
            // Odds are the class is TBD (which means we can't represent it)
            return false;
        }
        $lineSplit[10] = (($start[3] == 'PM') ? ($start[1] % 12) + 12 : $start[1] % 12) . $start[2] . "00";
        $lineSplit[11] = (($end[3] == 'PM') ? ($end[1] % 12) + 12 : $end[1] % 12) . $end[2] . "00";

        // Section number needs to be padded to at least 2 digits
        $lineSplit[4] = str_pad($lineSplit[4], 2, '0', STR_PAD_LEFT);
        return $lineSplit;
    }

    function procInstrArray(array $lineSplit): array {
        // Escape the instructor names
        $lineSplit[6] = mysqli_real_escape_string($this->dbConn, $lineSplit[6]);
        $lineSplit[7] = mysqli_real_escape_string($this->dbConn, $lineSplit[7]);

        // Section number needs to be padded to at lease 2 digits
        $lineSplit[4] = str_pad($lineSplit[4], 2, '0', STR_PAD_LEFT);
        return $lineSplit;
    }
}
