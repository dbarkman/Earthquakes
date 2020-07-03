<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if (count($argv) < 5) {
    echo 'Include arguments, start time, end time and table.' . "\n";
} else {
    $feq = new fetchEarthquakes($argv[1], $argv[2], $argv[3]);
}

class fetchEarthquakes
{
	private $_container;
	private $_logger;
	private $_db;
	private $_table;
	private $_countLimit;
	private $_urlCount;
	private $_urlQuery;
	private $_openCageKey;
	private $_originalStartTime;
	private $_originalEndTime;
	private $_totalEarthquakesProcessed;
    private $_updatedEarthquakes;
    private $_newEarthquakes;
    private $_failedNewEarthquakes;
    private $_apiCalls;
    private $_apiCallsFailed;
    private $_sleepWait;

	public function __construct($startTime, $endTime, $table)
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
		$this->_db = $this->_container->getMySQLDBConnect();
		$this->_table = $table;
		$this->_countLimit = 20000;

		$properties = $this->_container->getProperties();
		$this->_urlQuery = $properties->getUrlQuery();
        $this->_urlCount = $properties->getUrlCount();
        $this->_openCageKey = $properties->getKeyOpenCage();
        $this->_originalStartTime = $startTime;
        $this->_originalEndTime = $endTime;

        $this->_totalEarthquakesProcessed = 0;
        $this->_updatedEarthquakes = 0;
        $this->_newEarthquakes = 0;
        $this->_failedNewEarthquakes = 0;
        $this->_failedUpdateEarthquakes = 0;
        $this->_apiCalls = 0;
        $this->_apiCallsFailed = 0;
        $this->_sleepWait = 1;

