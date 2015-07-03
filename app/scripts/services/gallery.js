'use strict';

/**
 * @ngdoc service
 * @name eatApp.Gallery
 * @description
 * # Gallery
 * Service in the eatApp.
 */
angular.module('eatApp')
  .service('Gallery', function Gallery($resource, ENV, ALERT) {
    // AngularJS will instantiate a singleton by calling "new" on this function

    var galleryResource = $resource(ENV.apiServer + 'user/images/:id', {id: '@id'},
    	{
    		withCredentials: true,
    		update: { method: 'PUT' }
    	}
    );

    var imageResource = $resource(ENV.apiServer + 'image/:id', {id: '@id'},
    	{
    		withCredentials: true,
    		update: { method: 'PUT' }
    	}
    );
    
    var Gallery = {
    	images: [],
      ctx: {},
      labels: [],
      photos: [],
      maxid: 0,
    	read: function (context){
    		galleryResource.get({}, function(data){
    			
          //console.log(data);
          //Gallery.images = data.images;
    			angular.copy(data.images, Gallery.images);
          
          //create distinct Gallery.labels
          //Gallery.labels = [];
          Gallery.labels.length = 0;
          for (var i=0;i<Gallery.images.length;i++) {
            var img = Gallery.images[i];
            if (img.id>= Gallery.maxid) Gallery.maxid = img.id;
            for (var l=0; l<img.labels.length; l++){
              var labelText = img.labels[l];
              if (Gallery.labels.indexOf(labelText) === -1){
                Gallery.labels.push(labelText);
              }
            }

          }
    		}).$promise;
    	},
    	delete: function (photo) {
        //console.log(photo);
   			
    		var id = Gallery.images.indexOf(photo);
        //console.log(id);
    		//delete from server
   			imageResource.delete({id: photo.id});
   			//delete from view
   			Gallery.images.splice(id,1);

   		},
   		update: function(photo) {
    		//update data in server
        //console.log(photo);
   			imageResource.update(photo, function(data){
          
          if (!data.status)
           {
              console.log(data.error);
              ALERT.create('danger',data.error.info);
          }
        });
   		}
    };

    return Gallery;
  });
