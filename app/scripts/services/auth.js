'use strict';

/**
 * @ngdoc service
 * @name eatApp.Auth
 * @description
 * # Auth
 * Service in the eatApp.
 */



// factory for reset password
app.factory('REMINDPASSWORD', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/password/:email',{}, {withCredentials: true});
});
app.factory('RESETPASSWORD', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/password/:id',{}, {
    withCredentials: true,
    update: {
        method: 'PUT', 
        //params: {'old': '@old', 'password': '@pass'}, 
        isArray: false 
        }
    });
});

// factory for referer
app.factory('REFERER', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/referer/:referer?referer=' + encodeURIComponent(document.referrer),{}, {withCredentials: true});
});

// factory for enterer
app.factory('ENTERER', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/enter/:enterer?referer=' + encodeURIComponent(document.referrer),{}, {withCredentials: true});
});

// factory for cities on homepage
app.factory('CITIES', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'city',{}, {withCredentials: true});
});


// factory for event reviews
app.factory('EVENTREVIEWS', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'event/:username/:eventname/reviews',{}, {
    withCredentials: true,
    update: {
        method: 'PUT', 
        params: {username: '@username'}, 
        isArray: false 
    }

    });
});

// factory for reviews
app.factory('REVIEWS', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/:username/reviews',{}, {
    withCredentials: true,
    update: {
        method: 'PUT', 
        params: {username: '@username'}, 
        isArray: false 
    },
    get: {
        method: 'get', 
        params: {username: '@username'}, 
        isArray: false 
    }

    });
});


// factory for available countries
app.factory('COUNTRIES', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/countries',{}, {withCredentials: true});
});


// factory for available langs
app.factory('LANGS', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/langs/:id',{}, {
    withCredentials: true,
    update: {
        method: 'PUT', 
        params: {id: '@id'}, 
        isArray: false 
    }
    });
});


// factory reading user photos 
app.factory('Photos', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/images/:id',{},{withCredentials: true});
});

app.factory('UploadPhotos', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'image', {ctx: '@ctxid'});
});

app.factory('GEO', function ($resource, ENV) {
  return $resource(ENV.apiServer + 'user/geo');
});

app.factory('Auth', function($resource, $rootScope, ENV, $routeParams, ALERT, gettextCatalog) {

    var geo = $resource(ENV.apiServer + 'user/geo');

    var auth =  $resource(ENV.apiServer + 'user/:id', 
        { id: '@id' }, { 
            register: { 
                method: 'POST', 
                params: { id: '@id'}, 
                isArray: false 
            }, 
            login: { 
                method: 'GET', 
                params: { id: '@id', action: 'login'}, 
                isArray: false 
            },
            logout: { 
                method: 'GET', 
                params: {action: 'logout'}, 
                isArray: false 
            }, 
            update: {
                method: 'PUT', 
                params: {id: '@id'}, 
                isArray: false 
            },
            readprofile: {
                method: 'GET', 
                params: {id: '@id'}, 
                isArray: false 
            }

        } );


    var Auth = {
        user: {id:0},
        geo: {},
        login: auth.login,
        register: auth.register,
        readprofile: auth.readprofile,
        update: function (u, silent) {
            auth.update(u, function (data) {
                if (data.status) {
                    if (!silent) {
                        ALERT.create('info', gettextCatalog.getString('Profile saved'));
                    }
                }
                else {
                    ALERT.create('danger',data.error.info);
                }

            });
        },
        logout: function () {
            auth.logout();
            $rootScope.$emit('Auth:logout');
        },
        resolveUser: function() {
            return auth.get({}, function (ref){
                angular.copy(ref.user, Auth.user);
                return ref.user;
            }).$promise;
            
        },
        resolveGeo: function () {
            return geo.get({}, function (ref) {
                //Auth.geo = ref.geo;
                angular.copy(ref.geo, Auth.geo);
                return ref.geo;
            }).$promise;
            //zawsze zwracaj promise, wtedy czeka a wszystko przeczyta i potem inicjuje controller
        },
        signedIn: function() {
            return !!Auth.user.id;
        }     
    };


    $rootScope.$on('Auth:login', function(e, user) {
        //console.log('logged in');
        $rootScope.userid = Auth.user.id;
        //angular.copy(user, Auth.user);
        
    });

    $rootScope.$on('Auth:logout', function(e, user) {
        //console.log('logged out');
        angular.copy({}, Auth.user);
        
    });

    return Auth;

});
