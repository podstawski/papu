'use strict';

app.controller('ReviewsCtrl', function($scope, $routeParams, Auth, REVIEWS, ENV, ALERT) {

    var username = $routeParams.username;

    var user = Auth.user;


    $scope.reviews = [];
    $scope.options = {};
    $scope.offset = 0;
    $scope.stopLoad = true;
    var getReviews = true;

    REVIEWS.get({username: username, offset: $scope.offset}, function(data){
        if (data.status) {
            $scope.reviews = data.reviews;
            $scope.options = data.options;
            $scope.offset = $scope.options.limit;
            $scope.stopLoad = false;
            getReviews = true;

        }
        else{
            //ALERT.create('danger',data.error.info);  
            $scope.stopLoad = true;
        }
      });

    $scope.loadMoreReviews = function() {
        //console.log('event profile review');
        $scope.stopLoad = true;
        if (getReviews) {
            REVIEWS.get({username: username,offset:$scope.offset}, function(data){
                $scope.stopLoad = false;
                if (data.status) {
                    $scope.options = data.options;
                    $scope.offset += $scope.options.limit;
                    for (var r=0; r<data.reviews.length; r++ ) {
                        $scope.reviews.push(data.reviews[r]);
                    };
                    if (!data.reviews.length) {
                        $scope.stopLoad = true;
                        getReviews = false;
                    }
                }
                else {
                    $scope.stopLoad = true; // infinite-scroll disabled
                    getReviews = false; //do get reviews from server
                };
            });
        };
    };

});


app.controller('EventReviewsCtrl', function($scope, $routeParams, Auth, EVENTREVIEWS, RATEEVENT, ENV, ALERT, gettextCatalog) {

    var username = $routeParams.username;
    var eventname = $routeParams.eventname;

    var user = Auth.user;


    $scope.reviews = [];
    $scope.options = {};
    $scope.offset = 0;
    $scope.stopLoad = true;
    var getReviews = true;

    EVENTREVIEWS.get({username: username, eventname: eventname, offset: $scope.offset}, function(data){
        if (data.status) {
            $scope.reviews = data.reviews;
            $scope.options = data.options;
            $scope.offset = $scope.options.limit;
            $scope.stopLoad = false;
        }
        else{
            //ALERT.create('danger',data.error.info);  
            $scope.stopLoad = true;
            getReviews = false;
        }
      });



    $scope.deleteReview = function (review, index) {
        
        if (review.id && review.editable) {
            RATEEVENT.delete({id: review.id}, function (data) {
                if (data.status) {
                    $scope.reviews.splice(index,1);
                    ALERT.create('info', gettextCatalog.getString('Review deleted'));

                } else {
                    ALERT.create('danger',data.error.info);
                };

            });
        };

    };


    $scope.loadMoreEventReviews = function() {
        
        //console.log('event publicprofile review')
        $scope.stopLoad = true;
        if (getReviews) {
            
            EVENTREVIEWS.get({username: username, eventname: eventname,offset:$scope.offset}, function(data){
                $scope.stopLoad = false;
                if (data.status) {
                    $scope.options = data.options;
                    $scope.offset += $scope.options.limit;
                    for (var r=0; r<data.reviews.length; r++ ) {
                        $scope.reviews.push(data.reviews[r]);
                    };
                    if (!data.reviews.length) {
                        $scope.stopLoad = true;
                        getReviews = false;
                    }
          
                }
                else {
                    $scope.stopLoad = true; // infinite-scroll disabled
                    getReviews = false; //do get reviews from server
                };
            });
        };
  };

});
