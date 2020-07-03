<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if (count($argv) < 6) {
    echo 'Include arguments, start time, end time, table, function and force.' . "\n";
} else {
    $feq = new updateBlankEqData($argv[1], $argv[2], $argv[3], $argv[4], $argv[5]);
}

class updateBlankEqData
{
	private $_container;
	private $_logger;
	private $_db;
	private $_table;
	private $_originalStartTime;
	private $_originalEndTime;
	private $_totalEarthquakesProcessed;
    private $_updatedEarthquakes;
    private $_newEarthquakes;
    private $_failedNewEarthquakes;
    private $_apiCalls;
    private $_apiCallsFailed;

	public function __construct($startTime, $endTime, $table, $function, $force = false)
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
		$this->_db = $this->_container->getMySQLDBConnect();
		$this->_table = $table;

        $this->_totalEarthquakesProcessed = 0;
        $this->_updatedEarthquakes = 0;
        $this->_newEarthquakes = 0;
        $this->_failedNewEarthquakes = 0;
        $this->_failedUpdateEarthquakes = 0;

        $startTimestamp = Earthquakes::getTimeFromDate($startTime);
        $endTimestamp = Earthquakes::getTimeFromDate($endTime);
        echo 'Running ' . $function . ' for: ' . $startTime . ' - ' . $endTime . "\n";
        $this->getEarthquakesWithBlanks($startTimestamp, $endTimestamp, $function, $force);
    }

    public function getEarthquakesWithBlanks($startTimestamp, $endTimestamp, $function, $force)
    {
        $queryCondition = '';
        if ($function == 'setDate' && $force == 'false') {
            $queryCondition = 'AND date IS null';
        } else if ($function == 'setLocation' && $force == 'false') {
            $queryCondition = "AND location = ''";
        }
        $sql = "
                SELECT
                    id, time, place
                FROM
                    $this->_table
                WHERE
                    time > $startTimestamp
                AND
                    time < $endTimestamp
                $queryCondition
            ";
//        echo 'SQL: ' . $sql . "\n";

        $result = mysqli_query($this->_db, $sql);
        if ($result === FALSE) {
            echo 'Failed to retrieve any earthquakes.' . "\n";
        } else {
            $totalCount = $result->num_rows;
            echo $totalCount . ' earthquakes to update.' . "\n";
            $count = 0;
            while ($row = mysqli_fetch_assoc($result)) {
                $count++;
                if ($function == 'setDate') {
                    $date = Earthquakes::getDateFromTime($row['time']);
                    $this->updateEarthquakeBlanks($row['id'], $date, null, $function, $force);
                } else if ($function == 'setLocation') {
                    $location = $row['place'];
                    if (strpos($row['place'], 'of') != FALSE) {
                        $locationArray = explode('of', $row['place']);
                        $location = trim($locationArray[1]);
                    }
                    $this->updateEarthquakeBlanks($row['id'], null, $location, $function, $force);
                }
                $totalCount--;
                if ($count == 100) {
                    $count = 0;
                    echo $totalCount . ' earthquakes left to update.' . "\n";
                }
            }
        }
	}

	public function updateEarthquakeBlanks($id, $date, $location, $function, $force)
    {
        $setColumn = '';
        if ($function == 'setDate' && $date != null) {
            $setColumn = "SET date = '$date'";
        } else if ($function == 'setLocation' && $location != null) {
            $escapedLocation = mysqli_real_escape_string($this->_db, $location);
            $setColumn = "SET location = '$escapedLocation'";
        }
        $sql = "
			UPDATE
				$this->_table
			$setColumn
            WHERE
				id = '$id'
		";
//        echo 'SQL: ' . $sql . "\n";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else if ($force == 'true') {
            return TRUE;
        } else {
            $errors = $this->_db->error;
            $this->_logger->info('Database error - UB: ' . $errors);
            return FALSE;
        }
    }

    public function reportResults()
    {
        $this->_logger->info($this->_totalEarthquakesProcessed . ' total earthquakes processed for the range of ' . $this->_originalStartTime . ' - ' . $this->_originalEndTime . '.');
        $this->_logger->info($this->_newEarthquakes . ' earthquakes were added to the database.');
        $this->_logger->info($this->_failedNewEarthquakes . ' earthquakes failed on insert to the database.');
        $this->_logger->info($this->_updatedEarthquakes . ' earthquakes were updated in the database.');
        $this->_logger->info($this->_failedNewEarthquakes . ' earthquakes failed on update to the database.');
        $this->_logger->info($this->_apiCalls . ' calls were made to the USGS API.');
        $this->_logger->info($this->_apiCallsFailed . ' API calls failed.');
	}
}