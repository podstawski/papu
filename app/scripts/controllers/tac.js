'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:TacCtrl
 * @description
 * # TacCtrl
 * Controller of the eatApp
 */
angular.module('eatApp')
  .controller('TacCtrl', function ($scope,$sce, gettextCatalog) {
    $scope.url = $sce.trustAsResourceUrl(gettextCatalog.getString("tac"));
    
  });

