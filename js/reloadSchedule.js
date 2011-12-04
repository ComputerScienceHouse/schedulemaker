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
			courseOnFocus($("#courses" + courseId));
			$("#courses" + courseId).val(schedule.courses[c].courseNum);
			getCourseOptions(document.getElementById("courses" + courseId));

			courseId++;
		} else {
			// Add another nonCourse slot if we don't have enough
			if($("#nonCourseCount").val() < nonCourseId) {
				addItem();
			}
			
			// Fill in the title
			var title = $("#nonCourseTitle" + nonCourseId);
			title.val(schedule.courses[c].title);
			
			// Give a high-level view of the tree for ease of manipulation
			var nonCourseRow = title.parent().parent().children();

			// Fill in the start and end times
			var startDrop = nonCourseRow[1].children[0].children;
			for(var i=0; i < startDrop.length; i++) {
				// Set the start time
				if(startDrop[i].value == schedule.courses[c].times[0].start) {
					$(startDrop[i]).attr("selected", "selected");
					break;
				}
			}

			var endDrop = nonCourseRow[2].children[0].children;
			for(var i=0; i < endDrop.length; i++) {
				// Set the end time
				if(endDrop[i].value == schedule.courses[c].times[0].end) {
					$(endDrop[i]).prop("selected", true);
					break;
				}
			}

			// Fill in the day USING MATH!
			$(nonCourseRow[parseInt(schedule.courses[c].times[0].day) + 3].children).prop("checked", true);
			
			nonCourseId++;
		}
	}

	// Remove the info from the session storage (so we can store more later)
	sessionStorage.removeItem("scheduleJson");
}
