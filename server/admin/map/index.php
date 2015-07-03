<?php

if (basename($_SERVER['REQUEST_URI'])=='loading-navi.gif') {
    Header('Content-type: image/gif');
    die(file_get_contents(__DIR__.'/loading-navi.gif'));
}

if (isset($_GET['lat1']) && isset($_GET['lat2']) && isset($_GET['lng1']) && isset($_GET['lng2'])) {
    require_once __DIR__.'/../base.php';

    require_once __DIR__.'/../../rest/models/eventModel.php';

    
    
    if ($_GET['lat1']>$_GET['lat2']) {
        $lat=$_GET['lat1'];
        $_GET['lat1']=$_GET['lat2'];
        $_GET['lat2']=$lat;
    }

    if ($_GET['lng1']>$_GET['lng2']) {
        $lng=$_GET['lng1'];
        $_GET['lng1']=$_GET['lng2'];
        $_GET['lng2']=$lng;
    }
    
    
    $event=new eventModel();
    $events=$event->map($_GET['lat1'],$_GET['lat2'],$_GET['lng1'],$_GET['lng2'],0,0,true)?:[];
    //mydie($events);
    
    Header('Content-type: application/json; charset=utf8'); die(json_encode($events,JSON_NUMERIC_CHECK));
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Jemy Razem</title>
    <style>
      html, body, #map-canvas {
        height: 100%;
        margin: 0px;
        padding: 0px
      }
      #map-canvas {
        text-align: center;
        background-color: #3c73a3;
      }
    </style>
    <script src="//maps.googleapis.com/maps/api/js?v=3.exp&signed_in=true&key=AIzaSyDaAo7uINyxYaszVBCMQIWcpIvJ2dpE8u8"></script>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <script>
        function initialize(lat,lng) {
          var myLatlng = new google.maps.LatLng(lat,lng);
          var mapOptions = {
            zoom: 12,
            center: myLatlng
          }
          var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
          var markerarray=[];
        
            google.maps.event.addListener(map, 'idle', function(ev){
                var bounds = map.getBounds();
                var ne = bounds.getNorthEast();
                var sw = bounds.getSouthWest();                
                
                var url='index.php?lat1='+ne.lat()+'&lng1='+ne.lng()+'&lat2='+sw.lat()+'&lng2='+sw.lng();
                

                $.get(url,function(events) {
                    
                    for (var i = 0; i < markerarray.length; i++) {
                      markerarray[i].setMap(null);
                    }
                    markerarray=[];

                    var markers=[];
                    var active=0;
                    var inactive=0;
                    for(var i=0;i<events.length; i++)
                    {
                        lat=events[i].lat;
                        lng=events[i].lng;
                        
                        while (typeof(markers[lat+','+lng])=='boolean') {
                            lng+=0.0001;
                            lat+=0.0001;
                        }
                        markers[lat+','+lng]=true;
                                                
                        var marker = new google.maps.Marker({
                            position: new google.maps.LatLng(lat,lng),
                            map: map,
                            icon: 'http://maps.google.com/mapfiles/marker_'+(events[i].active==1?'green':'orange')+'.png',
                            title: events[i].name+' '+events[i].event_start,
                            url: 'http://www.jemyrazem.pl/'+events[i].host_url+'/'+events[i].url
                        });
                        google.maps.event.addListener(marker, 'click', function() {
                            window.location.href = this.url;
                        });
                        
                        if (events[i].active==1) {
                            active++;
                        } else {
                            inactive++;
                        }
                        
                        markerarray.push(marker);
                    }
                    $('#infobox').html('<a href="..">Admin</a>, active events: '+active+', inactive: '+inactive);
                });
                
            });        
        
        

        }
        
        google.maps.event.addDomListener(window, 'load', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (pos) {
                    
                    initialize(pos.coords.latitude,pos.coords.longitude);
                    
                });
            }
            
        });

    </script>
  </head>
  <body>
    <div id="map-canvas">
        <img style="margin-top:100px;" src="loading-navi.gif"/><br/>
        <h1 style="color:#fff">Waiting for navigator</h1>
    </div>
    <div style="padding:0.75em; font-family: Tahoma; position: absolute; top:0; left:0; background-color: rgba(150,150,150,0.5);" id="infobox"></div>
  </body>
</html>