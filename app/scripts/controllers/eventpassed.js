'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:EventpassedCtrl
 * @description
 * # EventpassedCtrl
 * Controller of the eatApp
 */
angular.module('eatApp')
  .controller('EventpassedCtrl', function ($scope, EVENTLAST, ALERT) {
  	$scope.events = [];
    
    EVENTLAST.get({}, function(data){
        if (data.status) {
        	$scope.events = data.events;
        }
        else{
            ALERT.create('danger',data.error.info);  
        }
      });
  });
