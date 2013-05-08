<?php

/**
 * MobileAPIValidation.class.php
 * Description:
 *
 */

class ReadeyFrameworkValidation
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

	public function validateAPIKey()
	{
		$_REQUEST['key'] = $this->_validate->sanitizeAPIKey($_REQUEST['key']);
		$this->validateKey(TRUE);
	}

	public function validateItemsCategory()
	{
		$_REQUEST['category'] = $this->_validate->sanitizeCategory($_REQUEST['category']);
		$this->validateCategory(TRUE);
	}

	private function validateKey($required) {
		if (isset($_REQUEST['key']) && !empty($_REQUEST['key'])) {
			$this->_logger->debug('Checking API Key: ' . $_REQUEST['key']);
			$returnError = $this->_validate->validateAPIKey($_REQUEST['key']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'API Key', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'API Key', '');
		}
	}

	private function validateCategory($required) {
		if (isset($_REQUEST['category']) && !empty($_REQUEST['category'])) {
			$this->_logger->debug('Checking category: ' . $_REQUEST['category']);
			$returnError = $this->_validate->validateCategory($_REQUEST['category']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'category', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'category', '');
		}
	}

	private function validateUsername($required) {
		if (isset($_REQUEST['username']) && !empty($_REQUEST['username'])) {
			$this->_logger->debug('Checking username: ' . $_REQUEST['username']);
			$returnError = $this->_validate->validateUsername($_REQUEST['username']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'username', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'username', '');
		}
	}

	private function validatePassword($required) {
		if (isset($_REQUEST['password']) && !empty($_REQUEST['password'])) {
			$this->_logger->debug('Checking password: ********');
			$returnError = $this->_validate->validatePassword($_REQUEST['password']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'password', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'password', '');
		}
	}

	private function validateDate($required) {
		if (isset($_REQUEST['date']) && !empty($_REQUEST['date'])) {
			$this->_logger->debug('Checking date: ' . $_REQUEST['date']);
			$returnError = $this->_validate->validateDate($_REQUEST['date']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'date', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'date', '');
		}
	}

	private function validateEmail($required) {
		if (isset($_REQUEST['email' ]) && !empty($_REQUEST['email'])) {
			$this->_logger->debug('Checking email: ' . $_REQUEST['email']);
			$returnError = $this->_validate->validateEmail($_REQUEST['email']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'email', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'email', '');
		}
	}

	private function validateFirstName($required) {
		if (isset($_REQUEST['first_name' ]) && !empty($_REQUEST['first_name'])) {
			$this->_logger->debug('Checking first name: ' . $_REQUEST['first_name']);
			$returnError = $this->_validate->validateName(rtrim($_REQUEST['first_name']));
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'first_name', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'first_name', '');
		}
	}

	private function validateLastName($required) {
		if (isset($_REQUEST['last_name' ]) && !empty($_REQUEST['last_name'])) {
			$this->_logger->debug('Checking last name: ' . $_REQUEST['last_name']);
			$returnError = $this->_validate->validateName($_REQUEST['last_name']);
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'last_name', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'last_name', '');
		}
	}

	private function validateCost($required) {
		if (isset($_REQUEST['cost' ]) && !empty($_REQUEST['cost'])) {
			$this->_logger->debug('Checking cost: ' . $_REQUEST['cost']);
			$returnError = $this->_validate->validateMoney(trim($_REQUEST['cost']));
			if (!empty($returnError)) $this->reportVariableErrors('invalid', 'cost', $returnError);
		} else if ($required === TRUE) {
			$this->reportVariableErrors('missing', 'cost', '');
		}
	}

	private function reportVariableErrors($type, $variable, $returnError) {
		if ($type === 'invalid') {
			$this->_errorCode = 'invalidParameter';
			$this->_errors[] = $returnError . ' value for ' . $variable . ' (' . $_REQUEST[$variable] . ')';
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
