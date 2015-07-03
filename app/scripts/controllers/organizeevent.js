'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:OrganizeeventCtrl
 * @description
 * # OrganizeeventCtrl
 * Controller of the eatApp
 */
app.controller('OrganizeeventCtrl', function ($scope, ENV, $location) {

  //set default page after successfull login
  if (!$scope.defaultLocation) {
    $scope.defaultLocation = '/profile';
  }

  $scope.googleAuth = ENV.apiServer + 'user/google?redirect=' + encodeURI($location.absUrl().replace($location.path(),$scope.defaultLocation));
  $scope.facebookAuth = ENV.apiServer + 'user/facebook?redirect=' + encodeURI($location.absUrl().replace($location.path(),$scope.defaultLocation));


  $scope.referer = function (ref) {
    $scope.defaultLocation = ref;
    $scope.googleAuth = ENV.apiServer + 'user/google?redirect=' + encodeURI($location.absUrl().replace($location.path(),$scope.defaultLocation));
    $scope.facebookAuth = ENV.apiServer + 'user/facebook?redirect=' + encodeURI($location.absUrl().replace($location.path(),$scope.defaultLocation));
  }




});
