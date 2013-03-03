////////////////////////////////////////////////////////////////////////////
// SAVED SCHEDULE JAVASCRIPT
//
// @file	js/savedSchedule.js
// @descrip	This file will provide functionality for printing/forking a saved
//			schedule. Mainly through the use of global variables...
// @author	Ben Russell (benrr101@csh.rit.edu)
////////////////////////////////////////////////////////////////////////////

$(document).ready(function() {
	// Associate a handler with the print/fork/ical buttons
	$("#printButton").click(function() {
		printSchedule();
	});

	$("#forkButton").click(function() {
		forkSchedule();
	});

    $("#iCalButton").click(function(e) {
        e.preventDefault();
        window.location = window.location + "&mode=ical";
    });

    // Add hover handlers for the timeContainers (just like on the generated schedule)
    var timeContainers = $(".timeContainer");
    timeContainers.on("mouseover", function() {
        var container = $(this);
        var infoDiv   = container.children("div");

        // Make things visible, add glow to the container
        container.css("overflow", "visible");
        container.css("box-shadow", "0px 0px 5px yellow");
        infoDiv.css("background-color", container.css("background-color"));
    });

    timeContainers.on("mouseout", function() {
        var container = $(this);
        var infoDiv   = container.children("div");

        // Hide things
        container.css("overflow", "hidden");
        container.css("box-shadow", "");
        infoDiv.css("background-color", "");
    });
});

function forkSchedule() {
	// Load up the schedule's JSON into the session storage
	json = $("#schedJson").val();
	window.sessionStorage.setItem("scheduleJson", json);
	json = eval("(" + json + ")");
	
	// Now redirect the browser to the generate page
	window.location = "generate.php?mode=fork";	
}

function printSchedule() {
	// Load up the schedule's JSON into the session storage
	json = $("#schedJson").val();
	window.sessionStorage.setItem("scheduleJson", json);
	
	json = eval("(" + json + ")");

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
