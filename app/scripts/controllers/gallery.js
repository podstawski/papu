'use strict';

/**
 * @ngdoc function
 * @name eatApp.controller:GalleryCtrl
 * @description
 * # GalleryCtrl
 * Controller of the eatApp
 */


app.controller('ModalInstanceCtrl', function ($scope, $modalInstance, maxid) {

  $scope.selected = maxid;

  $scope.ok = function (maxid) {
    $modalInstance.close($scope.selected);
  };
  $scope.selectPhoto = function (idImage) {
    $modalInstance.close(idImage);
  };
  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };


});


app.controller('GalleryCtrl', function($scope, $routeParams, ENV, $http, Gallery, ALERT, ngProgress) {

    $scope.fotki = Gallery.images;
    $scope.alerts = ALERT.alerts;
    $scope.labels = Gallery.labels;

    $scope.selected = Gallery.maxid;
    
    //progresbar
    ngProgress.height('5px');

    //load profile images
    //Gallery.read('profile');

    $scope.deletePhoto = Gallery.delete;

    $scope.updatePhoto = Gallery.update;

    //parent actions - ProfileCtrl, EventCtrl
    // $scope.setProfilePhoto = function (id) {
    //     $scope.selected = id;
    // };

    $scope.nullLabels = function(elem, index) {
        var res = false;
        if (elem.labels.length === 0)  res = true;
        return res;
    }

    // config do fileupload
    $scope.config = {
        //target: ENV.apiServer + 'image',
        uploadMethod: 'POST',
        withCredentials: true,
        permanentErrors: [404, 500, 501],
        testChunks: false, //robi POST wtedy
        chunkSize: 1024 * 1024

    };

    $scope.$on('flow::filesAdded', function(event, $flow, files) {
        var uploadStart = true;
        var maxUploadSize = 6 * 1024 * 1024;
        for (var i=0; i < files.length; i++) {
            var file = files[i];

            if (file.size > maxUploadSize) {
                ALERT.create('danger', 'Limit size exceded, max 6Mb. Upload to server not started. '+' File: '+file.name);
                $flow.cancel();
                uploadStart = false;
                event.preventDefault(); //wymagane aby nie pozwalal ladowac nastepnych
            }
        }

        if (uploadStart) {
            //ask for upload url with context profile
            $http.get(ENV.apiServer + 'image', {params: Gallery.ctx}).
            success(function(resp) {
                ngProgress.start();
                $flow.opts.target = resp.url;
                $flow.opts.uploadMethod = 'POST';
                $flow.upload();
            }).
            error(function(resp) {
                ALERT.create('danger',resp.error.info);
                
            });
        };

    });

    $scope.$on('flow::complete', function(event, $flow, files) {
        ngProgress.complete();
        Gallery.read();
        $flow.cancel();
    });



    var _video = null,
        patData = null;

    $scope.patOpts = {x: 0, y: 0, w: 25, h: 25};
    $scope.canvasOpts = {w:320, h:240};

    $scope.webcamError = false;
    $scope.onError = function (err) {
        $scope.$apply(
            function() {
                $scope.webcamError = err;
            }
        );
    };

    $scope.onSuccess = function (videoElem) {
        // The video element contains the captured camera data
        /*_video = angular.element(videoElem);
        $scope.$apply(function() {
            $scope.patOpts.w = _video.width;
            $scope.patOpts.h = _video.height;
            $scope.showDemos = true;
        });*/
    };

    $scope.onStream = function (stream, videoElem) {
        // You could do something manually with the stream.
    };


    /**
     * Make a snapshot of the camera data and show it in another canvas.
     */
    $scope.makeSnapshot = function makeSnapshot() {
        if (_video) {

            var patCanvas = angular.element('#snapshot');
            if (!patCanvas) return;

            patCanvas.width = _video.width;
            patCanvas.height = _video.height;
            var ctxPat = patCanvas.getContext('2d');

            var idata = getVideoData($scope.patOpts.x, $scope.patOpts.y, $scope.patOpts.w, $scope.patOpts.h);
            ctxPat.putImageData(idata, 0, 0);

            //sendSnapshotToServer(patCanvas.toDataURL());

            patData = idata;

        }
    };


    /**
     * Redirect the browser to the URL given.
     * Used to download the image by passing a dataURL string
     */
    $scope.downloadSnapshot = function downloadSnapshot(dataURL) {
        window.location.href = dataURL;
    };

    var getVideoData = function getVideoData(x, y, w, h) {
        var hiddenCanvas = document.createElement('canvas');
        hiddenCanvas.width = _video.width;
        hiddenCanvas.height = _video.height;
        var ctx = hiddenCanvas.getContext('2d');
        ctx.drawImage(_video, 0, 0, _video.width, _video.height);
        return ctx.getImageData(x, y, w, h);
    };




});
