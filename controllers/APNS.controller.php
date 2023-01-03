<?php

/**
 * APNS.controller.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 12/21/22 @ 09:28
 */

class APNS extends Curl
{

    private $_logger;

    public function __construct($logger) {
        parent::__construct($logger);

        $this->_logger = $logger;
    }

    public function sendNotifications($url, $headers, $payload)
    {
        $this->_logger->debug('APNS URL: ' . $url);
        $response = self::runCurl('POST', $url, $headers, null, $payload, true);
        if ($response['status'] == 200) {
            $this->_logger->debug("APNS API returned: " . $response['status']);
        } else {
            $this->_logger->warn("APNS API returned: " . $response['status']);
        }
        if ($response['status'] == 429) {
            $this->_logger->error("APNS API returned 429, BACK OFF!");
            $response['output'] = false;
            return $response;
        } else {
            return $response;
        }
    }

}
