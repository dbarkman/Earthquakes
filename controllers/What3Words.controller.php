<?php

/**
 * What3Words.controller.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 4/9/22 @ 14:55
 */

class What3Words extends Curl
{
    private $_logger;

    public function __construct($logger) {
        parent::__construct($logger);

        $this->_logger = $logger;
    }

    public function getWhat3Words($coordinates) {
        $url = 'https://api.what3words.com/v3/convert-to-3wa?key=3Y7FHYJ8&coordinates=' . $coordinates . '&language=en&format=json';
        $response = self::runCurl('GET', $url, null, null, null, true);
        $this->_logger->info("W3W API returned: " . $response['status']);
        if ($response['status'] == 429) {
            $this->_logger->error("W3W API returned 429, BACK OFF!");
            return FALSE;
        } else {
            $output = json_decode($response['output']);
            return $output->words;
        }
    }

}
