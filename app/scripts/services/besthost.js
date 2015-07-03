'use strict';

/**
 * @ngdoc service
 * @name eatApp.besthost
 * @description
 * # besthost
 * Service in the eatApp.
 */

  // factory for passed event
app.factory('BESTHOST', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/ranking/:id',{}, {withCredentials: true});
});
