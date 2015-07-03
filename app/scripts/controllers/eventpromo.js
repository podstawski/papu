'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:EventpromoCtrl
 * @description
 * # EventpromoCtrl
 * Controller of the eatApp
 */
angular.module('eatApp')
  .controller('EventpromoCtrl', function ($scope, SEARCH, ALERT) {
  	$scope.events = [];


	$scope.param = {
        title: '',
        distance: 50,
        limit: 1,
        vip: 1
    };
    SEARCH.get($scope.param, function(data) {
        if (data.status) {
        	$scope.events = data.events;
        }
        else{
            ALERT.create('danger',data.error.info);  
        }
    });

  });
