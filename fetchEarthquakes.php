<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

if (count($argv) < 5) {
    echo 'Include an arguments, start time and end time.' . "\n";
} else {
    $feq = new fetchEarthquakes($argv[1], $argv[2], $argv[3], $argv[4]);
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
	private $_originalStartTime;
	private $_originalEndTime;
	private $_totalEarthquakesProcessed;
    private $_duplicateEarthquakes;
    private $_newEarthquakes;
    private $_failedEarthquakes;
    private $_apiCalls;
    private $_apiCallsFailed;

	public function __construct($startTime, $endTime, $table, $function)
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
		$this->_db = $this->_container->getMySQLDBConnect();
		$this->_table = $table;
		$this->_countLimit = 20000;

		$properties = $this->_container->getProperties();
		$this->_urlQuery = $properties->getUrlQuery();
        $this->_urlCount = $properties->getUrlCount();
        $this->_originalStartTime = $startTime;
        $this->_originalEndTime = $endTime;

        $this->_totalEarthquakesProcessed = 0;
        $this->_duplicateEarthquakes = 0;
        $this->_newEarthquakes = 0;
        $this->_failedEarthquakes = 0;
        $this->_apiCalls = 0;
        $this->_apiCallsFailed = 0;

        if ($function == 'fetch') {
            $this->fetchEarthquakes($startTime, $endTime);
        }
        if ($function == 'setDate') {

        }
        if ($function == 'setLocation') {

        }
        if ($function == 'setCountry') {

        }
    }

    public function setDate()
    {
        $count  = 1;
        while ($count > 0) {

        }
    }

    public function fetchEarthquakes($startTime, $endTime)
    {
        while ($startTime < $this->_originalEndTime) {
            $endTime = $this->getBestEndDate($startTime, $endTime);
            echo 'Fetching for: ' . $startTime . ' - ' . $endTime . "\n";
            $url = $this->_urlQuery . '?format=geojson' . '&starttime=' . $startTime . '&endtime=' . $endTime . '&orderby=time-asc';
            $this->getEarthquakes($url);
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
        $this->_logger->info($this->_duplicateEarthquakes . ' earthquakes already existed in the database.');
        $this->_logger->info($this->_newEarthquakes . ' new earthquakes were added to the database.');
        $this->_logger->info($this->_failedEarthquakes . ' new earthquakes failed on insert to the database.');
        $this->_logger->info($this->_apiCalls . ' were made to the USGS API.');
        $this->_logger->info($this->_apiCallsFailed . ' API calls failed.');
	}

	public function getBestEndDate($startTime, $endTime)
    {
        $url = $this->_urlCount . '?format=geojson' . '&starttime=' . $startTime . '&endtime=' . $endTime . '&orderby=time-asc';
        echo 'Checking: ' . $startTime . ' - ' . $endTime . "\n";
        $count = $this->getEarthquakeCount($url);
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
            $count = $this->getEarthquakeCount($url);
            echo 'Count: ' . $count . "\n";
        }
        return $endTime;
    }

	public function getEarthquakeCount($url)
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

	public function getEarthquakes($url)
	{
		$this->_logger->info('Digging for earthquakes!');

		$usgs = new USGS($this->_logger);
        $earthquakes = $usgs->getEarthquakes($url);
        $this->_apiCalls++;

        if (!isset($earthquakes->features)) {
            $this->_apiCallsFailed++;
            $this->getEarthquakes($url);
        } else {
            $earthquakeArray = $earthquakes->features;

            $newEarthquakeCount = 0;
            $failedEarthquakeCount = 0;

            $count = $earthquakes->metadata->count;
            echo '                      Earthquake count: ' . $count . "\n";

            foreach ($earthquakeArray as $earthquakeElement) {
                if (!empty($earthquakeElement->id)) {
                    $earthquake = new Earthquake($this->_logger, $this->_db, $earthquakeElement);
                    $this->_earthquakeId = $earthquake->getId();
                    $this->_totalEarthquakesProcessed++;

                    if ($earthquake->getEarthquakeExists($this->_table) === TRUE) {
                        $this->_logger->debug('Duplicate earthquake: ' . $this->_earthquakeId);
                        $this->_duplicateEarthquakes++;
                    } else {
                        try {
                            if ($earthquake->saveEarthquake($this->_table)) {
                                $newEarthquakeCount++;
                                $this->_newEarthquakes++;
                            } else {
                                $this->_logger->info('🤯 Earthquake NOT added: ' . $this->_earthquakeId . ' **********');
                                $failedEarthquakeCount++;
                                $this->_failedEarthquakes++;
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
            }

            if ($newEarthquakeCount > 0) {
                $earthquakeLabel = ($newEarthquakeCount == 1) ? 'earthquake' : 'earthquakes';
                $this->_logger->info($newEarthquakeCount . ' new ' . $earthquakeLabel . ' added.');
                if ($failedEarthquakeCount > 0) {
                    $earthquakeLabel = ($failedEarthquakeCount == 1) ? 'earthquake' : 'earthquakes';
                    $this->_logger->info($failedEarthquakeCount . ' ' . $earthquakeLabel . ' failed.');
                } else {
                    $this->_logger->info('No earthquakes failed.');
                }
            } else {
                $this->_logger->info('No new earthquakes.');
            }
        }
	}
}