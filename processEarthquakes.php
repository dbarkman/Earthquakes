<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$peq = new processEarthquakes();

class processEarthquakes
{
	private $_container;
	private $_logger;
	private $_db;
    private $_table;
    private $_sendPushes;
    private $_store;
    private $_notify;
    private $_sendPush;
    private $_earthquakeId;
    private $_url;
    private $_bigDataCloudKey;
    private $_pushesDebug;

	public function __construct()
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
		$this->_db = $this->_container->getMySQLDBConnect();
        $this->_table = 'earthquakes';

        $this->_sendPushes = new SendPushes();
        $this->_pushesDebug = 0;

		$properties = $this->_container->getProperties();
        $this->_store = $properties->getStoreValue();
        $this->_notify = $properties->getNotifyValue();
        $this->_sendPush = $properties->getNotifyValue();
		$urlHour = $properties->getUrlHour();
		$urlDay = $properties->getUrlDay();
		$this->_url = $urlDay; //configure hour or day here
		$this->_bigDataCloudKey = $properties->getKeyBigDataCloud();

		$this->getEarthquakes();
	}

	public function getEarthquakes() {
		$this->_logger->info('----------------------------------------');
		$this->_logger->info('Checking for earthquakes!');

		$usgs = new USGS($this->_logger);
        $earthquakes = $usgs->getEarthquakes($this->_url);
//        var_dump($earthquakes);
//        exit();
        if (isset($earthquakes->features)) {
            $earthquakeArray = $earthquakes->features;

            $newEarthquakeCount = 0;
            $failedEarthquakeCount = 0;
            $updatedEarthquakeCount = 0;
            $failedUpdateEarthquakeCount = 0;

            foreach ($earthquakeArray as $earthquakeElement) {
                if (!empty($earthquakeElement->id)) {
                    $earthquake = new Earthquake($this->_logger, $this->_db, $earthquakeElement);
                    $this->_earthquakeId = $earthquake->getId();

                    $earthquakeEntry = $earthquake->getEarthquakeExists($this->_table);
                    if ($earthquakeEntry != 0) {
                        $updatedDB = $earthquake->getDBUpdateDate($this->_table);
                        $updatedAPI = $earthquake->getAPIUpdateDate();
                        if ($updatedDB < $updatedAPI) {
                            $latitudeDB = round($earthquake->getDBLatitude($this->_table), 1);
                            $longitudeDB = round($earthquake->getDBLongitude($this->_table), 1);
                            $latitudeAPI = round($earthquake->getLatitude(), 1);
                            $longitudeAPI = round($earthquake->getLongitude(), 1);
                            if ($latitudeDB != $latitudeAPI || $longitudeDB != $longitudeAPI) {
                                $earthquake->setBDCLocationData($this->_bigDataCloudKey);
                                EarthquakeLocation::deleteEarthquakeLocationConnections($this->_logger, $this->_db, $earthquakeEntry);
                                $geocode = $earthquake->getGeocode();
                                if (isset($geocode->localityInfo->administrative)) {
                                    $administrativeArray = $geocode->localityInfo->administrative;
                                    foreach ($administrativeArray as $adminLocation) {
                                        $this->createLocation($adminLocation, $earthquakeEntry);
                                    }
                                }
                                if (isset($geocode->localityInfo->informative)) {
                                    $informativeArray = $geocode->localityInfo->informative;
                                    foreach ($informativeArray as $infoLocation) {
                                        $this->createLocation($infoLocation, $earthquakeEntry);
                                    }
                                }
                            }
                            $earthquake->setDate();
                            $earthquake->setDistance();
                            $earthquake->setLocation();
                            if ($earthquake->updateEarthquake($this->_table)) {
                                $this->_logger->debug('Earthquake updated: ' . $this->_earthquakeId . ' - ' . $earthquakeEntry);
                                $updatedEarthquakeCount++;
                            } else {
                                $this->_logger->warn('🤯 Earthquake NOT updated: ' . $this->_earthquakeId . ' - ' . $earthquakeEntry);
                                $failedUpdateEarthquakeCount++;
                            }
                        }
                    } else {
                        try {
                            $earthquake->setDate();
                            $earthquake->setDistance();
                            $earthquake->setLocation();
                            $earthquake->setBDCLocationData($this->_bigDataCloudKey);
                            if ($this->_store === "TRUE") {
                                if ($earthquakeEntry = $earthquake->saveEarthquake($this->_table)) {
                                    $this->_logger->debug('Earthquake added: ' . $this->_earthquakeId . ' - ' . $earthquakeEntry);
                                    $newEarthquakeCount++;
                                    $geocode = $earthquake->getGeocode();
                                    if (isset($geocode->localityInfo->administrative)) {
                                        $administrativeArray = $geocode->localityInfo->administrative;
                                        foreach ($administrativeArray as $adminLocation) {
                                            $this->createLocation($adminLocation, $earthquakeEntry);
                                        }
                                    }
                                    if (isset($geocode->localityInfo->informative)) {
                                        $informativeArray = $geocode->localityInfo->informative;
                                        foreach ($informativeArray as $infoLocation) {
                                            $this->createLocation($infoLocation, $earthquakeEntry);
                                        }
                                    }
                                } else {
                                    $this->_logger->warn('🤯 Earthquake NOT added: ' . $this->_earthquakeId);
                                    $failedEarthquakeCount++;
                                }
                            }
                            if ($this->_notify === "TRUE") {
                                $this->sendTwitterNotification($earthquakeElement);
                                $lat = $earthquake->getLatitude();
                                $long = $earthquake->getLongitude();
                                $this->_logger->debug('Lat: ' . $lat . ' - Long: ' . $long);
                            }
                            if ($this->_sendPush === "TRUE") {
                                $this->_logger->info("********** Sending Push Notifications **********");
                                $this->sendPushNotification($earthquakeElement);
                            }
                        } catch (mysqli_sql_exception $e) {
                            $this->_logger->error('Problem with earthquake data: ' . $this->_earthquakeId . ', could not insert into database: ' . $e->getMessage());
                        } catch (Exception $e) {
                            $this->_logger->error('Extensive problems with earthquake data: ' . $this->_earthquakeId . ', could not insert into database: ' . $e->getMessage());
                        }
                    }
                } else {
                    $this->_logger->warn('Could not extract an id for an earthquake (exception not thrown).');
                }
            }

            if ($newEarthquakeCount > 0) {
                $earthquakeLabel = ($newEarthquakeCount == 1) ? 'earthquake' : 'earthquakes';
                $this->_logger->info($newEarthquakeCount . ' ' . $earthquakeLabel . ' added and reported.');
                if ($failedEarthquakeCount > 0) {
                    $earthquakeLabel = ($failedEarthquakeCount == 1) ? 'earthquake' : 'earthquakes';
                    $this->_logger->info($failedEarthquakeCount . ' ' . $earthquakeLabel . ' failed on insert.');
                } else {
                    $this->_logger->info('No earthquakes failed database insert.');
                }
            } else {
                $this->_logger->info('No new earthquakes to add.');
            }
            if ($updatedEarthquakeCount > 0) {
                $earthquakeLabel = ($updatedEarthquakeCount == 1) ? 'earthquake' : 'earthquakes';
                $this->_logger->info($updatedEarthquakeCount . ' ' . $earthquakeLabel . ' updated.');
                if ($failedUpdateEarthquakeCount > 0) {
                    $earthquakeLabel = ($failedUpdateEarthquakeCount == 1) ? 'earthquake' : 'earthquakes';
                    $this->_logger->info($failedUpdateEarthquakeCount . ' ' . $earthquakeLabel . ' failed on update.');
                } else {
                    $this->_logger->info('No earthquakes failed database update.');
                }
            } else {
                $this->_logger->info('No new earthquakes to update.');
            }
        }
	}

	private function createLocation($locationElement, $earthquakeEntry)
    {
        $location = new Location($this->_logger, $this->_db, $locationElement);
        $locationEntry = $location->getLocationExists();
        if ($locationEntry == 0) {
            $locationEntry = $location->saveLocation();
            if ($locationEntry == 0) {
                $this->_logger->warn('🤯 Location NOT added: ' . $locationEntry);
            } else {
                $this->linkEarthquakeToLocation($earthquakeEntry, $locationEntry);
                $this->_logger->debug('New Location : ' . $locationEntry . ' linked to Earthquake: ' . $earthquakeEntry);
            }
        } else {
//        if ($locationEntry != 0) {
            $this->linkEarthquakeToLocation($earthquakeEntry, $locationEntry);
            $this->_logger->debug('Existing Location : ' . $locationEntry . ' linked to Earthquake: ' . $earthquakeEntry);
        }
    }

    private function linkEarthquakeToLocation($earthquakeEntry, $locationEntry)
    {
        $eqLocation = new EarthquakeLocation($this->_logger, $this->_db, $earthquakeEntry, $locationEntry);
        if ($eqLocation->getEarthquakeLocationExists() === FALSE) {
            if ($eqLocation->saveEarthquakeLocation() === FALSE) {
                $this->_logger->warn('🤯 Earthquake-Location NOT added: eq: ' . $earthquakeEntry . ' - lc: ' . $locationEntry);
            }
        }
    }

    private function sendPushNotification($earthquake) {
        $magnitude = round($earthquake->properties->mag, 2);
        $latitude = $earthquake->geometry->coordinates[1];
        $longitude = $earthquake->geometry->coordinates[0];
        $place = $earthquake->properties->place;
        $time = date('n/j/y @ G:i:s', substr($earthquake->properties->time, 0, 10));
        $type = $earthquake->properties->type;

        $title = 'New ' . $type . '!';
        $payload = 'M' . $magnitude .  ' ' . $type . ', ' . $place . ' on ' . $time . ' UTC';
        $this->_sendPushes->sendPushes($this->_pushesDebug, $title, $payload, $magnitude, $latitude, $longitude);
    }

	private function sendTwitterNotification($earthquake) {
        global $twitterCreds;

        $magnitude = round($earthquake->properties->mag, 2);
		$place = $earthquake->properties->place;
		$time = date('n/j/y @ G:i:s', substr($earthquake->properties->time, 0, 10));
		$url = $earthquake->properties->url;
		$type = $earthquake->properties->type;
		$lat = $earthquake->geometry->coordinates[1];
		$long = $earthquake->geometry->coordinates[0];
		$hashtag = str_replace(" ", "", $type);

		$status = "USGS reports a M" . $magnitude .  " " . $type . ", " . $place . " on " . $time . " UTC\r";
        $status .= $url . "\r";
        if ($magnitude >= 4 && $magnitude < 7) {
            $now = time();
            $lastAdvertiseFile = '/var/www/html/Earthquakes/lastAdvertise';
            $lastAdvertise = file_get_contents($lastAdvertiseFile);
            $timeDifference = $now - $lastAdvertise;
            if ($timeDifference > 3600) {
                file_put_contents($lastAdvertiseFile,$now);
                $status .= "Try Every Earthquake on your iPhone! Grab the new app here: https://t.co/E1g19fUIFv" . "\r";
            }
        }
        $status .= "#" . $hashtag;
		$completeStatus = $status . ' @ ' . $lat . ' ' . $long;

		$this->_logger->info('Tweeting this: ' . $completeStatus);

        $tweet = array(
            'status' => $status,
            'lat' => $lat,
            'long' => $long,
            'display_coordinates' => true
        );

        $twitter = new Twitter($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
        $response = $twitter->tweet($tweet);
        $responseDecoded = json_decode($response, true);
        $curlErrno = $responseDecoded['curlErrno'];
        $curlInfo = $responseDecoded['curlInfo'];
        $httpCode = $curlInfo['http_code'];

        if ($curlErrno != 0) {
            $this->_logger->error('Twitter post (' . $twitterCreds['who'] . ') failed again for earthquake: ' . $earthquake->id . ' - Curl error: ' . $curlErrno);
        } else if ($httpCode != 200) {
            $this->_logger->error('Twitter post  (' . $twitterCreds['who'] . ') failed again for earthquake: ' . $earthquake->id . ' - Twitter error: ' . $httpCode);
        } else {
            $this->_logger->debug('Tweet  (' . $twitterCreds['who'] . ') sent for: Earthquake: ' . $earthquake->id);
        }
	}
}