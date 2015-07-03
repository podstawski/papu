'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:BookCtrl
 * @description
 * # BookCtrl
 * Controller of the eatApp
 */
app.controller('BookCtrl', function ($scope, $window, Auth, BOOK, GUEST, ALERT, ENV,$location) {
  	$scope.event = {free_slots: 1};
  	$scope.persons = 0;
    $scope.book = {};
  	$scope.readyToPay = false;
  	$scope.payLink = "";
    $scope.guest_agreement = Auth.user.guest_agreement;
    $scope.signedIn = Auth.signedIn;
    $scope.user = Auth.user;

    var loc=$location.absUrl().replace('/login','/book');
    var ioq=loc.indexOf('?');
    if (ioq>0) loc=loc.substr(0,ioq);
    $scope.facebookFriendsAuth = ENV.apiServer + 'user/facebook?friends=1&redirect=' + encodeURIComponent(loc);

    
    $scope.saveProfile = function (silent) {
      Auth.update($scope.user, silent);
    };

    //set default page after successfull login
    if (!$scope.defaultLocation)
        $scope.defaultLocation = '/book';

    //set default page after successfull google login
    if (!$scope.googleAuth)
        $scope.googleAuth = ENV.apiServer + 'user/google?redirect=' + $location.absUrl().replace('/book',$scope.defaultLocation);

    //set default page after successfull facebook login
    if (!$scope.facebookAuth)
        $scope.facebookAuth = ENV.apiServer + 'user/facebook?redirect=' + $location.absUrl().replace('/book',$scope.defaultLocation);

    // read booked event
  	BOOK.get({}, function(data) {
  		if (data.status) {
  			$scope.event = data.book.event;
  			$scope.persons = data.book.persons;
        $scope.sloty=$scope.createSlots($scope.event.free_slots);
  		} else {
        $location.path('/');
      }


  	});

  	$scope.buyEvent = function () {
      $scope.book.event = $scope.event.id;
      $scope.book.persons = $scope.persons;

  		GUEST.save($scope.book, function(data) {
  			if (data.status) {
				  $scope.payLink = data.guest.payu;
  				$scope.readyToPay = true;	
          if ($scope.payLink) {
            $window.location.href = $scope.payLink;
          }
          else {
            $location.path('/profile')
          }
  			} else {
  				ALERT.create('danger',data.error.info);
  			}
  		});
  	};

    $scope.createSlots = function (slots) {
      var arr = [];
      for (var i=1; i<=slots; i++) {
          arr.push(i);
      }
      return arr;
    }

  });
