angular.module('sm').directive('schedule', function($timeout, $filter) {
	function Schedule(scope) {
		this.scope = scope;
		this.drawOptions = {
			parsedTime: {}
		};
		this.courseDrawIndex = 0;
	}
	Schedule.prototype.init = function() {
		
		this.drawOptions.parsedTime.start = parseInt(this.scope.state.drawOptions.startTime);
		this.drawOptions.parsedTime.end = parseInt(this.scope.state.drawOptions.endTime);
		if(!this.drawOptions.parsedTime.start || !this.drawOptions.parsedTime.end) return false;
        
		this.scope.hiddenCourses = [];
		this.scope.onlineCourses = [];
		this.scope.scheduleItems = [];
		
		return true;
	};
	Schedule.prototype.drawGrid = function() {
		
		var hourArray = [];
        for(var time = this.drawOptions.parsedTime.start; time < this.drawOptions.parsedTime.end; time += 60) {
    		// Calculate the label
    		var hourLabel = Math.floor(time / 60);
    		if(hourLabel > 12) { hourLabel -= 12; }
    		else if(hourLabel == 0) { hourLabel = 12; }

    		if(time >= 720) { ap = " PM"; } else { ap = " AM"; }	
    		
    		hourArray.push(String(hourLabel) + ap);
    	}

		// Generate grid
        var numDays = this.scope.state.drawOptions.endDay - this.scope.state.drawOptions.startDay + 1;
		// Set up grid
		var rawHeight = (hourArray.length * 40),
		globalOpts = {
			height: rawHeight + 25,
			hoursWidth: 5
		},
		rawDayWidth = 100 / numDays,
		dayPadding = 1,
		dayOpts = {
			num: numDays,
			rawWidth: rawDayWidth,
			width: (rawDayWidth - (globalOpts.hoursWidth / numDays) - (2 * dayPadding)) + '%',
			padding: dayPadding,
			height: rawHeight
		};
		
		var dayArray = [];
		//Generate days
		
		var dayIndex = this.scope.state.drawOptions.startDay;
		for(var i=0; i < numDays; i++) {
			var offset = globalOpts.hoursWidth + ( 2 * dayOpts.padding) + ((dayOpts.rawWidth - dayOpts.padding) * i);
			dayArray.push({
				name: this.scope.ui.optionLists.days[dayIndex],
				offset: offset + '%',
			});
			dayIndex++;
		}
		
        
		//Set the this.scope variable
		this.scope.grid = {
			hours: hourArray,
			days: dayArray,
			opts: {
				height: globalOpts.height,
				hoursWidth: globalOpts.hoursWidth,
				daysWidth: dayOpts.width,
				daysHeight: dayOpts.height,
				pixelAlignment:''
			}
		};
		return true;
	};
	
	Schedule.prototype.drawCourse = function(course, index) {
		var grid = this.scope.grid;
		var startTime = this.drawOptions.parsedTime.start;
		var endTime = this.drawOptions.parsedTime.end;
		
		// Using the old logic here because it works just as good as anything
		
		for(var t = 0; t < course.times.length; t++) {
			// Make it easier for the developer
			var time = course.times[t];
			// Skip times that aren't part of the displayed days
			if(time.day < this.scope.state.drawOptions.startDay || time.day > this.scope.state.drawOptions.endDay) {
				if(this.scope.hiddenCourses.indexOf(course) == -1) {
					this.scope.hiddenCourses.push(course);
				}
				continue;
			}
			
			var courseStart = time.start,
			courseEnd = time.end,
			shorten = 0;

			// Skip times that aren't part of the displayed hours
			if(courseStart < startTime || courseStart > endTime || courseEnd > endTime) {
				// Shorten up the boxes of times that extend into
				// the visible spectrum
				if(courseStart < startTime && courseEnd > startTime) {
					courseStart = startTime;
					shorten = -1;
				} else if(courseEnd > endTime && courseStart < endTime) {
					courseEnd = endTime;
					shorten = 1;
				} else {
					// The course is completely hidden
					if(this.scope.hiddenCourses.indexOf(course) == -1) {
						this.scope.hiddenCourses.push(course);
					}
					continue;
				}
			}
			
			// Calculate the height
			var timeHeight = parseInt(courseEnd) - parseInt(courseStart);
			timeHeight = timeHeight / 30;
			timeHeight = Math.ceil(timeHeight);
			timeHeight = (timeHeight * 20);

			// Calculate the top offset
			var timeTop = parseInt(courseStart) - startTime;
			timeTop = timeTop / 30;
			timeTop = Math.floor(timeTop);
			timeTop = timeTop * 20;
			timeTop += 19;					// Offset for the header
			
			if(course.courseNum != 'non') {
				var location = ((this.scope.state.drawOptions.bldgStyle == 'code') ? time.bldg.code : time.bldg.number) + "-" + time.room,
				instructor = course.instructor,
				courseNum = course.courseNum;
			} else {
				var location = '',
				instructor = '',
				courseNum = '';
			}
			this.scope.scheduleItems.push({
				title:course.title,
				content: {
				    location: location ,
				    courseNum: courseNum,
				    instructor: instructor
				},
				boundry: {
					x: grid.days[time.day - this.scope.state.drawOptions.startDay].offset,
					y: timeTop,
					shorten: shorten,
					width: grid.opts.daysWidth,
					height:timeHeight
				},
				color: this.scope.ui.colors[course.courseIndex?(course.courseIndex - 1):this.courseDrawIndex % 10]
			});
			
		}
	};
	
	Schedule.prototype.drawCourses = function() {
		this.courseDrawIndex = 0;
		for(var coursesIndex = 0, coursesLength = this.scope.schedule.length; coursesIndex < coursesLength; coursesIndex++) {
			var course = this.scope.schedule[coursesIndex];
			this.courseDrawIndex++;
			if(course.online) {
				this.scope.onlineCourses.push(course);
			} else if(course.times != undefined) {
				this.drawCourse(course);
			}
		}
	};
	
	Schedule.prototype.draw = function() {
		this.drawGrid()
		this.drawCourses();
	};

	return {
		restrict: 'A',
		templateUrl: '/assets/prod/modules/sm/Schedule/templates/scheduleitem.min.html',
		link: {
			pre: function(scope, elm, attrs) {
				scope.scheduleController = new Schedule(scope);
				scope.itemEnter = function($event) {
					$target = $($event.target);
					$scope = $target.scope();
					if($scope.item.boundry.height < 70) {
						$scope.item.boundry.orig_height = $scope.item.boundry.height;
						$scope.item.boundry.height = 70;
					}
				};
				scope.itemLeave = function($event) {
					$target = $($event.target);
					$scope = $target.scope();
					if($scope.item.boundry.orig_height) {
						$scope.item.boundry.height = $scope.item.boundry.orig_height;
					}
				};
				
				if(typeof attrs.existing === "undefined") {
					scope.saveAction = "create";
				} else {
					scope.saveAction = "fork";
				}
				
				if(typeof attrs.print === "undefined") {
					scope.print = false;
				} else {
					scope.print = true;
				}
				
			},
			post: function(scope, elm) {
				scope.$watchCollection('state.drawOptions', function() {	
					if(scope.scheduleController.init()) {
						// Only redraw if valid options
						scope.scheduleController.draw();
					
						
						// Fix the pixel issues after DOM updates (not on Chrome)
						if(typeof window.chrome == 'undefined') {
							$timeout(function() {
								var offset = elm.find("svg").offset(),
								vert = 1 - parseFloat('0.'+('' + offset.top).split('.')[1]);
								horz = 1 - parseFloat('0.'+('' + offset.left).split('.')[1]);
								scope.grid.opts.pixelAlignment ='translate('+horz+','+vert+')';
							},10,true);
						}
					}
				});
			}
		}
	};
});