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

	public function getEarthquakes($url)
	{
		$this->_logger->info('USGS URL: ' . $url);

        return json_decode(self::runCurl('GET', $url, null, null, null));
	}
}
