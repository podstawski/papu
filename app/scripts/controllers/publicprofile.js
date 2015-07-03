'use strict';





app.controller('ShareCtrl', function ($scope, $modalInstance, url,$sce) {

    $scope.trusted_url = $sce.trustAsResourceUrl(url);

    $scope.ok = function () {
      $modalInstance.close();
    };
    $scope.cancel = function () {
      $modalInstance.dismiss('cancel');
    };
});


app.controller('PublicProfileCtrl', function($scope, $modal, $routeParams, Auth, Gallery, $location, $window, $anchorScroll, BOOK) {

    var username = $routeParams.username; 

    $scope.fotki = Gallery.images;
    $scope.error = {status:0, info:''};

    $scope.url = "";

    Auth.readprofile({id: username}, function(ref) {
        if (ref.status) {
            //angular.copy(ref.user, $scope.user);
            $scope.user = ref.user;
        } else {
            $location.path('/error404');
        }
    }, function(ref) {
        console.log('Error: reading data from server');
    });


    $scope.createSlots = function (slots) {
        var arr = [];
        for (var i=1; i<=slots; i++) {
            arr.push(i);
        }
        return arr;
    };

    $scope.scrollTo = function (elem) {
        $location.hash(elem);
        $anchorScroll();
    };

    $scope.bookEvent = function(event, guests) {
        BOOK.save({"event": event.id, "persons": guests}, function(data) {
            if (data.status) {
               //console.log(data) ;
               $location.path("/book");
            }
        });
        
    };

    Gallery.read('public');


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

app.controller('DemoController', function($scope) {
  $scope.images = [1, 2, 3, 4, 5, 6, 7, 8];

  $scope.loadMore = function() {
    var last = $scope.images[$scope.images.length - 1];
    for(var i = 1; i <= 8; i++) {
      $scope.images.push(last + i);
    }
  };
});
