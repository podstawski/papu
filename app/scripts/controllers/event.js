'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:EventCtrl
 * @description
 * # EventCtrl
 * Controller of the eatApp
 */

app.directive('integer', function(){
    return {
        require: 'ngModel',
        link: function(scope, ele, attr, ctrl){
            ctrl.$parsers.unshift(function(viewValue){
                return parseInt(viewValue, 10);
            });
        }
    };
});

app.controller('EventCtrl', function ($scope, ngDialog, gettextCatalog, uiGmapGoogleMapApi, $http, Auth, ENV, EVENT, TAG, $filter, Gallery, $routeParams, $modal, ALERT, GUEST, $location) {

  //console.log(user.status);
  //if (!Auth.user.id) {$location.path('/login');};

    
  //must be declared
	$scope.event = EVENT.event;
	$scope.events = EVENT.all;
	$scope.alerts = ALERT.alerts;
  $scope.fotki = Gallery.images; 
  $scope.user = Auth.user;
  $scope.geo = Auth.geo;
	$scope.tagData = [];
  $scope.currentDate = new Date();
  $scope.host_agreement = 0;
  $scope.start_opened = false;

  if ($scope.event.host_agreement) $scope.host_agreement = 1;

	
  $scope.durationData = [
		{id: 3600, name:'1h'},
		{id: 1.5*3600, name:'1,5h'},
		{id: 2*3600, name:'2h'},
		{id: 2.5*3600, name:'2,5h'},
		{id: 3*3600, name:'3h'},
		{id: 4*3600, name:'4h'},
		{id: 6*3600, name:'6h'}
	];	

  $scope.minmaxGuestData = [
    {id: 1, name:'1'},
    {id: 2, name:'2'},
    {id: 3, name:'3'},
    {id: 4, name:'4'},
    {id: 5, name:'5'},
    {id: 6, name:'6'},
    {id: 7, name:'7'},
    {id: 8, name:'8'},
    {id: 9, name:'9'},
    {id: 10, name:'10 ' + gettextCatalog.getString('and more')},

  ];

  // jesli brak konta to pokaz input do wpisania
  if (!Auth.user.payment) $scope._payment = 1;
  if (!Auth.user.phone) $scope._phone = 1;

    $scope._fb_id = Auth.user.fb_id;
    $scope._fb_friend = Auth.user.fb_friend;
    $scope.facebookFriendsAuth = ENV.apiServer + 'user/facebook?friends=1&redirect=' + encodeURIComponent($location.absUrl().replace('/login','/event/'+$scope.event.id)) + '&r=' + Math.random();

    
  //load guest events data

  GUEST.get({}, function (data) {
    if (data.status) {
      $scope.eventguest = data.guest;
      //console.log(data.guest);

      if ($scope.events.length) {
        if ($scope.eventguest.length) {
          var event_create = $scope.events[0].sort;
          var guest_create = $scope.eventguest[0].sort;
          if (event_create<guest_create) {
            $scope.$parent.activeHost = true;
            $scope.$parent.activeGuest = false;
          } else {
            $scope.$parent.activeHost = false;
            $scope.$parent.activeGuest = true;

          }

        } else {
          $scope.$parent.activeHost = true;
          $scope.$parent.activeGuest = false;          
        }

      } else {
        if ($scope.eventguest.length) {
          $scope.$parent.activeHost = false;
          $scope.$parent.activeGuest = true;
        }
        else {
          $scope.$parent.activeHost = true;
          $scope.$parent.activeGuest = false;
        };
      };
      //prctrl - this is alias for controller ProfileCtrl, added to route in app.js
      switch ($location.hash()) {
        case 'host': $scope.prctrl.activeHost=true;$scope.prctrl.activeGuest = false; $scope.prctrl.activeProfile = false; break;
        case 'guest': $scope.prctrl.activeHost=false;$scope.prctrl.activeGuest = true; $scope.prctrl.activeProfile = false; break;
        case 'editprofile': $scope.prctrl.activeHost=false;$scope.prctrl.activeGuest = false; $scope.prctrl.activeProfile = true; break;
      };

    };
  });

  //Guest in profile button statuses
  $scope.cancelStatus = function (guest) {
    var now = new Date();
    var deadline = new Date(guest.deadline);

    if (!guest.cancel && guest.payment && deadline>now)
      return true;
    else 
      return false;
    
  };

  $scope.cancelVisit = function (event) {


    var dialog = ngDialog.openConfirm({ 
      template: 'views/modalconfirmdialog.html',
      scope: $scope,
      showClose: true
    }).then(function (value) {
      //console.log('Modal promise resolved. Value: ', value);
      GUEST.delete({id: event.id, reason: value}, function(data) {
        if (data.status) {
          ALERT.create('info', gettextCatalog.getString('You have canceled your visit'));
          var id = $scope.eventguest.indexOf(event);
          //delete from view
          $scope.eventguest.splice(id,1);
        } else {
          ALERT.create('danger',data.error.info);
        };
      }); 

    }, function (reason) {


    });

   
    // if (confirm(gettextCatalog.getString('Are you sure?'))) {
    //   GUEST.delete({id: event.id}, function(data) {
    //     if (data.status) {
    //       ALERT.create('info', gettextCatalog.getString('You have canceled your visit'));
    //       var id = $scope.eventguest.indexOf(event);
    //       //delete from view
    //       $scope.eventguest.splice(id,1);
    //     } else {
    //       ALERT.create('danger',data.error.info);
    //     };
    //   });
    // }
  };

  $scope.rejectGuest = function (event, guestArr, index) {
    
  var dialog = ngDialog.openConfirm({ 
      template: 'views/modalconfirmdialog.html',
      scope: $scope,
      showClose: true
    }).then(function (value) {
      //console.log('Modal promise resolved. Value: ', value);
      GUEST.delete({id: event.guest_id, reason: value}, function(data) {
        if (data.status) {
          ALERT.create('info', gettextCatalog.getString('You have rejected guest'));
          //var id = $scope.event.guests.indexOf(event);
          //delete from view
          guestArr.splice(index,1);
        } else {
          ALERT.create('danger',data.error.info);
        };
      });       

    }, function (reason) {


    });



    // if (confirm(gettextCatalog.getString('Are you sure?'))) {
    //   GUEST.delete({id: event.guest_id}, function(data) {
    //     if (data.status) {
    //       ALERT.create('info', gettextCatalog.getString('You have rejected guest'));
    //       //var id = $scope.event.guests.indexOf(event);
    //       //delete from view
    //       guestArr.splice(index,1);
    //     } else {
    //       ALERT.create('danger',data.error.info);
    //     };
    //   });
    // }
  
  };


  $scope.reviewStatus = function (guest) {
    var now = new Date();
    var event_start = new Date(guest.event_start);

    if (event_start<now && !guest.cancel && guest.payment)
      return true;
    else 
      return false;
    
  };


  $scope.saveProfile = function () {
    Auth.update($scope.user);

  };

  $scope.clone = function (event) {
    EVENT.clone({"parent": event.id});
  };

  $scope.PublishEvent = function (event, val) {
    EVENT.res.update({id: event.id, active: val}, function (data) {
      if (data.status) {
        event.active = val;
      } else {
      ALERT.create('danger',data.error.info);
      }
    });
  };
  

// BEGIN to jest do modalnej galerii
  $scope.open = function (context) {
    //context - skad wywolano galerie, aby potem wlasciwa akcje wybrac, po klikniecie lub zamknieciu
    //

    //ustaw context do wgrywania obrazkow do konkretnego ID
    // gallery upload ctx
  	Gallery.ctx = { "ctx" : {"event": $scope.event.id}};

    var modalInstance = $modal.open({
      templateUrl: 'views/modalgallery.html',
      controller: 'ModalInstanceCtrl',
      size: 'lg',
      backdrop: true,
      backdropClass: 'modal-backdrop',
      //przekaz do controllera modalu ostatni wgrany/wczytany obrazek
      resolve: {
        maxid: function () {
          return Gallery.maxid;
        }
      }
    });

    modalInstance.result.then(function (photoId) {
      var photoArr = $filter('filter')($scope.fotki,{id: photoId});
      var photo = photoArr[0];
      if (angular.isObject(photo)){
        switch (context) {
          case 'cover': $scope.setCoverPhoto(photo); break;
          case 'photo' : $scope.setProfilePhoto(photo); break;
          case 'eventImages' : $scope.setEventPhotos(photo); break;
        }
        //console.log('ctx:' + context);
      }

    }, function () {
      //console.log('Modal dismissed at: ' + new Date());
    });

  };
// END to jest do modalnej galerii

  $scope.setCoverPhoto = function(photo) {
      $scope.event.img = photo;
      $scope.saveEvent();
  };


  $scope.setProfilePhoto = function(photo) {
      //Auth.user.photo = photo.square;
      //$scope.saveEvent();
  };


  //dodawanie z galerii obrazkow do eventu
  $scope.setEventPhotos = function(photo) {

      //musimy dodac label do obrazka i zapisac
      $http.post(ENV.apiServer + 'event/image', 
        {
            "event": $scope.event.id,
            "image": photo.id
        }).
        success(function(resp) {
          //console.log('Event: images added');
          $scope.event.images.push(photo);
          //EVENT.get({"id": $scope.event.id});
        }).
        error(function(data) {
            ALERT.create('danger',data.error.info);
        });     
  };


  //usuwanie fotki z eventu
  $scope.deleteEventPhoto = function(photo) {

      $http.put(ENV.apiServer + 'event/image', 
        {
            "event": $scope.event.id,
            "image": photo.id

        }).
        success(function(data) {
          if (!data.status) {
             ALERT.create('danger',data.error.info);
          } 
          else
          {
            var id = $scope.event.images.indexOf(photo);
            //console.log('Event images: image deleted: ' + id);
            $scope.event.images.splice(id,1);
          }
        }).
        error(function(data) {
            ALERT.create('danger',data.error.info);
        });     
  };




// inicjacja pol formy
//

	$scope.today = function() {
	   var day = new Date();
	   day.setDate(day.getDate()+7);
	   day.setHours (19);
	   day.setMinutes (0);
//     day.setSeconds (0);
	   $scope.event.event_start = day;
	   $scope.event.start_time = day;

		
	   var end =new Date(day);
	   end.setHours(end.getHours()+2);
	   $scope.event.event_end = end;
	};


	$scope.startDateChanged = function () {
		var p = $scope.event.event_start;
	};

	$scope.timeChanged = function () {

    //console.log($scope.event.event_start);

		//$scope.event.event_start.setHours($scope.event.start_time.getHours());
		//$scope.event.event_start.setMinutes($scope.event.start_time.getMinutes());
	};

	  $scope.clear = function () {
	    $scope.event.start = null;
	    $scope.event.end = null;
	  };

	  // Disable weekend selection
	  $scope.disabled = function(date, mode) {
	  	return false;
	    //return ( mode === 'day' && ( date.getDay() === 0 || date.getDay() === 6 ) );
	  };

	  $scope.toggleMin = function() {
	    $scope.minDate = $scope.minDate ? null : new Date();
	  };
	  $scope.toggleMin();

	  $scope.openstart = function($event) {

	    $event.preventDefault();
	    $event.stopPropagation();

	    $scope.start_opened = true;
	    $scope.end_opened = false;
      //console.log('cal opened');
	  };

	  $scope.openend = function($event) {
	    $event.preventDefault();
	    $event.stopPropagation();

	    $scope.end_opened = true;
	    $scope.start_opened = false;
	  };


	  $scope.dateOptions = {
	    formatYear: 'yy',
	    formatMonth: 'MM',
	    startingDay: 1
	  };

	  $scope.formats = ['dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy', 'shortDate','dd-MM-yyyy'];
	  $scope.format = $scope.formats[4];


    $scope.createEvent = function () {

      $scope.event = {};
      //gdy dodajemy event
      $scope.today();

    	var end = new Date($scope.event.event_start);
    	end.setHours(end.getHours()+$scope.event.duration);
    	$scope.event.event_end = end; 
      $scope.event.name = '';
      $scope.event.info = 2;
      $scope.event.restaurant = 0;
    	// var timestart = $scope.event.start.getTime() / 1000 | 0;
    	//console.log('start:'+ $scope.event.event_start);
    	//console.log('end:'+ $scope.event.event_end);


      EVENT.create($scope.event);
    	


    };

    $scope.deleteEvent = function (id, confirmation, reject) {

      $scope.dialogData = "";

      if (confirmation) {
        var dialog = ngDialog.openConfirm({ 
          template: 'views/modalconfirmdialog.html',
          scope: $scope,
          showClose: true
        }).then(function (value) {
            //gdy wpisal usprawiedliwienia
            //console.log('Modal promise resolved. Value: ', value);
            if (reject) {
              EVENT.reject(id, value); 
            } else {
              EVENT.delete(id, value); 
            }
          }, function (reason) {
            ;
          });
      } else {
        if (confirm(gettextCatalog.getString('Are you sure?'))) {
          EVENT.delete(id);  
        }        
      }
    	
    };

    $scope.fbFriendsAuth = function () {
      $scope.saveEvent(false);
      location.href=$scope.facebookFriendsAuth;
      //console.log($scope.facebookFriendsAuth);
    };
    
    $scope.saveEvent = function (exit) {
      var end = new Date($scope.event.event_start);
      end.setHours(end.getHours()+$scope.event.duration);
      $scope.event.host_agreement = $scope.host_agreement;
      $scope.event.event_end = end; 

    	EVENT.res.update($scope.event, function (data) {
        if (data.status){
          $scope.event = data.event;
          if (exit) $location.path('/profile');
        } else {
          ALERT.create('danger',data.error.info);
        }
      });
      return true;
    };

  // jesli nie wiemy gdzie jest user to ustawiamy mape na podstawie GEO
//if (!Auth.user.lat) $scope.map = { center: { latitude: $scope.geo.location.latitude, longitude: $scope.geo.location.longitude }, zoom: 10 };

//domylne ustawienia mapy
$scope.map = {
  zoom: 14,
  center: {
    latitude: 52.41,
    longitude: 16.90
  },
  events: {},
  marker: {}

};
$scope.marker = {coords: {latitude:52.41, longitude:16.90}};


if (!$scope.event.lat) {
  $scope.map.center.latitude = $scope.geo.location.latitude;
  $scope.map.center.longitude = $scope.geo.location.longitude;

  $scope.marker.coords.latitude = $scope.geo.location.latitude;
  $scope.marker.coords.longitude = $scope.geo.location.longitude;  
}
else {
  $scope.map.center.latitude = $scope.event.lat;
  $scope.map.center.longitude = $scope.event.lng;

  $scope.marker.coords.latitude = $scope.event.lat;
  $scope.marker.coords.longitude = $scope.event.lng;  
}



//inicjacja mapy
uiGmapGoogleMapApi.then(function(maps) {

   //map glue
    $scope.map.zoom = 14;
    $scope.map.events = {
        click: function (map, eventName, args) {
          //console.log(args[0].latLng);
          var posObj = args[0].latLng;
          $scope.event.lat = posObj.lat();
          $scope.event.lng = posObj.lng();
          $scope.marker.coords.latitude = posObj.lat();
          $scope.marker.coords.longitude = posObj.lng();
          //scope apply required because this event handler is outside of the angular domain
          $scope.$apply();
          $scope.saveEvent();

        }
      };
    $scope.map.marker = {
        options: { draggable: true },
        events: {
          dragend: function (marker, eventName, args) {
            //console.log('marker dragend');
            var lat = marker.getPosition().lat();
            var lon = marker.getPosition().lng();
            $scope.event.lat = lat;
            $scope.event.lng = lon;
            $scope.saveEvent();

          }
        }
    }
  });
});
