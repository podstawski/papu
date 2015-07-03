'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:CountriesCtrl
 * @description
 * # CountriesCtrl
 * Controller of the eatApp
 */
app.controller('CountriesCtrl', function ($scope, COUNTRIES) {
  $scope.countries = {};

// var values = {...}
// $scope.selectOptions = [];
// angular.forEach(values, function(value, key) {
//     $scope.selectOptions.push({
//         key: value,
//         label: key
//     });
// });



  //read countries and setup select options
  COUNTRIES.get({}, function(data){
      if (data.status) {
          $scope.countries = data.countries;
      }
  }); 

});
