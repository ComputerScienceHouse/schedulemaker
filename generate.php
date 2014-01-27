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
<form novalidate id="scheduleForm" name="schedule" class="form-horizontal ng-pristine ng-valid container" method="POST">
	<div class="panel panel-default" ng-controller="scheduleCoursesCtrl">
		<div class="panel-heading">
			<h2 class="panel-title">Select Courses</h2>
		</div>
		<div class="panel-body">
			<div id="scheduleCourses">
				<div dynamic-items="courses" use-class="scheduleCourse" helpers="courses_helpers"></div>
			</div>
			<!--<pre>{{courses | json}}</pre>-->
		</div>
		<div class="panel-footer">
			<input type="hidden" value="{{courses.length}}" name="courseCount" id="courseCount">
			<div class="row">
				<div class="col-md-6">
					<button class="btn btn-primary btn-block visible-xs visible-sm" type="button" ng-click="courses_helpers.add()">
						<i class="fa fa-plus"></i> Add Course
					</button>
					<span class="visible-md visible-lg">
						<button class="btn btn-primary" type="button" ng-click="courses_helpers.add()">
							<i class="fa fa-plus"></i> Add Course
						</button> <i>or</i> press enter after each course
					</span> </span>
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
					<input id="ignoreFull" value="{{ignoreFull}}" name="ignoreFull" type="hidden">
					<button type="button" class="ng-class: {'btn-success': ignoreFull}; btn-default btn btn-block" ng-click="ignoreFull = !ignoreFull">{{ignoreFull?"Show":"Hide"}} filled up courses</button>
				</div>
			</div>
		</div>
	</div>
	<div>&nbsp;</div>
	<div class="row" ng-show="showScheduleOptions">
		<div class="col-md-6">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h2 class="panel-title">Non-Course Schedule Items</h2>
				</div>
				<div class="panel-body">
					<table id="nonCourses">
						<tbody>
							<tr>
								<th>Title</th>
								<th>Start Time</th>
								<th>End Time</th>
								<th>U</th>
								<th>M</th>
								<th>T</th>
								<th>W</th>
								<th>R</th>
								<th>F</th>
								<th>S</th>
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
						</tbody>
					</table>
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
						<tbody>
							<tr>
								<th>Start Time</th>
								<th>End Time</th>
								<th>U</th>
								<th>M</th>
								<th>T</th>
								<th>W</th>
								<th>R</th>
								<th>F</th>
								<th>S</th>
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
						</tbody>
					</table>
				</div>
				<div class="panel-footer">
					<input id="noCourseCount" class="itemCount" name="noCourseCount" value="3" type="hidden">
					<button class="addItemButton btn btn-default">Add Time</button>
				</div>
			</div>
		</div>
	</div>
	<input name="action" value="getMatchingSchedules" type="hidden"> <input type="hidden" value="true" name="verbose" id="verbose">
	<div class="center" role="toolbar">
		<div class="btn-group">
			<button type="button" class="btn btn-default btn-lg" ng-click="showScheduleOptions = !showScheduleOptions">Toggle Schedule Options</button>
		</div>
		<div class="btn-group">
			<button class="btn-lg btn btn-primary btn-default" ng-click="generateSchedules()">Show Matching Schedules</button>
		</div>
	</div>
