<?php

/**
 * sendPushes.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 12/21/22 @ 11:08
 */

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if (count($argv) < 7) {
    echo 'Include argument for debug, title, payload, magnitude, latitude and longitude.' . PHP_EOL;
} else {
    $pushes = new SendPushes();
    $pushes->sendPushes($argv[1], $argv[2], $argv[3], $argv[4], $argv[5], $argv[6]);
}
