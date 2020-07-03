<?php

/**
 * Created by PhpStorm.
 * User: David Barkman
 * Date: 6/16/20
 * Time: 10:35 PM
 */
class Earthquakes
{
    private $_logger;
    private $_db;

    public function __construct($logger, $db)
    {
        $this->_logger = $logger;
        $this->_db = $db;
    }

    //date: filter in code
    //location: circle search SQL
    //magnitude: filter in code
    //intensity: filter in code
    //count: sort and limit in code
    //set time based, ex: all Eqs in 2019
    //recent time based, ex: all Eqs in the last week
    //last time period for all locations
    public static function GetEarthquakes($db, $logger, $parameters) {

        $interval = (array_key_exists('interval', $parameters)) ? $parameters['interval'] : '';
        $startDate = (array_key_exists('startDate', $parameters)) ? $parameters['startDate'] : '';
        $endDate = (array_key_exists('endDate', $parameters)) ? $parameters['endDate'] : '';
        $latitude = (array_key_exists('latitude', $parameters)) ? $parameters['latitude'] : '';
        $longitude = (array_key_exists('longitude', $parameters)) ? $parameters['longitude'] : '';
        $radius = (array_key_exists('radius', $parameters)) ? $parameters['radius'] : '';
        $units = (array_key_exists('units', $parameters)) ? $parameters['units'] : '';
        $count = (array_key_exists('count', $parameters)) ? $parameters['count'] : 100;
        $magnitude = (array_key_exists('magnitude', $parameters)) ? $parameters['magnitude'] : 0;
        $intensity = (array_key_exists('intensity', $parameters)) ? $parameters['intensity'] : 0;
        $type = (array_key_exists('type', $parameters)) ? $parameters['type'] : 'earthquake';

        if (!empty($interval)) {
            $time = time();
            $dateTime = new DateTime();
            $dateTime->setTimestamp($time);
            $dateTime->sub(new DateInterval($interval));
            $startDate = $dateTime->format('U') * 1000;
            $endDate = $time * 1000;
        } else if (!empty($startDate)) {
            $startDate = self::getTimeFromDate($startDate);
            $endDate = self::getTimeFromDate($endDate);
        }

        if (!empty($latitude)) {
            if ($count == 'all') {
                $startDate = -11676096000000;
                $endDate = time() * 1000;
                $count = 0;
            }
            return self::circleSearch($db, $logger, $startDate, $endDate, $latitude, $longitude, $radius, $units, $count, $magnitude, $intensity, $type);
        } else {
            $queryCondition = '';
            if (!empty($startDate) && !empty($endDate)) {
                $queryCondition = "
                    WHERE time BETWEEN $startDate AND $endDate
                    AND magnitude >= $magnitude
                    AND mmi >= $intensity
                    AND type = '$type'
                    ORDER BY time DESC
                ";
            } else {
                $queryCondition = "
                    WHERE magnitude >= $magnitude
                    AND mmi >= $intensity
                    AND type = '$type'
                    ORDER BY time DESC
                    LIMIT $count
                ";
            }

            $earthquakes = array();
            $sql = "
                SELECT * FROM
                    earthquakes
                $queryCondition
            ";
            $logger->info('SQL: ' . preg_replace('!\s+!', ' ', $sql));

            $result = mysqli_query($db, $sql);
            if ($result === FALSE) {
                return $earthquakes;
            } else {
                while ($row = mysqli_fetch_assoc($result)) {
                    $date = self::getDateFromTime($row['time']);
                    $earthquakes[] = array(
                        'id' => $row['id'],
                        'magnitude' => $row['magnitude'],
                        'place' => $row['place'],
                        'time' => $row['time'],
                        'date' => $date,
                        'updated' => $row['updated'],
                        'timezone' => $row['timezone'],
                        'url' => $row['url'],
                        'detailUrl' => $row['detailUrl'],
                        'felt' => $row['felt'],
                        'cdi' => $row['cdi'],
                        'mmi' => $row['mmi'],
                        'alert' => $row['alert'],
                        'status' => $row['status'],
                        'tsunami' => $row['tsunami'],
                        'sig' => $row['sig'],
                        'net' => $row['net'],
                        'code' => $row['code'],
                        'ids' => $row['ids'],
                        'sources' => $row['sources'],
                        'types' => $row['types'],
                        'nst' => $row['nst'],
                        'dmin' => $row['dmin'],
                        'rms' => $row['rms'],
                        'gap' => $row['gap'],
                        'magType' => $row['magType'],
                        'type' => $row['type'],
                        'title' => $row['title'],
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'depth' => $row['depth']
                    );
                }
                return $earthquakes;
            }
        }
    }

