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
<!-- <script type='text/javascript' src='./js/reloadSchedule.js'></script>
<script type='text/javascript' src='./js/schedule.js'></script>
<script type='text/javascript' src='./js/jquery.timepicker.min.js'></script>-->
<div ng-controller="GenerateCtrl">
	<form novalidate id="scheduleForm" name="schedule" class="container" method="POST">
		<div class="col-md-8">
			<div class="panel panel-default form-horizontal" ng-controller="scheduleCoursesCtrl">
				<div class="panel-heading">
					<h2 class="panel-title">Select Courses</h2>
				</div>
				<div class="panel-body">
					<div id="scheduleCourses">
						<div dynamic-items="state.courses" colors="ui.colors" use-class="scheduleCourse" helpers="courses_helpers"></div>
					</div>
					<!-- <pre>{{state.courses | json}}</pre> -->
				</div>
				<div class="panel-footer">
					<input type="hidden" value="{{state.courses.length}}" name="courseCount" id="courseCount">
					<div class="row">
						<div class="col-md-3">
							<button class="btn btn-primary btn-block visible-xs visible-sm" type="button" ng-click="courses_helpers.add()">
								<i class="fa fa-plus"></i> Add Course
							</button>
							<span class="visible-md visible-lg">
								<button class="btn btn-primary" type="button" ng-click="courses_helpers.add()">
									<i class="fa fa-plus"></i> Add Course
								</button>
							</span>
						</div>
						<div class="col-md-5 col-xs-12">
							<div class="row">
								<label for="term" class="col-md-4 control-label">Term:</label>
								<div class="col-md-8">
									<?= getTermField('state.requestOptions.term', $CURRENT_QUARTER) ?>
								</div>
							</div>
						</div>
						<div class="visible-sm visible-xs">&nbsp;</div>
						<div class="col-md-4 col-xs-12">
							<button type="button" class="ng-class: {'btn-success': state.requestOptions.ignoreFull}; btn-default btn btn-block" ng-click="state.requestOptions.ignoreFull = !state.requestOptions.ignoreFull">{{state.requestOptions.ignoreFull?"Show":"Hide"}} filled up courses</button>
						</div>
					</div>
				</div>
			</div>
			<div>&nbsp;</div>
			<div>
				<div class="panel panel-default" ng-controller="nonCourseItemsCtrl">
					<div class="panel-heading">
						<h2 class="panel-title">Non-Course Schedule Items</h2>
					</div>
					<div class="panel-body">
						<div class="container row form-group repeat-item" ng-repeat="nonCourse in state.nonCourses">
							<div class="col-lg-2 col-md-12">
								<div class="container-fluid">
									<input autocomplete="off" id="nonCourses{{$index}}" class="form-control" ng-model="nonCourse.title" type="text" name="nonCourses{{$index}}"  placeholder="Title" />
								</div>
							</div>
							<div class="hidden-lg vert-spacer-static-md"></div>
							<div class="col-lg-5 col-md-6 col-sm-6">
								<div class="row form-inline">
									<div class="col-xs-12">
										<div class="form-group">
											<select id="options-start_time" ng-change="ensureCorrectEndTime($index)" class="form-control" ng-model="nonCourse.start_time" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys"><option value="">Start</option></select>
										</div>
										<div class="form-group"> to </div> 
										<div class="form-group">
											<select id="options-end_time" class="form-control" ng-model="nonCourse.end_time" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys | startFrom: ui.optionLists.times.keys.indexOf(nonCourse.start_time) + 1"><option value="">End</option></select>
										</div>
									</div>
								</div>
							</div>
							<div class="hidden-lg vert-spacer-static-md"></div>
							<div class="col-lg-4 col-sm-5">
								<div class="container-fluid">
									<div dow-select-fields="nonCourse.days"></div>
								</div>
							</div>
							<div class="hidden-md hidden-lg vert-spacer-static-md"></div>
							<div class="col-sm-1">
								<div class="container-fluid">
									<button type="button" class="btn btn-danger hidden-xs" ng-click="removeNonC($index)"><i class="fa fa-times"></i></button>
									<button type="button" class="btn btn-danger btn-block visible-xs" ng-click="removeNonC($index)"><i class="fa fa-times"></i> Delete</button>
								</div>
							</div>
						</div>
					</div>
					<div class="panel-footer">
						<button type="button" class="btn btn-default" ng-click="addNonC()">Add Item</button>
					</div>
				</div>
				<div class="panel panel-default" ng-controller="noCourseItemsCtrl">
					<div class="panel-heading">
						<h2 class="panel-title">Times You Don't Want Classes</h2>
					</div>
					<div class="panel-body">
						<div class="container row form-group repeat-item" ng-repeat="noCourse in state.noCourses">
							<div class="col-sm-6">
								<div class="row form-inline">
									<div class="col-xs-12">
										<div class="form-group">
											<select id="options-start_time" ng-change="ensureCorrectEndTime($index)" class="form-control" ng-model="noCourse.start_time" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys"><option value="">Start</option></select>
										</div>
										<div class="form-group"> to </div> 
										<div class="form-group">
											<select id="options-end_time" class="form-control" ng-model="noCourse.end_time" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys | startFrom: ui.optionLists.times.keys.indexOf(noCourse.start_time) + 1"><option value="">End</option></select>
										</div>
									</div>
								</div>
							</div>
							<div class="col-sm-5">
								<div class="container-fluid">
									<div dow-select-fields="noCourse.days"></div>
								</div>
							</div>
							<div class="hidden-md hidden-lg vert-spacer-static-md"></div>
							<div class="col-sm-1">
								<div class="container-fluid">
									<button type="button" class="btn btn-danger hidden-xs" ng-click="removeNoC($index)"><i class="fa fa-times"></i></button>
									<button type="button" class="btn btn-danger btn-block visible-xs" ng-click="removeNoC($index)"><i class="fa fa-times"></i> Delete</button>
								</div>
							</div>
						</div>
					</div>
					<div class="panel-footer">
						<button type="button" class="btn btn-default" ng-click="addNoC()">Add Time</button>
					</div>
				</div>
			</div>
			<input name="action" value="getMatchingSchedules" type="hidden"> <input type="hidden" value="true" name="verbose" id="verbose">
			<div class="center" role="toolbar">
				<div class="btn-group">
					<button type="button" class="btn-lg btn btn-primary btn-default" ng-click="generateSchedules()">Show Matching Schedules</button>
				</div>
				<div class="btn-group">
					<button type="button" class="btn-lg btn btn-default btn-danger" ng-click="resetState()">Reset</button>
				</div>
			</div>
		</div>
		<div class="col-md-4" ng-init="showCourseCart = true">
			<div class="visible-xs visible-sm vert-spacer-static-md"></div>
			<div class="panel panel-default course-cart">
				<div class="panel-heading">
					<h2 class="panel-title clearfix">
						Course Cart
						<button type="button" class="btn btn-xs btn-primary hidden-md hidden-lg pull-right" ng-click="showCourseCart = !showCourseCart">
							<i class="fa" ng-class="{'fa-angle-down': showCourseCart, 'fa-angle-up': !showCourseCart}"></i>
						</button>
					</h2>
				</div>
				<div class="panel-body" ng-class="{'hidden-xs':showCourseCart, 'hidden-sm': showCourseCart}">
					<div class="course-cart-window animate-show-hide" ng-switch="getSelectedCount() > 0">
						<ul ng-switch-when="true" class="list-group">
							<li class="list-group-item repeat-item course-cart-item" ng-style="{'border-left-color':course.color}" ng-if="course.sections.length > 0 && !course.sections[0].isError" ng-repeat="course in state.courses">
								<div class="btn-group pull-right">
									<button type="button" class="btn btn-danger" ng-click="courses_helpers.removeThis(course)">
										<i class="fa fa-minus"></i> <i class="fa fa-shopping-cart"></i>
									</button>
									<button type="button" class="btn btn-primary" ng-click="showCourseSections = !showCourseSections">
										<i class="fa" ng-class="{'fa-angle-down': !showCourseSections, 'fa-angle-up': showCourseSections}"></i>
									</button>
								</div>
								<h4 class="list-group-item-heading">{{course.search}}:</h4>
								<p class="list-group-item-text">{{getSelectedSectionCount(course)}} selected</p>
								<ul class="list-group" ng-if="showCourseSections">
									<li class="list-group-item repeat-item" ng-repeat="section in course.sections | filter:{selected: true}">
										<button type="button" class="btn pull-right btn-danger visible-md visible-lg" ng-click="section.selected = !section.selected">
											<i class="fa fa-minus"></i> <i class="fa fa-shopping-cart"></i>
										</button>
										<h4 class="list-group-item-heading">{{section.courseNum}}</h4>
										<p class="list-group-item-text">{{section.instructor}}</p>
										<button type="button" class="btn btn-danger btn-block visible-xs visible-sm" ng-click="section.selected = !section.selected">
											<i class="fa fa-minus"></i> <i class="fa fa-shopping-cart"></i>
										</button>
									</li>
								</ul>
							</li>
						</ul>
						<div class="alert" ng-switch-when="false">Add courses to your cart and make a schedule with them. They will show up here.</div>
					</div>
				</div>
				<div class="panel-footer" ng-class="{'hidden-xs':showCourseCart, 'hidden-sm': showCourseCart}">
					<button type="button" class="btn btn-primary btn-block" ng-click="generateSchedules()">Show Matching Schedules</button>
				</div>
			</div>
		</div>
	</form>
	<div id="master_schedule_results" ng-show="state.schedules.length > 0" ng-init="showOptions = true">
		<div class="container">
			<div class="visible-xs visible-sm form-group">
				<button class="btn btn-block btn-primary" ng-click="showOptions = !showOptions" type="button">
					<i class="fa" ng-class="{'fa-chevron-down':showOptions,'fa-chevron-up':!showOptions}"></i> Options
				</button>
			</div>
			<div ng-class="{'hidden-xs':showOptions, 'hidden-sm': showOptions}" class="row">
				<div class="col-xs-12">
					<div class="panel panel-default">
						<div class="panel-body">
							<div class="row form-inline">
								<div class="col-xs-12">
									<div class="form-group">
										<button class="hidden-xs hidden-sm btn btn-primary" ng-click="showDisplayOptions = !showDisplayOptions" type="button">
											<i class="fa" ng-class="{'fa-chevron-down':!showDisplayOptions,'fa-chevron-up':showDisplayOptions}"></i>
										</button>
									</div>
									<div class="form-group">Display from</div>
									<div class="form-group">
										<select id="options-start_time" ng-change="ensureCorrectEndTime()" class="form-control" ng-model="state.drawOptions.start_time" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys"></select>
									</div>
									<div class="form-group">to</div>
									<div class="form-group">
										<select id="options-end_time" class="form-control" ng-model="state.drawOptions.end_time" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys | startFrom: ui.optionLists.times.keys.indexOf(state.drawOptions.start_time) + 1"></select>
									</div>
									<div class="form-group">and from</div>
									<div class="form-group">
										<select id="options-start_day" ng-change="ensureCorrectEndDay()" class="form-control" ng-model="state.drawOptions.start_day" ng-options="ui.optionLists.days.indexOf(value) as value for (key, value) in ui.optionLists.days"></select>
									</div>
									<div class="form-group">to</div>
									<div class="form-group">
										<select id="options-end_day" class="form-control" ng-model="state.drawOptions.end_day" ng-options="ui.optionLists.days.indexOf(value) as value for (key, value) in ui.optionLists.days | startFrom: state.drawOptions.start_day"></select>
									</div>
									<div class="form-group pull-right" schedule-pagination="state.displayOptions" total-length="state.schedules.length"></div>
								</div>
							</div>
							<div class="visible-xs visible-sm">
								<button class="btn btn-block btn-primary" ng-click="showDisplayOptions = !showDisplayOptions" type="button">
									<i class="fa" ng-class="{'fa-chevron-down':!showDisplayOptions,'fa-chevron-up':showDisplayOptions}"></i> Advanced Options
								</button>
							</div>
							<div ng-show="showDisplayOptions" ng-init="showDisplayOptions = false">
								<div class="vert-spacer-static-md"></div>
								<div class="row form-horizontal">
									<div class="col-md-4">
										<div class="form-group">
											<label for="options-building_style" class="col-sm-4 control-label">Buildings</label>
											<div class="col-sm-8">
												<select id="options-building_style" class="form-control" ng-model="state.drawOptions.building_style">
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
													<label> <input id="options-fullscreen" type="checkbox" ng-model="state.displayOptions.fullscreen"> Fullscreen
													</label>
												</div>
											</div>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label for="displayOptions-pageSize" class="col-sm-4 control-label">Page Size</label>
											<div class="col-sm-8">
												<select id="displayOptions-pageSize" class="form-control" ng-model="state.displayOptions.pageSize">
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
		<div ng-class="{container: !state.displayOptions.fullscreen}">
			<div ng-class="{'col-sm-12': state.displayOptions.fullscreen}">
				<div class="row" ng-repeat="schedule in state.schedules | startFrom:state.displayOptions.currentPage*state.displayOptions.pageSize | limitTo:state.displayOptions.pageSize">
					<div class="col-md-12">
						<div class="panel panel-default">
							<div id="matchingSchedules" class="panel-heading">
								<h2 class="panel-title">Schedule {{state.schedules.indexOf(schedule) + 1}}:</h2>
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
												<button type="button" class="btn btn-block btn-primary">
													Share to <i class="fa fa-facebook"></i>
												</button>
												<button type="button" class="btn btn-block btn-primary">
													Share to <i class="fa fa-google-plus"></i>
												</button>
												<button type="button" class="btn btn-block btn-primary">
													Share to <i class="fa fa-twitter"></i>
												</button>
											</div>
										</div>
									</div>
								</div>
								<div ng-if="hiddenCourses.length > 0" class="row">
									<div class="col-xs-12">
										<div class="alert alert-warning">
											<strong>Warning!</strong> The following course{{hiddenCourses.length != 1?'s are':' is'}} not displayed: <span ng-repeat="course in hiddenCourses">{{course}}{{$last?'':', '}}</span>
										</div>
									</div>
								</div>
								<div ng-if="onlineCourses.length > 0" class="row">
									<div class="col-xs-12">
										<div class="alert alert-info">
											Online Course{{onlineCourses.length != 1?'s':''}}: <span ng-repeat="course in onlineCourses">{{course}}{{$last?'':','}}</span>
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
							<div class="center" schedule-pagination="state.displayOptions" total-length="state.schedules.length" schedule-pagination-callback="scrollToSchedules()"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<? require "./inc/footer.inc"; ?>
