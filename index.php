<?php
error_reporting(E_ALL);

require('flintstone.class.php');

$options = array('dir' => './', 'gzip' => true);
$db['locations'] = Flintstone::load('db_locations', $options);

function getCoordinates($deviceID) {
  global $db;
  $keys = $db['locations']->getKeys();

  foreach ($keys as $location) {
    $entry = $db['locations']->get($location);
    if($entry['deviceID'] == $deviceID) {
      $result[] = $entry;
    }
  }
  return $result;
}
?>
<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Simple markers</title>
    <style>
    #map-canvas {
      width: 100%;
      height:500px;
    }
    
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
    <script>
    google.maps.visualRefresh = true;

    function initialize() {
      var myLatlng = new google.maps.LatLng(51.4,7.6);
      var mapOptions = {
        zoom: 10,
        center: myLatlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
      }
      var map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
    
    <?php
      $keys = $db['locations']->getKeys();
      
      foreach ($keys as $location) {
        $entry = $db['locations']->get($location);
        //echo 'new google.maps.Marker({position: new google.maps.LatLng('.$entry['lat'].','.$entry['lng'].'), map: map, icon: "http://load.my-azur.de/f/o/measle_red.png"});'."\n";
        echo 'new google.maps.Marker({position: new google.maps.LatLng('.$entry['lat'].','.$entry['lng'].'), map: map, icon: "https://mts.googleapis.com/vt/icon/name=icons/spotlight/spotlight-poi.png&scale=0.5"});'."\n";
        //echo 'new google.maps.Marker({position: new google.maps.LatLng('.$entry['lat'].','.$entry['lng'].'), map: map});'."\n";
      }
    ?> 
    }
    
    google.maps.event.addDomListener(window, 'load', initialize);

    </script>
  </head>
  <body>
<?php 

if(isset($_GET['device'])){
  echo json_encode(getCoordinates($_GET['device']));
}else{
  $keys = $db['locations']->getKeys(); // returns array('bob', 'joe', ...)
  echo "<table>";
    echo "<tr>";
    echo "<th>deviceID</th>";
    echo "<th>name</th>";
    echo "<th>lat</th>";
    echo "<th>lng</th>";
    echo "<th>locationType</th>";
    echo "<th>place</th>";
    echo "<th>locationTimestamp</th>";
    echo "<th>batteryLevel</th>";
    echo "</tr>";
  foreach ($keys as $location) {
    $entry = $db['locations']->get($location);
    echo "<tr>";
    echo "<td>".$entry['deviceID']."</td>";
    echo "<td>".$entry['name']."</td>";
    echo "<td>".$entry['lat']."</td>";
    echo "<td>".$entry['lng']."</td>";
    echo "<td>".$entry['locationType']."</td>";
    echo "<td><a href='http://maps.googleapis.com/maps/api/staticmap?center=".$entry['place']."&size=400x200&sensor=false' target='_blank' title='Position als Maps Image'>".$entry['place']."</a></td>";
    echo "<td><time datetime='".$entry['locationTimestamp']."'>".date("d.m.Y H:i:s",$entry['locationTimestamp'])."</time></td>";
    echo "<td>".$entry['batteryLevel']."</td>";
    echo "</tr>";
  }
  echo "</table>";
}
?>  
    <div id="map-canvas"></div>
  </body>
</html>