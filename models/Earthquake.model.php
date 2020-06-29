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
	private $_magnitude;
	private $_place;
	private $_location;
	private $_time;
	private $_date;
	private $_diurnal;
	private $_updated;
	private $_timezone;
    private $_url;
	private $_detailUrl;
    private $_felt;
    private $_cdi;
    private $_mmi;
    private $_alert;
	private $_status;
	private $_tsunami;
	private $_sig;
	private $_net;
	private $_code;
	private $_ids;
	private $_sources;
	private $_types;
	private $_nst;
	private $_dmin;
	private $_rms;
	private $_gap;
	private $_magType;
	private $_type;
	private $_title;
	private $_geometryType;
	private $_latitude;
	private $_longitude;
	private $_depth;

	public function __construct($logger, $db, $earthquake) {
		$this->_logger = $logger;
		$this->_db = $db;

        $latitude = $earthquake->geometry->coordinates[1];
        $longitude = $earthquake->geometry->coordinates[0];
        $depth = $earthquake->geometry->coordinates[2];

		$this->_id = $earthquake->id;
		$this->_magnitude = (isset($earthquake->properties->mag)) ? $earthquake->properties->mag : 0.0;
		$this->_place = (isset($earthquake->properties->place)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->place) : '';
		$this->_time = (isset($earthquake->properties->time)) ? $earthquake->properties->time : 0;
		$this->_updated = (isset($earthquake->properties->updated)) ? $earthquake->properties->updated : 0;
		$this->_timezone = (isset($earthquake->properties->tz)) ? $earthquake->properties->tz : 0;
		$this->_url = (isset($earthquake->properties->url)) ? $earthquake->properties->url : '';
        $this->_detailUrl = (isset($earthquake->properties->detail)) ? $earthquake->properties->detail : '';
        $this->_felt = (isset($earthquake->properties->felt)) ? $earthquake->properties->felt : 0;
        $this->_cdi = (isset($earthquake->properties->cdi)) ? $earthquake->properties->cdi : 0.0;
        $this->_mmi = (isset($earthquake->properties->mmi)) ? $earthquake->properties->mmi : 0.0;
        $this->_alert = (isset($earthquake->properties->alert)) ? $earthquake->properties->alert : '';
		$this->_status = (isset($earthquake->properties->status)) ? $earthquake->properties->status : '';
		$this->_tsunami = (isset($earthquake->properties->tsunami)) ? $earthquake->properties->tsunami : 0;
		$this->_sig = (isset($earthquake->properties->sig)) ? $earthquake->properties->sig : 0;
		$this->_net = (isset($earthquake->properties->net)) ? $earthquake->properties->net : '';
		$this->_code = (isset($earthquake->properties->code)) ? $earthquake->properties->code : '';
		$this->_ids = (isset($earthquake->properties->ids)) ? $earthquake->properties->ids : '';
		$this->_sources = (isset($earthquake->properties->sources)) ? $earthquake->properties->sources : '';
		$this->_types = (isset($earthquake->properties->types)) ? $earthquake->properties->types : '';
		$this->_nst = (isset($earthquake->properties->nst)) ? $earthquake->properties->nst : 0;
		$this->_dmin = (isset($earthquake->properties->dmin)) ? $earthquake->properties->dmin : 0.0;
		$this->_rms = (isset($earthquake->properties->rms)) ? $earthquake->properties->rms : 0.0;
		$this->_gap = (isset($earthquake->properties->gap)) ? $earthquake->properties->gap : 0.0;
		$this->_magType = (isset($earthquake->properties->magType)) ? $earthquake->properties->magType : '';
		$this->_type = (isset($earthquake->properties->type)) ? $earthquake->properties->type : '';
		$this->_title = (isset($earthquake->properties->title)) ? mysqli_real_escape_string($this->_db, $earthquake->properties->title) : '';
        $this->_geometryType = (isset($earthquake->geometry->type)) ? $earthquake->geometry->type : '';
		$this->_latitude = (isset($latitude)) ? $latitude : 0.0;
		$this->_longitude = (isset($longitude)) ? $longitude : 0.0;
		$this->_depth = (isset($depth)) ? $depth : 0.0;
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

    public function setOpenCageGeocode($openCageKey)
    {
        $url = 'https://api.opencagedata.com/geocode/v1/json?q=' . $this->_latitude . '+' . $this->_longitude . '&key=' . $openCageKey;
        $openCage = new OpenCage($this->_logger);
        $geocode = $openCage->reverseGeocode($url);
        if ($geocode->status->code == 200 && $geocode->total_results > 0) {
            if (isset($geocode->results[0]->components)) {
                $components = $geocode->results[0]->components;
                $this->insertIntoLocationComponents($this->_id, null, null, $components);
                if (isset($components->_category)) $this->insertIntoLocationComponents($this->_id, 'category', $components->_category);
                if (isset($components->_type)) $this->insertIntoLocationComponents($this->_id, 'type', $components->_type);
                if (isset($components->body_of_water)) $this->insertIntoLocationComponents($this->_id, 'body_of_water', $components->body_of_water);
                if (isset($components->building)) $this->insertIntoLocationComponents($this->_id, 'building', $components->building);
                if (isset($components->city)) $this->insertIntoLocationComponents($this->_id, 'city', $components->city);
                if (isset($components->city_block)) $this->insertIntoLocationComponents($this->_id, 'city_block', $components->city_block);
                if (isset($components->city_district)) $this->insertIntoLocationComponents($this->_id, 'city_district', $components->city_district);
                if (isset($components->commercial)) $this->insertIntoLocationComponents($this->_id, 'commercial', $components->commercial);
                if (isset($components->continent)) $this->insertIntoLocationComponents($this->_id, 'continent', $components->continent);
                if (isset($components->country)) $this->insertIntoLocationComponents($this->_id, 'country', $components->country);
                if (isset($components->country_code)) $this->insertIntoLocationComponents($this->_id, 'country_code', $components->country_code);
                if (isset($components->country_name)) $this->insertIntoLocationComponents($this->_id, 'country_name', $components->country_name);
                if (isset($components->county)) $this->insertIntoLocationComponents($this->_id, 'county', $components->county);
                if (isset($components->county_code)) $this->insertIntoLocationComponents($this->_id, 'county_code', $components->county_code);
                if (isset($components->croft)) $this->insertIntoLocationComponents($this->_id, 'croft', $components->croft);
                if (isset($components->district)) $this->insertIntoLocationComponents($this->_id, 'district', $components->district);
                if (isset($components->footway)) $this->insertIntoLocationComponents($this->_id, 'footway', $components->footway);
                if (isset($components->hamlet)) $this->insertIntoLocationComponents($this->_id, 'hamlet', $components->hamlet);
                if (isset($components->house)) $this->insertIntoLocationComponents($this->_id, 'house', $components->house);
                if (isset($components->house_number)) $this->insertIntoLocationComponents($this->_id, 'house_number', $components->house_number);
                if (isset($components->houses)) $this->insertIntoLocationComponents($this->_id, 'houses', $components->houses);
                if (isset($components->industrial)) $this->insertIntoLocationComponents($this->_id, 'industrial', $components->industrial);
                if (isset($components->island)) $this->insertIntoLocationComponents($this->_id, 'island', $components->island);
                if (isset($components->local_administrative_area)) $this->insertIntoLocationComponents($this->_id, 'local_administrative_area', $components->local_administrative_area);
                if (isset($components->locality)) $this->insertIntoLocationComponents($this->_id, 'locality', $components->locality);
                if (isset($components->municipality)) $this->insertIntoLocationComponents($this->_id, 'municipality', $components->municipality);
                if (isset($components->neighbourhood)) $this->insertIntoLocationComponents($this->_id, 'neighbourhood', $components->neighbourhood);
                if (isset($components->partial_postcode)) $this->insertIntoLocationComponents($this->_id, 'partial_postcode', $components->partial_postcode);
                if (isset($components->path)) $this->insertIntoLocationComponents($this->_id, 'path', $components->path);
                if (isset($components->pedestrian)) $this->insertIntoLocationComponents($this->_id, 'pedestrian', $components->pedestrian);
                if (isset($components->place)) $this->insertIntoLocationComponents($this->_id, 'place', $components->place);
                if (isset($components->postal_city)) $this->insertIntoLocationComponents($this->_id, 'postal_city', $components->postal_city);
                if (isset($components->postcode)) $this->insertIntoLocationComponents($this->_id, 'postcode', $components->postcode);
                if (isset($components->province)) $this->insertIntoLocationComponents($this->_id, 'province', $components->province);
                if (isset($components->public_building)) $this->insertIntoLocationComponents($this->_id, 'public_building', $components->public_building);
                if (isset($components->quarter)) $this->insertIntoLocationComponents($this->_id, 'quarter', $components->quarter);
                if (isset($components->region)) $this->insertIntoLocationComponents($this->_id, 'region', $components->region);
                if (isset($components->residential)) $this->insertIntoLocationComponents($this->_id, 'residential', $components->residential);
                if (isset($components->road)) $this->insertIntoLocationComponents($this->_id, 'road', $components->road);
                if (isset($components->road_reference)) $this->insertIntoLocationComponents($this->_id, 'road_reference', $components->road_reference);
                if (isset($components->road_reference_intl)) $this->insertIntoLocationComponents($this->_id, 'road_reference_intl', $components->road_reference_intl);
                if (isset($components->square)) $this->insertIntoLocationComponents($this->_id, 'square', $components->square);
                if (isset($components->state)) $this->insertIntoLocationComponents($this->_id, 'state', $components->state);
                if (isset($components->state_code)) $this->insertIntoLocationComponents($this->_id, 'state_code', $components->state_code);
                if (isset($components->state_district)) $this->insertIntoLocationComponents($this->_id, 'state_district', $components->state_district);
                if (isset($components->street)) $this->insertIntoLocationComponents($this->_id, 'street', $components->street);
                if (isset($components->street_name)) $this->insertIntoLocationComponents($this->_id, 'street_name', $components->street_name);
                if (isset($components->street_number)) $this->insertIntoLocationComponents($this->_id, 'street_number', $components->street_number);
                if (isset($components->subdivision)) $this->insertIntoLocationComponents($this->_id, 'subdivision', $components->subdivision);
                if (isset($components->suburb)) $this->insertIntoLocationComponents($this->_id, 'suburb', $components->suburb);
                if (isset($components->town)) $this->insertIntoLocationComponents($this->_id, 'town', $components->town);
                if (isset($components->village)) $this->insertIntoLocationComponents($this->_id, 'village', $components->village);
            }
            if (isset($geocode->results[0]->annotations)) {
                $annotations = $geocode->results[0]->annotations;
                if (isset($annotations->what3words) && isset($annotations->what3words->words)) {
                    $this->insertIntoLocationComponents($this->_id, 'what3words', $annotations->what3words->words);
                }
                if (isset($annotations->timezone) && isset($annotations->timezone->offset_sec)) {
                    $this->_timezone = $annotations->timezone->offset_sec / 60;
                }
                if (isset($annotations->sun) && isset($annotations->sun->rise) && isset($annotations->sun->set) && isset($annotations->sun->rise->apparent) && isset($annotations->sun->set->apparent)) {
                    $this->_diurnal = ($annotations->sun->rise->apparent > $annotations->sun->set->apparent) ? 'day' : 'night';
                }
            }
        } else if ($geocode->status->code != 200) {
            $this->_logger->error('Problem with location lookup, someone call David.');
            $this->insertIntoLocationComponents($this->_id, 'fail', $geocode->status->code, $geocode);
        }
    }

    public function insertIntoLocationComponents($earthquakeId, $component, $name, $raw = null) {
	    $rawEscaped = null;
	    if ($raw != null) {
            $rawEncoded = json_encode($raw);
            $rawEscaped = mysqli_real_escape_string($this->_db, $rawEncoded);
        }
        $sql = "
			INSERT INTO
				locationComponents
			SET
	            earthquakeId = '$earthquakeId',
	            component = '$component',
	            name = '$name',
	            raw = '$rawEscaped'
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
        } else {
            $errors = $this->_db->error;
            $this->_logger->info('Database error - LC: ' . $errors);
            return FALSE;
        }
    }

    public function setGoogleMapsGeocode($googleMapsKey) {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $this->_latitude . ',' . $this->_longitude . '&key=' . $googleMapsKey;
        $googleMaps = new GoogleMaps($this->_logger);
        $geocode = $googleMaps->reverseGeocode($url);
        $addressComponentArray = $geocode->results[0]->address_components;
        foreach ($addressComponentArray as $component) {
            $typesArray = $component->types;
            if (in_array('country', $typesArray)) {
                $this->_country = $component->long_name;
            }
            if (in_array('administrative_area_level_1', $typesArray)) {
                $this->_adminAreaLevel1 = $component->long_name;
            }
            if (in_array('administrative_area_level_2', $typesArray)) {
                $this->_adminAreaLevel2 = $component->long_name;
            }
            if (in_array('locality', $typesArray)) {
                $this->_locality = $component->long_name;
            } else if (in_array('sublocality', $typesArray)) {
                $this->_locality = $component->long_name;
            }
            if (in_array('postal_code', $typesArray)) {
                $this->_postalcode = $component->long_name;
            }
        }
    }

    public function dumpEarthquake() {
	    var_dump($this);
    }

    public function saveEarthquake($table) {
        $sql = "
			INSERT INTO
				$table
			SET
				id = '$this->_id',
				magnitude = '$this->_magnitude',
				place = '$this->_place',
				location = '$this->_location',
				time = '$this->_time',
				date = '$this->_date',
				diurnal = '$this->_diurnal',
				updated = '$this->_updated',
				timezone = '$this->_timezone',
				url = '$this->_url',
				detailUrl = '$this->_detailUrl',
				felt = '$this->_felt',
				cdi = '$this->_cdi',
				mmi = '$this->_mmi',
				alert = '$this->_alert',
				status = '$this->_status',
				tsunami = '$this->_tsunami',
				sig = '$this->_sig',
				net = '$this->_net',
				code = '$this->_code',
				ids = '$this->_ids',
				sources = '$this->_sources',
				types = '$this->_types',
				nst = '$this->_nst',
				dmin = '$this->_dmin',
				rms = '$this->_rms',
				gap = '$this->_gap',
				magType = '$this->_magType',
				type = '$this->_type',
				title = '$this->_title',
				geometryType = '$this->_geometryType',
				latitude = '$this->_latitude',
				longitude = '$this->_longitude',
				depth = '$this->_depth'
		";

        mysqli_query($this->_db, $sql);
        $rowsAffected = mysqli_affected_rows($this->_db);

        if ($rowsAffected === 1) {
            return TRUE;
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
				magnitude = '$this->_magnitude',
				place = '$this->_place',
				time = '$this->_time',
				date = '$this->_date',
				updated = '$this->_updated',
				timezone = '$this->_timezone',
				url = '$this->_url',
				detailUrl = '$this->_detailUrl',
				felt = '$this->_felt',
				cdi = '$this->_cdi',
				mmi = '$this->_mmi',
				alert = '$this->_alert',
				status = '$this->_status',
				tsunami = '$this->_tsunami',
				sig = '$this->_sig',
				net = '$this->_net',
				code = '$this->_code',
				ids = '$this->_ids',
				sources = '$this->_sources',
				types = '$this->_types',
				nst = '$this->_nst',
				dmin = '$this->_dmin',
				rms = '$this->_rms',
				gap = '$this->_gap',
				magType = '$this->_magType',
				type = '$this->_type',
				title = '$this->_title',
				geometryType = '$this->_geometryType',
				latitude = '$this->_latitude',
				longitude = '$this->_longitude',
				depth = '$this->_depth'
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

    public function getEarthquakeExists($table) {
		$sql = "
			SELECT
				*
			FROM
				$table
			WHERE
				id = '$this->_id'
		";

		$result = mysqli_query($this->_db, $sql);
		$rows = mysqli_num_rows($result);

		if ($rows > 0) {
			return TRUE;
		} else {
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
	 * @param mixed $id
	 */
	public function setId($id)
	{
		$this->_id = $id;
	}

	/**
	 * @return mixed
	 */
	public function getMagnitude()
	{
		return $this->_magnitude;
	}

	/**
	 * @param mixed $magnitude
	 */
	public function setMagnitude($magnitude)
	{
		$this->_magnitude = $magnitude;
	}

	/**
	 * @return mixed
	 */
	public function getPlace()
	{
		return $this->_place;
	}

	/**
	 * @param mixed $place
	 */
	public function setPlace($place)
	{
		$this->_place = $place;
	}

	/**
	 * @return mixed
	 */
	public function getTime()
	{
		return $this->_time;
	}

	/**
	 * @param mixed $time
	 */
	public function setTime($time)
	{
		$this->_time = $time;
	}

	/**
	 * @return mixed
	 */
	public function getUpdated()
	{
		return $this->_updated;
	}

	/**
	 * @param mixed $updated
	 */
	public function setUpdated($updated)
	{
		$this->_updated = $updated;
	}

	/**
	 * @return mixed
	 */
	public function getTimezone()
	{
		return $this->_timezone;
	}

	/**
	 * @param mixed $timezone
	 */
	public function setTimezone($timezone)
	{
		$this->_timezone = $timezone;
	}

	/**
	 * @return mixed
	 */
	public function getUrl()
	{
		return $this->_url;
	}

	/**
	 * @param mixed $url
	 */
	public function setUrl($url)
	{
		$this->_url = $url;
	}

    /**
     * @return mixed
     */
    public function getDetailUrl()
    {
        return $this->_detailUrl;
    }

    /**
     * @param mixed $detailUrl
     */
    public function setDetailUrl($detailUrl)
    {
        $this->_detailUrl = $detailUrl;
    }

    /**
     * @return mixed
     */
    public function getFelt()
    {
        return $this->_felt;
    }

    /**
     * @param mixed $felt
     */
    public function setFelt($felt)
    {
        $this->_felt = $felt;
    }

    /**
     * @return mixed
     */
    public function getCdi()
    {
        return $this->_cdi;
    }

    /**
     * @param mixed $cdi
     */
    public function setCdi($cdi)
    {
        $this->_cdi = $cdi;
    }

    /**
     * @return mixed
     */
    public function getMmi()
    {
        return $this->_mmi;
    }

    /**
     * @param mixed $mmi
     */
    public function setMmi($mmi)
    {
        $this->_mmi = $mmi;
    }

    /**
     * @return mixed
     */
    public function getalert()
    {
        return $this->_alert;
    }

    /**
     * @param mixed $alert
     */
    public function setAlert($alert)
    {
        $this->_alert = $alert;
    }

    /**
	 * @return mixed
	 */
	public function getStatus()
	{
		return $this->_status;
	}

	/**
	 * @param mixed $status
	 */
	public function setStatus($status)
	{
		$this->_status = $status;
	}

	/**
	 * @return mixed
	 */
	public function getTsunami()
	{
		return $this->_tsunami;
	}

	/**
	 * @param mixed $tsunami
	 */
	public function setTsunami($tsunami)
	{
		$this->_tsunami = $tsunami;
	}

	/**
	 * @return mixed
	 */
	public function getSig()
	{
		return $this->_sig;
	}

	/**
	 * @param mixed $sig
	 */
	public function setSig($sig)
	{
		$this->_sig = $sig;
	}

	/**
	 * @return mixed
	 */
	public function getNet()
	{
		return $this->_net;
	}

	/**
	 * @param mixed $net
	 */
	public function setNet($net)
	{
		$this->_net = $net;
	}

	/**
	 * @return mixed
	 */
	public function getCode()
	{
		return $this->_code;
	}

	/**
	 * @param mixed $code
	 */
	public function setCode($code)
	{
		$this->_code = $code;
	}

	/**
	 * @return mixed
	 */
	public function getIds()
	{
		return $this->_ids;
	}

	/**
	 * @param mixed $ids
	 */
	public function setIds($ids)
	{
		$this->_ids = $ids;
	}

	/**
	 * @return mixed
	 */
	public function getSources()
	{
		return $this->_sources;
	}

	/**
	 * @param mixed $sources
	 */
	public function setSources($sources)
	{
		$this->_sources = $sources;
	}

	/**
	 * @return mixed
	 */
	public function getTypes()
	{
		return $this->_types;
	}

	/**
	 * @param mixed $types
	 */
	public function setTypes($types)
	{
		$this->_types = $types;
	}

	/**
	 * @return mixed
	 */
	public function getNst()
	{
		return $this->_nst;
	}

	/**
	 * @param mixed $nst
	 */
	public function setNst($nst)
	{
		$this->_nst = $nst;
	}

	/**
	 * @return mixed
	 */
	public function getDmin()
	{
		return $this->_dmin;
	}

	/**
	 * @param mixed $dmin
	 */
	public function setDmin($dmin)
	{
		$this->_dmin = $dmin;
	}

	/**
	 * @return mixed
	 */
	public function getRms()
	{
		return $this->_rms;
	}

	/**
	 * @param mixed $rms
	 */
	public function setRms($rms)
	{
		$this->_rms = $rms;
	}

	/**
	 * @return mixed
	 */
	public function getGap()
	{
		return $this->_gap;
	}

	/**
	 * @param mixed $gap
	 */
	public function setGap($gap)
	{
		$this->_gap = $gap;
	}

	/**
	 * @return mixed
	 */
	public function getMagType()
	{
		return $this->_magType;
	}

	/**
	 * @param mixed $magType
	 */
	public function setMagType($magType)
	{
		$this->_magType = $magType;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->_type;
	}

	/**
	 * @param mixed $type
	 */
	public function setType($type)
	{
		$this->_type = $type;
	}

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @return mixed
     */
    public function getGeometryType()
    {
        return $this->_geometryType;
    }

    /**
     * @param mixed $geometryType
     */
    public function setGeometryType($geometryType)
    {
        $this->_geometryType = $geometryType;
    }

    /**
	 * @return mixed
	 */
	public function getLatitude()
	{
		return $this->_latitude;
	}

	/**
	 * @param mixed $latitude
	 */
	public function setLatitude($latitude)
	{
		$this->_latitude = $latitude;
	}

	/**
	 * @return mixed
	 */
	public function getLongitude()
	{
		return $this->_longitude;
	}

	/**
	 * @param mixed $longitude
	 */
	public function setLongitude($longitude)
	{
		$this->_longitude = $longitude;
	}

	/**
	 * @return mixed
	 */
	public function getDepth()
	{
		return $this->_depth;
	}

	/**
	 * @param mixed $depth
	 */
	public function setDepth($depth)
	{
		$this->_depth = $depth;
	}
}