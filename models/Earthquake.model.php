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
	private $_latitude;
	private $_longitude;
	private $_depth;

	public function __construct($logger, $db, $earthquake) {
		$this->_logger = $logger;
		$this->_db = $db;
		$this->_id = $earthquake->id;
		$this->_magnitude = $earthquake->properties->mag;
		$this->_place = $earthquake->properties->place;
		$this->_time = $earthquake->properties->time;
		$this->_updated = $earthquake->properties->updated;
		$this->_timezone = $earthquake->properties->tz;
		$this->_url = $earthquake->properties->url;
		$this->_detailUrl = $earthquake->properties->detail;
		$this->_status = $earthquake->properties->status;
		$this->_tsunami = $earthquake->properties->tsunami;
		$this->_sig = $earthquake->properties->sig;
		$this->_net = $earthquake->properties->net;
		$this->_code = $earthquake->properties->code;
		$this->_ids = $earthquake->properties->ids;
		$this->_sources = $earthquake->properties->sources;
		$this->_types = $earthquake->properties->types;
		$this->_nst = $earthquake->properties->nst;
		$this->_dmin = $earthquake->properties->dmin;
		$this->_rms = $earthquake->properties->rms;
		$this->_gap = $earthquake->properties->gap;
		$this->_magType = $earthquake->properties->magType;
		$this->_type = $earthquake->properties->type;
		$this->_title = $earthquake->properties->title;
		$this->_latitude = $earthquake->geometry->coordinates[1];
		$this->_longitude = $earthquake->geometry->coordinates[0];
		$this->_depth = $earthquake->geometry->coordinates[2];
	}

	public function saveEarthquake() {
		$sql = "
			INSERT INTO
				earthquakes
			SET
				id = '$this->_id',
				magnitude = '$this->_magnitude',
				place = '$this->_place',
				time = '$this->_time',
				updated = '$this->_updated',
				timezone = '$this->_timezone',
				url = '$this->_url',
				detailUrl = '$this->_detailUrl',
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
				latitude = '$this->_latitude',
				longitude = '$this->_longitude',
				depth = '$this->_depth'
		";

		mysqli_query($this->_db, $sql);
		$rowsAffected = mysqli_affected_rows($this->_db);

		if ($rowsAffected === 1) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function getEarthquakeExists() {
		$sql = "
			SELECT
				*
			FROM
				earthquakes
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