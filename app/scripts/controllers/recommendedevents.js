'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:RecommendedeventsCtrl
 * @description: Nearest events close to user
 * # RecommendedeventsCtrl
 * Controller of the eatApp
 */
app.controller('RecommendedeventsCtrl', function($scope, SEARCH) {

    var myLat, myLng;
    var geoAllowed = false;
    var deflimit = 21;
    $scope.events = [];


    // zapytaj przegladarke i jesli user sie zgodzi to zapisz lokalizacje do bazy
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {

            //console.log('jest pozwolenie');
            $scope.$apply(function() {
                geoAllowed = true;

                myLat = position.coords.latitude;
                myLng = position.coords.longitude;

                $scope.param = {
                    lat: myLat,
                    lng: myLng,
                    title: '',
                    distance: 25,
                    unique: 1,
                    limit: deflimit
                };
                SEARCH.get($scope.param, function(data) {
                    if (data.status) {
                        $scope.events = data.events;
                        $scope.options = data.options;
                        $scope.offset = $scope.options.limit;
                        if (!$scope.events.length) {
                            $scope.param = {
                                lat: 0,
                                lng: 0,
                                title: '',
                                distance: 0,
                                unique: 1,
                                limit: deflimit
                            };
                            SEARCH.get($scope.param, function(data) {
                                if (data.status) {
                                    $scope.events = data.events;
                                    $scope.options = data.options;
                                    $scope.offset = $scope.options.limit;
                                };
                            });                 
                        };
                    };
                });
            });
        }, function (error) {
            //console.log('nie ma pozwolenia');
            $scope.param = {
                lat: 0,
                lng: 0,
                title: '',
                distance: 0,
                unique: 1,
                limit: deflimit
            };
            SEARCH.get($scope.param, function(data) {
                if (data.status) {
                    $scope.events = data.events;
                    $scope.options = data.options;
                    $scope.offset = $scope.options.limit;
                };
            });            
        });
    };

    if (!geoAllowed) {
        $scope.param = {
            lat: 0,
            lng: 0,
            title: '',
            distance: 0,
            unique: 1,
            limit: deflimit
        };

        SEARCH.get($scope.param, function(data) {
            if (data.status) {
                $scope.events = data.events;
                $scope.options = data.options;
                $scope.offset = $scope.options.limit;
            };
        });  
    };

});
