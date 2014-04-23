angular.module('sm').controller("SchedulePrintController", function($scope, $location, localStorage) {
	
	if($scope.schedule) {	
		var pTerm ='' + $scope.state.requestOptions.term;
		
		var year = parseInt(pTerm.substring(0,4));
        var term = pTerm.substring(4);
        if(year >= 2013) {
            switch(term) {
                case '1': term = "Fall"; break;
                case '3': term = "Winter Intersession"; break;
                case '5': term = "Spring"; break;
                case '8': term = "Summer"; break;
                default:  term = "Unknown";
            }
        } else {
            switch(term) {
                case '1': term = "Fall"; break;
                case '2': term = "Winter"; break;
                case '3': term = "Spring"; break;
                case '4': term = "Summer"; break;
                default:  term = "Unknown";
            }
        }
        
        $scope.heading = "My " + year + "-" + (year+1) + " " + term  + " Schedule";
        
        $scope.printTheme = 'woc';
        
        $scope.printThemeOptions = [{
        	value: 'woc',
        	label: "Modern Colors"
        }, {
        	value: 'bow',
        	label: "Classic B&W"
        }, {
        	value: 'gow',
        	label: "Classic Greyscale"
        }, {
        	value: 'boc',
        	label: "Black Text & Colors"
        }];
	}
	
	localStorage.setItem("reloadSchedule", null);
	
	$scope.printFn = window.print.bind(window);
	
	$scope.globalUI.layoutClass = "print";
});