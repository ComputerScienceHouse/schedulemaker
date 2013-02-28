////////////////////////////////////////////////////////////////////////////
// COURSE ROULETTE JAVASCRIPT FUNCTIONS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/roulette.js
// @descrip	Functions for managing displaying and manipulating the course
//			roulette page.
////////////////////////////////////////////////////////////////////////////

// GLOBAL VARIABLES ////////////////////////////////////////////////////////
var schoolCache = null;

// When the document's ready, add handlers to necessary elements
$(document).ready(function() {
    // Add handler to roulette spinner button
    $("#spinButton").click(function(e) {
        e.preventDefault();
        spinRoulette();
    });

    // Add handler to the term selector
    $("#term").change(function(e) {
        e.preventDefault();
        termOnChange();
    });

    // Add handler to the school selector
    $("#college").change(function(e) {
        e.preventDefault();
        collegeOnChange();
    });

    // Load the initial school list
    termOnChange();
});

/**
 * Called when the college is changed. Loads the departments for the school
 * from the browse ajax handler.
 */
function collegeOnChange() {
    // Clear out the list of departments
    var departments = $("#department");
    departments.children().remove();
    var school = $("#college").children(":selected").val();
    var term = $("#term").children(":selected").val();

    // If the school selected is 'all', then back out
    if(school == 'all') {
        $("<option>Select a College From Above</option>").appendTo(departments);
        return;
    }

    // Setup the post parameters
    var parameters = {
        action: "getDepartments",
        school: school,
        term:   term
    };

    // Get the departments
    $.post("./js/browseAjax.php", parameters, function(d) {
        // Check for errors
        if(d.error != undefined && d.error != null) {
            alert(d.msg);
            return;
        }

        // Iterate over the array we got back and add options for them
        for(var i = 0; i < d.departments.length; ++i) {
            var dept = d.departments[i];

            // Create the option
            var opt = $("<option>");
            opt.val(dept.id);
            if(term > 20130) {
                opt.html(dept.code + " - " + dept.title);
            } else {
                opt.html(dept.number + " - " + dept.title);
            }

            opt.appendTo(departments);
        }
    });

    // Prepend a all option and select it
    $("<option value='all' selected='selected'>All Departments</option>").prependTo(departments);
}

/**
 * Called when the term field changes. Reloads the list of schools.
 */
function termOnChange() {
    // Do we have a list of schools cached?
    if(schoolCache == null) {
        // Schools haven't been cached. Grab them then call back here
        $.post("./js/browseAjax.php", {action: "getSchools"}, function(d) {
            // Error check
            if(d.error != undefined && data.error != null) {
                alert(d.msg);
                return;
            }

            // Store the schools and call the function again.
            schoolCache = d;
            termOnChange();
        });
        return;
    }

    // Clear out the list of schools
    var schools = $("#college");
    schools.children().remove();

    // Clear out the list of departments
    var departments = $("#department");
    departments.children().remove();
    $("<option>Select a College From Above</option>").appendTo(departments);

    // Sort the list of schools based on code or number
    var term = $("#term").children(":selected").val();
    if(term > 20130) {
        // Sort by code
        sortSchools("title");
    } else {
        // Sort by number
        sortSchools("number");
    }

    // Iterate over the list of schools and generate new options for each
    for(var i = 0; i < schoolCache.length; ++i) {
        var school = schoolCache[i];

        // Create an option
        var option = $("<option>");
        if(term > 20130) {
            // Make sure it has a code
            if(school.code == null) { continue; }
            option.html(school.code + " - " + school.title);
        } else {
            // Make sure it has a number
            if(school.number == null) { continue; }
            option.html(school.number + " - " + school.title);
        }
        option.val(school.id);

        // Add the option to the school selector
        option.appendTo(schools);
    }

    // Add the all option to the front of the list
    $("<option value='all'>All Colleges</option>").prependTo(schools);
    schools.children().first().attr("selected", "selected");
}

/**
 * Sorts schools based on the property given
 * Adapted from code at: http://stackoverflow.com/a/881987
 * @param   prop    String  The property to sort by
 */
function sortSchools(prop) {
    schoolCache = schoolCache.sort(function(a, b) {
        return (a[prop] > b[prop]);
    });
}

function spinRoulette() {
	// Serialize the data from the restrictions form and send it in a POST request
	$.post("./js/rouletteAjax.php", $('#parameters').serialize(), function(data) {
        // Store the roulette course div for future use
        var courseDiv = $('#rouletteCourse');

		// Process the resulting code
		try {		
			jsonResult = eval(data);
		} catch(e) {
			courseDiv.html("<h2>Sorry! An Error Occurred!</h2>");
			courseDiv.slideDown();
			return;
		}

		// Was there an error?
		if(jsonResult.error != undefined && jsonResult.error != null) {
			// Display the error in the result box
			courseDiv.html("<h2>Sorry! An Error Occurred!</h2>" + jsonResult.msg + "");
			courseDiv.removeClass();
			courseDiv.addClass('rouletteError');
		} else {
			courseDiv.empty();
			$("<h2>").html("Your Random Course")
					.appendTo(courseDiv);
			var title = jsonResult.department + "-" + jsonResult.course + "-" + jsonResult.section
					+ " " + jsonResult.title + " with " + jsonResult.instructor;
			$("<p>").css("font-weight", "bold")
					.html(title)
					.appendTo(courseDiv);
			if(jsonResult.times.length > 0) {
				table = $("<table>").attr("id", "rouletteCourseTimes");
				for(i = 0; i < jsonResult.times.length; i++) {
					var t = jsonResult.times[i];
					row = $("<tr>");
					$("<td>").html(t.day)
							.appendTo(row);
					$("<td>").html(t.start)
							.appendTo(row);
					$("<td>").html("-")
							.appendTo(row);
					$("<td>").html(t.end)
							.appendTo(row);
					$("<td>").html(t.bldg.code + "(" + t.bldg.number + ")" + "-" + t.room)
							.appendTo(row);
					row.appendTo(table);
				}
				table.appendTo(courseDiv);
			}

			// @TODO:	Link to course in SIS
			
			// Make a button that stores the course info in session data
			$("<input>").attr("type", "button")
						.attr("value", "Build Schedule with this Course")
						.click(function() {	
							sessionStorage.setItem("rouletteCourse", jsonResult.department + "-" + jsonResult.course + "-" + jsonResult.section); 
							window.location = "generate.php";
							})
						.appendTo(courseDiv);

			courseDiv.removeClass();
		}

		// Make it visible and change the button text
		courseDiv.slideDown();
		$('#spinButton').val("I Want a Different Course!");
	});
}

function toggleDaysAny(field) {
    // Grab the days checkboxes
    var days = $(".days");

    // Are we hiding or showing
	if(field.checked) {
        // Hide them all!
        days.attr("disabled", "disabled");
	} else {
		// Show them all!
		days.attr("disabled", "");
	}
}

function toggleTimesAny(field) {
	// Are we hiding or showing?
	if(field.checked) {
		// Hide them all!
		document.getElementById('morn').setAttribute('disabled', 'disabled');
		document.getElementById('aftn').setAttribute('disabled', 'disabled');
		document.getElementById('even').setAttribute('disabled', 'disabled');
	} else {
		// Show them all!
		document.getElementById('morn').removeAttribute('disabled');
		document.getElementById('aftn').removeAttribute('disabled');
		document.getElementById('even').removeAttribute('disabled');
	}
}
