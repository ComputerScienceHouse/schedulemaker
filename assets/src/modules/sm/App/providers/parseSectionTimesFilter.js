angular.module('sm').filter('parseSectionTimes', function($filter) {
	var translateDay = $filter('translateDay');
	return function(times, byLocation) {
		if(typeof times != 'object') return times;
		var parsedTimes = [];
		for(var e = 0; e < times.length; ++e) {
            // Search the existing list of times to see if a match exists
            var found = false;
            var time = times[e];
            
            if(byLocation && typeof time.bldg != "undefined") {
        		time.location =  time.bldg.code
                + "(" + time.bldg.number + ")"
                + "-" + time.room;
        	} else {
        		time.location = false;
        	}
            
            for(var f = 0; f < parsedTimes.length; ++f) {
                if(parsedTimes[f].start == time.start && parsedTimes[f].end == time.end && parsedTimes[f].location == time.location) {
                    found = f;
                }
            }

            // If a match was found, add the day to it, otherwise add a new time
            if(found !== false) {
            	parsedTimes[found].days += ", " + translateDay(time.day);
            } else {
            	parsedTimes.push({
                    start: time.start,
                    end:   time.end,
                    days:  translateDay(time.day),
                    location: time.location
                });
            }
        }
		return parsedTimes;
	};
});