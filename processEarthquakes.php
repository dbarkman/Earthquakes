<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';
require_once dirname(__FILE__) . '/Sag/Sag.php';

$peq = new processEarthquakes();
$peq->getEarthquakes();

class processEarthquakes
{
	private $_logger;
	private $_sag;
	private $_docID;

	public function __construct()
	{
		$container = new Container();

		$this->_logger = $container->getLogger();
	}

	public function getEarthquakes()
	{
		$usgs = new USGS($this->_logger);
		$earthquakes = $usgs->getEarthquakes();
		$earthquakeArray = $earthquakes->features;

		global $earthquakesDBLogin;
		$this->_sag = new Sag();
		$this->_sag->login($earthquakesDBLogin['username'], $earthquakesDBLogin['password']);
		$this->_sag->setDatabase('earthquakes');

		$newEarthquakeCount = 0;

		foreach ($earthquakeArray as $earthquake) {
			$this->_docID = $earthquake->id;
			if (!empty($this->_docID)) {
				try {
					$response = $this->_sag->put($this->_docID, $earthquake);
					if (isset($response->headers->_HTTP->status) && $response->headers->_HTTP->status == '201') {
						$this->_logger->info('Earthquake added: ' . $earthquake->id);
						$this->sendTweet($earthquake);
						$newEarthquakeCount++;
					} else {
						$this->logAndAddError(0, 'There was a problem adding the following earthquake to the database: ' . $this->_docID . ' (exception not thrown).');
					}
				} catch (SagCouchException $sce) {
					if ($sce->getCode() != 409) {
						try {
							$this->logAndAddError($sce->getCode(), 'Problem with earthquake data, could not insert into database: ' . $sce->getMessage() . ' - sagCouchException');
						} catch (Exception $e) {
							$this->logAndAddError($e->getCode(), 'Extensive problems with earthquake data, could not insert into database: ' . $e->getMessage() . ' - sagCouchException');
						}
					} else {
						$this->_logger->debug('Duplicate earthquake - sagCouchException.');
					}
				} catch (SagException $se) {
					if ($se->getCode() != 409) {
						try {
							$this->logAndAddError($se->getCode(), 'Problem with earthquake data, could not insert into database: ' . $se->getMessage() . ' - sagException');
						} catch (Exception $e) {
							$this->logAndAddError($e->getCode(), 'Extensive problems with earthquake data, could not insert into database: ' . $e->getMessage() . ' - sagException');
						}
					} else {
						$this->_logger->debug('Duplicate earthquake - sagException.');
					}
				}
			} else {
				$this->_docID = 0;
				try {
					$this->logAndAddError(0, 'Could not extract an id for an earthquake (exception not thrown).');
				} catch (Exception $e) {
					$this->_logger->error('Could not extract an id for this earthquake and could not make any entry into the database(' . $e->getCode() . '): ' . $e->getMessage());
				}
			}
		}
		if ($newEarthquakeCount > 0) {
			$earthquakeLabel = ($newEarthquakeCount > 1) ? 'earthquakes' : 'earthquake';
			$this->_logger->info($newEarthquakeCount . ' new ' . $earthquakeLabel . ' added and reported.');
		}
	}

	private function logAndAddError($exceptionCode, $exceptionMessage)
	{
		$this->_logger->error($exceptionMessage);
		$this->_sag->post($this->getStandardObject($exceptionCode, $exceptionMessage));
	}

	private function getStandardObject($exceptionCode, $exceptionMessage)
	{
		$exceptionArray = array(
			'exceptionCode' => $exceptionCode,
			'exceptionMessage' => $exceptionMessage
		);
		$doc = new stdClass();
		$doc->id = $this->_docID;
		$doc->created = time();
		$doc->data = $exceptionArray;

		return $doc;
	}

	private function sendTweet($earthquake)
	{
		global $twitterCreds;

		$magnitude = $earthquake->properties->mag;
		$place = $earthquake->properties->place;
		$time = date('n/j/y @ G:i:s', substr($earthquake->properties->time, 0, 10));
		$url = $earthquake->properties->url;
		$lat = $earthquake->geometry->coordinates[1];
		$long = $earthquake->geometry->coordinates[0];

		$status = 'USGS reports a M' . $magnitude .  ' earthquake ' . $place . ' on ' . $time . ' UTC ' . $url . ' #quake';

		$this->_logger->info('Tweeting this: ' . $status . ' @ ' . $lat . ' ' . $long);

		$tweet = array(
			'status' => $status,
			'lat' => $lat,
			'long' => $long,
			'display_coordinates' => true
		);

		$twitter = new Twitter($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
		if ($twitter->tweet($tweet) === false) {
			$this->_logger->error('Twitter post failed for earthquake: ' . $earthquake->id);
		} else {
			$this->_logger->info('Twitter post succeeded for earthquake: ' . $earthquake->id);
		}
	}
}