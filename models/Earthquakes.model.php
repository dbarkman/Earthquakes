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

        $location = (array_key_exists('location', $parameters)) ? $parameters['location'] : false;
        $latest = (array_key_exists('latest', $parameters)) ? $parameters['latest'] : false;
        $interval = (array_key_exists('interval', $parameters)) ? $parameters['interval'] : '';
        $startDate = (array_key_exists('startDate', $parameters)) ? $parameters['startDate'] : '';
        $endDate = (array_key_exists('endDate', $parameters)) ? $parameters['endDate'] : '';
        $latitude = (array_key_exists('latitude', $parameters)) ? $parameters['latitude'] : '';
        $longitude = (array_key_exists('longitude', $parameters)) ? $parameters['longitude'] : '';
        $radius = (array_key_exists('radius', $parameters)) ? $parameters['radius'] : '';
        $units = (array_key_exists('units', $parameters)) ? $parameters['units'] : '';
        $magnitude = (array_key_exists('magnitude', $parameters)) ? $parameters['magnitude'] : 0;
        $intensity = (array_key_exists('intensity', $parameters)) ? $parameters['intensity'] : 0;
        $significance = (array_key_exists('significance', $parameters)) ? $parameters['significance'] : 0;
        $count = (array_key_exists('count', $parameters)) ? $parameters['count'] : 100;
        $start = (array_key_exists('start', $parameters)) ? $parameters['start'] : 0;
        $order = (array_key_exists('order', $parameters)) ? $parameters['order'] : 'DESC';
        $type = (array_key_exists('type', $parameters)) ? $parameters['type'] : 'earthquake';
        if ($type == 'notEarthquake') {
            $type = "AND type != 'earthquake'";
        } else if ($type == 'all') {
            $type = "";
        } else {
            $type = "AND type = '$type'";
        }

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

        if ($latest) {
            $radius = 100;
            $units = 'miles';
            $count = 1;
            $location = true;
        }

        $limit = 'LIMIT ' . $count;
        if ($start > 0) {
            $limit = 'LIMIT ' . $start . ',' . $count;
        } else if ($count == -1) {
            $limit = '';
        }
