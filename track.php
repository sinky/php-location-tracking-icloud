<?php
error_reporting(E_ALL);
require('flintstone.class.php');

if( file_exists('config.php'))	require_once('config.php'); // Config laden wenn Datei existiert

// Default config
if(! defined('ICLOUD_USER') ) define('ICLOUD_USER', 'CHANGE_ME');
if(! defined('ICLOUD_PASS') ) define('ICLOUD_PASS', 'CHANGE_ME');
if(! defined('DEVICES') ) define('DEVICES', '0');

// Datenbanken
$options = array('dir' => './', 'gzip' => false);
$db['settings'] = Flintstone::load('db_settings', $options);
$db['locations'] = Flintstone::load('db_locations', $options);

// Location Funktion aufrufen
getPosition( explode(',', DEVICES) );

function getPosition($deviceIDs) {
  if(!is_array($deviceIDs)) { die("deviceID muss vom Type Array sein"); }
  global $db;

  $lastcheck = $db['settings']->get('lastcheck');
  $lastlat = $db['settings']->get('lastlat') ;
  $lastlng = $db['settings']->get('lastlng');

  if ($lastcheck < ( time() - 3600 ) ) { // Letzter Check mindesten 1 Stunde alt?
    require('class.sosumi.php');
    $ssm = new Sosumi(ICLOUD_USER, ICLOUD_PASS);

    foreach($deviceIDs as $deviceID) { // Nur in config.php definierte Geräte

      if(!array_key_exists($deviceID, $ssm->devices)){ continue; } // Wenn es die DeviceID by Apple nicht gibt Rest des loop überspringen
      
      $newLocation = array();
      $newLocation['deviceID'] = $deviceID;
      $newLocation['name'] = $ssm->devices[$deviceID]->name;
      $newLocation['deviceClass'] = $ssm->devices[$deviceID]->deviceClass;
      $newLocation['deviceModel'] = $ssm->devices[$deviceID]->deviceModel;
      $newLocation['lat'] = $ssm->devices[$deviceID]->latitude;
      $newLocation['lng'] = $ssm->devices[$deviceID]->longitude;
      $newLocation['locationType'] = $ssm->devices[$deviceID]->locationType;
      $newLocation['locationTimestamp'] = strtotime($ssm->devices[$deviceID]->locationTimestamp); // ist minus 9 Stunden
      $newLocation['batteryLevel'] = ceil($ssm->devices[$deviceID]->batteryLevel*100)+1;
      
      // Distanz
      // Wenn mindestens 1 KM zwischen aktueller und letzter Position, wenn nicht abbrechen
      $distance = gpsDistance($lastlat,$lastlng,$newLocation['lat'],$newLocation['lng']); // in Metern
      $distanceKM = round($distance/1000,0); // to Kilometern
      if($distanceKM > 1) {
        $newLocation['distance'] = round($distance, 0);

        // Google Maps API Koordinate->Adresse
        $newLocation['place'] = reverse_geocode($newLocation['lat'],$newLocation['lng']);
     
        // Location speichern
        $db['locations']->set(uniqid(time().'_'), $newLocation); // Array in DB speichern
      
        // Settings speichern
        $db['settings']->set('lastcheck', time()); // Letzten Check in settings DB speichern
        $db['settings']->set('lastlat', $newLocation['lat']); // letzten Koordinaten in settings DB
        $db['settings']->set('lastlng', $newLocation['lng']); // für Distanz Berechnung speichern

        echo "Device location gespeichert: ".$deviceID;
      }else{
        echo "Distanz: ".$distanceKM." KM zu niedrig";
      }
      $db['settings']->set('lastcheck', time()); // Letzten Check in settings DB speichern
    }
  }else{
    echo "zu früh";
  }// if $lastcheck
}

// Google Maps API
function reverse_geocode($lat,$lng) {
  $url = "http://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$lng&sensor=false&language=de";
  $result = file_get_contents("$url");
  $json = json_decode($result);

  if($json->status != "OK"){ return ""; }

  $result = $json->results[0];

  foreach($result->address_components as $addressPart) {
    if((in_array('locality', $addressPart->types)) && (in_array('political', $addressPart->types))) {
      $city = $addressPart->long_name;
    }else if((in_array('administrative_area_level_1', $addressPart->types)) && (in_array('political', $addressPart->types))) {
      $state = $addressPart->long_name;
    }else if((in_array('country', $addressPart->types)) && (in_array('political', $addressPart->types))) {
      $country = $addressPart->long_name;
    }
  }

  if(!empty($city)) { $implode[] = $city; }
  if(!empty($state)) { $implode[] = $state; }
  if(!empty($country)) { $implode[] = $country; }

  $location = implode(", ", $implode );
  return $location;
}


// Distanz zwischen zwei GPS koordinaten
// http://stackoverflow.com/a/10054282
function gpsDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $lonDelta = $lonTo - $lonFrom;
  $a = pow(cos($latTo) * sin($lonDelta), 2) +
    pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
  $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

  $angle = atan2(sqrt($a), $b);
  return $angle * $earthRadius;
}
