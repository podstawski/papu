'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:BesthostCtrl
 * @description
 * # BesthostCtrl
 * Controller of the eatApp
 */
angular.module('eatApp')
  .controller('BesthostCtrl', function ($scope, BESTHOST, ALERT) {
	$scope.hosts = [];

	var param = {id:1, limit: 3};
    
    BESTHOST.get(param, function(data){

        if (data.status) {
        	$scope.hosts = data.users;
        }
        else{
            ALERT.create('danger',data.error.info);  
        }
      });
  });

