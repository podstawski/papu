'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:AlertCtrl
 * @description
 * # AlertCtrl
 * Controller of the eatApp
 */
angular.module('eatApp')
  .controller('AlertCtrl', function ($scope, ALERT) {

  	$scope.alerts = ALERT.alerts;

	$scope.closeAlert = ALERT.delete;

  });
