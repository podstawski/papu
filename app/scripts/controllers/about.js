'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:AboutCtrl
 * @description
 * # AboutCtrl
 * Controller of the eatApp
 */
app.controller('AboutCtrl', function ($scope, ENTERER) {
    ENTERER.get({"enterer": "about-201504"});
});
