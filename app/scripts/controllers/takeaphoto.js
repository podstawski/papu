'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:TakeaphotoCtrl
 * @description
 * # TakeaphotoCtrl
 * Controller of the eatApp
 */
app.controller('TakeaphotoCtrl', function ($scope, Gallery, USEREVENTS) {

	$scope.gallery = Gallery;
	//ustaw context do wgrywania obrazkow do konkretnego ID
    // gallery upload ctx
  	//Gallery.ctx = { "ctx" : {"event": $scope.event.id}};    

	USEREVENTS.get({}, function (data) {
	    if (data.status) {
	    	$scope.events = data.events;
	  	};
	});


});
