<?php

/**
 * USGS.controller.php
 * Description:
 *
 */

class Firebase extends Curl
{

	private $_logger;

	public function __construct($logger) {
		parent::__construct($logger);

		$this->_logger = $logger;
	}

	public function saveEarthquake($earthquake)
	{
		$earthquakeId = $earthquake->id;
		$rawEarthquake = json_encode($earthquake);
		$url = 'https://earthquakes-2a5bd.firebaseio.com/earthquakes/' . $earthquakeId . '.json';
		$this->_logger->debug('Firebase URL: ' . $url);

		return json_decode(self::runCurl('PUTJson', $url, null, null, $rawEarthquake));
	}
}
