'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:LangsCtrl
 * @description
 * # LangsCtrl
 * Controller of the eatApp
 */
app.controller('LangsCtrl', function ($scope, gettextCatalog, LANGS, Auth) {


  $scope.languages = {};


  //read langs and setup select options
  LANGS.get({}, function(data){
      if (data.status) {
          $scope.lang = data.lang;
          $scope.languages = data.langs;
          gettextCatalog.setCurrentLanguage($scope.lang);
      }
  });                

 

  //user changed lang
  $scope.changeLang = function () {
    gettextCatalog.setCurrentLanguage($scope.lang);
    LANGS.update({lang: $scope.lang});
  };



  });
