////////////////////////////////////////////////////////////////////////////
// SCHEDULE BUILDER JAVASCRIPT FUNCTIONS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/schedule.js
// @descrip	Functions for building a schedule in a fancy way
////////////////////////////////////////////////////////////////////////////

// GLOBAL VARS
var curPage;			// The current page of results being displayed
var endday;				// The ending day for the schedule
var endtime;			// The ending time for the schedule
var pages;				// The number of pages 
var schedHeight;		// Height of the schedule
var SCHEDPERPAGE;		// The number of schedules per page
var schedules;			// jSON object of schedules that was retrieved via AJAX
var schedWidth;			// Width of the schedule
var serialForm;			// The serialized form so we can tell if there has been 
						// any changes to the form
var startday;			// The starting day for the schedule
var starttime = null;	// The starting time for the schedule

// We NEED to make any ajax a synchronous call
$.ajaxSetup({async: false});
// @TODO: No we don't. This is making things slow as balls.

// If session data for a roulette course was stored, load it and delete it
$(document).ready(function() {
	// Load course roulette items from session storage
    if(sessionStorage.getItem("rouletteCourse") != null) {
		// Show the course in the list
		$("#courses1").val(sessionStorage.getItem("rouletteCourse"));
		getCourseOptions($("courses1"));

		// Delete the data from the session data
		sessionStorage.removeItem("rouletteCourse");
	}

	if(sessionStorage.getItem("scheduleJson") != null && window.location.search != "?mode=print") {
		reloadSchedule();
	}

    // Add live handlers to the timeContainers that will show/hide things
    $(document).on("mouseover", ".timeContainer", function() { $(this).addClass("timeContainerHover"); });
    $(document).on("mouseout", ".timeContainer", function() { $(this).removeClass("timeContainerHover"); });

    // Add handler to reset the course selections when terms change or when ignore full is clicked
    $("#ignoreFull").click(function() { refreshCourses(); });
    $("#term").click(function() { refreshCourses(); });

    // Add time pickers to the start time pickers
    var startTimePickers = $(".startTimePicker");
    startTimePickers.timepicker({scrollDefaultTime: "08:00" });
    $(document).on("change", ".startTimePicker", function() {
        // Add a time picker to the neighboring endTimePicker
        var endPicker = $(this).parent().parent().find(".endTimePicker");
        endPicker.timepicker("remove");
        if($(this).val() != "") {
            endPicker.timepicker({
                showDuration: true,
                durationTime: $(this).val(),
                minTime: $(this).val()
            });
        }
    });

    // Don't do anything on enter being pressed
    $(document).on("keypress", "input", function(e) { if(e.keyCode == 13) { e.preventDefault(); } });

    // Add handlers for the add item buttons
    $("#addCourseButton").click(function(e) { e.preventDefault(); addCourse(); });
    $(".addItemButton").click(function(e) { e.preventDefault(); addNonCourseItem($(this)); });

    // Add change handlers for the course fields
    $(document).on("blur", ".courseField", function() { getCourseOptions($(this)); });
    $(document).on("keypress", ".courseField", function(e) {
       if(e.keyCode == 13) { getCourseOptions($(this)); }
    });

    // Add handler for course option expanders
    $(document).on("click", ".courseOptionsExpander", function(e) {
        e.preventDefault();
        var list = $(this).parent().parent().find(".list");
        if(!list.is(":visible")) {
            $(this).html("-");
            list.slideDown();
        } else {
            $(this).html("+");
            list.slideUp();
        }
    });

    // Add handler to the show schedules button
    $("#showSchedulesButton").click(function(e) { e.preventDefault(); showSchedules(); });
});

// @TODO: save the schedule data between page loads?

/**
 * Called when the Add Course button is clicked to add an additional slot for
 * a course item. This works by cloning the last course options field
 */
function addCourse() {
    // First things first. Clone the last courseField
    var lastCourse = $(".courseRow").last();
    var newCourse = lastCourse.clone();

    // Increment the count of courses
    var courseCount = $("#courseCount");
    var count = parseInt(courseCount.val()) + 1;
    courseCount.val(count);

    // Change the name of the new course to reflect the incremented number
    var newInput = newCourse.find("input");
    newInput.attr("name", "courses" + count);
    newInput.attr("id", "courses" + count);
    newInput.val("");

    var newTitle = newCourse.find("label");
    newTitle.attr("for", "courses" + count);
    newTitle.html("Course " + count);

    // Clean out the course options
    var newCourseOpts = newCourse.find(".courseOptions");
    newCourseOpts.empty();
    newCourseOpts.removeClass("courseOptsError");

    // Append the new field to the end of the list of courses
    $("#scheduleCourses").append(newCourse);
}

