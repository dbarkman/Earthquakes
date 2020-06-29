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
	private $_earthquakeId;

	public function __construct()
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
		$this->_db = $this->_container->getMySQLDBConnect();
        $this->_table = 'earthquakes';

		$properties = $this->_container->getProperties();
		$store = $properties->getStoreValue();
		$notify = $properties->getNotifyValue();
		$urlHour = $properties->getUrlHour();
		$urlDay = $properties->getUrlDay();
		$googleMapsKey = $properties->getKeyGoogleMaps();
		$openCageKey = $properties->getKeyOpenCage();

		$this->getEarthquakes($store, $notify, $urlDay, $openCageKey);
	}

	public function getEarthquakes($store, $notify, $url, $openCageKey)
	{
		global $twitterCreds;

		$this->_logger->info('Checking for earthquakes!');

		$usgs = new USGS($this->_logger);
        $earthquakes = $usgs->getEarthquakes($url);
		$earthquakeArray = $earthquakes->features;

		$newEarthquakeCount = 0;
        $failedEarthquakeCount = 0;

		foreach ($earthquakeArray as $earthquakeElement) {
			if (!empty($earthquakeElement->id)) {
				$earthquake = new Earthquake($this->_logger, $this->_db, $earthquakeElement);
				$this->_earthquakeId = $earthquake->getId();

				if ($earthquake->getEarthquakeExists($this->_table) === TRUE) {
					$this->_logger->debug('Duplicate earthquake: ' . $this->_earthquakeId);
				} else {
					try {
                        //run setup location here
                        //run get location here
                        //run setup date here
                        $earthquake->setDate();
                        $earthquake->setLocation();
                        $earthquake->setOpenCageGeocode($openCageKey);
                        if ($store === "TRUE") {
							if ($earthquake->saveEarthquake($this->_table)) {
                                $this->_logger->info('Earthquake added: ' . $this->_earthquakeId);
                                $newEarthquakeCount++;
                            } else {
                                $this->_logger->info('🤯 Earthquake NOT added: ' . $this->_earthquakeId);
                                $failedEarthquakeCount++;
                            }
						} else {
//                            $earthquake->dumpEarthquake();
                        }
						if ($notify === "TRUE") {
							$this->sendNotifications($earthquakeElement, $twitterCreds);
							$lat = $earthquake->getLatitude();
							$long = $earthquake->getLongitude();
							$this->_logger->debug('Lat: ' . $lat . ' - Long: ' . $long);
						}
					} catch (mysqli_sql_exception $e) {
						$this->_logger->error('Problem with earthquake data: ' . $this->_earthquakeId . ', could not insert into database: ' . $e->getMessage());
					} catch (Exception $e) {
						$this->_logger->error('Extensive problems with earthquake data: ' . $this->_earthquakeId . ', could not insert into database: ' . $e->getMessage());
					}
				}
			} else {
				$this->_logger->error('Could not extract an id for an earthquake (exception not thrown).');
			}
		}

		if ($newEarthquakeCount > 0) {
			$earthquakeLabel = ($newEarthquakeCount == 1) ? 'earthquake' : 'earthquakes';
			$this->_logger->info($newEarthquakeCount . ' new ' . $earthquakeLabel . ' added and reported.');
            if ($failedEarthquakeCount > 0) {
                $earthquakeLabel = ($failedEarthquakeCount == 1) ? 'earthquake' : 'earthquakes';
                $this->_logger->info($failedEarthquakeCount . ' ' . $earthquakeLabel . ' failed.');
            } else {
                $this->_logger->info('No earthquakes failed database insert.');
            }
		} else {
		    $this->_logger->info('No new earthquakes.');
        }
	}

	private function sendNotifications($earthquake, $creds, $location = null)
	{
		$magnitude = $earthquake->properties->mag;
		$place = $earthquake->properties->place;
		$time = date('n/j/y @ G:i:s', substr($earthquake->properties->time, 0, 10));
		$url = $earthquake->properties->url;
		$type = $earthquake->properties->type;
		$lat = $earthquake->geometry->coordinates[1];
		$long = $earthquake->geometry->coordinates[0];
		$hashtag = str_replace(" ", "", $type);

		$location = ($location == null) ? "" : $location . " ";

		$status = 'USGS reports a ' . $location . 'M' . $magnitude .  ' ' . $type . ', ' . $place . ' on ' . $time . ' UTC ' . $url . ' #' . $hashtag;
		$completeStatus = $status . ' @ ' . $lat . ' ' . $long;

		$this->_logger->info('Tweeting this: ' . $completeStatus);

		$tweet = array(
			'status' => $status,
			'lat' => $lat,
			'long' => $long,
			'display_coordinates' => true
		);

		$twitter = new Twitter($creds['consumerKey'], $creds['consumerSecret'], $creds['accessToken'], $creds['accessTokenSecret']);
		$response = $twitter->tweet($tweet);
		$responseDecoded = json_decode($response, true);
		$curlErrno = $responseDecoded['curlErrno'];
		$curlInfo = $responseDecoded['curlInfo'];
		$httpCode = $curlInfo['http_code'];

		if ($curlErrno != 0) {
			$this->_logger->error('Twitter post (' . $creds['who'] . ') failed again for earthquake: ' . $earthquake->id . ' - Curl error: ' . $curlErrno);
		} else if ($httpCode != 200) {
			$this->_logger->error('Twitter post  (' . $creds['who'] . ') failed again for earthquake: ' . $earthquake->id . ' - Twitter error: ' . $httpCode);
		} else {
			$this->_logger->debug('Tweet  (' . $creds['who'] . ') sent for: Earthquake: ' . $earthquake->id);
		}
	}
}