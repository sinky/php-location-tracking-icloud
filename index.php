<?php
error_reporting(E_ALL);

require('flintstone.class.php');

$options = array('dir' => './', 'gzip' => false);
$db['settings'] = Flintstone::load('db_settings', $options);

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no">
<meta charset="utf-8">
<title>Find my iPhone - Location Tracking</title>
<style>
  html, body {
  padding: 0;
  margin: 0;
  height: 100%;
  font-family: sans-serif;
}

#map-container{
  position: relative;
}

#map-container, #map-canvas {
  width: 100%;
  height:100%;
}

#status {
  position: absolute;
  bottom: 13px;
  right: 0;
  z-index: 99999999;
  color: #444;
  background: rgba(255,255,255,0.5);
  font-size: 80%;
}

#buttons {
  position: absolute;
  bottom:30px;
  right: 0;
  z-index: 99999999;
  color: #444;
  background: rgba(255,255,255,0.5);
  font-size: 80%;
}

.gm-style-iw h1,
.gm-style-iw h2,
.gm-style-iw h3{
  margin: 0 0 0.5em 0;
}

.spinner {
  display: block;
  position: fixed;
  z-index: 100;
  top: 50%;
  right: 50%;
  margin-right: -25px;
  margin-top: -25px;
  color: #444;
  background: rgba(255,255,255,0.8);
  padding: 5px;
  border-radius: 5px;
}

.spinner-icon {
  width: 50px;
  height: 50px;
  box-sizing: border-box;
  margin: 0 auto;
  border: solid 5px transparent;
  border-top-color: #444;
  border-left-color: #444;
  border-bottom-color: #444;
  border-radius: 50%;
  -webkit-animation: nprogress-spinner 800ms linear infinite;
          animation: nprogress-spinner 800ms linear infinite;
}

