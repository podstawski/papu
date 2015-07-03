'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:RemindpassCtrl
 * @description
 * # RemindpassCtrl
 * Controller of the eatApp
 */
app.controller('RemindpassCtrl', function ($scope, REMINDPASSWORD, RESETPASSWORD, $routeParams) {

	$scope.remind = {};

    $scope.remindinfo = false;
    $scope.hideDialog = false;

    $scope.remindPass = function(email) {
	    REMINDPASSWORD.get({"email": email}, function (data) {
	        if (data.status) {
	            //console.log(data);
	            $scope.remindinfo = true;

	        };
	    });
	};


	$scope.resetPass = function () {
		var resetstring = $routeParams.resetstring;
		

		RESETPASSWORD.save({"id": resetstring}, function (data) {
	        if (data.status) {
	        	var oldpass = data.user.password;
	        	var newpass = $scope.remind.pass;

	        	RESETPASSWORD.update({'old': oldpass, 'password': newpass}, function (data) {
	        		
	        		if (data.status) {
	        			$scope.hideDialog = true;


	        		}
	        	});
	            
	            

	        };
	    });

	}



  });
