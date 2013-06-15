////////////////////////////////////////////////////////////////////////////
// SCHEDULE BUILDER JAVASCRIPT FUNCTIONS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/browse.js
// @descrip	Functions for browsing the course list in a fancy way
////////////////////////////////////////////////////////////////////////////

// Register the on clicks for the schools
$(document).ready( function() {
	// Add handlers to the 
	$(".school > button").each(function(k, v) {
		$(v).click(function() {
			schoolOnExpand($(v));
			return false;			// Avoid following the clicks
			});
		});
	});

function courseOnCollapse(obj) {
	obj.html("+");
	
	// Get the parent and hide all it's children courses
	var p = obj.parent();
	p.children().last().slideUp();
	obj.next().next().slideUp();

	// Reset the click function
	obj.unbind("click");
	obj.click(function() { courseOnExpand($(this)); return false; });
}

function courseOnExpand(obj) {
	// Set the clicked obj to a -
	obj.html("-");
	obj.unbind("click");
	obj.click(function() { courseOnCollapse($(this)); return false; });

	// Get the parent and the input field
	var p = obj.parent();
	var input  = obj.next();

	// Expand the course description
	input.next().slideDown();
	
	// If the sections already exist, then don't do the post request
	if(p.children().last().hasClass("subDivision")) {
		p.children().last().slideDown();
		return;
	}

	// If there was an error, remove the error and redo the post request
	if(p.children().last().hasClass("error")) {
		p.children().last().remove();
	}

	// Creat a div for storing all the sections
	var box = $("<div>").addClass("subDivision")
			.appendTo(p);

	// Do an ajax call for the sections of the course
	$.post("js/browseAjax.php", {"action": "getSections", "course": input.val()}, function(data) {
		// Check for errors
		if(data.error != null && data.error != undefined) {
			box.addClass("error")
				.html("Sorry! An error occurred!<br />" + data.msg);
			box.slideDown();
			return;
		}
		
		// No Errors!! No we need to add a div for each section
		for(var i=0; i < data.sections.length; i++) {
            var section = data.sections[i];

            // Department code for semesters, department number for quarters
            var term = $("#termSelect").val().match(/=(\d{5})/)[1];
            var dept = (term > 20130) ? section.department.code : section.department.number;

			var div = $("<div>").addClass("item")
					.html("<b>" + dept + "-" + section.course + "-" + section.section + "</b>"
						+ " : " + section.title + " with " + section.instructor + " ");

            // Process the locations of the section and determine if the
            // section is off campus
            var locations = "";
            var offsite = false;
            for(j=0; j < section.times.length; ++j) {
                // Add code for the time
                locations += section.times[j].day + " " + section.times[j].start + " - " + section.times[j].end
                    + " " + section.times[j].building.code
                    + "(" + section.times[j].building.number + ")"
                    + "-" + section.times[j].room + "<br />";

                if(section.times[j].building.offSite) {
                    offsite = true;
                }
            }

			// If the section is online, mark it as such
			if(section.online) {
				div.append($("<span class='online'>ONLINE</span>"));
			}
            if(offsite) {
                div.append($("<span class='online'>OFF-CAMPUS</span>"));
            }

			// Add a paragraph for the current and maximum enrollment
			$("<p>").html("Course Enrollment: " + data.sections[i].curenroll + " out of " + data.sections[i].maxenroll)
				.appendTo(div);

			// Add a paragraph for each meeting time
			var times = $("<p>");
            times.html(times.html() + locations);
			times.appendTo(div);

			div.appendTo(box);
		}

		box.slideDown();
	});
}

function departmentOnCollapse(obj) {
	obj.html("+");
	
	// Get the parent and hide all it's children courses
	var p = obj.parent();
	p.children().last().slideUp()

	// Reset the click function
	obj.unbind("click");
	obj.click(function() { departmentOnExpand($(this)); return false; });
}

