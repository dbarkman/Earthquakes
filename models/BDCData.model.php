<?php

/**
 * Created by PhpStorm.
 * User: David Barkman
 * Date: 7/5/20
 * Time: 9:01 AM
 */

class BDCData
{
    private $_logger;
    private $_db;
    private $_earthquakeId;
    private $_latitude;
    private $_longitude;
    private $_response;

    public static function saveBDCData($logger, $db, $earthquakeId, $latitude, $longitude, $response) {
        $responseEncoded = json_encode($response);
        $responseEscaped = mysqli_real_escape_string($db, $responseEncoded);
        $sql = "
			INSERT INTO
				bdcData
			SET
                earthquakeId = '$earthquakeId',
                latitude = '$latitude',
                longitude = '$longitude',
                response = '$responseEscaped'
		";
        $logger->debug('SQL: ' . preg_replace('!\s+!', ' ', $sql));

        mysqli_query($db, $sql);
        $rowsAffected = mysqli_affected_rows($db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $errors = $db->error;
            $logger->error('Database error - IBDCD: ' . $errors);
            return FALSE;
        }
    }
}