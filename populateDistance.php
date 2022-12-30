<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$populateDistance = new PopulateDistance();

class PopulateDistance
{
    private $_container;
    private $_logger;
    private $_db;

    public function __construct() {
        $start = microtime(true);
        $this->_container = new Container();
        $this->_logger = $this->_container->getLogger();
        $this->_db = $this->_container->getMySQLDBConnect();

        $this->_logger->info('Populating distance');
        $nextEq = $this->getNextEarthquake();
        foreach($nextEq as $eq) {
            $entry = $eq['entry'];
            $distanceKM = 0;
            $placeOnly = $eq['place'];
            $pattern = "/^([0-9]{1,})[ ]*(km)/";
            if (preg_match($pattern, $placeOnly, $matches)) {
                $distanceKM = trim($matches[1]);
                $splits = preg_split($pattern, $placeOnly);
                $placeOnly = trim(mysqli_real_escape_string($this->_db, $splits[1]));
            } else {
                $placeOnly = trim(mysqli_real_escape_string($this->_db, $placeOnly));
            }
            $this->populateDistance($entry, $distanceKM, $placeOnly);
//            $this->_logger->info('Place populated: ' . $placeOnly . ', Distance: ' . $distanceKM);
        }
        $time = (microtime(true) - $start);
        $this->_logger->info('Time to populate distance: ' . $time);
    }

    private function getNextEarthquake() {
        $sql = "
            SELECT
                entry, place
            FROM
                earthquakes
            WHERE
                (distanceKM = ''
                OR
                placeOnly = '')
                AND
                place != ''
            ORDER BY
                entry DESC
            LIMIT 1000
        ";
        $earthquakes = array();
        $result = mysqli_query($this->_db, $sql);
        if ($result === FALSE) {
            return $earthquakes;
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $earthquakes[] = array(
                    'entry' => $row['entry'],
                    'place' => $row['place']
                );
            }
            return $earthquakes;
        }
    }

    private function populateDistance($entry, $distanceKM, $placeOnly) {
        $sql = "
			UPDATE
				earthquakes
			SET
				distanceKM = '$distanceKM',
				placeOnly = '$placeOnly'
            WHERE
				entry = '$entry'
		";
//        $this->_logger->info($sql);
        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $this->_logger->error('Database error for: ' . $entry . ' while updating distance - with: ' . $this->_db->error);
            return FALSE;
        }
    }

}