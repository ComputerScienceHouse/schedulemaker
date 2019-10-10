<?php

namespace API;

/***************************************************************************
 * Generation Class for creating schedule possibilities
 *
 * @package API
 * @author  Devin Matte <matted@csh.rit.edu>
 ***************************************************************************/
class Generate {

    /**
     * Generates a list of valid courses using a recursive tree-traversing
     * algorithm. This also prunes any branches of the tree that are invalid.
     * @param    array $courses    The list of courses set up as an array of course slots, which are arrays of course
     *                             information.
     * @param    array $nonCourses The list of nonCourse items that are fixed in the schedule
     * @param    array $noCourses  The list of times when the user does not want courses
     * @param    array $chain      A partially built schedule. Basically the parent list of the tree traversal
     * @param    array $results    The list of complete and valid schedules
     * @param    int   $level      What level of the tree we're currently at
     * @return    array    Returns an array of complete and valid schedules
     */
    public function generateSchedules(array $courses, array $nonCourses, array $noCourses, array $chain = [], array $results = [], int $level = 0) {
        // Iterate over the course choices in the level
        $oldChain = $chain;        // Use this to preserve the chain to eliminate multiple sections in same schedule
        foreach ($courses[$level] as $childCourse) {
            if (!$this->overlap($chain, $childCourse) &&
                !$this->overlapNonCourse($nonCourses, $childCourse) &&
                !$this->overlapNoCourse($noCourses, $childCourse)) {
                // It doesn't overlap, so add it to the chain
                $chain[] = $childCourse;

                // If there are further children, recurse through them.
                if ($level < count($courses) - 1) {
                    $results = $this->generateSchedules($courses, $nonCourses, $noCourses, $chain, $results, $level + 1);
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


    private function overlapBase($item, $course) {
        // If there isn't even times defined for this course, or item, then
        // return false
        if (empty($item['times']) || empty($course['times'])) {
            return false;
        }

        // Does the item overlap with the course?
        foreach ($item['times'] as $itemTime) {
            foreach ($course['times'] as $courseTime) {
                if (
                    (
                        ($itemTime['start'] <= $courseTime['start'] && $courseTime['start'] < $itemTime['end']) ||  // itemStart <= courseStart < itemEnd
                        ($itemTime['start'] < $courseTime['end'] && $courseTime['end'] <= $itemTime['end']) ||      // OR itemStart < courseEnd <= itemEnd
                        ($courseTime['start'] <= $itemTime['start'] && $courseTime['end'] >= $itemTime['end'])      // the course engulfs the item
                    ) &&
                    $courseTime['day'] == $itemTime['day']                                                            // AND the days are the same
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
     * @param    array $schedule A partial schedule (generally the chain from generateSchedules)
     * @param    array $course   The course that could be added to the schedule. It will be validated
     * @return    bool    True if the course overlaps, false otherwise
     */
    private function overlap(array $schedule, array $course): bool {
        // Pull in the error global
        global $ERRORS;

        // If there are no courses in the schedule, there is no overlap, duh!
        if (count($schedule) == 0) {
            return false;
        }

        // Now we need to do some comparisons. Do any course time slots overlap?
        foreach ($schedule as $c) {
            if ($this->overlapBase($c, $course)) {
                // It overlaps.
                $ERRORS[] = ["error" => "conflict", "msg" => "A schedule could not be generated because {$c['courseNum']} conflicts with {$course['courseNum']}"];
                return true;
            }
        }

        // Apparently it doesn't overlap!
        return false;
    }

    private function overlapNonCourse($nonCourses, $course): bool {
        // Pull in the error global
        global $ERRORS;

        // If there's no nonCourses, there is no overlap, duh!
        if (count($nonCourses) == 0) {
            return false;
        }

        // Now we need to do comparisons. Do any of the nonCourse items overlap with this course?
        foreach ($nonCourses as $c) {
            if ($this->overlapBase($c, $course)) {
                // It overlaps.
                $ERRORS[] = ["error" => "conflict", "msg" => "A schedule could not be generated because {$course['courseNum']} conflicts with '{$c['title']}'"];
                return true;
            }
        }

        // Apparently, it doesn't overlap!
        return false;
    }

    private function overlapNoCourse($noCourses, $course): bool {
        // Pull in the error global
        global $ERRORS;

        // If there's no noCourses, there's no overlap.
        if (count($noCourses) == 0) {
            return false;
        }

        // Compare the noCourse times with the time of the course
        foreach ($noCourses as $c) {
            if ($this->overlapBase($c, $course)) {
                // It overlaps.
                $ERRORS[] = ["error" => "conflict", "msg" => "A schedule could not be generated because {$course['courseNum']} occurs during a time you don't want classes"];
                return true;
            }
        }

        // Apparently, it doesn't overlap!
        return false;
    }

    /**
     * Returns a cleaned course number, free of special sections or designators
     * @param $courseInfo
     * @return mixed
     */
    public function getCleanCourseNum($courseInfo) {
        $matches = [];
        if (preg_match('/^(.*?)-?(?:[A-Z]\d{0,2})$/', $courseInfo['courseNum'], $matches) === 1) {
            return $matches[1];
        } else {
            return $courseInfo['courseNum'];
        }
    }

    /**
     * Prunes invalid schedules based on courseGroups
     * @param $schedules
     * @param $courseGroups
     * @return array
     */
    public function pruneSpecialCourses($schedules, $courseGroups): array {

        // The array of schedules that meet all course requirements
        $validSchedules = [];

        // Loop through each possible schedule
        foreach ($schedules as $schedule) {

            // Flattened schedule [courseNum => <value>] where <value> is:
            // false: no co-requirements
            // true: is a co-requirement
            // string[]: list of possible requirements
            $flattenedSchedule = [];

            // Loop through each course
            foreach ($schedule as &$course) {

                $cleanCourseNum = $this->getCleanCourseNum($course);

                // This course has selected labs or is a lab
                if (array_key_exists($cleanCourseNum, $courseGroups) && count($courseGroups[$cleanCourseNum]) > 0) {
                    if (!isSpecialSection($course)) {

                        // Set the course requirement as an array of courseNum strings
                        $flattenedSchedule[$course['courseNum']] = array_keys($courseGroups[$cleanCourseNum]);
                    } else {
                        $flattenedSchedule[$course['courseNum']] = true;
                    }
                } else {
                    $flattenedSchedule[$course['courseNum']] = false;
                }
            }

            $scheduleMeetsRequirements = true;
            // Loop through the flatten schedules
            foreach ($flattenedSchedule as $courseNum => $courseRequirements) {

                // Check if course has requirements
                if (is_array($courseRequirements)) {
                    $courseMeetsRequirement = false;

                    // Loop through the requirements, checking if the schedule contains AT LEAST one required course
                    foreach ($courseRequirements as $specialCourseNum) {
                        if (array_key_exists($specialCourseNum, $flattenedSchedule)) {
                            $courseMeetsRequirement = true;
                            break;
                        }
                    }

                    // "AND" the previous results with the current one
                    $scheduleMeetsRequirements = $scheduleMeetsRequirements && $courseMeetsRequirement;

                    // Don't bother checking other courses if the schedule already does not meet requirements
                    if (!$scheduleMeetsRequirements) {
                        continue;
                    }
                }
            }

            // Add this to the valid schedules if it meets all requirements
            if ($scheduleMeetsRequirements) {
                $validSchedules[] = $schedule;
            }
        }

        // Return the resulting array of all schedules that met co-requirements
        return $validSchedules;
    }

}
