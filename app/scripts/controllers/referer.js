'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:RefererCtrl
 * @description
 * # RefererCtrl
 * Controller of the eatApp
 */
app.controller('RefererCtrl', function ($scope, $routeParams, REFERER) {

  	var referer = $routeParams.referer;
  	$scope.referer = referer;

  	if (referer) {

  	    REFERER.get({"referer": referer});

  	}

  });
