'use strict';

app.controller('ProfileCtrl', function ($scope, $routeParams, $filter,$modal, Auth, Gallery, user, geo, uiGmapGoogleMapApi, $location) {

  //if (!Auth.user.id) {$location.path('/login');};

  var username = $routeParams.username;

  $scope.user = Auth.user;
  $scope.geo = geo.geo;
  $scope.fotki = Gallery.images;

  $scope.activeHost = false;
  $scope.activeGuest = false;
  $scope.activeProfile = false;


  $scope.welcomeOK = function () {
      $scope.user.welcome = 1;
      $scope.saveProfile(true);
  };

  //$scope.yearsRange = [1970,1971,1975, 1984, 1985, 1990];
  $scope.yearsRange = [""];
  var today=new Date();
  
  for (var y=today.getFullYear()-18;y>=today.getFullYear()-100;y--) {
    $scope.yearsRange.push(y);
  }
  

  // otworz galerie na poup, kontekst to profile
  $scope.open = function (context) {

    Gallery.ctx = { "ctx" : 'profile'};
    var modalInstance = $modal.open({
      templateUrl: 'views/modalgallery.html',
      controller: 'ModalInstanceCtrl',
      size: 'lg',
      backdrop: true,
      backdropClass: 'modal-backdrop',
      resolve: {
        maxid: function () {
          return Gallery.maxid;
        }
      }
    });

    //tu wraca po kliknieciu w photo lub button OK
    modalInstance.result.then(function (photoId) {

      var photoArr = $filter('filter')($scope.fotki,{id: photoId});
      var photo = photoArr[0];
      if (angular.isObject(photo)){
        switch (context) {
          case 'cover': $scope.setCoverPhoto(photo); break;
          case 'photo' : $scope.setProfilePhoto(photo);
        }
        //console.log(photo);
        //console.log(context);

      }

    }, function () {
      //console.log('Modal dismissed at: ' + new Date());
    });

  };


   
    //load profile images
  if ($scope.publicprofile) {
    Gallery.ctx = 'public';
    Gallery.read('public');
  } else {
    Gallery.ctx = 'profile';
    Gallery.read();
  }

	$scope.deletePhoto = function (id) {
		Gallery.delete(id);
	}
	$scope.updatePhoto = function (id) {
		Gallery.update(id);
	}

  $scope.saveProfile = function (silent) {
    Auth.update($scope.user, silent);
  };

  $scope.hideProfile = function () {
    $scope.profilechecked = false;
  };

  $scope.setProfilePhoto = function(photo) {
      Auth.user.photo = photo.square;
      $scope.saveProfile(true);
  };
  $scope.setCoverPhoto = function(photo) {
      Auth.user.cover = photo.url;
      $scope.saveProfile(true);
  };


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

  
  // zapytaj przegladarke i jesli user sie zgodzi to zapisz lokalizacje do bazy
  if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position){

        $scope.$apply(function(){
          var myLat=position.coords.latitude;
          var myLng=position.coords.longitude;


          if (!Auth.user.lat) {            
             $scope.user.lat = myLat;
             $scope.user.lng = myLng;


             $scope.map.center.latitude = myLat;
             $scope.map.center.longitude = myLng;
             $scope.map.zoom = 14;

             $scope.marker.coords.latitude = myLat;
             $scope.marker.coords.longitude = myLng;
             $scope.saveProfile(true);
           }
          
        });
      });
  };



  // jesli nie wiemy gdzie jest user to ustawiamy mape na podstawie GEO
  if (!Auth.user.lat) {
    $scope.map.center.latitude = $scope.geo.location.latitude;
    $scope.map.center.longitude = $scope.geo.location.longitude;

    $scope.marker.coords.latitude = $scope.geo.location.latitude;
    $scope.marker.coords.longitude = $scope.geo.location.longitude;

  } 
  else {
    $scope.map.center.latitude = $scope.user.lat;
    $scope.map.center.longitude = $scope.user.lng;

    $scope.marker.coords.latitude = $scope.user.lat;
    $scope.marker.coords.longitude = $scope.user.lng;    

  };


  // jesli mapa jest gotowa to inicjuj mape
  uiGmapGoogleMapApi.then(function(maps) {

   //map glue
    $scope.map.zoom = 14;
      
    $scope.map.events = {
        click: function (map, eventName, args) {
          //console.log(args[0].latLng);
          var posObj = args[0].latLng

          $scope.user.lat = posObj.lat();
          $scope.user.lng = posObj.lng();
          $scope.marker.coords.latitude = posObj.lat();
          $scope.marker.coords.longitude = posObj.lng();
          //scope apply required because this event handler is outside of the angular domain
          $scope.$apply();
          $scope.saveProfile(true);

        }
    };
    $scope.map.marker = {
        options: { draggable: true },
        events: {
          dragend: function (marker, eventName, args) {
            //console.log('marker dragend');
            var lat = marker.getPosition().lat();
            var lon = marker.getPosition().lng();
            $scope.user.lat = lat;
            $scope.user.lng = lon;
            $scope.saveProfile(true);

          }
        }
      }


  //console.log('mapa gotowa');
  //console.log($scope.map);
  });
});