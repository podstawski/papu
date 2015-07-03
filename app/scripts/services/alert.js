'use strict';

/**
 * @ngdoc service
 * @name eatApp.Alert
 * @description
 * # Alert
 * Service in the eatApp.
 */
angular.module('eatApp')
  .service('ALERT', function Alert($filter) {
    // AngularJS will instantiate a singleton by calling "new" on this function

    var Alert = {
    	alerts: [],
    	create: function (type, message){

            var a = $filter('filter')(Alert.alerts, {type: type, msg: message});

            if (!a.length)
    		  Alert.alerts.push({type: type, msg: message});
    	},
    	delete: function (index) {
    		Alert.alerts.splice(index, 1);
    	}
    }

    return Alert;

  });
