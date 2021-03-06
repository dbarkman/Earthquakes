<?php

// VERSION 1.1.1
// BUILD 20180824-002

session_start();

require_once dirname(__FILE__) . '/includes/includes.php';

$count = 1000;
if (isset($argv[1])) {
    $count = $argv[1];
}

$feq = new updateEarthquakeLocations($count);

class updateEarthquakeLocations
{
	private $_container;
	private $_logger;
	private $_db;
	private $_table;
    private $_urlQuery;
    private $_bigDataCloudKey;
    private $_count;

	public function __construct($count)
	{
		$this->_container = new Container();
		$this->_logger = $this->_container->getLogger();
		$this->_db = $this->_container->getMySQLDBConnect();
        $this->_table = 'earthquakes';

        $properties = $this->_container->getProperties();
        $this->_urlQuery = $properties->getUrlQuery();
        $this->_bigDataCloudKey = $properties->getKeyBigDataCloud();

        $this->_count = 0;

        $earthquakeArray = $this->getEarthquakesWithNoLocation($count);
        $this->_logger->info('💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥💥');
        foreach ($earthquakeArray as $earthquake) {
            $this->_count++;
            echo $earthquake . ' - ' . $this->_count . "\n";
            $this->setEarthquakeLocation($earthquake);
        }
    }

    public function getEarthquakesWithNoLocation($count)
    {
        $sql = "
            SELECT 
                id 
            FROM 
                $this->_table 
            WHERE 
                locationUpdated = 0
            LIMIT $count
        ";
//        $this->_logger->info('SQL: ' . $sql);

        $earthquakes = array();
        $result = mysqli_query($this->_db, $sql);
        if ($result === FALSE) {
            return $earthquakes;
        } else {
            while ($row = mysqli_fetch_assoc($result)) {
                $earthquakes[] = $row['id'];
                Earthquake::updateLocationUpdated($this->_table, 1, $row['id'], $this->_db, $this->_logger);
            }
            return $earthquakes;
        }
	}

    public function setEarthquakeLocation($id)
    {
        $earthquake = new Earthquake($this->_logger, $this->_db, "", $id);
        $earthquakeEntry = $earthquake->getEarthquakeExists($this->_table);
        $earthquake->getDBLatitude($this->_table);
        $earthquake->getDBLongitude($this->_table);
        $earthquake->setBDCLocationData($this->_bigDataCloudKey);
        if ($earthquake->updateBDCData($this->_table)) {
            $geocode = $earthquake->getGeocode();
            if (isset($geocode->localityInfo->administrative)) {
                $administrativeArray = $geocode->localityInfo->administrative;
                foreach ($administrativeArray as $adminLocation) {
                    $this->createLocation($adminLocation, $earthquakeEntry);
                }
            }
            if (isset($geocode->localityInfo->informative)) {
                $informativeArray = $geocode->localityInfo->informative;
                foreach ($informativeArray as $infoLocation) {
                    $this->createLocation($infoLocation, $earthquakeEntry);
                }
            }
            $this->_logger->info('Earthquake location data updated: ' . $id . ' - ' . $earthquakeEntry . ' - Count: ' . $this->_count);
        } else {
            Earthquake::updateLocationUpdated($this->_table, 0, $id, $this->_db, $this->_logger);
        }
    }

    private function createLocation($locationElement, $earthquakeEntry)
    {
        $location = new Location($this->_logger, $this->_db, $locationElement);
        $locationEntry = $location->getLocationExists();
        if ($locationEntry == 0) {
            $locationEntry = $location->saveLocation();
            if ($locationEntry == 0) {
                $this->_logger->info('🤯 Location NOT added: ' . $locationEntry);
            }
        }
        if ($locationEntry != 0) {
            $this->linkEarthquakeToLocation($earthquakeEntry, $locationEntry);
        }
    }

    private function linkEarthquakeToLocation($earthquakeEntry, $locationEntry)
    {
        $eqLocation = new EarthquakeLocation($this->_logger, $this->_db, $earthquakeEntry, $locationEntry);
        if ($eqLocation->getEarthquakeLocationExists() === FALSE) {
            if ($eqLocation->saveEarthquakeLocation() === FALSE) {
                $this->_logger->info('🤯 Earthquake-Location NOT added: eq: ' . $earthquakeEntry . ' - lc: ' . $locationEntry);
            }
        }
    }
}