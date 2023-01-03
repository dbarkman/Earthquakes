<?php

/**
 * Created by PhpStorm.
 * User: David Barkman
 * Date: 7/3/20
 * Time: 3:12 PM
 */

class Location
{
    private $_logger;
    private $_db;
    private $_wikidataId;
    private $_name;
    private $_description;
    private $_geonameId;
    private $_adminLevel;

    public function __construct($logger, $db, $location) {
        $this->_logger = $logger;
        $this->_db = $db;

        $this->_wikidataId = (isset($location->wikidataId)) ? mysqli_real_escape_string($this->_db, $location->wikidataId) : '';
        $this->_name = (isset($location->name)) ? mysqli_real_escape_string($this->_db, $location->name) : '';
        $this->_description = (isset($location->description)) ? mysqli_real_escape_string($this->_db, $location->description) : '';
        $this->_geonameId = (isset($location->geonameId)) ? $location->geonameId : 0;
        $this->_adminLevel = (isset($location->adminLevel)) ? $location->adminLevel : 0;
    }

    public function getLocationExists() {
        $sql = "
			SELECT
				entry
			FROM
				locations
			WHERE
			    name = '$this->_name'
			    AND 
			    wikidataId = '$this->_wikidataId'
		";
        $this->_logger->debug('SQL: ' . preg_replace('!\s+!', ' ', $sql));

        $result = mysqli_query($this->_db, $sql);
        $rows = mysqli_num_rows($result);
        if ($rows > 0) {
            $row = $result->fetch_row();
            return $row[0];
        } else {
            return 0;
        }
    }

    public function saveLocation() {
        $sql = "
			INSERT INTO
				locations
			SET
                wikidataId = '$this->_wikidataId',
                name = '$this->_name',
                description = '$this->_description',
                geonameId = '$this->_geonameId',
                adminLevel = '$this->_adminLevel'
		";
        $this->_logger->debug('SQL: ' . preg_replace('!\s+!', ' ', $sql));

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return mysqli_insert_id($this->_db);
        } else {
            $errors = $this->_db->error;
            $this->_logger->error('Database error - IL: ' . $errors);
            return FALSE;
        }
    }

    public function updateLocation() {
        $sql = "
			UPDATE
				locations
			SET
                wikidataId = '$this->_wikidataId',
                name = '$this->_name',
                description = '$this->_description',
                geonameId = '$this->_geonameId',
                adminLevel = '$this->_adminLevel'
			WHERE
			    name = $this->_name
			    AND 
			    wikidataId = $this->_wikidataId
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $errors = $this->_db->error;
            $this->_logger->error('Database error - UL: ' . $errors);
            return FALSE;
        }
    }

    public function getName() {
        return $this->_name;
    }
}