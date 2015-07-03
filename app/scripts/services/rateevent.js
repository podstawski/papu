'use strict';

/**
 * @ngdoc service
 * @name eatApp.rateevent
 * @description
 * # rateevent
 * Service in the eatApp.
 */

// factory for event reviews
app.factory('RATEEVENT', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'event/rate/:id',{id: '@id'}, {
    withCredentials: true,
    update: {
        method: 'PUT', 
        params: {id: '@id'}, 
        isArray: false 
    }

    });
});