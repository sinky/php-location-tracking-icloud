<?php
error_reporting(E_ALL);

// CACHING
$cache_time = 120; 
$cache_filename = "location.json"; 
$cache_created  = (file_exists($cache_filename)) ? filemtime($cache_filename) : 0;  
if ((time() - $cache_created) < $cache_time) {  
 readfile($cache_filename); 
 die();  
}  
ob_start();
// CACHING

require('flintstone.class.php');

$options = array('dir' => './', 'gzip' => false);
$db['settings'] = Flintstone::load('db_settings', $options);
$db['locations'] = Flintstone::load('db_locations', $options);

function getCoordinates($deviceID = false) {
  global $db;
  $keys = $db['locations']->getKeys();

  foreach ($keys as $location) {
    $entry = $db['locations']->get($location);
    if(!$deviceID || $entry['deviceID'] == $deviceID) {
      $result[] = $entry;
    }
 }
  return $result;
}
$locations = getCoordinates();

foreach ($locations as $entry) {
  $l[] = $entry;
}

echo json_encode($l);
 
// CACHING
file_put_contents($cache_filename, ob_get_contents());  
ob_end_flush();  
// CACHING
?>