<?php
////////////////////////////////////////////////////////////////////////////
// SCHEDULE BUILDER
//
// @author	Ben Russell (benrr101@csh.rit.edu)
//
// @file	schedule.php
// @descrip	Form for building a schedule. Fill in the information and BAM
//			a link to the matching schedules page does all the work for you!
////////////////////////////////////////////////////////////////////////////

require "./inc/header.inc";
global $CURRENT_QUARTER;

?>
<script type='text/javascript' src='./js/reloadSchedule.js'></script>
<script type='text/javascript' src='./js/schedule.js'></script>
<script type='text/javascript' src='./js/jquery.timepicker.min.js'></script>
<link href='inc/jquery.timepicker.css' rel='stylesheet' type='text/css' />
<form id='scheduleForm' name='schedule' method='POST'>
<div class='scheduleForm'>
	<div class='subheader'>
		<h2>Courses</h2>
		<button id='addCourseButton'>Add Course</button>
	</div>
	<div class='courseSettings'>
		<label for='term'>Term:</label> <?= getTermField('term', $CURRENT_QUARTER) ?>
		<input id='courseCount' type='hidden' name='courseCount' value='5' />
		
		<input id='ignoreFull' type='checkbox' name='ignoreFull' value='true' />
		<label for='ignoreFull'>Ignore full courses</label>
	</div>
	<div id='scheduleCourses'>
        <div class='courseRow'>
            <div class='courseRowField'>
                <label for='courses1'>Course 1:</label>
                <input id='courses1' type='text' name='courses1' class='courseField' maxlength='17' placeholder="XXXX-XXX-XXXX" />
            </div>
            <div class='courseRowOptions'></div>
        </div>
        <div class='courseRow'>
            <div class='courseRowField'>
                <label for='courses2'>Course 2:</label>
                <input id='courses2' type='text' name='courses2' class='courseField' maxlength='17' placeholder="XXXX-XXX-XXXX" />
            </div>
            <div class='courseRowOptions'></div>
        </div>
        <div class='courseRow'>
            <div class='courseRowField'>
                <label for='courses3'>Course 3:</label>
                <input id='courses3' type='text' name='courses3' class='courseField' maxlength='17' placeholder="XXXX-XXX-XXXX" />
            </div>
            <div class='courseRowOptions'></div>
        </div>
        <div class='courseRow'>
            <div class='courseRowField'>
                <label for='courses4'>Course 4:</label>
                <input id='courses4' type='text' name='courses4' class='courseField' maxlength='17' placeholder="XXXX-XXX-XXXX" />
            </div>
            <div class='courseRowOptions'></div>
        </div>
        <div class='courseRow'>
            <div class='courseRowField'>
                <label for='courses5'>Course 5:</label>
                <input id='courses5' type='text' name='courses5' class='courseField' maxlength='17' placeholder="XXXX-XXX-XXXX" />
            </div>
            <div class='courseRowOptions'></div>
        </div>
	</div>
</div>
<div class='scheduleForm'>
	<div class='subheader'>
		<h2>Non-Course Schedule Items</h2>
		<input id='nonCourseCount' class='itemCount' type='hidden' name='nonCourseCount' value='3' />
		<button id="addNonCourseButton" class='addItemButton'>Add Item</button>
	</div>
	<table id='nonCourses'>
		<tr>
			<th>Title</th><th>Start Time</th><th>End Time</th><th>U</th><th>M</th><th>T</th><th>W</th><th>R</th><th>F</th><th>S</th>
		</tr>
		<tr>
			<td><input type='text' name='nonCourseTitle1' id='nonCourseTitle1' /></td>
			<td><input type='text' class='startTimePicker' name='nonCourseStartTime1' id='nonCourseStartTime1' placeholder="12:00pm"/></td>
			<td><input type='text' class='endTimePicker' name='nonCourseEndTime1' id='nonCourseStartTime1' placeholder="12:00pm"/></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Sun' id='nonCourseDaysSun1' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Mon' id='nonCourseDaysMon1' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Tue' id='nonCourseDaysTue1' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Wed' id='nonCourseDaysWed1' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Thu' id='nonCourseDaysThu1' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Fri' id='nonCourseDaysFri1' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Sat' id='nonCourseDaysSat1' /></td>
		</tr>
		<tr>
			<td><input type='text' name='nonCourseTitle2' id='nonCourseTitle2' /></td>
            <td><input type='text' class='startTimePicker' name='nonCourseStartTime2' id='nonCourseStartTime2' placeholder="12:00pm"/></td>
            <td><input type='text' class='endTimePicker' name='nonCourseEndTime2' id='nonCourseStartTime2' placeholder="12:00pm"/></td>
            <td><input type='checkbox' name='nonCourseDays2[]' value='Sun' id='nonCourseDaysSun2' /></td>
            <td><input type='checkbox' name='nonCourseDays2[]' value='Mon' id='nonCourseDaysMon2' /></td>
            <td><input type='checkbox' name='nonCourseDays2[]' value='Tue' id='nonCourseDaysTue2' /></td>
            <td><input type='checkbox' name='nonCourseDays2[]' value='Wed' id='nonCourseDaysWed2' /></td>
            <td><input type='checkbox' name='nonCourseDays2[]' value='Thu' id='nonCourseDaysThu2' /></td>
            <td><input type='checkbox' name='nonCourseDays2[]' value='Fri' id='nonCourseDaysFri2' /></td>
            <td><input type='checkbox' name='nonCourseDays2[]' value='Sat' id='nonCourseDaysSat2' /></td>
		</tr>
		<tr class='lastNonCourseItem'>
			<td><input type='text' name='nonCourseTitle3' id='nonCourseTitle3' /></td>
            <td><input type='text' class='startTimePicker' name='nonCourseStartTime3' id='nonCourseStartTime3' placeholder="12:00pm"/></td>
            <td><input type='text' class='endTimePicker' name='nonCourseEndTime3' id='nonCourseStartTime3' placeholder="12:00pm"/></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Sun' id='nonCourseDaysSun3' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Mon' id='nonCourseDaysMon3' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Tue' id='nonCourseDaysTue3' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Wed' id='nonCourseDaysWed3' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Thu' id='nonCourseDaysThu3' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Fri' id='nonCourseDaysFri3' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Sat' id='nonCourseDaysSat3' /></td>
		</tr>
	</table>