/**
 * Called when a the Add Item button is clicked to add an additional row for
 * a non-course item or no course time. This works via cloning the last row
 * of the table.
 * @param   button  jQuery  A jQuery object for the button that was clicked
 */
function addNonCourseItem(button) {
    var parent = button.parent();

    // Increment the non course count
    var countInput = parent.find(".itemCount");
    var count = parseInt(countInput.val()) + 1;
    countInput.val(count);

    // Grab the last row from the table of non-course items
    var table   = parent.next();
    var lastRow = table.find("tr").last();

    // Clone it
    var newRow = lastRow.clone();

    // Add a time picker
    newRow.find(".startTimePicker").timepicker({scrollDefaultTime: "08:00"});

    // Increment the id/name fields for each input, reset them while we're at it
    newRow.find("input").each(function() {
        var element = $(this);

        // Grab the name, split it, increment the numerical portion
        // parts[3] is the [] for sun-sat checkboxes.
        var parts = element.attr("name").match(/(\D+)(\d+)(\[\])?$/);
        element.attr("name", parts[1] + count + ((parts[3]) ? parts[3] : ""));

        // Repeat for the id
        parts = element.attr("id").match(/(\D+)(\d+)$/);
        element.attr("id", parts[1] + count);

        // Depending on the type, reset the value or selected value
        if(element.attr("type") == "checkbox") {
            element.attr("checked", false);
        } else {
            element.val("");
        }
    });

    // Insert the row into the table
    table.append(newRow);
}

function collapseErrors() {
	// Grab the errorDiv and slide up everything
	$('#errorContents').slideUp('slow');
	
	// Edit the control for hiding/showing
    var errorControl = $("#errorControl");
	errorControl.val("Expand");
	errorControl.attr("onClick", "expandErrors();");
}

function collapseForm() {
	// Scroll up to the top of the page (a workaround for tiny screens)
	$("html, body").animate({ scrollTop: 0 }, "fast");


	// Grab the schedule form div and slide it up
	$('.scheduleForm').each(function(k, v) {
		$(v).slideUp('slow');
	});

	// Add a control for expanding re-expanding the form
	var control = $("<div>");
	control.attr("id", "formControl");
	control.addClass("scheduleForm");
	control.addClass("subheader");
	
	var header = $("<h2>");
	header.html("Schedule Parameters");
	control.append(header);
	
	var button = $("<input>");
	button.attr("type", "button");
	button.attr("value", "Expand");
	button.attr("onClick", "expandForm();");
	control.append(button);

	control.insertBefore(".scheduleForm:first");
}

