'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:PubliceventCtrl
 * @description
 * # PubliceventCtrl
 * Controller of the eatApp
 */
app.controller('PubliceventCtrl', function ($scope,$routeParams, $location, PUBLICEVENT, BOOK, uiGmapGoogleMapApi, $modal, $filter, Lightbox, LANGS) {

  var username = $routeParams.username;
  var eventname = $routeParams.eventname;

  $scope.event = PUBLICEVENT.event;
  $scope.slides = PUBLICEVENT.event.images;
  $scope.user = username;
  $scope.userevent = eventname;
  $scope.calendarId = 0;

  LANGS.get({}, function(data){
      if (data.status) {
          $scope.intefracelang = data.lang;
          $scope.languages = data.langs;
      }
  }); 



  if ($routeParams.id) {
    //$scope.calendarId = $routeParams.id;
    var calEv = $filter('filter')($scope.event.calendar,{id: $routeParams.id});
    var idx = $scope.event.calendar.indexOf(calEv[0]);
    if (idx >= 0) $scope.calendarId = idx;
  }


  $scope.openLightboxModal = function (index) {
    Lightbox.openModal($scope.slides, index);
  };

  $scope.translateDescription = function (event) {
    
    var transWin=window.open("","translation","width=600, height=300, toolbar=no, scrollbars=no, resizable=yes");
    var destlang=$scope.intefracelang;
    
    
    var html='<html lang="'+event.host.lang+'">';
    html+='<head><meta charset="utf-8"><title>'+event.name+'</title>';
    html+='<link href="//fonts.googleapis.com/css?family=Roboto+Condensed:400,700,300&subset=latin,latin-ext" rel="stylesheet" type="text/css">';
    html+='<style>* {font-family: Roboto Condensed}</style>';
    html+='</head>';
    html+='<body>';
    
    html+='<h1>'+event.name+'</h1>';
    html+='<div>'+event.about+'</div>';

    html+='</body>';
    html+='<script>document.cookie="googtrans=/'+event.host.lang+'/'+destlang+'; path=/";</script>';
    html+='<script>function gtranslate() {new google.translate.TranslateElement({"pageLanguage":"'+event.host.lang+'","multilanguagePage":true,"autoDisplay":true,"layout":"1"}, null);}</script>';
    html+='<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=gtranslate"></script>';
    html+='<script>setTimeout(gtranslate,1000);</script>';
    html+='</html>';
    
    transWin.document.write(html);
  };
  
  
    $scope.createSlots = function (slots) {
        var arr = [];
        for (var i=1; i<=slots; i++) {
            arr.push(i);
        }
        return arr;
    }

    $scope.bookEvent = function(event, guests) {
        BOOK.save({"event": event.id, "persons": guests}, function(data) {
            if (data.status) {
               //console.log(data) ;
               $location.path("/book");
            }
        });
        
    };    

	//gdy nie zalogowany to pokazujemy kolko
	var cVisible = false;
	if($scope.event.tolerance) {
		cVisible = true;
	}
	else {
		cVisible = false;
	}

	//inicjacja mapy
	uiGmapGoogleMapApi.then(function(maps) {

	   //map glue
	    $scope.map = { 
	      center: { latitude: $scope.event.lat, longitude: $scope.event.lng }, 
	      zoom: 13,
	      marker: {
	        options: { draggable: false, visible: !cVisible}

	      }
	    };

	    $scope.marker = {
	        coords: {
	          latitude: $scope.event.lat,
	          longitude: $scope.event.lng
	        }
	        
	    };
	    
	  });



	$scope.circles = [
            {
                id: 1,
                center: {
                    latitude: $scope.event.lat,
                    longitude: $scope.event.lng
                },
                radius: $scope.event.tolerance * 100*1100,
                stroke: {
                    color: '#ff9000',
                    weight: 1,
                    opacity: 0.7
                },
                fill: {
                    color: '#ff9000',
                    opacity: 0.3
                },
                geodesic: true, // optional: defaults to false
                draggable: false, // optional: defaults to false
                clickable: false, // optional: defaults to true
                editable: false, // optional: defaults to false
                visible: cVisible // optional: defaults to true
            }
        ];


    // otworz share na poup, 
    $scope.open = function (share) {
      if (share.type == 'popup') {
           window.open(share.url, "", "width=520, height=300, toolbar=no, scrollbars=no, resizable=yes");
           
      }
      else {

        var modalInstance = $modal.open({
          templateUrl: 'views/modalshare.html',
          controller: 'ShareCtrl',
          size: 'sm',
          backdrop: true,
          backdropClass: 'modal-backdrop',
          resolve: {
                "url": function () {return share.url;}
            }
          });

          //tu wraca po kliknieciu w photo lub button OK
          modalInstance.result.then(function () {

          }, function () {
            console.log('Modal dismissed at: ' + new Date());
          });
        };
      };


  });