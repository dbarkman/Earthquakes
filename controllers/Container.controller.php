<?php

/**
 * Container.controller.php
 * Description:
 *
 */

class Container
{
	static protected $shared = array();

	private $_logFile;
	private $_logLevel;

	public function __construct()
	{
		$properties = new EarthquakesProperties();
		$this->_logFile = $properties->getLogFile();
		$this->_logLevel = $properties->getLogLevel();
	}

	public function getLogger()
	{
		if (isset(self::$shared['logger'])) {
			return self::$shared['logger'];
		}

		$logger = new Logger($this->_logLevel, $this->_logFile);

		return self::$shared['logger'] = $logger;
	}

	public function getMongoDBConnect()
	{
		if (isset(self::$shared['mongoDBConnect'])) {
			return self::$shared['mongoDBConnect'];
		}

		global $earthquakesMongoDBLogin;
		$mongoDBConnect = new MongoDBConnect($earthquakesMongoDBLogin['username'], $earthquakesMongoDBLogin['password'], $earthquakesMongoDBLogin['server'], $earthquakesMongoDBLogin['database'], $earthquakesMongoDBLogin['collection']);

		return self::$shared['mongoDBConnect'] = $mongoDBConnect->getCollection();
	}
}
