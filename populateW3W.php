<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$populateW3W = new populateW3W();

class populateW3W
{
    private $_container;
    private $_logger;
    private $_db;

    public function __construct() {
        $start = microtime(true);
        $this->_container = new Container();
        $this->_logger = $this->_container->getLogger();
        $this->_db = $this->_container->getMySQLDBConnect();

        $w3w = new What3Words($this->_logger);

        $nextEq = $this->getNextEarthquake();
        foreach($nextEq as $eq) {
            $coordinates = $eq['latitude'] . '%2C' . $eq['longitude'];
            $words = $w3w->getWhat3Words($coordinates);
            $this->updateW3W($eq['entry'], $words);
            sleep(1);
        }
        $this->_logger->info('Updated W3W for: ' . count($nextEq) . ' earthquakes.');
        $time = (microtime(true) - $start);
        $this->_logger->info('Time to update w3w: ' . $time);
    }

    private function getNextEarthquake() {
        $sql = "
            SELECT
                entry, latitude, longitude
            FROM
                earthquakes
            WHERE
                what3words IS null
            ORDER BY
                entry DESC
        ";
//        LIMIT 45
        $earthquakes = array();
        $result = mysqli_query($this->_db, $sql);
        if ($result === FALSE) {
            return $earthquakes;
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $earthquakes[] = array(
                    'entry' => $row['entry'],
                    'latitude' => $row['latitude'],
                    'longitude' => $row['longitude']
                );
            }
            return $earthquakes;
        }
    }

    private function updateW3W($entry, $words) {
        $sql = "
			UPDATE
				earthquakes
			SET
				what3words = '$words'
            WHERE
				entry = '$entry'
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $this->_logger->error('Database error for: ' . $entry . ' while inserting: ' . $words . ' - with: ' . $this->_db->error);
            return FALSE;
        }
    }

}