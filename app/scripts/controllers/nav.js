'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:NavCtrl
 * @description
 * # NavCtrl
 * Controller of the eatApp
 */

app.controller('NavCtrl', function ($scope, Auth) {
  
  $scope.signedIn = Auth.signedIn;
  $scope.logout = Auth.logout;
  $scope.user = Auth.user;


});