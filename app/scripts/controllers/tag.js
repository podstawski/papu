'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:TagCtrl
 * @description
 * # TagCtrl
 * Controller of the eatApp
 */
angular.module('eatApp')
  .controller('TagCtrl', function ($scope) {
    $scope.awesomeThings = [
      'HTML5 Boilerplate',
      'AngularJS',
      'Karma'
    ];
  });