@-webkit-keyframes nprogress-spinner {
  0%   { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}
@keyframes nprogress-spinner {
  0%   { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

</style>
</head>
<body>
<div class="spinner" role="spinner">
  <div class="spinner-icon"></div>
  Loading
</div>

<div id="map-container">
  <div id="buttons"><button id="heatmap">Heatmap/Marker</button></div>
  <div id="status">Lastcheck: <?php echo date("d.m.Y H:i:s", $db['settings']->get('lastcheck')); ?> &mdash; NextCheck: <?php echo -(-3600+time()-$db['settings']->get('lastcheck')); ?> Sekunden</div>
  <div id="map-canvas"></div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script src="//maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=visualization"></script>
<script>
var map;

/*window.onload = function() {
  var script = document.createElement('script');
  script.type = 'text/javascript';
  script.src = 'https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false&libraries=visualization&callback=initialize';
  document.body.appendChild(script);
};*/

initialize();

function initialize() {
  google.maps.visualRefresh = true;
  
  map = new google.maps.Map(document.getElementById('map-canvas'), {
    zoom: 10,
    center: new google.maps.LatLng(51.5,7.5),
    mapTypeId: google.maps.MapTypeId.ROADMAP
  });
  
  $.getJSON( "location.json.php", function( data ) {
    addLocation(data);
    $('.spinner').hide();
    
    if(location.search.indexOf("showtable") != -1) {
      showDBTable(data);
    }
  });  
}

function addLocation(locationJSON) {
  var iconGoogle = { url: "https://mts.googleapis.com/vt/icon/name=icons/spotlight/spotlight-poi.png&scale=1" };    
  var iconRed = { anchor: new google.maps.Point(11, 11), url: "http://load.my-azur.de/f/o/dot-red.png" };
  var iconGreen = { anchor: new google.maps.Point(11, 11), url: "http://load.my-azur.de/f/o/dot-green.png" };
  var iconBlue = { anchor: new google.maps.Point(11, 11), url: "http://load.my-azur.de/f/o/dot-blue.png"};

  var infowindow = new google.maps.InfoWindow();
  
  var locations = locationJSON;
  var allLatLngs = [];      
  var allMarker = [];     

  // Fit All Markers (http://blog.shamess.info/2009/09/29/zoom-to-fit-all-markers-on-google-maps-api-v3/)
  var allMarkerBounds = new google.maps.LatLngBounds();  
   
  // Display all location marker
  for (var i = 0; i < locations.length; i++) {  
    var latlng = new google.maps.LatLng(locations[i].lat, locations[i].lng);
    
    allMarkerBounds.extend(latlng); // for "Fit All Markers"
    allLatLngs.push(latlng); // for "Heatmap"

    // Marker
    var marker = new google.maps.Marker({
      position: latlng,
      icon: iconRed,
      //map: map
    });

    allMarker.push(marker);
  
    // Infowindow on Marker
    google.maps.event.addListener(marker, 'click', (function(marker, i) {
      return function() {
        var date = new Date(locations[i].locationTimestamp*1000);
        var locationDate = date.getDate()+"."+(date.getMonth()+1)+"."+date.getFullYear()+" "+date.getHours()+":"+date.getMinutes()+":"+date.getSeconds();
        infowindow.setContent("<h3>"+locations[i].place+"</h3>"+locationDate);
        infowindow.open(map, marker);
      }
    })(marker, i));
  }
  
  // Fit All Markers
  map.fitBounds(allMarkerBounds); 

  // Heatmap
  heatmap = new google.maps.visualization.HeatmapLayer({
    data: new google.maps.MVCArray(allLatLngs)
  });  
  var gradient = [ 'rgba(0, 255, 255, 0)', 'rgba(0, 255, 255, 1)', 'rgba(0, 191, 255, 1)', 'rgba(0, 127, 255, 1)', 'rgba(0, 63, 255, 1)', 'rgba(0, 0, 255, 1)', 'rgba(0, 0, 223, 1)', 'rgba(0, 0, 191, 1)', 'rgba(0, 0, 159, 1)', 'rgba(0, 0, 127, 1)', 'rgba(63, 0, 91, 1)', 'rgba(127, 0, 63, 1)', 'rgba(191, 0, 31, 1)', 'rgba(255, 0, 0, 1)' ];
  var gradient = [ 'rgba(0, 0, 0, 0)', 'rgba(33,255,0,1)', 'rgba(244,255,50,1)', 'rgba(255,139,50,1)', 'rgba(255,50,50,1)' ];
  heatmap.setOptions({ gradient:gradient, radius: 15, opacity: 0.8, maxIntensity: 5});
  heatmap.setMap(map);
  
  // Marker Last
  markerLast = new google.maps.Marker({
    position: allLatLngs[allLatLngs.length-1],
    icon: iconBlue,
    zIndex: 9999,
   // map: map
  }); 
  
  // events
  google.maps.event.addDomListener(map, 'click', function() { infowindow.close(); });
  google.maps.event.addDomListener(map, 'rightclick', geoLocation);
  
  // functions
  function geoLocation() {
    // Try HTML5 geolocation
    if(navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
        map.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
      }, function() {
        handleNoGeolocation(true);
      });
    } else {
      // Browser doesn't support Geolocation
      handleNoGeolocation(false);
    }  
  }
  function handleNoGeolocation(errorFlag) {
    if (errorFlag) {
      var content = 'Error: The Geolocation service failed.';
    } else {
      var content = 'Error: Your browser doesn\'t support geolocation.';
    }
    var options = {
      map: map,
      position: new google.maps.LatLng(51.4,7.6),
      content: content
    };
    var infowindow = new google.maps.InfoWindow(options);
    map.setCenter(options.position);
  }    
  function toggleHeatmap() {
    heatmap.setMap(heatmap.getMap() ? null : map);
		markerLast.setMap(markerLast.getMap() ? null : map);
    for (var i = 0; i < allMarker.length; i++) {
      allMarker[i].setMap(allMarker[i].getMap() ? null : map);      
    }    
  }
  $('#heatmap').click(toggleHeatmap);  
}

function showDBTable(data) {
  var tbl_body = "", tbl_head = "", tbl_head = "";
  
  tbl_head += "<th>ID</th>";
  tbl_head += "<th>name</th>";
  tbl_head += "<th>timestamp</th>";
  tbl_head += "<th>lat</th>";
  tbl_head += "<th>lng</th>";
  tbl_head += "<th>type</th>";
  tbl_head += "<th>place</th>";   
  $.each(data, function() {      
    var tbl_row = "";
    tbl_row += "<td>"+this.deviceID+"</td>";
    tbl_row += "<td>"+this.name+"</td>";
    tbl_row += "<td>"+this.locationTimestamp+"</td>";
    tbl_row += "<td>"+this.lat+"</td>";
    tbl_row += "<td>"+this.lng+"</td>";
    tbl_row += "<td>"+this.locationType+"</td>";
    tbl_row += "<td>"+this.place+"</td>";
    tbl_body += "<tr>"+tbl_row+"</tr>";                 
  });
  tbl_head += "<thead><tr>"+tbl_head+"</tr></thead>"; 
  tbl_body = "<tbody>"+tbl_body+"</tbody>";
  $('#map-container').height("60%"); 
  var table = $('<table id="db" cellspacing="5" />').appendTo('body');
  table.append(tbl_head);
  table.append(tbl_body);
}
</script>
</body>
</html>