        $this->fetchEarthquakes($startTime, $endTime, $this->_openCageKey);
    }

    public function fetchEarthquakes($startTime, $endTime, $openCageKey)
    {
        while ($startTime < $this->_originalEndTime) {
            $endTime = $this->getBestEndDate($startTime, $endTime);
            echo 'Fetching for: ' . $startTime . ' - ' . $endTime . "\n";
            $url = $this->_urlQuery . '?format=geojson' . '&starttime=' . $startTime . '&endtime=' . $endTime . '&orderby=time-asc';
            $this->getEarthquakesFromUSGS($url, $openCageKey);
            $startDate = new DateTime($startTime);
            $endDate = new DateTime($endTime);
            $interval = $startDate->diff($endDate);
            if ($interval->m == 0 && $interval->d > 0) {
                $interval->m = 1;
                $interval->d = 0;
            } elseif ($interval->m == 1 || $interval->m == 2) {
                $interval->m += 1;
            }
            $startTime = $endTime;
            $endTime = $endDate->add($interval)->format('Y-m-d');
            if ($endTime > $this->_originalEndTime) $endTime = $this->_originalEndTime;
        }
        $this->reportResults();
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

	public function getBestEndDate($startTime, $endTime)
    {
        $url = $this->_urlCount . '?format=geojson' . '&starttime=' . $startTime . '&endtime=' . $endTime . '&orderby=time-asc';
        echo 'Checking: ' . $startTime . ' - ' . $endTime . "\n";
        $count = $this->getEarthquakeCountFromUSGS($url);
        echo 'Count: ' . $count . "\n";
        while ($count > $this->_countLimit) {
            $startDate = new DateTime($startTime);
            $endDate = new DateTime($endTime);
            $interval = $startDate->diff($endDate);
            if ($interval->y > 1) {
                $interval->y /= 2;
                $endTime = $startDate->add($interval)->format('Y-m-d');
            } elseif ($interval->y == 1) {
                $interval->y = 0;
                $interval->m = 9;
                $endTime = $startDate->add($interval)->format('Y-m-d');
            } elseif ($interval->m > 1) {
                $interval->m -= 1;
                $endTime = $startDate->add($interval)->format('Y-m-d');
            } elseif ($interval->m == 1) {
                $interval->m = 0;
                $interval->d = 22;
                $endTime = $startDate->add($interval)->format('Y-m-d');
            } elseif ($interval->d > 1) {
                $interval->d = ($interval->d / 4) * 3;
                $endTime = $startDate->add($interval)->format('Y-m-d');
            }
            $url = $this->_urlCount . '?format=geojson' . '&starttime=' . $startTime . '&endtime=' . $endTime . '&orderby=time-asc';
            echo 'Checking: ' . $startTime . ' - ' . $endTime . "\n";
            $count = $this->getEarthquakeCountFromUSGS($url);
            echo 'Count: ' . $count . "\n";
        }
        return $endTime;
    }

	public function getEarthquakeCountFromUSGS($url)
    {
        $this->_logger->info('Counting earthquakes!');

        $usgs = new USGS($this->_logger);
        $earthquakes = $usgs->getEarthquakes($url);
        $this->_apiCalls++;
        if (isset($earthquakes->count)) {
            return $earthquakes->count;
        } else {
            return $this->_countLimit + 1;
        }
    }

	public function getEarthquakesFromUSGS($url, $openCageKey)
	{
	    $this->_logger->info('Sleeping for ' . $this->_sleepWait . ' seconds.');
	    sleep($this->_sleepWait);
		$this->_logger->info('Digging for earthquakes!');

		$usgs = new USGS($this->_logger);
        $earthquakes = $usgs->getEarthquakes($url);
        $this->_apiCalls++;

        if (!isset($earthquakes->features)) {
            if ($this->_sleepWait == 64) {
                $this->_sleepWait = 1;
            } else {
                $this->_sleepWait *= 2;
            }
            $this->_apiCallsFailed++;
            $this->getEarthquakesFromUSGS($url, $openCageKey);
        } else {
            $this->_sleepWait = 1;

            $earthquakeArray = $earthquakes->features;

            $totalCount = $earthquakes->metadata->count;
            echo 'Processing ' . $totalCount . ' earthquakes.' . "\n";

            $count = 0;
            foreach ($earthquakeArray as $earthquakeElement) {
                $count++;
                if (!empty($earthquakeElement->id)) {
                    $earthquake = new Earthquake($this->_logger, $this->_db, $earthquakeElement);
                    $this->_earthquakeId = $earthquake->getId();
                    $this->_totalEarthquakesProcessed++;

                    if ($earthquake->getEarthquakeExists($this->_table) === TRUE) {
                        $updatedDB = $earthquake->getDBUpdateDate($this->_table);
                        $updatedAPI = $earthquake->getUpdated();
                        if ($updatedDB < $updatedAPI) {
                            $latitudeDB = round($earthquake->getDBLatitude($this->_table), 1);
                            $longitudeDB = round($earthquake->getDBLongitude($this->_table), 1);
                            $latitudeAPI = round($earthquake->getLatitude(), 1);
                            $longitudeAPI = round($earthquake->getLongitude(), 1);
                            if ($latitudeDB != $latitudeAPI || $longitudeDB != $longitudeAPI) {
                                $earthquake->preCleanLocationComponents($this->_earthquakeId);
                            }
                            $earthquake->setDate();
                            $earthquake->setLocation();
                            if ($earthquake->updateEarthquake($this->_table)) {
                                $this->_updatedEarthquakes++;
                            } else {
                                $this->_logger->info('🤯 Earthquake NOT updated: ' . $this->_earthquakeId);
                                $this->_failedUpdateEarthquakes++;
                            }
                        }
                    } else {
                        try {
                            $earthquake->setDate();
                            $earthquake->setLocation();
                            if ($earthquake->saveEarthquake($this->_table)) {
                                $this->_newEarthquakes++;
                            } else {
                                $this->_logger->info('🤯 Earthquake NOT added: ' . $this->_earthquakeId . ' **********');
                                $this->_failedNewEarthquakes++;
                            }
                        } catch (mysqli_sql_exception $e) {
                            $this->_logger->error('Problem with earthquake data: ' . $this->_earthquakeId . ', could not insert into database: ' . $e->getMessage());
                        } catch (Exception $e) {
                            $this->_logger->error('Extensive problems with earthquake data: ' . $this->_earthquakeId . ', could not insert into database: ' . $e->getMessage());
                        }
                    }
                } else {
                    $this->_logger->error('Could not extract an id for an earthquake (exception not thrown).');
                }
                $totalCount--;
                if ($count == 1000) {
                    $count = 0;
                    echo $totalCount . ' earthquakes left to review.' . "\n";
                }
            }
        }
	}
}