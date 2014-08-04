<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$peq = new processEarthquakes();
$peq->getEarthquakes();

class processEarthquakes
{
	private $_logger;
	private $_earthquakeId;
	private $_container;

	public function __construct()
	{
		$this->_container = new Container();

		$this->_logger = $this->_container->getLogger();
	}

	public function getEarthquakes()
	{
//		$this->_logger->error(time());

		global $twitterCreds;
		global $SoCaltwitterCreds;

		$usgs = new USGS($this->_logger);
		$earthquakes = $usgs->getEarthquakes();
		$earthquakeArray = $earthquakes->features;

		$collection = $this->_container->getMongoDBConnect();

		$newEarthquakeCount = 0;

		foreach ($earthquakeArray as $earthquake) {

			if (!empty($earthquake->id)) {
				$this->_earthquakeId = $earthquake->id;
				$criteria = array('id' => $this->_earthquakeId);
				$count = $collection->count($criteria);

				if ($count > 0) {
					$this->_logger->debug('Duplicate earthquake');
				} else {
					try {
						$collection->insert($earthquake);
						$this->_logger->debug('Earthquake added: ' . $this->_earthquakeId);
						$this->sendTweet($earthquake, $twitterCreds);
						$lat = $earthquake->geometry->coordinates[1];
						$long = $earthquake->geometry->coordinates[0];
						if (($lat >= 32 && $lat <= 35) && ($long >= -121 && $long <= -115.5)) {
							$this->sendTweet($earthquake, $SoCaltwitterCreds);
						}
						$newEarthquakeCount++;
					} catch (MongoException $e) {
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
		}
	}

	private function sendTweet($earthquake, $creds)
	{
		$magnitude = $earthquake->properties->mag;
		$place = $earthquake->properties->place;
		$time = date('n/j/y @ G:i:s', substr($earthquake->properties->time, 0, 10));
		$url = $earthquake->properties->url;
		$lat = $earthquake->geometry->coordinates[1];
		$long = $earthquake->geometry->coordinates[0];

		$status = 'USGS reports a M' . $magnitude .  ' #earthquake ' . $place . ' on ' . $time . ' UTC ' . $url . ' #quake';

		$this->_logger->debug('Tweeting this: ' . $status . ' @ ' . $lat . ' ' . $long);

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
			$this->_logger->error('Twitter post failed again for earthquake: ' . $earthquake->id . ' - Curl error: ' . $curlErrno);
		} else if ($httpCode != 200) {
			$this->_logger->error('Twitter post failed again for earthquake: ' . $earthquake->id . ' - Twitter error: ' . $httpCode);
		} else {
			$this->_logger->info('Tweet sent for: Earthquake: ' . $earthquake->id);
		}
	}
}