function drawCourse(parent, course, startDay, endDay, startTime, endTime, colorNum, print, hiddenCourses) {
	// If the course is online OR there aren't any times set, don't even bother
	if(course.online || course.times == undefined) {
		return;
	}

	// Draw the time divs of the course
	for(t = 0; t < course.times.length; t++) {
		// Make it easier for the developer
		var time = course.times[t];
		
		// Skip times that aren't part of the displayed days
		if(time.day < startDay || time.day > endDay) {
			if($.inArray(course.courseNum, hiddenCourses) == -1) {
				hiddenCourses.push(course.courseNum);
			}
			continue;
		}

		// Skip times that aren't part of the displayed hours
		if(time.start < startTime || time.start > endTime || time.end > endTime) {
			// Shorten up the boxes of times that extend into
			// the visible spectrum
			if(time.start < startTime && time.end > startTime) {
				time.start = startTime;
				time.shorten = "top";
			} else if(time.end > endTime && time.start < endTime) {
				time.end = endTime;
				time.shorten = "bottom";
			} else {
				// The course is completely hidden
				if($.inArray(course.courseNum, hiddenCourses) == -1) {
					hiddenCourses.push(course.courseNum);
				}
				continue;
			}
		}

		// Add a div for the time
		var timeDiv = $("<div>");
        timeDiv.addClass("day" + (time.day - startDay));
        timeDiv.addClass("timeContainer");

		// Shade the time slot if it's a printout
		if(print) {
			timeDiv.addClass("color" + colorNum);
		}
		
		// Calculate the height
		var timeHeight = parseInt(time.end) - parseInt(time.start);
		timeHeight = timeHeight / 30;
		timeHeight = Math.ceil(timeHeight);
		timeHeight = (timeHeight * 20) - 1;

		// Calculate the top offset
		var timeTop = parseInt(time.start) - startTime;
		timeTop = timeTop / 30;
		timeTop = Math.floor(timeTop);
		timeTop = timeTop * 20;
		timeTop += 20;					// Offset for the header

		// Apply the styles
		timeDiv.css("height", timeHeight + "px");
		timeDiv.css("top", timeTop + "px");
		timeDiv.addClass("color" + colorNum);

		// Add the course information
		var header = $("<h4>").html(course.title)
			.appendTo(timeDiv);

		if(course.courseNum != "non") {
			var courseInfo = $("<div>");
			if(timeHeight < 40) {
                // Shorten the header for < 2 hr courses
				header.addClass("shortHeader");
			}

            // Pre generate the building info for easy insertion
            var building = ($("#buildingStyle").val()=='code') ? time.bldg.code : time.bldg.number;
            courseInfo.html(courseInfo.html() + building + "-" + time.room);
            courseInfo.appendTo(timeDiv);

            // Special case for what to print in short times (ie, all of semester classes)
            if(print) {
                courseInfo.html(building + "-" + time.room + "<br/>");
                if(timeHeight >= 40) {
                    courseInfo.html(courseInfo.html() + course.courseNum + "<br/>");
                }
                if(timeHeight >= 60) {
                    courseInfo.html(courseInfo.html() + course.instructor + "<br/>");
                }
            } else {
                // Add all course number/instructor info (it will be hidden if it overflows)
                courseInfo.html(course.courseNum + "<br />");
                courseInfo.html(courseInfo.html() + course.instructor + "<br />");

                // Add building info
			    courseInfo.html(courseInfo.html() + building + "-" + time.room);
			    courseInfo.appendTo(timeDiv);
            }
		}
		if(time.shorten == "top") {
			var curHeight = timeDiv.css("height");
			curHeight = curHeight.substring(0, curHeight.length - 2); 
			var newHeight = curHeight - 1;
			timeDiv.css("height", newHeight + "px");
			timeDiv.addClass("shortenTop");
		}
		if(time.shorten == "bottom") {
			var curHeight = timeDiv.css("height");
			curHeight = curHeight.substring(0, curHeight.length - 2); 
			var newHeight = curHeight - 1;
			timeDiv.css("height", newHeight + "px");
			timeDiv.addClass("shortenBottom");
		}
		
		// Add the time to the schedule
		timeDiv.appendTo(parent);
	}
}

