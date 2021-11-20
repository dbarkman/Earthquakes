<?php

/**
 * API.controller.php
 * Description:
 *
 */

class API extends Curl
{

	private $_logger;

	public function __construct($logger) {
		parent::__construct($logger);

		$this->_logger = $logger;
	}

	public function getAPI($url)
	{
		$this->_logger->debug('API URL: ' . $url);

        return json_decode(self::runCurl('GET', $url, null, null, null));
	}
}
