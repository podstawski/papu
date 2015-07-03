'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:KonkursCtrl
 * @description
 * # KonkursCtrl
 * Controller of the eatApp
 */
angular.module('eatApp')
  .controller('KonkursCtrl', function ($scope, ENTERER) {
    ENTERER.get({"enterer": "konkurs-201504"});
  });
