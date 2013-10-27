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
<form id="scheduleForm" name="schedule" class="form-horizontal ng-pristine ng-valid" method="POST">
<div class="panel panel-default ng-scope" ng-controller="scheduleCoursesCtrl">
	<div class="panel-heading">
		<h2 class="panel-title">Courses</h2>
	</div>
    <div class="panel-body">
        <div class="row">
            <div id="scheduleCourses" class="col-md-6">
                <div dynamic-items="courses" use-class="scheduleCourse" helpers="courses_helpers"></div>
            	<div class="visible-xs visible-sm"><button class="btn btn-default btn-block" type="button" ng-click="courses_helpers.add()">Add Course</button><div>&nbsp;</div></div>
            </div>
            <div class="col-md-6">
	            <div class="row ">
		            <div class="col-md-12">
		                <div class="well well-sm">
		                <i>0 Selected Courses</i>
		                </div>
		            </div>
                </div>
	       </div>
        </div>
    </div>
    <div class="panel-footer">
    	<div class="row">
    		<div class="col-md-6">
    		<span class="visible-md visible-lg"><button class="btn btn-default" type="button" ng-click="courses_helpers.add()">Add Course</button> <i>or</i> press enter after each course</span>
    		</div>
            <div class="col-md-3 col-xs-12">
            	<div class="row">
	                <label for="term" class="col-md-4 control-label">Term:</label>
	                <div class="col-md-8">
	                	 <?= getTermField('term', $CURRENT_QUARTER) ?>
		            </div>
	            </div>
	        </div>
	        <div class="visible-sm visible-xs">&nbsp;</div>
	        <div class="col-md-3 col-xs-12" ng-init="ignoreFull = true">
                    <input id="ignoreFull" ng-model="ignoreFull" name="ignoreFull" type="hidden">
                    <button type="button" class="ng-class: {'btn-success': ignoreFull}; btn-default btn btn-block" ng-click="ignoreFull = !ignoreFull">{{ignoreFull?"Show":"Hide"}} filled up courses</button>
	        </div>
       </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">Non-Course Schedule Items</h2>
            </div>
            <div class="panel-body">
                <table id="nonCourses">
                    <tbody><tr>
                        <th>Title</th><th>Start Time</th><th>End Time</th><th>U</th><th>M</th><th>T</th><th>W</th><th>R</th><th>F</th><th>S</th>
                    </tr>
                    <tr>
                        <td><input class="form-control" name="nonCourseTitle1" id="nonCourseTitle1" type="text"></td>
                        <td><input autocomplete="off" class="startTimePicker form-control ui-timepicker-input" name="nonCourseStartTime1" id="nonCourseStartTime1" placeholder="12:00pm" type="text"></td>
                        <td><input class="endTimePicker form-control" name="nonCourseEndTime1" id="nonCourseStartTime1" placeholder="12:00pm" type="text"></td>
                        <td><input name="nonCourseDays1[]" value="Sun" id="nonCourseDaysSun1" type="checkbox"></td>
                        <td><input name="nonCourseDays1[]" value="Mon" id="nonCourseDaysMon1" type="checkbox"></td>
                        <td><input name="nonCourseDays1[]" value="Tue" id="nonCourseDaysTue1" type="checkbox"></td>
                        <td><input name="nonCourseDays1[]" value="Wed" id="nonCourseDaysWed1" type="checkbox"></td>
                        <td><input name="nonCourseDays1[]" value="Thu" id="nonCourseDaysThu1" type="checkbox"></td>
                        <td><input name="nonCourseDays1[]" value="Fri" id="nonCourseDaysFri1" type="checkbox"></td>
                        <td><input name="nonCourseDays1[]" value="Sat" id="nonCourseDaysSat1" type="checkbox"></td>
                    </tr>
                    <tr>
                        <td><input class="form-control" name="nonCourseTitle2" id="nonCourseTitle2" type="text"></td>
                        <td><input autocomplete="off" class="startTimePicker form-control ui-timepicker-input" name="nonCourseStartTime2" id="nonCourseStartTime2" placeholder="12:00pm" type="text"></td>
                        <td><input class="endTimePicker form-control" name="nonCourseEndTime2" id="nonCourseStartTime2" placeholder="12:00pm" type="text"></td>
                        <td><input name="nonCourseDays2[]" value="Sun" id="nonCourseDaysSun2" type="checkbox"></td>
                        <td><input name="nonCourseDays2[]" value="Mon" id="nonCourseDaysMon2" type="checkbox"></td>
                        <td><input name="nonCourseDays2[]" value="Tue" id="nonCourseDaysTue2" type="checkbox"></td>
                        <td><input name="nonCourseDays2[]" value="Wed" id="nonCourseDaysWed2" type="checkbox"></td>
                        <td><input name="nonCourseDays2[]" value="Thu" id="nonCourseDaysThu2" type="checkbox"></td>
                        <td><input name="nonCourseDays2[]" value="Fri" id="nonCourseDaysFri2" type="checkbox"></td>
                        <td><input name="nonCourseDays2[]" value="Sat" id="nonCourseDaysSat2" type="checkbox"></td>
                    </tr>
                    <tr class="lastNonCourseItem">
                        <td><input class="form-control" name="nonCourseTitle3" id="nonCourseTitle3" type="text"></td>
                        <td><input autocomplete="off" class="startTimePicker form-control ui-timepicker-input" name="nonCourseStartTime3" id="nonCourseStartTime3" placeholder="12:00pm" type="text"></td>
                        <td><input class="endTimePicker form-control" name="nonCourseEndTime3" id="nonCourseStartTime3" placeholder="12:00pm" type="text"></td>
                        <td><input name="nonCourseDays3[]" value="Sun" id="nonCourseDaysSun3" type="checkbox"></td>
                        <td><input name="nonCourseDays3[]" value="Mon" id="nonCourseDaysMon3" type="checkbox"></td>
                        <td><input name="nonCourseDays3[]" value="Tue" id="nonCourseDaysTue3" type="checkbox"></td>
                        <td><input name="nonCourseDays3[]" value="Wed" id="nonCourseDaysWed3" type="checkbox"></td>
                        <td><input name="nonCourseDays3[]" value="Thu" id="nonCourseDaysThu3" type="checkbox"></td>
                        <td><input name="nonCourseDays3[]" value="Fri" id="nonCourseDaysFri3" type="checkbox"></td>
                        <td><input name="nonCourseDays3[]" value="Sat" id="nonCourseDaysSat3" type="checkbox"></td>
                    </tr>
                </tbody></table>
            </div>
            <div class="panel-footer">
                <input id="nonCourseCount" class="itemCount" name="nonCourseCount" value="4" type="hidden">
                <button id="addNonCourseButton" class="addItemButton btn btn-default">Add Item</button>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h2 class="panel-title">Times You Don't Want Classes</h2>
            </div>
            <div class="panel-body">
                <table id="noCourses">
                    <tbody><tr>
                        <th>Start Time</th><th>End Time</th><th>U</th><th>M</th><th>T</th><th>W</th><th>R</th><th>F</th><th>S</th>
                    </tr>
                    <tr>
                        <td><input autocomplete="off" class="startTimePicker form-control ui-timepicker-input" name="noCourseStartTime1" id="noCourseStartTime1" placeholder="12:00pm" type="text"></td>
                        <td><input class="endTimePicker form-control" name="noCourseEndTime1" id="noCourseStartTime1" placeholder="12:00pm" type="text"></td>
                        <td><input name="noCourseDays1[]" value="Sun" id="noCourseDaysSun1" type="checkbox"></td>
                        <td><input name="noCourseDays1[]" value="Mon" id="noCourseDaysMon1" type="checkbox"></td>
                        <td><input name="noCourseDays1[]" value="Tue" id="noCourseDaysTue1" type="checkbox"></td>
                        <td><input name="noCourseDays1[]" value="Wed" id="noCourseDaysWed1" type="checkbox"></td>
                        <td><input name="noCourseDays1[]" value="Thu" id="noCourseDaysThu1" type="checkbox"></td>
                        <td><input name="noCourseDays1[]" value="Fri" id="noCourseDaysFri1" type="checkbox"></td>
                        <td><input name="noCourseDays1[]" value="Sat" id="noCourseDaysSat1" type="checkbox"></td>
                    </tr>
                    <tr>
                        <td><input autocomplete="off" class="startTimePicker form-control ui-timepicker-input" name="noCourseStartTime2" id="noCourseStartTime2" placeholder="12:00pm" type="text"></td>
                        <td><input class="endTimePicker form-control" name="noCourseEndTime2" id="noCourseStartTime2" placeholder="12:00pm" type="text"></td>
                        <td><input name="noCourseDays2[]" value="Sun" id="noCourseDaysSun2" type="checkbox"></td>
                        <td><input name="noCourseDays2[]" value="Mon" id="noCourseDaysMon2" type="checkbox"></td>
                        <td><input name="noCourseDays2[]" value="Tue" id="noCourseDaysTue2" type="checkbox"></td>
                        <td><input name="noCourseDays2[]" value="Wed" id="noCourseDaysWed2" type="checkbox"></td>
                        <td><input name="noCourseDays2[]" value="Thu" id="noCourseDaysThu2" type="checkbox"></td>
                        <td><input name="noCourseDays2[]" value="Fri" id="noCourseDaysFri2" type="checkbox"></td>
                        <td><input name="noCourseDays2[]" value="Sat" id="noCourseDaysSat2" type="checkbox"></td>
                    </tr>
                    <tr>
                        <td><input autocomplete="off" class="startTimePicker form-control ui-timepicker-input" name="noCourseStartTime3" id="noCourseStartTime3" placeholder="12:00pm" type="text"></td>
                        <td><input class="endTimePicker form-control" name="noCourseEndTime3" id="noCourseStartTime3" placeholder="12:00pm" type="text"></td>
                        <td><input name="noCourseDays3[]" value="Sun" id="noCourseDaysSun3" type="checkbox"></td>
                        <td><input name="noCourseDays3[]" value="Mon" id="noCourseDaysMon3" type="checkbox"></td>
                        <td><input name="noCourseDays3[]" value="Tue" id="noCourseDaysTue3" type="checkbox"></td>
                        <td><input name="noCourseDays3[]" value="Wed" id="noCourseDaysWed3" type="checkbox"></td>
                        <td><input name="noCourseDays3[]" value="Thu" id="noCourseDaysThu3" type="checkbox"></td>
                        <td><input name="noCourseDays3[]" value="Fri" id="noCourseDaysFri3" type="checkbox"></td>
                        <td><input name="noCourseDays3[]" value="Sat" id="noCourseDaysSat3" type="checkbox"></td>
                    </tr>
                </tbody></table>
            </div>
            <div class="panel-footer">
                <input id="noCourseCount" class="itemCount" name="noCourseCount" value="3" type="hidden">
                <button class="addItemButton btn btn-default">Add Time</button>
            </div>
        </div>
    </div>
