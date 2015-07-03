'use strict';

/**
 * @ngdoc service
 * @name eatApp.book
 * @description
 * # book
 * Service in the eatApp.
 */
// factory for available countries
app.factory('BOOK', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'book',{}, {withCredentials: true});
});