<?php

/**
 * Created by PhpStorm.
 * User: David Barkman
 * Date: 1/22/16
 * Time: 12:19 PM
 */
class Earthquake
{
    private $_logger;
    private $_db;
	private $_id;
    private $_title;
    private $_magnitude;
    private $_type;
    private $_date;
    private $_time;
    private $_updated;
    private $_place;
    private $_location;
    private $_continent;
    private $_country;
    private $_subnational;
    private $_city;
    private $_locality;
    private $_postcode;
    private $_timezone;
    private $_latitude;
    private $_longitude;
    private $_depth;
    private $_geometryType;
    private $_tsunami;
    private $_status;
    private $_ids;
    private $_types;
    private $_felt;
    private $_sig;
    private $_cdi;
    private $_mmi;
	private $_net;
	private $_sources;
	private $_rms;
	private $_gap;
	private $_magType;
    private $_code;
    private $_nst;
    private $_dmin;
    private $_alert;
    private $_url;
    private $_detailUrl;
    private $_locationUpdated;
    private $_geocode;

	public function __construct($logger, $db, $earthquake, $id = null) {
		$this->_logger = $logger;
		$this->_db = $db;

        if (isset($earthquake->id)) {
            $this->_id = $earthquake->id;
        } else if (isset($id)) {
            $this->_id = $id;
        } else {
            $this->_id = 0;
        }
        $this->_title = (isset($earthquake->properties->title)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->title) : '';
		$this->_magnitude = (isset($earthquake->properties->mag)) ? $earthquake->properties->mag : 0.0;
        $this->_type = (isset($earthquake->properties->type)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->type) : '';
        $this->_time = (isset($earthquake->properties->time)) ? $earthquake->properties->time : 0;
        $this->_updated = (isset($earthquake->properties->updated)) ? $earthquake->properties->updated : 0;
		$this->_place = (isset($earthquake->properties->place)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->place) : '';
        $this->_continent = '';
        $this->_country = '';
        $this->_subnational = '';
        $this->_city = '';
        $this->_locality = '';
        $this->_postcode = '';
        $this->_timezone = 0;
        $this->_latitude = (isset($earthquake->geometry->coordinates[1])) ? $earthquake->geometry->coordinates[1] : 0.0;
        $this->_longitude = (isset($earthquake->geometry->coordinates[0])) ? $earthquake->geometry->coordinates[0] : 0.0;
        $this->_depth = (isset($earthquake->geometry->coordinates[2])) ? $earthquake->geometry->coordinates[2] : 0.0;
        $this->_geometryType = (isset($earthquake->geometry->type)) ? mysqli_real_escape_string($this->_db, $earthquake->geometry->type) : '';
        $this->_tsunami = (isset($earthquake->properties->tsunami)) ? $earthquake->properties->tsunami : 0;
        $this->_status = (isset($earthquake->properties->status)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->status) : '';
        $this->_ids = (isset($earthquake->properties->ids)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->ids) : '';
        $this->_types = (isset($earthquake->properties->types)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->types) : '';
        $this->_felt = (isset($earthquake->properties->felt)) ? $earthquake->properties->felt : 0;
        $this->_sig = (isset($earthquake->properties->sig)) ? $earthquake->properties->sig : 0;
        $this->_cdi = (isset($earthquake->properties->cdi)) ? $earthquake->properties->cdi : 0.0;
        $this->_mmi = (isset($earthquake->properties->mmi)) ? $earthquake->properties->mmi : 0.0;
		$this->_net = (isset($earthquake->properties->net)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->net) : '';
        $this->_sources = (isset($earthquake->properties->sources)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->sources) : '';
        $this->_rms = (isset($earthquake->properties->rms)) ? $earthquake->properties->rms : 0.0;
        $this->_gap = (isset($earthquake->properties->gap)) ? $earthquake->properties->gap : 0.0;
        $this->_magType = (isset($earthquake->properties->magType)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->magType) : '';
		$this->_code = (isset($earthquake->properties->code)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->code) : '';
		$this->_nst = (isset($earthquake->properties->nst)) ? $earthquake->properties->nst : 0;
		$this->_dmin = (isset($earthquake->properties->dmin)) ? $earthquake->properties->dmin : 0.0;
        $this->_alert = (isset($earthquake->properties->alert)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->alert) : '';
        $this->_url = (isset($earthquake->properties->url)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->url) : '';
        $this->_detailUrl = (isset($earthquake->properties->detail)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->detail) : '';
        $this->_locationUpdated = 0;
	}

	public function setDate() {
        $this->_date = Earthquakes::getDateFromTime($this->_time);
    }

