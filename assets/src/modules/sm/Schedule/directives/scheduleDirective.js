angular.module('sm').directive('schedule', function($timeout, $filter) {
	function Schedule(scope) {
		this.scope = scope;
		this.drawOptions = {};
		this.courseDrawIndex = 0;
	}
	Schedule.prototype.init = function(options) {
		this.drawOptions = options;
		/*this.drawOptions.parsedTime = {
			start: parseInt(options.startTime),
			end: parseInt(options.endTime)
		};*/
		if((!this.drawOptions.startTime && this.drawOptions.startTime !== 0) || !this.drawOptions.endTime) return false;
		this.scope.hiddenCourses = [];
		this.scope.onlineCourses = [];
		this.scope.scheduleItems = [];
		this.scope.totalCredits = 10;
		
		return true;
	};
	Schedule.prototype.drawGrid = function() {
		
		var hourArray = [];
        for(var time = +this.drawOptions.startTime; time < +this.drawOptions.endTime; time += 60) {
    		// Calculate the label
    		var hourLabel = Math.floor(time / 60);
    		if(hourLabel > 12) { hourLabel -= 12; }
    		else if(hourLabel == 0) { hourLabel = 12; }

    		if(time >= 720) { ap = " PM"; } else { ap = " AM"; }	
    		
    		hourArray.push(String(hourLabel) + ap);
    	}

		// Generate grid
        var numDays = this.drawOptions.endDay - this.drawOptions.startDay + 1;
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
		
		var dayIndex = this.drawOptions.startDay;
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
		var startTime = +this.drawOptions.startTime;
		var endTime = +this.drawOptions.endTime;
		
		// Using the old logic here because it works just as good as anything
		
		for(var t = 0; t < course.times.length; t++) {
			// Make it easier for the developer
			var time = course.times[t];
			// Skip times that aren't part of the displayed days
			if(time.day < this.drawOptions.startDay || time.day > this.drawOptions.endDay) {
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
			timeHeight = timeHeight / 15;
			timeHeight = Math.ceil(timeHeight);

			timeHeight = (timeHeight * 10);

			// Calculate the top offset
			var timeTop = parseInt(courseStart) - startTime;
			timeTop = timeTop / 30;
			timeTop = Math.floor(timeTop);
			timeTop = timeTop * 20;
			timeTop += 19;					// Offset for the header
			
			if(course.courseNum != 'non') {
				var location = ((this.drawOptions.bldgStyle == 'code') ? time.bldg.code : time.bldg.number) + "-" + time.room,
				instructor = course.instructor,
				courseNum = course.courseNum;
			} else {
				var location = '',
				instructor = '',
				courseNum = '';
			}
			this.scope.scheduleItems.push({
				title: course.title.replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">"),
				content: {
				    location: location,
				    courseNum: courseNum,
				    instructor: instructor
				},
				boundry: {
					x: grid.days[time.day - this.drawOptions.startDay].offset,
					y: timeTop,
					shorten: shorten,
					width: grid.opts.daysWidth,
					height:timeHeight
				},
				color: this.scope.ui.colors[course.courseIndex?(course.courseIndex - 1):this.courseDrawIndex - 1 % 10]
			});
		}
	};
	
	Schedule.prototype.drawCourses = function() {
		this.courseDrawIndex = 0;
		this.scope.totalCredits = 0;
		for(var coursesIndex = 0, coursesLength = this.scope.schedule.length; coursesIndex < coursesLength; coursesIndex++) {
			var course = this.scope.schedule[coursesIndex];
			this.courseDrawIndex++;
			if(course.online && !course.hasOwnProperty('times')) {
				this.scope.onlineCourses.push(course);
			} else if(course.hasOwnProperty('times')) {
				this.drawCourse(course);
			}
			//console.log(course);
			this.scope.totalCredits+= (course.hasOwnProperty('credits')? +course.credits: 0);
		}
	};
	
	Schedule.prototype.draw = function() {
		this.drawGrid()
		this.drawCourses();
	};

	return {
		restrict: 'A',
		templateUrl: '/<%=modulePath%>Schedule/templates/scheduleitem.min.html',
		link: {
			pre: function(scope, elm, attrs) {
				scope.scheduleController = new Schedule(scope);
				scope.itemEnter = function($event) {
					var $target = $($event.target),
						$scope = $target.scope();
					if($scope.item.boundry.height < 70) {
						$scope.item.boundry.orig_height = $scope.item.boundry.height;
						$scope.item.boundry.height = 70;
					}
				};
				scope.itemLeave = function($event) {
					var $target = $($event.target),
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
				
				var update = function(options) {
					if(scope.scheduleController.init(options)) {
						// Only redraw if valid options
						scope.scheduleController.draw();
					
						// Fix pixel alignment issues
						$timeout(function() {
							var offset = elm.find("svg").offset(),
							vert = 1 - parseFloat('0.'+('' + offset.top).split('.')[1]);
							horz = 1 - parseFloat('0.'+('' + offset.left).split('.')[1]);
							scope.grid.opts.pixelAlignment ='translate('+horz+','+vert+')';

							// Toggle showing and hiding svgs, which forces a redraw
							var svg = $(elm).find('svg');
								svg.hide();
								setTimeout(function() {
								svg.show();
							}, 0);
						},10,true);
					}
				};
				
				if(!scope.overrideDrawOptions) {
					scope.$watchCollection('state.drawOptions', update);
				} else {
					scope.$watchCollection('overrideDrawOptions', update);
				}
			}
		}
	};
});