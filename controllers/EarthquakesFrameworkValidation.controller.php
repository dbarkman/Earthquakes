<?php

/**
 * EarthquakesFrameworkValidation.class.php
 * Description:
 *
 */

class EarthquakesFrameworkValidation
{
	private $_validate;
	private $_logger;
	private $_errorCode = '';
	private $_errors = array();
	private $_friendlyError = '';
	private $_errorCount = 0;

	public function __construct($logger)
	{
		//setup for log entries
		$this->_logger = $logger;

		$this->_validate = new FrameworkValidation();
	}

	public function validateAPICommon()
	{
		if (isset($_REQUEST['key'])) $_REQUEST['key'] = $this->_validate->sanitizeAPIKey($_REQUEST['key']);
		if (isset($_REQUEST['user'])) $_REQUEST['user'] = $this->_validate->sanitizeUUID($_REQUEST['user']);
		if (isset($_REQUEST['appVersion'])) $_REQUEST['appVersion'] = $this->_validate->sanitizeFloat($_REQUEST['appVersion']);
		if (isset($_REQUEST['device'])) $_REQUEST['device'] = $this->_validate->sanitizeTextWithSpace($_REQUEST['device']);
		if (isset($_REQUEST['machine'])) $_REQUEST['machine'] = $this->_validate->sanitizeMachineName($_REQUEST['machine']);
		if (isset($_REQUEST['osVersion'])) $_REQUEST['osVersion'] = $this->_validate->sanitizeFloat($_REQUEST['osVersion']);
		$this->validateKey(TRUE);
		$this->validateUser(FALSE);
		$this->validateAppVersion(FALSE);
		$this->validateDevice(FALSE);
		$this->validateMachine(FALSE);
		$this->validateOSVersion(FALSE);
	}

	public function validateGetEarthquakes() {
	    $queryStringArray = explode('&', $_SERVER['QUERY_STRING']);
	    $parameterArray = array();
	    foreach ($queryStringArray as $query) {
	        $queryArray = explode('=', $query);
	        $parameterArray[$queryArray[0]] = $queryArray[1];
        }
        $queryStringKeys = array_keys($parameterArray);
	    foreach ($queryStringKeys as $key) {
            $this->validateParameters($key);
        }
	    $queryStringValues = array_values($parameterArray);
	    foreach ($queryStringValues as $value) {
	        $this->validateParameterValues($value);
        }
    }

    private function validateKey($required) {
        if (isset($_REQUEST['key']) && !empty($_REQUEST['key'])) {
            $this->_logger->debug('Checking API Key: ' . $_REQUEST['key']);
            $returnError = $this->_validate->validateAPIKey($_REQUEST['key']);
            if (!empty($returnError)) $this->reportVariableErrors('invalid', 'key', $returnError);
        } else if ($required === TRUE) {
            $this->reportVariableErrors('missing', 'API Key', '');
        }
    }

    private function validateParameters($parameter) {
        $this->_logger->info('Checking Parameter Key: ' . $parameter);
        $returnError = $this->_validate->validateParameterKeys($parameter);
        if (!empty($returnError)) $this->reportVariableErrors('invalid', $parameter, $returnError);
    }

    private function validateParameterValues($value) {
        $this->_logger->info('Checking Parameter Value: ' . $value);
        $returnError = $this->_validate->validateParameterValues($value);
        if (!empty($returnError)) $this->reportValueErrors($returnError, $value);
    }

    private function reportValueErrors($returnError, $value) {
        if ($returnError === 'Blank') {
            $this->_errorCode = 'missingParameter';
            $this->_errors[] = $returnError . ' value for one of the parameters.';
            $this->_friendlyError = 'Invalid value or format for one of the submitted parameters.';
            $this->_errorCount++;
        } else if ($returnError === 'Illegal') {
            $this->_errorCode = 'invalidParameter';
            $this->_errors[] = $returnError . ' value: ' . $value . '.';
            $this->_friendlyError = 'Invalid value or format for one of the submitted parameters.';
            $this->_errorCount++;
        } else if ($returnError === 'Invalid') {
            $this->_errorCode = 'invalidParameter';
            $this->_errors[] = $returnError . ' value: ' . $value . '.';
            $this->_friendlyError = 'Invalid value or format for one of the submitted parameters.';
            $this->_errorCount++;
        }
    }

    private function validateUser($required) {
		if (isset($_REQUEST['user']) && !empty($_REQUEST['user'])) {
			$this->_logger->debug('Checking user: ' . $_REQUEST['user']);
			$returnError = $this->_validate->validateUUID($_REQUEST['user']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'user', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'user', '');
		}
	}

	private function validateAppVersion($required) {
		if (isset($_REQUEST['appVersion']) && !empty($_REQUEST['appVersion'])) {
			$this->_logger->debug('Checking appVersion: ' . $_REQUEST['appVersion']);
			$returnError = $this->_validate->validateVersionNumber($_REQUEST['appVersion']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'appVersion', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'appVersion', '');
		}
	}

	private function validateDevice($required) {
		if (isset($_REQUEST['device']) && !empty($_REQUEST['device'])) {
			$this->_logger->debug('Checking device: ' . $_REQUEST['device']);
			$returnError = $this->_validate->validateTextWithSpace($_REQUEST['device']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'device', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'device', '');
		}
	}

	private function validateMachine($required) {
		if (isset($_REQUEST['machine']) && !empty($_REQUEST['machine'])) {
			$this->_logger->debug('Checking machine: ' . $_REQUEST['machine']);
			$returnError = $this->_validate->validateMachineName($_REQUEST['machine']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'machine', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'machine', '');
		}
	}

	private function validateOSVersion($required) {
		if (isset($_REQUEST['osVersion']) && !empty($_REQUEST['osVersion'])) {
			$this->_logger->debug('Checking osVersion: ' . $_REQUEST['osVersion']);
			$returnError = $this->_validate->validateVersionNumber($_REQUEST['osVersion']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'osVersion', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'osVersion', '');
		}
	}

	private function reportVariableErrors($type, $variable, $returnError) {
		if ($type === 'invalid') {
			$this->_errorCode = 'invalidParameter';
			$this->_errors[] = $returnError . ' value for ' . $variable . ' (' . (isset($_REQUEST[$variable]) ? $_REQUEST[$variable] : '') . ')';
			$this->_friendlyError = 'Invalid value or format for one of the submitted parameters.';
			$this->_errorCount++;
		} else if ($type === 'missing') {
			$this->_errorCode = 'missingParameter';
			$this->_errors[] = 'Required parameter ' . $variable . ' is missing from request.';
			$this->_friendlyError = 'A required parameter is missing from this request.';
			$this->_errorCount++;
		}
	}

	public function getErrorCode() {
		return $this->_errorCode;
	}

	public function getErrors() {
		return $this->_errors;
	}

	public function getFriendlyError() {
		return $this->_friendlyError;
	}

	public function getErrorCount() {
		return $this->_errorCount;
	}
}