function drawPage(pageNum, print) {
	// Clear out the currently displayed schedules
	$(".schedSupaWrapper").remove();

	// Calculate the subset of schedules to display
	var startIndex = pageNum * SCHEDPERPAGE;
	var endIndex   = startIndex + SCHEDPERPAGE;
	if(endIndex >= schedules.length) { endIndex = schedules.length; }
	var schedSubset = schedules.slice(startIndex, endIndex);

	// Draw the new schedules
	for(var s = 0; s < schedSubset.length; s++) {
		// Set the unique index of the schedule
		var schedId = s + startIndex;

		// Create a 'super wrapper' for containing the URL div, schedule, and
		// notes divs
		var schedSupa = $("<div class='schedSupaWrapper'>");
		schedSupa.attr("id", "sched" + schedId);

		// Create a 'no-hide' wrapper for the schedule to show controls
		var schedWrap = $("<div class='scheduleWrapper'>");
		schedWrap.css("height", schedHeight + "px");
		schedWrap.css("width", schedWidth + "px");
		schedWrap.appendTo(schedSupa);

		// Create a div for the schedule and it's components
		var sched = $("<div class='schedule'>");
		sched.append($("<img src='img/grid.png'>"));
		sched.css("height", schedHeight + "px");
		sched.css("width", schedWidth + "px");
		sched.appendTo(schedWrap);

		// Add the headers to the schedules
		drawScheduleHeaders(sched, startday, endday, starttime, endtime);

		// Iterate over each course and draw them
		var onlineCourses = [];
		var hiddenCourses = [];
		for(c = 0; c < schedSubset[s].length; c++) {
			var colorNum = c % 4;

			// If we found an online course, don't draw it
			if(schedSubset[s][c].online) {
				onlineCourses.push(schedSubset[s][c].courseNum);
			} else {
				drawCourse(sched, schedSubset[s][c], startday, endday, starttime, endtime, colorNum, print, hiddenCourses);
			}
		}

        // Do we need a notes box?
        if(onlineCourses.length || hiddenCourses.length) {
            // Create a notes div
            var notesDiv = $("<div>");
            notesDiv.addClass("schedNotes");
            notesDiv.css("width", schedWidth + "px");

            // Do we have online courses?
            if(onlineCourses.length) {
                // Create the beginning of the notice
                var onlineNotes = $("<p>");
                onlineNotes.html("This schedule contains online courses");
                onlineNotes.prepend($("<span style='font-weight:bold'>Notice: </span>"));

                // Add each course number to the notice
                for(var ol = 0; ol < onlineCourses.length; ol++) {
                    onlineNotes.html(onlineNotes.html() + " " + onlineCourses[ol]);
                }

                // Add it to the notes div
                onlineNotes.appendTo(notesDiv);
            }

            // Do we have courses that are obscured?
            if(hiddenCourses.length) {
                // Create the beginning of the notice
                var hiddenNotes = $("<p>");
                hiddenNotes.html("This schedule does not show");
                hiddenNotes.prepend($("<span style='font-weight:bold'>Notice: </span>"));

                // Add each item to the notice
                for(var hi = 0; hi < hiddenCourses.length; hi++) {
                    hiddenNotes.html(hiddenNotes.html() + " " + hiddenCourses[ol]);
                }

                // Add it to the notes div
                hiddenNotes.appendTo(notesDiv);
            }

            // Put the notes div in the schedule wrapper
            notesDiv.appendTo(schedSupa);
        }

		if(!print) {
		// Create a control box
		var schedControl = $("<div>").addClass("scheduleControl");
		var saveForm = $("<form>").attr("action", "schedule.php")
						.attr("method", "POST")
						.appendTo(schedControl);
		$("<input>").attr("type", "hidden")
			.attr("name", "schedule")
			.val(JSON.stringify(schedSubset[s]))
			.appendTo(saveForm);
		$("<input>").attr("type", "hidden")
			.attr("name", "url")
			.val("none")
			.appendTo(saveForm);
		$("<input>").attr("type", "hidden")
			.attr("name", "scheduleId")
			.val("sched" + schedId)
			.appendTo(saveForm);
		$("<input type='button' value='Print Schedule'>")
			.click(function() { printSchedule($(this)); })
			.appendTo(saveForm);
		$("<input type='button' value='Save Schedule'>")
			.click(function() { saveSchedule($(this)); })
			.appendTo(saveForm);
		$("<input type='button' value='Download iCal'>")
			.click(function() { icalSchedule($(this)); })
			.appendTo(saveForm);
		$("<button type='button'>")
			.html("<img src='img/share_facebook.png' /> Share Facebook")
			.click(function() { shareFacebook($(this)); })
			.appendTo(saveForm);
		$("<button type='button'>")
			.html("<img src='img/share_google.png' /> Share Google+")
			.click(function() { shareGoogle($(this)); })
			.appendTo(saveForm);
		$("<button type='button'>")
			.html("<img src='img/share_twitter.png' /> Share Twitter")
			.click(function() { shareTwitter($(this)); })
			.appendTo(saveForm);
		schedControl.appendTo(schedWrap);
		}

		// Add the schedule to the schedules
        var schedulePagination = $(".schedulePagination");
		if(schedulePagination.length) {
			schedSupa.insertBefore(schedulePagination.last());
		} else {
			$('#schedules').append(schedSupa);
		}
	}
}

