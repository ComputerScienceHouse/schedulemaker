angular.module('sm').directive('schedule', function ($timeout, $filter) {
  function Schedule (scope) {
    this.scope = scope
    this.drawOptions = {}
    this.courseDrawIndex = 0
  }

  Schedule.prototype.init = function (options) {
    this.drawOptions = options
    /* this.drawOptions.parsedTime = {
      start: parseInt(options.startTime),
      end: parseInt(options.endTime)
    }; */
    if ((!this.drawOptions.startTime && this.drawOptions.startTime !== 0) || !this.drawOptions.endTime) return false
    this.scope.hiddenCourses = []
    this.scope.onlineCourses = []
    this.scope.scheduleItems = []
    this.scope.totalCredits = 10

    return true
  }
  Schedule.prototype.drawGrid = function () {
    const hourArray = []
    let ap: string
    for (let time = +this.drawOptions.startTime; time < +this.drawOptions.endTime; time += 60) {
      // Calculate the label
      let hourLabel = Math.floor(time / 60)
      if (hourLabel > 12) {
        hourLabel -= 12
      } else if (hourLabel === 0) {
        hourLabel = 12
      }

      if (time >= 720) {
        ap = ' PM'
      } else {
        ap = ' AM'
      }

      hourArray.push(String(hourLabel) + ap)
    }

    // Generate grid
    const numDays = this.drawOptions.endDay - this.drawOptions.startDay + 1
    // Set up grid
    const rawHeight = (hourArray.length * 40)
    const globalOpts = {
      height: rawHeight + 25,
      hoursWidth: 5
    }
    const rawDayWidth = 100 / numDays
    const dayPadding = 1
    const dayOpts = {
      num: numDays,
      rawWidth: rawDayWidth,
      width: (rawDayWidth - (globalOpts.hoursWidth / numDays) - (2 * dayPadding)) + '%',
      padding: dayPadding,
      height: rawHeight
    }

    const dayArray = []
    // Generate days

    let dayIndex = this.drawOptions.startDay
    for (let i = 0; i < numDays; i++) {
      const offset = globalOpts.hoursWidth + (2 * dayOpts.padding) + ((dayOpts.rawWidth - dayOpts.padding) * i)
      dayArray.push({
        name: this.scope.ui.optionLists.days[dayIndex],
        offset: offset + '%'
      })
      dayIndex++
    }

    // Set the this.scope variable
    this.scope.grid = {
      hours: hourArray,
      days: dayArray,
      opts: {
        height: globalOpts.height,
        hoursWidth: globalOpts.hoursWidth,
        daysWidth: dayOpts.width,
        daysHeight: dayOpts.height,
        pixelAlignment: ''
      }
    }
    return true
  }

  Schedule.prototype.drawCourse = function (course, index) {
    const grid = this.scope.grid
    const startTime = +this.drawOptions.startTime
    const endTime = +this.drawOptions.endTime

    // Using the old logic here because it works just as good as anything

    for (let t = 0; t < course.times.length; t++) {
      // Make it easier for the developer
      const time = course.times[t]
      // Skip times that aren't part of the displayed days
      if (time.day < this.drawOptions.startDay || time.day > this.drawOptions.endDay) {
        if (this.scope.hiddenCourses.indexOf(course) === -1) {
          this.scope.hiddenCourses.push(course)
        }
        continue
      }

      let courseStart = time.start
      let courseEnd = time.end
      let shorten = 0

      // Skip times that aren't part of the displayed hours
      if (courseStart < startTime || courseStart > endTime || courseEnd > endTime) {
        // Shorten up the boxes of times that extend into
        // the visible spectrum
        if (courseStart < startTime && courseEnd > startTime) {
          courseStart = startTime
          shorten = -1
        } else if (courseEnd > endTime && courseStart < endTime) {
          courseEnd = endTime
          shorten = 1
        } else {
          // The course is completely hidden
          if (this.scope.hiddenCourses.indexOf(course) === -1) {
            this.scope.hiddenCourses.push(course)
          }
          continue
        }
      }

      // Calculate the height
      let timeHeight = parseInt(courseEnd) - parseInt(courseStart)
      timeHeight = timeHeight / 7.5
      timeHeight = Math.ceil(timeHeight)

      timeHeight = (timeHeight * 5)

      // Calculate the top offset
      let timeTop = parseInt(courseStart) - startTime
      timeTop = timeTop / 7.5
      timeTop = Math.floor(timeTop)
      timeTop = timeTop * 5
      timeTop += 19 // Offset for the header

      // Add Padding for Formatting Time
      function pad (d) {
        // Allows for time to display as example: 12:04 instead of 12:4
        return (d < 10) ? '0' + d.toString() : d.toString()
      }

      // Format Start time
      let hourLabel = Math.floor(courseStart / 60)
      if (hourLabel > 12) {
        hourLabel -= 12
      } else if (hourLabel === 0) {
        hourLabel = 12
      }
      let minuteLabel = (courseStart % 60)
      let ap: string
      minuteLabel = pad(minuteLabel)
      if (courseStart >= 720) {
        ap = ' PM'
      } else {
        ap = ' AM'
      }

      // Set event data
      let location = ''
      let instructor = ''
      let courseNum = ''
      if (course.courseNum !== 'non') {
        location = ((this.drawOptions.bldgStyle === 'code') ? time.bldg.code : time.bldg.number) + '-' + time.room + ' (' + (String(hourLabel) + ':' + String(minuteLabel) + ' ' + ap) + ')'
        instructor = course.instructor
        courseNum = course.courseNum
      }
      course.title = course.title.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/\\\\/, '\\')
      this.scope.scheduleItems.push({
        title: course.title,
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
          height: timeHeight
        },
        color: this.scope.ui.colors[course.courseIndex ? (course.courseIndex - 1) : this.courseDrawIndex - 1 % 10]
      })
    }
  }

  Schedule.prototype.drawCourses = function () {
    this.courseDrawIndex = 0
    this.scope.totalCredits = 0
    for (let coursesIndex = 0, coursesLength = this.scope.schedule.length; coursesIndex < coursesLength; coursesIndex++) {
      const course = this.scope.schedule[coursesIndex]
      this.courseDrawIndex++
      if (course.online && !course.hasOwnProperty('times')) {
        this.scope.onlineCourses.push(course)
      } else if (course.hasOwnProperty('times')) {
        this.drawCourse(course)
      }
      // console.log(course);
      this.scope.totalCredits += (course.hasOwnProperty('credits') ? +course.credits : 0)
    }
  }

  Schedule.prototype.draw = function () {
    this.drawGrid()
    this.drawCourses()
  }

  return {
    restrict: 'A',
    templateUrl: '/<%=modulePath%>Schedule/templates/scheduleitem.min.html',
    link: {
      pre: function (scope, elm, attrs) {
        if (scope.schedule.length > 0) {
          scope.initialIndex = scope.schedule[0].initialIndex
        }

        scope.scheduleController = new Schedule(scope)
        scope.itemEnter = function ($event) {
          const $target = $($event.target)
          const $scope = $target.scope()
          if ($scope.item.boundry.height < 70) {
            $scope.item.boundry.orig_height = $scope.item.boundry.height
            $scope.item.boundry.height = 70
          }
        }
        scope.itemLeave = function ($event) {
          const $target = $($event.target)
          const $scope = $target.scope()
          if ($scope.item.boundry.orig_height) {
            $scope.item.boundry.height = $scope.item.boundry.orig_height
          }
        }

        if (typeof attrs.existing === 'undefined') {
          scope.saveAction = 'create'
        } else {
          scope.saveAction = 'fork'
        }

        if (typeof attrs.print === 'undefined') {
          scope.print = false
        } else {
          scope.print = true
        }
      },
      post: function (scope, elm) {
        const update = function (options) {
          if (scope.scheduleController.init(options)) {
            scope.scheduleDrawOptions = options
            // Only redraw if valid options
            scope.scheduleController.draw()

            // Fix pixel alignment issues
            $timeout(function () {
              const offset = elm.find('svg').offset()
              const vert = 1 - parseFloat('0.' + ('' + offset.top).split('.')[1])
              const horz = 1 - parseFloat('0.' + ('' + offset.left).split('.')[1])
              scope.grid.opts.pixelAlignment = 'translate(' + horz + ',' + vert + ')'

              // Toggle showing and hiding svgs, which forces a redraw
              const svg = $(elm).find('svg')
              svg.hide()
              setTimeout(function () {
                svg.show()
              }, 0)
            }, 10, true)
          }
        }

        if (!scope.overrideDrawOptions) {
          scope.$watchCollection('state.drawOptions', update)
        } else {
          scope.$watchCollection('overrideDrawOptions', update)
        }
      }
    }
  }
})
