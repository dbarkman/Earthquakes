<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if (count($argv) < 5) {
    echo 'Include arguments, start time, end time, table and action.' . "\n";
} else {
    $feq = new fetchEarthquakes($argv[1], $argv[2], $argv[3], $argv[4]);
}

class fetchEarthquakes
{
	private $_container;
	private $_logger;
	private $_db;
	private $_table;
	private $_action;
	private $_countLimit;
	private $_urlCount;
	private $_urlQuery;
	private $_originalStartTime;
	private $_originalEndTime;
	private $_totalEarthquakesProcessed;
    private $_updatedEarthquakes;
    private $_newEarthquakes;
    private $_deletedEarthquakes;
    private $_failedNewEarthquakes;
    private $_failedUpdateEarthquakes;
    private $_failedDeletedEarthquakes;
    private $_apiCalls;
    private $_apiCallsFailed;
    private $_sleepWait;

	public function __construct($startTime, $endTime, $table, $action)
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
		$this->_db = $this->_container->getMySQLDBConnect();
		$this->_table = $table;
		$this->_action = $action;
		$this->_countLimit = 10000;

		$properties = $this->_container->getProperties();
		$this->_urlQuery = $properties->getUrlQuery();
        $this->_urlCount = $properties->getUrlCount();
        $this->_originalStartTime = $startTime;
        $this->_originalEndTime = $endTime;

        $this->_totalEarthquakesProcessed = 0;
        $this->_updatedEarthquakes = 0;
        $this->_newEarthquakes = 0;
        $this->_deletedEarthquakes = 0;
        $this->_failedNewEarthquakes = 0;
        $this->_failedUpdateEarthquakes = 0;
        $this->_failedDeletedEarthquakes = 0;
        $this->_apiCalls = 0;
        $this->_apiCallsFailed = 0;
        $this->_sleepWait = 1;