function drawScheduleHeaders(parent, startDay, endDay, startTime, endTime) {
	// Draw the days of the week
	// It falls through the cases until the end day is reached. Pretty snazzy!
	switch(startDay) {
		case 0:
			$("<div>").addClass("weekday")
			    .addClass("day0")	// Will be skipped if start day > 0
				.html("Sunday")
				.appendTo(parent);
			if(endDay == 0) { break; }
		case 1:
			$("<div>").addClass("weekday")
				.addClass("day" + String(1 - startDay))
				.html("Monday")
				.appendTo(parent);
			if(endDay == 1) { break; }
		case 2:
			$("<div>").addClass("weekday")
				.addClass("day" + String(2 - startDay))
				.html("Tuesday")
				.appendTo(parent);
			if(endDay == 2) { break; }
		case 3:
			$("<div>").addClass("weekday")
				.addClass("day" + String(3 - startDay))
				.html("Wednesday")
				.appendTo(parent);
			if(endDay == 3) { break; }
		case 4:
			$("<div>").addClass("weekday")
				.addClass("day" + String(4 - startDay))
				.html("Thursday")
				.appendTo(parent);
			if(endDay == 4) { break; }
		case 5:
			$("<div>").addClass("weekday")
				.addClass("day" + String(5 - startDay))
				.html("Friday")
				.appendTo(parent);
			if(endDay == 5) { break; }
		case 6:
			$("<div>").addClass("weekday")
				.addClass("day" + String(6 - startDay))
				.html("Saturday")
				.appendTo(parent);
			if(endDay == 6) { break; }
		    break;
	}

	// Draw all the times of the day
	// We do this with a for loop
	for(var time = startTime; time < endTime; time += 30) {
		// Calculate the label
		var hourLabel = Math.floor(time / 60);
		if(hourLabel > 12) { hourLabel -= 12; }
		else if(hourLabel == 0) { hourLabel = 12; }

		var minuteLabel = time % 60;
		if(minuteLabel == 0) { minuteLabel = "00"; }

		if(time >= 720) { ap = "pm"; } else { ap = "am"; }

		var timeLabel = String(hourLabel) + ':' + String(minuteLabel) + " " + ap;
		
		// Draw the time Div
		$("<div>").addClass("daytime")
			.css("top", ((Math.floor((time - startTime) / 30) * 20) + 20) + "px")
			.html(timeLabel)
			.appendTo(parent);
	}
}

function expandErrors() {
	// Unhide all the error Div
	$('#errorContents').slideDown('slow');

	// Change the control for hiding/showing
    var errorControl = $("#errorControl");
	errorControl.val("Collapse");
	errorControl.attr("onClick", "collapseErrors();");
}

function expandForm() {
	// Hide and delete the control
	$('#formControl').remove();

	// Unhide all the form divs
	$('.scheduleForm').each(function(k, v) {
		$(v).slideDown('slow');
	});
}

/**
 * Retrieves the course options that match the input's value.
 * @param field jQuery  The field to retrieve course options for
 */
function getCourseOptions(field) {
    // Store some handy points in the DOM relative to the field
    var courseOptions = field.parent().parent().children(".courseRowOptions");

	// If no course number was provided, remove the options
	if(field.val() == "") {
		courseOptions.empty();
		return;
	}

	// It wasn't blank! Let's send it to the ajaxHandler
    var options = {
        'action'     : 'getCourseOpts',
        'course'     : field.val(),
        'term'       : $('#term').val(),
        'ignoreFull' : $('#ignoreFull').prop('checked')
    };
	$.post("./js/scheduleAjax.php", options, function(d) {
        if(d.error != null && d.error != undefined) {
            // Bomb out on an error
            courseOptions.empty();
            courseOptions.addClass("courseOptionsError");
            courseOptions.html(d.msg);
            return;
        }

        // Empty out any currently showing courses
        courseOptions.empty();
        courseOptions.removeClass("courseOptionsError");

        // Load the template
        var template = fetchTemplate("js/templates/generateCourseOptions.html");
        if(template == null) {
            courseOptions.addClass("courseOptionsError");
            courseOptions.html("Error: Failed to load course options template.");
            return;
        }

        // Condense the times into a single string for output
        for(var c = 0; c < d.length; ++c) {
            var times = [];
            var course = d[c];

            // Iterate over the times for the course
            if(course.times == undefined) { continue; }
            for(var e = 0; e < course.times.length; ++e) {
                // Search the existing list of times to see if a match exists
                var found = false;
                var time = course.times[e];
                for(var f = 0; f < times.length; ++f) {
                    if(times[f].start == time.start && times[f].end == time.end) {
                        found = f;
                    }
                }

                // If a match was found, add the day to it, otherwise add a new time
                if(found !== false) {
                    times[found].days += ", " + translateDay(time.day);
                } else {
                    times.push({
                        start: time.start,
                        end:   time.end,
                        days:  translateDay(time.day)
                    });
                }
            }

            // Replace the current list of times with the newly constructed one
            d[c].times = times;
        }

        // Setup the parameters to the template
        var params = {
            count: d.length,
            courses: d,
            courseField: field.attr('id')
        };

        // Apply the transform
        courseOptions.html(template(params));
	});
}

