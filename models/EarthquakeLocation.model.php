<?php

/**
 * Created by PhpStorm.
 * User: David Barkman
 * Date: 7/3/20
 * Time: 3:37 PM
 */

class EarthquakeLocation
{
    private $_logger;
    private $_db;
    private $_earthquakeEntry;
    private $_locationEntry;

    public function __construct($logger, $db, $earthquakeEntry, $locationEntry) {
        $this->_logger = $logger;
        $this->_db = $db;

        $this->_earthquakeEntry = $earthquakeEntry;
        $this->_locationEntry = $locationEntry;
    }

    public function getEarthquakeLocationExists() {
        $sql = "
			SELECT
				*
			FROM
				earthquakesLocations
			WHERE
			    earthquakeEntry = $this->_earthquakeEntry
			    AND 
			    locationEntry = $this->_locationEntry
		";

        $result = mysqli_query($this->_db, $sql);
        $rows = mysqli_num_rows($result);

        if ($rows > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function saveEarthquakeLocation()
    {
        $sql = "
			INSERT INTO
				earthquakesLocations
			SET
                earthquakeEntry = '$this->_earthquakeEntry',
                locationEntry = '$this->_locationEntry'
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $errors = $this->_db->error;
            $this->_logger->info('Database error - IEL: ' . $errors);
            return FALSE;
        }
    }

    public function updateEarthquakeLocation()
    {
        $sql = "
			UPDATE
				earthquakesLocations
			SET
                earthquakeEntry = '$this->_earthquakeEntry',
                locationEntry = '$this->_locationEntry'
			WHERE
			    earthquakeEntry = $this->_earthquakeEntry
			    AND 
			    locationEntry = $this->_locationEntry
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $errors = $this->_db->error;
            $this->_logger->info('Database error - UEL: ' . $errors);
            return FALSE;
        }
    }

    public static function deleteEarthquakeLocationConnections($logger, $db, $earthquakeEntry)
    {
        $sql = "
            DELETE FROM
                earthquakesLocations
            WHERE
                earthquakeEntry = $earthquakeEntry
        ";

        mysqli_query($db, $sql);
        $rowsAffected = mysqli_affected_rows($db);
        $logger->info('Deleted ' . $rowsAffected . ' records from Earthquakes-Locations.');
    }
}