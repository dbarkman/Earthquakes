<?php

/**
 * Apigee.controller.php
 * Description:
 *
 */

class Apigee extends Curl
{

	private $_logger;
	private $_accessToken;

	public function __construct($logger) {
		parent::__construct($logger);

		$this->_logger = $logger;
	}

	public function getAuthToken()
	{
		global $readeyAPILogin;

		$baseUrl = 'https://api.usergrid.com/reallysimpleapps/readey/token';
		$query = '?grant_type=password&username=' . $readeyAPILogin['username'] . '&password=' . $readeyAPILogin['password'];
		$url = $baseUrl . $query;

		$token = json_decode(self::runCurl('GET', $url));
		if (isset($token->access_token)) {
			$this->_accessToken = $token->access_token;
			return TRUE;
		} else {
			return FALSE;
		}
	}

	public function getReadLogs($when = null, $created = null)
	{
		$baseUrl = 'https://api.usergrid.com/reallysimpleapps/readey/readlogs';
		$query = '';
		if (isset($when) && isset($created)) {
			if ($when === 'newer') {
				$query .= 'ql=select%20*%20where%20created>' . $created;
			} else if ($when === 'older') {
				$query .= 'ql=select%20*%20where%20created<' . $created;
			} else if ($when === 'equal') {
				$query .= 'ql=select%20*%20where%20created=' . $created;
			}
		}
		$url = $baseUrl . '?' . $query;
		$this->_logger->debug('ReadLog URL: ' . $url);

		$readLogs = json_decode(self::runCurl('GET', $url));
		return $readLogs;
	}

	public function getUsers($when = null, $created = null)
	{
		$baseUrl = 'https://api.usergrid.com/reallysimpleapps/readey/users';
		$query = '';
		if (isset($when) && isset($created)) {
			if ($when === 'newer') {
				$query .= '?ql=select%20*%20where%20created>' . $created;
			} else if ($when === 'older') {
				$query .= '?ql=select%20*%20where%20created<' . $created;
			} else if ($when === 'equal') {
				$query .= '?ql=select%20*%20where%20created=' . $created;
			}
		}
		$url = $baseUrl . $query;

		$url .= (strstr($url, '?') !== FALSE) ? '&' : '?';
		$url .= 'access_token=' . $this->_accessToken;
		$this->_logger->debug('Users URL: ' . $url);

		$users = json_decode(self::runCurl('GET', $url));
		return $users;
	}

	public function getSupportTickets($when = null, $created = null)
	{
		$baseUrl = 'https://api.usergrid.com/reallysimpleapps/readey/supporttickets';
		$query = '';
		if (isset($when) && isset($created)) {
			if ($when === 'newer') {
				$query .= '?ql=select%20*%20where%20created>' . $created;
			} else if ($when === 'older') {
				$query .= '?ql=select%20*%20where%20created<' . $created;
			} else if ($when === 'equal') {
				$query .= '?ql=select%20*%20where%20created=' . $created;
			}
		}
		$url = $baseUrl . $query;

		$url .= (strstr($url, '?') !== FALSE) ? '&' : '?';
		$url .= 'access_token=' . $this->_accessToken;
		$this->_logger->debug('SupportTickets URL: ' . $url);

		$supportTickets = json_decode(self::runCurl('GET', $url));
		return $supportTickets;
	}

	public function getFeedbacks($when = null, $created = null)
	{
		$baseUrl = 'https://api.usergrid.com/reallysimpleapps/readey/feedbacks';
		$query = '';
		if (isset($when) && isset($created)) {
			if ($when === 'newer') {
				$query .= '?ql=select%20*%20where%20created>' . $created;
			} else if ($when === 'older') {
				$query .= '?ql=select%20*%20where%20created<' . $created;
			} else if ($when === 'equal') {
				$query .= '?ql=select%20*%20where%20created=' . $created;
			}
		}
		$url = $baseUrl . $query;

		$url .= (strstr($url, '?') !== FALSE) ? '&' : '?';
		$url .= 'access_token=' . $this->_accessToken;
		$this->_logger->debug('Feedbacks URL: ' . $url);

		$feedbacks = json_decode(self::runCurl('GET', $url));
		return $feedbacks;
	}

	protected function runCurl($requestMethod, $url)
	{
		return parent::runCurl($requestMethod, $url);
	}
}
