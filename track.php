<?php
error_reporting(E_ALL);

// Maps image API
// https://maps.googleapis.com/maps/api/staticmap?center=Schwerte,%20Nordrhein-Westfalen,%20Deutschland&size=400x200&sensor=false

// Maps Coordinates to Adress
// http://maps.googleapis.com/maps/api/geocode/json?latlng=51.413263941776,7.5642276952297&sensor=false&language=de

// Maps Adress to Coordinates
// http://maps.googleapis.com/maps/api/geocode/json?address=Schwerte,%20Nordrhein-Westfalen,%20Deutschland&sensor=false&language=de

if( file_exists('config.php'))	require_once('config.php');

if(! defined('ICLOUD_USER') ) define('ICLOUD_USER', 'CHANGE_ME');
if(! defined('ICLOUD_PASS') ) define('ICLOUD_PASS', 'CHANGE_ME');
if(! defined('DEVICES') ) define('DEVICES', '0');

require('flintstone.class.php');
$options = array('dir' => './', 'gzip' => true);
$db['settings'] = Flintstone::load('db_settings', $options);
$db['locations'] = Flintstone::load('db_locations', $options);

if(isset($_GET['debug'])) { debug(); }

getPosition( explode(',', DEVICES) );

function getPosition($deviceIDs) {
  if(!is_array($deviceIDs)) { die("deviceID muss vom Type Array sein"); }
  global $db;

  $lastcheck = $db['settings']->get('lastcheck');

  if ($lastcheck < ( time() - 3600 ) ) {
    require('class.sosumi.php');
    $ssm = new Sosumi(ICLOUD_USER, ICLOUD_PASS);
    foreach($deviceIDs as $deviceID) {

      if(!array_key_exists($deviceID, $ssm->devices)){ continue; }

      $newLocation['deviceID'] = $deviceID;
      $newLocation['name'] = $ssm->devices[$deviceID]->name;
      $newLocation['deviceClass'] = $ssm->devices[$deviceID]->deviceClass;
      $newLocation['deviceModel'] = $ssm->devices[$deviceID]->deviceModel;
      $newLocation['lat'] = $ssm->devices[$deviceID]->latitude;
      $newLocation['lng'] = $ssm->devices[$deviceID]->longitude;
      $newLocation['locationType'] = $ssm->devices[$deviceID]->locationType;

      $newLocation['place'] = reverse_geocode($newLocation['lat'],$newLocation['lng']);

      $newLocation['locationTimestamp'] = strtotime($ssm->devices[$deviceID]->locationTimestamp); // ist minus 9 Stunden

      //$newLocation['batteryLevel'] = round($ssm->devices[$deviceID]->batteryLevel, 2)*100;
      $newLocation['batteryLevel'] = ceil($ssm->devices[$deviceID]->batteryLevel*100)+1;

      $db['locations']->set(uniqid(time().'_'), $newLocation);
      $db['settings']->set('lastcheck', time());
    }
  }// if $lastcheck
}

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

function debug() {
  require('class.sosumi.php');
  $ssm = new Sosumi(ICLOUD_USER, ICLOUD_PASS);
  var_dump($ssm);
  exit;
}