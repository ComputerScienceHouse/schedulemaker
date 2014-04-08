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
<div ng-controller="GenerateCtrl">
	<form novalidate id="scheduleForm" name="schedule" class="container">
		<div class="row">
			<div class="col-md-8">
				<div class="panel panel-default form-horizontal" ng-controller="scheduleCoursesCtrl">
					<div class="panel-heading">
						<div class="row form-horizontal">
							<div class="col-sm-4">
								<h2 class="panel-title control-label pull-left">Select Courses</h2>
							</div>
							<div class="col-sm-8">
								<div class="row">
									<label class="col-sm-6 control-label" for="term">Term:</label>
									<div class="col-sm-6">
									<?= getTermField("state.requestOptions.term"); ?>
								</div>
								</div>
							</div>
						</div>
					</div>
					<div class="panel-body">
						<div id="scheduleCourses">
							<div dynamic-items="state.courses" colors="ui.colors" use-class="scheduleCourse" helpers="courses_helpers"></div>
						</div>
					</div>
					<div class="panel-footer">
						<input type="hidden" value="{{state.courses.length}}" name="courseCount" id="courseCount">
						<div class="row">
							<div class="col-md-4 col-xs-6">
								<button type="button" class="ng-class: {'btn-success': state.requestOptions.ignoreFull}; btn-default btn btn-block" ng-click="state.requestOptions.ignoreFull = !state.requestOptions.ignoreFull">
									<i class="fa" ng-class="{'fa-check-square-o': state.requestOptions.ignoreFull, 'fa-square-o': !state.requestOptions.ignoreFull}"></i> Ignore full
								</button>
							</div>
							<div class="col-md-4 col-md-offset-4 col-xs-6">
								<button class="btn btn-primary btn-block" type="button" ng-click="courses_helpers.add()" title="Shortcut: Enter">
									<i class="fa fa-plus"></i> Add Course
								</button>
							</div>
						</div>
					</div>
				</div>
				<div>&nbsp;</div>
				<div>
					<div class="panel panel-default panel-control-overlap" ng-controller="nonCourseItemsCtrl">
						<div class="panel-heading form-horizontal">
							<div class="form-horizontal row">
								<div class="col-xs-12">
									<h2 class="panel-title">Non-Course Schedule Items</h2>
								</div>
							</div>
						</div>
						<div class="panel-body" ng-show="state.nonCourses.length > 0">
							<div class="container row form-group repeat-item" ng-repeat="nonCourse in state.nonCourses">
								<div class="col-lg-2 col-md-12">
									<div class="container-fluid">
										<input autocomplete="off" id="nonCourses{{$index}}" class="form-control" ng-model="nonCourse.title" type="text" name="nonCourses{{$index}}" placeholder="Title" />
									</div>
								</div>
								<div class="hidden-lg vert-spacer-static-md"></div>
								<div class="col-lg-5 col-md-6 col-sm-6">
									<div class="row form-inline">
										<div class="col-xs-12">
											<div class="form-group inline-sm">
												<select id="options-startTime" ng-change="ensureCorrectEndTime($index)" class="form-control" ng-model="nonCourse.startTime" ng-options="key as ui.optionLists.timesHalfHours.values[key] for key in ui.optionLists.timesHalfHours.keys"><option value="">Start</option></select>
											</div>
											<div class="form-group inline-sm">to</div>
											<div class="form-group inline-sm">
												<select id="options-endTime" class="form-control" ng-model="nonCourse.endTime" ng-options="key as ui.optionLists.timesHalfHours.values[key] for key in ui.optionLists.timesHalfHours.keys | startFrom: ui.optionLists.timesHalfHours.keys.indexOf(nonCourse.startTime) + 1"><option value="">End</option></select>
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
										<button type="button" class="btn btn-danger hidden-xs" ng-click="removeNonC($index)">
											<i class="fa fa-times"></i>
										</button>
										<button type="button" class="btn btn-danger btn-block visible-xs" ng-click="removeNonC($index)">
											<i class="fa fa-times"></i> Delete
										</button>
									</div>
								</div>
							</div>
						</div>
						<div class="panel-footer">
							<div class="row">
								<div class="col-md-4 col-md-offset-8">
									<button type="button" class="btn btn-block btn-primary" ng-click="addNonC()">
										<i class="fa fa-plus"></i> Add Item
									</button>
								</div>
							</div>
						</div>
					</div>
					<div class="panel panel-default panel-control-overlap" ng-controller="noCourseItemsCtrl">
						<div class="panel-heading">
							<div class="form-horizontal row">
								<div class="col-xs-12">
									<h2 class="panel-title">Times You Don't Want Classes</h2>
								</div>
							</div>
						</div>
						<div class="panel-body" ng-show="state.noCourses.length > 0">
							<div class="container row form-group repeat-item" ng-repeat="noCourse in state.noCourses">
								<div class="col-sm-6">
									<div class="row form-inline">
										<div class="col-xs-12">
											<div class="form-group inline-sm">
												<select id="options-startTime" ng-change="ensureCorrectEndTime($index)" class="form-control" ng-model="noCourse.startTime" ng-options="key as ui.optionLists.timesHalfHours.values[key] for key in ui.optionLists.timesHalfHours.keys"><option value="">Start</option></select>
											</div>
											<div class="form-group inline-sm">to</div>
											<div class="form-group inline-sm">
												<select id="options-endTime" class="form-control" ng-model="noCourse.endTime" ng-options="key as ui.optionLists.timesHalfHours.values[key] for key in ui.optionLists.timesHalfHours.keys | startFrom: ui.optionLists.timesHalfHours.keys.indexOf(noCourse.startTime) + 1"><option value="">End</option></select>
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
										<button type="button" class="btn btn-danger hidden-xs" ng-click="removeNoC($index)">
											<i class="fa fa-times"></i>
										</button>
										<button type="button" class="btn btn-danger btn-block visible-xs" ng-click="removeNoC($index)">
											<i class="fa fa-times"></i> Delete
										</button>
									</div>
								</div>
							</div>
						</div>
						<div class="panel-footer">
							<div class="row">
								<div class="col-md-4 col-md-offset-8">
									<button type="button" class="btn btn-primary btn-block" ng-click="addNoC()">
										<i class="fa fa-plus"></i> Add Time
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="btn-group">
					<button type="button" class="btn btn-lg btn-danger pull-left btn-xs-block dropdown-toggle" data-toggle="dropdown">
						<i class="fa fa-times"></i> Reset... <span class="caret"></span>
					</button>
					<ul class="dropdown-menu" role="menu">