function getNextPage() {
	// If we're at the last page, hide the next button and quit
	if(curPage + 1 == pages) {
		$(".nextbutton").each(function(k,v) {
			$(v).hide();
			});
		return;
	}

	// Now we need to draw the next set of schedules
	drawPage(curPage + 1, false);

	// Set the current page number and hide the next button if need be, show prev button
	curPage++;
	$(".curpage").each(function(k,v) {
		$(v).html(curPage + 1);
		});
	if(curPage + 1 == pages) {
		$(".nextbutton").each(function(k,v) {
			$(v).hide();
			});
	}
	$(".prevbutton").each(function(k,v) {
		$(v).show();
		});

	// Scroll up to the top of the page
	$("html, body").animate({ scrollTop: 0 }, "fast");
}

function getPrevPage() {
	// If we're at the first page, hide the previous button and quit
	if(curPage == 0) {
		$(".prevbutton").each(function(k,v) {
			$(v).hide();
			});
		return;
	}

	// Draw the previous set of schedules
	drawPage(curPage - 1, false);

	// Set the current page number, hide previous button if needed, show next button
	curPage--;
	$(".curpage").each(function(k,v) {
		$(v).html(curPage + 1);
		});
	if(curPage == 0) {
		$(".prevbutton").each(function(k,v) {
			$(v).hide();
			});
	}
	$(".nextbutton").each(function(k,v) {
		$(v).show();
		});

	// Scroll up to the top of the page
	$("html, body").animate({ scrollTop: 0 }, "fast");
}

function getScheduleUrl(button) {
	// Do we already have a url stored?
	var urlInput = $(button.parent().children()[1]);
	if(urlInput.val() == "none") {
        // Grab the id of the schedule
        var scheduleId = $(button.parent().children()[2]).val();

		// Grab the field for the json
		var jsonObj = $(button.parent().children()[0]).val();
		var jsonModified = {
				"startday":  $("#scheduleStartDay").val(),
				"endday":    $("#scheduleEndDay").val(),
				"starttime": $("#scheduleStart").val(),
				"endtime":   $("#scheduleEnd").val(),
				"schedule":  eval(jsonObj),
				"building":  $("#buildingStyle").val(),
				"term":      $("#term").val()	// This /could/ be incorrect... just sayin
				};
		// We don't have a url already, so get one!
		$.post("./js/scheduleAjax.php", {action: "saveSchedule", data: JSON.stringify(jsonModified)}, function(data) {
			// Error checking. Display a error. URL will remain NONE on error.
			if(data.error != null && data.error != undefined) {
                // Get the URL div
                var scheduleId = $(button.parent().children()[2]).val();
                var urlDiv = $("#" + scheduleId + "Url");
                if(urlDiv.length) {
                    // URL Div exists, so empty it
                    urlDiv.empty();
                    urlDiv.removeClass();
                } else {
                    urlDiv = $("<div>");
                    urlDiv.attr("id", scheduleId + "Url");
                    urlDiv.css("width", $(button.parent().parent().parent()).css("width"));
                }

                // Add the appropriate class to the error div
                urlDiv.addClass("schedUrlError");

                // Add the error message
                urlDiv.append("<p style='font-weight:bold;'>An Error Occurred</p>");
                urlDiv.append("<p>We are unable to store your schedule at this time.</p>");

                // Add it to the schedule
                var schedule = $("#" + scheduleId);
                urlDiv.prependTo(schedule);

				return false;
			}
			
			// Store the url
			var savedUrl = data.url;
			urlInput.val(savedUrl);
		});
		
		// Should be asynch. So this SHOULD be ok.
		return urlInput.val();
	} else {
		// We already have a url, so return it
		return urlInput.val();
	}
}

function icalSchedule(button) {
	// Get a schedule url
	var url = getScheduleUrl(button);

    // Error checking
    if(!url || url == 'none') {
        return;
    }
	
	// Add the magic sauce and redirect
	url += "&mode=ical";
	window.location = url;
}