function departmentOnExpand(obj) {
	// Set the clicked obj to a -
	obj.html("-");
	obj.unbind("click");
	obj.click(function() { departmentOnCollapse($(this)); return false; });

	// Get the parent and the input field
	var p       = obj.parent();
	var input   = obj.next();
	var term = $("#termSelect").val().match(/=(\d{5})/)[1];

	// If the courses already exist, then don't do the post request
	if(p.children().last().hasClass("subDivision")) {
		p.children().last().slideDown();
		return;
	}

	// If there was an error, remove the error and redo the post request
	if(p.children().last().hasClass("error")) {
		p.children().last().remove();
	}

	// Create a div for storing all the courses
	var box = $("<div>").addClass("subDivision")
			.appendTo(p);

	// Do an ajax call for the courses within the department
	$.post("js/browseAjax.php", {"action": "getCourses", "department": input.val(), "term": term}, function(data) {
		// Check for errors
		if(data.error != null && data.error != undefined) {
			box.addClass("error")
				.html("Sorry! An error occurred!<br />" + data.msg);
			box.slideDown();
			return;
		} else if(data.courses.length == 0) {
            // There were no matching courses
            box.addClass("error");
            box.html("Sorry! There are no courses in this department for this term.");
            box.slideDown();
            return;
        }

		// No errors! Now we need to add a div for each course
		for(i=0; i < data.courses.length; i++) {
            var course = data.courses[i];

            // Create a dive for the course
            var div = $("<div>");
            div.addClass("item");

            // The base text for the div is the course number and the title
            if(term > 20130) {
                div.html(course.department.code + "-" + course.course + " - " + course.title);
            } else {
                div.html(course.department.number + "-" + course.course + " - " + course.title);
            }

            // Add description information
			$("<p>").html(data.courses[i].description)
						.addClass("courseDescription")
						.appendTo(div);
			$("<input>").attr("type", "hidden")
						.val(data.courses[i].id)
						.prependTo(div);
			$("<button>").html("+")
						.click(function() { courseOnExpand($(this)); return false; })
						.prependTo(div);
			div.appendTo(box);
		}

		// Expand the Box
		box.slideDown();
	});
}

function schoolOnCollapse(obj) {
	obj.html("+");
	
	// Get the parent and hide all it's children	
	var p = obj.parent();
	p.children().last().slideUp();

	// Reset the click mechanism
	obj.unbind("click");
	obj.click(function() {schoolOnExpand($(this)); return false; });
}

function schoolOnExpand(obj) {
	// Set the clicked obj to a -
	obj.html("-");
	obj.unbind("click");
	obj.click(function() {schoolOnCollapse($(this)); return false;});

	// Get the parent and the input field of this school
	var p      = obj.parent();
	var input  = obj.next();

	// Snag the quarter for use in the query and determining whether to show
	// department codes
	var term = $("#termSelect").val().match(/=(\d{5})/)[1];

	// If the department already exists, then don't do the post resquest
	if(p.children().last().hasClass("subDivision")) {
		p.children().last().slideDown();
		return;
	}

	// If there was an error, remove the departments and redo the post request
	if(p.children().last().hasClass("error")) {
		p.children().last().remove();
	}
	
	// Create a div for storing all the departments
	var box    = $("<div>").addClass("subDivision")
			.appendTo(p);

	// Do an ajax call for the departments within this school
	$.post("js/browseAjax.php", {action: 'getDepartments', school: input.val(), term:term }, function(data) {
		// Check for errors
		if(data.error != null && data.error != undefined) {
			box.addClass("error")
				.html("Sorry! An error occurred!<br/>" + data.msg);
			box.slideDown();
			return;
		}

		// No errors! Now we need to add a div for each department
		for(i=0; i < data.departments.length; i++) {
			var code;
			if(term > 20130) {
                code = data.departments[i].code;
				code += (data.departments[i].number) ? " (" + data.departments[i].number + ")" : "";
			} else {
				code = data.departments[i].number;
			}

			div = $("<div>").addClass("item")
					.html(" " + code + " - " + data.departments[i].title);
			$("<input>").attr("type", "hidden")
					.val(data.departments[i].id)
					.prependTo(div);
			$("<button>").html("+")
					.click(function() { departmentOnExpand($(this)); return false; })
					.prependTo(div);
			div.appendTo(box);
		}

		// Expand the box
		box.slideDown();
	});
}
