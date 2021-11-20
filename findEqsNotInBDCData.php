<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

//if (count($argv) < 3) {
//    echo 'Include arguments, commit (true or false), network.' . "\n";
//} else {
//}
$feq = new findEqsNotInBDCData();

class findEqsNotInBDCData
{
    private $_container;
    private $_logger;
    private $_db;
    private $_table;

    public function __construct()
    {
        $this->_container = new Container();
        $this->_logger = $this->_container->getLogger();
        $this->_db = $this->_container->getMySQLDBConnect();
        $this->_table = 'bdcData';

        $this->getDuplicates();
    }

    public function getDuplicates()
    {
        $sql = "
            SELECT
                eq.id
            FROM
                earthquakes eq
            WHERE
                eq.id
                NOT IN (
                    SELECT 
                        DISTINCT(bdc.earthquakeId)
                    FROM 
                        bdcData bdc
                )
        ";
        echo $sql . "\n";

        $result = mysqli_query($this->_db, $sql);
        if ($result === FALSE) {
            echo 'Failed to retrieve any records.' . "\n";
        } else {
            $count = 0;
            $totalCount = $result->num_rows;
            echo $totalCount . ' earthquakes missing.' . "\n";
//            while ($row = mysqli_fetch_assoc($result)) {
//                $count++;
//                Earthquake::updateLocationUpdated('earthquakes', 0, $row['id'], $this->_db, $this->_logger);
//            }
            echo $count . ' earthquakes updated.' . "\n";
        }
    }

    public function deleteEarthquake($entry)
    {
        $sql = "
            DELETE FROM
                $this->_table
            WHERE
                entry = '$entry'
        ";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);
        if ($rowsAffected > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}