    public function setLocation() {
	    if (strpos($this->_place, 'of') != FALSE) {
            $locationArray = explode('of', $this->_place);
            $this->_location = trim($locationArray[1]);
        } else {
	        $this->_location = $this->_place;
        }
    }

    public function setBDCLocationData($key)
    {
        $url = 'https://api.bigdatacloud.net/data/reverse-geocode-with-timezone?latitude=' . $this->_latitude . '&longitude=' . $this->_longitude . '&localityLanguage=en&key=' . $key;
        $bigDataCloud = new BigDataCloud($this->_logger);
        $geocode = $bigDataCloud->reverseGeocode($url);
        $this->_geocode = $geocode;
        $this->_continent = (isset($geocode->continent)) ? mysqli_real_escape_string($this->_db, $geocode->continent) : '';
        $this->_country = (isset($geocode->countryName)) ? mysqli_real_escape_string($this->_db, $geocode->countryName) : '';
        $this->_subnational = (isset($geocode->principalSubdivision)) ? mysqli_real_escape_string($this->_db, $geocode->principalSubdivision) : '';
        $this->_city = (isset($geocode->city)) ? mysqli_real_escape_string($this->_db, $geocode->city) : '';
        $this->_locality = (isset($geocode->locality)) ? mysqli_real_escape_string($this->_db, $geocode->locality) : '';
        $this->_postcode = (isset($geocode->postcode)) ? mysqli_real_escape_string($this->_db, $geocode->postcode) : '';
        $this->_timezone = (isset($geocode->timeZone)) ? $geocode->timeZone->utcOffsetSeconds / 60 : -1;
        $this->_locationUpdated = 1;
        if (isset($geocode->status)) {
            if ($geocode->status != 200) {
                $this->_locationUpdated = 0;
            }
        }
        BDCData::saveBDCData($this->_logger, $this->_db, $this->_id, $this->_latitude, $this->_longitude, $geocode);
    }

