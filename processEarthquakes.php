<?php

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';
require_once dirname(__FILE__) . '/Sag/Sag.php';
require_once dirname(__FILE__) . '/Twitter/oauthdamnit.php';

$peq = new processEarthquakes();
$peq->getEarthquakes();

class processEarthquakes
{
	private $_logger;

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
		$sag = new Sag();
		$sag->login($earthquakesDBLogin['username'], $earthquakesDBLogin['password']);
		$sag->setDatabase('earthquakes');

		foreach ($earthquakeArray as $earthquake) {
			$docID = $earthquake->id;
			if (!empty($docID)) {
				try {
					$response = $sag->put($docID, $earthquake);
					if (isset($response->headers->_HTTP->status) && $response->headers->_HTTP->status == '201') {
						$this->sendTweet($earthquake);
						$this->_logger->info('Earthquake added.');
					}
				} catch (SagCouchException $sce) {
					if ($sce->getCode() != 409) {
						try {
							$this->_logger->error('Problem with earthquake data, could not insert into database: ' . $sce->getMessage());
							$sag->put($docID, $this->getStandardObject($docID, $sce->getCode(), $sce->getMessage()));
						} catch (Exception $e) {
							$this->_logger->error('Extensive problems with earthquake data, could not insert into database: ' . $e->getMessage() . ' - sagCouchException');
							$sag->post($this->getStandardObject($docID, $e->getCode(), $e->getMessage(), 'post'));
						}
					} else {
						$this->_logger->debug('duplicate earthquake - sagCouchException');
					}
				} catch (SagException $se) {
					if ($se->getCode() != 409) {
						try {
							$this->_logger->error('Problem with earthquake data, could not insert into database: ' . $se->getMessage());
							$sag->put($docID, $this->getStandardObject($docID, $se->getCode(), $se->getMessage()));
						} catch (Exception $e) {
							$this->_logger->error('Extensive problems with earthquake data, could not insert into database: ' . $e->getMessage() . ' - sagException');
							$sag->post($this->getStandardObject($docID, $e->getCode(), $e->getMessage(), 'post'));
						}
					} else {
						$this->_logger->debug('duplicate earthquake - sagException');
					}
				}
			} else {
				try {
					$this->_logger->error('Could not extract an id for this earthquake.');
					$sag->post($this->getStandardObject($docID, 0, 'Could not extract an id for this earthquake.', 'post'));
				} catch (Exception $e) {
					$this->_logger->error('Could not extract an id for this earthquake and could not make any entry into the database.');
				}
			}
		}
	}

	private function getStandardObject($docID, $exceptionCode, $exceptionMessage, $verb = 'put')
	{
		$exceptionArray = array(
			'exceptionCode' => $exceptionCode,
			'exceptionMessage' => $exceptionMessage
		);
		$doc = new stdClass();
		if ($verb == 'put') {
			$doc->_id = $docID;
		} else if ($verb == 'post') {
			$doc->id = $docID;
		}
		$doc->created = time();
		$doc->data = $exceptionArray;

		return $doc;
	}

	private function sendTweet($earthquake)
	{
		$this->_logger->debug('Sending tweet about earthquake.');

		global $twitterCreds;

		$magnitude = $earthquake->properties->mag;
		$place = $earthquake->properties->place;
		$time = date('n/j/y @ G:i:s', substr($earthquake->properties->time, 0, 10));
		$url = $earthquake->properties->url;
		$status = 'USGS reports a M ' . $magnitude .  ' earthquake ' . $place . ' on ' . $time . ' UTC ' . $url . ' #quake';

		$tweet = array(
			'status' => $status,
			'lat' => $earthquake->geometry->coordinates[1],
			'long' => $earthquake->geometry->coordinates[0],
			'display_coordinates' => true
		);
		$twitter = new OAuthDamnit($twitterCreds['consumerKey'], $twitterCreds['consumerSecret'], $twitterCreds['accessToken'], $twitterCreds['accessTokenSecret']);
		$raw = $twitter->post('https://api.twitter.com/1.1/statuses/update.json', $tweet);
		$response = @json_decode($raw, true);
		var_dump($response);
	}
}