//        $limit = ($start > 0) ? 'LIMIT ' . $start . ',' . $count : 'LIMIT ' . $count;

        if ($location) {
            return self::circleSearch($db, $logger, $startDate, $endDate, $latitude, $longitude, $radius, $units, $limit, $magnitude, $intensity, $significance, $type, $order, $latest);
//        } else if ($latest) {
//            return self::circleSearch($db, $logger, $startDate, $endDate, $latitude, $longitude, 100, 'miles', 1, $magnitude, $intensity, $type, $order, $latest);
        } else {
            $queryCondition = '';
            if (!empty($startDate) && !empty($endDate)) {
                $queryCondition = "
                    WHERE time BETWEEN $startDate AND $endDate
                    AND magnitude >= $magnitude
                    AND mmi >= $intensity
                    AND sig >= $significance
                    $type
                ";
            } else {
                $queryCondition = "
                    WHERE magnitude >= $magnitude
                    AND mmi >= $intensity
                    AND sig >= $significance
                    $type
                ";
            }

            $earthquakes = array();
            $sql = "
                SELECT * FROM
                    earthquakes
                $queryCondition
                ORDER BY 
                    time $order
                $limit
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
                        'magnitude' => strval(round($row['magnitude'], 2)),
                        'type' => $row['type'],
                        'title' => $row['title'],
                        'date' => $date,
                        'time' => $row['time'],
                        'updated' => $row['updated'],
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
                        'geometryType' => $row['geometryType'],
                        'depth' => $row['depth'],
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'place' => $row['place'],
                        'distanceKM' => $row['distanceKM'],
                        'placeOnly' => $row['placeOnly'],
                        'location' => $row['location'],
                        'continent' => $row['continent'],
                        'country' => $row['country'],
                        'subnational' => $row['subnational'],
                        'city' => $row['city'],
                        'locality' => $row['locality'],
                        'postcode' => $row['postcode'],
                        'what3words' => $row['what3words'],
                        'timezone' => $row['timezone'],
                        'locationDetails' => self::fetchLocations($logger, $db, $row['entry'])
                    );
                }
                return $earthquakes;
            }
        }
    }

    public static function fetchLocations($logger, $db, $entry) {
        $sql = "
            SELECT
                l.*
            FROM locations l
                JOIN
                earthquakesLocations el on el.locationEntry = l.entry 
                JOIN
                earthquakes e on e.entry = el.earthquakeEntry
            WHERE
                e.entry = '$entry'
            ORDER BY
                adminLevel ASC
        ";
        $locations = array();
        $result = mysqli_query($db, $sql);
        if ($result === FALSE) {
            return $locations;
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $locations[] = array(
                    'id' => $row['entry'],
                    'wikidataId' => $row['wikidataId'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'geonameId' => $row['geonameId'],
                    'adminLevel' => $row['adminLevel']
                );
            }
            return $locations;
        }
    }

    //recent, near me
    private static function circleSearch($db, $logger, $startDate, $endDate, $latitude, $longitude, $radius, $units, $limit, $magnitude, $intensity, $significance, $type, $order, $latest) {
        /**
         * Geodesy-related code is © 2008-2022 Chris Veness
         * Under an MIT licence, without any warranty express or implied
         * PHP adapted by David Barkman from JavaScript at:
         * https://www.movable-type.co.uk/scripts/latlong-db.html
         */

        if ($units == 'miles') {
            $radius = self::convertMilesToMeters($radius);
        } else if ($units == 'kilometers') {
            $radius = self::convertKilometersToMeters($radius);
        }

        $queryCondition = '';
        if (!empty($startDate) && !empty($endDate)) {
//            $count = 0;
            $queryCondition = "
                AND time BETWEEN $startDate AND $endDate
                AND magnitude >= $magnitude
                AND mmi >= $intensity
                AND sig >= $significance
                $type
            ";
        } else {
            $queryCondition = "
                AND magnitude >= $magnitude
                AND mmi >= $intensity
                AND sig >= $significance
                $type
            ";
        }

//        $count = ($count > 100) ? 100 : $count;

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
                ORDER BY 
                    time $order
                $limit
        ";
        $logger->info('Circle SQL: ' . preg_replace('!\s+!', ' ', $sql));

        $earthquakes = array();
        $earthquakeArray = array();
        $result = mysqli_query($db, $sql);
        if ($result === FALSE) {
            return $earthquakes;
        } else {
            if ($latest) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $date = self::getDateFromTime($row['time'], true, 'D, n/j/y, g:i a');
                    $earthquakes[] = array(
                        'title' => $row['title'],
                        'date' => $date . ' UTC',
                        'url' => $row['url']
                    );
                }
            } else {
                while ($row = mysqli_fetch_assoc($result)) {
                    $rowLatitude = $row['latitude'];
                    $rowLongitude = $row['longitude'];
                    $distance = acos(sin($rowLatitude * pi() / 180) * sin($latitude * pi() / 180) + cos($rowLatitude * pi() / 180) * cos($latitude * pi() / 180) * cos($rowLongitude * pi() / 180 - $longitude * pi() / 180)) * $R;
                    $date = self::getDateFromTime($row['time']);
                    $earthquakes[] = array(
                        'id' => $row['id'],
                        'magnitude' => strval(round($row['magnitude'], 2)),
                        'type' => $row['type'],
                        'title' => $row['title'],
                        'date' => $date,
                        'time' => $row['time'],
                        'updated' => $row['updated'],
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
                        'geometryType' => $row['geometryType'],
                        'depth' => $row['depth'],
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'place' => $row['place'],
                        'distanceKM' => $row['distanceKM'],
                        'placeOnly' => $row['placeOnly'],
                        'location' => $row['location'],
                        'continent' => $row['continent'],
                        'country' => $row['country'],
                        'subnational' => $row['subnational'],
                        'city' => $row['city'],
                        'locality' => $row['locality'],
                        'postcode' => $row['postcode'],
                        'what3words' => $row['what3words'],
                        'timezone' => $row['timezone'],
                        'locationDetails' => self::fetchLocations($logger, $db, $row['entry'])
                    );
                }
            }
            foreach ($earthquakeArray as $earthquake) {
                if ($earthquake['distance'] < $radius) {
                    $earthquakes[] = $earthquake;
                }
            }
//        $dateColumn = array_column($earthquakes, 'date');
//        array_multisort($dateColumn, SORT_DESC, $earthquakes);

//            if ($count > 0) {
//                $earthquakes = array_slice($earthquakes, 0, $count);
//            }

            return $earthquakes;
        }
    }

    public static function convertMilesToMeters($miles) {
        return $miles * 1609.34;
    }

    public static function convertKilometersToMeters($kilometers) {
        return $kilometers * 1000;
    }

    public static function getTimeFromDate($date) {
        $dateObject = new DateTime($date);
        $timeStamp = $dateObject->format('U');
        $miliseconds = $dateObject->format('v');
        if (strlen($miliseconds) > 3) substr($miliseconds, 0, 3);
        return $timeStamp . $miliseconds;
    }

    public static function getDateFromTime($timestamp, $milliseconds = true, $format = 'Y-m-d\TH:i:s') {
        $time = ($milliseconds) ? substr($timestamp, 0, -3) : $timestamp;
        $dateTime = new DateTime();
        $dateTime->setTimestamp($time);
        return $dateTime->format($format);
    }
}