function printSchedule(button) {
	// We need a schedule json object
	var jsonobj = eval($(button.parent().children()[0]).val());
	var json = {
		courses: [jsonobj],
		startTime: starttime,
		endTime: endtime,
		startDay: startday,
		endDay: endday,
		term: $("#term").val(),
        bldgStyle: $("#buildingStyle").val()
		};

	// Store the schedule in local storage
	window.sessionStorage.setItem("scheduleJson", JSON.stringify(json));

	// Open up a new window
	window.prin=window.prin||{};
	var D=schedWidth + 40, A=450, C=screen.height, B=screen.width, H=Math.round((B/2)-(D/2)), G=0;
	window.prin.prinWin=window.open(
		"schedule.php?mode=print",
		"",
		'left='+H+',top='+G+',width='+D+',height='+A+',personalbar=0,toolbar=0,scrollbars=1,resizable=1'
		);
}

/**
 * Called when the course options need to all be refreshed. The best example
 * of when this needs to happen is when the term is changed.
 */
function refreshCourses() {
	// Iterate over the course slots and refresh each one
    $(".courseField").each(function() {
       if($(this).val() != "") {
           getCourseOptions($(this));
       }
    });
}

function saveSchedule(button) {
	// We need a schedule url
	url = getScheduleUrl(button);

	// Error checking
	if(!url || url == 'none') {
        return;
	}

    // Grab the schedule we're adding this to
    var scheduleId = $(button.parent().children()[2]).val();
    var schedule = $("#" + scheduleId);

    // Disable the button
    $(button).attr("disabled", "disabled");

    // Draw the url div
    var urlDiv = $("<div>");
    urlDiv.addClass("schedUrl");
    urlDiv.css("width", $(button.parent().parent().parent()).css("width"));
    urlDiv.append("<p>This schedule can be accessed at: <a href='" + url + "'>" + url + "</a></p>");
    urlDiv.append("<p class='disclaimer'>This schedule will be removed after 3 months of inactivity</p>");
	urlDiv.prependTo(schedule);
	urlDiv.slideDown();
}
	
function shareFacebook(button) {
	// We need a schedule url
	var url = getScheduleUrl(button);

    // Error checking
    if(!url || url == 'none') {
        return;
    }

	// Run the code.
	window.faceb=window.faceb||{};
	var D=550, A=450, C=screen.height, B=screen.width, H=Math.round((B/2)-(D/2)),G=0;
	window.faceb.shareWin=window.open(
		'http://www.facebook.com/sharer.php?u=' + escape(url),
		'Share on Facebook',
		'left='+H+',top='+G+',width='+D+',height='+A+',personalbar=0,toolbar=0,scrollbars=1,resizable=1'
		);
}

function shareGoogle(button) {
	// We need a schedule url
	var url = getScheduleUrl(button);

    // Error checking
    if(!url || url == 'none') {
        return;
    }

	// Run the code.
	window.googl=window.googl||{};
	var D=550, A=450, C=screen.height, B=screen.width, H=Math.round((B/2)-(D/2)),G=0;
	window.googl.shareWin=window.open(
		'https://plus.google.com/share?ur\l='+encodeURIComponent(url),
		'Share on Google+',
		'left='+H+',top='+G+',width='+D+',height='+A+',personalbar=0,toolbar=0,scrollbars=1,resizable=1'
		);
}

function shareTwitter(button) {
	// We need a schedule url
	var url = getScheduleUrl(button);

    // Error checking
    if(!url || url == 'none') {
        return;
    }

	// Run the code
	window.twttr=window.twttr||{};
	var D=550,A=450,C=screen.height,B=screen.width,H=Math.round((B/2)-(D/2)),G=0;
	if(C<A){
		G=Math.round((C/2)-(A/2))
	}
	window.twttr.shareWin=window.open(
		'http://twitter.com/share?url=' + escape(url) + "&text=My%20Class%20Schedule",
		'Share on Twitter',
		'left='+H+',top='+G+',width='+D+',height='+A+',personalbar=0,toolbar=0,scrollbars=1,resizable=1'
		);
}

