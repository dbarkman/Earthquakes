<?php

/**
 * Token.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 12/20/22 @ 17:05
 */

class Token
{
    private $_logger;
    private $_db;

    private $_uuid;
    private $_token;
    private $_debug;
    private $_sendPush;
    private $_magnitude;
    private $_location;
    private $_radius;
    private $_units;
    private $_latitude;
    private $_longitude;

    public function __construct($logger, $db, $token, $debug, $sendPush, $magnitude, $location = 0, $radius = 0, $units = '', $latitude = 0, $longitude = 0) {
        $this->_logger = $logger;
        $this->_db = $db;
        $this->_uuid = mysqli_real_escape_string($this->_db, UUID::getUUID());
        $this->_token = $token;
        $this->_debug = $debug;
        $this->_sendPush = $sendPush;
        $this->_magnitude = $magnitude;
        $this->_location = $location;
        $this->_radius = $radius;
        $this->_units = $units;
        $this->_latitude = $latitude;
        $this->_longitude = $longitude;
    }

    public function saveToken() {
        $sql = "
			INSERT INTO
				notificationTokens
			SET
				id = '$this->_uuid',
				token = '$this->_token',
				debug = '$this->_debug',
				sendPush = '$this->_sendPush',
				magnitude = '$this->_magnitude',
				location = '$this->_location',
				radius = '$this->_radius',
				units = '$this->_units',
				latitude = '$this->_latitude',
				longitude = '$this->_longitude'
		";
        $this->_logger->info('SQL: ' . preg_replace('!\s+!', ' ', $sql));

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $errors = $this->_db->error;
            $this->_logger->info('Database error on token insert: ' . $errors);
            return FALSE;
        }
    }

    public function updateToken() {
        $sql = "
			UPDATE
				notificationTokens
			SET
				debug = '$this->_debug',
				sendPush = '$this->_sendPush',
				magnitude = '$this->_magnitude',
				location = '$this->_location',
				radius = '$this->_radius',
				units = '$this->_units',
				latitude = '$this->_latitude',
				longitude = '$this->_longitude'
            WHERE
				token = '$this->_token'
		";
        $this->_logger->info('SQL: ' . preg_replace('!\s+!', ' ', $sql));

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        $errors = $this->_db->error;
        if ($rowsAffected === 1 || empty($errors)) {
            return TRUE;
        } else {
            $this->_logger->info('Database error on token update: ' . $errors);
            return FALSE;
        }
    }

    public function getTokenExists() {
        $sql = "
            SELECT
                token
            FROM
                notificationTokens
            WHERE
                token = '$this->_token'
        ";
        $this->_logger->info('SQL: ' . preg_replace('!\s+!', ' ', $sql));

        $result = mysqli_query($this->_db, $sql);
        $rows = mysqli_num_rows($result);
        if ($rows > 0) {
            $row = $result->fetch_row();
            return $row[0];
        } else {
            return 0;
        }
    }

    public static function deleteToken($logger, $db, $token) {
        $sql = "
            DELETE FROM
                notificationTokens
            WHERE
                token = '$token'
        ";
        $logger->info('SQL: ' . preg_replace('!\s+!', ' ', $sql));

        mysqli_query($db, $sql);
        $rowsAffected = mysqli_affected_rows($db);
        if ($rowsAffected > 0) {
            $logger->info('Deleted notification token: ' . $token);
            return TRUE;
        } else {
            $logger->info('Failed to delete notification token: ' . $token);
            return FALSE;
        }
    }

}
