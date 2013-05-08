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
		$properties = new ReadeyProperties();
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

	public function getMySqlConnect()
	{
		if (isset(self::$shared['mySqlConnect'])) {
			return self::$shared['mySqlConnect'];
		}

		global $readeyDB;
		$mySqlConnect = new MySQLConnect($readeyDB['sqlHost'], $readeyDB['sqlUser'], $readeyDB['sqlPassword'], $readeyDB['sqlDatabase']);

		return self::$shared['mySqlConnect'] = $mySqlConnect;
	}

	public function getValidation()
	{
		if (isset(self::$shared['validation'])) {
			return self::$shared['validation'];
		}

		$validation = new ReadeyFrameworkValidation($this->getLogger());

		return self::$shared['validation'] = $validation;
	}
}