function showSchedules() {
	// Hide the form and show the expand bar
	collapseForm();

	// Serialize the form and store it if it changed
    var form = $("#scheduleForm");
	if(serialForm != form.serialize()) {
		serialForm = form.serialize();
		
		// Clear out the schedules and errors
		$("#schedules").find("> :not(:first-child)").remove();

		// Now we need to submit all the data to the ajax caller
		$.post("./js/scheduleAjax.php", $('#scheduleForm').serialize(), function(data) {
            var scheduleDiv = $("#schedules");

			// If there was a single, non-recoverable error, show it and die
			if(data.error != null && data.error != undefined) {
				$("<div>").attr("id", "errorDiv")
						.addClass("scheduleError")
						.html("<b>Fatal Error: </b>" + data.msg)
						.appendTo(scheduleDiv);
				scheduleDiv.slideDown();
				return;
			}

			// Store the data for pagination later
			schedules = data.schedules;

			// If we're showing all schedules on one page, then do that
            var schedPerPage = $("#schedPerPage");
			if(schedPerPage.val() == 'all') {
				SCHEDPERPAGE = schedules.length;
			} else {
				SCHEDPERPAGE = parseInt(schedPerPage.val());
			}
			
			// How many pages of schedules are there
			pages = Math.ceil(schedules.length / SCHEDPERPAGE);
			curPage = 0;

			// Generate a subset of the schedules for display
			data.schedules = schedules.slice(0, SCHEDPERPAGE);

			// If there are no matching schedules, display an error
			if(data.schedules == undefined || data.schedules == null || data.schedules.length == 0) {
				var errorDiv = $("<div id='errorDiv' class='scheduleError'>").html("There are no matching schedules!");
				scheduleDiv.append(errorDiv);
				scheduleDiv.slideDown();
				return;
			}

			// If there were recoverable errors, show them
			// NOTE: the php side determines whether to send errors based on verbose value
			if(data.errors != null && data.errors != undefined) {
				errorDiv = $("<div id='errorDiv' class='scheduleWarning'>");
				var errorHTML = "<div class='subheader'><h3>Schedule Generator Warnings</h3><input id='errorControl' type='button' value='Collapse' onClick='collapseErrors();' /></div>";
				errorHTML = "<div class='subheader'><h3>Schedule Generator Warnings</h3><input id='errorControl' type='button' value='Collapse' onClick='collapseErrors();' /></div>";
				errorHTML += "<div id='errorContents'>";
				for(i = 0; i < data.errors.length; i++) {
					errorHTML += data.errors[i].msg + "<br />";
				}
				errorHTML += "</div>";
				errorDiv.html(errorHTML);
				$('#schedules').append(errorDiv);
			}

			// Grab the advanced options for the schedule
			startday  = parseInt($("#scheduleStartDay").val());
			endday    = parseInt($("#scheduleEndDay").val());
			starttime = parseInt($("#scheduleStart").val());
			endtime   = parseInt($("#scheduleEnd").val());

			// Determine the height and width of the schedule based on start/end time/day
			schedHeight = (Math.floor((endtime - starttime) / 30) * 20) + 20;
			schedWidth  = ((endday - startday) * 100) + 200;		// +200 b/c we always show at least ONE day

			// Now we draw the schedules
			drawPage(0, false);

			// Add next/previous page controls
			var pagination = $("<div>").addClass("schedulePagination");
			var pageinfo = schedules.length + " Schedules Generated (Page <span class='curpage'>" + (curPage + 1) + "</span> of " + pages + ")";
			pagination.html(pageinfo);
			if(pages > 1) {
				var prev = $("<input>").attr("type", "button")
							.attr("value", "<- Previous")
							.attr("onClick", "getPrevPage();")
							.addClass("prevbutton")
							.css("display", "none");
				var next = $("<input>").attr("type", "button")
							.attr("value", "Next ->")
							.attr("onClick", "getNextPage();")
							.addClass("nextbutton");
				pagination.append(prev);
				pagination.append(next);
			}
			pagination.insertAfter('#matchingSchedules');
			pagination2 = pagination.clone();
			pagination2.appendTo('#schedules');

			// Unhide the schedules page
			scheduleDiv.slideDown();
		}).error( function() {
            var scheduleDiv = $("#schedules");
			var errorDiv = $("<div>");
			errorDiv.attr("id", "errorDiv");
			errorDiv.addClass("scheduleError");
			errorDiv.html("Fatal Error: An internal server error occurred");
			errorDiv.appendTo(scheduleDiv);
			scheduleDiv.slideDown();
		});
	}
}
