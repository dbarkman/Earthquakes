<?php

/**
 * Types.model.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 4/8/22 @ 22:57
 */

class Types {

    public static function GetTypes($logger, $db) {
        $sql = "
            SELECT
                DISTINCT(type),
                COUNT(entry) as count
            FROM earthquakes
            GROUP BY
                type
            ORDER BY
                count DESC
        ";

        $types = array();
        $result = mysqli_query($db, $sql);
        if ($result === FALSE) {
            return $types;
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $types[] = array(
                    'id' => str_replace(' ', '', $row['type']),
                    'type' => $row['type'],
                    'count' => $row['count']
                );
            }
            return $types;
        }
    }
}
