'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:HomepageCtrl
 * @description
 * # HomepageCtrl
 * Controller of the eatApp
 */


app.controller('HomepageCtrl', function ($scope, $location, GEO, CITIES, ENTERER) {
    $scope.options = {};
    $scope.search = {};
    $scope.search.details = null;

    $scope.options = {
        country: 'PL',
        types: 'geocode'
    }
    GEO.get({}, function(data) {
        if (data.status) {
            $scope.options.country = data.geo.country.iso_code;
        }
    });

    ENTERER.get({"enterer": "home"});

    $scope.searchEvent = function () {
    	if ($scope.search.details) {
			var lat = $scope.search.details.geometry.location.lat();
			var lng = $scope.search.details.geometry.location.lng();
			
			var distance = 25;
			var title = $scope.search.text;
			$location.url('events/'+lat + '/'+lng+'/'+distance+'/'+title);

    	}
    }


  });
