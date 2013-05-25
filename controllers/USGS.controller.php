<?php

/**
 * USGS.controller.php
 * Description:
 *
 */

class USGS extends Curl
{

	private $_logger;

	public function __construct($logger) {
		parent::__construct($logger);

		$this->_logger = $logger;
	}

	public function getEarthquakes()
	{
		$baseUrl = 'http://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/all_hour.geojson';
//		$baseUrl = 'http://earthquake.usgs.gov/earthquakes/feed/v1.0/summary/all_day.geojson';
		$url = $baseUrl;
		$this->_logger->debug('USGS URL: ' . $url);

		return json_decode(self::runCurl('GET', $url));
	}

	protected function runCurl($requestMethod, $url)
	{
		return parent::runCurl($requestMethod, $url);
	}
}
