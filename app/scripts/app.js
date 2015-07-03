/* global app:true */
/* exported app */

'use strict';
 

/**
 * @ngdoc overview
 * @name eatApp
 * @description
 * # eatApp
 *
 * Main module of the application.
 */


var app = angular
    .module('eatApp', [
        'ngAnimate',
        'ngCookies',
        'ngResource',
        'ngRoute',
        'ngSanitize',
        'ngTouch',
        'ngMessages',
        'reCAPTCHA',
        'config',
        'uiGmapgoogle-maps',
        'flow',
        'gettext',
        'ngAutocomplete',
        'ui.bootstrap',
        'ui.bootstrap-slider',
        'infinite-scroll',
        'nsPopover',
        'ngProgress',
        'bootstrapLightbox',
        'ngDialog'
    ])
    .config(function(uiGmapGoogleMapApiProvider) {
        uiGmapGoogleMapApiProvider.configure({
            key: 'AIzaSyCehlb1DxZ1pxdACYBBUm7ocnjZORSxOY0',
            v: '3.17',
            //libraries: 'places,weather,geometry,visualization'
        });
    })
    .config(['$compileProvider', function ($compileProvider) {
        $compileProvider.debugInfoEnabled(false);
    }])
    .config(['flowFactoryProvider', function (flowFactoryProvider) {
        flowFactoryProvider.defaults = {
            target: '/image',
            permanentErrors:[404, 500, 501]
        };
        // You can also set default events:
        //flowFactoryProvider.on('catchAll', function (event) {
        //  ...
        //});
        // Can be used with different implementations of Flow.js
        // flowFactoryProvider.factory = fustyFlowFactory;
    }])
    .config(function ($sceDelegateProvider) {
        $sceDelegateProvider.resourceUrlWhitelist([
           // Allow same origin resource loads.
           'self',
           // Allow loading from our assets domain.  Notice the difference between * and **.
           'https://www.facebook.com/**',
           'https://plus.google.com/**',
           'https://twitter.com/**'
        ]);
    })
    .config(function (LightboxProvider) {
      // set a custom template
      LightboxProvider.templateUrl = 'views/lightbox.html';
    })
    .config(function($routeProvider, $locationProvider,$httpProvider, reCAPTCHAProvider) {
        
        reCAPTCHAProvider.setPublicKey('6Le55fwSAAAAAJWv435Os_Jrw_x7vibjKpXBUG9H');
         // optional: gets passed into the Recaptcha.create call
        reCAPTCHAProvider.setOptions({
                theme: 'clean'
            });
        //delete $httpProvider.defaults.headers.common['X-Requested-With'];
        //$httpProvider.defaults.headers.common['Access-Control-Allow-Origin'] ='*';
        //$httpProvider.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        //$httpProvider.defaults.headers.common['X-Frame-Options'] = 'ALLOW';
        
        // nie id options
        //$httpProvider.defaults.useXDomain = true;

        //Reset headers to avoid OPTIONS request (aka preflight)
//         $httpProvider.defaults.headers.common = {};
  //        $httpProvider.defaults.headers.post = {};
    //      $httpProvider.defaults.headers.put = {};
      //    $httpProvider.defaults.headers.patch = {};

        //required cookie - to musi byc
        $httpProvider.defaults.withCredentials = true;

        //$resourceProvider.defaults.stripTrailingSlashes = false;
        

        $routeProvider
            .when('/', {
              templateUrl: 'views/homepage.html',
              controller: 'HomepageCtrl',
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            }) 
            .when('/about', {
              templateUrl: 'views/about.html',
              controller: 'AboutCtrl'
            })
            .when('/privacy', {
              templateUrl: 'views/privacy.html',
              controller: 'PrivacyCtrl'
            })
            .when('/gallery', {
                templateUrl: 'views/gallery.html',
                controller: 'GalleryCtrl',
                resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })            
            .when('/event', {
              templateUrl: 'views/event.html',
              controller: 'EventCtrl',
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })
            .when('/event/:id', {
              templateUrl: 'views/event.html',
              controller: 'EventCtrl',
              resolve: {
                    "user": function(Auth) {
                        return Auth.resolveUser();
                    },
                    "geo" : function (Auth) {
                        return Auth.resolveGeo(); //promise
                    },
                    "ev": function(EVENT, $route) {
                        //$route.current.params must be used
                        return EVENT.get({"id": $route.current.params.id});
                    }
                }
            })
            .when('/events/:lat/:lng/:distance?/:search?', {
              templateUrl: 'views/events.html',
              controller: 'SearchCtrl',
              reloadOnSearch: false,
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })
            .when('/events/:all?', {
              templateUrl: 'views/events.html',
              controller: 'SearchCtrl',
              reloadOnSearch: false,
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })            
            .when('/events/:all*', {
              templateUrl: 'views/events.html',
              controller: 'SearchCtrl',
              reloadOnSearch: false,
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })                                              
            .when('/posts/:postId', {
                templateUrl: 'views/postview.html',
                controller: 'PostViewCtrl'

            })
            .when('/register', {
                templateUrl: 'views/register.html',
                controller: 'AuthCtrl',
                resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })
            .when('/login', {
                templateUrl: 'views/login.html',
                controller: 'AuthCtrl',
                resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })
            .when('/book', {
              templateUrl: 'views/book.html',
              controller: 'BookCtrl',
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })            
            .when('/how-it-works', {
                templateUrl: 'views/how-it-works.html',
                controller: 'AuthCtrl',
                resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }
            })
            .when('/profile', {
                templateUrl: 'views/profile.html',
                controller: 'ProfileCtrl as prctrl' ,
                resolve: {
                    "user": function(Auth) {
                        return Auth.resolveUser();  //promise
                    },
                    "geo" : function (Auth) {
                        return Auth.resolveGeo(); //promise
                    },
                    "ev": function(EVENT) {
                        return EVENT.read();
                    }
                }
            })
            .when('/error404', {
                templateUrl: 'views/error404.html',
            })

            .when('/tac', {
                templateUrl: 'views/tac.html',
                controller: 'TacCtrl',
                resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }             
            })
            .when('/contact', {
              templateUrl: 'views/contact.html',
              controller: 'ContactCtrl',
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                } 
            })
            .when('/konkurs', {
              templateUrl: 'views/konkurs.html',
              controller: 'KonkursCtrl'
            })
            .when('/konkurs-regulamin', {
              templateUrl: 'views/konkurs-regulamin.html',
              controller: 'KonkursRegulaminCtrl'
            })  
            .when('/password/:resetstring', {
              templateUrl: 'views/remindpass.html',
              controller: 'RemindpassCtrl'
            })          
            .when('/test', {
                templateUrl: 'views/posts.html',
                controller: 'PostsCtrl',
                resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }             
            })            
            .when('/:username', {
              templateUrl: 'views/publicprofile.html',
              controller: 'PublicProfileCtrl',
              reloadOnSearch: false,
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    }
                }              
            })
            .when('/:username/:eventname', {
              templateUrl: 'views/publicevent.html',
              controller: 'PubliceventCtrl',
              resolve: {
                    user: function(Auth) {
                        return Auth.resolveUser();
                    },
                    "event": function (PUBLICEVENT, $route) {
                        //$route.current.params must be used
                        return PUBLICEVENT.get({"username": $route.current.params.username, "event": $route.current.params.eventname});
                    }

                }
            })             


            .otherwise({
                redirectTo: '/'
            });

            $locationProvider.html5Mode({enabled:true,requireBase:true,rewriteLinks:true});

        
    })
    .run(function(gettextCatalog, ENV, $http, LANGS, $rootScope, Auth, $location,$anchorScroll, $routeParams, $route, REFERER){
        gettextCatalog.currentLanguage = 'pl';
        gettextCatalog.debug = true;
        gettextCatalog.debugPrefix = '';

        //get-set user-time server
        $http.get(ENV.apiServer + 'user/time?d='+encodeURIComponent(new Date()));

        $rootScope.$on("$routeChangeStart", function (event, next, current) {

            if (next.templateUrl === "views/profile.html" || next.templateUrl === "views/event.html") {
                //console.log('login required');
                Auth.resolveUser().
                    then(function(data) {
                        var a = Auth.signedIn();
                        //console.log(a);
                        if (!a) $location.path('/login');
                    });
                

            }

        });
        $rootScope.$on('$routeChangeSuccess', function(newRoute, oldRoute) {

        });

        $rootScope.$on ("$locationChangeStart", function (event, next, current) {
           
            if ($routeParams.referer) {
                //console.log($routeParams.referer);

                REFERER.get({"referer": encodeURI($routeParams.referer)});

            };
        });
        var params = $location.search();
        if (params.referer) {
                REFERER.get({"referer": params.referer});
        };   

    });

