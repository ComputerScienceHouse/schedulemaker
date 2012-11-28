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
		try {
			data = eval("(" + data + ")");
		} catch(e) {
			alert("An error occurred: the jSON is malformed");
			return;
		}

		// Check for errors
		if(data.error != null && data.error != undefined) {
			box.addClass("error")
				.html("Sorry! An error occurred!<br />" + data.msg);
			box.slideDown();
			return;
		}
		
		// No Errors!! No we need to add a div for each section
		for(i=0; i < data.sections.length; i++) {
			div = $("<div>").addClass("item")
					.html("<b>" + data.sections[i].department + "-" + data.sections[i].course + "-" + data.sections[i].section + "</b>"
						+ " : " + data.sections[i].title + " with " + data.sections[i].instructor + " ");
			
			// If the section is online, mark it as such
			if(data.sections[i].online) {
				div.append($("<span class='online'>ONLINE</span>"));
			}

			// Add a paragraph for the current and maximum enrollment
			$("<p>").html("Course Enrollment: " + data.sections[i].curenroll + " out of " + data.sections[i].maxenroll)
				.appendTo(div);

			// Add a paragraph for each meeting time
			var times = $("<p>");
			for(j=0; j < data.sections[i].times.length; j++) {
				times.html(times.html() +
					data.sections[i].times[j].day + " " + data.sections[i].times[j].start + " - " + data.sections[i].times[j].end
					+ " " + data.sections[i].times[j].building.code
					+ "(" + data.sections[i].times[j].building.number + ")"
					+ "-" + data.sections[i].times[j].room + "<br />");
			}
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
	var quarter = $("#quarterSelect").val().match(/quarter=(\d{5})/)[1];

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
	$.post("js/browseAjax.php", {"action": "getCourses", "department": input.val(), "quarter": quarter}, function(data) {
		try {
			data = eval("(" + data + ")");
		} catch(e) {
			alert("An error occurred: the resulting jSON is malformed.");
			return;
		}
		
		// Check for errors
		if(data.error != null && data.error != undefined) {
			box.addClass("error")
				.html("Sorry! An error occurred!<br />" + data.msg);
			box.slideDown();
			return;
		}

		// No errors! Now we need to add a div for each course
		for(i=0; i < data.courses.length; i++) {
			div = $("<div>").addClass("item")
						.html(" " + data.courses[i].department + "-" + data.courses[i].course + " - " + data.courses[i].title);
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
	var quarter = $("#quarterSelect").val().match(/=(\d{5})/)[1];

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
	$.post("js/browseAjax.php", {'action': 'getDepartments', 'school': input.val(), 'quarter':quarter }, function(data) {
		try {		
			data = eval("(" + data + ")");
		} catch(e) {
			alert("An error occurred: the resulting jSON is malformed.");
			return;
		}

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
			if(quarter > 20130) {
				code = (data.departments[i].code.length) ? " (" + data.departments[i].code + ")" : "";
			} else {
				code = "";
			}

			div = $("<div>").addClass("item")
					.html(" " + data.departments[i].id + code + " - " + data.departments[i].title);
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
