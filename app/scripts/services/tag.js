'use strict';

/**
 * @ngdoc service
 * @name eatApp.Tag
 * @description
 * # Tag
 * Service in the eatApp.
 */
angular.module('eatApp')
  .service('TAG', function Tag($resource, ENV) {

    var tagResource = $resource(ENV.apiServer + 'tag', {},
    	{
    		withCredentials: true,
    		update: { method: 'PUT' },
        
    	}
    );
    return tagResource;
  });