</form>
<div id="master_schedule_results" ng-show="schedules.length > 0" ng-init="showOptions = true">
	<div class="container">
		<div class="visible-xs visible-sm form-group">
			<button class="btn btn-block btn-primary" ng-click="showOptions = !showOptions" type="button"><i class="fa" ng-class="{'fa-chevron-down':showOptions,'fa-chevron-up':!showOptions}"></i> Options</button>
		</div>
		<div ng-class="{'hidden-xs':showOptions, 'hidden-sm': showOptions}" class="row">
			<div class="col-xs-12">
				<div class="panel panel-default">
					<div class="panel-body">
						<div class="row form-inline">
							<div class="col-xs-12">
								<div class="form-group">
									<button class="hidden-xs hidden-sm btn btn-primary" ng-click="showDisplayOptions = !showDisplayOptions" type="button"><i class="fa" ng-class="{'fa-chevron-down':!showDisplayOptions,'fa-chevron-up':showDisplayOptions}"></i></button>
								</div>
								<div class="form-group">
									Display from
								</div>
								<div class="form-group">
									<select id="options-start_time" ng-change="ensureCorrectEndTime()" class="form-control" ng-model="options.start_time" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys"></select>
								</div>
								<div class="form-group">
									to
								</div>
								<div class="form-group">
									<select id="options-end_time" class="form-control" ng-model="options.end_time" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys | startFrom: ui.optionLists.times.keys.indexOf(options.start_time) + 1"></select>
								</div>
								<div class="form-group">
									and from
								</div>
								<div class="form-group">
									<select id="options-start_day" ng-change="ensureCorrectEndDay()" class="form-control" ng-model="options.start_day" ng-options="ui.optionLists.days.indexOf(value) as value for (key, value) in ui.optionLists.days"></select>
								</div>
								<div class="form-group">
									to
								</div>
								<div class="form-group">
									<select id="options-end_day" class="form-control" ng-model="options.end_day" ng-options="ui.optionLists.days.indexOf(value) as value for (key, value) in ui.optionLists.days | startFrom: options.start_day"></select>
								</div>
								<div class="form-group pull-right" schedule-pagination="displayOptions"></div>
							</div>
						</div>
						<div class="visible-xs visible-sm">
							<button class="btn btn-block btn-primary" ng-click="showDisplayOptions = !showDisplayOptions" type="button"><i class="fa" ng-class="{'fa-chevron-down':!showDisplayOptions,'fa-chevron-up':showDisplayOptions}"></i> Advanced Options</button>
						</div>
						<div ng-show="showDisplayOptions" ng-init="showDisplayOptions = false">
							<div class="vert-spacer-static-md"></div>
							<div class="row form-horizontal">
								<div class="col-md-4">
									<div class="form-group">
										<label for="options-building_style" class="col-sm-4 control-label">Buildings</label>
										<div class="col-sm-8">
											<select id="options-building_style" class="form-control" ng-model="options.building_style">
												<option value="code">Codes (eg. GOL)</option>
												<option value="number">Number (eg. 70)</option>
											</select>
										</div>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group hidden-xs">
										<label for="options-fullscreen" class="col-sm-4 control-label">Width</label>
										<div class="col-sm-8">
											<div class="checkbox">
												<label> <input id="options-fullscreen" type="checkbox" ng-model="displayOptions.fullscreen"> Fullscreen
												</label>
											</div>
										</div>
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label for="displayOptions-pageSize" class="col-sm-4 control-label">Page Size</label>
										<div class="col-sm-8">
											<select id="displayOptions-pageSize" class="form-control" ng-model="displayOptions.pageSize">
												<option value="3">3</option>
												<option value="5">5</option>
												<option value="10">10</option>
												<option value="15">15</option>
												<option value="20">20</option>
												<option value="50">50</option>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div ng-class="{container: !displayOptions.fullscreen}">
		<div ng-class="{'col-sm-12': displayOptions.fullscreen}">
			<div class="row" ng-repeat="schedule in schedules | startFrom:displayOptions.currentPage*displayOptions.pageSize | limitTo:displayOptions.pageSize">
				<div class="col-md-12">
					<div class="panel panel-default">
						<div id="matchingSchedules" class="panel-heading">
							<h2 class="panel-title">Schedule {{$index +1}}:</h2>
						</div>
						<div class="panel-body">
							<div class="row">
								<div class="col-md-9 col-lg-10">
									<div schedule></div>
								</div>
								<div class="col-md-3 col-lg-2">
									<div class="panel panel-default">
										<div class="panel-heading">
											<h2 class="panel-title">Options</h2>
										</div>
										<div class="panel-body">
											<button type="button" class="btn btn-block btn-info hidden-xs hidden-sm">Print Schedule</button>
											<button type="button" class="btn btn-block btn-default">Save Schedule</button>
											<button type="button" class="btn btn-block btn-default">Download iCal</button>
											<button type="button" class="btn btn-block btn-primary">Share to <i class="fa fa-facebook"></i></button>
											<button type="button" class="btn btn-block btn-primary">Share to <i class="fa fa-google-plus"></i></button>
											<button type="button" class="btn btn-block btn-primary">Share to <i class="fa fa-twitter"></i></button>
										</div>
									</div>
								</div>
							</div>
							<div ng-show="hiddenCourses.length > 0" class="row">
								<div class="col-xs-12">
									<div class="alert alert-warning">
										<strong>Warning!</strong> The following courses are not displayed: <span ng-repeat="course in hiddenCourses">{{course}}{{$last?'':','}}</span>
									</div>
								</div>
							</div>
							<div ng-show="onlineCourses.length > 0" class="row">
								<div class="col-xs-12">
									<div class="alert alert-info">
										Online Courses: <span ng-repeat="course in onlineCourses">{{course}}{{$last?'':','}}</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="row">
			<div class="col-xs-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<div class="center" schedule-pagination="displayOptions" schedule-pagination-callback="scrollToSchedules()"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div id="schedules"></div>
<script type='text/javascript' src='js/handlebars.js'></script>
<script type='text/javascript' src='js/translateFunctions.js'></script>
<? require "./inc/footer.inc"; ?>
