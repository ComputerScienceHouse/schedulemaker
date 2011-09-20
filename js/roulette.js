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
			// Display the course in the result box
			htmll = "<h2>Your Random Course</h2>";
			htmll += "<p style='font-weight:bold'>" + jsonResult.department + "-" + jsonResult.course + "-" + jsonResult.section + "</p>";
			htmll += "<p>" + jsonResult.title + " with " + jsonResult.instructor + "</p>";
			htmll += "<table id='rouletteCourseTimes'>";
			for(i = 0; i < jsonResult.times.length; i++) {
				htmll += "<tr>";
				htmll += "<td>" + jsonResult.times[i].day + "</td><td>" + jsonResult.times[i].start + "</td><td>-</td><td>" + jsonResult.times[i].end + "</td>";
				htmll += "<td>" + jsonResult.times[i].bldg + "-" + jsonResult.times[i].room + "</td>";
				htmll += "</tr>";
			}
			htmll += "</table>";
			// @todo:	Link to course in SIS
			// @todo:	Link to make into schedule
			$('#rouletteCourse').html(htmll);
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