</div>
<div id="advancedOptionsCont" ng-init="showAdvancedOptions = false" ng-show="showAdvancedOptions" class="panel panel-default ng-hide">
	<div class="panel-heading">
		<h2 class="panel-title">Advanced Options</h2>
	</div>
    <div class="panel-body">
        <table id="advancedOptions">
            <tbody><tr>
                <td class="lbl"><label for="scheduleStart">Start Time:</label></td>
                <td><input autocomplete="off" class="form-control ui-timepicker-input" id="scheduleStart" value="8:00am" name="scheduleStart" type="text"></td>
                <td class="lbl"><label for="scheduleEnd">End Time:</label></td>
                <td><input autocomplete="off" class="form-control ui-timepicker-input" id="scheduleEnd" value="10:00pm" name="scheduleEnd" type="text"></td>
            </tr>
            <tr>
                <td class="lbl"><label for="scheduleStartDay">First Day:</label></td>
                <td><?= getDayField("scheduleStartDay", 1, true) ?></td>
                <td class="lbl"><label for="scheduleEndDay">End Day:</label></td>
                <td><?= getDayField("scheduleEndDay", 6, true) ?></td>
            </tr>
            <tr>
                <td class="lbl">
                    <label class="lbl">Schedules per Page:</label>
                </td>
                <td>
                    <select id="schedPerPage" name="schedPerPage">
                        <option value="3" selected="selected">3 per Page</option>
                        <option value="5">5 per Page</option>
                        <option value="10">10 per Page</option>
                        <option value="15">15 per Page</option>
                        <option value="20">20 per Page</option>
                        <option value="all">All Schedules</option>
                    </select>
                </td>
                <td>
                    <label for="buildingStyle">Buildings:</label>
                </td>
                <td>
                    <select id="buildingStyle" name="buildingStyle">
                        <option selected="selected" value="code">Codes (eg. GOL)</option>
                        <option value="number">Number (eg. 70)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td style="text-align:right">
                    <input id="verbose" name="verbose" value="true" type="checkbox">
                </td>
                <td>
                    <label for="verbose">Show Error Messages/Course Conflicts</label>
                </td>
            </tr>
        </tbody></table>
    </div>
</div>
<input name="action" value="getMatchingSchedules" type="hidden">
<div id="formSubmit" class="scheduleForm">
    <button class="btn btn-lg btn-default ng-binding" ng-click="showAdvancedOptions = !showAdvancedOptions">Show Advanced Options</button> 
    <button class="btn-lg btn btn-primary btn-default" id="showSchedulesButton">Show Matching Schedules</button>
</div>
</form>
<div id="schedules">
	<div id="matchingSchedules" class="subheader">
		<h2>Matching Schedules</h2>
	</div>
</div>
<script type='text/javascript' src='js/handlebars.js'></script>
<script type='text/javascript' src='js/translateFunctions.js'></script>
<? require "./inc/footer.inc"; ?>
