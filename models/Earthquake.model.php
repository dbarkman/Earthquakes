<?php

/**
 * Created by PhpStorm.
 * User: David
 * Date: 1/22/16
 * Time: 12:19 PM
 */
class Earthquake
{
	private $_id;
	private $_magnitude;
	private $_place;
	private $_time;
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

	public function saveEarthquake($table) {
		$sql = "
			INSERT INTO
				$table
			SET
				id = '$this->_id',
				magnitude = '$this->_magnitude',
				place = '$this->_place',
				time = '$this->_time',
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
            $this->_logger->info('Database error: ' . $errors);
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