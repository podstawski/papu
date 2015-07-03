'use strict';

/**
 * @ngdoc service
 * @name eatApp.Event
 * @description
 * # Event
 * Service in the eatApp.
 */


// factory for passed event
app.factory('EVENTPRICE', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'event/price',{}, {withCredentials: true});
});

// factory for passed event
app.factory('EVENTLAST', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'event/last',{}, {withCredentials: true});
});

// factory for available countries
app.factory('USEREVENTS', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/events',{}, {withCredentials: true});
});

// factory for publicevent data
app.factory('PUBLICEVENT', function ($resource, ENV, $location) {
  
var pubeventResource = $resource(ENV.apiServer + 'event/:username/:event',{}, {withCredentials: true});

var pubevent = {
      event: {},
      get: function (eventparam){
        return pubeventResource.get(eventparam, function(data){
          if (data.status == true){
            pubevent.event = {};
            pubevent.event = data.event;
          }
          else {
            $location.path('/error404');
          }
        }).$promise;
      }
    };
    return pubevent;
});

// factory for available countries
app.factory('SEARCH', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'event/search',{}, {withCredentials: true});
});

// factory for guest in event
app.factory('GUEST', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'guest/:id',{}, {withCredentials: true});
});

app.service('EVENT', function Event($resource, ENV, $rootScope, ALERT,$location, gettextCatalog) {
    // AngularJS will instantiate a singleton by calling "new" on this function

    var eventResource = $resource(ENV.apiServer + 'event/:id', {id: '@id'},
    	{
    		withCredentials: true,
    		update: { method: 'PUT' },
        
    	}
    );
    
    var Event = {
    	all: [],
      event: {},
      res: eventResource,
      create: function (event){
        eventResource.save(event, function(data){
          if (data.status == true) {
            angular.copy(data.event, Event.event);
            //add event on the beginig of list
            //Event.all.unshift(data.event);
            //after add redir to edit page
            $location.path('/event/'+ data.event.id);
          }
          else {
              ALERT.create('danger',data.error.info);
          };
        });
      },
      get: function (event){

        return eventResource.get(event, function(data){
          if (data.status == true){
            //console.log('1 event readed');
            Event.event = {};
            Event.event.images = [];
            //console.log(data.event);
            angular.copy(data.event, Event.event);
          }
          else {
            ALERT.create('danger',data.error.info);
          }
        }).$promise;
      },

    	read: function (context){
    		return eventResource.get({}, function(data){
          if (data.status == true){
            //console.log('Events readed');
            angular.copy(data.events, Event.all);
          } else {
            ;//ALERT.create('danger',data.error.info);
          }
    		}).$promise;
    	},
    	delete: function (id, reason) {
   			var elem = {};

    		elem = Event.all[id];
    		//delete from server
   			eventResource.delete({id: elem.id, reason: reason}, function (data) {
          //delete from view
          if (data.status) {
            Event.all.splice(id,1);
            ALERT.create('info', gettextCatalog.getString('Event deleted'));
          } else {
            ALERT.create('danger',data.error.info);
          }

        });

   		},
      reject: function (id, reason) {
        var elem = {};

        elem = Event.all[id];
        //delete from server
        eventResource.delete({id: elem.id, reason: reason}, function (data) {
          //delete from view
          if (data.status) {
            ALERT.create('info', gettextCatalog.getString('Event has been rejected'));
          } else {
            ALERT.create('danger',data.error.info);
          }

        });

      },      
   		update: function(event) {
   			eventResource.update(event, function (data){
          if (data.status == false){
            
            ALERT.create('danger',data.error.info);
          }
          else {
            Event.event = data.event;
          }          
        }).$promise;
   		},
      clone: function(event) {
        eventResource.save(event, function (data){
          if (data.status == false){
            
            ALERT.create('danger',data.error.info);
          }
          else {
            Event.all.push(data.event);
          }          
        });
      }      

    };

    return Event;
  });
