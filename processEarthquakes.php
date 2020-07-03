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
        $updatedEarthquakeCount = 0;
        $failedUpdateEarthquakeCount = 0;

		foreach ($earthquakeArray as $earthquakeElement) {
			if (!empty($earthquakeElement->id)) {
				$earthquake = new Earthquake($this->_logger, $this->_db, $earthquakeElement);
				$this->_earthquakeId = $earthquake->getId();

				if ($earthquake->getEarthquakeExists($this->_table) === TRUE) {
                    $updatedDB = $earthquake->getDBUpdateDate($this->_table);
                    $updatedAPI = $earthquake->getUpdated();
                    if ($updatedDB < $updatedAPI) {
                        $latitudeDB = round($earthquake->getDBLatitude($this->_table), 1);
                        $longitudeDB = round($earthquake->getDBLongitude($this->_table), 1);
                        $latitudeAPI = round($earthquake->getLatitude(), 1);
                        $longitudeAPI = round($earthquake->getLongitude(), 1);
                        if ($latitudeDB != $latitudeAPI || $longitudeDB != $longitudeAPI) {
                            $earthquake->setOpenCageGeocode($openCageKey);
                            $this->_logger->info('*** LOCATION UPDATED ***');
                            $this->_logger->info('DB Lat: ' . $latitudeDB . ', API Lat: ' . $latitudeAPI . ', DB Lon: ' . $longitudeDB . ', API Lon: ' . $longitudeAPI);
                        }
                        $earthquake->setDate();
                        $earthquake->setLocation();
                        if ($earthquake->updateEarthquake($this->_table)) {
                            $this->_logger->info('Earthquake updated: ' . $this->_earthquakeId);
                            $updatedEarthquakeCount++;
                        } else {
                            $this->_logger->info('🤯 Earthquake NOT updated: ' . $this->_earthquakeId);
                            $failedUpdateEarthquakeCount++;
                        }
                    }
				} else {
					try {
                        $earthquake->setDate();
                        $earthquake->setLocation();
                        $earthquake->setOpenCageGeocode($openCageKey);
                        if ($store === "TRUE") {
							if ($earthquake->saveEarthquake($this->_table)) {
                                $this->_logger->info('Earthquake added: ' . $this->_earthquakeId);
                                $newEarthquakeCount++;
                                $earthquake->saveEarthquake('earthquakesOg');
                            } else {
                                $this->_logger->info('🤯 Earthquake NOT added: ' . $this->_earthquakeId);
                                $failedEarthquakeCount++;
                            }
                        }
						if ($notify === "TRUE") {
							$this->sendNotifications($earthquakeElement, $twitterCreds, TRUE);
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

	private function sendNotifications($earthquake, $creds, $send)
	{
		$magnitude = $earthquake->properties->mag;
		$place = $earthquake->properties->place;
		$time = date('n/j/y @ G:i:s', substr($earthquake->properties->time, 0, 10));
		$url = $earthquake->properties->url;
		$type = $earthquake->properties->type;
		$lat = $earthquake->geometry->coordinates[1];
		$long = $earthquake->geometry->coordinates[0];
		$hashtag = str_replace(" ", "", $type);

		$status = 'USGS reports a M' . $magnitude .  ' ' . $type . ', ' . $place . ' on ' . $time . ' UTC ' . $url . ' #' . $hashtag;
		$completeStatus = $status . ' @ ' . $lat . ' ' . $long;

		$this->_logger->info('Tweeting this: ' . $completeStatus);

		if ($send === TRUE) {
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
}