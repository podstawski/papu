'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:PrivacyCtrl
 * @description
 * # PrivacyCtrl
 * Controller of the eatApp
 */

angular.module('eatApp')
  .controller('PrivacyCtrl', function ($scope,$sce, gettextCatalog) {
    $scope.url = $sce.trustAsResourceUrl(gettextCatalog.getString("privacy"));
    
  });
