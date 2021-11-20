<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if (count($argv) < 3) {
    echo 'Include arguments, commit (true or false), network.' . "\n";
} else {
    $feq = new removeDuplicates($argv[1], $argv);
}

class removeDuplicates
{
    private $_container;
    private $_logger;
    private $_db;
    private $_table;

    public function __construct($commit, $networks)
    {
        $this->_container = new Container();
        $this->_logger = $this->_container->getLogger();
        $this->_db = $this->_container->getMySQLDBConnect();
        $this->_table = 'bdcData';

        unset($networks[1], $networks[0]);

        foreach($networks as $network) {
            $this->getDuplicates($commit, $network);
        }
    }

    public function getDuplicates($commit, $network)
    {
        $whereClause = '';
        if ($network != 'all') {
            $whereClause = "WHERE earthquakeId LIKE '$network%'";
        }
        $sql = "
            SELECT 
                entry, earthquakeId, insertDate
            FROM 
                $this->_table
            $whereClause
            ORDER BY
                earthquakeId ASC, entry DESC
        ";
        echo $sql . "\n";

        $result = mysqli_query($this->_db, $sql);
        if ($result === FALSE) {
            echo 'Failed to retrieve any duplicates.' . "\n";
        } else {
            $totalCount = $result->num_rows;
            echo $totalCount . ' earthquakes to check.' . "\n";
            $duplicateEntries = 0;
            $deletedRows = 0;
            $failedDeletedRows = 0;
            $previousId = -1;
            while ($row = mysqli_fetch_assoc($result)) {
                if ($previousId == $row['earthquakeId']) {
                    $duplicateEntries++;
                    if ($commit == 'true') {
                        if ($this->deleteEarthquake($row['entry'])) {
                            $deletedRows++;
                        } else {
                            $failedDeletedRows++;
                        }
                    }
                }
                $previousId = $row['earthquakeId'];
            }
            echo 'duplicate entries: ' . $duplicateEntries . "\n";
            echo 'deleted rows: ' . $deletedRows . "\n";
            echo 'failed deleted rows: ' . $failedDeletedRows . "\n";
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