        $this->fetchEarthquakes($startTime, $endTime);
    }

    public function fetchEarthquakes($startTime, $endTime)
    {
        while ($startTime < $this->_originalEndTime) {
            $endTime = $this->getBestEndDate($startTime, $endTime);
            $url = $this->_urlQuery . '?format=geojson' . '&starttime=' . $startTime . '&endtime=' . $endTime . '&orderby=time-asc';
            if ($this->_action == 'delete') {
                $url .= '&includedeleted=only';
            }
            $this->getEarthquakesFromUSGS($url);
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
            sleep(2);
        }
        $this->reportResults();
    }

    public function reportResults()
    {
        $this->_logger->info($this->_totalEarthquakesProcessed . ' total earthquakes processed for the range of ' . $this->_originalStartTime . ' - ' . $this->_originalEndTime . ', for ' . $this->_action);
        $this->_logger->info($this->_newEarthquakes . ' earthquakes were added to the database.');
        $this->_logger->info($this->_failedNewEarthquakes . ' earthquakes failed on insert to the database.');
        $this->_logger->info($this->_updatedEarthquakes . ' earthquakes were updated in the database.');
        $this->_logger->info($this->_failedUpdateEarthquakes . ' earthquakes failed on update to the database.');
        $this->_logger->info($this->_deletedEarthquakes . ' earthquakes were deleted from the database.');
        $this->_logger->info($this->_failedDeletedEarthquakes . ' earthquakes failed to delete from the database.');
        $this->_logger->info($this->_apiCalls . ' calls were made to the USGS API.');
        $this->_logger->info($this->_apiCallsFailed . ' API calls failed.');
	}

	public function getBestEndDate($startTime, $endTime)
    {
        $url = $this->_urlCount . '?format=geojson' . '&starttime=' . $startTime . '&endtime=' . $endTime . '&orderby=time-asc';
        $count = $this->getEarthquakeCountFromUSGS($url);
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
            $count = $this->getEarthquakeCountFromUSGS($url);
            sleep(2);
        }
        return $endTime;
    }

	public function getEarthquakeCountFromUSGS($url)
    {
        $usgs = new USGS($this->_logger);
        $earthquakes = $usgs->getEarthquakes($url);
        $this->_apiCalls++;
        if (isset($earthquakes->count)) {
            return $earthquakes->count;
        } else {
            return $this->_countLimit + 1;
        }
    }

	public function getEarthquakesFromUSGS($url)
	{
	    sleep($this->_sleepWait);

		$usgs = new USGS($this->_logger);
        $earthquakes = $usgs->getEarthquakes($url);
        $this->_apiCalls++;

        if (!isset($earthquakes->features)) {
            if ($this->_sleepWait == 32) {
                $this->_sleepWait = 1;
            } else {
                $this->_sleepWait *= 2;
            }
            $this->_apiCallsFailed++;
            $this->getEarthquakesFromUSGS($url);
        } else {
            $this->_sleepWait = 1;

            $earthquakeArray = $earthquakes->features;

            $totalCount = $earthquakes->metadata->count;

            $count = 0;
            foreach ($earthquakeArray as $earthquakeElement) {
                $count++;
                if (!empty($earthquakeElement->id)) {
                    $earthquake = new Earthquake($this->_logger, $this->_db, $earthquakeElement);
                    $this->_earthquakeId = $earthquake->getId();
                    $this->_totalEarthquakesProcessed++;

                    $earthquakeEntry = $earthquake->getEarthquakeExists($this->_table);
                    if ($earthquakeEntry != 0) {
                        if ($this->_action == 'delete') {
                            if ($earthquake->deleteEarthquake($this->_table)) {
                                $this->_deletedEarthquakes++;
                                $this->_logger->info('Earthquake deleted: ' . $this->_earthquakeId);
                            } else {
                                $this->_failedDeletedEarthquakes++;
                                $this->_logger->info('🤯 Earthquake NOT deleted: ' . $this->_earthquakeId);
                            }
                        } else {
                            $updatedDB = $earthquake->getDBUpdateDate($this->_table);
                            $updatedAPI = $earthquake->getAPIUpdateDate();
                            if ($updatedDB < $updatedAPI) {
                                $latitudeDB = round($earthquake->getDBLatitude($this->_table), 1);
                                $longitudeDB = round($earthquake->getDBLongitude($this->_table), 1);
                                $latitudeAPI = round($earthquake->getLatitude(), 1);
                                $longitudeAPI = round($earthquake->getLongitude(), 1);
                                if ($latitudeDB != $latitudeAPI || $longitudeDB != $longitudeAPI) {
                                    EarthquakeLocation::deleteEarthquakeLocationConnections($this->_logger, $this->_db, $earthquakeEntry);
                                    $earthquake->setLocationUpdated(0);
                                    $earthquake->updateBDCData($this->_table);
                                }
                                $earthquake->setDate();
                                $earthquake->setLocation();
                                if ($earthquake->updateEarthquake($this->_table)) {
                                    $this->_logger->info('Earthquake updated: ' . $this->_earthquakeId . ' - ' . $earthquakeEntry);
                                    $this->_updatedEarthquakes++;
                                } else {
                                    $this->_logger->info('🤯 Earthquake NOT updated: ' . $this->_earthquakeId);
                                    $this->_failedUpdateEarthquakes++;
                                }
                            }
                        }
                    } else if($this->_action != 'delete') {
                        try {
                            $earthquake->setDate();
                            $earthquake->setLocation();
                            if ($earthquake->saveEarthquake($this->_table)) {
                                $this->_logger->info('Earthquake added: ' . $this->_earthquakeId . ' - ' . $earthquakeEntry);
                                $this->_newEarthquakes++;
                            } else {
                                $this->_logger->info('🤯 Earthquake NOT added: ' . $this->_earthquakeId . ' **********');
                                var_dump($earthquakeElement);
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
                }
            }
        }
	}
}