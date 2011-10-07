////////////////////////////////////////////////////////////////////////////
// COURSE ROULETTE JAVASCRIPT FUNCTIONS
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	js/roulette.js
// @descrip	Functions for managing displaying and manipulating the course
//			roulette page.
////////////////////////////////////////////////////////////////////////////

function spinRoulette() {
	// Serialize the data from the restrictions form and send it in a POST request
	$.post("./js/ajaxCalls.php", $('#restrictions').serialize(), function(data) {
		// Process the resulting code
		try {		
			jsonResult = eval(data);
		} catch(e) {
			$('#rouletteCourse').html("<h2>Sorry! An Error Occurred!</h2>");
			$('#rouletteCourse').slideDown();
			return;
		}

		// Was there an error?
		if(jsonResult.error != undefined && jsonResult.error != null) {
			// Display the error in the result box
			$('#rouletteCourse').html("<h2>Sorry! An Error Occurred!</h2><p>" + jsonResult.msg + "</p>");
			$('#rouletteCourse').removeClass();
			$('#rouletteCourse').addClass('rouletteError');
		} else {
			courseDiv = $('#rouletteCourse');
			courseDiv.empty();
			$("<h2>").html("Your Random Course")
					.appendTo(courseDiv);
			$("<p>").css("font-weight", "bold")
					.html(jsonResult.department + "-" + jsonResult.course + "-" + jsonResult.section)
					.appendTo(courseDiv);
			$("<p>").html(jsonResult.title + " with " + jsonResult.instructor)
					.appendTo(courseDiv);
			table = $("<table>").attr("id", "rouletteCourseTimes");
			for(i = 0; i < jsonResult.times.length; i++) {
				row = $("<tr>");
				$("<td>").html(jsonResult.times[i].day)
						.appendTo(row);
				$("<td>").html(jsonResult.times[i].start)
						.appendTo(row);
				$("<td>").html("-")
						.appendTo(row);
				$("<td>").html(jsonResult.times[i].end)
						.appendTo(row);
				$("<td>").html(jsonResult.times[i].bldg + "-" + jsonResult.times[i].room)
						.appendTo(row);
				row.appendTo(table);
			}
			table.appendTo(courseDiv);			

			// @TODO:	Link to course in SIS
			
			// Make a button that stores the course info in session data
			$("<input>").attr("type", "button")
						.attr("value", "Build Schedule with this Course")
						.click(function() {	
							sessionStorage.setItem("rouletteCourse", jsonResult.department + "-" + jsonResult.course + "-" + jsonResult.section); 
							window.location = "generate.php";
							})
						.appendTo(courseDiv);

			$('#rouletteCourse').removeClass();
			$('#rouletteCourse').addClass('rouletteCourse');
		}

		// Make it visible and change the button text
		$('#rouletteCourse').slideDown();
		$('#spinButton').val("Respin!");
	});
}

function toggleDaysAny(field) {
	// Are we hiding or showing
	if(field.checked) {
		// Hide them all!
		document.getElementById('mon').setAttribute('disabled', 'disabled');
		document.getElementById('tue').setAttribute('disabled', 'disabled');
		document.getElementById('wed').setAttribute('disabled', 'disabled');
		document.getElementById('hur').setAttribute('disabled', 'disabled');
		document.getElementById('fri').setAttribute('disabled', 'disabled');
		document.getElementById('sat').setAttribute('disabled', 'disabled');
	} else {
		// Show them all!
		document.getElementById('mon').removeAttribute('disabled');
		document.getElementById('tue').removeAttribute('disabled');
		document.getElementById('wed').removeAttribute('disabled');
		document.getElementById('hur').removeAttribute('disabled');
		document.getElementById('fri').removeAttribute('disabled');
		document.getElementById('sat').removeAttribute('disabled');
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
