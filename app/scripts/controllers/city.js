'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:CityCtrl
 * @description
 * # CityCtrl
 * Controller of the eatApp
 */

app.controller('CityCtrl', function ($scope, CITIES) {

    $scope.cities = [];

    CITIES.get({}, function (data) {
        if (data.status) {
            //angular.copy(data.cities, $scope.cities);
            $scope.cities = data.cities;
        };
    });
  });
