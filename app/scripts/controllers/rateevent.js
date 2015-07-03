'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:RateeventCtrl
 * @description
 * # RateeventCtrl
 * Controller of the eatApp
 */
app.controller('RateEventCtrl', function ($scope, RATEEVENT, ALERT, gettextCatalog) {

  $scope.isCollapsed = true;
  $scope.rate = {food:0, cleanliness:0, atmosphere:0, description: ''};

  $scope.RateEvent = function (event) {
  	var id = event.event;


    if ($scope.rate.description.length < 2)
    {
      alert(gettextCatalog.getString('Type some text about event'));
      return false;
    }
  	//console.log(event);
  	$scope.rate.id = id;
  	//console.log($scope.rate);
  	RATEEVENT.save($scope.rate, function(data) {
  		if (data.status) {
  			ALERT.create('info', gettextCatalog.getString('Your rate saved properly. You can manage this rate on event page'));
        $scope.isCollapsed = true;
        $scope.rated = true;
  		} else {
  			ALERT.create('danger',data.error.info);
  		}
  	})

  };


  $scope.hoveringOver = function(value) {
    $scope.overStar = value;
    $scope.percent = 100 * (value / 5);
  };

  $scope.reviewStatus = function (guest) {
    var now = new Date();
    var event_start = new Date(guest.event_start);

    if (event_start<now && !guest.cancel && guest.payment)
      return true;
    else 
      return false;
    
  };
});
