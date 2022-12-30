<?php

/**
 * EarthquakesProperties.class.php
 * Description:
 *
 */

class EarthquakesProperties extends Properties
{
	private $_propertiesFile;
	const PROP_LOGFILE = "earthquakes.log.file";
	const PROP_LOGLEVEL = "earthquakes.log.level";
	const PROP_ACTION_STORE = "earthquakes.action.store";
	const PROP_ACTION_NOTIFY = "earthquakes.action.notify";
	const PROP_ACTION_SENDPUSH = "earthquakes.action.sendPush";
	const PROP_URL_HOUR = "earthquakes.url.hour";
	const PROP_URL_DAY = "earthquakes.url.day";
    const PROP_URL_QUERY = "earthquakes.url.query";
    const PROP_URL_COUNT = "earthquakes.url.count";
    const PROP_KEY_GOOGLEMAPS = "earthquakes.key.googlemaps";
    const PROP_KEY_OPENCAGE = "earthquakes.key.opencage";
    const PROP_KEY_BIGDATACLOUD = "earthquakes.key.bigdatacloud";

	public function __construct()
	{
		$this->_propertiesFile = dirname(__FILE__) . '/../config/earthquakes.properties';
		$this->load($this->_propertiesFile);
	}

	public function load($file)
	{
		parent::load($file);
	}

	public function save($file)
	{
		parent::save($file);
	}

	public function getLogFile()
	{
		return parent::getProperty(self::PROP_LOGFILE);
	}

	public function getLogLevel()
	{
		$string = $this->getLogLevelString();
		return Logger::getLevelInt($string);
	}

	public function getLogLevelString()
	{
		$string = $this->getProperty(self::PROP_LOGLEVEL);
		$level = Logger::getLevelInt($string);
		if ($level != NULL) {
			return $string;
		}
		return "INFO";
	}

	public function setLogFile($value)
	{
		$this->setProperty(self::PROP_LOGFILE, $value);
	}

	public function setLogLevel($value)
	{
		$this->setLogLevelString(Logger::getLevelString($value));
	}

	public function setLogLevelString($value)
	{
		$this->setProperty(self::PROP_LOGLEVEL, $value);
	}

	public function getStoreValue()
	{
		return parent::getProperty(self::PROP_ACTION_STORE);
	}

    public function getNotifyValue()
    {
        return parent::getProperty(self::PROP_ACTION_NOTIFY);
    }

    public function getSendPushValue()
    {
        return parent::getProperty(self::PROP_ACTION_SENDPUSH);
    }

    public function getUrlHour()
    {
        return parent::getProperty(self::PROP_URL_HOUR);
    }

    public function getUrlDay()
    {
        return parent::getProperty(self::PROP_URL_DAY);
    }

    public function getUrlQuery()
    {
        return parent::getProperty(self::PROP_URL_QUERY);
    }

    public function getUrlCount()
    {
        return parent::getProperty(self::PROP_URL_COUNT);
    }

    public function getKeyGoogleMaps()
    {
        return parent::getProperty(self::PROP_KEY_GOOGLEMAPS);
    }

    public function getKeyOpenCage()
    {
        return parent::getProperty(self::PROP_KEY_OPENCAGE);
    }

    public function getKeyBigDataCloud()
    {
        return parent::getProperty(self::PROP_KEY_BIGDATACLOUD);
    }
}
