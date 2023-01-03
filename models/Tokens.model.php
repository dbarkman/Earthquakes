<?php

/**
 * Tokens.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 12/21/22 @ 10:44
 */

class Tokens {

    public static function getTokensToReceiveNotification($logger, $db, $debug, $magnitude) {
        $sql = "
            SELECT DISTINCT
                token,
                location,
                latitude,
                longitude,
                radius,
                units
            FROM
                notificationTokens
            WHERE
                sendPush = '1'
                AND
                debug = '$debug'
                AND
                magnitude <= '$magnitude'
        ";
        $logger->debug('SQL: ' . preg_replace('!\s+!', ' ', $sql));

        $tokens = array();
        $result = mysqli_query($db, $sql);
        if ($result === FALSE) {
            return $tokens;
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $tokens[] = array(
                    'token' => $row['token'],
                    'location' => $row['location'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'radius' => $row['radius'],
                    'units' => $row['units']
                );
            }
            return $tokens;
        }
    }
}