app.directive('contenteditable', function() {
    return {
      restrict: 'A', // only activate on element attribute
      require: '?ngModel', // get a hold of NgModelController
      link: function(scope, element, attrs, ngModel) {
        if(!ngModel) return; // do nothing if no ng-model

        // Specify how UI should be updated
        ngModel.$render = function() {
          element.html(ngModel.$viewValue || '');
        };

        // Listen for change events to enable binding
        element.on('blur keyup change', function() {
          scope.$apply(read);
        });
        read(); // initialize

        // Write data to the model
        function read() {
          var html = element.html();
          // When we clear the content editable the browser leaves a <br> behind
          // If strip-br attribute is provided then we strip this out
          if( attrs.stripBr && html == '<br>' ) {
            html = '';
          }
          ngModel.$setViewValue(html);
        }
      }
    };
  });


app.directive('numeric', function() {
  return {
    require: 'ngModel',
    link: function(scope, elm, attrs, ctrl) {
      ctrl.$validators.integer = function(modelValue, viewValue) {
        if (ctrl.$isEmpty(modelValue)) {
          // consider empty models to be valid
          return true;
        }
        var INTEGER_REGEXP = /[\-\+]?[0-9]*(\.[0-9]+)?/;
        if (INTEGER_REGEXP.test(viewValue)) {
          // it is valid
          return true;
        }

        // it is invalid
        return false;
      };
    }
  };
});



// filtr do URLi, powinien by w osobnym JS, ale to pozniej
app.filter('hostnameFromUrl', function() {
    return function(str) {
        var url = document.createElement('a');

        url.href = str;

        return url.hostname;
    };
});


// {{some_text | cut:true:100:' ...'}}
// Options:
// wordwise (boolean) - if true, cut only by words bounds,
// max (integer) - max length of the text, cut to this number of chars,
// tail (string, default: ' …') - add this string to the input string if the string was cut.
app.filter('cut', function () {
    return function (value, wordwise, max, tail) {
        if (!value) return '';

        max = parseInt(max, 10);
        if (!max) return value;
        if (value.length <= max) return value;

        value = value.substr(0, max);
        if (wordwise) {
            var lastspace = value.lastIndexOf(' ');
            if (lastspace != -1) {
                value = value.substr(0, lastspace);
            }
        }

        return value + (tail || ' …');
    };
});