<!-- 						<li><a ng-click="resetGenerate()" href="#">Current Form Fields</a></li> -->
						<li><a ng-click="resetState()" href="#">Saved Session</a></li>
					</ul>
				</div>
				<button type="button" class="pull-right btn-lg btn btn-primary btn-xs-block" loading-button="generationStatus" loading-text="Generating..." ng-click="generateSchedules()" title="Shortcut: Ctrl + Enter"> Show Matching Schedules <i class="fa fa-chevron-right"></i></button>
				<div class="vert-spacer-static-md"></div>
				<div ng-show="!!resultError">
					<div class="alert alert-danger">
						<button type="button" class="close" aria-hidden="true" ng-click="resultError = null">
							<i class="fa fa-times"></i>
						</button>
						<i class="fa fa-exclamation-circle"></i> {{resultError}}
					</div>
				</div>
			</div>
			<div class="col-md-4 pinned-track" ng-init="showCourseCart = true">
				<div course-cart></div>
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
										<select id="options-startTime" ng-change="ensureCorrectEndTime()" class="form-control" ng-model="state.drawOptions.startTime" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys"></select>
									</div>
									<div class="form-group">to</div>
									<div class="form-group">
										<select id="options-endTime" class="form-control" ng-model="state.drawOptions.endTime" ng-options="key as ui.optionLists.times.values[key] for key in ui.optionLists.times.keys | startFrom: ui.optionLists.times.keys.indexOf(state.drawOptions.startTime) + 1"></select>
									</div>
									<div class="form-group">and from</div>
									<div class="form-group">
										<select id="options-startDay" ng-change="ensureCorrectEndDay()" class="form-control" ng-model="state.drawOptions.startDay" ng-options="ui.optionLists.days.indexOf(value) as value for (key, value) in ui.optionLists.days"></select>
									</div>
									<div class="form-group">to</div>
									<div class="form-group">
										<select id="options-endDay" class="form-control" ng-model="state.drawOptions.endDay" ng-options="ui.optionLists.days.indexOf(value) as value for (key, value) in ui.optionLists.days | startFrom: state.drawOptions.startDay"></select>
									</div>
									<div class="form-group pull-right" pagination-controls="state.displayOptions" pagination-length="state.schedules.length"></div>
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
											<label for="options-bldgStyle" class="col-sm-4 control-label">Buildings:</label>
											<div class="col-sm-8">
												<select id="options-bldgStyle" class="form-control" ng-model="state.drawOptions.bldgStyle">
													<option value="code">Codes (eg. GOL)</option>
													<option value="number">Number (eg. 70)</option>
												</select>
											</div>
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group hidden-xs">
											<label for="options-fullscreen" class="col-sm-4 control-label">Width:</label>
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
											<label for="displayOptions-pageSize" class="col-sm-4 control-label">Page Size:</label>
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
					<div class="col-md-12" schedule></div>
				</div>
			</div>
		</div>
		<div class="container">
			<div class="row">
				<div class="col-xs-12">
					<div class="panel panel-default">
						<div class="panel-heading">
							<div class="center" pagination-controls="state.displayOptions" pagination-length="state.schedules.length" pagination-callback="scrollToSchedules()"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<? require "./inc/footer.inc"; ?>
