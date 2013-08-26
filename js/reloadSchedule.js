////////////////////////////////////////////////////////////////////////////
// RELOADING A SCHEDULE
//
// @file	js/reloadSchedule.js
// @descrip	This file provides javascript functionality for taking a schedule
//			stored in sessionStorage and loading all the variables into the
//			generation form.
// @author	Ben Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

function reloadSchedule() {
	// Pull down the schedule information from the session storage
	var schedule = eval("(" + sessionStorage.scheduleJson + ")");
	// @TODO: WHY O WHY DO WE NEED THIS NESTED ARRAYYYYYYYYYY?????!!!
	schedule.courses = schedule.courses[0];
	
	// Grab and set the schedule's quarter
	var quarter = schedule.quarter;
	$("#quarter").val(quarter);
	
	// Create enough course fields to fit all the COURSES
	var courseId = 1;
	var nonCourseId = 1;
	for(var c = 0; c < schedule.courses.length; c++) {
		if(schedule.courses[c].courseNum != 'non') {
			// Add another course slot if we don't have enough
			if($("#courseCount").val() < courseId) {
				addCourse();
			}
			
			// Fill in the course and prompt a reloading
            var courseInput = $("#courses" + courseId);
			courseInput.val(schedule.courses[c].courseNum);
			getCourseOptions(courseInput);

			courseId++;
		} else {
			// Add another nonCourse slot if we don't have enough
			if($("#nonCourseCount").val() < nonCourseId) {
				addNonCourseItem($("#addNonCourseButton"));
			}
			
			// Fill in the title
			var title = $("#nonCourseTitle" + nonCourseId);
			title.val(schedule.courses[c].title);
			
			// Give a high-level view of the tree for ease of manipulation
			var nonCourseRow = title.parent().parent().children();

			// Fill in the start and end times
            var nonCourseRowElements = nonCourseRow.children();
			var startDrop = $(nonCourseRowElements[1]);
			var endDrop = $(nonCourseRowElements[2]);

            var startTime = parseInt(schedule.courses[c].times[0].start);
            var endTime = parseInt(schedule.courses[c].times[0].end);

            startDrop.val(Math.floor(startTime / 60) + ":" + (startTime % 60));
            endDrop.val(Math.floor(endTime / 60) + ":" + (endTime % 60));

            endDrop.timepicker({
                showDuration: true,
                durationTime: startDrop.val(),
                minTime: startDrop.val()
            });

			// Fill in the day USING MATH!
			$(nonCourseRow[parseInt(schedule.courses[c].times[0].day) + 3].children).prop("checked", true);
			
			nonCourseId++;
		}
	}

	// Remove the info from the session storage (so we can store more later)
	sessionStorage.removeItem("scheduleJson");
}