    public function saveEarthquake($table) {
        $sql = "
			INSERT INTO
				$table
			SET
				id = '$this->_id',
				title = '$this->_title',
				magnitude = '$this->_magnitude',
				type = '$this->_type',
				date = '$this->_date',
				time = '$this->_time',
				updated = '$this->_updated',
				place = '$this->_place',
				location = '$this->_location',
				continent = '$this->_continent',
				country = '$this->_country',
				subnational = '$this->_subnational',
				city = '$this->_city',
				locality = '$this->_locality',
				postcode = '$this->_postcode',
				timezone = '$this->_timezone',
				latitude = '$this->_latitude',
				longitude = '$this->_longitude',
				depth = '$this->_depth',
				geometryType = '$this->_geometryType',
				tsunami = '$this->_tsunami',
				status = '$this->_status',
				ids = '$this->_ids',
				types = '$this->_types',
				felt = '$this->_felt',
				sig = '$this->_sig',
				cdi = '$this->_cdi',
				mmi = '$this->_mmi',
				net = '$this->_net',
				sources = '$this->_sources',
				rms = '$this->_rms',
				gap = '$this->_gap',
				magType = '$this->_magType',
				code = '$this->_code',
				nst = '$this->_nst',
				dmin = '$this->_dmin',
				alert = '$this->_alert',
				url = '$this->_url',
				detailUrl = '$this->_detailUrl',
				locationUpdated = '$this->_locationUpdated'
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return mysqli_insert_id($this->_db);
        } else {
            $errors = $this->_db->error;
            $this->_logger->info('Database error - IEQ: ' . $errors);
            return FALSE;
        }
    }

    public function updateEarthquake($table) {
        $sql = "
			UPDATE
				$table
			SET
				title = '$this->_title',
				magnitude = '$this->_magnitude',
				type = '$this->_type',
				date = '$this->_date',
				time = '$this->_time',
				updated = '$this->_updated',
				place = '$this->_place',
				location = '$this->_location',
				latitude = '$this->_latitude',
				longitude = '$this->_longitude',
				depth = '$this->_depth',
				geometryType = '$this->_geometryType',
				tsunami = '$this->_tsunami',
				status = '$this->_status',
				ids = '$this->_ids',
				types = '$this->_types',
				felt = '$this->_felt',
				sig = '$this->_sig',
				cdi = '$this->_cdi',
				mmi = '$this->_mmi',
				net = '$this->_net',
				sources = '$this->_sources',
				rms = '$this->_rms',
				gap = '$this->_gap',
				magType = '$this->_magType',
				code = '$this->_code',
				nst = '$this->_nst',
				dmin = '$this->_dmin',
				alert = '$this->_alert',
				url = '$this->_url',
				detailUrl = '$this->_detailUrl'
            WHERE
				id = '$this->_id'
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $errors = $this->_db->error;
            $this->_logger->info('Database error - UEQ: ' . $errors);
            return FALSE;
        }
    }

    public function updateBDCData($table) {
        $sql = "
			UPDATE
				$table
			SET
				continent = '$this->_continent',
				country = '$this->_country',
				subnational = '$this->_subnational',
				city = '$this->_city',
				locality = '$this->_locality',
				postcode = '$this->_postcode',
				timezone = '$this->_timezone',
				locationUpdated = '$this->_locationUpdated'
            WHERE
				id = '$this->_id'
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            if ($errors = $this->_db->error) {
                $this->_logger->info('Database error for - UBDCD: ' . $this->_id . ' - UBDCD: ' . $errors);
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }

    public static function updateTimezone($table, $timezone, $id, $db, $logger) {
        $sql = "
			UPDATE
				$table
			SET
				timezone = '$timezone'
            WHERE
				id = '$id'
		";

        mysqli_query($db, $sql);
        $rowsAffected = mysqli_affected_rows($db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            if ($errors = $db->error) {
                $logger->info('Database error for: ' . $id . ' - UT: ' . $errors);
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }

    public static function updateLocationUpdated($table, $locationUpdated, $id, $db, $logger) {
        $sql = "
			UPDATE
				$table
			SET
				locationUpdated = '$locationUpdated'
            WHERE
				id = '$id'
		";

        mysqli_query($db, $sql);
        $rowsAffected = mysqli_affected_rows($db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            if ($errors = $db->error) {
                $logger->info('Database error for: ' . $id . ' - UT: ' . $errors);
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }

    public function getEarthquakeExists($table) {
        $sql = "
			SELECT
				entry
			FROM
				$table
			WHERE
				id = '$this->_id'
		";

        $result = mysqli_query($this->_db, $sql);
        $rows = mysqli_num_rows($result);
        if ($rows > 0) {
            $row = $result->fetch_row();
            return $row[0];
        } else {
            return 0;
        }
    }

    public function getDBUpdateDate($table) {
        $sql = "
			SELECT
				updated
			FROM
				$table
			WHERE
				id = '$this->_id'
		";

        $result = mysqli_query($this->_db, $sql);
        $rows = mysqli_num_rows($result);
        if ($rows > 0) {
            $row = $result->fetch_row();
            return $row[0];
        } else {
            return 0;
        }
    }

    public function getDBLatitude($table) {
        $sql = "
			SELECT
				latitude
			FROM
				$table
			WHERE
				id = '$this->_id'
		";

        $result = mysqli_query($this->_db, $sql);
        $rows = mysqli_num_rows($result);
        if ($rows > 0) {
            $row = $result->fetch_row();
            $this->_latitude = $row[0];
            return $row[0];
        } else {
            return 0;
        }
    }

    public function getDBLongitude($table) {
        $sql = "
			SELECT
				longitude
			FROM
				$table
			WHERE
				id = '$this->_id'
		";

        $result = mysqli_query($this->_db, $sql);
        $rows = mysqli_num_rows($result);
        if ($rows > 0) {
            $row = $result->fetch_row();
            $this->_longitude = $row[0];
            return $row[0];
        } else {
            return 0;
        }
    }

    public function deleteEarthquake($table)
    {
        $sql = "
            DELETE FROM
                $table
            WHERE
                id = '$this->_id'
        ";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);
        if ($rowsAffected > 0) {
            $this->_logger->info('Deleted earthquake: ' . $this->_id);
            return TRUE;
        } else {
            $this->_logger->info('Failed to delete earthquake: ' . $this->_id);
            return FALSE;
        }
    }

    /**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @return mixed
	 */
	public function getAPIUpdateDate()
	{
		return $this->_updated;
	}

    /**
	 * @return mixed
	 */
	public function getLatitude()
	{
		return $this->_latitude;
	}

	/**
	 * @return mixed
	 */
	public function getLongitude()
	{
		return $this->_longitude;
	}

    /**
     * @return mixed
     */
    public function getGeocode()
    {
        return $this->_geocode;
    }

    /**
     * @return mixed
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return mixed
     */
    public function setTimezone($timezone)
    {
        $this->_timezone = $timezone;
    }

    /**
     * @return mixed
     */
    public function setLocationUpdated($locationUpdated)
    {
        $this->_locationUpdated = $locationUpdated;
    }
}