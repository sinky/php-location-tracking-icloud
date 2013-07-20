<?php 
error_reporting(E_ALL);

require('flintstone.class.php');

$options = array('dir' => './', 'gzip' => true);
$db['locations'] = Flintstone::load('db_locations', $options);

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
    echo "<td>".$entry['locationTimestamp']."</td>";
    echo "<td>".$entry['batteryLevel']."</td>";
    echo "</tr>";
  } 
  echo "</table>";   
}

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