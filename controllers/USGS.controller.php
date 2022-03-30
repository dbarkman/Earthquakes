<?php

/**
 * USGS.controller.php
 * Description:
 *
 */

class USGS extends Curl
{

	private $_logger;
    private $_status;
    private $_response;

	public function __construct($logger) {
		parent::__construct($logger);

		$this->_logger = $logger;
	}

	public function getEarthquakes($url)
	{
		$this->_logger->info('USGS URL: ' . $url);
        $response = self::runCurl('GET', $url, null, null, null, true);
        $this->_logger->info("USGS API returned: " . $response['status']);
        if ($response['status'] == 429) {
            $this->_logger->error("USGS API returned 429, BACK OFF!");
            return array();
        } else {
            return json_decode($response['output']);
        }
    }

}
