'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:EventsCtrl
 * @description
 * # EventsCtrl
 * Controller of the eatApp
 */
app.controller('SearchCtrl', function ($scope, SEARCH, $routeParams, BOOK, $location) {


	$scope.search = {};
	$scope.events = [];
	$scope.persons = 1;
    $scope.options = {};
    $scope.offset = 0;
    $scope.stopLoad = false;
    $scope.param = {
		lat: $routeParams.lat,
		lng: $routeParams.lng,
		title: $routeParams.search,
		offset:0,
		limit: 9
	}
	var getEvents = true;


	$scope.param.distance = $routeParams.distance;



    $scope.createSlots = function (slots) {
        var arr = [];
        for (var i=1; i<=slots; i++) {
            arr.push(i);
        }
        return arr;
    }

    $scope.bookEvent = function(event, guests) {
        BOOK.save({"event": event.id, "persons": guests}, function(data) {
            if (data.status) {
               //console.log(data) ;
               $location.path("/book");
            }
        });
        
    };

    $scope.searchEvents = function () {
	    SEARCH.get($scope.param, function (data) {
	    	if (data.status) {
	    		$scope.events=data.events;
	    		$scope.options = data.options;
	    		$scope.offset = $scope.options.limit;
	    		$scope.stopLoad = false;
	    		getEvents = true;
                if (!data.events.length && !$scope.options.offset) {
                	$scope.noEvents = true;
                	$scope.param = {
						lat: 0,
						lng: 0,
						title: '',
						offset: 0,
						limit: 6
					};
					SEARCH.get($scope.param, function (data) {
				    	if (data.status) {
				    		$scope.events=data.events;
				    		$scope.options = data.options;
				    		$scope.offset = $scope.options.limit;
				    	}
				    });	
                }

	    	}
	    });	
    };

    //first load search
    $scope.searchEvents();

    $scope.loadMoreEvents = function() {
        $scope.stopLoad = true;
        $scope.param.offset = $scope.offset;
        if (getEvents) {
            SEARCH.get($scope.param, function(data){
            	$scope.stopLoad = false;
                if (data.status) {
                    $scope.options = data.options;
                    $scope.offset += $scope.options.limit;

                    for (var r=0; r<data.events.length; r++ ) {
                        $scope.events.push(data.events[r]);
                    };
                    
                    if (!data.events.length) {
                    	$scope.stopLoad = true;
                    };
                    if (!data.events.length && !$scope.options.offset) {
                    	$scope.noEvents = true;
                    	$scope.param = {
							lat: 0,
							lng: 0,
							title: '',
							offset: 0,
							limit: 6
						};
						SEARCH.get($scope.param, function (data) {
					    	if (data.status) {
					    		$scope.events=data.events;
					    		$scope.options = data.options;
					    		$scope.offset = $scope.options.limit;
					    	}
					    });	
                    }
                }
                else {
                    $scope.stopLoad = true; // infinite-scroll disabled
                    getEvents = false; //do get events from server
                };
            });
        };
    };

    
});



app.controller('SearchFilterCtrl', function ($scope, SEARCH, $routeParams, $route, $location) {

	$scope.filter ={};

	$scope.filter.distance = parseInt($routeParams.distance);

	if ($routeParams.fstart) {
		$scope.param.start = $routeParams.fstart;
		$scope.filter.event_start = $routeParams.fstart;
	};
	if ($routeParams.fend) {
		$scope.param.end = $routeParams.fend;
		$scope.filter.event_end = $routeParams.fend;
	};

	//console.log($scope.filter);

	$scope.filterChange = function () {

		var path = '/events/'+ $routeParams.lat + '/' + $routeParams.lng +'/' + $scope.filter.distance + '/' + $routeParams.search;
		if ($scope.filter.event_start) {
			var start = new Date ($scope.filter.event_start);
			$location.search('fstart',start);
		}
		if ($scope.filter.event_end) {
			var end = new Date ($scope.filter.event_end);
			$location.search('fend',end);
		}

		$location.path(path);
	};


	$scope.filterEvents = function () {
		$scope.param.distance = $scope.filter.distance;
		$scope.param.offset = 0;
		if ($scope.filter.event_start) $scope.param.start = new Date ($scope.filter.event_start);
		if ($scope.filter.event_end) $scope.param.end = new Date ($scope.filter.event_end);
		$scope.searchEvents();
	};




	$scope.today = function() {
	   var day = new Date();
	   day.setDate(day.getDate()+7);
	   day.setHours (19);
	   day.setMinutes (0);
//     day.setSeconds (0);
	   $scope.event.event_start = day;
	   $scope.event.start_time = day;

		
	   var end =new Date(day);
	   end.setHours(end.getHours()+2);
	   $scope.event.event_end = end;
	};



	$scope.toggleMin = function() {
	    $scope.minDate = $scope.minDate ? null : new Date();
	};
	$scope.toggleMin();

	$scope.openstart = function($event) {
	    $event.preventDefault();
	    $event.stopPropagation();

	    $scope.start_opened = true;
	    $scope.end_opened = false;
	};

	$scope.openend = function($event) {
	    $event.preventDefault();
	    $event.stopPropagation();

	    $scope.end_opened = true;
	    $scope.start_opened = false;
	};


	$scope.dateOptions = {
	    formatYear: 'yy',
	    formatMonth: 'MM',
	    startingDay: 1
	};

	$scope.formats = ['dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy', 'shortDate', 'dd-MM-yyyy'];
	$scope.format = $scope.formats[4];


});