    //recent, near me
    private static function circleSearch($db, $logger, $startDate, $endDate, $latitude, $longitude, $radius, $units, $count, $magnitude, $intensity, $type) {
        /**
         * Geodesy-related code is © 2008-2020 Chris Veness
         * Under an MIT licence, without any warranty express or implied
         * PHP adapted from JavaScript at:
         * https://www.movable-type.co.uk/scripts/latlong-db.html
         */

        if ($units == 'miles') {
            $radius = self::convertMilesToMeters($radius);
        } else if ($units == 'kilometers') {
            $radius = self::convertKilometersToMeters($radius);
        }

        $queryCondition = '';
        if (!empty($startDate) && !empty($endDate)) {
            $count = 0;
            $queryCondition = "
                AND time BETWEEN $startDate AND $endDate
                AND magnitude >= $magnitude
                AND mmi >= $intensity
                AND type = '$type'
                ORDER BY time DESC
            ";
        } else {
            $queryCondition = "
                AND magnitude >= $magnitude
                AND mmi >= $intensity
                AND type = '$type'
                ORDER BY time DESC
            ";
        }

        $R = 6371e3; //Earth's mean radius in metres

        $minLatitude = $latitude - $radius / $R * 180 / pi();
        $maxLatitude = $latitude + $radius / $R * 180 / pi();
        $minLongitude = $longitude - $radius / $R * 180 / pi() / cos($latitude * pi() / 180);
        $maxLongitude = $longitude + $radius / $R * 180 / pi() / cos($latitude * pi() / 180);

        $sql = "
            SELECT
                *
            FROM
                earthquakes
            WHERE
                latitude BETWEEN $minLatitude AND $maxLatitude
                AND
                longitude BETWEEN $minLongitude AND $maxLongitude
                $queryCondition
        ";
        $logger->info('Circle SQL: ' . preg_replace('!\s+!', ' ', $sql));

        $earthquakes = array();
        $earthquakeArray = array();
        $result = mysqli_query($db, $sql);
        if ($result === FALSE) {
            return $earthquakes;
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $rowLatitude = $row['latitude'];
                $rowLongitude = $row['longitude'];
                $distance = acos(sin($rowLatitude * pi() / 180) * sin($latitude * pi() / 180) + cos($rowLatitude * pi() / 180) * cos($latitude * pi() / 180) * cos($rowLongitude * pi() / 180 - $longitude * pi() / 180)) * $R;
                $date = self::getDateFromTime($row['time']);
                $earthquakeArray[] = array(
                    'id' => $row['id'],
                    'magnitude' => $row['magnitude'],
                    'place' => $row['place'],
                    'time' => $row['time'],
                    'date' => $date,
                    'updated' => $row['updated'],
                    'timezone' => $row['timezone'],
                    'url' => $row['url'],
                    'detailUrl' => $row['detailUrl'],
                    'felt' => $row['felt'],
                    'cdi' => $row['cdi'],
                    'mmi' => $row['mmi'],
                    'alert' => $row['alert'],
                    'status' => $row['status'],
                    'tsunami' => $row['tsunami'],
                    'sig' => $row['sig'],
                    'net' => $row['net'],
                    'code' => $row['code'],
                    'ids' => $row['ids'],
                    'sources' => $row['sources'],
                    'types' => $row['types'],
                    'nst' => $row['nst'],
                    'dmin' => $row['dmin'],
                    'rms' => $row['rms'],
                    'gap' => $row['gap'],
                    'magType' => $row['magType'],
                    'type' => $row['type'],
                    'title' => $row['title'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude'],
                    'depth' => $row['depth'],
                    'distance' => $distance);
            }
            foreach ($earthquakeArray as $earthquake) {
                if ($earthquake['distance'] < $radius) {
                    $earthquakes[] = $earthquake;
                }
            }
//        $dateColumn = array_column($earthquakes, 'date');
//        array_multisort($dateColumn, SORT_DESC, $earthquakes);

            if ($count > 0) {
                $earthquakes = array_slice($earthquakes, 0, $count);
            }

            return $earthquakes;
        }
    }

    private static function convertMilesToMeters($miles) {
        return $miles * 1609.34;
    }

    private static function convertKilometersToMeters($kilometers) {
        return $kilometers * 1000;
    }

    public static function getTimeFromDate($date) {
        $dateObject = new DateTime($date);
        $timeStamp = $dateObject->format('U');
        $miliseconds = $dateObject->format('v');
        if (strlen($miliseconds) > 3) substr($miliseconds, 0, 3);
        return $timeStamp . $miliseconds;
    }

    public static function getDateFromTime($timestamp, $milliseconds = true) {
        $time = ($milliseconds) ? substr($timestamp, 0, -3) : $timestamp;
        $dateTime = new DateTime();
        $dateTime->setTimestamp($time);
        return $dateTime->format('Y-m-d\TH:i:s');
    }
}