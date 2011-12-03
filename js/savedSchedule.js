////////////////////////////////////////////////////////////////////////////
// SAVED SCHEDULE JAVASCRIPT
//
// @file	js/savedSchedule.js
// @descrip	This file will provide functionality for printing/forking a saved
//			schedule. Mainly through the use of global variables...
// @author	Ben Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

$(document).ready(function() {
	// Associate a handler with the print and fork buttons
	$("#printButton").click(function() {
		printSchedule();
	});

	$("#forkButton").click(function() {
		forkSchedule();
	});
});

function printSchedule() {
	// Load up the schedule's JSON into the session storage
	json = $("#schedJson").val();
	window.sessionStorage.setItem("scheduleJson", json);

	json = eval("(" + json + ")" );
	
	// Open the popup window with the printable schedule
	window.prin=window.prin||{};
	var D=((json.endDay - json.startDay + 1) * 100) + 40,
		A=450,
		C=screen.height,
		B=screen.width,
		H=Math.round((B/2) - (D/2)),
		G=0;
	window.prin.prinWin = window.open(
		"schedule.php?mode=print",
		"",
		'left='+H+',top='+G+',width='+D+',height='+A+',personalbar=0,toolbar=0,scrollbars=1,resizable=1'
	);
}