</div>
<div class='scheduleForm'>
	<div class='subheader'>
		<h2>Times You Don't Want Classes</h2>
		<input id='noCourseCount' class='itemCount' type='hidden' name='noCourseCount' value='3' />
		<button class='addItemButton'>Add Time</button>
	</div>
	<table id='noCourses'>
		<tr>
			<th>Start Time</th><th>End Time</th><th>U</th><th>M</th><th>T</th><th>W</th><th>R</th><th>F</th><th>S</th>
		</tr>
		<tr>
			<td><input type='text' class='startTimePicker' name='noCourseStartTime1' id='noCourseStartTime1' placeholder="12:00pm"/></td>
			<td><input type='text' class='endTimePicker' name='noCourseEndTime1' id='noCourseStartTime1' placeholder="12:00pm"/></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Sun' id='noCourseDaysSun1' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Mon' id='noCourseDaysMon1' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Tue' id='noCourseDaysTue1' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Wed' id='noCourseDaysWed1' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Thu' id='noCourseDaysThu1' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Fri' id='noCourseDaysFri1' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Sat' id='noCourseDaysSat1' /></td>
		</tr>
		<tr>
			<td><input type='text' class='startTimePicker' name='noCourseStartTime2' id='noCourseStartTime2' placeholder="12:00pm"/></td>
            <td><input type='text' class='endTimePicker' name='noCourseEndTime2' id='noCourseStartTime2' placeholder="12:00pm"/></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Sun' id='noCourseDaysSun2' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Mon' id='noCourseDaysMon2' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Tue' id='noCourseDaysTue2' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Wed' id='noCourseDaysWed2' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Thu' id='noCourseDaysThu2' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Fri' id='noCourseDaysFri2' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Sat' id='noCourseDaysSat2' /></td>
		</tr>
		<tr>
			<td><input type='text' class='startTimePicker' name='noCourseStartTime3' id='noCourseStartTime3' placeholder="12:00pm"/></td>
            <td><input type='text' class='endTimePicker' name='noCourseEndTime3' id='noCourseStartTime3' placeholder="12:00pm"/></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Sun' id='noCourseDaysSun3' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Mon' id='noCourseDaysMon3' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Tue' id='noCourseDaysTue3' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Wed' id='noCourseDaysWed3' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Thu' id='noCourseDaysThu3' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Fri' id='noCourseDaysFri3' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Sat' id='noCourseDaysSat3' /></td>
		</tr>
	</table>
</div>
<div class='scheduleForm'>
	<div class='subheader'>
		<h2>Advanced Options</h2>
	</div>
	<table id='advancedOptions'>
		<tr>
			<td class='lbl'><label for='scheduleStart'>Start Time:</label></td>
			<td><input type='text' id='scheduleStart' value='8:00am' name='scheduleStart' /></td>
			<td class='lbl'><label for='scheduleEnd'>End Time:</label></td>
			<td><input type='text' id='scheduleEnd' value='10:00pm' name='scheduleEnd' /></td>
		</tr>
		<tr>
			<td class='lbl'><label for='scheduleStartDay'>First Day:</label></td>
			<td><?= getDayField("scheduleStartDay", 1, true) ?></td>
			<td class='lbl'><label for='scheduleEndDay'>End Day:</label></td>
			<td><?= getDayField("scheduleEndDay", 6, true) ?></td>
		</tr>
		<tr>
			<td class='lbl'>
				<label class='lbl'>Schedules per Page:</label>
			</td>
			<td>
				<select id='schedPerPage' name='schedPerPage'>
					<option value='3' selected='selected'>3 per Page</option>
					<option value='5'>5 per Page</option>
					<option value='10'>10 per Page</option>
					<option value='15'>15 per Page</option>
					<option value='20'>20 per Page</option>
					<option value='all'>All Schedules</option>
				</select>
			</td>
			<td>
				<label for='buildingStyle'>Buildings:</label>
			</td>
			<td>
				<select id='buildingStyle' name='buildingStyle'>
					<option value='code'>Codes (eg. GOL)</option>
					<option value='number'>Number (eg. 70)</option>
				</select>
			</td>
		</tr>
		<tr>
			<td style='text-align:right'>
				<input id='verbose' type='checkbox' name='verbose' value='true' />
			</td>
			<td>
				<label for='verbose'>Show Error Messages/Course Conflicts</label>
			</td>
		</tr>
	</table>
</div>
<input type='hidden' name='action' value='getMatchingSchedules' />
<div id='formSubmit' class='scheduleForm'>
    <button class='bigButton' id='showSchedulesButton'>Show Matching Schedules</button>
</div>
</form>
<div id='schedules'>
	<div id='matchingSchedules' class='subheader'>
		<h2>Matching Schedules</h2>
	</div>
</div>
<script type='text/javascript' src='js/handlebars.js'></script>
<script type='text/javascript' src='js/translateFunctions.js'></script>
<? require "./inc/footer.inc"; ?>
