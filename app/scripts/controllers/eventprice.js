'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:EventpriceCtrl
 * @description
 * # EventpriceCtrl
 * Controller of the eatApp
 */
app.controller('EventpriceCtrl', function ($scope, EVENTPRICE) {

  	$scope.gp = 0;
  	$scope.prowizja = 0;

  	var price = {};

  	$scope.getPrice = function (price, currency) {

    	EVENTPRICE.get({"price": price, "currency": currency}, function(data){
	        if (data.status) {
	        	price = data.price;
	        	//console.log(data);
	        	$scope.gp = price.guest_price;
	        	$scope.currency = price.currency;
	        	$scope.prowizja = price.guest_price - price.host_price;
	        	
	        }
	        else {
	            //ALERT.create('danger',data.error.info);  
	            $scope.gp = 0;
	        }
      	});
	}

  });
