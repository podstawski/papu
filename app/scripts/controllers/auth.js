'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:AuthCtrl
 * @description
 * # AuthCtrl
 * Controller of the eatApp
 */
app.controller('AuthCtrl', function($scope, $location, Auth, reCAPTCHA, $rootScope, ENV, ALERT, LANGS, gettextCatalog) {

    $scope.user = {};
    $scope.remindCollapse = true;

    //set default page after successfull login
    if (!$scope.defaultLocation)
        $scope.defaultLocation = '/profile';

    //set default page after successfull google login
    if (!$scope.googleAuth)
        $scope.googleAuth = ENV.apiServer + 'user/google?redirect=' + $location.absUrl().replace('/login',$scope.defaultLocation);

    //set default page after successfull facebook login
    if (!$scope.facebookAuth)
        $scope.facebookAuth = ENV.apiServer + 'user/facebook?redirect=' + $location.absUrl().replace('/login',$scope.defaultLocation);

    $scope.register = function () {
        Auth.register($scope.user, function(data){
            //cdn.
            if (!data.status) {
                //podal zle dane
                ALERT.create('danger',data.error.info);
                //$scope.user = {};
                reCAPTCHA.reload();
            }
            else {
                //Poprawne dane to zaloguj go
                $scope.login($scope.user);
            }
            
        }, function (errorResp) {
            ALERT.create('danger',errorResp);


        });
    };

    $scope.login = function () {
        Auth.login($scope.user, function(data){

            if (data.status) {
                angular.copy(data.user, Auth.user);
                $rootScope.$emit('Auth:login');
                $location.path($scope.defaultLocation);
                  //read langs and setup select options
                  LANGS.get({}, function(data){
                      if (data.status) {
                          $scope.lang = data.lang;
                          $scope.languages = data.langs;
                          gettextCatalog.setCurrentLanguage($scope.lang);
                      }
                  });  
            }
            else {
                ALERT.create('danger',data.error.info);
            }

        }, function (errorResp) {
            ALERT.create('danger',errorResp);

        });
    };





/*
    if (user) {
        $location.path('/');
    }

    $scope.login = function() {
        Auth.login($scope.user).then(function() {
            $location.path('/');
        }, function(error) {
            $scope.error = error.toString();
        });
    };


    $scope.register = function() {
        Auth.register($scope.user).then(function(user) {
            return Auth.login($scope.user).then(function() {
            	user.username = $scope.user.username;
      			return Auth.createProfile(user);
            }).then(function(){
            	$location.path('/');
            });
        }, function(error) {
            $scope.error = error.toString();
        });
    };
    */
});
