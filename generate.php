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

?>
<script type='text/javascript' src='./js/schedule.js'></script>
<form id='scheduleForm' name='schedule' action='buildSchedule.php' method='POST'>
<div class='scheduleForm'>
	<div class='subheader'>
		<h2>Courses</h2>
		<input type='button' value="Add Course" onClick='addCourse();' />
	</div>
	<div class='courseRow'>
		<label for='quarter'>Quarter:</label> <?= getQuarterField('quarter', $CURRENT_QUARTER) ?>
		<input id='courseCount' type='hidden' name='courseCount' value='4' />
	</div>
	<div id='scheduleCourses'>
		<div id='courseRow1' class='courseRow'>
			<div class='course'>
				<h3>Course 1</h3>
				<input id='courses1' type='text' name='courses1' maxlength='11' onFocus='courseOnFocus(this);' onBlur='getCourseOptions(this);' value='XXXX-XXX-XX' />
				<div class='courseOpts'>
				</div>
			</div>
			<div class='course'>
				<h3>Course 2</h3>
				<input id='courses2' type='text' name='courses2' maxlength='11' onFocus='courseOnFocus(this);' onBlur='getCourseOptions(this);' value='XXXX-XXX-XX' />
				<div class='courseOpts'>
				</div>
			</div>
			<div class='course'>
				<h3>Course 3</h3>
				<input id='courses3' type='text' name='courses3' maxlength='11' onFocus='courseOnFocus(this);' onBlur='getCourseOptions(this);' value='XXXX-XXX-XX' />
				<div class='courseOpts'>
				</div>
			</div>
			<div class='course'>
				<h3>Course 4</h3>
				<input id='courses4' type='text' name='courses4' maxlength='11' onFocus='courseOnFocus(this);' onBlur='getCourseOptions(this);' value='XXXX-XXX-XX' />
				<div class='courseOpts'>
				</div>
			</div>
		</div>
	</div>
</div>
<div class='scheduleForm'>
	<div class='subheader'>
		<h2>Non-Course Schedule Items</h2>
		<input id='nonCourseCount' type='hidden' name='nonCourseCount' value='3' />
		<input type='button' value="Add Item" onClick='addItem();' />
	</div>
	<table id='nonCourses'>
		<tr>
			<th>Title</th><th>Start Time</th><th>End Time</th><th>U</th><th>M</th><th>T</th><th>W</th><th>R</th><th>F</th><th>S</th>
		</tr>
		<tr>
			<td><input type='text' name='nonCourseTitle1' /></td>
			<td><?= getTimeField("nonCourseStartTime1") ?></td>
			<td><?= getTimeField("nonCourseEndTime1") ?></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Sun' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Mon' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Tue' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Wed' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Thu' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Fri' /></td>
			<td><input type='checkbox' name='nonCourseDays1[]' value='Sat' /></td>
		</tr>
		<tr>
			<td><input type='text' name='nonCourseTitle2' /></td>
			<td><?= getTimeField("nonCourseStartTime2") ?></td>
			<td><?= getTimeField("nonCourseEndTime2") ?></td>
			<td><input type='checkbox' name='nonCourseDays2[]' value='Sun' /></td>
			<td><input type='checkbox' name='nonCourseDays2[]' value='Mon' /></td>
			<td><input type='checkbox' name='nonCourseDays2[]' value='Tue' /></td>
			<td><input type='checkbox' name='nonCourseDays2[]' value='Wed' /></td>
			<td><input type='checkbox' name='nonCourseDays2[]' value='Thu' /></td>
			<td><input type='checkbox' name='nonCourseDays2[]' value='Fri' /></td>
			<td><input type='checkbox' name='nonCourseDays2[]' value='Sat' /></td>
		</tr>
		<tr>
			<td><input type='text' name='nonCourseTitle3' /></td>
			<td><?= getTimeField("nonCourseStartTime3") ?></td>
			<td><?= getTimeField("nonCourseEndTime3") ?></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Sun' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Mon' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Tue' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Wed' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Thu' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Fri' /></td>
			<td><input type='checkbox' name='nonCourseDays3[]' value='Sat' /></td>
		</tr>
	</table>
</div>
<div class='scheduleForm'>
	<div class='subheader'>
		<h2>Times You Don't Want Classes</h2>
		<input id='noCourseCount' type='hidden' name='noCourseCount' value='3' />
		<input type='button' value="Add Time" onClick='addTime();' />
	</div>
	<table id='noCourses'>
		<tr>
			<th>Start Time</th><th>End Time</th><th>U</th><th>M</th><th>T</th><th>W</th><th>R</th><th>F</th><th>S</th>
		</tr>
		<tr>
			<td><?= getTimeField("noCourseStartTime1") ?></td>
			<td><?= getTimeField("noCourseEndTime1") ?></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Sun' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Mon' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Tue' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Wed' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Thu' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Fri' /></td>
			<td><input type='checkbox' name='noCourseDays1[]' value='Sat' /></td>
		</tr>
		<tr>
			<td><?= getTimeField("noCourseStartTime2") ?></td>
			<td><?= getTimeField("noCourseEndTime2") ?></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Sun' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Mon' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Tue' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Wed' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Thu' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Fri' /></td>
			<td><input type='checkbox' name='noCourseDays2[]' value='Sat' /></td>
		</tr>
		<tr>
			<td><?= getTimeField("noCourseStartTime3") ?></td>
			<td><?= getTimeField("noCourseEndTime3") ?></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Sun' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Mon' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Tue' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Wed' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Thu' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Fri' /></td>
			<td><input type='checkbox' name='noCourseDays3[]' value='Sat' /></td>
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
			<td><?= getTimeField("scheduleStart", 480) ?></td>
			<td class='lbl'><label for='scheduleEnd'>End Time:</label></td>
			<td><?= getTimeField("scheduleEnd", 1320) ?></td>
		</tr>
		<tr>
			<td class='lbl'><label for='scheduleStartDay'>First Day:</label></td>
			<td><?= getDayField("scheduleStartDay", 1, true) ?></td>
			<td class='lbl'><label for='scheduleEndDay'>End Day:</label></td>
			<td><?= getDayField("scheduleEndDay", 6, true) ?></td>
		</tr>
		<tr>
			<td class='lbl'><input id='verbose' type='checkbox' name='verbose' value='true' /></td>
			<td colspan='3'><label for='verbose'>Show Error Messages/Course Conflicts</label></td>
		</tr>
	</table>
</div>
<input type='hidden' name='action' value='getMatchingSchedules' />
<div id='formSubmit' class='scheduleForm'><input type='button' class='bigButton' value="Show Matching Schedules" onClick="showSchedules();" /></div>
</form>
<div id='schedules'>
	<div id='matchingSchedules' class='subheader'>
		<h2>Matching Schedules</h2>
	</div>
</div>
<? require "./inc/footer.inc"; ?>
